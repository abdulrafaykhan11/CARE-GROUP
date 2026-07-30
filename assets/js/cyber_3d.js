/**
 * CARE Group - Cybernetic Clinical 3D WebGL & GSAP Animation Engine
 * Features:
 * 1. Three.js Medical 3D DNA Double Helix & Cardiac Bio-Mesh Core (Hero Canvas)
 * 2. Dynamic Holographic City Network Matrix with Real DB Doctor Counts
 * 3. GSAP 3 ScrollTrigger Shard Reveals & Micro-animations
 * 4. 3D Tilt Cards & Glowing Mouse Tracking for All Cards
 * 5. Interactive Admin Chart Hover Tooltips
 */

(function() {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    initHeroMedicalDNA3D();
    initHolographicMap();
    initGSAPAnimations();
    initCard3DTilt();
    initCounterStats();
    initChartHoverTooltips();
  });

  /* ----------------------------------------------------
     1. THREE.JS 3D MEDICAL DNA DOUBLE HELIX & BIO CORE
  ---------------------------------------------------- */
  function initHeroMedicalDNA3D() {
    const container = document.getElementById('hero-canvas-3d');
    if (!container || typeof THREE === 'undefined') return;

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(48, container.clientWidth / container.clientHeight, 0.1, 1000);
    camera.position.set(0, 4, 135);

    const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
    renderer.setSize(container.clientWidth, container.clientHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    container.appendChild(renderer.domElement);

    scene.add(new THREE.AmbientLight(0xffffff, 0.34));

    const keyLight = new THREE.PointLight(0x9fffe0, 1.45, 220);
    keyLight.position.set(-45, 46, 82);
    scene.add(keyLight);

    const rimLight = new THREE.PointLight(0x22c55e, 1.1, 220);
    rimLight.position.set(60, -28, 74);
    scene.add(rimLight);

    const dnaGroup = new THREE.Group();
    dnaGroup.position.set(28, -2, 0);
    scene.add(dnaGroup);

    const basePairs = 38;
    const curvePoints = 180;
    const strandRadius = 22;
    const helixHeight = 128;
    const turns = 3.9;
    const strandA = [];
    const strandB = [];
    const nucleotideMeshes = [];
    const bondMeshes = [];
    const palette = [0x22c55e, 0x14b8a6, 0x60a5fa, 0xa7f3d0];

    function helixPoint(index, offset = 0) {
      const progress = index / (curvePoints - 1);
      const angle = progress * turns * Math.PI * 2 + offset;
      const taper = 1 - Math.abs(progress - 0.5) * 0.16;
      return new THREE.Vector3(
        Math.cos(angle) * strandRadius * taper,
        (progress - 0.5) * helixHeight,
        Math.sin(angle) * strandRadius * taper
      );
    }

    for (let i = 0; i < curvePoints; i++) {
      strandA.push(helixPoint(i, 0));
      strandB.push(helixPoint(i, Math.PI));
    }

    const strandMaterialA = new THREE.MeshStandardMaterial({
      color: 0x34d399,
      emissive: 0x064e3b,
      emissiveIntensity: 0.8,
      roughness: 0.28,
      metalness: 0.18
    });

    const strandMaterialB = new THREE.MeshStandardMaterial({
      color: 0x5eead4,
      emissive: 0x0f766e,
      emissiveIntensity: 0.7,
      roughness: 0.3,
      metalness: 0.16
    });

    const strandTubeA = new THREE.Mesh(
      new THREE.TubeGeometry(new THREE.CatmullRomCurve3(strandA), 220, 1.35, 14, false),
      strandMaterialA
    );
    const strandTubeB = new THREE.Mesh(
      new THREE.TubeGeometry(new THREE.CatmullRomCurve3(strandB), 220, 1.35, 14, false),
      strandMaterialB
    );
    dnaGroup.add(strandTubeA, strandTubeB);

    const sphereGeo = new THREE.SphereGeometry(3.15, 20, 20);
    const cylinderGeo = new THREE.CylinderGeometry(0.52, 0.52, 1, 12);
    const upVector = new THREE.Vector3(0, 1, 0);

    function makeMaterial(color, opacity = 0.9) {
      return new THREE.MeshStandardMaterial({
        color,
        emissive: color,
        emissiveIntensity: 0.34,
        transparent: true,
        opacity,
        roughness: 0.25,
        metalness: 0.12
      });
    }

    function createBond(start, end, color) {
      const direction = new THREE.Vector3().subVectors(end, start);
      const midpoint = new THREE.Vector3().addVectors(start, end).multiplyScalar(0.5);
      const bond = new THREE.Mesh(cylinderGeo, makeMaterial(color, 0.66));

      bond.scale.y = direction.length();
      bond.position.copy(midpoint);
      bond.quaternion.setFromUnitVectors(upVector, direction.clone().normalize());
      bond.userData.phase = Math.random() * Math.PI * 2;
      dnaGroup.add(bond);
      bondMeshes.push(bond);
    }

    for (let i = 0; i < basePairs; i++) {
      const sample = Math.floor((i / (basePairs - 1)) * (curvePoints - 1));
      const a = strandA[sample];
      const b = strandB[sample];
      const colorA = palette[i % palette.length];
      const colorB = palette[(i + 2) % palette.length];

      const nodeA = new THREE.Mesh(sphereGeo, makeMaterial(colorA));
      const nodeB = new THREE.Mesh(sphereGeo, makeMaterial(colorB));
      nodeA.position.copy(a);
      nodeB.position.copy(b);
      nodeA.userData.phase = i * 0.28;
      nodeB.userData.phase = i * 0.28 + Math.PI;
      dnaGroup.add(nodeA, nodeB);
      nucleotideMeshes.push(nodeA, nodeB);

      createBond(a, b, i % 2 ? 0x86efac : 0x93c5fd);
    }

    const orbitGroup = new THREE.Group();
    const orbitMat = new THREE.MeshBasicMaterial({
      color: 0x22c55e,
      transparent: true,
      opacity: 0.18
    });
    for (let i = 0; i < 3; i++) {
      const ring = new THREE.Mesh(new THREE.TorusGeometry(34 + i * 7, 0.42, 12, 96), orbitMat.clone());
      ring.rotation.x = Math.PI / 2 + i * 0.42;
      ring.rotation.y = i * 0.58;
      orbitGroup.add(ring);
    }
    dnaGroup.add(orbitGroup);

    const dustCount = 150;
    const dustGeo = new THREE.BufferGeometry();
    const dustPos = new Float32Array(dustCount * 3);

    for (let i = 0; i < dustCount; i++) {
      dustPos[i * 3] = (Math.random() - 0.5) * 210;
      dustPos[i * 3 + 1] = (Math.random() - 0.5) * 150;
      dustPos[i * 3 + 2] = (Math.random() - 0.5) * 130;
    }
    dustGeo.setAttribute('position', new THREE.BufferAttribute(dustPos, 3));

    const dustMat = new THREE.PointsMaterial({
      size: 2.2,
      color: 0xa7f3d0,
      transparent: true,
      opacity: 0.52,
      blending: THREE.AdditiveBlending
    });
    const dustMesh = new THREE.Points(dustGeo, dustMat);
    scene.add(dustMesh);

    const ecgPoints = [
      new THREE.Vector3(-92, -54, -18),
      new THREE.Vector3(-64, -54, -18),
      new THREE.Vector3(-54, -41, -18),
      new THREE.Vector3(-46, -68, -18),
      new THREE.Vector3(-34, -54, -18),
      new THREE.Vector3(0, -54, -18),
      new THREE.Vector3(10, -45, -18),
      new THREE.Vector3(18, -58, -18),
      new THREE.Vector3(60, -54, -18),
      new THREE.Vector3(94, -54, -18)
    ];
    const ecgGeo = new THREE.BufferGeometry().setFromPoints(ecgPoints);
    const ecgLine = new THREE.Line(
      ecgGeo,
      new THREE.LineBasicMaterial({ color: 0x22c55e, transparent: true, opacity: 0.58 })
    );
    scene.add(ecgLine);

    dnaGroup.rotation.z = -0.18;
    dnaGroup.rotation.x = 0.18;

    let mouseX = 0, mouseY = 0;
    let targetX = 0, targetY = 0;
    const clock = new THREE.Clock();

    window.addEventListener('mousemove', (e) => {
      mouseX = (e.clientX - window.innerWidth / 2) * 0.05;
      mouseY = (e.clientY - window.innerHeight / 2) * 0.05;
    });

    function animate() {
      requestAnimationFrame(animate);

      const elapsed = clock.getElapsedTime();
      targetX += (mouseX - targetX) * 0.05;
      targetY += (mouseY - targetY) * 0.05;

      dnaGroup.rotation.y += 0.011;
      dnaGroup.position.y = Math.sin(elapsed * 0.8) * 2.2;
      orbitGroup.rotation.y -= 0.006;
      orbitGroup.rotation.z += 0.004;
      dustMesh.rotation.y -= 0.0016;
      dustMesh.rotation.x += 0.0007;
      ecgLine.material.opacity = 0.28 + Math.sin(elapsed * 3.5) * 0.16 + 0.16;
      ecgLine.position.x = Math.sin(elapsed * 0.9) * 8;

      nucleotideMeshes.forEach(mesh => {
        const pulse = 1 + Math.sin(elapsed * 3.2 + mesh.userData.phase) * 0.14;
        mesh.scale.setScalar(pulse);
      });

      bondMeshes.forEach(mesh => {
        mesh.material.opacity = 0.46 + Math.sin(elapsed * 2.6 + mesh.userData.phase) * 0.14;
      });

      dnaGroup.rotation.x = 0.18 + targetY * 0.005;
      dnaGroup.rotation.z = -0.18 + targetX * 0.005;

      renderer.render(scene, camera);
    }
    animate();

    window.addEventListener('resize', () => {
      if (!container) return;
      camera.aspect = container.clientWidth / container.clientHeight;
      camera.updateProjectionMatrix();
      renderer.setSize(container.clientWidth, container.clientHeight);
    });
  }

  /* ----------------------------------------------------
     2. DYNAMIC HOLOGRAPHIC CITY NETWORK MATRIX (REAL DB COUNTS)
  ---------------------------------------------------- */
  function initHolographicMap() {
    const canvas = document.getElementById('holographic-map-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let width = canvas.width = canvas.parentElement.clientWidth || 800;
    let height = canvas.height = 360;

    // Fetch dynamic doctor count per city from PHP if available
    const dbCounts = window.CITY_DOCTOR_COUNTS || {};

    const cityNodes = [
      { name: 'Karachi', x: 0.22, y: 0.78, count: (dbCounts['Karachi'] || 0) + ' Verified' },
      { name: 'Lahore', x: 0.68, y: 0.42, count: (dbCounts['Lahore'] || 0) + ' Verified' },
      { name: 'Islamabad', x: 0.62, y: 0.25, count: (dbCounts['Islamabad'] || 0) + ' Verified' },
      { name: 'Rawalpindi', x: 0.60, y: 0.28, count: (dbCounts['Rawalpindi'] || 0) + ' Verified' },
      { name: 'Peshawar', x: 0.48, y: 0.22, count: (dbCounts['Peshawar'] || 0) + ' Verified' },
      { name: 'Multan', x: 0.52, y: 0.55, count: (dbCounts['Multan'] || 0) + ' Verified' },
      { name: 'Faisalabad', x: 0.58, y: 0.46, count: (dbCounts['Faisalabad'] || 0) + ' Verified' },
      { name: 'Quetta', x: 0.28, y: 0.52, count: (dbCounts['Quetta'] || 0) + ' Verified' }
    ];

    let scanLineY = 0;
    let selectedCity = null;

    function drawMap() {
      ctx.clearRect(0, 0, width, height);

      // Grid Lines
      ctx.strokeStyle = 'rgba(0, 242, 254, 0.06)';
      ctx.lineWidth = 1;
      const gridSize = 30;

      for (let x = 0; x < width; x += gridSize) {
        ctx.beginPath();
        ctx.moveTo(x, 0);
        ctx.lineTo(x, height);
        ctx.stroke();
      }
      for (let y = 0; y < height; y += gridSize) {
        ctx.beginPath();
        ctx.moveTo(0, y);
        ctx.lineTo(width, y);
        ctx.stroke();
      }

      // Synapse Lines
      ctx.strokeStyle = 'rgba(0, 242, 254, 0.25)';
      ctx.lineWidth = 1.2;
      ctx.setLineDash([4, 4]);

      for (let i = 0; i < cityNodes.length; i++) {
        for (let j = i + 1; j < cityNodes.length; j++) {
          const n1 = cityNodes[i];
          const n2 = cityNodes[j];

          const px1 = n1.x * width;
          const py1 = n1.y * height;
          const px2 = n2.x * width;
          const py2 = n2.y * height;

          const dist = Math.hypot(px2 - px1, py2 - py1);
          if (dist < width * 0.35) {
            ctx.beginPath();
            ctx.moveTo(px1, py1);
            ctx.lineTo(px2, py2);
            ctx.stroke();
          }
        }
      }
      ctx.setLineDash([]);

      // Radar Scanline
      scanLineY += 1.5;
      if (scanLineY > height) scanLineY = 0;

      const grad = ctx.createLinearGradient(0, scanLineY - 20, 0, scanLineY + 5);
      grad.addColorStop(0, 'rgba(0, 242, 254, 0)');
      grad.addColorStop(0.8, 'rgba(0, 242, 254, 0.3)');
      grad.addColorStop(1, 'rgba(0, 242, 254, 0.8)');

      ctx.fillStyle = grad;
      ctx.fillRect(0, scanLineY - 20, width, 25);

      // Render Nodes
      const now = Date.now() * 0.003;

      cityNodes.forEach(node => {
        const nx = node.x * width;
        const ny = node.y * height;
        const isHovered = selectedCity && selectedCity.name === node.name;

        // Pulse Ring
        const pulse = (Math.sin(now + nx) + 1) * 6 + 6;
        ctx.strokeStyle = isHovered ? '#10B981' : 'rgba(0, 242, 254, 0.6)';
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.arc(nx, ny, pulse, 0, Math.PI * 2);
        ctx.stroke();

        // Solid Core
        ctx.fillStyle = isHovered ? '#10B981' : '#00F2FE';
        ctx.shadowColor = isHovered ? '#10B981' : '#00F2FE';
        ctx.shadowBlur = 14;
        ctx.beginPath();
        ctx.arc(nx, ny, 5, 0, Math.PI * 2);
        ctx.fill();
        ctx.shadowBlur = 0;

        // Node Label
        ctx.font = '600 11px "Space Grotesk", sans-serif';
        ctx.fillStyle = isHovered ? '#10B981' : '#E2E8F0';
        ctx.fillText(node.name.toUpperCase(), nx + 12, ny + 4);

        ctx.font = '400 9px "JetBrains Mono", monospace';
        ctx.fillStyle = 'rgba(148, 163, 184, 0.85)';
        ctx.fillText(node.count, nx + 12, ny + 16);
      });

      requestAnimationFrame(drawMap);
    }

    drawMap();

    // Mouse Interaction
    canvas.addEventListener('mousemove', (e) => {
      const rect = canvas.getBoundingClientRect();
      const mx = e.clientX - rect.left;
      const my = e.clientY - rect.top;

      let found = null;
      cityNodes.forEach(node => {
        const nx = node.x * width;
        const ny = node.y * height;
        if (Math.hypot(mx - nx, my - ny) < 22) {
          found = node;
        }
      });
      selectedCity = found;
      canvas.style.cursor = found ? 'pointer' : 'default';
    });

    canvas.addEventListener('click', () => {
      if (selectedCity) {
        const citySelect = document.querySelector('select[name="city"]');
        if (citySelect) {
          for (let opt of citySelect.options) {
            if (opt.text.toLowerCase().includes(selectedCity.name.toLowerCase())) {
              citySelect.value = opt.value;
              const form = citySelect.closest('form');
              if (form) form.submit();
              break;
            }
          }
        }
      }
    });

    window.addEventListener('resize', () => {
      width = canvas.width = canvas.parentElement.clientWidth || 800;
      height = canvas.height = 360;
    });
  }

  /* ----------------------------------------------------
     3. GSAP SCROLLTRIGGER & REVEAL ANIMATIONS
  ---------------------------------------------------- */
  function initGSAPAnimations() {
    if (typeof gsap === 'undefined') return;

    if (typeof ScrollTrigger !== 'undefined') {
      gsap.registerPlugin(ScrollTrigger);
    }

    gsap.from('.cyber-hero h1', {
      opacity: 0,
      y: 40,
      duration: 1.2,
      ease: 'power3.out'
    });

    gsap.from('.cyber-hero .eyebrow-badge', {
      opacity: 0,
      scale: 0.8,
      duration: 0.8,
      delay: 0.2,
      ease: 'back.out(1.7)'
    });

    if (typeof ScrollTrigger !== 'undefined') {
      gsap.utils.toArray('.doctor-card, .profile-shard, .hud-metric, .cyber-table-wrap, .admin-stat-card').forEach((card, index) => {
        gsap.from(card, {
          scrollTrigger: {
            trigger: card,
            start: 'top 90%',
            toggleActions: 'play none none none'
          },
          opacity: 0,
          y: 35,
          scale: 0.96,
          duration: 0.7,
          delay: (index % 4) * 0.1,
          ease: 'power3.out'
        });
      });
    }
  }

  /* ----------------------------------------------------
     4. 3D TILT EFFECT FOR ALL CARDS & SHARDS
  ---------------------------------------------------- */
  function initCard3DTilt() {
    const cards = document.querySelectorAll('.doctor-card, .profile-shard, .cyber-glass-panel, .cyber-card, .hud-metric, .admin-stat-card');

    cards.forEach(card => {
      card.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const centerX = rect.width / 2;
        const centerY = rect.height / 2;

        const rotateX = (centerY - y) / 16;
        const rotateY = (x - centerX) / 16;

        card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-5px)`;
        card.style.setProperty('--mouse-x', `${x}px`);
        card.style.setProperty('--mouse-y', `${y}px`);
      });

      card.addEventListener('mouseleave', () => {
        card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateY(0px)';
      });
    });
  }

  /* ----------------------------------------------------
     5. LIVE COUNTER NUMBERS ANIMATION
  ---------------------------------------------------- */
  function initCounterStats() {
    const counters = document.querySelectorAll('[data-counter]');
    if (!counters.length) return;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const el = entry.target;
          const target = parseInt(el.getAttribute('data-counter'), 10) || 0;
          let current = 0;
          const step = Math.max(1, Math.floor(target / 40));
          const timer = setInterval(() => {
            current += step;
            if (current >= target) {
              el.textContent = target.toLocaleString();
              clearInterval(timer);
            } else {
              el.textContent = current.toLocaleString();
            }
          }, 30);
          observer.unobserve(el);
        }
      });
    }, { threshold: 0.5 });

    counters.forEach(c => observer.observe(c));
  }

  /* ----------------------------------------------------
     6. INTERACTIVE CHART HOVER TOOLTIPS IN ADMIN
  ---------------------------------------------------- */
  function initChartHoverTooltips() {
    const bars = document.querySelectorAll('.funnel-row, [data-chart-bar]');
    bars.forEach(bar => {
      bar.addEventListener('mouseenter', () => {
        bar.style.transform = 'scale(1.03)';
        bar.style.transition = 'transform 0.2s ease';
      });
      bar.addEventListener('mouseleave', () => {
        bar.style.transform = 'scale(1)';
      });
    });
  }

})();
