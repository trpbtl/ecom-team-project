/*
 * Light / dark mode toggle (the sun and moon button in the header).
 * The <head> script in views/layout/header.php has already applied the saved choice before the page painted;
 * this file only handles clicks, remembers the choice, and follows the device setting until the visitor picks one.
 */
(function () {
  const root = document.documentElement;
  const button = document.getElementById('theme-toggle');
  const themeMeta = document.querySelector('meta[name="theme-color"]');
  const deviceDark = window.matchMedia('(prefers-color-scheme: dark)');

  function saved() {
    try { return localStorage.getItem('packe-theme'); } catch (e) { return null; }
  }

  function apply(dark) {
    root.classList.toggle('dark', dark);
    root.style.colorScheme = dark ? 'dark' : 'light';
    if (themeMeta) themeMeta.setAttribute('content', dark ? '#161412' : '#c2410c');
    if (button) {
      const label = dark ? 'Switch to light mode' : 'Switch to dark mode';
      button.setAttribute('aria-pressed', String(dark));
      button.setAttribute('aria-label', label);
      button.title = label;
    }
  }

  apply(root.classList.contains('dark'));

  if (button) {
    button.addEventListener('click', function () {
      const dark = !root.classList.contains('dark');
      try { localStorage.setItem('packe-theme', dark ? 'dark' : 'light'); } catch (e) {}
      apply(dark);
    });
  }

  // No choice made yet: follow the device if it switches (e.g. at sunset)
  deviceDark.addEventListener('change', function (e) {
    if (!saved()) apply(e.matches);
  });
})();
