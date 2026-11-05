// Packe 3D box designer (views/customize.php).
// Builds a mailer box from its flat dieline: each panel hangs on a hinge with an axis, an angle and a fold step.
// Artwork is drawn onto a canvas per panel and wrapped on as a texture, so the 3D view and the flat dieline match.
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';

const $ = id => document.getElementById(id);
const form = $('design-form');
const BASE_PRICE = parseFloat(form.dataset.basePrice);
const TIERS = JSON.parse(form.dataset.tiers);          // { "50": 1, "100": 0.88, ... } from CustomizationController
const LIMITS = { length: [10, 60], width: [8, 50], height: [3, 30] };
const T = 0.3;   // board thickness, cm
const PX = 28;   // texture pixels per cm

const state = { L: 30, W: 22, H: 10, material: 'kraft', ink: '', logo: null, logoScale: 0.55, text: '', qty: 100, fold: 0 };

/* ---------- Read the form ---------- */
function readForm() {
  for (const [k, key] of [['length', 'L'], ['width', 'W'], ['height', 'H']]) {
    const v = parseFloat($(k).value), [lo, hi] = LIMITS[k];
    if (v >= lo && v <= hi) state[key] = v;            // ignore out-of-range values while typing
  }
  state.material = form.querySelector('input[name=material]:checked').value;
  state.ink = $('print_colour').value;
  state.text = $('brandText').value.trim();
  state.logoScale = $('logoScale').value / 100;
  state.qty = parseInt(form.querySelector('input[name=quantity]:checked').value, 10);
}

/* ---------- Price estimate: same formula as CustomizationController::unitPrice ---------- */
function boardArea(L, W, H) {
  const lidD = W - 0.4, tuckH = H * 0.8, earW = H * 0.85, earD = lidD * 0.8;
  return L * W + 2 * L * H + 2 * H * W + L * lidD + (L - 0.8) * tuckH + 2 * earW * earD;
}
function unitPrice(qty) {
  let u = BASE_PRICE + boardArea(state.L, state.W, state.H) * 0.0011 * (state.material === 'white' ? 1.15 : 1);
  if (state.ink) u += 0.9;
  if (state.logo || state.text) u += 0.6;
  return Math.round(u * TIERS[qty] * 100) / 100;
}
const fmt = v => 'GH₵' + v.toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
function renderPrice() {
  for (const q of Object.keys(TIERS)) {
    const el = document.querySelector(`[data-tier-price="${q}"]`);
    if (el) el.textContent = fmt(unitPrice(+q));
  }
  const u = unitPrice(state.qty);
  $('total').textContent = fmt(u * state.qty);
  $('unit').textContent = `${state.qty} boxes × ${fmt(u)} each`;
}

/* ---------- 3D setup (falls back to the flat view if WebGL is unavailable) ---------- */
const canvas = $('gl');
let renderer, scene, camera, controls;
try {
  renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
} catch (err) {
  $('gl-error').hidden = false;
}

const kraftCanvas = (() => {
  const c = document.createElement('canvas'); c.width = c.height = 256;
  const x = c.getContext('2d');
  x.fillStyle = '#c79c6b'; x.fillRect(0, 0, 256, 256);
  for (let i = 0; i < 2600; i++) {
    const a = Math.random() * Math.PI, len = 2 + Math.random() * 8, px = Math.random() * 256, py = Math.random() * 256;
    x.strokeStyle = Math.random() < 0.5 ? `rgba(95,62,30,${0.05 + Math.random() * 0.12})` : `rgba(245,220,185,${0.05 + Math.random() * 0.12})`;
    x.lineWidth = 0.5 + Math.random() * 0.9;
    x.beginPath(); x.moveTo(px, py); x.lineTo(px + Math.cos(a) * len, py + Math.sin(a) * len); x.stroke();
  }
  return c;
})();

let edgeMat, maxAniso = 1, box = null, piv = {}, dims = {};
const disposables = [];

if (renderer) {
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
  renderer.outputColorSpace = THREE.SRGBColorSpace;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.shadowMap.enabled = true;
  renderer.shadowMap.type = THREE.PCFSoftShadowMap;
  maxAniso = renderer.capabilities.getMaxAnisotropy();

  scene = new THREE.Scene();
  scene.environment = new THREE.PMREMGenerator(renderer).fromScene(new RoomEnvironment(renderer), 0.04).texture;
  camera = new THREE.PerspectiveCamera(32, 1, 0.5, 2000);
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

  const ground = new THREE.Mesh(new THREE.PlaneGeometry(600, 600), new THREE.ShadowMaterial({ opacity: 0.16 }));
  ground.rotation.x = -Math.PI / 2; ground.position.y = -T / 2 - 0.02; ground.receiveShadow = true;
  scene.add(ground);

  const ec = document.createElement('canvas'); ec.width = 64; ec.height = 8;
  const ex = ec.getContext('2d');
  ex.fillStyle = '#b48857'; ex.fillRect(0, 0, 64, 8); ex.fillStyle = '#8f6a40';
  for (let i = 0; i < 64; i += 4) ex.fillRect(i, 2, 2, 4);
  const et = new THREE.CanvasTexture(ec); et.wrapS = et.wrapT = THREE.RepeatWrapping; et.repeat.set(8, 1); et.colorSpace = THREE.SRGBColorSpace;
  edgeMat = new THREE.MeshStandardMaterial({ map: et, roughness: 1 });
}

/* ---------- Artwork per panel ---------- */
const lum = hex => { const n = parseInt(hex.slice(1), 16); return (0.2126 * (n >> 16) + 0.7152 * ((n >> 8) & 255) + 0.0722 * (n & 255)) / 255; };
const textColour = () => state.ink ? (lum(state.ink) < 0.45 ? '#fbf8f2' : '#1c1a18') : (state.material === 'kraft' ? '#2b1d12' : '#1c1a18');

function drawText(x, txt, cx, cy, size, maxW) {
  if (!txt) return;
  x.fillStyle = textColour(); x.textAlign = 'center'; x.textBaseline = 'middle';
  let s = size;
  do { x.font = `700 ${s}px Archivo, Arial, sans-serif`; s *= 0.92; } while (x.measureText(txt).width > maxW && s > 8);
  x.fillText(txt, cx, cy);
}
function drawLogo(x, cx, cy, maxW, maxH) {
  const img = state.logo; if (!img) return;
  const r = Math.min(maxW / img.width, maxH / img.height);
  x.drawImage(img, cx - img.width * r / 2, cy - img.height * r / 2, img.width * r, img.height * r);
}
function panelCanvas(w, d, art) {
  const c = document.createElement('canvas');
  c.width = Math.max(32, Math.min(2048, Math.round(w * PX)));
  c.height = Math.max(32, Math.min(2048, Math.round(d * PX)));
  const x = c.getContext('2d'), cw = c.width, ch = c.height;
  x.fillStyle = state.material === 'kraft' ? x.createPattern(kraftCanvas, 'repeat') : '#f4f2ee';
  x.fillRect(0, 0, cw, ch);
  if (state.ink) {
    x.save();
    if (state.material === 'kraft') { x.globalCompositeOperation = 'multiply'; x.globalAlpha = 0.93; }   // ink soaks into brown board
    x.fillStyle = state.ink; x.fillRect(0, 0, cw, ch); x.restore();
  }
  if (art === 'lid') {
    const s = Math.min(cw, ch) * state.logoScale;
    if (state.logo) {
      drawLogo(x, cw / 2, ch / 2 - (state.text ? ch * 0.07 : 0), s * 1.4, s);
      drawText(x, state.text, cw / 2, ch / 2 + s / 2 + ch * 0.06, ch * 0.07, cw * 0.85);
    } else drawText(x, state.text, cw / 2, ch / 2, ch * 0.14, cw * 0.85);
  } else if (art === 'front') {
    if (state.logo) drawLogo(x, cw / 2, ch / 2, cw * 0.4, ch * 0.55 * (state.logoScale / 0.55));
    else drawText(x, state.text, cw / 2, ch / 2, ch * 0.3, cw * 0.85);
  }
  return c;
}

/* ---------- Panels and hinges ---------- */
function panel(w, d, art) {
  const geo = new THREE.BoxGeometry(w, T, d);
  const outTex = new THREE.CanvasTexture(panelCanvas(w, d, art));
  outTex.colorSpace = THREE.SRGBColorSpace; outTex.anisotropy = maxAniso;
  const inTex = new THREE.CanvasTexture(kraftCanvas);
  inTex.wrapS = inTex.wrapT = THREE.RepeatWrapping; inTex.repeat.set(w / 9, d / 9); inTex.colorSpace = THREE.SRGBColorSpace;
  const outside = new THREE.MeshStandardMaterial({ map: outTex, roughness: state.material === 'white' ? 0.55 : 0.92, envMapIntensity: 0.7 });
  const inside = new THREE.MeshStandardMaterial({ map: inTex, roughness: 0.95, envMapIntensity: 0.6 });
  disposables.push(geo, outTex, inTex, outside, inside);
  // Face order +x, -x, +y (inside), -y (printed outside), +z, -z
  const m = new THREE.Mesh(geo, [edgeMat, edgeMat, inside, outside, edgeMat, edgeMat]);
  m.castShadow = m.receiveShadow = true;
  return m;
}
function hinge(parent, x, y, z) { const g = new THREE.Group(); g.position.set(x, y, z); parent.add(g); return g; }

function computeDims() {
  const { L, W, H } = state, lidD = W - 0.4;
  dims = { L, W, H, lidD, tuckH: H * 0.8, earW: H * 0.85, earD: lidD * 0.8, earIn: T * 1.2 };
}

function buildBox() {
  computeDims();
  if (!renderer) return;
  if (box) { scene.remove(box); disposables.splice(0).forEach(o => o.dispose()); }
  const { L, W, H, lidD, tuckH, earW, earD, earIn } = dims;
  box = new THREE.Group();
  box.add(panel(L, W));
  piv.front = hinge(box, 0, 0, W / 2);  const f = panel(L, H, 'front'); f.position.z = H / 2;  piv.front.add(f);
  piv.back  = hinge(box, 0, 0, -W / 2); const b = panel(L, H);          b.position.z = -H / 2; piv.back.add(b);
  piv.left  = hinge(box, -L / 2, 0, 0); const l = panel(H, W);          l.position.x = -H / 2; piv.left.add(l);
  piv.right = hinge(box, L / 2, 0, 0);  const r = panel(H, W);          r.position.x = H / 2;  piv.right.add(r);
  piv.lid  = hinge(piv.back, 0, 0, -H);   const lid = panel(L, lidD, 'lid'); lid.position.z = -lidD / 2; piv.lid.add(lid);
  piv.tuck = hinge(piv.lid, 0, 0, -lidD); const tk = panel(L - 0.8, tuckH);  tk.position.z = -tuckH / 2; piv.tuck.add(tk);
  piv.earL = hinge(piv.lid, -(L / 2 - earIn), 0, -lidD / 2); const el = panel(earW, earD); el.position.set(-earW / 2, T, 0); piv.earL.add(el);
  piv.earR = hinge(piv.lid,  (L / 2 - earIn), 0, -lidD / 2); const er = panel(earW, earD); er.position.set( earW / 2, T, 0); piv.earR.add(er);
  scene.add(box);
  applyFold(state.fold);
}

/* ---------- Fold sequence: 1 walls up, 2 flaps in, 3 lid closes ---------- */
const clamp01 = v => Math.min(1, Math.max(0, v));
const seg = (p, a, b) => clamp01((p - a) / (b - a));
const ease = t => (t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2);
const LID_LEAN = -0.45;
function applyFold(p) {
  if (!box) return;
  const walls = ease(seg(p, 0, 40)) * Math.PI / 2, flaps = ease(seg(p, 40, 60)) * Math.PI / 2, close = ease(seg(p, 60, 100));
  piv.front.rotation.x = -walls; piv.back.rotation.x = walls;
  piv.left.rotation.z = -walls;  piv.right.rotation.z = walls;
  piv.tuck.rotation.x = flaps;   piv.earL.rotation.z = -flaps; piv.earR.rotation.z = flaps;
  piv.lid.rotation.x = p < 60 ? ease(seg(p, 40, 60)) * LID_LEAN : LID_LEAN + close * (Math.PI / 2 - LID_LEAN);
}

function frame() {
  if (!renderer) return;
  const m = Math.max(state.L, state.W, state.H * 2.2);
  camera.position.set(m * 1.9, m * 1.7, m * 2.5);
  controls.target.set(0, state.H * 0.35, 0);
  controls.minDistance = m * 0.8; controls.maxDistance = m * 5;
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
  const from = state.fold, start = performance.now();
  const dur = matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : Math.abs(to - from) * 18 + 200;
  anim = now => {
    const t = dur ? clamp01((now - start) / dur) : 1;
    state.fold = from + (to - from) * t; $('fold').value = state.fold; applyFold(state.fold);
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

/* ---------- Flat dieline drawn from the same numbers ---------- */
function drawDieline() {
  const { L, W, H, lidD, tuckH, earW, earD, earIn } = dims;
  const lidTop = -W / 2 - H - lidD, earZ = -W / 2 - H - lidD / 2 - earD / 2;
  const rects = [
    ['Base', -L / 2, -W / 2, L, W], ['Front', -L / 2, W / 2, L, H], ['Back', -L / 2, -W / 2 - H, L, H],
    ['Left', -L / 2 - H, -W / 2, H, W], ['Right', L / 2, -W / 2, H, W], ['Lid', -L / 2, lidTop, L, lidD],
    ['Tuck', -(L - 0.8) / 2, lidTop - tuckH, L - 0.8, tuckH],
    ['Ear', -(L / 2 - earIn) - earW, earZ, earW, earD], ['Ear', L / 2 - earIn, earZ, earW, earD],
  ];
  const folds = [
    [-L / 2, W / 2, L / 2, W / 2], [-L / 2, -W / 2, L / 2, -W / 2], [-L / 2, -W / 2, -L / 2, W / 2], [L / 2, -W / 2, L / 2, W / 2],
    [-L / 2, -W / 2 - H, L / 2, -W / 2 - H], [-(L - 0.8) / 2, lidTop, (L - 0.8) / 2, lidTop],
    [-(L / 2 - earIn), earZ, -(L / 2 - earIn), earZ + earD], [L / 2 - earIn, earZ, L / 2 - earIn, earZ + earD],
  ];
  const minX = -L / 2 - Math.max(H, earW + earIn) - 2, maxX = L / 2 + Math.max(H, earW) + 2;
  const minZ = lidTop - tuckH - 2, maxZ = W / 2 + H + 2;
  const fill = state.ink || (state.material === 'kraft' ? '#d6b48a' : '#f4f2ee');
  const label = state.ink && lum(state.ink) < 0.45 ? '#fbf8f2' : '#1c1a18';
  let s = `<svg id="dieSvg" class="flex-1 w-full min-h-0" role="img" aria-label="Flat dieline of the box" xmlns="http://www.w3.org/2000/svg" viewBox="${minX} ${minZ} ${maxX - minX} ${maxZ - minZ}" preserveAspectRatio="xMidYMid meet">`;
  for (const [n, x, z, w, d] of rects) {
    s += `<rect x="${x}" y="${z}" width="${w}" height="${d}" fill="${fill}" stroke="#1c1a18" stroke-width="0.18"/>`;
    s += `<text x="${x + w / 2}" y="${z + d / 2}" font-size="${Math.min(2.2, d * 0.25, w * 0.18)}" text-anchor="middle" dominant-baseline="middle" fill="${label}" font-family="DM Sans, Arial, sans-serif" opacity="0.75">${n}</text>`;
  }
  for (const [x1, z1, x2, z2] of folds) s += `<line x1="${x1}" y1="${z1}" x2="${x2}" y2="${z2}" stroke="#c2410c" stroke-width="0.22" stroke-dasharray="0.8 0.6"/>`;
  $('dieSvg').outerHTML = s + '</svg>';
  $('dieDims').textContent = `Flat sheet about ${(maxX - minX - 4).toFixed(1)} × ${(maxZ - minZ - 4).toFixed(1)} cm`;
}

/* ---------- Wiring ---------- */
let queued = false, lastSize = '';
function refresh() {
  if (queued) return; queued = true;
  requestAnimationFrame(() => {
    queued = false;
    readForm(); buildBox(); drawDieline(); renderPrice();
    const size = `${state.L}x${state.W}x${state.H}`;
    if (size !== lastSize) { lastSize = size; frame(); }
  });
}
form.addEventListener('input', e => { if (e.target.id !== 'customInk' && e.target.id !== 'logo') refresh(); });
form.addEventListener('change', e => { if (e.target.name === 'material' || e.target.name === 'quantity') refresh(); });

$('presets').addEventListener('click', e => {
  const b = e.target.closest('[data-size]'); if (!b) return;
  const [l, w, h] = b.dataset.size.split(',');
  $('length').value = l; $('width').value = w; $('height').value = h; refresh();
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

$('fold').addEventListener('input', e => { anim = null; state.fold = +e.target.value; applyFold(state.fold); });
document.querySelectorAll('[data-fold]').forEach(b => b.addEventListener('click', () => animateFold(+b.dataset.fold)));

function setTab(is3d) {
  $('tab3d').setAttribute('aria-pressed', String(is3d)); $('tab2d').setAttribute('aria-pressed', String(!is3d));
  $('dieline').hidden = is3d; $('foldbar').hidden = !is3d;
}
$('tab3d').addEventListener('click', () => setTab(true));
$('tab2d').addEventListener('click', () => setTab(false));
if (!renderer) setTab(false);

/* ---------- Start: fold up from the flat sheet ---------- */
readForm(); resize(); buildBox(); drawDieline(); renderPrice(); frame();
lastSize = `${state.L}x${state.W}x${state.H}`;
document.fonts?.ready.then(refresh);
setTimeout(() => animateFold(60), 500);
