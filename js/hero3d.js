// Home page hero: a mailer box that slowly turns, opens, changes its design and closes again,
// to show in a few seconds what the 3D designer can do. Same fold approach as js/designer.js.
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';

const canvas = document.getElementById('hero-gl');
const label = document.getElementById('hero-look');
const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;

// The designs the box cycles through: board, print colour, name on the lid
const LOOKS = [
  { mat: 'kraft', ink: '#d14615', brand: 'YOUR BRAND', sub: 'THANK YOU FOR ORDERING', name: 'Kraft · Packe orange' },
  { mat: 'white', ink: '#1f4e79', brand: 'AMA COSMETICS', sub: 'NATURAL SKINCARE', name: 'White · Navy' },
  { mat: 'kraft', ink: '', brand: 'KOFI BREW', sub: 'GHANA ROAST', name: 'Natural kraft' },
  { mat: 'white', ink: '#1c1a18', brand: 'ACCRA STREET', sub: 'MADE IN GHANA', name: 'White · Black' },
  { mat: 'kraft', ink: '#2f6b3a', brand: 'GREEN LEAF', sub: 'FRESH FROM THE FARM', name: 'Kraft · Forest green' },
];
const L = 30, W = 22, H = 10, T = 0.3, PX = 24;

let renderer;
try {
  renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
} catch (e) {
  canvas.replaceWith(Object.assign(document.createElement('img'), {
    src: canvas.dataset.fallback, alt: 'A custom printed mailer box', className: canvas.className,
  }));
}

if (renderer) {
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
  renderer.outputColorSpace = THREE.SRGBColorSpace;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.shadowMap.enabled = true;
  renderer.shadowMap.type = THREE.PCFSoftShadowMap;

  const scene = new THREE.Scene();
  scene.environment = new THREE.PMREMGenerator(renderer).fromScene(new RoomEnvironment(renderer), 0.04).texture;
  const camera = new THREE.PerspectiveCamera(30, 1, 1, 1000);
  camera.position.set(62, 54, 80);
  const controls = new OrbitControls(camera, canvas);
  controls.target.set(0, 7, 0);
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
  ground.rotation.x = -Math.PI / 2; ground.position.y = -T / 2 - 0.02; ground.receiveShadow = true;
  scene.add(ground);

  // Board textures drawn in code
  const kraft = (() => {
    const c = document.createElement('canvas'); c.width = c.height = 256;
    const x = c.getContext('2d'); x.fillStyle = '#c79c6b'; x.fillRect(0, 0, 256, 256);
    for (let i = 0; i < 2400; i++) {
      const a = Math.random() * Math.PI, n = 2 + Math.random() * 8, px = Math.random() * 256, py = Math.random() * 256;
      x.strokeStyle = Math.random() < 0.5 ? `rgba(95,62,30,${0.05 + Math.random() * 0.12})` : `rgba(245,220,185,${0.05 + Math.random() * 0.12})`;
      x.lineWidth = 0.5 + Math.random() * 0.9; x.beginPath(); x.moveTo(px, py); x.lineTo(px + Math.cos(a) * n, py + Math.sin(a) * n); x.stroke();
    }
    return c;
  })();
  const edge = new THREE.MeshStandardMaterial({ color: 0xb48857, roughness: 1 });

  function panelTexture(w, d, look, art) {
    const c = document.createElement('canvas');
    c.width = Math.round(w * PX); c.height = Math.round(d * PX);
    const x = c.getContext('2d'), cw = c.width, ch = c.height;
    x.fillStyle = look.mat === 'kraft' ? x.createPattern(kraft, 'repeat') : '#f4f2ee';
    x.fillRect(0, 0, cw, ch);
    if (look.ink) {
      x.save();
      if (look.mat === 'kraft') { x.globalCompositeOperation = 'multiply'; x.globalAlpha = 0.93; }
      x.fillStyle = look.ink; x.fillRect(0, 0, cw, ch); x.restore();
    }
    const dark = look.ink && parseInt(look.ink.slice(1), 16) < 0x999999;
    x.fillStyle = look.ink ? (dark ? '#fbf8f2' : '#1c1a18') : '#2b1d12';
    x.textAlign = 'center'; x.textBaseline = 'middle';
    if (art === 'lid') {
      x.font = `800 ${Math.round(ch * 0.15)}px Archivo, Arial, sans-serif`;
      x.fillText(look.brand, cw / 2, ch * 0.46);
      x.font = `600 ${Math.round(ch * 0.055)}px "DM Sans", Arial, sans-serif`;
      x.fillText(look.sub.split('').join(String.fromCharCode(8202)), cw / 2, ch * 0.62);
    } else if (art === 'front') {
      x.font = `800 ${Math.round(ch * 0.28)}px Archivo, Arial, sans-serif`;
      x.fillText(look.brand, cw / 2, ch / 2);
    }
    const t = new THREE.CanvasTexture(c); t.colorSpace = THREE.SRGBColorSpace; t.anisotropy = 8;
    return t;
  }

  const insideMat = (() => {
    const t = new THREE.CanvasTexture(kraft); t.wrapS = t.wrapT = THREE.RepeatWrapping; t.repeat.set(3, 3); t.colorSpace = THREE.SRGBColorSpace;
    return new THREE.MeshStandardMaterial({ map: t, roughness: 0.95 });
  })();
  const printed = []; // [mesh, w, d, art] so a new look only swaps textures

  function panel(w, d, art) {
    const m = new THREE.Mesh(new THREE.BoxGeometry(w, T, d),
      [edge, edge, insideMat, new THREE.MeshStandardMaterial({ roughness: 0.85 }), edge, edge]);
    m.castShadow = m.receiveShadow = true;
    printed.push([m, w, d, art]);
    return m;
  }
  function applyLook(look) {
    for (const [m, w, d, art] of printed) {
      const mat = m.material[3];
      if (mat.map) mat.map.dispose();
      mat.map = panelTexture(w, d, look, art);
      mat.roughness = look.mat === 'white' ? 0.55 : 0.9;
      mat.needsUpdate = true;
    }
    if (label) {
      label.querySelector('[data-swatch]').style.background = look.ink || (look.mat === 'kraft' ? '#c79c6b' : '#f4f2ee');
      label.querySelector('[data-name]').textContent = look.name;
    }
  }
  const hinge = (p, x, y, z) => { const g = new THREE.Group(); g.position.set(x, y, z); p.add(g); return g; };

  const box = new THREE.Group();
  const lidD = W - 0.4, tuckH = H * 0.8, earW = H * 0.85, earD = lidD * 0.8, earIn = T * 1.2;
  box.add(panel(L, W));
  const piv = {};
  // Attach a panel to its hinge at an offset (Object3D.add returns the parent, so position the panel first)
  const attach = (hingeGroup, mesh, x, y, z) => { mesh.position.set(x, y, z); hingeGroup.add(mesh); };
  piv.front = hinge(box, 0, 0, W / 2);   attach(piv.front, panel(L, H, 'front'), 0, 0, H / 2);
  piv.back  = hinge(box, 0, 0, -W / 2);  attach(piv.back, panel(L, H), 0, 0, -H / 2);
  piv.left  = hinge(box, -L / 2, 0, 0);  attach(piv.left, panel(H, W), -H / 2, 0, 0);
  piv.right = hinge(box, L / 2, 0, 0);   attach(piv.right, panel(H, W), H / 2, 0, 0);
  piv.lid   = hinge(piv.back, 0, 0, -H); attach(piv.lid, panel(L, lidD, 'lid'), 0, 0, -lidD / 2);
  piv.tuck  = hinge(piv.lid, 0, 0, -lidD); attach(piv.tuck, panel(L - 0.8, tuckH), 0, 0, -tuckH / 2);
  piv.earL  = hinge(piv.lid, -(L / 2 - earIn), 0, -lidD / 2); attach(piv.earL, panel(earW, earD), -earW / 2, T, 0);
  piv.earR  = hinge(piv.lid, L / 2 - earIn, 0, -lidD / 2);    attach(piv.earR, panel(earW, earD), earW / 2, T, 0);
  // Walls and flaps stay folded; only the lid moves in the hero
  piv.front.rotation.x = -Math.PI / 2; piv.back.rotation.x = Math.PI / 2;
  piv.left.rotation.z = -Math.PI / 2;  piv.right.rotation.z = Math.PI / 2;
  piv.tuck.rotation.x = Math.PI / 2;   piv.earL.rotation.z = -Math.PI / 2; piv.earR.rotation.z = Math.PI / 2;
  box.rotation.y = -0.5;
  scene.add(box);

  const CLOSED = Math.PI, OPEN = Math.PI / 2 - 0.45;   // lid angle relative to the back wall
  const ease = t => (t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2);
  let lookIndex = 0;
  applyLook(LOOKS[0]);
  piv.lid.rotation.x = CLOSED - Math.PI / 2;

  // Timeline per design (seconds): hold closed, open, swap the design while open, close
  const CYCLE = 6.5;
  let start = performance.now(), swapped = false;
  function lidAngle(t) {
    if (t < 2.5) return CLOSED;
    if (t < 3.6) return CLOSED + (OPEN - CLOSED) * ease((t - 2.5) / 1.1);
    if (t < 4.6) return OPEN;
    if (t < 5.7) return OPEN + (CLOSED - OPEN) * ease((t - 4.6) / 1.1);
    return CLOSED;
  }

  function resize() {
    const r = canvas.getBoundingClientRect();
    if (!r.width) return;
    renderer.setSize(r.width, r.height, false);
    camera.aspect = r.width / r.height; camera.updateProjectionMatrix();
  }
  new ResizeObserver(resize).observe(canvas);
  resize();

  renderer.setAnimationLoop(now => {
    if (!reduceMotion) {
      const t = ((now - start) / 1000) % CYCLE;
      piv.lid.rotation.x = lidAngle(t) - Math.PI / 2;
      if (t >= 4.0 && t < 4.6 && !swapped) {
        lookIndex = (lookIndex + 1) % LOOKS.length;
        applyLook(LOOKS[lookIndex]);
        swapped = true;
      }
      if (t < 4.0) swapped = false;
    }
    controls.update();
    renderer.render(scene, camera);
  });
}
