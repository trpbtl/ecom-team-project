// Packe 3D designer (views/customize.php). One page, many packaging types.
// The page passes a template (data-config, from controllers/DesignTypes.php): which shape, its size fields,
// prices and quantities. The 3D models, folds and flat layouts come from js/packaging3d.js (shared with the home page).
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { createKit } from 'packe3d';

const $ = id => document.getElementById(id);
const form = $('design-form');
const CFG = JSON.parse(form.dataset.config);
const TYPE = CFG.type;

const state = { L: 0, W: 0, H: 0, material: 'kraft', ink: '', logo: null, logoScale: 0.55, text: '', qty: CFG.qtys[1], fold: 0 };

/* ---------- Read the form ---------- */
function readForm() {
  for (const [k, key] of [['length', 'L'], ['width', 'W'], ['height', 'H']]) {
    const f = CFG.fields[k];
    if (!f) continue;
    const v = parseFloat($(k).value);
    if (v >= f[1] && v <= f[2]) state[key] = v;            // ignore out-of-range values while typing
    else if (!state[key]) state[key] = f[3];
  }
  if (!CFG.fields.width) state.W = state.L;   // a round sticker is as wide as it is long
  if (!CFG.fields.height) state.H = 0;
  state.material = form.querySelector('input[name=material]:checked').value;
  state.ink = $('print_colour').value;
  state.text = $('brandText').value.trim();
  const [, family, weight] = CFG.fonts[form.querySelector('input[name=font]:checked')?.value] || [];
  state.fontFamily = family || ''; state.fontWeight = weight || 700;
  state.logoScale = $('logoScale').value / 100;
  state.qty = parseInt(form.querySelector('input[name=quantity]:checked').value, 10);
}

/* ---------- Price estimate: same rules as DesignTypes::area / unitPrice on the server ---------- */
function area(L, W, H) {
  switch (TYPE) {
    case 'foodbox': { const g = H * Math.sin(0.18), Lt = L + 2 * g, Wt = W + 2 * g; return L * W + H * (L + Lt + W + Wt) + 1.46 * Lt * Wt; }
    case 'mailer': { const lidD = W - 0.4; return L * W + 2 * L * H + 2 * H * W + L * lidD + (L - 0.8) * H * 0.8 + 2 * (H * 0.85) * (lidD * 0.8); }
    case 'carton': return (2 * L + 2 * W + 3) * (H + W);
    case 'pouch': return 2 * L * W + L * H;
    case 'sticker': return Math.PI * (L / 2) ** 2;
    case 'catalog': return L * W * H;
    default: return L * W;
  }
}
function unitPrice(qty) {
  let u = CFG.base + area(state.L, state.W, state.H) * CFG.rate * (state.material === 'white' ? 1.15 : 1);
  if (state.ink) u += CFG.ink;
  if (state.logo || state.text) u += CFG.brand;
  const step = Math.max(0, CFG.qtys.indexOf(qty));
  return Math.round(u * CFG.factors[step] * 100) / 100;
}
const fmt = v => 'GH₵' + v.toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
function renderPrice() {
  for (const q of CFG.qtys) {
    const el = document.querySelector(`[data-tier-price="${q}"]`);
    if (el) el.textContent = fmt(unitPrice(q));
  }
  const u = unitPrice(state.qty);
  $('total').textContent = fmt(u * state.qty);
  $('unit').textContent = `${state.qty.toLocaleString()} ${CFG.noun[1]} × ${fmt(u)} each`;
}

/* ---------- 3D setup (falls back to the flat view if WebGL is unavailable) ---------- */
const canvas = $('gl');
let renderer, scene, camera, controls, ground;
try {
  renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
} catch (err) {
  $('gl-error').hidden = false;
}


let model = null, build = null;

if (renderer) {
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
  renderer.outputColorSpace = THREE.SRGBColorSpace;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.shadowMap.enabled = true;
  renderer.shadowMap.type = THREE.PCFSoftShadowMap;

  scene = new THREE.Scene();
  scene.environment = new THREE.PMREMGenerator(renderer).fromScene(new RoomEnvironment(renderer), 0.04).texture;
  camera = new THREE.PerspectiveCamera(32, 1, 0.1, 2000);
  controls = new OrbitControls(camera, canvas);
  controls.enableDamping = true;
  controls.maxPolarAngle = Math.PI * 0.49;
  controls.autoRotateSpeed = 1.6;

  const sun = new THREE.DirectionalLight(0xffffff, 1.4);
  sun.position.set(30, 60, 40);
  sun.castShadow = true;
  sun.shadow.mapSize.set(2048, 2048);
  Object.assign(sun.shadow.camera, { left: -80, right: 80, top: 80, bottom: -80 });
  sun.shadow.radius = 6; sun.shadow.bias = -0.0005;
  scene.add(sun, new THREE.HemisphereLight(0xffffff, 0xd9cbb5, 0.35));

  ground = new THREE.Mesh(new THREE.PlaneGeometry(600, 600), new THREE.ShadowMaterial({ opacity: 0.16 }));
  ground.rotation.x = -Math.PI / 2; ground.receiveShadow = true;
  scene.add(ground);

}


const kit = createKit(renderer);
const clamp01 = v => Math.min(1, Math.max(0, v));

/* ---------- Build, frame and render ---------- */
function buildModel() {
  if (model && scene) { scene.remove(model); }
  build?.dispose();
  build = kit.build(TYPE, state);
  model = build.group;
  if (!renderer) return;
  ground.position.y = build.ground ?? -0.02;
  scene.add(model);
  build.fold?.(state.fold);
}

function frame() {
  if (!renderer) return;
  const m = build.view.size;
  if (build.view.dir) {
    const d = new THREE.Vector3(...build.view.dir).normalize().multiplyScalar(m * 2.3);
    camera.position.set(d.x, d.y + build.view.y, d.z);
  } else {
    camera.position.set(m * 1.9, m * 1.7, m * 2.5);
  }
  controls.target.set(0, build.view.y, 0);
  controls.minDistance = m * 0.6; controls.maxDistance = m * 5;
  camera.near = m * 0.01; camera.updateProjectionMatrix();
  controls.update();
}
function resize() {
  if (!renderer) return;
  const r = canvas.getBoundingClientRect();
  if (!r.width || !r.height) return;
  renderer.setSize(r.width, r.height, false);
  camera.aspect = r.width / r.height; camera.updateProjectionMatrix();
}
new ResizeObserver(resize).observe(canvas);

let anim = null;
function animateFold(to) {
  if (!renderer || !build?.fold) return;
  const from = state.fold, start = performance.now();
  const dur = matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : Math.abs(to - from) * 18 + 200;
  anim = now => {
    const t = dur ? clamp01((now - start) / dur) : 1;
    state.fold = from + (to - from) * t;
    if ($('fold')) $('fold').value = state.fold;
    build.fold(state.fold);
    if (t >= 1) anim = null;
  };
}
if (renderer) {
  renderer.setAnimationLoop(now => {
    if (anim) anim(now);
    controls.autoRotate = $('spin').checked;
    controls.update();
    renderer.render(scene, camera);
  });
}

/* ---------- Flat layout (only drawn while it is on screen) ---------- */
function drawDieline() {
  if ($('dieline').hidden) return;
  const { vb, body, note } = build.dieline();
  const pad = Math.max(vb[2], vb[3]) * 0.05;
  $('dieSvg').outerHTML = `<svg id="dieSvg" class="flex-1 w-full min-h-0" role="img" aria-label="Flat print layout" xmlns="http://www.w3.org/2000/svg" viewBox="${vb[0] - pad} ${vb[1] - pad} ${vb[2] + 2 * pad} ${vb[3] + 2 * pad}" preserveAspectRatio="xMidYMid meet">${body}</svg>`;
  $('dieDims').textContent = note;
}

/* ---------- Wiring ---------- */
let queued = false, lastSize = '';
function refresh() {
  if (queued) return; queued = true;
  requestAnimationFrame(() => {
    queued = false;
    readForm(); buildModel(); drawDieline(); renderPrice();
    const size = `${state.L}x${state.W}x${state.H}`;
    if (size !== lastSize) { lastSize = size; frame(); }
  });
}
form.addEventListener('input', e => { if (e.target.id !== 'customInk' && e.target.id !== 'logo') refresh(); });
form.addEventListener('change', e => {
  if (e.target.name === 'material' || e.target.name === 'quantity') refresh();
  if (e.target.name === 'font') {
    // A canvas can only draw a web font once it has downloaded, so wait for it, then redraw
    const [, family, weight] = CFG.fonts[e.target.value] || [];
    if (family && document.fonts) document.fonts.load(`${weight} 48px "${family}"`).then(refresh, refresh);
    else refresh();
  }
});

$('presets').addEventListener('click', e => {
  const b = e.target.closest('[data-size]'); if (!b) return;
  b.dataset.size.split(',').forEach((v, i) => { const el = $(['length', 'width', 'height'][i]); if (el) el.value = v; });
  refresh();
});

function setInk(hex, el) {
  $('print_colour').value = hex;
  document.querySelectorAll('#inks [data-ink]').forEach(s => s.setAttribute('aria-pressed', String(s === el)));
  refresh();
}
$('inks').addEventListener('click', e => { const b = e.target.closest('[data-ink]'); if (b) setInk(b.dataset.ink, b); });
$('customInk').addEventListener('input', e => setInk(e.target.value, null));

$('logo').addEventListener('change', e => {
  const f = e.target.files[0];
  if (!f) return;
  if (!['image/png', 'image/jpeg', 'image/webp'].includes(f.type) || f.size > 5 * 1024 * 1024) return;   // validate.js shows the message
  const reader = new FileReader();
  reader.onload = () => {
    const img = new Image();
    img.onload = () => {
      state.logo = img;
      $('logoThumb').src = reader.result; $('logoThumb').hidden = false;
      $('logoMeta').textContent = `${f.name} · ${img.width}×${img.height}px`;
      refresh();
    };
    img.src = reader.result;
  };
  reader.readAsDataURL(f);
});

// Fold controls (only on shapes that fold). Brochures and catalogues use their own words.
if ($('fold')) {
  const words = { brochure: ['Flat', 'Half', 'Folded'], catalog: ['Open', 'Half', 'Closed'] }[TYPE];
  if (words) document.querySelectorAll('[data-fold]').forEach((b, i) => { b.textContent = words[i]; if (i === 1) b.dataset.fold = 50; });
  $('fold').addEventListener('input', e => { anim = null; state.fold = +e.target.value; build.fold(state.fold); });
  document.querySelectorAll('[data-fold]').forEach(b => b.addEventListener('click', () => animateFold(+b.dataset.fold)));
}

function setTab(is3d) {
  $('tab3d').setAttribute('aria-pressed', String(is3d)); $('tab2d').setAttribute('aria-pressed', String(!is3d));
  $('dieline').hidden = is3d; $('foldbar').hidden = !is3d;
  if (!is3d) drawDieline();
}
$('tab3d').addEventListener('click', () => setTab(true));
$('tab2d').addEventListener('click', () => setTab(false));
if (!renderer) setTab(false);

/* ---------- Start: show it flat, then fold it up ---------- */
readForm(); resize(); buildModel(); renderPrice(); frame();
lastSize = `${state.L}x${state.W}x${state.H}`;
document.fonts?.ready.then(refresh);
if (build.start) setTimeout(() => animateFold(build.start), 500);
