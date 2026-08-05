<?php
$pageTitle = 'Interactive Eye Testing Suite';
$extraStylesheets = ['assets/css/eye-test.css'];
include 'includes/header.php';
?>

<main class="eye-suite" data-eye-suite>
  <section class="eye-hero">
    <div>
      <p class="eyebrow">CARE VISION LAB</p>
      <h1>Eye Testing Suite</h1>
      <p class="eye-copy">Live distance-aware acuity checks, orientation controls, color screening, and a printable clinical summary.</p>
    </div>
    <div class="eye-status-stack">
      <span class="eye-badge" id="cameraBadge">Camera idle</span>
      <span class="eye-badge is-warning" id="distanceBadge">Distance not calibrated</span>
    </div>
  </section>

  <section class="eye-grid">
    <aside class="vision-panel camera-panel">
      <div class="panel-title">
        <div>
          <span class="micro-label">FACE & DISTANCE TRACKING</span>
          <h2>Camera Calibration</h2>
        </div>
        <button class="btn btn-primary" type="button" id="startTrackingBtn">Start Camera</button>
      </div>

      <div class="camera-stage">
        <video id="faceVideo" autoplay playsinline muted></video>
        <canvas id="faceCanvas"></canvas>
      </div>

      <div class="distance-readout">
        <strong id="distanceValue">-- cm</strong>
        <span id="distanceText">Keep your face visible to begin calibration.</span>
      </div>
      <div class="distance-meter" aria-hidden="true"><span id="distanceMeterFill"></span></div>
      <p class="medical-disclaimer">Screening tool only - Consult an Optometrist for medical prescriptions.</p>
    </aside>

    <section class="vision-panel test-panel">
      <div class="test-tabs" role="tablist" aria-label="Eye test modules">
        <button class="tab-button is-active" type="button" data-tab="acuity">Acuity</button>
        <button class="tab-button" type="button" data-tab="color">Color</button>
        <button class="tab-button" type="button" data-tab="report">Report</button>
      </div>

      <div class="tab-view is-active" id="acuityTab">
        <div class="module-head">
          <div>
            <span class="micro-label">DYNAMIC VISION TEST</span>
            <h2>Optotype Challenge</h2>
          </div>
          <button class="btn btn-outline" type="button" id="startAcuityBtn">Start Test</button>
        </div>

        <div class="control-row">
          <div class="segmented-control" aria-label="Eye selector">
            <button type="button" class="is-active" data-eye="left">Left Eye</button>
            <button type="button" data-eye="right">Right Eye</button>
          </div>
          <select id="optotypeMode" aria-label="Optotype mode">
            <option value="tumbling">Tumbling E</option>
            <option value="landolt">Landolt C</option>
            <option value="snellen">Snellen Letters</option>
          </select>
        </div>

        <div class="acuity-stage" id="acuityStage">
          <span class="acuity-level" id="acuityLevel">20/200</span>
          <div class="optotype" id="optotypeSymbol">E</div>
          <input class="snellen-answer" id="snellenAnswer" maxlength="1" placeholder="Letter" hidden>
        </div>

        <div class="direction-pad" aria-label="Orientation controls">
          <button type="button" data-answer="up">Up</button>
          <button type="button" data-answer="left">Left</button>
          <button type="button" data-answer="right">Right</button>
          <button type="button" data-answer="down">Down</button>
        </div>

        <div class="acuity-feedback">
          <span id="acuityFeedback">Awaiting first optotype.</span>
          <button class="btn btn-outline" type="button" id="voiceControlBtn">Voice Control</button>
        </div>
      </div>

      <div class="tab-view" id="colorTab">
        <div class="module-head">
          <div>
            <span class="micro-label">ISHIHARA SCREENING</span>
            <h2>Color Plate Check</h2>
          </div>
          <span class="eye-badge" id="colorScoreBadge">0 / 0</span>
        </div>

        <div class="plate-layout">
          <canvas id="ishiharaCanvas" width="360" height="360"></canvas>
          <div class="plate-controls">
            <div class="plate-selector" id="plateSelector"></div>
            <div class="field">
              <label>NUMBER SEEN</label>
              <input type="number" id="plateAnswer" inputmode="numeric" placeholder="Enter number">
            </div>
            <button class="btn btn-primary" type="button" id="submitPlateBtn">Submit Plate</button>
            <p class="form-hint" id="plateFeedback">Select a plate and enter the number you see.</p>
          </div>
        </div>
      </div>

      <div class="tab-view" id="reportTab">
        <div class="module-head">
          <div>
            <span class="micro-label">AUTOMATED SUMMARY</span>
            <h2>Diagnostic Report</h2>
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
          <p>Screening tool only - Consult an Optometrist for medical prescriptions.</p>
        </div>
      </div>
    </section>
  </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/face_mesh.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js"></script>
<script src="assets/js/eye_test.js"></script>

<?php include 'includes/footer.php'; ?>
