<?php
// CARE MediBot Floating AI Chatbot Component
// Included globally across public pages and dashboard portals
?>
<!-- MediBot Stylesheet -->
<link rel="stylesheet" href="<?= (strpos($_SERVER['REQUEST_URI'], '/patient/') !== false || strpos($_SERVER['REQUEST_URI'], '/doctor/') !== false || strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../assets/css/chatbot.css' : 'assets/css/chatbot.css' ?>">

<!-- Floating Trigger Launcher Button -->
<button id="care-chat-trigger" aria-label="Open CARE MediBot AI Chatbot">
  <span class="trigger-icon">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2 2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/>
      <path d="M4 11a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7z"/>
      <circle cx="9" cy="15" r="1"/>
      <circle cx="15" cy="15" r="1"/>
    </svg>
  </span>
  <span>CARE MediBot</span>
  <span class="trigger-badge" title="AI Agent Active"></span>
</button>

<!-- Glassmorphic Chat Widget Window -->
<div id="care-chat-window" role="dialog" aria-label="CARE MediBot AI Assistant Window">
  <!-- Header -->
  <div class="care-chat-header">
    <div class="care-chat-header-info">
      <div class="care-chat-avatar-wrapper">
        <svg viewBox="0 0 24 24">
          <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/>
        </svg>
        <span class="care-chat-status-dot"></span>
      </div>
      <div class="care-chat-title-group">
        <h4>CARE MediBot</h4>
        <p>VERIFIED HEALTH AI</p>
      </div>
    </div>
    <div class="care-chat-header-actions">
      <button class="care-chat-btn-icon" id="care-chat-clear" title="Clear Chat History">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <polyline points="3 6 5 6 21 6"></polyline>
          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
        </svg>
      </button>
      <button class="care-chat-btn-icon" id="care-chat-close" title="Minimize Chatbot">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
  </div>

  <!-- Quick Action Chips -->
  <div class="care-chat-chips-wrap">
    <button class="care-chat-chip" data-prompt="Find verified doctors in Karachi">🩺 Find Doctors</button>
    <button class="care-chat-chip" data-prompt="How do I book an appointment on CARE Nexus?">📅 How to Book</button>
    <button class="care-chat-chip" data-prompt="What are the 24/7 emergency contact numbers?">🚨 Emergency</button>
    <button class="care-chat-chip" data-prompt="What should I do for a sudden high fever?">💊 Symptom Advice</button>
  </div>

  <!-- Messages Body -->
  <div class="care-chat-body" id="care-chat-messages">
    <!-- Messages injected dynamically by assets/js/chatbot.js -->
  </div>

  <!-- Footer Input Controls -->
  <div class="care-chat-footer">
    <div class="care-chat-input-wrapper">
      <input type="text" id="care-chat-input-text" class="care-chat-input" placeholder="Ask about health, doctors, or booking..." autocomplete="off">
      <button id="care-chat-mic" class="care-chat-mic-btn" title="Voice Input (Dictate Query)">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
          <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
          <line x1="12" y1="19" x2="12" y2="23"/>
          <line x1="8" y1="23" x2="16" y2="23"/>
        </svg>
      </button>
    </div>
    <button id="care-chat-send" class="care-chat-send-btn" title="Send Message">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <line x1="22" y1="2" x2="11" y2="13"></line>
        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
      </svg>
    </button>
  </div>
</div>

<!-- MediBot Script -->
<script src="<?= (strpos($_SERVER['REQUEST_URI'], '/patient/') !== false || strpos($_SERVER['REQUEST_URI'], '/doctor/') !== false || strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../assets/js/chatbot.js' : 'assets/js/chatbot.js' ?>"></script>
