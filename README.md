# WealthAI | Personal Financial Intelligence & DCA Strategy Tracker

WealthAI is a specialized, private investment dashboard developed as a personal project to monitor financial assets in real-time. The core mission of this project was twofold: to create a centralized hub for tracking my own investments and to satisfy a genuine curiosity about integrating Large Language Models (LLMs) with functional web APIs.

## 🚀 The Concept

Unlike traditional trackers, WealthAI combines asset management with an "Aggressive AI Guru" layer. The goal was to move beyond static numbers and learn how an AI can consume live portfolio data to provide contextual, unfiltered feedback. The project places a heavy emphasis on **Dollar Cost Averaging (DCA)**, allowing for long-term wealth projections and disciplined execution of recurring investments.

## 🧠 AI Integration (The Experiment)

A significant portion of this project was dedicated to exploring AI integration. Using the **Groq API** (Llama 3.1 8b), the system:
- Consumes real-time decrypted portfolio data.
- Analyzes PnL percentages and market trends.
- Evaluates the sustainability of DCA strategies.
- Provides a "Guru" persona that delivers direct, aggressive advice in Portuguese (PT-PT) to keep the investor disciplined.

## 🛠️ Technical Stack

- **Frontend**: Single Page Application (SPA) built with **React** (via CDN for portability) and **Tailwind CSS**.
- **Backend**: **PHP 8.x** handles the logic, API routing, and security layers.
- **Database**: **MySQL** stores asset details, transaction history, and DCA configurations.
- **Visuals**: **ApexCharts** for real-time portfolio distribution (Donut Charts) and accumulation history.

## 📡 APIs Used

- **Groq Cloud API**: Powers the AI Engine using the `llama-3.1-8b-instant` model.
- **CoinGecko API**: Fetches real-time prices for Cryptocurrencies.
- **Yahoo Finance API (query2)**: Provides live data for Stocks and ETFs.
- **TradingView**: Dynamic logo retrieval for financial assets.

## 🔒 Security & Privacy

Since this is a personal financial tool, security was a priority:
- **AES-256-CBC Encryption**: All sensitive data, including asset quantities and buy prices, are encrypted before being stored in the database. 
- **Decryption on the Fly**: Data is only decrypted in memory during the session to calculate totals or provide context to the AI.

## 📈 Core Features

- **DCA Setup & Projection**: Each asset can have a dedicated DCA amount. The UI calculates compound interest projections up to the year **2050** at an estimated 8% annual return.
- **Execution Engine**: A dedicated handler (`dca_handler.php`) that allows "Executing the Month," which automatically updates the encrypted average cost and total quantity based on the current market price.
- **Real-Time Search**: A unified search bar that queries both crypto and stock markets simultaneously.
- **Portfolio Valuation**: Instant calculation of total net worth, total profit/loss, and percentage changes.

## 📂 Project Structure

- `/api/ai_engine.php` - The bridge between my data and the LLM.
- `/api/dca_handler.php` - Logic for recurring investment math and database updates.
- `/api/security.php` - The encryption/decryption layer.
- `/api/assets.php` - CRUD operations for my portfolio.
- `/config/database.php` - Database connection settings.
- `index.html` - The entire React-based dashboard interface.

---
*Note: This is a private project developed for educational purposes and personal financial tracking.*
