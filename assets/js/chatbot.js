/* ==========================================================================
   CARE NEXUS MEDIBOT AI CHATBOT LOGIC
   Handles UI state, AJAX calls, RAG rendering, Voice Input, and Persistence
   ========================================================================== */

document.addEventListener('DOMContentLoaded', function() {
  const triggerBtn = document.getElementById('care-chat-trigger');
  const chatWindow = document.getElementById('care-chat-window');
  const closeBtn = document.getElementById('care-chat-close');
  const clearBtn = document.getElementById('care-chat-clear');
  const bodyEl = document.getElementById('care-chat-messages');
  const inputEl = document.getElementById('care-chat-input-text');
  const sendBtn = document.getElementById('care-chat-send');
  const micBtn = document.getElementById('care-chat-mic');
  const chips = document.querySelectorAll('.care-chat-chip');

  if (!triggerBtn || !chatWindow) return;

  // Determine correct API endpoint relative path
  function getApiEndpoint() {
    const path = window.location.pathname;
    if (path.includes('/patient/') || path.includes('/doctor/') || path.includes('/admin/')) {
      return '../api/chat_handler.php';
    }
    return 'api/chat_handler.php';
  }

  // Toggle Chat Window
  triggerBtn.addEventListener('click', function() {
    chatWindow.classList.toggle('active');
    if (chatWindow.classList.contains('active')) {
      inputEl.focus();
      scrollToBottom();
    }
  });

  if (closeBtn) {
    closeBtn.addEventListener('click', function() {
      chatWindow.classList.remove('active');
    });
  }

  // Load Saved Chat History from localStorage
  let chatHistory = JSON.parse(localStorage.getItem('care_medibot_history') || '[]');

  function saveHistory() {
    try {
      localStorage.setItem('care_medibot_history', JSON.stringify(chatHistory.slice(-20)));
    } catch(e) {
      console.warn('LocalStorage save failed:', e);
    }
  }

  function renderHistory() {
    if (!bodyEl) return;
    
    // Default initial greeting if no history
    if (chatHistory.length === 0) {
      appendBotMessage(
        "👋 Hello! I am **CARE MediBot**, your AI Healthcare Assistant.\n\nHow can I help you today? You can ask me about symptoms, doctors, specializations, hospital clinics, or booking appointments on CARE Nexus!",
        "website_knowledge",
        [
          { label: '🔍 Find a Doctor', url: getRelativeUrl('find_doctor.php') },
          { label: '🚨 Emergency Contacts', url: getRelativeUrl('index.php#contact') }
        ]
      );
      return;
    }

    bodyEl.innerHTML = '';
    chatHistory.forEach(item => {
      if (item.sender === 'user') {
        appendUserMessageDOM(item.text);
      } else {
        appendBotMessageDOM(item.text, item.source, item.actions);
      }
    });
    scrollToBottom();
  }

  function getRelativeUrl(targetUrl) {
    const path = window.location.pathname;
    if (path.includes('/patient/') || path.includes('/doctor/') || path.includes('/admin/')) {
      return '../' + targetUrl;
    }
    return targetUrl;
  }

  // Clear Chat History
  if (clearBtn) {
    clearBtn.addEventListener('click', function() {
      if (confirm('Clear your MediBot conversation history?')) {
        chatHistory = [];
        localStorage.removeItem('care_medibot_history');
        bodyEl.innerHTML = '';
        renderHistory();
      }
    });
  }

  // Append User Message to UI & History
  function appendUserMessage(text) {
    chatHistory.push({ sender: 'user', text: text });
    saveHistory();
    appendUserMessageDOM(text);
  }

  function appendUserMessageDOM(text) {
    const msgDiv = document.createElement('div');
    msgDiv.className = 'care-chat-msg user';
    msgDiv.innerHTML = `<div class="care-chat-bubble">${escapeHtml(text)}</div>`;
    bodyEl.appendChild(msgDiv);
    scrollToBottom();
  }

  // Append Bot Message to UI & History
  function appendBotMessage(text, source = 'gemini_ai', actions = []) {
    chatHistory.push({ sender: 'bot', text: text, source: source, actions: actions });
    saveHistory();
    appendBotMessageDOM(text, source, actions);
  }

  function appendBotMessageDOM(text, source = 'gemini_ai', actions = []) {
    const msgDiv = document.createElement('div');
    msgDiv.className = 'care-chat-msg bot';

    let formattedText = formatMarkdown(text);
    
    let sourceTagHtml = '';
    if (source === 'website_knowledge') {
      sourceTagHtml = `<div class="care-chat-source-tag">❖ Verified CARE Knowledge Base</div>`;
    } else if (source === 'guardrail') {
      sourceTagHtml = `<div class="care-chat-source-tag" style="color: var(--amber-flux); background: rgba(217, 119, 6, 0.1); border-color: rgba(217, 119, 6, 0.2);">❖ Specialized Domain Filter</div>`;
    } else {
      sourceTagHtml = `<div class="care-chat-source-tag" style="color: var(--cyan-neon);">❖ Medical AI Guidance</div>`;
    }

    let actionsHtml = '';
    if (actions && actions.length > 0) {
      actionsHtml = '<div class="care-chat-actions-group">';
      actions.forEach(act => {
        actionsHtml += `<a href="${escapeHtml(getRelativeUrl(act.url))}" class="care-chat-action-btn">${escapeHtml(act.label)}</a>`;
      });
      actionsHtml += '</div>';
    }

    msgDiv.innerHTML = `
      <div class="care-chat-bubble">
        ${formattedText}
        ${actionsHtml}
      </div>
      ${sourceTagHtml}
    `;

    bodyEl.appendChild(msgDiv);
    scrollToBottom();
  }

  // Show / Hide Typing Indicator
  function showTyping() {
    const typingDiv = document.createElement('div');
    typingDiv.id = 'care-chat-typing-indicator';
    typingDiv.className = 'care-chat-msg bot';
    typingDiv.innerHTML = `
      <div class="care-chat-typing">
        <span></span><span></span><span></span>
      </div>
    `;
    bodyEl.appendChild(typingDiv);
    scrollToBottom();
  }

  function hideTyping() {
    const el = document.getElementById('care-chat-typing-indicator');
    if (el) el.remove();
  }

  function scrollToBottom() {
    setTimeout(() => {
      bodyEl.scrollTop = bodyEl.scrollHeight;
    }, 50);
  }

  // Send Message Action
  function sendMessage() {
    const text = inputEl.value.trim();
    if (!text) return;

    inputEl.value = '';
    appendUserMessage(text);
    showTyping();

    fetch(getApiEndpoint(), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        message: text,
        history: chatHistory.slice(-6)
      })
    })
    .then(res => res.json())
    .then(data => {
      hideTyping();
      if (data.status === 'success') {
        appendBotMessage(data.reply, data.source || 'gemini_ai', data.actions || []);
      } else {
        appendBotMessage("⚠️ " + (data.reply || "Something went wrong. Please try again."));
      }
    })
    .catch(err => {
      hideTyping();
      console.error('Chat error:', err);
      appendBotMessage("⚠️ Connection error. Please check your network connection.");
    });
  }

  if (sendBtn) {
    sendBtn.addEventListener('click', sendMessage);
  }

  if (inputEl) {
    inputEl.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });
  }

  // Quick Action Chips Click
  chips.forEach(chip => {
    chip.addEventListener('click', function() {
      const prompt = this.getAttribute('data-prompt') || this.innerText;
      inputEl.value = prompt;
      sendMessage();
    });
  });

  // Speech Recognition (Voice Input)
  if (micBtn) {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
      const recognition = new SpeechRecognition();
      recognition.continuous = false;
      recognition.interimResults = false;
      recognition.lang = 'en-US';

      micBtn.addEventListener('click', function() {
        if (micBtn.classList.contains('listening')) {
          recognition.stop();
          micBtn.classList.remove('listening');
        } else {
          try {
            recognition.start();
            micBtn.classList.add('listening');
          } catch(e) {
            console.error('Speech recognition error:', e);
          }
        }
      });

      recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        inputEl.value = transcript;
        micBtn.classList.remove('listening');
        inputEl.focus();
      };

      recognition.onerror = function() {
        micBtn.classList.remove('listening');
      };

      recognition.onend = function() {
        micBtn.classList.remove('listening');
      };
    } else {
      micBtn.style.display = 'none';
    }
  }

  // Simple Markdown & Link Formatter
  function formatMarkdown(str) {
    if (!str) return '';
    let html = escapeHtml(str);
    
    // Bold **text**
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    
    // Italic *text*
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
    
    // Headers ### text
    html = html.replace(/^### (.*$)/gim, '<h4>$1</h4>');
    html = html.replace(/^## (.*$)/gim, '<h3>$1</h3>');
    
    // Bullet points (• or -)
    html = html.replace(/^[•\-]\s+(.*$)/gim, '<li>$1</li>');
    html = html.replace(/(<li>.*<\/li>)/g, '<ul>$1</ul>');
    html = html.replace(/<\/ul>\s*<ul>/g, '');

    // Markdown Links [Text](URL)
    html = html.replace(/\[(.*?)\]\((.*?)\)/g, function(match, label, url) {
      return `<a href="${getRelativeUrl(url)}">${label}</a>`;
    });

    // Newlines to <br>
    html = html.replace(/\n/g, '<br>');
    
    return html;
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
  }

  // Initial Render
  renderHistory();
});
