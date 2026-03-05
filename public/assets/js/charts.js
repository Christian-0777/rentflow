// public/assets/js/charts.js
// Chart.js helpers + export to PNG/PDF

function renderPie(canvasId, labels, series) {
  const ctx = document.getElementById(canvasId);
  const datasets = series.map(s => ({
    label: s.label,
    data: s.data,
    backgroundColor: s.color
  }));
  new Chart(ctx, {
    type: 'pie',
    data: { labels, datasets },
    options: { responsive: true }
  });
}

function renderDoughnut(canvasId, labels, series) {
  const ctx = document.getElementById(canvasId);
  const datasets = series.map(s => ({
    label: s.label,
    data: s.data,
    backgroundColor: s.color
  }));
  new Chart(ctx, {
    type: 'doughnut',
    data: { labels, datasets },
    options: { responsive: true }
  });
}

function renderBar(canvasId, labels, data, label) {
  const ctx = document.getElementById(canvasId);
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{ label, data, backgroundColor: '#0B3C5D' }]
    },
    options: { responsive: true }
  });
}

function renderLine(canvasId, labels, data, label) {
  const ctx = document.getElementById(canvasId);
  new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{ 
        label, 
        data, 
        backgroundColor: 'rgba(11, 60, 93, 0.2)',
        borderColor: '#0B3C5D',
        borderWidth: 2,
        fill: true
      }]
    },
    options: { responsive: true }
  });
}

function renderChart(canvasId, type, labels, data, label = null, series = null) {
  // Destroy existing chart if it exists
  const ctx = document.getElementById(canvasId);
  if (ctx.chart) {
    ctx.chart.destroy();
  }
  
  let config;
  
  if (type === 'pie' || type === 'doughnut') {
    const datasets = series.map(s => ({
      label: s.label,
      data: s.data,
      backgroundColor: s.color
    }));
    config = {
      type: type,
      data: { labels, datasets },
      options: { responsive: true }
    };
  } else if (type === 'bar') {
    // For bar charts, if we have series data (like from pie chart), convert to multiple datasets
    if (series && canvasId === 'pieAvail') {
      const datasets = series.map(s => ({
        label: s.label,
        data: s.data,
        backgroundColor: s.color
      }));
      config = {
        type: 'bar',
        data: { labels, datasets },
        options: { responsive: true }
      };
    } else {
      // Single dataset bar chart
      config = {
        type: 'bar',
        data: {
          labels,
          datasets: [{ 
            label, 
            data, 
            backgroundColor: '#0B3C5D'
          }]
        },
        options: { responsive: true }
      };
    }
  } else if (type === 'line') {
    config = {
      type: 'line',
      data: {
        labels,
        datasets: [{ 
          label, 
          data, 
          backgroundColor: 'rgba(11, 60, 93, 0.2)',
          borderColor: '#0B3C5D',
          borderWidth: 2,
          fill: true
        }]
      },
      options: { responsive: true }
    };
  }
  
  ctx.chart = new Chart(ctx, config);
}

function exportPNG(canvasId) {
  const canvas = document.getElementById(canvasId);
  const link = document.createElement('a');
  link.download = canvasId + '.png';
  link.href = canvas.toDataURL('image/png');
  link.click();
}

function exportPDF(canvasId) {
  const canvas = document.getElementById(canvasId);
  const dataUrl = canvas.toDataURL('image/png');
  
  // Create a form to post to the API
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/rentflow/api/export_pdf.php';
  form.target = '_blank'; // Open in new tab
  
  const dataInput = document.createElement('input');
  dataInput.type = 'hidden';
  dataInput.name = 'dataUrl';
  dataInput.value = dataUrl;
  form.appendChild(dataInput);
  
  const nameInput = document.createElement('input');
  nameInput.type = 'hidden';
  nameInput.name = 'name';
  nameInput.value = canvasId;
  form.appendChild(nameInput);
  
  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);
}
