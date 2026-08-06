# GDN website chatbot

A compact, secure retrieval-augmented chatbot API for [Global Digital Nexus](https://globaldigitalnexus.com). The public application surface is deliberately one endpoint: `POST /chat`.

## What is included

- Current typed Firecrawl SDK ingestion (`Firecrawl(...).crawl(...).data`), scoped to the GDN domain.
- Structure-aware Markdown heading splits, 900-character recursive chunks with overlap, contextual page/section prefixes, BGE embeddings, persistent Chroma, and MMR retrieval for diversity.
- LangChain model layer using an OpenAI-compatible client. xAI is the default (`https://api.x.ai/v1`); set `LLM_PROVIDER`, `LLM_BASE_URL`, and `LLM_API_KEY` to swap providers without API code changes.
- Strict request schema, small history budget, IP rate limits, restrictive CORS, basic prompt-injection screening, retrieval-context sanitisation, and a system prompt that treats website text as data.
- Sources returned with each answer so the widget can show citations.

## Setup

```bash
python -m venv .venv
.venv\Scripts\activate
pip install -e .[dev]
copy .env.example .env
# add FIRECRAWL_API_KEY and XAI_API_KEY to .env
python -m scripts.ingest
uvicorn app.main:app --host 127.0.0.1 --port 8000
```

In production, serve behind HTTPS/reverse proxy and set `ALLOWED_ORIGINS` exactly to the website origins. For multiple API instances, replace SlowAPI's memory storage with shared Redis-backed rate-limit storage.

## Endpoint

```json
POST /chat
{
  "message": "What cybersecurity services does GDN provide?",
  "history": []
}
```

```json
{
  "answer": "...",
  "sources": [{"title": "Cybersecurity Services", "url": "https://globaldigitalnexus.com/..."}]
}
```

The screenshots are well suited to a bottom-right widget: use the returned `answer` as a chat bubble and render `sources` below it as small “Learn more” links. The browser should never receive Firecrawl or LLM credentials.

## Widget demo

The white-background, bottom-right widget preview is in `demo/`. Start the API, then in another terminal run `python -m http.server 8080 --directory demo` and open `http://localhost:8080`. The demo talks to `http://127.0.0.1:8000/chat`; change `CHAT_URL` in `demo/widget.js` to the deployed API URL when embedding it on the GDN site.

## Safety note

This is a customer-information assistant, not an authority for legal, financial, regulatory, or security decisions. Keep contact/consultation escalation available in the UI.
