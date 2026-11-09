// Packe packaging models: one 3D builder per packaging type, shared by the designer (js/designer.js)
// and the home page hero (js/hero3d.js). Each builder makes the model from sizes and a "look"
// (material, print colour, logo, text), knows how to fold it, and draws its flat print layout.
// Artwork is drawn on canvases and wrapped on as textures, so the 3D view and the flat layout always match.
import * as THREE from 'three';

const T = 0.3;            // board thickness for boxes, cm
let renderer = null;      // null when WebGL is unavailable: builders then only draw flat layouts
let state = null;         // the look being built: { L, W, H, material, ink, logo, logoScale, text, sub }
let disposables = [];     // GPU resources of the model being built
let edgeMat = null, maxAniso = 1;

function grainCanvas(base, dark, light, n) {
  const c = document.createElement('canvas'); c.width = c.height = 256;
  const x = c.getContext('2d');
  x.fillStyle = base; x.fillRect(0, 0, 256, 256);
  for (let i = 0; i < n; i++) {
    const a = Math.random() * Math.PI, len = 2 + Math.random() * 8, px = Math.random() * 256, py = Math.random() * 256;
    x.strokeStyle = Math.random() < 0.5 ? `rgba(${dark},${0.05 + Math.random() * 0.12})` : `rgba(${light},${0.05 + Math.random() * 0.12})`;
    x.lineWidth = 0.5 + Math.random() * 0.9;
    x.beginPath(); x.moveTo(px, py); x.lineTo(px + Math.cos(a) * len, py + Math.sin(a) * len); x.stroke();
  }
  return c;
}
const kraftCanvas = grainCanvas('#c79c6b', '95,62,30', '245,220,185', 2600);

/* ---------- Artwork ---------- */
const lum = hex => { const n = parseInt(hex.slice(1), 16); return (0.2126 * (n >> 16) + 0.7152 * ((n >> 8) & 255) + 0.0722 * (n & 255)) / 255; };
const textColour = () => state.ink ? (lum(state.ink) < 0.45 ? '#fbf8f2' : '#1c1a18') : (state.material === 'kraft' ? '#2b1d12' : '#1c1a18');
const paperColour = () => state.material === 'kraft' ? '#d6b48a' : '#f4f2ee';

function drawText(x, txt, cx, cy, size, maxW, colour) {
  if (!txt) return;
  // The customer's text colour wins; otherwise dark or light, whichever stands out on the board
  x.fillStyle = state.textColour || colour || textColour(); x.textAlign = 'center'; x.textBaseline = 'middle';
  let s = size * (state.textScale || 1);   // text size slider (0.6–1.6); still shrinks to fit the panel
  // The customer's chosen font (look.fontFamily / fontWeight), with Archivo as the fallback
  const family = state.fontFamily ? `"${state.fontFamily}", Archivo, Arial, sans-serif` : 'Archivo, Arial, sans-serif';
  const style = state.italic ? 'italic ' : '';
  do { x.font = `${style}${state.fontWeight || 700} ${s}px ${family}`; s *= 0.92; } while (x.measureText(txt).width > maxW && s > 8);
  x.fillText(txt, cx, cy);
}
function drawLogo(x, cx, cy, maxW, maxH) {
  const img = state.logo; if (!img) return;
  const r = Math.min(maxW / img.width, maxH / img.height);
  x.drawImage(img, cx - img.width * r / 2, cy - img.height * r / 2, img.width * r, img.height * r);
}
/** Logo with the text under it (or the text alone), centred in a box. An optional small second line (sub). */
function drawBrand(x, cx, cy, bw, bh) {
  const s = Math.min(bw, bh) * state.logoScale;
  if (state.logo) {
    drawLogo(x, cx, cy - (state.text ? bh * 0.08 : 0), Math.min(bw * 0.9, s * 1.4), s);
    drawText(x, state.text, cx, cy + s / 2 + bh * 0.05, Math.min(bh * 0.08, bw * 0.1), bw * 0.88);
  } else if (state.sub) {
    drawText(x, state.text, cx, cy - bh * 0.05, Math.min(bh * 0.15, bw * 0.15), bw * 0.88);
    drawText(x, state.sub.split('').join(String.fromCharCode(8202)), cx, cy + bh * 0.09, Math.min(bh * 0.05, bw * 0.05), bw * 0.8);
  } else drawText(x, state.text, cx, cy, Math.min(bh * 0.16, bw * 0.16), bw * 0.88);
}

/**
 * A printed surface w × h cm. art: 'brand' (logo + text), 'logo' (logo, or text if no logo), 'text', 'blank', 'inside' (page content).
 * opts.rot180 turns the artwork round (for panels that are flipped over when folded).
 */
function artCanvas(w, h, art, opts = {}) {
  const px = Math.min(128, Math.max(28, 1024 / Math.max(w, h)));
  const c = document.createElement('canvas');
  c.width = Math.max(32, Math.min(2048, Math.round(w * px)));
  c.height = Math.max(32, Math.min(2048, Math.round(h * px)));
  const x = c.getContext('2d'), cw = c.width, ch = c.height;
  x.fillStyle = state.material === 'kraft' ? x.createPattern(kraftCanvas, 'repeat') : '#f4f2ee';
  x.fillRect(0, 0, cw, ch);
  if (art === 'inside') { drawInside(x, cw, ch); return c; }
  if (state.ink && !opts.noInk) {
    x.save();
    if (state.material === 'kraft') { x.globalCompositeOperation = 'multiply'; x.globalAlpha = 0.93; }   // ink soaks into brown board
    x.fillStyle = state.ink; x.fillRect(0, 0, cw, ch); x.restore();
  }
  if (opts.rot180) { x.translate(cw, ch); x.rotate(Math.PI); }
  if (opts.extra) opts.extra(x, cw, ch);
  const [bx, by, bw, bh] = opts.box || [0, 0, cw, ch];
  if (art === 'brand') drawBrand(x, bx + bw / 2, by + bh / 2, bw, bh);
  else if (art === 'logo') {
    if (state.logo) drawLogo(x, bx + bw / 2, by + bh / 2, bw * 0.45, bh * 0.6 * (state.logoScale / 0.55));
    else drawText(x, state.text, bx + bw / 2, by + bh / 2, bh * 0.3, bw * 0.85);
  } else if (art === 'text') drawText(x, state.text, bx + bw / 2, by + bh / 2, bh * 0.22, bw * 0.8);
  else if (art === 'sticker') {   // round sticker: logo filling the middle, or the text as large as fits the circle
    if (state.logo) drawLogo(x, cw / 2, ch / 2 - (state.text ? ch * 0.08 : 0), cw * 0.62, ch * (state.text ? 0.42 : 0.6));
    drawText(x, state.text, cw / 2, state.logo ? ch * 0.74 : ch / 2, state.logo ? ch * 0.09 : ch * 0.2, cw * (state.logo ? 0.6 : 0.74));
  }
  return c;
}
/** Inside pages: a heading, a picture block and lines of text, in grey. */
function drawInside(x, cw, ch) {
  const m = cw * 0.1;
  x.fillStyle = state.material === 'kraft' ? '#e2c9a6' : '#fbfaf7'; x.fillRect(0, 0, cw, ch);
  x.fillStyle = state.ink || '#c2410c'; x.globalAlpha = 0.85; x.fillRect(m, ch * 0.08, cw * 0.5, ch * 0.05);
  x.globalAlpha = 0.18; x.fillStyle = '#1c1a18'; x.fillRect(m, ch * 0.18, cw - 2 * m, ch * 0.3);
  x.globalAlpha = 0.25;
  for (let y = ch * 0.54; y < ch * 0.92; y += ch * 0.045) x.fillRect(m, y, (cw - 2 * m) * (0.7 + 0.3 * Math.sin(y)), ch * 0.016);
  x.globalAlpha = 1;
}

function tex(c, flip = true) {
  const t = new THREE.CanvasTexture(c);
  t.colorSpace = THREE.SRGBColorSpace; t.anisotropy = maxAniso; t.flipY = flip;
  disposables.push(t);
  return t;
}
function mat(map, opts = {}) {
  const m = new THREE.MeshStandardMaterial({ map, roughness: state.material === 'white' ? 0.55 : 0.9, envMapIntensity: 0.7, ...opts });
  disposables.push(m);
  return m;
}
function boardTex(w, d) {
  const t = new THREE.CanvasTexture(kraftCanvas);
  t.wrapS = t.wrapT = THREE.RepeatWrapping; t.repeat.set(w / 9, d / 9); t.colorSpace = THREE.SRGBColorSpace;
  disposables.push(t);
  return t;
}
const plainEdge = () => mat(null, { color: state.material === 'kraft' ? 0xb88e5e : 0xeeeae3, roughness: 0.9 });

/** A flat panel w × d lying in the x–z plane. top/bottom are materials; faces: +x, -x, +y, -y, +z, -z. */
function slab(w, d, thick, top, bottom, edge = edgeMat) {
  const geo = new THREE.BoxGeometry(w, thick, d); disposables.push(geo);
  const m = new THREE.Mesh(geo, [edge, edge, top, bottom, edge, edge]);
  m.castShadow = m.receiveShadow = true;
  return m;
}
/** Board panel for boxes: plain board inside (+y), printed outside (-y). */
function boardPanel(w, d, art) {
  return slab(w, d, T, mat(boardTex(w, d), { roughness: 0.95, envMapIntensity: 0.6 }), mat(tex(artCanvas(w, d, art || 'blank'))));
}
function hinge(parent, x, y, z) { const g = new THREE.Group(); g.position.set(x, y, z); parent.add(g); return g; }

/** A thin flat piece (label, sticker, card) cut from a 2D shape, lying flat, printed on top. */
function flatPiece(shape, w, h, depth, art, opts) {
  const geo = new THREE.ExtrudeGeometry(shape, { depth, bevelEnabled: false, curveSegments: 48 });
  disposables.push(geo);
  const t = tex(artCanvas(w, h, art, opts));
  t.repeat.set(1 / w, 1 / h); t.offset.set(0.5, 0.5);   // the shape is centred on 0, its UVs are in cm
  const m = new THREE.Mesh(geo, [mat(t, opts?.gloss ? { roughness: 0.25 } : {}), plainEdge()]);
  m.rotation.x = -Math.PI / 2;   // lie flat, printed face up
  m.castShadow = m.receiveShadow = true;
  return m;
}
function roundRect(w, h, r) {
  const s = new THREE.Shape(), x = -w / 2, y = -h / 2;
  r = Math.min(r, w / 2, h / 2);
  s.moveTo(x + r, y); s.lineTo(x + w - r, y); s.quadraticCurveTo(x + w, y, x + w, y + r);
  s.lineTo(x + w, y + h - r); s.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
  s.lineTo(x + r, y + h); s.quadraticCurveTo(x, y + h, x, y + h - r);
  s.lineTo(x, y + r); s.quadraticCurveTo(x, y, x + r, y);
  return s;
}
/** The glossy backing sheet labels and stickers come on. */
function liner(w, h) {
  const m = slab(w, h, 0.03, mat(null, { color: 0xfaf8f4, roughness: 0.35 }), mat(null, { color: 0xeeeae3 }), mat(null, { color: 0xeeeae3 }));
  m.position.y = 0.015;
  return m;
}

/* ---------- Small maths for the fold animations ---------- */
const clamp01 = v => Math.min(1, Math.max(0, v));
const seg = (p, a, b) => clamp01((p - a) / (b - a));
const ease = t => (t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2);
const smooth = (a, b, v) => { const t = clamp01((v - a) / (b - a)); return t * t * (3 - 2 * t); };

/* ---------- SVG helpers for the flat layout ---------- */
const svgRect = (n, x, y, w, h, fill) =>
  `<rect x="${x}" y="${y}" width="${w}" height="${h}" fill="${fill || paperColour()}" stroke="#1c1a18" stroke-width="0.18"/>` +
  (n ? `<text x="${x + w / 2}" y="${y + h / 2}" font-size="${Math.min(2.2, h * 0.25, w * 0.18)}" text-anchor="middle" dominant-baseline="middle" fill="#1c1a18" font-family="DM Sans, Arial, sans-serif" opacity="0.7">${n}</text>` : '');
const svgFold = (x1, y1, x2, y2) => `<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="#c2410c" stroke-width="0.22" stroke-dasharray="0.8 0.6"/>`;
const svgArt = (x, y, w, h, c) => `<image href="${c.toDataURL('image/jpeg', 0.85)}" x="${x}" y="${y}" width="${w}" height="${h}" preserveAspectRatio="none"/>`;
const n1 = v => (+v.toFixed(1)).toString();
const svgPoly = (n, pts, fill) => {
  const cx = pts.reduce((a, p) => a + p[0], 0) / pts.length, cy = pts.reduce((a, p) => a + p[1], 0) / pts.length;
  return `<polygon points="${pts.map(p => p.join(',')).join(' ')}" fill="${fill}" stroke="#1c1a18" stroke-width="0.18"/>` +
    (n ? `<text x="${cx}" y="${cy}" font-size="1.6" text-anchor="middle" dominant-baseline="middle" fill="#1c1a18" font-family="DM Sans, Arial, sans-serif" opacity="0.7">${n}</text>` : '');
};

/* =====================================================================
   Builders: one per packaging type. Each returns
   { group, fold(p) (optional), view: {size, y, dir}, ground, dieline() → {vb, body, note}, start }
   ===================================================================== */
const BUILDERS = {
  /* ----- Mailer box: base, four walls, lid with a tuck flap and two dust ears ----- */
  mailer() {
    const { L, W, H } = state, lidD = W - 0.4, tuckH = H * 0.8, earW = H * 0.85, earD = lidD * 0.8, earIn = T * 1.2;
    const g = new THREE.Group(), piv = {};
    if (renderer) {
      g.add(boardPanel(L, W));
      piv.front = hinge(g, 0, 0, W / 2);  const f = boardPanel(L, H, 'logo'); f.position.z = H / 2;  piv.front.add(f);
      piv.back  = hinge(g, 0, 0, -W / 2); const b = boardPanel(L, H);         b.position.z = -H / 2; piv.back.add(b);
      piv.left  = hinge(g, -L / 2, 0, 0); const l = boardPanel(H, W);         l.position.x = -H / 2; piv.left.add(l);
      piv.right = hinge(g, L / 2, 0, 0);  const r = boardPanel(H, W);         r.position.x = H / 2;  piv.right.add(r);
      piv.lid  = hinge(piv.back, 0, 0, -H);   const lid = boardPanel(L, lidD, 'brand'); lid.position.z = -lidD / 2; piv.lid.add(lid);
      piv.tuck = hinge(piv.lid, 0, 0, -lidD); const tk = boardPanel(L - 0.8, tuckH);  tk.position.z = -tuckH / 2; piv.tuck.add(tk);
      piv.earL = hinge(piv.lid, -(L / 2 - earIn), 0, -lidD / 2); const el = boardPanel(earW, earD); el.position.set(-earW / 2, T, 0); piv.earL.add(el);
      piv.earR = hinge(piv.lid,  (L / 2 - earIn), 0, -lidD / 2); const er = boardPanel(earW, earD); er.position.set( earW / 2, T, 0); piv.earR.add(er);
    }
    const LEAN = -0.45;
    return {
      group: g, start: 60, ground: -T / 2 - 0.02,
      view: { size: Math.max(L, W, H * 2.2), y: H * 0.35 },
      fold(p) {   // 1 walls up, 2 flaps in, 3 lid closes
        const walls = ease(seg(p, 0, 40)) * Math.PI / 2, flaps = ease(seg(p, 40, 60)) * Math.PI / 2, close = ease(seg(p, 60, 100));
        piv.front.rotation.x = -walls; piv.back.rotation.x = walls;
        piv.left.rotation.z = -walls;  piv.right.rotation.z = walls;
        piv.tuck.rotation.x = flaps;   piv.earL.rotation.z = -flaps; piv.earR.rotation.z = flaps;
        piv.lid.rotation.x = p < 60 ? ease(seg(p, 40, 60)) * LEAN : LEAN + close * (Math.PI / 2 - LEAN);
      },
      dieline() {
        const lidTop = -W / 2 - H - lidD, earZ = -W / 2 - H - lidD / 2 - earD / 2;
        let body = '';
        for (const [n, x, z, w, d] of [
          ['Base', -L / 2, -W / 2, L, W], ['Front', -L / 2, W / 2, L, H], ['Back', -L / 2, -W / 2 - H, L, H],
          ['Left', -L / 2 - H, -W / 2, H, W], ['Right', L / 2, -W / 2, H, W], ['Lid', -L / 2, lidTop, L, lidD],
          ['Tuck', -(L - 0.8) / 2, lidTop - tuckH, L - 0.8, tuckH],
          ['Ear', -(L / 2 - earIn) - earW, earZ, earW, earD], ['Ear', L / 2 - earIn, earZ, earW, earD],
        ]) body += svgRect(n, x, z, w, d, state.ink || paperColour());
        for (const f of [
          [-L / 2, W / 2, L / 2, W / 2], [-L / 2, -W / 2, L / 2, -W / 2], [-L / 2, -W / 2, -L / 2, W / 2], [L / 2, -W / 2, L / 2, W / 2],
          [-L / 2, -W / 2 - H, L / 2, -W / 2 - H], [-(L - 0.8) / 2, lidTop, (L - 0.8) / 2, lidTop],
          [-(L / 2 - earIn), earZ, -(L / 2 - earIn), earZ + earD], [L / 2 - earIn, earZ, L / 2 - earIn, earZ + earD],
        ]) body += svgFold(...f);
        const minX = -L / 2 - Math.max(H, earW + earIn), maxX = L / 2 + Math.max(H, earW), minZ = lidTop - tuckH, maxZ = W / 2 + H;
        return { vb: [minX, minZ, maxX - minX, maxZ - minZ], body, note: `Flat sheet about ${n1(maxX - minX)} × ${n1(maxZ - minZ)} cm` };
      },
    };
  },

  /* ----- Shipping carton (regular slotted carton): four walls, flaps on top that meet in the middle ----- */
  carton() {
    const { L, W, H } = state, fl = W / 2;
    const g = new THREE.Group(), piv = {};
    if (renderer) {
      g.add(boardPanel(L, W));
      piv.front = hinge(g, 0, 0, W / 2);  const f = boardPanel(L, H, 'logo'); f.position.z = H / 2;  piv.front.add(f);
      piv.back  = hinge(g, 0, 0, -W / 2); const b = boardPanel(L, H, 'logo'); b.position.z = -H / 2; piv.back.add(b);
      piv.left  = hinge(g, -L / 2, 0, 0); const l = boardPanel(H, W, 'text'); l.position.x = -H / 2; piv.left.add(l);
      piv.right = hinge(g, L / 2, 0, 0);  const r = boardPanel(H, W, 'text'); r.position.x = H / 2;  piv.right.add(r);
      // Long flaps on the front and back walls; short flaps on the side walls sit just under them
      piv.fF = hinge(piv.front, 0, 0, H);  const ff = boardPanel(L, fl); ff.position.set(0, -T, fl / 2);  piv.fF.add(ff);
      piv.fB = hinge(piv.back, 0, 0, -H);  const fb = boardPanel(L, fl); fb.position.set(0, -T, -fl / 2); piv.fB.add(fb);
      piv.fL = hinge(piv.left, -H, 0, 0);  const fll = boardPanel(fl, W - 0.4); fll.position.set(-fl / 2, T, 0); piv.fL.add(fll);
      piv.fR = hinge(piv.right, H, 0, 0);  const frr = boardPanel(fl, W - 0.4); frr.position.set(fl / 2, T, 0); piv.fR.add(frr);
    }
    return {
      group: g, start: 70, ground: -T / 2 - 0.02,
      view: { size: Math.max(L, W, H * 1.6), y: H * 0.45 },
      fold(p) {   // 1 walls up, 2 short flaps in, 3 long flaps close
        const walls = ease(seg(p, 0, 45)) * Math.PI / 2, minor = ease(seg(p, 45, 72)) * Math.PI / 2, major = ease(seg(p, 72, 100)) * Math.PI / 2;
        piv.front.rotation.x = -walls; piv.back.rotation.x = walls;
        piv.left.rotation.z = -walls;  piv.right.rotation.z = walls;
        piv.fL.rotation.z = -minor; piv.fR.rotation.z = minor;
        piv.fF.rotation.x = -major; piv.fB.rotation.x = major;
      },
      dieline() {   // the real carton sheet: four walls in a strip with a glue tab, flaps above and below
        const glue = 3, panels = [['Side', W], ['Front', L], ['Side', W], ['Back', L]];
        let body = '', x = glue;
        body += `<path d="M0 ${fl + 0.6} L${glue} ${fl} L${glue} ${fl + H} L0 ${fl + H - 0.6} Z" fill="${paperColour()}" stroke="#1c1a18" stroke-width="0.18"/>`;
        for (const [n, w] of panels) {
          body += svgRect('Flap', x, 0, w, fl, state.ink || paperColour()) + svgRect(n, x, fl, w, H, state.ink || paperColour()) + svgRect('Flap', x, fl + H, w, fl, state.ink || paperColour());
          body += svgFold(x, fl, x + w, fl) + svgFold(x, fl + H, x + w, fl + H) + svgFold(x, fl, x, fl + H);
          x += w;
        }
        return { vb: [0, 0, x, H + 2 * fl], body, note: `Flat sheet about ${n1(x)} × ${n1(H + 2 * fl)} cm` };
      },
    };
  },

  /* ----- Stand-up pouch: curved front and back that meet at the side seals, a gusset base, zip and window ----- */
  pouch() {
    const Wp = state.L, Hp = state.W, G = state.H;
    const g = new THREE.Group();
    // Front artwork: zip band at the top, clear window low down, logo and text above it
    const front = () => artCanvas(Wp, Hp, 'blank', {
      extra(x, cw, ch) {
        const win = [cw * 0.2, ch * 0.55, cw * 0.6, ch * 0.32];
        x.save();   // window: product showing through clear film
        x.beginPath(); x.roundRect(...win, cw * 0.05); x.clip();
        x.fillStyle = '#efe4cf'; x.fillRect(...win);
        for (let i = 0; i < 260; i++) {
          x.fillStyle = ['#7a4a26', '#9b6236', '#5e371b', '#b9844f'][i % 4];
          x.beginPath(); x.ellipse(win[0] + Math.random() * win[2], win[1] + win[3] * (0.25 + Math.random() * 0.8), cw * 0.022, cw * 0.015, Math.random() * 3, 0, 7); x.fill();
        }
        const gl = x.createLinearGradient(win[0], win[1], win[0] + win[2], win[1] + win[3]);
        gl.addColorStop(0, 'rgba(255,255,255,0.55)'); gl.addColorStop(0.35, 'rgba(255,255,255,0.08)'); gl.addColorStop(0.6, 'rgba(255,255,255,0.25)'); gl.addColorStop(1, 'rgba(255,255,255,0.05)');
        x.fillStyle = gl; x.fillRect(...win); x.restore();
        x.strokeStyle = 'rgba(0,0,0,0.25)'; x.lineWidth = cw * 0.004; x.beginPath(); x.roundRect(...win, cw * 0.05); x.stroke();
        x.fillStyle = 'rgba(0,0,0,0.12)'; x.fillRect(0, 0, cw, ch * 0.06);                      // heat seal at the top
        x.fillStyle = 'rgba(0,0,0,0.22)'; x.fillRect(0, ch * 0.105, cw, ch * 0.008);           // zip
        x.fillStyle = 'rgba(0,0,0,0.08)'; x.fillRect(0, 0, cw * 0.035, ch); x.fillRect(cw * 0.965, 0, cw * 0.035, ch);   // side seals
        // Logo and text sit between the zip and the window
        const top = ch * 0.14, bottom = win[1] - ch * 0.03;
        drawBrand(x, cw / 2, (top + bottom) / 2, cw * 0.8, bottom - top);
      },
    });
    if (renderer) {
      const frontCanvas = front();
      const backCanvas = artCanvas(Wp, Hp, 'blank', { extra(x, cw, ch) { x.fillStyle = 'rgba(0,0,0,0.12)'; x.fillRect(0, 0, cw, ch * 0.06); } });
      const surface = sign => {
        const nu = 40, nv = 50, pos = [], uv = [], idx = [];
        for (let j = 0; j <= nv; j++) {
          const v = j / nv, d = (G / 2) * (0.62 + 0.38 * (1 - v)) * (1 - smooth(0.78, 0.96, v));
          for (let i = 0; i <= nu; i++) {
            const u = i / nu, s = Math.pow(Math.sin(Math.PI * u), 0.35);
            pos.push((u - 0.5) * Wp, v * Hp, sign * d * s);
            uv.push(sign > 0 ? u : 1 - u, v);
          }
        }
        for (let j = 0; j < nv; j++) for (let i = 0; i < nu; i++) {
          const a = j * (nu + 1) + i, b = a + 1, c = a + nu + 1, d = c + 1;
          if (sign > 0) idx.push(a, b, c, b, d, c); else idx.push(a, c, b, b, c, d);
        }
        const geo = new THREE.BufferGeometry();
        geo.setAttribute('position', new THREE.Float32BufferAttribute(pos, 3));
        geo.setAttribute('uv', new THREE.Float32BufferAttribute(uv, 2));
        geo.setIndex(idx); geo.computeVertexNormals(); disposables.push(geo);
        return geo;
      };
      const opts = { side: THREE.DoubleSide, roughness: state.material === 'white' ? 0.6 : 0.85 };
      const fm = new THREE.Mesh(surface(1), mat(tex(frontCanvas), opts));
      const bm = new THREE.Mesh(surface(-1), mat(tex(backCanvas), opts));
      fm.castShadow = bm.castShadow = true;
      // Flat gusset base joining the bottom edges of the front and back
      const nu = 40, pos = [], idx = [];
      for (let i = 0; i <= nu; i++) {
        const u = i / nu, s = Math.pow(Math.sin(Math.PI * u), 0.35), d = (G / 2) * s;
        pos.push((u - 0.5) * Wp, 0, d, (u - 0.5) * Wp, 0, -d);
      }
      for (let i = 0; i < nu; i++) { const a = i * 2; idx.push(a, a + 1, a + 2, a + 1, a + 3, a + 2); }
      const bg = new THREE.BufferGeometry();
      bg.setAttribute('position', new THREE.Float32BufferAttribute(pos, 3)); bg.setIndex(idx); bg.computeVertexNormals(); disposables.push(bg);
      g.add(fm, bm, new THREE.Mesh(bg, mat(null, { color: state.ink || (state.material === 'kraft' ? '#c79c6b' : '#f4f2ee'), side: THREE.DoubleSide })));
    }
    return {
      group: g, ground: -0.01,
      view: { size: Math.max(Wp * 1.1, Hp), y: Hp * 0.45, dir: [0.45, 0.35, 1] },
      dieline() {
        const c = front();
        const body = svgArt(0, 0, Wp, Hp, c) + svgRect('', 0, 0, Wp, Hp, 'none') +
          svgRect('Gusset (base)', 0, Hp, Wp, G, state.ink || paperColour()) + svgFold(0, Hp + G / 2, Wp, Hp + G / 2) +
          svgRect('Back', 0, Hp + G, Wp, Hp, state.ink || paperColour()) + svgFold(0, Hp, Wp, Hp) + svgFold(0, Hp + G, Wp, Hp + G);
        return { vb: [0, 0, Wp, 2 * Hp + G], body, note: `Front ${n1(Wp)} × ${n1(Hp)} cm, ${n1(G)} cm gusset` };
      },
    };
  },

  /* ----- Rectangular label on a peel-off liner ----- */
  label() { return flatBuilder(roundRect(state.L, state.W, Math.min(state.L, state.W) * 0.08), state.L, state.W, 'brand', 'rect'); },

  /* ----- Round sticker on a square liner ----- */
  sticker() {
    const s = new THREE.Shape(); s.absarc(0, 0, state.L / 2, 0, Math.PI * 2, false);
    return flatBuilder(s, state.L, state.L, 'brand', 'circle', { gloss: state.material === 'white' });
  },

  /* ----- Tamper seal strip with tear slits ----- */
  seal() {
    const extra = (x, cw, ch) => {   // tear slits along both edges
      x.strokeStyle = 'rgba(0,0,0,0.35)'; x.lineWidth = Math.max(1, ch * 0.02);
      const step = ch * 0.5;
      for (let px = step / 2; px < cw; px += step) {
        x.beginPath(); x.moveTo(px - ch * 0.08, 0); x.lineTo(px, ch * 0.14); x.lineTo(px + ch * 0.08, 0); x.stroke();
        x.beginPath(); x.moveTo(px - ch * 0.08, ch); x.lineTo(px, ch * 0.86); x.lineTo(px + ch * 0.08, ch); x.stroke();
      }
    };
    return flatBuilder(roundRect(state.L, state.W, state.W * 0.12), state.L, state.W, state.logo ? 'brand' : 'text', 'rect',
                       { extra, box: null, fallbackText: 'SEALED FOR YOU' });
  },

  /* ----- Card (thank-you or insert card), shown leaning on a second card ----- */
  card() { return flatBuilder(roundRect(state.L, state.W, 0.3), state.L, state.W, 'brand', 'rect', { noLiner: true, depth: 0.06 }); },

  /* ----- Tri-fold brochure: three panels; the inside flap folds in, then the cover folds over it ----- */
  brochure() {
    const pw = state.L / 3, h = state.W, th = 0.05;
    const g = new THREE.Group(), piv = {};
    if (renderer) {
      const inside = mat(tex(artCanvas(pw, h, 'inside')));
      const plain = mat(tex(artCanvas(pw, h, 'text')));
      const cover = mat(tex(artCanvas(pw, h, 'brand', { rot180: true })));
      const edge = plainEdge();
      g.add(slab(pw, h, th, inside, plain, edge));                                  // back panel (middle)
      piv.r = hinge(g, pw / 2, 0, 0);  const r = slab(pw, h, th, mat(tex(artCanvas(pw, h, 'inside'))), mat(tex(artCanvas(pw, h, 'blank'))), edge);
      r.position.x = pw / 2; piv.r.add(r);                                          // inside flap
      piv.l = hinge(g, -pw / 2, 0, 0); const l = slab(pw, h, th, mat(tex(artCanvas(pw, h, 'inside'))), cover, edge);
      l.position.set(-pw / 2, -2.2 * th, 0); piv.l.add(l);                         // cover: ends up on top
    }
    return {
      group: g, start: 100, ground: -0.05,
      view: { size: Math.max(state.L * 0.85, h * 1.1), y: 0, dir: [0.15, 1.1, 0.9] },
      fold(p) {
        piv.r.rotation.z = ease(seg(p, 0, 50)) * Math.PI;
        piv.l.rotation.z = -ease(seg(p, 50, 100)) * Math.PI;
      },
      dieline() {
        const cv = artCanvas(pw, h, 'brand');
        const body = svgRect('Inside flap (back of sheet)', 0, 0, pw, h, state.ink || paperColour()) +
          svgRect('Back', pw, 0, pw, h, state.ink || paperColour()) + svgArt(2 * pw, 0, pw, h, cv) + svgRect('', 2 * pw, 0, pw, h, 'none') +
          svgFold(pw, 0, pw, h) + svgFold(2 * pw, 0, 2 * pw, h);
        return { vb: [0, 0, 3 * pw, h], body, note: `Outside of the sheet: ${n1(state.L)} × ${n1(h)} cm, folds into ${n1(pw)} cm panels` };
      },
    };
  },

  /* ----- Takeaway food box: walls that lean out, end flaps, two long lid flaps that lock with a tongue,
           and a round logo sticker sealing the lid. The board stays plain; the print colour is the sticker's. ----- */
  foodbox() {
    const { L, W, H } = state, lean = 0.18;
    const grow = H * Math.sin(lean), Lt = L + 2 * grow, Wt = W + 2 * grow;   // the top opening is wider than the base
    const endD = Lt * 0.26, frontD = Wt * 0.5, backD = Wt * 0.56, tongue = Math.min(3, Wt * 0.18);
    const stickerD = Math.max(3.5, Math.min(Lt, Wt) * 0.4);
    const g = new THREE.Group(), piv = {};
    if (renderer) {
      const it = new THREE.CanvasTexture(kraftCanvas);
      it.wrapS = it.wrapT = THREE.RepeatWrapping; it.repeat.set(1 / 9, 1 / 9); it.colorSpace = THREE.SRGBColorSpace; disposables.push(it);
      const insideMat = mat(state.material === 'kraft' ? it : null, { side: THREE.BackSide, roughness: 0.95, color: state.material === 'kraft' ? 0xffffff : 0xf4f2ee });
      // A trapezoid of thin board, hinged along its bottom edge, lying flat with the printed side down
      // Lid flaps: diagonal creases from the corners and a shaded tip, like a real fold-top box
      const creases = (x, cw, ch) => {
        x.strokeStyle = 'rgba(70,45,20,0.22)'; x.lineWidth = Math.max(1.5, cw * 0.004);
        x.beginPath(); x.moveTo(0, ch); x.lineTo(cw * 0.3, 0); x.moveTo(cw, ch); x.lineTo(cw * 0.7, 0); x.stroke();
        const gr = x.createLinearGradient(0, 0, 0, ch * 0.12);
        gr.addColorStop(0, 'rgba(60,35,15,0.28)'); gr.addColorStop(1, 'rgba(60,35,15,0)');
        x.fillStyle = gr; x.fillRect(0, 0, cw, ch * 0.12);
      };
      const sheet = (bottom, top, depth, withTongue = false, lid = false) => {
        const s = new THREE.Shape();
        s.moveTo(-bottom / 2, 0); s.lineTo(bottom / 2, 0); s.lineTo(top / 2, depth);
        if (withTongue) {
          s.lineTo(tongue * 0.9, depth); s.quadraticCurveTo(tongue * 0.6, depth + tongue, 0, depth + tongue);
          s.quadraticCurveTo(-tongue * 0.6, depth + tongue, -tongue * 0.9, depth);
        }
        s.lineTo(-top / 2, depth); s.closePath();
        const geo = new THREE.ShapeGeometry(s, 16); disposables.push(geo);
        const w = Math.max(bottom, top), d = depth + (withTongue ? tongue : 0);
        const t = tex(artCanvas(w, d, 'blank', { noInk: true, extra: lid ? creases : undefined })); t.repeat.set(1 / w, 1 / d); t.offset.set(0.5, 0);
        const out = new THREE.Mesh(geo, mat(t)), ins = new THREE.Mesh(geo, insideMat);
        out.castShadow = ins.castShadow = out.receiveShadow = ins.receiveShadow = true;
        const grp = new THREE.Group(); grp.add(out, ins); grp.rotation.x = Math.PI / 2;   // shape y → outward, printed face down
        return grp;
      };
      const wall = (rotY, off) => { const u = new THREE.Group(); u.rotation.y = rotY; g.add(u); return hinge(u, 0, 0, off); };
      const base = sheet(L, L, W); base.position.z = -W / 2; g.add(base);
      piv.front = wall(0, W / 2);           piv.front.add(sheet(L, Lt, H));
      piv.back  = wall(Math.PI, W / 2);     piv.back.add(sheet(L, Lt, H));
      piv.left  = wall(-Math.PI / 2, L / 2); piv.left.add(sheet(W, Wt, H));
      piv.right = wall(Math.PI / 2, L / 2);  piv.right.add(sheet(W, Wt, H));
      const flap = (parent, bottom, top, depth, y, withTongue, lid) => {
        const h = hinge(parent, 0, 0, H), s = sheet(bottom, top, depth, withTongue, lid); s.position.y = y; h.add(s); return h;
      };
      piv.endL = flap(piv.left, Wt, Wt * 0.45, endD, 0.06);
      piv.endR = flap(piv.right, Wt, Wt * 0.45, endD, 0.06);
      piv.lidF = flap(piv.front, Lt, Lt * 0.94, frontD, -0.05, false, true);
      piv.lidB = flap(piv.back, Lt, Lt * 0.94, backD, -0.12, true, true);
      // Round logo sticker in the middle of the closed lid (on the back flap's printed side)
      const saved = state;
      state = { ...saved, material: 'white', ink: saved.ink || '#1c1a18' };
      const sc = artCanvas(stickerD, stickerD, 'sticker', {
        extra(x, cw, ch) { x.strokeStyle = 'rgba(255,255,255,0.7)'; x.lineWidth = cw * 0.015; x.beginPath(); x.arc(cw / 2, ch / 2, cw * 0.44, 0, 7); x.stroke(); },
      });
      state = saved;
      const sg = new THREE.CircleGeometry(stickerD / 2, 64); disposables.push(sg);
      const sticker = new THREE.Mesh(sg, mat(tex(sc), { roughness: 0.3 }));
      sticker.rotation.set(Math.PI / 2, 0, Math.PI);   // printed side faces the same way as the flap's outside
      sticker.position.set(0, -0.15, Wt / 2);
      sticker.castShadow = true;
      piv.lidB.add(sticker);
    }
    return {
      group: g, start: 100, ground: -0.03,
      view: { size: Math.max(Lt, Wt, H * 2.4) * 1.2, y: H * 0.4 },
      fold(p) {   // 1 walls up, 2 end flaps in, 3 front flap, 4 back flap locks over it
        const walls = ease(seg(p, 0, 40)) * (Math.PI / 2 - lean), shut = Math.PI / 2 + lean;
        for (const k of ['front', 'back', 'left', 'right']) piv[k].rotation.x = -walls;
        piv.endL.rotation.x = piv.endR.rotation.x = -ease(seg(p, 40, 60)) * shut;
        piv.lidF.rotation.x = -ease(seg(p, 60, 80)) * shut;
        piv.lidB.rotation.x = -ease(seg(p, 80, 100)) * shut;
      },
      dieline() {
        const f = state.material === 'kraft' ? paperColour() : '#f4f2ee', yF = W / 2 + H, xR = L / 2 + H;
        let body = svgPoly('Base', [[-L / 2, -W / 2], [L / 2, -W / 2], [L / 2, W / 2], [-L / 2, W / 2]], f);
        for (const s of [1, -1]) {
          body += svgPoly(s > 0 ? 'Front' : 'Back', [[-L / 2, s * W / 2], [L / 2, s * W / 2], [Lt / 2, s * yF], [-Lt / 2, s * yF]], f);
          body += svgPoly(s > 0 ? 'End' : 'End', [[s * L / 2, -W / 2], [s * L / 2, W / 2], [s * xR, Wt / 2], [s * xR, -Wt / 2]], f);
          body += svgPoly('', [[s * xR, -Wt / 2], [s * xR, Wt / 2], [s * (xR + endD), Wt * 0.225], [s * (xR + endD), -Wt * 0.225]], f);
          const d = s > 0 ? frontD : backD;
          body += svgPoly(s > 0 ? 'Lid' : 'Lid (tongue)', [[-Lt / 2, s * yF], [Lt / 2, s * yF], [Lt * 0.47, s * (yF + d)], [-Lt * 0.47, s * (yF + d)]], f);
          body += svgFold(-L / 2, s * W / 2, L / 2, s * W / 2) + svgFold(s * L / 2, -W / 2, s * L / 2, W / 2);
          body += svgFold(-Lt / 2, s * yF, Lt / 2, s * yF) + svgFold(s * xR, -Wt / 2, s * xR, Wt / 2);
        }
        body += `<path d="M${-tongue * 0.9} ${-(yF + backD)} Q${-tongue * 0.6} ${-(yF + backD + tongue)} 0 ${-(yF + backD + tongue)} Q${tongue * 0.6} ${-(yF + backD + tongue)} ${tongue * 0.9} ${-(yF + backD)}" fill="${f}" stroke="#1c1a18" stroke-width="0.18"/>`;
        // The sticker is printed separately: shown beside the sheet
        const sx = xR + endD + stickerD * 0.9, saved = state;
        state = { ...saved, material: 'white', ink: saved.ink || '#1c1a18' };
        const sc = artCanvas(stickerD, stickerD, 'sticker');
        state = saved;
        body += `<defs><clipPath id="stk"><circle cx="${sx}" cy="0" r="${stickerD / 2}"/></clipPath></defs><g clip-path="url(#stk)">${svgArt(sx - stickerD / 2, -stickerD / 2, stickerD, stickerD, sc)}</g>` +
                `<circle cx="${sx}" cy="0" r="${stickerD / 2}" fill="none" stroke="#1c1a18" stroke-width="0.18"/>` +
                `<text x="${sx}" y="${stickerD / 2 + 1.5}" font-size="1.4" text-anchor="middle" fill="#1c1a18" opacity="0.7" font-family="DM Sans, Arial, sans-serif">Logo sticker</text>`;
        const minX = -(xR + endD), maxX = sx + stickerD / 2, minY = -(yF + backD + tongue), maxY = yF + frontD;
        return { vb: [minX, minY, maxX - minX, maxY - minY], body,
                 note: `Flat sheet about ${n1(2 * (xR + endD))} × ${n1(maxY - minY)} cm · sticker Ø ${n1(stickerD)} cm` };
      },
    };
  },

  /* ----- Catalogue: page block, back cover, and a front cover that opens on the spine ----- */
  catalog() {
    const L = state.L, W = state.W, pages = state.H, tb = Math.max(0.15, pages * 0.006), ct = 0.04;
    const g = new THREE.Group(), piv = {};
    if (renderer) {
      const edgeC = document.createElement('canvas'); edgeC.width = 8; edgeC.height = 64;
      const ex = edgeC.getContext('2d'); ex.fillStyle = '#f7f5f0'; ex.fillRect(0, 0, 8, 64);
      ex.fillStyle = '#d9d4ca'; for (let y = 0; y < 64; y += 3) ex.fillRect(0, y, 8, 1);
      const et = tex(edgeC); et.wrapS = et.wrapT = THREE.RepeatWrapping;
      const pageEdge = mat(et, { roughness: 0.9 }), coverEdge = plainEdge();
      const coverInside = mat(null, { color: 0xf4f2ee });
      const back = slab(L, W, ct, coverInside, mat(tex(artCanvas(L, W, 'blank'))), coverEdge);
      back.position.y = ct / 2;
      const block = slab(L - 0.3, W - 0.3, tb, mat(tex(artCanvas(L, W, 'inside'))), coverInside, pageEdge);
      block.position.y = ct + tb / 2;
      piv.c = hinge(g, -L / 2, ct + tb / 2, 0);
      const cover = slab(L, W, ct, mat(tex(artCanvas(L, W, 'brand'), true)), mat(tex(artCanvas(L, W, 'inside'))), coverEdge);
      cover.position.set(L / 2, tb / 2 + ct / 2, 0); piv.c.add(cover);
      g.add(back, block);
    }
    return {
      group: g, start: 100, ground: -0.01,
      view: { size: Math.max(L * 1.5, W * 1.1), y: 0, dir: [0.1, 1.1, 0.9] },
      fold(p) { piv.c.rotation.z = (1 - ease(seg(p, 0, 100))) * Math.PI; },
      dieline() {
        const sp = Math.max(0.3, tb);
        const body = svgRect('Back cover', 0, 0, L, W, state.ink || paperColour()) + svgRect('', L, 0, sp, W, state.ink || paperColour()) +
          svgArt(L + sp, 0, L, W, artCanvas(L, W, 'brand')) + svgRect('', L + sp, 0, L, W, 'none') + svgFold(L, 0, L, W) + svgFold(L + sp, 0, L + sp, W);
        return { vb: [0, 0, 2 * L + sp, W], body, note: `Cover spread ${n1(2 * L + sp)} × ${n1(W)} cm · ${pages} pages inside` };
      },
    };
  },
};

/** Shared builder for flat printed pieces (labels, stickers, seals, cards). */
function flatBuilder(shape, w, h, art, outline, opts = {}) {
  const g = new THREE.Group(), depth = opts.depth || 0.03;
  const artOpts = { ...opts, box: undefined };
  if (opts.extra) artOpts.extra = opts.extra;
  if (opts.fallbackText && !state.text && !state.logo) {   // a seal with no branding still shows a message
    artOpts.extra = (x, cw, ch) => { opts.extra?.(x, cw, ch); drawText(x, opts.fallbackText, cw / 2, ch / 2, ch * 0.3, cw * 0.8); };
  }
  if (renderer) {
    const piece = flatPiece(shape, w, h, depth, art, artOpts);
    if (opts.noLiner) {
      // Two cards: one lying flat (the back), one leaning so you see the front
      const backCard = flatPiece(shape, w, h, depth, 'blank', {});
      backCard.position.set(w * 0.08, 0, h * 0.12);
      piece.rotation.x = -Math.PI / 2 + 0.38;
      piece.position.set(-w * 0.04, Math.sin(0.38) * h / 2 + 0.05, -h * 0.05);
      g.add(backCard, piece);
    } else {
      const m = Math.max(0.8, Math.min(w, h) * 0.12);
      g.add(liner(w + 2 * m, h + 2 * m));
      piece.position.y = 0.03;
      g.add(piece);
    }
  }
  return {
    group: g, ground: -0.01,
    view: { size: Math.max(w, h) * 1.35, y: opts.noLiner ? h * 0.15 : 0, dir: [0.1, 1.15, 0.9] },
    dieline() {
      const c = artCanvas(w, h, art, artOpts), b = 0.3;   // 3 mm bleed
      const clip = outline === 'circle'
        ? `<clipPath id="cut"><circle cx="${w / 2}" cy="${h / 2}" r="${w / 2}"/></clipPath>`
        : `<clipPath id="cut"><rect width="${w}" height="${h}" rx="${outline === 'rect' ? Math.min(w, h) * 0.06 : 0}"/></clipPath>`;
      const cut = outline === 'circle'
        ? `<circle cx="${w / 2}" cy="${h / 2}" r="${w / 2}" fill="none" stroke="#1c1a18" stroke-width="${w * 0.006}"/>`
        : `<rect width="${w}" height="${h}" rx="${Math.min(w, h) * 0.06}" fill="none" stroke="#1c1a18" stroke-width="${Math.max(w, h) * 0.004}"/>`;
      const body = `<defs>${clip}</defs><g clip-path="url(#cut)">${svgArt(0, 0, w, h, c)}</g>${cut}`;
      return { vb: [-b, -b, w + 2 * b, h + 2 * b], body, note: outline === 'circle' ? `Ø ${n1(w)} cm` : `${n1(w)} × ${n1(h)} cm` };
    },
  };
}

/* ---------- Public API ---------- */
export const ease01 = ease;

/**
 * Set up the kit for a renderer (or null when WebGL failed: only flat layouts then).
 * kit.build(type, look) returns { group, fold(p)?, start?, view, ground, dieline(), dispose() }.
 * look = { L, W, H, material: 'kraft'|'white', ink: '#rrggbb'|'', logo: Image|null, logoScale, text, sub?, fontFamily?, fontWeight?, textScale?, textColour?, italic? }
 */
export function createKit(r) {
  renderer = r || null;
  if (renderer) {
    maxAniso = renderer.capabilities.getMaxAnisotropy();
    const ec = document.createElement('canvas'); ec.width = 64; ec.height = 8;
    const ex = ec.getContext('2d');
    ex.fillStyle = '#b48857'; ex.fillRect(0, 0, 64, 8); ex.fillStyle = '#8f6a40';
    for (let i = 0; i < 64; i += 4) ex.fillRect(i, 2, 2, 4);
    const et = new THREE.CanvasTexture(ec); et.wrapS = et.wrapT = THREE.RepeatWrapping; et.repeat.set(8, 1); et.colorSpace = THREE.SRGBColorSpace;
    edgeMat = new THREE.MeshStandardMaterial({ map: et, roughness: 1 });
  }
  return {
    types: Object.keys(BUILDERS),
    build(type, look) {
      state = look;
      const list = [];
      disposables = list;
      const b = BUILDERS[type]();
      const drawFlat = b.dieline;
      b.dieline = () => { state = look; return drawFlat(); };   // flat layouts are drawn later, from this look
      b.dispose = () => list.splice(0).forEach(o => o.dispose());
      return b;
    },
  };
}
