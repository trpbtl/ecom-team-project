// Client-side validation for every Packe form (the server checks everything again in the controllers).
// Each form has data-validate="<name>"; errors show in <small data-error-for="field">.
(function () {
  const RX = {
    email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    phone: /^[0-9+\-\s]{7,15}$/,
    password: /^(?=.*\d).{8,}$/,
  };
  // Look fields up by name with querySelector: form.elements.length is the field count, not the "length" input
  const field = (f, name) => f.querySelector(`[name="${name}"]`);
  const val = (f, name) => (field(f, name) ? String(field(f, name).value).trim() : '');
  const len = (v, min, max) => v.length >= min && v.length <= max;

  const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  function fileProblem(input, maxMB, types) {
    const file = input && input.files && input.files[0];
    if (!file) return null;
    if (!types.includes(file.type)) return 'Please choose a JPG, PNG, GIF or WebP image.';
    if (file.size > maxMB * 1024 * 1024) return `The image must be ${maxMB} MB or smaller.`;
    return null;
  }

  // Each rule set returns { fieldName: 'message' } for the fields that fail
  const rules = {
    register(f) {
      const e = profile(f);
      if (!RX.password.test(f.elements.password.value)) e.password = 'At least 8 characters, including a number.';
      if (f.elements.confirm_password.value !== f.elements.password.value) e.confirm_password = 'The passwords do not match.';
      return e;
    },
    profile,
    login(f) {
      const e = {};
      if (!RX.email.test(val(f, 'email'))) e.email = 'Enter a valid email address.';
      if (!f.elements.password.value) e.password = 'Enter your password.';
      return e;
    },
    'change-pass'(f) {
      const e = {};
      if (!f.elements.current_password.value) e.current_password = 'Enter your current password.';
      if (!RX.password.test(f.elements.new_password.value)) e.new_password = 'At least 8 characters, including a number.';
      if (f.elements.confirm_password.value !== f.elements.new_password.value) e.confirm_password = 'The passwords do not match.';
      return e;
    },
    'delete-account'(f) {
      const e = {};
      if (!f.elements.password.value) e.password = 'Enter your password.';
      if (val(f, 'confirm') !== 'DELETE') e.confirm = 'Type DELETE in capitals.';
      return e;
    },
    checkout(f) {
      const e = {};
      if (!len(val(f, 'recipient_name'), 2, 100)) e.recipient_name = 'Enter the name of the person receiving the order.';
      if (!RX.phone.test(val(f, 'recipient_phone'))) e.recipient_phone = 'Enter a valid number (7 to 15 digits).';
      if (!len(val(f, 'address'), 5, 255)) e.address = 'Enter the delivery address.';
      if (!len(val(f, 'city'), 2, 60)) e.city = 'Enter the city or town.';
      return e;
    },
    category(f) {
      return len(val(f, 'category_name'), 2, 60) ? {} : { category_name: 'Category name must be 2 to 60 characters.' };
    },
    product(f) {
      const e = {};
      if (!val(f, 'category_id')) e.category_id = 'Choose a category.';
      if (!len(val(f, 'name'), 2, 150)) e.name = 'Product name must be 2 to 150 characters.';
      const price = parseFloat(val(f, 'base_price'));
      if (!(price > 0 && price <= 100000)) e.base_price = 'Enter a price between 0.01 and 100,000.';
      if (val(f, 'description').length > 2000) e.description = 'Description must be 2,000 characters or fewer.';
      const img = fileProblem(field(f, 'image'), 2, IMAGE_TYPES);
      if (img) e.image = img;
      return e;
    },
    design(f) {
      const e = {};
      // Each size box carries its own limits (they differ per packaging type)
      for (const k of ['length', 'width', 'height']) {
        const input = field(f, k);
        if (!input) continue;
        const v = parseFloat(input.value), lo = parseFloat(input.min), hi = parseFloat(input.max);
        if (!(v >= lo && v <= hi)) { e.length = `${input.dataset.label} must be between ${lo} and ${hi}${input.dataset.unit}.`; break; }
        if (input.step === '4' && v % 4 !== 0) { e.length = `${input.dataset.label} must be a multiple of 4.`; break; }
      }
      const img = fileProblem(field(f, 'logo'), 5, ['image/jpeg', 'image/png', 'image/webp']);
      if (img) e.logo = img;
      if (val(f, 'brand_text').length > 28) e.brand_text = 'Keep the text to 28 characters or fewer.';
      return e;
    },
  };

  function profile(f) {
    const e = {};
    if (!len(val(f, 'full_name'), 2, 100)) e.full_name = 'Enter your full name.';
    if (!RX.email.test(val(f, 'email')) || val(f, 'email').length > 100) e.email = 'Enter a valid email address.';
    if (!RX.phone.test(val(f, 'phone'))) e.phone = 'Enter a valid phone number (7 to 15 digits).';
    if (!len(val(f, 'city'), 2, 60)) e.city = 'Enter your city.';
    if (val(f, 'business_name').length > 150) e.business_name = 'Business name must be 150 characters or fewer.';
    if (val(f, 'address').length > 255) e.address = 'Address must be 255 characters or fewer.';
    return e;
  }

  function show(form, errors) {
    form.querySelectorAll('[data-error-for]').forEach(el => { el.textContent = ''; });
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    let first = null;
    for (const [field, msg] of Object.entries(errors)) {
      const slot = form.querySelector(`[data-error-for="${field}"]`);
      if (slot) slot.textContent = msg;
      const input = form.querySelector(`[name="${field}"]`);
      if (input) { input.classList.add('is-invalid'); input.setAttribute('aria-invalid', 'true'); first = first || input; }
    }
    if (first) first.focus();
  }

  document.querySelectorAll('form[data-validate]').forEach(form => {
    const check = rules[form.dataset.validate];
    form.addEventListener('submit', ev => {
      // The cart's "Remove selected" button needs at least one ticked item
      const submitter = ev.submitter;
      if (submitter && submitter.hasAttribute('data-needs-selection') && !form.querySelector('input[name="remove[]"]:checked')) {
        ev.preventDefault();
        alert('Tick the items you want to remove first.');
        return;
      }
      if (check) {
        const errors = check(form);
        if (Object.keys(errors).length) { ev.preventDefault(); show(form, errors); return; }
      }
      const btn = submitter || form.querySelector('button:not([type=button])');
      if (btn) { btn.classList.add('is-loading'); setTimeout(() => btn.classList.remove('is-loading'), 8000); }
    });
    // Show file problems as soon as a file is picked
    form.querySelectorAll('input[type=file]').forEach(input => input.addEventListener('change', () => {
      if (check) show(form, check(form));
    }));
  });

  // Checkout: update the total when the delivery speed changes
  const checkout = document.getElementById('checkout-form');
  if (checkout) {
    const subtotal = parseFloat(document.getElementById('subtotal').dataset.value);
    const fmt = v => 'GH₵' + v.toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    checkout.querySelectorAll('input[name=delivery_option]').forEach(r => r.addEventListener('change', () => {
      const fee = parseFloat(r.dataset.fee);
      document.getElementById('delivery-fee').textContent = fmt(fee);
      document.getElementById('grand-total').textContent = fmt(subtotal + fee);
    }));
  }
})();
