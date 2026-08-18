# Step-by-Step Azure Deployment Guide for GDN Chatbot

This step-by-step guide walks you through deploying the **GDN Chatbot** (FastAPI + ChromaDB + Redis) on an **Azure Virtual Machine** using your **Azure for Students $100 Credit** (free for 12 months).

---

## Phase 1: Create Your Azure Ubuntu Server

1. Sign in to the [Azure Portal](https://portal.azure.com/) with your student account.
2. Search for **Virtual machines** in the top search bar and click **Create** -> **Azure virtual machine**.
3. Configure the **Basics** tab:
   - **Subscription**: Azure for Students
   - **Resource Group**: Click *Create new* -> Name: `gdn-chatbot-rg`
   - **Virtual machine name**: `gdn-chatbot-vm`
   - **Region**: Select a region close to you (e.g. `East US` or `West Europe`)
   - **Image**: `Ubuntu Server 22.04 LTS - x64 Gen2`
   - **Architecture**: `x64`
   - **Size**: Choose `Standard_B1s` (1 vCPU, 1 GB RAM ~ $8/mo, 100% free with student credit) or `Standard_B2s` (2 vCPU, 4 GB RAM ~ $15/mo)
   - **Authentication type**: Select **Password**
   - **Username**: `azureuser`
   - **Password**: Create a strong password (remember this!)
4. Configure **Disks & Networking**:
   - In **Public inbound ports**, select **Allow selected ports**.
   - Check **SSH (22)**, **HTTP (80)**, and **HTTPS (443)**.
5. Click **Review + create** at the bottom, then click **Create**.
6. Wait ~1-2 minutes until deployment completes. Click **Go to resource** and copy your **Public IP Address** (e.g., `20.124.55.10`).

---

## Phase 2: Connect to Your Azure VM

Open **PowerShell** or **Command Prompt** on your computer and run:

```bash
ssh azureuser@YOUR_AZURE_PUBLIC_IP
```

*(Type `yes` when prompted, then type your password.)*

---

## Phase 3: Install Docker & Dependencies on Azure VM

Run these commands inside your Azure SSH terminal:

```bash
# 1. Update system packages
sudo apt update && sudo apt upgrade -y

# 2. Install Git and prerequisites
sudo apt install -y git curl ufw

# 3. Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# 4. Allow your user to run Docker without sudo
sudo usermod -aG docker azureuser
newgrp docker

# 5. Verify Docker installation
docker --version
docker compose version
```

---

## Phase 4: Clone Repository & Build App

```bash
# 1. Clone your project code
git clone https://github.com/your-username/gdn-chatbot.git
cd gdn-chatbot

# 2. Create production environment file
cp .env.example .env
nano .env
```

Inside `nano`, set your actual API keys and values:

```ini
ENVIRONMENT=production
LLM_PROVIDER=groq
LLM_MODEL=llama-3.1-8b-instant
GROQ_API_KEY=your_actual_groq_api_key_here

REDIS_URL=redis://redis:6379/0
ALLOWED_ORIGINS=https://globaldigitalnexus.com,https://www.globaldigitalnexus.com,http://YOUR_AZURE_PUBLIC_IP
CHROMA_PERSIST_DIRECTORY=data/chroma
COLLECTION_NAME=gdn_website
RETRIEVAL_K=5
RATE_LIMIT=30/minute
```

*(Press `Ctrl + O`, then `Enter` to save, then `Ctrl + X` to exit).*

```bash
# 3. Launch API & Redis with Docker Compose
docker compose up -d --build

# 4. Check if containers are running
docker compose ps
```

---

## Phase 5: Ingest Website Knowledge (Vector Embeddings)

Populate ChromaDB vector database with website knowledge:

```bash
docker compose exec api python -m scripts.ingest
```

---

## Phase 6: Setup Nginx Reverse Proxy & SSL (HTTPS)

```bash
# 1. Install Nginx
sudo apt install -y nginx certbot python3-certbot-nginx

# 2. Create Nginx site configuration
sudo nano /etc/nginx/sites-available/gdn-chatbot
```

Paste the following configuration (replace `YOUR_AZURE_PUBLIC_IP_OR_DOMAIN` with your IP or domain like `api.globaldigitalnexus.com`):

```nginx
server {
    listen 80;
    server_name YOUR_AZURE_PUBLIC_IP_OR_DOMAIN;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # Enable SSE Streaming Support
        proxy_set_header Connection '';
        proxy_http_version 1.1;
        chunked_transfer_encoding off;
        proxy_buffering off;
        proxy_cache off;
    }
}
```

Save (`Ctrl+O`, `Enter`) and exit (`Ctrl+X`).

```bash
# 3. Enable Nginx config & restart
sudo ln -s /etc/nginx/sites-available/gdn-chatbot /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx
```

### Enable Free SSL (If you have a domain pointed to your Azure IP):
```bash
sudo certbot --nginx -d api.yourdomain.com
```

---

## Phase 7: Verification

From your local machine terminal or browser:

```bash
curl http://YOUR_AZURE_PUBLIC_IP/health
```

Expected output:
```json
{
  "status": "healthy",
  "environment": "production",
  "llm_provider": "groq",
  "llm_model": "llama-3.1-8b-instant",
  "redis_configured": true
}
```

Congratulations! Your GDN Chatbot backend is now live on Azure, fully funded by your $100 GitHub Student Pack credit for 12 months!
