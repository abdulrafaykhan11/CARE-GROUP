<?php
$pageTitle = 'Easy Eye Test';
$extraStylesheets = ['assets/css/eye-test.css'];
include 'includes/header.php';
?>

<main class="eye-suite" data-eye-suite>
  <section class="eye-hero">
    <div>
      <p class="eyebrow">CARE VISION CHECK</p>
      <h1>Easy Eye Test</h1>
      <p class="eye-copy">Start with simple guidance, then match the direction of the E and complete an easy color-number test.</p>
    </div>
    <div class="eye-status-stack">
      <span class="eye-badge" id="cameraBadge">Camera idle</span>
      <span class="eye-badge is-warning" id="distanceBadge">Camera optional</span>
    </div>
  </section>

  <section class="vision-panel guide-panel" aria-labelledby="guideTitle">
    <div class="guide-copy">
      <span class="micro-label">READ THIS FIRST</span>
      <h2 id="guideTitle">Before you start</h2>
      <p>This is a quick screening, not a medical prescription. Sit comfortably, keep the screen about one arm away, and use normal room light.</p>
    </div>
    <div class="guide-steps">
      <article><strong>1</strong><span>Cover your left eye and test the right eye first.</span></article>
      <article><strong>2</strong><span>Look which side the E is facing, then press Up, Down, Left, or Right.</span></article>
      <article><strong>3</strong><span>Switch eyes, repeat, then do the color test by choosing the number you see.</span></article>
    </div>
  </section>

  <section class="eye-grid">
    <aside class="vision-panel camera-panel">
      <div class="panel-title">
        <div>
          <span class="micro-label">OPTIONAL CAMERA HELP</span>
          <h2>Distance Check</h2>
        </div>
        <div class="compact-actions">
          <button class="btn btn-primary" type="button" id="startTrackingBtn">Start Camera</button>
          <button class="btn btn-outline" type="button" id="stopTrackingBtn" disabled>Stop Camera</button>
        </div>
      </div>

      <div class="camera-stage">
        <video id="faceVideo" autoplay playsinline muted></video>
        <canvas id="faceCanvas"></canvas>
      </div>

      <div class="distance-readout">
        <strong id="distanceValue">-- cm</strong>
        <span id="distanceText">Camera is optional. You can start the direction test without it.</span>
      </div>
      <div class="distance-meter" aria-hidden="true"><span id="distanceMeterFill"></span></div>
      <p class="medical-disclaimer">If the camera feels confusing, leave it off. The test still works manually.</p>
    </aside>

    <section class="vision-panel test-panel">
      <div class="test-tabs" role="tablist" aria-label="Eye test modules">
        <button class="tab-button is-active" type="button" data-tab="acuity">1. Direction Test</button>
        <button class="tab-button" type="button" data-tab="color">2. Color Test</button>
        <button class="tab-button" type="button" data-tab="report">3. Result</button>
      </div>

      <div class="tab-view is-active" id="acuityTab">
        <div class="module-head">
          <div>
            <span class="micro-label">SIMPLE DIRECTION CHECK</span>
            <h2>Which way is E facing?</h2>
            <p class="module-help">Cover one eye. Look at the E, then press the matching direction button.</p>
          </div>
          <button class="btn btn-primary" type="button" id="startAcuityBtn">Start Direction Test</button>
        </div>

        <div class="control-row">
          <div class="segmented-control" aria-label="Eye selector">
            <button type="button" class="is-active" data-eye="right">Right Eye</button>
            <button type="button" data-eye="left">Left Eye</button>
          </div>
          <span class="eye-instruction-pill" id="coverEyeHint">Cover left eye</span>
          <select id="optotypeMode" aria-label="Optotype mode" hidden>
            <option value="tumbling" selected>Direction Test</option>
          </select>
        </div>

        <div class="acuity-stage" id="acuityStage">
          <span class="acuity-level" id="acuityLevel">20/200</span>
          <div class="optotype" id="optotypeSymbol">E</div>
          <p class="direction-hint">Press the button for the direction the E is facing.</p>
        </div>

        <div class="direction-pad" aria-label="Direction controls">
          <button type="button" data-answer="up">Up</button>
          <button type="button" data-answer="left">Left</button>
          <button type="button" data-answer="right">Right</button>
          <button type="button" data-answer="down">Down</button>
        </div>

        <div class="acuity-feedback">
          <span id="acuityFeedback">Press Start Direction Test when you are ready.</span>
          <div class="compact-actions">
            <button class="btn btn-outline" type="button" id="voiceControlBtn">Start Voice</button>
            <button class="btn btn-outline" type="button" id="stopVoiceControlBtn" disabled>Stop Voice</button>
          </div>
        </div>
      </div>

      <div class="tab-view" id="colorTab">
        <div class="module-head">
          <div>
            <span class="micro-label">COLOR NUMBER CHECK</span>
            <h2>Choose the number you see</h2>
            <p class="module-help">Look at the circle, then tap the matching number. If no number is clear, choose "I cannot see it".</p>
          </div>
          <span class="eye-badge" id="colorScoreBadge">0 / 4 recorded</span>
        </div>

        <div class="plate-layout">
          <canvas id="ishiharaCanvas" width="360" height="360"></canvas>
          <div class="plate-controls">
            <div class="plate-selector" id="plateSelector"></div>
            <div class="plate-options" id="plateOptions" aria-label="Color plate answer choices"></div>
            <div class="field manual-plate-entry">
              <label>NUMBER SEEN</label>
              <input type="number" id="plateAnswer" inputmode="numeric" placeholder="Enter number">
            </div>
            <button class="btn btn-primary" type="button" id="submitPlateBtn" hidden>Submit Plate</button>
            <p class="form-hint" id="plateFeedback">Select the number you can see in the colored circle.</p>
          </div>
        </div>
      </div>

      <div class="tab-view" id="reportTab">
        <div class="module-head">
          <div>
            <span class="micro-label">SCREENING SUMMARY</span>
            <h2>Your result</h2>
          </div>
          <div class="report-actions">
            <button class="btn btn-outline" type="button" id="downloadReportBtn">Download</button>
            <button class="btn btn-primary" type="button" id="printReportBtn">Print / PDF</button>
          </div>
        </div>

        <div class="report-card" id="reportCard">
          <div>
            <span>Left Eye</span>
            <strong id="leftAcuityScore">Not tested</strong>
          </div>
          <div>
            <span>Right Eye</span>
            <strong id="rightAcuityScore">Not tested</strong>
          </div>
          <div>
            <span>Color Vision</span>
            <strong id="colorVisionScore">Not tested</strong>
          </div>
          <p>This is only a simple screening. For glasses, eye pain, sudden blur, or color-vision concerns, consult an optometrist or eye doctor.</p>
        </div>

        <div class="report-detail-grid" id="reportDetailGrid">
          <article class="report-section">
            <span class="micro-label">PERFORMANCE NOTES</span>
            <h3>What needs attention</h3>
            <ul id="mistakeList">
              <li>Complete the test to see a detailed review.</li>
            </ul>
          </article>

          <article class="report-section">
            <span class="micro-label">TIME TRACKING</span>
            <h3>Response timing</h3>
            <div class="timing-grid">
              <div><span>Total time</span><strong id="totalTimeValue">--</strong></div>
              <div><span>Average answer</span><strong id="averageTimeValue">--</strong></div>
              <div><span>Slowest answer</span><strong id="slowestTimeValue">--</strong></div>
            </div>
          </article>

          <article class="report-section">
            <span class="micro-label">EYE CARE PLAN</span>
            <h3>Exercises</h3>
            <ul id="exerciseList"></ul>
          </article>

          <article class="report-section">
            <span class="micro-label">FOOD SUPPORT</span>
            <h3>Diet suggestions</h3>
            <ul id="dietList"></ul>
          </article>

          <article class="report-section report-section-wide">
            <span class="micro-label">DAILY HABITS</span>
            <h3>What to do and avoid</h3>
            <div class="dos-grid">
              <div>
                <h4>Do</h4>
                <ul id="doList"></ul>
              </div>
              <div>
                <h4>Do not</h4>
                <ul id="dontList"></ul>
              </div>
            </div>
          </article>
        </div>
      </div>
    </section>
  </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/face_mesh.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js"></script>
<script src="assets/js/eye_test.js"></script>

<?php include 'includes/footer.php'; ?>
