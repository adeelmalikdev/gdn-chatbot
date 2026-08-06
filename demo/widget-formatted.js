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

