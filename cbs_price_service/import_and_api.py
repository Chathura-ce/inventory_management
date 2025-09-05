# import_and_api.py
# -----------------
# 1) Import daily PDF data into a SQLite cache
# 2) Expose a FastAPI HTTP API to query by date (with caching)
#    – If the requested date has no report, skip it and import every later
#      available report (3, 4, 5 August, etc).

import os
import requests
import datetime
import tempfile
import pandas as pd
from bs4 import BeautifulSoup
import camelot

from sqlalchemy import create_engine, Column, Integer, Date, Float
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker

import logging
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List, Dict, Any

# ─── CONFIG ─────────────────────────────────────────────────────────────
DATABASE_URL = os.getenv("DATABASE_URL", "sqlite:///prices.db")
engine       = create_engine(DATABASE_URL, connect_args={"check_same_thread": False})
SessionLocal = sessionmaker(bind=engine)
Base         = declarative_base()

CBSL_BASE   = "https://www.cbsl.gov.lk"
REPORT_PATH = "/en/statistics/economic-indicators/price-report"
PRODUCT_MAP = {"Beans":1, "Nadu":2, "Egg":3, "Salaya":4}

# ─── DB MODEL ────────────────────────────────────────────────────────────
class Price(Base):
    __tablename__ = "prices"
    id                 = Column(Integer, primary_key=True, index=True)
    product_id         = Column(Integer, index=True)
    price_date         = Column(Date,    index=True)
    narahenpita_retail = Column(Float)

Base.metadata.create_all(bind=engine)

# ─── LOGGER ──────────────────────────────────────────────────────────────
uvicorn_logger = logging.getLogger("uvicorn.error")
uvicorn_logger.setLevel(logging.INFO)

# ─── HELPERS ─────────────────────────────────────────────────────────────
def fetch_report_page() -> str:
    resp = requests.get(CBSL_BASE + REPORT_PATH, timeout=30)
    resp.raise_for_status()
    return resp.text

def find_pdf_url_for_date(html: str, report_date: datetime.date) -> str:
    date_str = f"{report_date.day} {report_date.strftime('%B')} {report_date.year}"
    soup     = BeautifulSoup(html, "html.parser")
    links    = soup.select("#block-views-price-report-block-1 .view-content a[href$='.pdf']")
    for a in links:
        text = a.get_text(strip=True)
        href = a["href"]
        full = href if href.startswith("http") else CBSL_BASE + href
        uvicorn_logger.info(f"[DEBUG LINKS] text={text!r}, url={full}")
        if date_str in text:
            uvicorn_logger.info(f"[MATCHED] → {full}")
            return full
    raise RuntimeError(f"No PDF link for {date_str}")

def download_pdf(url: str) -> str:
    r = requests.get(url, timeout=60)
    r.raise_for_status()
    fd, path = tempfile.mkstemp(suffix=".pdf")
    with os.fdopen(fd, "wb") as f:
        f.write(r.content)
    return path

def extract_retail_prices(pdf_path: str, report_date: datetime.date) -> pd.DataFrame:
    df_pdf     = None
    header_row = None
    for page, hdr in [("2",2), ("1",1)]:
        try:
            tables = camelot.read_pdf(pdf_path, pages=page, flavor="stream")
            if tables.n > 0:
                df_pdf, header_row = tables[0].df, hdr
                break
        except Exception:
            continue
    if df_pdf is None:
        raise RuntimeError("No table found in PDF")

    data = df_pdf.iloc[header_row:, :12].copy()
    data.columns = [
        "item","unit",
        "pettah_wholesale_yesterday","pettah_wholesale_today",
        "dambulla_wholesale_yesterday","dambulla_wholesale_today",
        "pettah_retail_yesterday","pettah_retail_today",
        "dambulla_retail_yesterday","dambulla_retail_today",
        "narahenpita_retail_yesterday","narahenpita_retail_today",
    ]
    data = data[data["item"].str.strip().astype(bool)]
    data["narahenpita_retail_today"] = pd.to_numeric(
        data["narahenpita_retail_today"].str.replace(",", ""),
        errors="coerce"
    )
    data = data[data["item"].isin(PRODUCT_MAP.keys())]

    return pd.DataFrame({
        "product_id":        data["item"].map(PRODUCT_MAP),
        "price_date":        [report_date] * len(data),
        "narahenpita_retail": data["narahenpita_retail_today"],
    })

def import_for_date(report_date: datetime.date) -> List[Dict[str, Any]]:
    """Fetch, parse, store, and return a day's prices as plain dicts."""
    html     = fetch_report_page()
    pdf_url  = find_pdf_url_for_date(html, report_date)
    pdf_path = download_pdf(pdf_url)
    df       = extract_retail_prices(pdf_path, report_date)
    os.remove(pdf_path)

    # write into DB
    db = SessionLocal()
    for row in df.itertuples(index=False):
        existing = db.query(Price).filter_by(
            product_id=row.product_id, price_date=row.price_date
        ).one_or_none()
        if existing:
            existing.narahenpita_retail = row.narahenpita_retail
        else:
            db.add(Price(
                product_id=row.product_id,
                price_date=row.price_date,
                narahenpita_retail=row.narahenpita_retail
            ))
    db.commit()
    db.close()

    # return plain dicts
    return df.to_dict("records")

# ─── FASTAPI APP ──────────────────────────────────────────────────────────
app = FastAPI()

class PriceSchema(BaseModel):
    product_id:        int
    price_date:        datetime.date
    narahenpita_retail: float

@app.get("/prices", response_model=List[PriceSchema])
def get_prices(date: str):
    # 1) parse
    try:
        requested = datetime.datetime.strptime(date, "%Y-%m-%d").date()
    except ValueError:
        raise HTTPException(400, "Invalid date format")

    # 2) check cache
    db   = SessionLocal()
    rows = db.query(Price).filter_by(price_date=requested).all()
    db.close()
    if rows:
        return [
            {"product_id": r.product_id,
             "price_date": r.price_date,
             "narahenpita_retail": r.narahenpita_retail}
            for r in rows
        ]

    # 3) try import exactly that date
    try:
        return import_for_date(requested)
    except RuntimeError as e:
        uvicorn_logger.info(f"[SKIP {requested}] {e}")

    # 4) fallback → all *later* dates
    html  = fetch_report_page()
    soup  = BeautifulSoup(html, "html.parser")
    links = soup.select("#block-views-price-report-block-1 .view-content a[href$='.pdf']")

    later_dates = set()
    for a in links:
        text = a.get_text(strip=True)
        part = text.split("–")[-1] if "–" in text else text.split("-")[-1]
        part = part.strip()
        try:
            dt = datetime.datetime.strptime(part, "%d %B %Y").date()
        except ValueError:
            continue
        if dt > requested:
            later_dates.add(dt)

    all_data: List[Dict[str, Any]] = []
    for dt in sorted(later_dates):
        try:
            all_data.extend(import_for_date(dt))
        except Exception as e:
            uvicorn_logger.info(f"[SKIP {dt}] {e}")
            continue

    return all_data

# ─── ENTRYPOINT ──────────────────────────────────────────────────────────
if __name__ == "__main__":
    import uvicorn
    uvicorn.run(
        "import_and_api:app",
        host="0.0.0.0",
        port=8003,
        reload=True,
        log_level="info"
    )
