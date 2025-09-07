📊 Inventory & Price Forecasting (Laravel + FastAPI)

This project combines Laravel (PHP) and FastAPI (Python) to build an integrated Inventory Management and Commodity Price Forecasting System for Sri Lanka.

It ingests daily retail prices from CBSL (Central Bank of Sri Lanka), maintains inventory data, and generates 30-day forecasts using a global LSTM model served by FastAPI.

🚀 Features

🔐 Authentication – via Laravel Breeze/Jetstream (or chosen stack)

📦 Products & Historical Prices – stored in historical_prices table

📈 Forecast Page – interactive ApexCharts with:

Last 60 days actuals

Next 30 days forecast

Smooth lines, 2-decimal formatting, cutoff “Forecast” band

🤖 FastAPI Model Service – single global LSTM (730→30) exposed at /predict & /health

⏰ Scheduled CBSL Data Ingest – runs daily at 06:00

🧾 Reports Module (if enabled) – Stock Balance, Movement, and Sales Summary

🏗️ Architecture
Laravel (PHP) ── HTTP ──> FastAPI (Python/TF) ── loads LSTM (models/lstm_730in30out.h5)
│
├── DB: historical_prices (retail prices per item/day)
└── UI: ApexCharts (actuals + forecast, 2-decimal formatting)

📋 Requirements

Backend

PHP 8.2+, Composer

MySQL/MariaDB

Python 3.10+, FastAPI, TensorFlow 2.x

Frontend

Node 18+, NPM

⚡ Quick Start
1. Laravel App
   git clone <your-repo>
   cd <repo>

cp .env.example .env
composer install
php artisan key:generate

# configure DB in .env, then:
php artisan migrate --seed   # if seeders exist
npm install
npm run build   # or: npm run dev


.env essentials:

APP_URL=http://127.0.0.1:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory
DB_USERNAME=root
DB_PASSWORD=secret

# FastAPI (no trailing /predict)
FASTAPI_URL=http://127.0.0.1:8002


Add mapping in config/services.php:

'fastapi' => ['url' => env('FASTAPI_URL', 'http://127.0.0.1:8002')],


Clear caches:

php artisan config:clear && php artisan cache:clear

2. FastAPI Server

Layout:

fastapi-server/
├─ main.py
├─ model_wrapper.py
└─ models/
└─ lstm_730in30out.h5


Run:

cd fastapi-server
python -m venv .venv && source .venv/bin/activate   # Windows: .venv\Scripts\activate
pip install fastapi uvicorn pandas numpy tensorflow
uvicorn main:app --host 0.0.0.0 --port 8002 --reload


Health Check:

GET http://127.0.0.1:8002/health
→ {"ok": true, ...}

🗄️ Data Model (minimum)

historical_prices table

Column	Type	Notes
id (PK)	int	Auto increment
product_id	int	e.g., 1=Beans, 3=Egg, 4=Salaya, etc.
price_date	date	Retail price date
narahenpita_retail	decimal	Daily retail price
timestamps	datetime	Standard Laravel timestamps

Duplicate rows per day are averaged.

Non-positive values are treated as missing.

🔮 Forecasting Flow

Request (Laravel → FastAPI)

POST /predict
{
"item": "Beans",
"history": [{"ds":"2023-01-01","y":100.0}, ...],
"horizon": 30,
"aggregate": "daily"
}


Response (FastAPI → Laravel)

{
"predictions": [
{"ds":"2025-09-06","yhat":123.45},
...
]
}


UI (Blade + ApexCharts)

Shows last 60 days actuals + 30-day forecast

Zeros hidden → treated as gaps

Cutoff band labeled “Forecast”

⏰ Scheduled CBSL Ingest

Laravel Scheduler (app/Console/Kernel.php)

protected function schedule(Schedule $schedule): void
{
$schedule->command('cbsl:ingest')->dailyAt('06:00')->withoutOverlapping();
}


System Cron (Linux/macOS):

* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1


Windows (Laragon):
Create a Task Scheduler job:

Program/script: php

Arguments: artisan schedule:run

Start in: C:\laragon\www\your-app

Trigger: Daily at 06:00

🌐 Routes (example)
// routes/web.php
Route::get('/forecast', [ForecastController::class,'index'])->name('forecast.index');
Route::post('/forecast/data', [ForecastController::class,'getData'])->name('forecast.data');

🛠️ Troubleshooting

cURL error 7 / API 502
→ Check FASTAPI_URL (must not end with /predict)

500 Internal Server Error (FastAPI)
→ Common causes: bad date parsing, too few points, input shape mismatch

Dots/zero spikes on chart
→ Mask zeros to null in controller; markers: { size:0 } in ApexCharts

Too many decimals
→ Use formatters:

yaxis: { labels: { formatter: val => Number(val).toFixed(2) } }
tooltip: { y: { formatter: val => Number(val).toFixed(2) } }

🔒 Security & Ops Notes

Validate all inputs server-side

Rate-limit /forecast/data if public

Log API errors with status/body

Version your model file (e.g., lstm_730in30out_v1.h5)

Retrain periodically (monthly/quarterly) and monitor sMAPE drift

📌 Credits

Data Source: Central Bank of Sri Lanka – Price Report

Model: Global LSTM, trained with 730-day window → 30-day horizon

Frontend: Laravel + Blade + ApexCharts

Backend: FastAPI + TensorFlow