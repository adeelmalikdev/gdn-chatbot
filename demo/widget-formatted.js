function ensureWidgetElements() {
  if (document.querySelector("#launcher")) return;
  const container = document.createElement("section");
  container.className = "chat-widget";
  container.setAttribute("aria-label", "GDN Assistant");
  container.innerHTML = `
    <button class="launcher" id="launcher" aria-label="Open GDN Assistant" aria-expanded="false" style="display: flex; align-items: center; justify-content: center;">
      <span class="launcher-label">Chat with GDN</span>
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11.25a7.25 7.25 0 0 1-7.25 7.25 7.15 7.15 0 0 1-3.18-.74L4 19.25l1.49-4.57A7.24 7.24 0 1 1 20 11.25Z"/><path d="M8.7 11.3h.01M12 11.3h.01M15.3 11.3h.01"/></svg>
    </button>
    <div class="panel is-hidden" id="panel">
      <header class="panel-header">
        <div class="brand-lockup">
          <span class="presence-dot"></span>
          <div><strong>GDN Assistant</strong><small>Typically replies instantly</small></div>
        </div>
        <button class="icon-button" id="close" aria-label="Close chat">×</button>
      </header>
      <div class="assistant-badge">GLOBAL DIGITAL NEXUS</div>
      <div class="conversation" id="conversation" aria-live="polite">
        <article class="message assistant-message">
          <p>Welcome to Global Digital Nexus.</p>
          <p>How can we help you transform your business today?</p>
          <time>Just now</time>
        </article>
        <div class="quick-prompts" id="quickPrompts">
          <button data-prompt="Tell me about your cybersecurity services">Cybersecurity services</button>
          <button data-prompt="What consulting services do you offer?">Technology consulting</button>
          <button data-prompt="How can I contact Global Digital Nexus?">Contact GDN</button>
          <button data-prompt="Tell me about your IFRS 9 solutions">IFRS 9 solutions</button>
        </div>
      </div>
      <form class="composer" id="composer">
        <label class="sr-only" for="message">Ask GDN a question</label>
        <input id="message" maxlength="2000" autocomplete="off" placeholder="Type your message..." />
        <button id="send" type="submit" aria-label="Send message">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21.7 2.3-19 8.1c-1 .43-.9 1.88.16 2.17l7.46 2.04 2.04 7.46c.29 1.06 1.74 1.16 2.17.16l8.1-19c.38-.9-.51-1.8-1.41-1.41ZM12 13l6.72-6.72L13 14l-1 4.34L10.72 14 6.38 12 10 11l7.72-5.72L11 12Z"/></svg>
        </button>
      </form>
      <footer>Powered by <strong>Global Digital Nexus</strong></footer>
    </div>
  `;
  (document.body || document.documentElement).appendChild(container);
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", ensureWidgetElements);
} else {
  ensureWidgetElements();
}

const CHAT_URL = window.GDN_CHAT_URL || "http://127.0.0.1:8000/chat";
const history = [];
const panel = document.querySelector("#panel");
const launcher = document.querySelector("#launcher");
const close = document.querySelector("#close");
const conversation = document.querySelector("#conversation");
const form = document.querySelector("#composer");
const input = document.querySelector("#message");
const send = document.querySelector("#send");

function now() { return new Intl.DateTimeFormat([], { hour: "numeric", minute: "2-digit" }).format(new Date()); }
function scrollToLatest() { conversation.scrollTop = conversation.scrollHeight; }

// Safe mini-Markdown renderer. It never injects model HTML into the page.
function inlineMarkdown(text) {
  const fragment = document.createDocumentFragment();
  const parts = text.split(/(\*\*[^*]+\*\*)/g);
  for (const part of parts) {
    if (part.startsWith("**") && part.endsWith("**")) {
      const bold = document.createElement("strong");
      bold.textContent = part.slice(2, -2);
      fragment.append(bold);
    } else {
      fragment.append(document.createTextNode(part));
    }
  }
  return fragment;
}

function richText(text) {
  const wrapper = document.createElement("div");
  const lines = text.replace(/\r/g, "").split("\n");
  let list = null;
  let paragraph = [];
  const flushParagraph = () => {
    if (!paragraph.length) return;
    const p = document.createElement("p");
    p.append(inlineMarkdown(paragraph.join(" ")));
    wrapper.append(p);
    paragraph = [];
  };
  const flushList = () => { if (list) wrapper.append(list); list = null; };
  for (const raw of lines) {
    const line = raw.trim();
    const unordered = line.match(/^[-*]\s+(.+)/);
    const ordered = line.match(/^\d+[.)]\s+(.+)/);
    if (unordered || ordered) {
      flushParagraph();
      const tag = ordered ? "ol" : "ul";
      if (!list || list.tagName.toLowerCase() !== tag) { flushList(); list = document.createElement(tag); }
      const item = document.createElement("li"); item.append(inlineMarkdown((unordered || ordered)[1])); list.append(item);
    } else if (!line) { flushParagraph(); flushList(); }
    else { flushList(); paragraph.push(line); }
  }
  flushParagraph(); flushList();
  return wrapper;
}

function messageBubble(text, role, sources = []) {
  const article = document.createElement("article");
  article.className = `message ${role}-message`;
  if (role === "assistant") article.append(richText(text));
  else { const p = document.createElement("p"); p.textContent = text; article.append(p); }
  if (sources.length) {
    const sourceList = document.createElement("div"); sourceList.className = "sources";
    sources.slice(0, 3).forEach(({ title, url }) => {
      const link = document.createElement("a"); link.href = url; link.target = "_blank"; link.rel = "noopener noreferrer"; link.textContent = title || "Learn more"; sourceList.append(link);
    });
    article.append(sourceList);
  }
  const time = document.createElement("time"); time.textContent = now(); article.append(time);
  conversation.append(article); scrollToLatest();
}

function setBusy(busy) { input.disabled = busy; send.disabled = busy; }
function showTyping() { const bubble = document.createElement("div"); bubble.className = "message assistant-message typing"; bubble.id = "typing"; bubble.innerHTML = "<span></span><span></span><span></span>"; conversation.append(bubble); scrollToLatest(); }
async function ask(question) {
  if (!question || send.disabled) return;
  document.querySelector("#quickPrompts")?.remove();
  messageBubble(question, "user");
  history.push({ role: "user", content: question });
  setBusy(true);
  showTyping();

  const streamUrl = CHAT_URL.endsWith("/stream") ? CHAT_URL : `${CHAT_URL}/stream`;
  let fullAnswer = "";
  let sources = [];
  let article = null;

  try {
    const response = await fetch(streamUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ message: question, history: history.slice(0, -1).slice(-8) })
    });

    if (!response.ok) {
      const data = await response.json().catch(() => ({}));
      throw new Error(data.detail || "The assistant is unavailable right now.");
    }

    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    document.querySelector("#typing")?.remove();

    article = document.createElement("article");
    article.className = "message assistant-message";
    conversation.append(article);

    let buffer = "";
    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      buffer += decoder.decode(value, { stream: true });
      const lines = buffer.split("\n\n");
      buffer = lines.pop() || "";
      for (const line of lines) {
        if (!line.startsWith("data: ")) continue;
        const rawData = line.slice(6).trim();
        if (rawData === "[DONE]") break;
        try {
          const parsed = JSON.parse(rawData);
          if (parsed.content) {
            fullAnswer += parsed.content;
            article.replaceChildren(richText(fullAnswer));
            scrollToLatest();
          }
          if (parsed.sources) sources = parsed.sources;
        } catch (e) {}
      }
    }

    if (sources.length) {
      const sourceList = document.createElement("div");
      sourceList.className = "sources";
      sources.slice(0, 3).forEach(({ title, url }) => {
        const link = document.createElement("a");
        link.href = url;
        link.target = "_blank";
        link.rel = "noopener noreferrer";
        link.textContent = title || "Learn more";
        sourceList.append(link);
      });
      article.append(sourceList);
    }
    const time = document.createElement("time");
    time.textContent = now();
    article.append(time);
    scrollToLatest();
    history.push({ role: "assistant", content: fullAnswer });
  } catch (error) {
    document.querySelector("#typing")?.remove();
    if (article) article.remove();
    messageBubble(error.message || "Unable to reach GDN Assistant. Please try again shortly.", "assistant");
  } finally {
    setBusy(false);
    input.focus();
  }
}

async function checkHealth() {
  const statusSmall = document.querySelector(".panel-header small");
  if (!statusSmall) return;
  try {
    const healthUrl = CHAT_URL.replace(/\/chat\/?$/, "/health");
    const res = await fetch(healthUrl, { method: "GET" });
    if (res.ok) {
      const data = await res.json();
      statusSmall.textContent = `Online (${data.llm_model || "grok-3-mini"})`;
    } else {
      statusSmall.textContent = "API Error";
    }
  } catch (e) {
    statusSmall.textContent = "Offline (start backend API)";
  }
}

checkHealth();

form.addEventListener("submit", event => { event.preventDefault(); const question = input.value.trim(); input.value = ""; ask(question); });
document.querySelector("#quickPrompts").addEventListener("click", event => { const button = event.target.closest("button"); if (button) ask(button.dataset.prompt); });
close.addEventListener("click", () => { panel.classList.add("is-hidden"); launcher.style.display = "grid"; launcher.setAttribute("aria-expanded", "false"); });
launcher.addEventListener("click", () => { panel.classList.remove("is-hidden"); launcher.style.display = "none"; launcher.setAttribute("aria-expanded", "true"); input.focus(); });

