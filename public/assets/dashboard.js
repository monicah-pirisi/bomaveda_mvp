(function () {
  var el = document.getElementById('dash-data');
  if (!el) return;
  var data;
  try { data = JSON.parse(el.textContent); } catch (e) { return; }

  var brand = {
    blue: '#0077A8', teal: '#00A39A', green: '#4DB88C', yellow: '#F5A623', sand: '#F2EFE8',
    blueLight: '#E3F1F7', tealLight: '#DDF3F1', greenLight: '#E4F5EE', yellowLight: '#FDF0D3',
    text: '#1F2937', muted: '#596579', danger: '#B42318'
  };
  var palette = [brand.blue, brand.teal, brand.green, brand.yellow, brand.danger, '#7C3AED', '#DB2777', '#EA580C'];

  var defaults = Chart.defaults;
  defaults.font.family = "system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif";
  defaults.font.size = 13;
  defaults.color = brand.muted;
  defaults.plugins.legend.labels.usePointStyle = true;
  defaults.plugins.legend.labels.padding = 14;
  defaults.plugins.tooltip.cornerRadius = 8;
  defaults.plugins.tooltip.padding = 10;
  defaults.responsive = true;
  defaults.maintainAspectRatio = false;

  function statusColor(s) {
    var m = { submitted: brand.yellow, under_review: brand.blue, verified: brand.teal,
      intervention_assigned: '#7C3AED', active_support: brand.green, monitoring: '#059669', resolved: '#6B7280' };
    return m[s] || brand.muted;
  }
  function urgencyColor(u) {
    return { low: brand.green, moderate: brand.blue, urgent: brand.danger }[u] || brand.muted;
  }

  if (data.byStatus) {
    var ctx = document.getElementById('chart-status');
    if (ctx) new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: data.byStatus.map(function (d) { return d.label; }),
        datasets: [{ data: data.byStatus.map(function (d) { return d.count; }),
          backgroundColor: data.byStatus.map(function (d) { return statusColor(d.key); }),
          borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }]
      },
      options: { cutout: '62%', plugins: { legend: { position: 'bottom' } } }
    });
  }

  if (data.byCategory) {
    var ctx2 = document.getElementById('chart-category');
    if (ctx2) new Chart(ctx2, {
      type: 'bar',
      data: {
        labels: data.byCategory.map(function (d) { return d.label; }),
        datasets: [{ data: data.byCategory.map(function (d) { return d.count; }),
          backgroundColor: palette, borderRadius: 6, borderSkipped: false, maxBarThickness: 40 }]
      },
      options: { indexAxis: 'y', plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.06)' }, ticks: { stepSize: 1 } },
                  y: { grid: { display: false } } } }
    });
  }

  if (data.byCounty) {
    var ctx3 = document.getElementById('chart-county');
    if (ctx3) new Chart(ctx3, {
      type: 'bar',
      data: {
        labels: data.byCounty.map(function (d) { return d.label; }),
        datasets: [{ label: 'Cases', data: data.byCounty.map(function (d) { return d.count; }),
          backgroundColor: brand.teal, borderRadius: 6, borderSkipped: false, maxBarThickness: 44 }]
      },
      options: { plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.06)' }, ticks: { stepSize: 1 } },
                  x: { grid: { display: false } } } }
    });
  }

  if (data.byUrgency) {
    var ctx4 = document.getElementById('chart-urgency');
    if (ctx4) new Chart(ctx4, {
      type: 'doughnut',
      data: {
        labels: data.byUrgency.map(function (d) { return d.label; }),
        datasets: [{ data: data.byUrgency.map(function (d) { return d.count; }),
          backgroundColor: data.byUrgency.map(function (d) { return urgencyColor(d.key); }),
          borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }]
      },
      options: { cutout: '62%', plugins: { legend: { position: 'bottom' } } }
    });
  }

  if (data.timeline && data.timeline.length > 0) {
    var ctx5 = document.getElementById('chart-timeline');
    if (ctx5) new Chart(ctx5, {
      type: 'line',
      data: {
        labels: data.timeline.map(function (d) { return d.month; }),
        datasets: [{ label: 'Cases', data: data.timeline.map(function (d) { return d.count; }),
          borderColor: brand.blue, backgroundColor: brand.blueLight, fill: true, tension: .35,
          pointRadius: 4, pointBackgroundColor: brand.blue, pointBorderColor: '#fff', pointBorderWidth: 2 }]
      },
      options: { plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.06)' }, ticks: { stepSize: 1 } },
                  x: { grid: { display: false } } } }
    });
  }

  if (data.referrals) {
    var ctx6 = document.getElementById('chart-referrals');
    if (ctx6) new Chart(ctx6, {
      type: 'doughnut',
      data: {
        labels: data.referrals.map(function (d) { return d.label; }),
        datasets: [{ data: data.referrals.map(function (d) { return d.count; }),
          backgroundColor: [brand.yellow, brand.green, brand.danger, brand.teal],
          borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }]
      },
      options: { cutout: '62%', plugins: { legend: { position: 'bottom' } } }
    });
  }

  var filterForm = document.getElementById('dash-filter');
  if (filterForm) {
    filterForm.addEventListener('change', function () { filterForm.submit(); });
  }
})();
