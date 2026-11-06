// Add to cart without reloading the page.
// Forms with data-ajax-cart are sent in the background. For ready-made products (forms with data-price)
// the cart badge updates the moment you click; the server saves the item behind the scenes, one request
// at a time, and the badge is then corrected to the server's numbers. Nothing reloads until you open the cart.
// Without JavaScript the same forms still work as normal posts.
(function () {
  const link = document.getElementById('cart-link');
  if (!link) return;
  const countEl = document.getElementById('cart-count');
  const totalEl = document.getElementById('cart-total');
  const fmt = v => 'GH₵' + v.toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  // What the badge shows (server numbers plus anything still being saved)
  const shown = {
    count: parseInt(link.dataset.count, 10) || 0,
    total: parseFloat(link.dataset.total) || 0,
    plain: new Set(JSON.parse(link.dataset.plain || '[]')),
  };
  let pending = 0;
  let queue = Promise.resolve();

  function paint(bump) {
    countEl.textContent = shown.count;
    totalEl.textContent = fmt(shown.total);
    link.setAttribute('aria-label', `Cart: ${shown.count} items, ${fmt(shown.total)}`);
    if (bump) { link.classList.remove('cart-bump'); void link.offsetWidth; link.classList.add('cart-bump'); }
  }

  /* ---------- Small message in the corner ---------- */
  const box = document.createElement('div');
  box.className = 'toast-stack';
  box.setAttribute('role', 'status');
  box.setAttribute('aria-live', 'polite');
  document.body.appendChild(box);
  function toast(message, ok = true) {
    const t = document.createElement('div');
    t.className = 'toast ' + (ok ? 'toast-ok' : 'toast-error');
    const p = document.createElement('p');
    p.textContent = message;
    t.appendChild(p);
    if (ok) {
      const a = document.createElement('a');
      a.href = link.href; a.textContent = 'View cart'; a.className = 'toast-link';
      t.appendChild(a);
    }
    box.appendChild(t);
    while (box.children.length > 3) box.firstChild.remove();
    setTimeout(() => { t.classList.add('toast-out'); setTimeout(() => t.remove(), 300); }, ok ? 3200 : 6000);
  }

  async function send(form) {
    const res = await fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      headers: { 'X-Requested-With': 'fetch', Accept: 'application/json' },
      credentials: 'same-origin',
    });
    let data = null;
    try { data = await res.json(); } catch (e) { /* not JSON: treat as a failure below */ }
    if (!data) throw new Error('The server did not answer. Please try again.');
    return data;
  }

  function settle(data) {
    // Once nothing else is on its way, trust the server's numbers
    if (pending === 0 && data && typeof data.count === 'number') {
      shown.count = data.count;
      shown.total = data.total;
      paint(false);
    }
  }

  document.addEventListener('submit', ev => {
    const form = ev.target;
    if (!form.matches('form[data-ajax-cart]') || ev.defaultPrevented) return;   // validate.js already stopped it
    ev.preventDefault();
    const btn = ev.submitter || form.querySelector('button:not([type=button])');
    if (btn) btn.classList.remove('is-loading');

    const price = parseFloat(form.dataset.price);
    if (!Number.isNaN(price)) {
      /* ---- Ready-made product: update the badge now, save in the background ---- */
      const id = parseInt(form.querySelector('[name=product_id]').value, 10);
      const qtyInput = form.querySelector('[name=quantity]');
      const qty = Math.max(1, parseInt(qtyInput ? qtyInput.value : 1, 10) || 1);
      const isNew = !shown.plain.has(id);
      const delta = { count: isNew ? 1 : 0, total: Math.round(price * qty * 100) / 100 };
      shown.count += delta.count; shown.total += delta.total; shown.plain.add(id);
      paint(true);
      toast(`${form.dataset.name || 'Item'} added to your cart.`);
      if (btn) {
        const label = btn.dataset.label || (btn.dataset.label = btn.textContent);
        btn.textContent = 'Added ✓';
        clearTimeout(btn._t); btn._t = setTimeout(() => { btn.textContent = label; }, 1400);
      }
      pending++;
      queue = queue.then(() => send(form)).then(data => {
        pending--;
        if (!data.success) {
          if (isNew) shown.plain.delete(id);
          toast(data.message || data.error || 'That item could not be added.', false);
        }
        settle(data);
        if (!data.success && pending > 0) { shown.count -= delta.count; shown.total -= delta.total; paint(false); }
      }).catch(err => {
        pending--;
        shown.count -= delta.count; shown.total -= delta.total;
        if (isNew) shown.plain.delete(id);
        paint(false);
        toast(err.message || 'You seem to be offline. That item was not added.', false);
      });
      return;
    }

    /* ---- A design (with a logo upload): the server works out the price, so wait for its answer ---- */
    if (btn) { btn.disabled = true; btn.dataset.label = btn.dataset.label || btn.textContent; btn.textContent = 'Adding to cart…'; }
    pending++;
    queue = queue.then(() => send(form)).then(data => {
      pending--;
      toast(data.message || data.error || (data.success ? 'Added to your cart.' : 'That design could not be saved.'), !!data.success);
      if (data.success && pending > 0) { shown.count += 1; shown.total += data.line_total || 0; }   // other adds still saving
      settle(data);
      if (data.success) paint(true);
      if (!data.success && data.field) {
        const slot = form.querySelector(`[data-error-for="${data.field}"]`);
        if (slot) slot.textContent = data.error;
      }
    }).catch(err => {
      pending--;
      toast(err.message || 'You seem to be offline. The design was not saved.', false);
    }).finally(() => {
      if (btn) { btn.disabled = false; btn.textContent = btn.dataset.label; }
    });
  });
})();
