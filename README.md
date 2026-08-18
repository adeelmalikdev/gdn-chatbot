# GDN Website Chatbot (`gdn-chatbot`)

A secure, enterprise-grade, retrieval-augmented generation (RAG) backend API and embeddable website widget built for [Global Digital Nexus (GDN)](https://globaldigitalnexus.com).

Powered by **FastAPI**, **LangChain**, **Chroma Vector DB**, **HuggingFace Embeddings**, and **Groq** (`llama-3.1-8b-instant`), it answers visitor questions about GDN's services, industries, and expertise with high accuracy, streaming responses, and citation links.

---

## 🌟 Key Features

* **RAG Architecture**: Structure-aware Markdown heading splits, 900-character recursive chunking with overlap, BGE embeddings (`BAAI/bge-small-en-v1.5`), persistent Chroma vector store, and **Max Marginal Relevance (MMR)** retrieval for answer diversity.
* **Cost-Optimized Model**: Defaulted to Groq free tier (`llama-3.1-8b-instant`) (or configurable to xAI `grok-3-mini`, OpenAI `gpt-4o-mini`, or custom OpenAI-compatible endpoints) for low operational costs and fast responses.
* **Real-Time SSE Token Streaming**: Exposes `POST /chat/stream` for real-time, token-by-token streaming responses (~200ms latency).
* **Multi-Layered Security & Guardrails**:
  * Input prompt injection screening (`reject_prompt_injection`).
  * Context HTML sanitisation and override command stripping (`safe_context`).
  * Security response headers (`X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `X-XSS-Protection`).
* **Shared Redis Rate Limiting**: SlowAPI rate limiter with optional Redis storage support for multi-worker container scaling.
* **Free GitHub Actions CI/CD**:
  * Automated testing & linting pipeline (`ci.yml`).
  * Scheduled weekly website re-crawling workflow (`ingest.yml`).
* **Evaluation & Benchmark Suite**: Built-in 41-case evaluation dataset and CLI summary generator (`scripts/generate_report.py`).
* **Embeddable Frontend Widget**: Sleek, responsive bottom-right widget with automatic health pings, quick prompts, Markdown rendering, and citation cards.

---

## 🏗️ Architecture & Component Overview

```
gdn-chatbot/
├── .github/workflows/
│   ├── ci.yml                 # Automated testing & linting workflow
│   └── ingest.yml             # Scheduled weekly website re-crawling cron
├── app/
│   ├── config.py              # Pydantic settings & environment management
│   ├── main.py                # FastAPI routes, rate limiter, CORS & security middleware
│   ├── providers.py           # Decoupled OpenAI-compatible chat model provider
│   ├── rag.py                 # Vector retrieval, prompt synthesis & SSE stream generator
│   ├── schemas.py             # ChatRequest, ChatResponse, ChatMessage, Source schemas
│   └── security.py            # Prompt injection regex guards & context sanitizer
├── demo/
│   ├── index.html             # Standalone widget preview page
│   ├── widget.js              # Minified widget client script
│   └── widget-formatted.js    # Readable widget script with SSE stream reader
├── evaluations/
│   ├── gdn_eval_cases.json    # 41 ground-truth evaluation test cases
│   └── reports/               # Benchmark JSON output reports
├── scripts/
│   ├── evaluate.py            # Benchmark execution script
│   ├── generate_report.py     # Evaluation summary CLI generator
│   └── ingest.py              # Scrapes GDN site via Firecrawl and embeds into Chroma
├── tests/
│   ├── test_main.py           # Endpoint & streaming tests
│   └── test_security.py       # Security regex unit tests
├── Dockerfile                 # Multi-stage production container build
├── docker-compose.yml         # Production stack (FastAPI + Redis 7)
├── DEPLOYMENT.md              # Detailed production deployment guide
└── pyproject.toml             # Dependencies & tool configurations
```

---

## ⚡ Quick Start

### 1. Local Setup

```bash
# Clone the repository
git clone https://github.com/your-org/gdn-chatbot.git
cd gdn-chatbot

# Create virtual environment & activate
python -m venv .venv
.venv\Scripts\activate       # On Windows
# source .venv/bin/activate  # On Linux/macOS

# Install dependencies in editable mode with dev tools
pip install -e .[dev]

# Create environment configuration
copy .env.example .env
# Edit .env and set FIRECRAWL_API_KEY and XAI_API_KEY
```

### 2. Ingest Website Content

Build the local vector database by scraping `globaldigitalnexus.com`:

```bash
python -m scripts.ingest
```

### 3. Run the Development Server

```bash
uvicorn app.main:app --host 127.0.0.1 --port 8000 --reload
```

Test the health check endpoint in your browser or terminal:
```bash
curl http://127.0.0.1:8000/health
```

### 4. Test the Interactive Web Widget Demo

In a second terminal, serve the demo widget:

```bash
python -m http.server 8080 --directory demo
```

Open **`http://localhost:8080`** in your web browser and click the bottom-right **"Chat with GDN"** launcher button.

---

## 📡 API Endpoints

### `GET /health`
Returns API uptime, active model configuration, and Redis status.

**Response:**
```json
{
  "status": "healthy",
  "environment": "development",
  "llm_provider": "groq",
  "llm_model": "llama-3.1-8b-instant",
  "redis_configured": false
}
```

---

### `POST /chat` (Blocking Response)
Standard JSON chat endpoint.

**Request Body:**
```json
{
  "message": "What cybersecurity services does GDN provide?",
  "history": []
}
```

**Response:**
```json
{
  "answer": "GDN offers comprehensive cybersecurity services including vulnerability assessment, penetration testing (VAPT), virtual CISO (vCISO), incident response planning, and ISO 27001 compliance guidance.",
  "sources": [
    {
      "title": "Cybersecurity Services",
      "url": "https://globaldigitalnexus.com/services/cybersecurity"
    }
  ]
}
```

---

### `POST /chat/stream` (Real-Time SSE Token Streaming)
Streams tokens chunk-by-chunk using Server-Sent Events (`text/event-stream`).

**Request Body:** Same as `POST /chat`.

**Stream Chunk Format:**
```http
data: {"content": "GDN "}

data: {"content": "offers "}

data: {"content": "cybersecurity... "}

data: {"sources": [{"title": "Cybersecurity", "url": "https://..."}]}

data: [DONE]
```

---

## 🐳 Docker Deployment

Run the complete production stack (FastAPI API + Redis 7 rate limiter) with Docker Compose:

```bash
docker compose up -d --build
```

---

## 🧪 Testing & Evaluation

### Run Unit Tests
```bash
pytest
```

### Run Benchmark Evaluation
Run the 41 evaluation cases against the active RAG pipeline:
```bash
python -m scripts.evaluate
```

View formatted markdown benchmark report:
```bash
python -m scripts.generate_report
```

---

## 🔒 Security Policy

* **Prompt Override Prevention**: Strict regex barrier blocks malicious prompt injection attempts.
* **Context Escaping**: Retrieved website data is sanitized and HTML-escaped before model insertion.
* **Disclaimer Bounding**: System prompt restricts legal, financial, or security guarantees and offers human consultation escalation.

---

## 📄 License

Developed for **Global Digital Nexus (GDN)**. All rights reserved.
