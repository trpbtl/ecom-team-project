// Home page hero: a showcase that cycles through Packe's packaging types (mailer box, takeaway food box,
// pouch, shipping carton, catalogue, sticker). Each one grows in, opens and closes if it folds, then makes way
// for the next. The models come from js/packaging3d.js, the same builders the 3D designer uses.
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { createKit, ease01 } from 'packe3d';

const canvas = document.getElementById('hero-gl');
const lookLabel = document.getElementById('hero-look');
const typeLabel = document.getElementById('hero-type');
const priceLabel = document.getElementById('hero-price');
const PRICES = JSON.parse(canvas.dataset.showcase || '{}');   // { type: { label, from } } from views/home.php
const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;

// What the showcase shows: type, size, look, and how far it opens (fold value; 100 = closed)
const SHOW = [
  { type: 'mailer',  L: 30, W: 22, H: 10,  open: 60, fontFamily: 'Archivo', fontWeight: 800, material: 'kraft', ink: '#d14615', text: 'YOUR BRAND', sub: 'THANK YOU FOR ORDERING', name: 'Kraft · Packe orange' },
  { type: 'foodbox', L: 17, W: 12, H: 6.5, open: 62, zoom: 1.15, fontFamily: 'Pacifico', fontWeight: 400, material: 'kraft', ink: '#1c1a18', text: "Mama Ama's", name: 'Kraft · Black sticker' },
  { type: 'pouch',   L: 16, W: 24, H: 8, fontFamily: 'Playfair Display', fontWeight: 700,             material: 'kraft', ink: '#2f6b3a', text: 'KOFI BREW', sub: 'GHANA ROAST', name: 'Kraft paper · Green' },
  { type: 'carton',  L: 40, W: 30, H: 25, open: 72, zoom: 0.85, fontFamily: 'Bebas Neue', fontWeight: 400, material: 'kraft', ink: '',        text: 'ACCRA STREET', name: 'Brown corrugated' },
  { type: 'catalog', L: 21, W: 29.7, H: 24, open: 55, zoom: 1.2, fontFamily: 'Montserrat', fontWeight: 700, material: 'white', ink: '#1f4e79', text: 'AMA COSMETICS', sub: 'NATURAL SKINCARE', name: 'White gloss · Navy' },
  { type: 'sticker', L: 10, W: 10, H: 0, fontFamily: 'Caveat', fontWeight: 700,             material: 'white', ink: '#e8b4b8', text: 'Green Leaf', sub: 'FRESH FROM THE FARM', name: 'White vinyl · Blush pink' },
].filter(s => !Object.keys(PRICES).length || PRICES[s.type]);

let renderer;
try {
  renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
} catch (e) {
  canvas.replaceWith(Object.assign(document.createElement('img'), {
    src: canvas.dataset.fallback, alt: 'A custom printed mailer box', className: canvas.className,
  }));
}

if (renderer && SHOW.length) {
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
  renderer.outputColorSpace = THREE.SRGBColorSpace;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.shadowMap.enabled = true;
  renderer.shadowMap.type = THREE.PCFSoftShadowMap;

  const scene = new THREE.Scene();
  scene.environment = new THREE.PMREMGenerator(renderer).fromScene(new RoomEnvironment(renderer), 0.04).texture;
  const camera = new THREE.PerspectiveCamera(30, 1, 0.1, 1000);
  camera.position.set(62, 54, 80);
  const controls = new OrbitControls(camera, canvas);
  controls.enableZoom = false;
  controls.enablePan = false;
  controls.enableDamping = true;
  controls.maxPolarAngle = Math.PI * 0.47;
  controls.autoRotate = !reduceMotion;
  controls.autoRotateSpeed = 1.2;

  const sun = new THREE.DirectionalLight(0xffffff, 1.5);
  sun.position.set(30, 60, 40);
  sun.castShadow = true;
  sun.shadow.mapSize.set(1024, 1024);
  Object.assign(sun.shadow.camera, { left: -50, right: 50, top: 50, bottom: -50 });
  sun.shadow.radius = 6;
  scene.add(sun, new THREE.HemisphereLight(0xffffff, 0xe8d6c0, 0.4));
  const ground = new THREE.Mesh(new THREE.PlaneGeometry(400, 400), new THREE.ShadowMaterial({ opacity: 0.14 }));
  ground.rotation.x = -Math.PI / 2; ground.receiveShadow = true;
  scene.add(ground);

  const kit = createKit(renderer);
  const holder = new THREE.Group();   // scaled in and out between items
  holder.rotation.y = -0.5;
  scene.add(holder);

  // The camera stays put; each model is scaled so it fills the window like the 30 cm mailer box does
  const REF = 32;
  let index = -1, current = null, item = null, fit = 1;
  function show(i) {
    if (current) { holder.remove(current.group); current.dispose(); }
    index = i; item = SHOW[i];
    current = kit.build(item.type, { ...item, logo: null, logoScale: 0.55 });
    holder.add(current.group);
    current.fold?.(100);
    fit = REF / current.view.size * (current.view.dir ? 1.15 : 1) * (item.zoom || 1);
    ground.position.y = (current.ground ?? -0.02) * fit;
    const info = PRICES[item.type];
    if (typeLabel && info) typeLabel.textContent = info.label;
    if (priceLabel && info) priceLabel.textContent = 'from ' + info.from;
    if (lookLabel) {
      lookLabel.querySelector('[data-swatch]').style.background = item.ink || (item.material === 'kraft' ? '#c79c6b' : '#f4f2ee');
      lookLabel.querySelector('[data-name]').textContent = item.name;
    }
    canvas.setAttribute('aria-label', `${info ? info.label : item.type} turning in 3D`);
  }
  show(0);
  // Canvas text needs the web fonts downloaded first: once they are, redraw the item on screen
  if (document.fonts) {
    Promise.all(SHOW.map(s => document.fonts.load(`${s.fontWeight} 48px "${s.fontFamily}"`).catch(() => null)))
      .then(() => show(index));
  }
  controls.target.set(0, 6, 0);

  // Timeline per item (seconds): grow in, hold closed, open, hold, close, hold, shrink out
  const CYCLE = 6.8;
  function foldAt(t) {
    const open = item.open ?? 100;
    if (t < 2.4) return 100;
    if (t < 3.5) return 100 + (open - 100) * ease01((t - 2.4) / 1.1);
    if (t < 4.5) return open;
    if (t < 5.6) return open + (100 - open) * ease01((t - 4.5) / 1.1);
    return 100;
  }
  function scaleAt(t) {
    if (t < 0.45) return ease01(t / 0.45);
    if (t > CYCLE - 0.4) return ease01((CYCLE - t) / 0.4);
    return 1;
  }

  function resize() {
    const r = canvas.getBoundingClientRect();
    if (!r.width) return;
    renderer.setSize(r.width, r.height, false);
    camera.aspect = r.width / r.height; camera.updateProjectionMatrix();
  }
  new ResizeObserver(resize).observe(canvas);
  resize();

  let start = performance.now();
  renderer.setAnimationLoop(now => {
    if (!reduceMotion) {
      let t = (now - start) / 1000;
      if (t >= CYCLE) { start = now; t = 0; show((index + 1) % SHOW.length); }
      holder.scale.setScalar(Math.max(0.001, scaleAt(t) * fit));
      current.fold?.(foldAt(t));
    } else holder.scale.setScalar(fit);
    controls.update();
    renderer.render(scene, camera);
  });
}
