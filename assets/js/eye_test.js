(function () {
  const state = {
    trackingStarted: false,
    acuityStarted: false,
    distanceCm: null,
    distanceOk: false,
    faceVisible: false,
    currentEye: 'right',
    mode: 'tumbling',
    levelIndex: 0,
    currentAnswer: 'right',
    challengeCursor: 1,
    currentLetter: 'E',
    missCount: 0,
    eyeScores: { left: null, right: null },
    colorAnswers: {},
    currentPlate: 0,
    voiceActive: false,
  };

  const levels = [
    { label: '20/200', size: 150 },
    { label: '20/100', size: 126 },
    { label: '20/70', size: 108 },
    { label: '20/50', size: 90 },
    { label: '20/40', size: 76 },
    { label: '20/30', size: 62 },
    { label: '20/25', size: 52 },
    { label: '20/20', size: 42 },
  ];
  const directions = ['up', 'right', 'down', 'left'];
  const rotations = { right: 0, down: 90, left: 180, up: 270 };
  const snellenLetters = ['E', 'F', 'P', 'T', 'O', 'Z', 'L', 'D', 'C'];
  const plates = [
    { expected: '12', seed: 101, fg: ['#D95F59', '#B9413F'], bg: ['#6FB97E', '#89C886'] },
    { expected: '8', seed: 202, fg: ['#D98245', '#B76637'], bg: ['#62A2A6', '#86BBB7'] },
    { expected: '29', seed: 303, fg: ['#9E6AC8', '#7E56A6'], bg: ['#8FAF59', '#AFC56D'] },
    { expected: '5', seed: 404, fg: ['#C8506B', '#9F425A'], bg: ['#58A497', '#78B9A9'] },
  ];

  const $ = selector => document.querySelector(selector);
  const $$ = selector => Array.from(document.querySelectorAll(selector));

  const els = {
    startTracking: $('#startTrackingBtn'),
    stopTracking: $('#stopTrackingBtn'),
    cameraBadge: $('#cameraBadge'),
    distanceBadge: $('#distanceBadge'),
    distanceValue: $('#distanceValue'),
    distanceText: $('#distanceText'),
    distanceFill: $('#distanceMeterFill'),
    video: $('#faceVideo'),
    canvas: $('#faceCanvas'),
    startAcuity: $('#startAcuityBtn'),
    acuityLevel: $('#acuityLevel'),
    optotype: $('#optotypeSymbol'),
    feedback: $('#acuityFeedback'),
    snellenAnswer: $('#snellenAnswer'),
    mode: $('#optotypeMode'),
    coverEyeHint: $('#coverEyeHint'),
    checkLetter: $('#checkLetterBtn'),
    skipLetter: $('#skipLetterBtn'),
    voice: $('#voiceControlBtn'),
    stopVoice: $('#stopVoiceControlBtn'),
    ishihara: $('#ishiharaCanvas'),
    plateSelector: $('#plateSelector'),
    plateOptions: $('#plateOptions'),
    plateAnswer: $('#plateAnswer'),
    plateFeedback: $('#plateFeedback'),
    submitPlate: $('#submitPlateBtn'),
    colorScore: $('#colorScoreBadge'),
    leftScore: $('#leftAcuityScore'),
    rightScore: $('#rightAcuityScore'),
    colorVisionScore: $('#colorVisionScore'),
    downloadReport: $('#downloadReportBtn'),
    printReport: $('#printReportBtn'),
  };

  let faceMesh = null;
  let camera = null;
  let ctx = null;
  let recognition = null;

  function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
  }

  function setBadge(el, text, tone) {
    el.textContent = text;
    el.classList.toggle('is-warning', tone === 'warning');
    el.classList.toggle('is-danger', tone === 'danger');
  }

  function setFeedback(text) {
    if (els.feedback) {
      els.feedback.textContent = text;
    }
  }

  function testPaused() {
    return false;
  }

  function updateControlLock() {
    const locked = testPaused();
    $$('.direction-pad button').forEach(button => button.disabled = locked);
    els.startAcuity.disabled = locked;
    if (els.checkLetter) els.checkLetter.disabled = locked;
    if (els.skipLetter) els.skipLetter.disabled = locked;
  }

  function updateDistance(distanceCm, faceVisible) {
    state.distanceCm = distanceCm;
    state.faceVisible = faceVisible;
    state.distanceOk = faceVisible && distanceCm >= 38 && distanceCm <= 58;

    if (!faceVisible) {
      els.distanceValue.textContent = '-- cm';
      els.distanceText.textContent = 'Face not detected. You can still continue the test manually.';
      els.distanceFill.style.width = '0%';
      setBadge(els.distanceBadge, 'Face not detected', 'warning');
      updateControlLock();
      return;
    }

    els.distanceValue.textContent = Math.round(distanceCm) + ' cm';
    els.distanceFill.style.width = clamp((distanceCm / 60) * 100, 0, 100) + '%';

    if (distanceCm < 38) {
      els.distanceText.textContent = 'Too close. Move back a little, but the test will keep working.';
      setBadge(els.distanceBadge, 'Too close', 'danger');
    } else if (state.distanceOk) {
      els.distanceText.textContent = 'Good distance. You can continue the test.';
      setBadge(els.distanceBadge, 'Good distance', 'success');
    } else {
      els.distanceText.textContent = 'Move slightly closer for a better distance check.';
      setBadge(els.distanceBadge, 'Adjust distance', 'warning');
    }

    updateControlLock();
    renderOptotype();
  }

  function drawFaceOverlay(results) {
    if (!ctx || !els.canvas.width) return;
    ctx.clearRect(0, 0, els.canvas.width, els.canvas.height);
    const landmarks = results.multiFaceLandmarks && results.multiFaceLandmarks[0];
    if (!landmarks) {
      updateDistance(null, false);
      return;
    }

    const left = landmarks[33];
    const right = landmarks[263];
    const eyePx = Math.hypot((right.x - left.x) * els.canvas.width, (right.y - left.y) * els.canvas.height);
    const distanceCm = (6.3 * 620) / Math.max(eyePx, 1);

    ctx.strokeStyle = distanceCm < 38 ? '#F87171' : '#34D399';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(left.x * els.canvas.width, left.y * els.canvas.height);
    ctx.lineTo(right.x * els.canvas.width, right.y * els.canvas.height);
    ctx.stroke();

    [left, right].forEach(point => {
      ctx.beginPath();
      ctx.arc(point.x * els.canvas.width, point.y * els.canvas.height, 5, 0, Math.PI * 2);
      ctx.fillStyle = '#67E8F9';
      ctx.fill();
    });

    updateDistance(distanceCm, true);
  }

  async function startTracking() {
    if (state.trackingStarted) return;
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      setBadge(els.cameraBadge, 'Camera unsupported', 'danger');
      els.distanceText.textContent = 'This browser does not expose camera access.';
      return;
    }

    try {
      state.trackingStarted = true;
      setBadge(els.cameraBadge, 'Requesting camera', 'warning');
      ctx = els.canvas.getContext('2d');

      if (window.FaceMesh && window.Camera) {
        faceMesh = new FaceMesh({
          locateFile: file => 'https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/' + file,
        });
        faceMesh.setOptions({
          maxNumFaces: 1,
          refineLandmarks: true,
          minDetectionConfidence: 0.6,
          minTrackingConfidence: 0.6,
        });
        faceMesh.onResults(drawFaceOverlay);
        camera = new Camera(els.video, {
          onFrame: async () => {
            els.canvas.width = els.video.videoWidth || 640;
            els.canvas.height = els.video.videoHeight || 480;
            await faceMesh.send({ image: els.video });
          },
          width: 640,
          height: 480,
        });
        await camera.start();
      } else {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        els.video.srcObject = stream;
        updateDistance(45, true);
        els.distanceText.textContent = 'Camera preview is active. Keep the screen about one arm away.';
      }

      els.startTracking.textContent = 'Camera Active';
      els.startTracking.disabled = true;
      els.stopTracking.disabled = false;
      setBadge(els.cameraBadge, 'Camera active', 'success');
    } catch (error) {
      state.trackingStarted = false;
      setBadge(els.cameraBadge, 'Camera blocked', 'danger');
      els.distanceText.textContent = 'Camera permission was denied or no camera was found.';
    }
  }

  function stopTracking() {
    if (camera && typeof camera.stop === 'function') {
      camera.stop();
    }
    if (els.video.srcObject) {
      els.video.srcObject.getTracks().forEach(track => track.stop());
      els.video.srcObject = null;
    }
    if (faceMesh && typeof faceMesh.close === 'function') {
      faceMesh.close();
    }
    camera = null;
    faceMesh = null;
    state.trackingStarted = false;
    state.distanceCm = null;
    state.distanceOk = false;
    state.faceVisible = false;
    els.distanceValue.textContent = '-- cm';
    els.distanceFill.style.width = '0%';
    els.distanceText.textContent = 'Camera stopped. You can continue the test manually.';
    if (ctx) ctx.clearRect(0, 0, els.canvas.width, els.canvas.height);
    els.startTracking.textContent = 'Start Camera';
    els.startTracking.disabled = false;
    els.stopTracking.disabled = true;
    setBadge(els.cameraBadge, 'Camera stopped', 'warning');
    setBadge(els.distanceBadge, 'Manual mode', 'warning');
    updateControlLock();
  }

  function renderOptotype() {
    const level = levels[state.levelIndex];
    const distanceScale = clamp((state.distanceCm || 45) / 45, 0.82, 1.24);
    const size = Math.round(level.size * distanceScale);
    els.acuityLevel.textContent = level.label;
    els.optotype.className = 'optotype';
    els.optotype.style.fontSize = size + 'px';
    els.optotype.style.width = Math.max(84, size * 1.45) + 'px';
    els.optotype.style.height = Math.max(84, size * 1.45) + 'px';

    if (state.mode === 'landolt') {
      els.optotype.textContent = '';
      els.optotype.classList.add('landolt');
      els.optotype.style.borderWidth = Math.max(8, Math.round(size * 0.16)) + 'px';
      els.optotype.style.transform = 'rotate(' + rotations[state.currentAnswer] + 'deg)';
      if (els.snellenAnswer) els.snellenAnswer.hidden = true;
    } else if (state.mode === 'snellen') {
      els.optotype.textContent = state.currentLetter;
      els.optotype.style.transform = 'rotate(0deg)';
      if (els.snellenAnswer) {
        els.snellenAnswer.hidden = false;
        els.snellenAnswer.value = '';
        els.snellenAnswer.focus({ preventScroll: true });
      }
    } else {
      els.optotype.textContent = 'E';
      els.optotype.style.transform = 'rotate(' + rotations[state.currentAnswer] + 'deg)';
      if (els.snellenAnswer) els.snellenAnswer.hidden = true;
    }
  }

  function nextChallenge() {
    state.currentAnswer = directions[state.challengeCursor % directions.length];
    state.challengeCursor += 1;
    state.currentLetter = snellenLetters[Math.floor(Math.random() * snellenLetters.length)];
    renderOptotype();
  }

  function startAcuity() {
    if (testPaused()) {
      updateControlLock();
      return;
    }
    state.acuityStarted = true;
    state.levelIndex = 0;
    state.missCount = 0;
    state.challengeCursor = directions.indexOf(state.currentAnswer) + 1;
    nextChallenge();
    updateEyeHint();
    setFeedback('Test started for ' + readableEyeName() + '. Press the direction where the E is facing.');
  }

  function scoreForCurrentProgress() {
    const index = clamp(state.levelIndex - (state.missCount ? 1 : 0), 0, levels.length - 1);
    return levels[index].label;
  }

  function answerAcuity(answer) {
    if (!state.acuityStarted) {
      state.acuityStarted = true;
      state.missCount = 0;
    }
    if (testPaused()) {
      updateControlLock();
      return;
    }

    let correct = false;
    if (answer === 'skip') {
      correct = false;
    } else if (state.mode === 'snellen') {
      correct = String((els.snellenAnswer && els.snellenAnswer.value) || '').trim().toUpperCase() === state.currentLetter;
    } else {
      correct = answer === state.currentAnswer;
    }

    if (correct) {
      state.missCount = 0;
      if (state.levelIndex < levels.length - 1) {
        state.levelIndex += 1;
        setFeedback('Correct. Now choose the new direction.');
        nextChallenge();
      } else {
        state.eyeScores[state.currentEye] = levels[state.levelIndex].label;
        state.acuityStarted = false;
        setFeedback('Done: ' + readableEyeName() + ' result is about ' + levels[state.levelIndex].label + '. Switch eyes and repeat.');
        nextChallenge();
        updateReport();
      }
    } else {
      state.missCount += 1;
      if (state.missCount >= 2) {
        state.eyeScores[state.currentEye] = scoreForCurrentProgress();
        state.acuityStarted = false;
        setFeedback('Done: ' + readableEyeName() + ' result is about ' + state.eyeScores[state.currentEye] + '. Switch eyes and repeat.');
        nextChallenge();
        updateReport();
      } else {
        setFeedback('No problem. Try one more direction at this size.');
        nextChallenge();
      }
    }
  }

  function readableEyeName() {
    return state.currentEye === 'right' ? 'right eye' : 'left eye';
  }

  function updateEyeHint() {
    if (!els.coverEyeHint) return;
    els.coverEyeHint.textContent = state.currentEye === 'right' ? 'Cover left eye' : 'Cover right eye';
  }

  function seedRandom(seed) {
    let value = seed % 2147483647;
    return function () {
      value = value * 16807 % 2147483647;
      return (value - 1) / 2147483646;
    };
  }

  function drawPlate(index) {
    const plate = plates[index];
    const canvas = els.ishihara;
    const context = canvas.getContext('2d');
    const mask = document.createElement('canvas');
    mask.width = canvas.width;
    mask.height = canvas.height;
    const maskCtx = mask.getContext('2d');
    const rand = seedRandom(plate.seed);

    context.clearRect(0, 0, canvas.width, canvas.height);
    context.fillStyle = '#F8FEFC';
    context.fillRect(0, 0, canvas.width, canvas.height);

    maskCtx.fillStyle = '#000';
    maskCtx.textAlign = 'center';
    maskCtx.textBaseline = 'middle';
    maskCtx.font = '900 150px Arial';
    maskCtx.fillText(plate.expected, canvas.width / 2, canvas.height / 2 + 8);
    const maskData = maskCtx.getImageData(0, 0, canvas.width, canvas.height).data;

    for (let i = 0; i < 620; i++) {
      const angle = rand() * Math.PI * 2;
      const radius = Math.sqrt(rand()) * 168;
      const x = canvas.width / 2 + Math.cos(angle) * radius;
      const y = canvas.height / 2 + Math.sin(angle) * radius;
      const px = (Math.floor(y) * canvas.width + Math.floor(x)) * 4 + 3;
      const inNumber = maskData[px] > 20;
      const palette = inNumber ? plate.fg : plate.bg;
      context.beginPath();
      context.arc(x, y, 5 + rand() * 8, 0, Math.PI * 2);
      context.fillStyle = palette[Math.floor(rand() * palette.length)];
      context.fill();
    }

    state.currentPlate = index;
    els.plateAnswer.value = '';
    renderPlateOptions(index);
    $$('#plateSelector button').forEach((button, buttonIndex) => button.classList.toggle('is-active', buttonIndex === index));
  }

  function renderPlateOptions(index) {
    const expected = plates[index].expected;
    const choicesByPlate = [
      ['12', '13', '17'],
      ['8', '3', '6'],
      ['29', '70', '21'],
      ['5', '2', '6'],
    ];
    els.plateOptions.innerHTML = '';
    choicesByPlate[index].forEach(choice => {
      const button = document.createElement('button');
      button.type = 'button';
      button.textContent = choice;
      button.addEventListener('click', () => {
        els.plateAnswer.value = choice;
        submitPlate();
      });
      els.plateOptions.appendChild(button);
    });
    const cannotSee = document.createElement('button');
    cannotSee.type = 'button';
    cannotSee.className = 'cannot-see';
    cannotSee.textContent = 'I cannot see it';
    cannotSee.addEventListener('click', () => {
      els.plateAnswer.value = '';
      submitPlate();
    });
    els.plateOptions.appendChild(cannotSee);
  }

  function submitPlate() {
    const plate = plates[state.currentPlate];
    const answer = String(els.plateAnswer.value || '').trim();
    state.colorAnswers[state.currentPlate] = answer === plate.expected;
    els.plateFeedback.textContent = state.colorAnswers[state.currentPlate]
      ? 'Correct. Moving to the next color plate.'
      : 'Answer recorded. Moving to the next color plate.';
    updateColorScore();
    const next = (state.currentPlate + 1) % plates.length;
    if (Object.keys(state.colorAnswers).length >= plates.length) {
      els.plateFeedback.textContent += ' Color test complete. Open Result to see the summary.';
    } else {
      setTimeout(() => drawPlate(next), 650);
    }
  }

  function updateColorScore() {
    const answered = Object.keys(state.colorAnswers).length;
    const correct = Object.values(state.colorAnswers).filter(Boolean).length;
    els.colorScore.textContent = correct + ' / ' + answered;
    updateReport();
  }

  function updateReport() {
    els.leftScore.textContent = state.eyeScores.left || 'Not tested';
    els.rightScore.textContent = state.eyeScores.right || 'Not tested';
    const answered = Object.keys(state.colorAnswers).length;
    const correct = Object.values(state.colorAnswers).filter(Boolean).length;
    els.colorVisionScore.textContent = answered ? correct + ' / ' + answered + ' plates' : 'Not tested';
  }

  function downloadReport() {
    updateReport();
    const html = '<!doctype html><meta charset="utf-8"><title>CARE Vision Screening Report</title>' +
      '<style>body{font-family:Arial,sans-serif;padding:28px;color:#0f172a}strong{font-size:24px;display:block;margin:6px 0 18px}</style>' +
      '<h1>CARE Vision Screening Report</h1>' +
      '<p>Left Eye</p><strong>' + els.leftScore.textContent + '</strong>' +
      '<p>Right Eye</p><strong>' + els.rightScore.textContent + '</strong>' +
      '<p>Color Vision</p><strong>' + els.colorVisionScore.textContent + '</strong>' +
      '<p>Screening tool only - Consult an Optometrist for medical prescriptions.</p>';
    const blob = new Blob([html], { type: 'text/html' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'care-vision-screening-report.html';
    link.click();
    URL.revokeObjectURL(url);
  }

  function spokenDirection(text) {
    const value = String(text || '').toLowerCase();
    const words = {
      up: ['up', 'upar', 'oper', 'upper'],
      down: ['down', 'neeche', 'niche', 'neechay'],
      left: ['left', 'baen', 'baayen'],
      right: ['right', 'daen', 'daayen'],
    };
    return directions.find(direction => words[direction].some(word => value.includes(word)));
  }

  function startVoiceControl() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
      setFeedback('Voice is not supported in this browser. Use Chrome or Edge, or use the direction buttons.');
      return;
    }
    stopVoiceControl(false);
    state.voiceActive = true;
    recognition = new SpeechRecognition();
    recognition.lang = 'en-US';
    recognition.interimResults = false;
    recognition.continuous = true;
    recognition.onresult = event => {
      const latest = event.results[event.results.length - 1][0].transcript.toLowerCase();
      const match = spokenDirection(latest);
      if (match) answerAcuity(match);
      else setFeedback('Voice heard "' + latest + '". Say up, down, left, or right.');
    };
    recognition.onerror = event => {
      if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
        setFeedback('Microphone permission is blocked. Allow microphone access or use the direction buttons.');
        stopVoiceControl(false);
        return;
      }
      setFeedback('Voice did not catch that. Say up, down, left, or right.');
    };
    recognition.onend = () => {
      if (!state.voiceActive) {
        els.voice.textContent = 'Start Voice';
        els.voice.disabled = false;
        els.stopVoice.disabled = true;
        return;
      }
      setTimeout(() => {
        if (!state.voiceActive || !recognition) return;
        try {
          recognition.start();
        } catch (error) {
          state.voiceActive = false;
          els.voice.textContent = 'Start Voice';
          els.voice.disabled = false;
          els.stopVoice.disabled = true;
        }
      }, 250);
    };
    try {
      recognition.start();
      els.voice.textContent = 'Voice Active';
      els.voice.disabled = true;
      els.stopVoice.disabled = false;
      setFeedback('Listening. Say up, down, left, right, upar, or neeche.');
    } catch (error) {
      state.voiceActive = false;
      setFeedback('Voice could not start. Use the direction buttons.');
    }
  }

  function stopVoiceControl(showMessage = true) {
    state.voiceActive = false;
    if (recognition) {
      recognition.onend = null;
      try {
        recognition.stop();
      } catch (error) {}
      recognition = null;
    }
    els.voice.textContent = 'Start Voice';
    els.voice.disabled = false;
    els.stopVoice.disabled = true;
    if (showMessage) {
      setFeedback('Voice stopped. You can use the direction buttons manually.');
    }
  }

  function wireEvents() {
    els.startTracking.addEventListener('click', startTracking);
    els.stopTracking.addEventListener('click', stopTracking);
    els.startAcuity.addEventListener('click', startAcuity);
    els.mode.addEventListener('change', event => {
      state.mode = event.target.value;
      nextChallenge();
      updateControlLock();
    });
    $$('.segmented-control [data-eye]').forEach(button => {
      button.addEventListener('click', () => {
        state.currentEye = button.dataset.eye;
        $$('.segmented-control [data-eye]').forEach(item => item.classList.toggle('is-active', item === button));
        updateEyeHint();
        startAcuity();
      });
    });
    $$('.direction-pad [data-answer]').forEach(button => button.addEventListener('click', () => answerAcuity(button.dataset.answer)));
    if (els.checkLetter) els.checkLetter.addEventListener('click', () => answerAcuity('snellen'));
    if (els.skipLetter) els.skipLetter.addEventListener('click', () => answerAcuity('skip'));
    document.addEventListener('keydown', event => {
      const keyMap = { ArrowUp: 'up', ArrowDown: 'down', ArrowLeft: 'left', ArrowRight: 'right', Enter: 'enter' };
      if (!keyMap[event.key]) return;
      if (event.key === 'Enter' && state.mode === 'snellen') answerAcuity('snellen');
      if (state.mode === 'snellen') return;
      if (keyMap[event.key] !== 'enter') {
        event.preventDefault();
        answerAcuity(keyMap[event.key]);
      }
    });
    if (els.snellenAnswer) {
      els.snellenAnswer.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
          event.stopPropagation();
          answerAcuity('snellen');
        }
      });
    }
    els.voice.addEventListener('click', startVoiceControl);
    els.stopVoice.addEventListener('click', () => stopVoiceControl(true));
    $$('.tab-button').forEach(button => {
      button.addEventListener('click', () => {
        $$('.tab-button').forEach(item => item.classList.toggle('is-active', item === button));
        $$('.tab-view').forEach(view => view.classList.remove('is-active'));
        $('#' + button.dataset.tab + 'Tab').classList.add('is-active');
        updateReport();
      });
    });
    els.submitPlate.addEventListener('click', submitPlate);
    els.printReport.addEventListener('click', () => {
      updateReport();
      window.print();
    });
    els.downloadReport.addEventListener('click', downloadReport);
    window.addEventListener('beforeunload', () => {
      stopVoiceControl(false);
      stopTracking();
    });
  }

  function initPlates() {
    plates.forEach((plate, index) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.textContent = 'Plate ' + (index + 1);
      button.addEventListener('click', () => drawPlate(index));
      els.plateSelector.appendChild(button);
    });
    drawPlate(0);
    updateColorScore();
  }

  document.addEventListener('DOMContentLoaded', () => {
    wireEvents();
    initPlates();
    nextChallenge();
    updateEyeHint();
    updateControlLock();
    updateReport();
  });
})();
