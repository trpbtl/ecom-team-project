// Charts for the admin Analytics page (views/admin/analytics.php), drawn with Chart.js.
// The numbers come from the server as JSON in <script id="analytics-data">; colours follow light/dark mode.
(function () {
  const data = JSON.parse(document.getElementById('analytics-data').textContent);
  if (!window.Chart) return;

  const css = name => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
  const rgb = (name, a = 1) => `rgb(${css(name)} / ${a})`;
  const money = v => 'GH₵' + Number(v).toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const palette = ['#c2410c', '#2563eb', '#16a34a', '#a855f7', '#f59e0b', '#64748b'];
  const charts = [];

  function theme() {
    Chart.defaults.color = rgb('--muted-rgb');
    Chart.defaults.borderColor = rgb('--line-rgb');
    Chart.defaults.font.family = "'DM Sans', 'Segoe UI', Arial, sans-serif";
  }

  function draw() {
    charts.splice(0).forEach(c => c.destroy());
    theme();

    charts.push(new Chart(document.getElementById('chart-daily'), {
      data: {
        labels: data.daily.labels,
        datasets: [
          { type: 'bar', label: 'Sales (GH₵)', data: data.daily.revenue, backgroundColor: 'rgba(194, 65, 12, .75)', borderRadius: 4, yAxisID: 'y' },
          { type: 'line', label: 'Orders', data: data.daily.orders, borderColor: rgb('--ink-rgb'), backgroundColor: rgb('--ink-rgb'),
            tension: .3, pointRadius: 2, yAxisID: 'y1' }
        ]
      },
      options: {
        maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
        scales: {
          y: { beginAtZero: true, ticks: { callback: v => 'GH₵' + v } },
          y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { precision: 0 } },
          x: { ticks: { maxTicksLimit: 10 } }
        },
        plugins: { tooltip: { callbacks: { label: c => c.dataset.yAxisID === 'y' ? `Sales: ${money(c.raw)}` : `Orders: ${c.raw}` } } }
      }
    }));

    const doughnut = (id, labels, values, fmt) => charts.push(new Chart(document.getElementById(id), {
      type: 'doughnut',
      data: { labels, datasets: [{ data: values, backgroundColor: palette, borderColor: rgb('--surface-rgb'), borderWidth: 2 }] },
      options: { maintainAspectRatio: false, cutout: '62%',
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10 } },
                   tooltip: { callbacks: { label: c => `${c.label}: ${fmt(c.raw)}` } } } }
    }));
    doughnut('chart-streams', data.streams.labels, data.streams.values, money);
    doughnut('chart-status', data.statuses.labels, data.statuses.values, v => `${v} orders`);
  }

  draw();
  // Redraw in the right colours when the light/dark toggle is used
  new MutationObserver(draw).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
})();
