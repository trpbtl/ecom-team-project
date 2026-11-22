// Checkout delivery map (views/checkout.php). All free:
//   map + pins ........ Leaflet with OpenStreetMap map pictures
//   "Use my location" . the browser's Geolocation
//   address search .... Nominatim (OpenStreetMap's search), limited to Ghana
//   distance + prices . Packe's server (actions/delivery_quote.php → DeliveryPricing), which routes with
//                       OpenRouteService or OSRM. The server works the price out again at checkout.
(function () {
  const el = document.getElementById('delivery-map');
  if (!el) return;
  const $ = id => document.getElementById(id);
  const form = $('checkout-form');
  const origin = [parseFloat(el.dataset.originLat), parseFloat(el.dataset.originLng)];
  const money = v => 'GH₵' + v.toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const subtotal = parseFloat($('subtotal').dataset.value);
  const discount = parseFloat($('pro-discount')?.dataset.value || '0');   // Packe Pro members: 10% off
  let quote = null;   // the last good answer from the server

  /* ---------- Order summary and price cards ---------- */
  function chosenOption() { return form.querySelector('input[name=delivery_option]:checked')?.value || 'standard'; }
  const feeText = p => (p === 0 && quote && quote.pro) ? 'Free with Pro' : money(p);
  function paintPrices() {
    form.querySelectorAll('[data-price-for]').forEach(span => {
      const p = quote ? quote[span.dataset.priceFor] : null;
      span.textContent = p != null ? feeText(p) : 'Pick a spot';
      span.classList.toggle('is-empty', p == null);
    });
    const fee = quote ? quote[chosenOption()] : null;
    $('delivery-fee').textContent = fee != null ? feeText(fee) : 'Choose a spot';
    $('delivery-fee').classList.toggle('text-muted', fee == null);
    $('delivery-km').textContent = quote ? `· ${quote.km.toFixed(1)} km` : '';
    $('grand-total').textContent = money(subtotal - discount + (fee || 0));
  }
  form.querySelectorAll('input[name=delivery_option]').forEach(r => r.addEventListener('change', paintPrices));

  function pill(text, state) {
    const p = $('distance-pill');
    p.classList.toggle('is-loading', state === 'loading');
    p.classList.toggle('is-error', state === 'error');
    $('distance-text').textContent = text;
  }

  /* ---------- Map, Packe's pin, the customer's pin and the route ---------- */
  if (typeof L === 'undefined') {   // map library didn't load: search and "Use my location" still work
    el.innerHTML = '<p class="p-6 text-sm text-muted">The map could not load. Search for your area or use your location above.</p>';
  }
  const map = typeof L !== 'undefined' ? L.map(el, { scrollWheelZoom: false }).setView([5.66, -0.2], 11) : null;
  let pin = null, routeCasing = null, routeLine = null;
  if (map) {
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);
    const logo = '<svg width="18" height="18" viewBox="0 0 64 64" aria-hidden="true"><polygon points="32,7 55,19.5 32,32 9,19.5" fill="#e4572e"/><polygon points="9,19.5 32,32 32,57 9,44.5" fill="#c2410c"/><polygon points="55,19.5 32,32 32,57 55,44.5" fill="#fbf9f4"/></svg>';
    L.marker(origin, { icon: L.divIcon({ className: '', html: `<div class="pin-origin">${logo}</div>`, iconSize: [34, 34], iconAnchor: [17, 17] }), keyboard: false })
      .addTo(map).bindTooltip('Packe · Ashesi', { permanent: true, direction: 'right', offset: [16, 0], className: 'pin-label' });
    map.on('click', e => setSpot(e.latlng));
    el.addEventListener('focus', () => map.scrollWheelZoom.enable());   // zoom with the wheel only once the map is in use
    el.addEventListener('mouseleave', () => map.scrollWheelZoom.disable());
    el.addEventListener('click', () => map.scrollWheelZoom.enable());
  }

  function drawRoute(line) {
    if (!map) return;
    if (routeCasing) { routeCasing.remove(); routeLine.remove(); routeCasing = routeLine = null; }
    if (line && line.length > 1) {
      routeCasing = L.polyline(line, { color: '#ffffff', weight: 9, opacity: .9 }).addTo(map);
      routeLine = L.polyline(line, { color: '#c2410c', weight: 5, opacity: .95 }).addTo(map);
      map.fitBounds(routeLine.getBounds(), { padding: [40, 40] });
    } else if (pin) {
      map.fitBounds(L.latLngBounds([origin, pin.getLatLng()]), { padding: [60, 60] });
    }
  }

  /* ---------- Setting the delivery spot ---------- */
  let quoteTimer = null, quoteSeq = 0;
  function setSpot(latlng, opts = {}) {
    const ll = map ? L.latLng(latlng) : { lat: latlng[0] ?? latlng.lat, lng: latlng[1] ?? latlng.lng };
    $('delivery_lat').value = ll.lat.toFixed(6);
    $('delivery_lng').value = ll.lng.toFixed(6);
    const slot = form.querySelector('[data-error-for="delivery_location"]');
    if (slot) slot.textContent = '';
    if (map) {
      if (!pin) {
        pin = L.marker(ll, {
          draggable: true, autoPan: true, title: 'Your delivery spot (drag to adjust)',
          icon: L.divIcon({ className: '', html: '<div class="pin-drop"></div>', iconSize: [30, 30], iconAnchor: [15, 34] }),
        }).addTo(map);
        pin.on('dragend', () => setSpot(pin.getLatLng(), { fillAddress: true }));
      } else pin.setLatLng(ll);
      if (opts.zoom) map.setView(ll, 15);
    }
    clearTimeout(quoteTimer);
    quoteTimer = setTimeout(() => fetchQuote(ll), 250);
    if (opts.fillAddress !== false) fillAddressFrom(ll, opts.place);
  }

  async function fetchQuote(ll) {
    const seq = ++quoteSeq;
    quote = null; paintPrices();
    pill('Measuring the road distance from Ashesi…', 'loading');
    try {
      const res = await fetch(`${el.dataset.quoteUrl}?lat=${ll.lat.toFixed(6)}&lng=${ll.lng.toFixed(6)}`,
                              { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
      const data = await res.json();
      if (seq !== quoteSeq) return;   // the customer moved the pin again meanwhile
      if (!data.success) {
        pill(data.error || 'We could not price that spot.', 'error');
        drawRoute(null);
        return;
      }
      quote = data;
      const how = data.method === 'road' ? 'by road' : 'estimated';
      pill(`${data.km.toFixed(1)} km from Ashesi ${how}` + (data.minutes ? ` · about ${data.minutes} min` : ''));
      drawRoute(data.line);
      paintPrices();
    } catch (e) {
      if (seq === quoteSeq) pill('We could not reach the server. Please try again.', 'error');
    }
  }

  /* ---------- Fill in the address and city from the map, if the customer hasn't typed them ---------- */
  async function fillAddressFrom(ll, place) {
    const address = $('address'), city = $('city');
    if (address.value.trim() && city.value.trim()) return;
    let a = place?.address;
    if (!a) {
      try {
        const r = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&zoom=17&lat=${ll.lat}&lon=${ll.lng}`,
                              { headers: { 'Accept-Language': 'en' } }).then(r => r.json());
        a = r.address;
      } catch (e) { return; }
    }
    if (!a) return;
    const town = a.city || a.town || a.village || a.municipality || a.county || '';
    const street = [a.house_number, a.road].filter(Boolean).join(' ');
    const area = a.suburb || a.neighbourhood || a.quarter || '';
    if (!city.value.trim() && town) city.value = town;
    if (!address.value.trim() && (street || area)) address.value = [street, area].filter(Boolean).join(', ');
  }

  /* ---------- "Use my location" (browser Geolocation) ---------- */
  $('locate-me').addEventListener('click', () => {
    const btn = $('locate-me');
    if (!navigator.geolocation) { pill('This browser cannot share its location. Search instead.', 'error'); return; }
    btn.classList.add('is-loading');
    pill('Asking your browser for your location…', 'loading');
    navigator.geolocation.getCurrentPosition(p => {
      btn.classList.remove('is-loading');
      setSpot([p.coords.latitude, p.coords.longitude], { zoom: true });
    }, err => {
      btn.classList.remove('is-loading');
      pill(err.code === 1 ? 'Location not shared. Search for your area or tap the map.' : 'Could not find your location. Search instead.', 'error');
    }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 });
  });

  /* ---------- Address search (Nominatim, Ghana only) ---------- */
  const results = $('place-results');
  function closeResults() { results.hidden = true; results.innerHTML = ''; }
  async function search() {
    const q = $('place-q').value.trim();
    if (q.length < 2) return;
    results.hidden = false;
    results.innerHTML = '<li class="place-empty">Searching…</li>';
    try {
      const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&countrycodes=gh&limit=6&q=${encodeURIComponent(q)}`;
      const list = await fetch(url, { headers: { 'Accept-Language': 'en' } }).then(r => r.json());
      results.innerHTML = '';
      if (!list.length) { results.innerHTML = '<li class="place-empty">No places found in Ghana. Try a nearby landmark, area or town.</li>'; return; }
      for (const place of list) {
        const [main, ...rest] = place.display_name.split(', ');
        const li = document.createElement('li');
        li.innerHTML = '<button type="button" role="option"><svg class="w-4 h-4 mt-0.5 shrink-0 text-packe-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg><span><span class="place-main"></span><span class="place-sub"></span></span></button>';
        li.querySelector('.place-main').textContent = main;
        li.querySelector('.place-sub').textContent = rest.filter(p => p !== 'Ghana').slice(0, 3).join(', ');
        li.querySelector('button').addEventListener('click', () => {
          closeResults();
          $('place-q').value = main;
          setSpot([+place.lat, +place.lon], { zoom: true, place });
        });
        results.appendChild(li);
      }
      results.querySelector('button')?.focus();
    } catch (e) {
      results.innerHTML = '<li class="place-empty">Search is unavailable right now. Tap the map instead.</li>';
    }
  }
  $('place-go').addEventListener('click', search);
  $('place-q').addEventListener('keydown', e => {   // Enter searches; it must not submit the checkout form
    if (e.key === 'Enter') { e.preventDefault(); search(); }
    if (e.key === 'Escape') closeResults();
  });
  document.addEventListener('click', e => { if (!$('place-search').contains(e.target)) closeResults(); });

  /* ---------- Start: show Packe and the customer's earlier pin, if any ---------- */
  paintPrices();
  const lat = parseFloat($('delivery_lat').value), lng = parseFloat($('delivery_lng').value);
  if (!isNaN(lat) && !isNaN(lng)) setSpot([lat, lng], { fillAddress: false });
  else if (map) map.fitBounds(L.latLngBounds([origin, [5.56, -0.19]]), { padding: [30, 30] });   // Ashesi to central Accra
})();
