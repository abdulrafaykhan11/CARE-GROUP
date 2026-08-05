import os

# 1. UPDATE FOOTER
footer_path = 'd:/xampp/htdocs/care/includes/footer.php'
with open(footer_path, 'r', encoding='utf-8') as f:
    footer = f.read()

# Social Links
footer = footer.replace(
    '<a href="#" style="width: 36px; height: 36px; border-radius: 50%; background: rgba(2, 132, 199, 0.1); color: var(--cyan-neon); display: flex; align-items: center; justify-content: center; transition: all 0.3s;" onmouseover="this.style.background=\'var(--cyan-neon)\'; this.style.color=\'#fff\';" onmouseout="this.style.background=\'rgba(2, 132, 199, 0.1)\'; this.style.color=\'var(--cyan-neon)\';">',
    '<a href="https://www.facebook.com/login/" target="_blank" style="width: 36px; height: 36px; border-radius: 50%; background: rgba(2, 132, 199, 0.1); color: var(--cyan-neon); display: flex; align-items: center; justify-content: center; transition: all 0.3s;" onmouseover="this.style.background=\'var(--cyan-neon)\'; this.style.color=\'#fff\';" onmouseout="this.style.background=\'rgba(2, 132, 199, 0.1)\'; this.style.color=\'var(--cyan-neon)\';">',
    1
)

footer = footer.replace(
    '<a href="#" style="width: 36px; height: 36px; border-radius: 50%; background: rgba(2, 132, 199, 0.1); color: var(--cyan-neon); display: flex; align-items: center; justify-content: center; transition: all 0.3s;" onmouseover="this.style.background=\'var(--cyan-neon)\'; this.style.color=\'#fff\';" onmouseout="this.style.background=\'rgba(2, 132, 199, 0.1)\'; this.style.color=\'var(--cyan-neon)\';">',
    '<a href="https://twitter.com/login" target="_blank" style="width: 36px; height: 36px; border-radius: 50%; background: rgba(2, 132, 199, 0.1); color: var(--cyan-neon); display: flex; align-items: center; justify-content: center; transition: all 0.3s;" onmouseover="this.style.background=\'var(--cyan-neon)\'; this.style.color=\'#fff\';" onmouseout="this.style.background=\'rgba(2, 132, 199, 0.1)\'; this.style.color=\'var(--cyan-neon)\';">',
    1
)

footer = footer.replace(
    '<a href="#" style="width: 36px; height: 36px; border-radius: 50%; background: rgba(2, 132, 199, 0.1); color: var(--cyan-neon); display: flex; align-items: center; justify-content: center; transition: all 0.3s;" onmouseover="this.style.background=\'var(--cyan-neon)\'; this.style.color=\'#fff\';" onmouseout="this.style.background=\'rgba(2, 132, 199, 0.1)\'; this.style.color=\'var(--cyan-neon)\';">',
    '<a href="https://www.linkedin.com/login" target="_blank" style="width: 36px; height: 36px; border-radius: 50%; background: rgba(2, 132, 199, 0.1); color: var(--cyan-neon); display: flex; align-items: center; justify-content: center; transition: all 0.3s;" onmouseover="this.style.background=\'var(--cyan-neon)\'; this.style.color=\'#fff\';" onmouseout="this.style.background=\'rgba(2, 132, 199, 0.1)\'; this.style.color=\'var(--cyan-neon)\';">',
    1
)

# Subscribe form
old_form = """<form action="#" method="POST" style="display: flex; gap: 8px;" onsubmit="event.preventDefault();">
          <input type="email" placeholder="Your email address" style="flex: 1; padding: 12px 16px; border: 1px solid rgba(203, 213, 225, 0.9); border-radius: var(--radius-sm); font-family: var(--font-body); font-size: 13px; outline: none; transition: border 0.3s;" onfocus="this.style.borderColor=\'var(--cyan-neon)\';" onblur="this.style.borderColor=\'rgba(203, 213, 225, 0.9)\';">
          <button type="submit" class="btn btn-primary" style="padding: 12px 20px; border-radius: var(--radius-sm);">
            Subscribe
          </button>
        </form>"""

new_form = """<form id="footerSubscribeForm" action="#" method="POST" style="display: flex; gap: 8px;">
          <input type="email" name="email" id="subscribeEmailInput" placeholder="Your email address" style="flex: 1; padding: 12px 16px; border: 1px solid rgba(203, 213, 225, 0.9); border-radius: var(--radius-sm); font-family: var(--font-body); font-size: 13px; outline: none; transition: border 0.3s;" onfocus="this.style.borderColor=\'var(--cyan-neon)\';" onblur="this.style.borderColor=\'rgba(203, 213, 225, 0.9)\';">
          <button type="submit" class="btn btn-primary" style="padding: 12px 20px; border-radius: var(--radius-sm);">
            Subscribe
          </button>
        </form>
        <p id="subscribeFeedback" style="color: var(--emerald-bio); font-size: 12px; margin-top: 8px; font-weight: bold; display: none;"></p>"""

footer = footer.replace(old_form, new_form)

# Add script just before </body>
script_tag = """
  <script>
    if(document.getElementById('footerSubscribeForm')) {
        document.getElementById('footerSubscribeForm').addEventListener('submit', function(e) {
          e.preventDefault();
          var email = document.getElementById('subscribeEmailInput').value;
          if(!email) return;
          var feedback = document.getElementById('subscribeFeedback');
          
          // Fix URL path to always point to root ajax_subscribe.php regardless of current directory
          var pathPrefix = window.location.pathname.includes('/admin/') || window.location.pathname.includes('/doctor/') || window.location.pathname.includes('/patient/') ? '../' : '';
          
          fetch(pathPrefix + 'ajax_subscribe.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'email=' + encodeURIComponent(email)
          }).then(res => res.text()).then(res => {
            feedback.textContent = 'Successful! You have subscribed.';
            feedback.style.display = 'block';
            document.getElementById('subscribeEmailInput').value = '';
            setTimeout(() => { feedback.style.display = 'none'; }, 5000);
          });
        });
    }
  </script>
</body>"""

footer = footer.replace('</body>', script_tag)

with open(footer_path, 'w', encoding='utf-8') as f:
    f.write(footer)

# 2. CREATE AJAX_SUBSCRIBE.PHP
ajax_code = """<?php
require_once __DIR__ . '/config/mail.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Send email
        $subject = "CARE Nexus Newsletter Subscription";
        $body = "<html><body><h3>Thank you for subscribing!</h3><p>You have successfully subscribed to the CARE Nexus Newsletter. We will keep you updated with the latest health articles and medical news.</p></body></html>";
        sendEmail($email, $subject, $body);
        echo "Success";
    }
}
?>"""
with open('d:/xampp/htdocs/care/ajax_subscribe.php', 'w', encoding='utf-8') as f:
    f.write(ajax_code)

# 3. UPDATE EYE TEST JS
eye_js = 'd:/xampp/htdocs/care/assets/js/eye_test.js'
with open(eye_js, 'r', encoding='utf-8') as f:
    js = f.read()

# Randomize direction
js = js.replace('state.currentAnswer = directions[state.challengeCursor % directions.length];', 'state.currentAnswer = directions[Math.floor(Math.random() * directions.length)];')

# Interim results and debounce for voice
old_voice = """    recognition.interimResults = false;
    recognition.continuous = true;
    recognition.onresult = event => {
      const latest = event.results[event.results.length - 1][0].transcript.toLowerCase();
      const match = spokenDirection(latest);
      if (match) answerAcuity(match);
      else setFeedback('Voice heard "' + latest + '". Say up, down, left, or right.');
    };"""

new_voice = """    let lastProcessedTime = 0;
    recognition.interimResults = true;
    recognition.continuous = true;
    recognition.onresult = event => {
      const latest = event.results[event.results.length - 1][0].transcript.toLowerCase();
      const match = spokenDirection(latest);
      const now = Date.now();
      if (match && (now - lastProcessedTime > 1500)) {
        lastProcessedTime = now;
        answerAcuity(match);
      } else if (!match && event.results[event.results.length - 1].isFinal) {
        setFeedback('Voice heard "' + latest + '". Say up, down, left, or right.');
      }
    };"""

js = js.replace(old_voice, new_voice)

with open(eye_js, 'w', encoding='utf-8') as f:
    f.write(js)

print("Patch applied successfully.")
