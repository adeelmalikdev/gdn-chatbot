# GDN Website Chatbot - Production Deployment Guide

This guide details how to deploy the **GDN Website Chatbot** backend API to production for embedding on [Global Digital Nexus](https://globaldigitalnexus.com).

---

## 1. Environment Configuration

Create a `.env` file on your production server based on `.env.example`:

```ini
ENVIRONMENT=production
LLM_PROVIDER=groq
LLM_MODEL=llama-3.1-8b-instant
GROQ_API_KEY=your_groq_api_key_here

# Redis Rate Limiting (Upstash or Local Redis)
REDIS_URL=redis://redis:6379/0

# Production CORS Security (Only allow GDN domain)
ALLOWED_ORIGINS=https://globaldigitalnexus.com,https://www.globaldigitalnexus.com

# Vector Store Path
CHROMA_PERSIST_DIRECTORY=data/chroma
COLLECTION_NAME=gdn_website
RETRIEVAL_K=5
RATE_LIMIT=30/minute
```

---

## 2. Deployment Options

### Option A: Docker Compose (Recommended for VPS / DigitalOcean / AWS EC2)

1. **Clone Repository & Build Container**:
   ```bash
   git clone https://github.com/your-org/gdn-chatbot.git
   cd gdn-chatbot
   cp .env.example .env
   # Add your API keys to .env
   ```

2. **Launch Services (API + Redis)**:
   ```bash
   docker compose up -d --build
   ```

3. **Verify Health**:
   ```bash
   curl http://localhost:8000/health
   ```

---

### Option B: Cloud Container Hosting (AWS Cloud Run / Render / Railway)

1. **Build & Push Docker Image**:
   ```bash
   docker build -t gdn-chatbot:latest .
   ```

2. **Environment Variables**:
   Set environment variables (`LLM_PROVIDER`, `XAI_API_KEY`, `ENVIRONMENT=production`, `ALLOWED_ORIGINS`) in your cloud dashboard.

3. **Rate Limiting**:
   Set `REDIS_URL` to an [Upstash Redis](https://upstash.com) free tier endpoint string.

---

## 3. Reverse Proxy & SSL Setup (Nginx)

Place Nginx in front of Uvicorn/Docker on port 8000 with a free Let's Encrypt SSL certificate:

```nginx
server {
    server_name api.globaldigitalnexus.com;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # SSE Streaming Support
        proxy_set_header Connection '';
        proxy_http_version 1.1;
        chunked_transfer_encoding off;
        proxy_buffering off;
        proxy_cache off;
    }
}
```

Enable SSL:
```bash
sudo certbot --nginx -d api.globaldigitalnexus.com
```

---

## 4. Website Widget Integration

To embed the chatbot on `globaldigitalnexus.com`, paste the following before `</body>`:

```html
<link rel="stylesheet" href="https://api.globaldigitalnexus.com/widget.css" />
<script>
  window.GDN_CHAT_URL = "https://api.globaldigitalnexus.com/chat";
</script>
<script src="https://api.globaldigitalnexus.com/widget.js" defer></script>
```

---

## 5. Maintenance & Website Re-Crawling

To update the chatbot's knowledge base when GDN adds new services or pages to the website:

```bash
python -m scripts.ingest
```

*(This can also be scheduled automatically via GitHub Actions in `.github/workflows/ingest.yml`).*

