/* CARE Nexus MediBot chat UI */
document.addEventListener('DOMContentLoaded', function () {
  const triggerBtn = document.getElementById('care-chat-trigger');
  const chatWindow = document.getElementById('care-chat-window');
  const closeBtn = document.getElementById('care-chat-close');
  const clearBtn = document.getElementById('care-chat-clear');
  const bodyEl = document.getElementById('care-chat-messages');
  const inputEl = document.getElementById('care-chat-input-text');
  const sendBtn = document.getElementById('care-chat-send');
  const micBtn = document.getElementById('care-chat-mic');
  const chips = document.querySelectorAll('.care-chat-chip');

  if (!triggerBtn || !chatWindow || !bodyEl || !inputEl) return;

  function getApiEndpoint() {
    const path = window.location.pathname;
    if (path.includes('/patient/') || path.includes('/doctor/') || path.includes('/admin/')) {
      return '../api/chat_handler.php';
    }
    return 'api/chat_handler.php';
  }

  function getRelativeUrl(targetUrl) {
    const path = window.location.pathname;
    if (path.includes('/patient/') || path.includes('/doctor/') || path.includes('/admin/')) {
      return '../' + targetUrl;
    }
    return targetUrl;
  }

  let chatHistory = [];
  try {
    chatHistory = JSON.parse(localStorage.getItem('care_medibot_history') || '[]');
  } catch (error) {
    chatHistory = [];
  }

  function saveHistory() {
    try {
      localStorage.setItem('care_medibot_history', JSON.stringify(chatHistory.slice(-24)));
    } catch (error) {
      console.warn('LocalStorage save failed:', error);
    }
  }

  function scrollToBottom() {
    setTimeout(function () {
      bodyEl.scrollTop = bodyEl.scrollHeight;
    }, 50);
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text || '';
    return div.innerHTML;
  }

  function formatMarkdown(str) {
    if (!str) return '';
    let html = escapeHtml(str);

    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
    html = html.replace(/^### (.*$)/gim, '<h4>$1</h4>');
    html = html.replace(/^## (.*$)/gim, '<h3>$1</h3>');
    html = html.replace(/^[\-\u2022]\s+(.*$)/gim, '<li>$1</li>');
    html = html.replace(/(<li>.*<\/li>)/g, '<ul>$1</ul>');
    html = html.replace(/<\/ul>\s*<ul>/g, '');
    html = html.replace(/\[(.*?)\]\((.*?)\)/g, function (match, label, url) {
      return '<a href="' + escapeHtml(getRelativeUrl(url)) + '">' + label + '</a>';
    });
    html = html.replace(/\n/g, '<br>');

    return html;
  }

  function sourceLabel(source) {
    if (source === 'website_knowledge') return 'Verified CARE knowledge';
    if (source === 'guardrail') return 'Healthcare scope';
    return 'Medical AI guidance';
  }

  function sourceClass(source) {
    if (source === 'guardrail') return ' is-warning';
    if (source === 'website_knowledge') return ' is-care';
    return '';
  }

  function appendUserMessageDOM(text) {
    const msgDiv = document.createElement('div');
    msgDiv.className = 'care-chat-msg user';
    msgDiv.innerHTML = '<div class="care-chat-bubble">' + escapeHtml(text) + '</div>';
    bodyEl.appendChild(msgDiv);
    scrollToBottom();
  }

  function appendBotMessageDOM(text, source = 'gemini_ai', actions = []) {
    const msgDiv = document.createElement('div');
    msgDiv.className = 'care-chat-msg bot';

    let actionsHtml = '';
    if (actions && actions.length) {
      actionsHtml = '<div class="care-chat-actions-group">';
      actions.forEach(function (action) {
        actionsHtml += '<a href="' + escapeHtml(getRelativeUrl(action.url)) + '" class="care-chat-action-btn">' + escapeHtml(action.label) + '</a>';
      });
      actionsHtml += '</div>';
    }

    msgDiv.innerHTML =
      '<div class="care-chat-bubble">' +
        formatMarkdown(text) +
        actionsHtml +
      '</div>' +
      '<div class="care-chat-source-tag' + sourceClass(source) + '">' + sourceLabel(source) + '</div>';

    bodyEl.appendChild(msgDiv);
    scrollToBottom();
  }

  function appendUserMessage(text) {
    chatHistory.push({ sender: 'user', text: text });
    saveHistory();
    appendUserMessageDOM(text);
  }

  function appendBotMessage(text, source = 'gemini_ai', actions = []) {
    chatHistory.push({ sender: 'bot', text: text, source: source, actions: actions });
    saveHistory();
    appendBotMessageDOM(text, source, actions);
  }

  function renderHistory() {
    bodyEl.innerHTML = '';
    if (!chatHistory.length) {
      appendBotMessage(
        'Ask about a symptom, disease, medical field, doctor, clinic, diet, lab test, or booking. I will check CARE data first and then give practical medical guidance when needed.',
        'website_knowledge',
        [
          { label: 'Find Doctor', url: 'find_doctor.php' },
          { label: 'Emergency Contacts', url: 'index.php#contact' },
        ]
      );
      return;
    }

    chatHistory.forEach(function (item) {
      if (item.sender === 'user') {
        appendUserMessageDOM(item.text);
      } else {
        appendBotMessageDOM(item.text, item.source, item.actions);
      }
    });
    scrollToBottom();
  }

  function showTyping() {
    const typingDiv = document.createElement('div');
    typingDiv.id = 'care-chat-typing-indicator';
    typingDiv.className = 'care-chat-msg bot';
    typingDiv.innerHTML = '<div class="care-chat-typing"><span></span><span></span><span></span></div>';
    bodyEl.appendChild(typingDiv);
    scrollToBottom();
  }

  function hideTyping() {
    const el = document.getElementById('care-chat-typing-indicator');
    if (el) el.remove();
  }

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
        history: chatHistory.slice(-8),
      }),
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        hideTyping();
        if (data.status === 'success') {
          appendBotMessage(data.reply, data.source || 'gemini_ai', data.actions || []);
        } else {
          appendBotMessage(data.reply || 'Something went wrong. Please try again.', 'guardrail');
        }
      })
      .catch(function (error) {
        hideTyping();
        console.error('Chat error:', error);
        appendBotMessage('Connection error. Please check the server and try again.', 'guardrail');
      });
  }

  triggerBtn.addEventListener('click', function () {
    chatWindow.classList.toggle('active');
    if (chatWindow.classList.contains('active')) {
      inputEl.focus();
      scrollToBottom();
    }
  });

  if (closeBtn) {
    closeBtn.addEventListener('click', function () {
      chatWindow.classList.remove('active');
    });
  }

  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      if (confirm('Clear your MediBot conversation history?')) {
        chatHistory = [];
        localStorage.removeItem('care_medibot_history');
        renderHistory();
      }
    });
  }

  if (sendBtn) {
    sendBtn.addEventListener('click', sendMessage);
  }

  inputEl.addEventListener('keydown', function (event) {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      sendMessage();
    }
  });

  chips.forEach(function (chip) {
    chip.addEventListener('click', function () {
      inputEl.value = chip.getAttribute('data-prompt') || chip.innerText;
      sendMessage();
    });
  });

  if (micBtn) {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
      const recognition = new SpeechRecognition();
      recognition.continuous = false;
      recognition.interimResults = false;
      recognition.lang = 'en-US';

      micBtn.addEventListener('click', function () {
        if (micBtn.classList.contains('listening')) {
          recognition.stop();
          micBtn.classList.remove('listening');
          return;
        }
        try {
          recognition.start();
          micBtn.classList.add('listening');
        } catch (error) {
          console.error('Speech recognition error:', error);
        }
      });

      recognition.onresult = function (event) {
        inputEl.value = event.results[0][0].transcript;
        micBtn.classList.remove('listening');
        inputEl.focus();
      };
      recognition.onerror = function () {
        micBtn.classList.remove('listening');
      };
      recognition.onend = function () {
        micBtn.classList.remove('listening');
      };
    } else {
      micBtn.style.display = 'none';
    }
  }

  renderHistory();
});
