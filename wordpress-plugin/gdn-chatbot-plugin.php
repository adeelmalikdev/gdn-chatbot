<?php
/**
 * Plugin Name: GDN Chatbot Widget
 * Plugin URI: https://globaldigitalnexus.com
 * Description: 1-to-1 pixel-perfect replication of the GDN Assistant Demo Widget for WordPress.
 * Version: 1.0.1
 * Author: Global Digital Nexus
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_footer', function() {
    ?>
    <!-- GDN Assistant Demo Pixel-Perfect Styles -->
    <style>
      :root {
        --gdn-red: #ce1638;
        --gdn-ink: #17212b;
        --gdn-muted: #69717b;
        --gdn-line: #e8eaed;
      }
      .chat-widget {
        position: fixed !important;
        right: 30px !important;
        bottom: 26px !important;
        z-index: 999999 !important;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, sans-serif !important;
      }
      .launcher {
        position: absolute !important;
        right: 0 !important;
        bottom: 0 !important;
        width: 64px !important;
        height: 64px !important;
        border: 3px solid #ffffff !important;
        border-radius: 50% !important;
        background: var(--gdn-red) !important;
        color: #ffffff !important;
        cursor: pointer !important;
        display: flex;
        align-items: center !important;
        justify-content: center !important;
        box-shadow: 0 12px 30px #8e0f2c62 !important;
        transition: transform .15s, background .15s !important;
      }
      .launcher:hover {
        transform: translateY(-3px) !important;
        background: #b80d2d !important;
      }
      .launcher svg {
        width: 31px !important;
        fill: none !important;
        stroke: currentColor !important;
        stroke-width: 1.85 !important;
        stroke-linecap: round !important;
        stroke-linejoin: round !important;
      }
      /* CHAT WITH GDN AI badge visible 24/7 */
      .launcher-label {
        position: absolute !important;
        right: 76px !important;
        white-space: nowrap !important;
        padding: 9px 14px !important;
        border-radius: 8px !important;
        background: #202a34 !important;
        color: #ffffff !important;
        font-size: .75rem !important;
        font-weight: 700 !important;
        opacity: 1 !important;
        transform: none !important;
        pointer-events: auto !important;
        box-shadow: 0 6px 18px #17212b29 !important;
        letter-spacing: 0.04em !important;
      }
      /* Hide circular launcher button completely when chat panel is open */
      .panel:not(.is-hidden) ~ .launcher,
      .chat-widget:has(.panel:not(.is-hidden)) .launcher {
        display: none !important;
      }
      .panel {
        width: 382px !important;
        height: 572px !important;
        border-radius: 17px !important;
        overflow: hidden !important;
        background: #ffffff !important;
        border: 1px solid #e5e5e5 !important;
        box-shadow: 0 22px 58px #10131b2b !important;
        display: flex !important;
        flex-direction: column !important;
        transform-origin: bottom right !important;
        transition: opacity .18s, transform .18s !important;
      }
      .panel.is-hidden {
        opacity: 0 !important;
        transform: scale(.92) !important;
        pointer-events: none !important;
      }
      .panel-header {
        min-height: 76px !important;
        color: #ffffff !important;
        background: var(--gdn-red) !important;
        padding: 16px 17px 15px 20px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
      }
      .brand-lockup {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
      }
      .brand-lockup strong {
        display: block !important;
        font-size: 1rem !important;
        letter-spacing: -.02em !important;
        color: #ffffff !important;
      }
      .brand-lockup small {
        display: block !important;
        font-size: .71rem !important;
        opacity: .84 !important;
        margin-top: 3px !important;
        color: #ffffff !important;
      }
      .presence-dot {
        width: 10px !important;
        height: 10px !important;
        border-radius: 50% !important;
        background: #b3ebc4 !important;
        box-shadow: 0 0 0 4px #ffffff2d !important;
      }
      .icon-button {
        color: #ffffff !important;
        background: transparent !important;
        border: 0 !important;
        cursor: pointer !important;
        line-height: 1 !important;
        font-size: 28px !important;
        font-weight: 200 !important;
        padding: 4px !important;
      }
      .assistant-badge {
        align-self: center !important;
        margin: 14px 0 4px !important;
        padding: 6px 11px !important;
        border-radius: 999px !important;
        color: var(--gdn-red) !important;
        background: #fff1f3 !important;
        font-weight: 800 !important;
        letter-spacing: .09em !important;
        font-size: .58rem !important;
      }
      .conversation {
        flex: 1 !important;
        overflow: auto !important;
        padding: 8px 17px 20px !important;
        scrollbar-width: thin !important;
      }
      .message {
        width: fit-content !important;
        max-width: 87% !important;
        padding: 13px 14px 9px !important;
        border-radius: 13px !important;
        font-size: .88rem !important;
        line-height: 1.45 !important;
        animation: arrive .18s ease-out !important;
      }
      .message p { margin: 0 0 6px !important; }
      .message p:last-of-type { margin-bottom: 4px !important; }
      .message time {
        display: block !important;
        color: #9fa6ad !important;
        font-size: .65rem !important;
      }
      .assistant-message {
        background: #202a34 !important;
        color: #ffffff !important;
        border-bottom-left-radius: 3px !important;
      }
      .assistant-message time { color: #b9c0c7 !important; }
      .assistant-message > div > p { margin: 0 0 9px !important; }
      .assistant-message > div > p:last-child { margin-bottom: 5px !important; }
      .assistant-message > div > ul, .assistant-message > div > ol { margin: 3px 0 10px !important; padding-left: 18px !important; }
      .assistant-message > div > li { margin: 4px 0 !important; padding-left: 1px !important; }
      .assistant-message strong { color: #ffffff !important; font-weight: 750 !important; }
      .user-message {
        margin: 13px 0 13px auto !important;
        color: #ffffff !important;
        background: var(--gdn-red) !important;
        border-bottom-right-radius: 3px !important;
      }
      .user-message time { color: #ffe5eb !important; text-align: right !important; }
      .quick-prompts {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 8px !important;
        margin-top: 18px !important;
      }
      .quick-prompts button {
        min-height: 42px !important;
        padding: 8px !important;
        border: 1px solid #e5e7e9 !important;
        border-radius: 8px !important;
        color: #39424b !important;
        background: #ffffff !important;
        font-size: .71rem !important;
        cursor: pointer !important;
      }
      .quick-prompts button:hover {
        border-color: var(--gdn-red) !important;
        color: var(--gdn-red) !important;
        background: #fff8f9 !important;
      }
      .typing {
        display: flex !important;
        align-items: center !important;
        gap: 5px !important;
        min-height: 37px !important;
        padding: 12px 14px !important;
      }
      .typing span {
        width: 6px !important;
        height: 6px !important;
        border-radius: 50% !important;
        background: #aab0b7 !important;
        animation: dot 1s infinite alternate !important;
      }
      .typing span:nth-child(2) { animation-delay: .18s !important; }
      .typing span:nth-child(3) { animation-delay: .36s !important; }
      .sources {
        margin-top: 8px !important;
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 5px !important;
      }
      .sources a {
        color: var(--gdn-red) !important;
        background: #fff0f2 !important;
        border-radius: 5px !important;
        padding: 4px 6px !important;
        font-size: .65rem !important;
        text-decoration: none !important;
      }
      .sources a:hover { text-decoration: underline !important; }
      .composer {
        height: 74px !important;
        padding: 14px 14px 12px 16px !important;
        border-top: 1px solid var(--gdn-line) !important;
        display: flex !important;
        gap: 8px !important;
        align-items: center !important;
        background: #ffffff !important;
      }
      .composer input {
        height: 43px !important;
        flex: 1 !important;
        min-width: 0 !important;
        border: 1px solid #e2e4e7 !important;
        border-radius: 999px !important;
        padding: 0 15px !important;
        color: var(--gdn-ink) !important;
        outline: none !important;
        font: inherit !important;
        font-size: .83rem !important;
      }
      .composer input:focus {
        border-color: #e26b7f !important;
        box-shadow: 0 0 0 3px #ce16381c !important;
      }
      .composer button {
        width: 42px !important;
        height: 42px !important;
        border: 0 !important;
        border-radius: 50% !important;
        background: transparent !important;
        color: var(--gdn-red) !important;
        cursor: pointer !important;
      }
      .composer button:disabled { opacity: .45 !important; cursor: wait !important; }
      .composer button svg { width: 28px !important; fill: currentColor !important; }
      .chat-widget footer {
        color: #8e969f !important;
        text-align: center !important;
        font-size: .61rem !important;
        padding: 0 0 11px !important;
        background: #ffffff !important;
      }
      .chat-widget footer strong { color: var(--gdn-red) !important; }
      .sr-only {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        overflow: hidden !important;
        clip: rect(0,0,0,0) !important;
        white-space: nowrap !important;
      }
      @keyframes arrive { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }
      @keyframes dot { to { transform: translateY(-3px); opacity: .4; } }
      @media(max-width: 520px) {
        .chat-widget { right: 12px !important; bottom: 12px !important; left: 12px !important; }
        .panel { width: 100% !important; height: min(570px, calc(100dvh - 24px)) !important; }
        .launcher { right: 0 !important; }
      }
    </style>

    <!-- GDN Assistant DOM Structure -->
    <section class="chat-widget" aria-label="GDN Assistant">
      <button class="launcher" id="gdnLauncher" aria-label="Open GDN Assistant" aria-expanded="false">
        <span class="launcher-label">CHAT WITH GDN AI</span>
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M20 11.25a7.25 7.25 0 0 1-7.25 7.25 7.15 7.15 0 0 1-3.18-.74L4 19.25l1.49-4.57A7.24 7.24 0 1 1 20 11.25Z"/>
          <path d="M8.7 11.3h.01M12 11.3h.01M15.3 11.3h.01"/>
        </svg>
      </button>

      <div class="panel is-hidden" id="gdnPanel">
        <header class="panel-header">
          <div class="brand-lockup">
            <span class="presence-dot"></span>
            <div>
              <strong>GDN Assistant</strong>
              <small id="gdnStatusSmall">Typically replies instantly</small>
            </div>
          </div>
          <button class="icon-button" id="gdnClose" aria-label="Close chat">&times;</button>
        </header>
        <div class="assistant-badge">GLOBAL DIGITAL NEXUS</div>
        <div class="conversation" id="gdnConversation" aria-live="polite">
          <article class="message assistant-message">
            <p>Welcome to Global Digital Nexus.</p>
            <p>How can we help you transform your business today?</p>
            <time>Just now</time>
          </article>
          <div class="quick-prompts" id="gdnQuickPrompts">
            <button data-prompt="Tell me about your cybersecurity services">Cybersecurity services</button>
            <button data-prompt="What consulting services do you offer?">Technology consulting</button>
            <button data-prompt="How can I contact Global Digital Nexus?">Contact GDN</button>
            <button data-prompt="Tell me about your IFRS 9 solutions">IFRS 9 solutions</button>
          </div>
        </div>
        <form class="composer" id="gdnComposer">
          <label class="sr-only" for="gdnMessageInput">Ask GDN a question</label>
          <input id="gdnMessageInput" maxlength="2000" autocomplete="off" placeholder="Type your message..." />
          <button id="gdnSendButton" type="submit" aria-label="Send message">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21.7 2.3-19 8.1c-1 .43-.9 1.88.16 2.17l7.46 2.04 2.04 7.46c.29 1.06 1.74 1.16 2.17.16l8.1-19c.38-.9-.51-1.8-1.41-1.41ZM12 13l6.72-6.72L13 14l-1 4.34L10.72 14 6.38 12 10 11l7.72-5.72L11 12Z"/></svg>
          </button>
        </form>
        <footer>Powered by <strong>Global Digital Nexus</strong></footer>
      </div>
    </section>

    <!-- GDN Assistant JavaScript -->
    <script>
      (function() {
        const CHAT_URL = "https://gdn-chatbot.onrender.com/chat";
        const history = [];
        const panel = document.querySelector("#gdnPanel");
        const launcher = document.querySelector("#gdnLauncher");
        const close = document.querySelector("#gdnClose");
        const conversation = document.querySelector("#gdnConversation");
        const form = document.querySelector("#gdnComposer");
        const input = document.querySelector("#gdnMessageInput");
        const send = document.querySelector("#gdnSendButton");

        function now() { return new Intl.DateTimeFormat([], { hour: "numeric", minute: "2-digit" }).format(new Date()); }
        function scrollToLatest() { conversation.scrollTop = conversation.scrollHeight; }

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
        function showTyping() { const bubble = document.createElement("div"); bubble.className = "message assistant-message typing"; bubble.id = "gdnTyping"; bubble.innerHTML = "<span></span><span></span><span></span>"; conversation.append(bubble); scrollToLatest(); }

        async function ask(question) {
          if (!question || send.disabled) return;
          document.querySelector("#gdnQuickPrompts")?.remove();
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
            document.querySelector("#gdnTyping")?.remove();

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
            document.querySelector("#gdnTyping")?.remove();
            if (article) article.remove();
            messageBubble(error.message || "Unable to reach GDN Assistant. Please try again shortly.", "assistant");
          } finally {
            setBusy(false);
            input.focus();
          }
        }

        async function checkHealth() {
          const statusSmall = document.querySelector("#gdnStatusSmall");
          if (!statusSmall) return;
          try {
            const healthUrl = CHAT_URL.replace(/\/chat\/?$/, "/health");
            const res = await fetch(healthUrl, { method: "GET" });
            if (res.ok) {
              statusSmall.textContent = "Typically replies instantly";
            } else {
              statusSmall.textContent = "Typically replies instantly";
            }
          } catch (e) {
            statusSmall.textContent = "Typically replies instantly";
          }
        }

        checkHealth();

        form.addEventListener("submit", event => { event.preventDefault(); const question = input.value.trim(); input.value = ""; ask(question); });
        document.querySelector("#gdnQuickPrompts")?.addEventListener("click", event => { const button = event.target.closest("button"); if (button) ask(button.dataset.prompt); });
        close.addEventListener("click", () => {
          panel.classList.add("is-hidden");
          launcher.style.setProperty("display", "flex", "important");
          launcher.setAttribute("aria-expanded", "false");
        });
        launcher.addEventListener("click", () => {
          panel.classList.remove("is-hidden");
          launcher.style.setProperty("display", "none", "important");
          launcher.setAttribute("aria-expanded", "true");
          input.focus();
        });
      })();
    </script>
    <?php
});
