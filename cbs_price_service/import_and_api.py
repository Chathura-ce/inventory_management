# import_and_api.py
# -----------------
# Combined script for:
# 1) Importing daily PDF data into a SQLite (or other) database as cache
# 2) Exposing a FastAPI HTTP API to query by date (with caching)

import os
import requests
import datetime
import tempfile
import pandas as pd
from bs4 import BeautifulSoup
import camelot  # pip install camelot-py[cv]

from sqlalchemy import create_engine, Column, Integer, Date, Float
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List

# ─── CONFIG ─────────────────────────────────────────────────────────────

# Database URL (fallback to SQLite file)
DATABASE_URL = os.getenv("DATABASE_URL", "sqlite:///prices.db")
engine = create_engine(DATABASE_URL, connect_args={"check_same_thread": False})
SessionLocal = sessionmaker(bind=engine)
Base = declarative_base()

# CBSL site
CBSL_BASE   = "https://www.cbsl.gov.lk"
REPORT_PATH = "/en/statistics/economic-indicators/price-report"

# Map displayed names → product_id
PRODUCT_MAP = {"Beans":1, "Nadu":2, "Egg":3, "Salaya":4}

# ─── DB MODEL ────────────────────────────────────────────────────────────
class Price(Base):
    __tablename__ = "prices"
    id = Column(Integer, primary_key=True, index=True)
    product_id = Column(Integer, index=True)
    price_date = Column(Date, index=True)
    narahenpita_retail = Column(Float)

Base.metadata.create_all(bind=engine)

# ─── HELPERS ─────────────────────────────────────────────────────────────

def fetch_report_page():
    resp = requests.get(CBSL_BASE + REPORT_PATH, timeout=30)
    resp.raise_for_status()
    return resp.text


def find_pdf_url_for_date(html: str, report_date: datetime.date) -> str:
    date_str = f"{report_date.day} {report_date.strftime('%B')} {report_date.year}"
    soup = BeautifulSoup(html, "html.parser")
    links = soup.select("#block-views-price-report-block-1 .view-content a[href$='.pdf']")
    for a in links:
        if date_str in a.get_text(strip=True):
            href = a["href"]
            return href if href.startswith("http") else CBSL_BASE + href
    raise RuntimeError(f"No PDF link for {date_str}")


def download_pdf(url: str) -> str:
    r = requests.get(url, timeout=60)
    r.raise_for_status()
    fd, path = tempfile.mkstemp(suffix='.pdf')
    with os.fdopen(fd, 'wb') as f:
        f.write(r.content)
    return path


def extract_retail_prices(pdf_path: str, report_date: datetime.date) -> pd.DataFrame:
    # Extract Narahenpita retail prices using Camelot stream mode
    df_pdf = None
    header_row = None
    for page, hdr in [("2",2), ("1",1)]:
        try:
            tables = camelot.read_pdf(pdf_path, pages=page, flavor='stream')
            if tables.n > 0:
                df_pdf = tables[0].df
                header_row = hdr
                break
        except Exception:
            pass
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
    data = data[data['item'].str.strip().astype(bool)]
    data['narahenpita_retail_today'] = pd.to_numeric(
        data['narahenpita_retail_today'].str.replace(',', ''), errors='coerce'
    )
    data = data[data['item'].isin(PRODUCT_MAP.keys())]
    df = pd.DataFrame({
        'product_id': data['item'].map(PRODUCT_MAP),
        'price_date': [report_date]*len(data),
        'narahenpita_retail': data['narahenpita_retail_today'],
    })
    return df


def import_for_date(report_date: datetime.date) -> List[Price]:
    html = fetch_report_page()
    pdf_url = find_pdf_url_for_date(html, report_date)
    pdf_path = download_pdf(pdf_url)
    df = extract_retail_prices(pdf_path, report_date)
    os.remove(pdf_path)

    db = SessionLocal()
    imported = []
    for row in df.itertuples(index=False):
        existing = db.query(Price).filter_by(
            product_id=row.product_id, price_date=row.price_date
        ).one_or_none()
        if existing:
            existing.narahenpita_retail = row.narahenpita_retail
            imported.append(existing)
        else:
            new = Price(
                product_id=row.product_id,
                price_date=row.price_date,
                narahenpita_retail=row.narahenpita_retail
            )
            db.add(new)
            imported.append(new)
    db.commit()
    db.close()
    return imported

# ─── FASTAPI APP ──────────────────────────────────────────────────────────
app = FastAPI()

class PriceSchema(BaseModel):
    product_id: int
    price_date: datetime.date
    narahenpita_retail: float

@app.get('/prices/', response_model=List[PriceSchema])
def get_prices(date: str):
    """Get prices for a given date, using cache or importing if missing."""
    try:
        d = datetime.datetime.strptime(date, '%Y-%m-%d').date()
    except ValueError:
        raise HTTPException(400, 'Invalid date format')
    db = SessionLocal()
    rows = db.query(Price).filter_by(price_date=d).all()
    db.close()
    if not rows:
        # not cached → fetch, store, then return
        try:
            imported = import_for_date(d)
            return imported
        except Exception as e:
            raise HTTPException(500, str(e))
    return rows

# ─── ENTRYPOINT ──────────────────────────────────────────────────────────
if __name__ == '__main__':
    import uvicorn
    uvicorn.run(app, host='0.0.0.0', port=8002)

# uvicorn import_and_api:app --reload --host 0.0.0.0 --port 8002