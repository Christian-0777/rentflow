/**
 * RentFlow Charts Module
 * Handles chart rendering with Chart.js and export functionality
 * 
 * Usage: RentFlow.chart.create('canvasId', 'pie', { labels: [...], datasets: [...] })
 */

RentFlow.chart = {
  /**
   * Unified chart creation function (replaces renderPie, renderBar, etc.)
   * @param {string} canvasId - Canvas element ID
   * @param {string} type - Chart type: 'pie', 'doughnut', 'bar', 'line'
   * @param {object} config - Configuration object
   * @param {array} config.labels - Chart labels
   * @param {array} config.datasets - Chart datasets
   * @param {object} config.options - Chart.js options (optional)
   */
  create: function(canvasId, type, config) {
    try {
      const ctx = document.getElementById(canvasId);
      if (!ctx) {
        console.error(`Chart: Canvas element with ID "${canvasId}" not found`);
        return null;
      }

      // Destroy existing chart if it exists
      if (ctx.chart && typeof ctx.chart.destroy === 'function') {
        ctx.chart.destroy();
      }

      // Default options
      const defaultOptions = {
        responsive: true,
        maintainAspectRatio: true
      };

      // Merge user options with defaults
      const options = { ...defaultOptions, ...(config.options || {}) };

      // Create the chart
      const chartConfig = {
        type: type,
        data: {
          labels: config.labels || [],
          datasets: config.datasets || []
        },
        options: options
      };

      ctx.chart = new Chart(ctx, chartConfig);
      return ctx.chart;
    } catch (error) {
      console.error(`Error creating chart "${canvasId}":`, error);
      return null;
    }
  },

  /**
   * Create pie chart
   */
  pie: function(canvasId, labels, series) {
    const datasets = series.map(s => ({
      label: s.label,
      data: s.data,
      backgroundColor: s.color
    }));

    return this.create(canvasId, 'pie', {
      labels: labels,
      datasets: datasets
    });
  },

  /**
   * Create doughnut chart
   */
  doughnut: function(canvasId, labels, series) {
    const datasets = series.map(s => ({
      label: s.label,
      data: s.data,
      backgroundColor: s.color
    }));

    return this.create(canvasId, 'doughnut', {
      labels: labels,
      datasets: datasets
    });
  },

  /**
   * Create bar chart
   */
  bar: function(canvasId, labels, data, label = null, series = null) {
    let datasets;

    if (series && Array.isArray(series)) {
      // Multiple datasets from series data
      datasets = series.map(s => ({
        label: s.label,
        data: s.data,
        backgroundColor: s.color
      }));
    } else {
      // Single dataset
      datasets = [{
        label: label || 'Data',
        data: data || [],
        backgroundColor: '#0B3C5D'
      }];
    }

    return this.create(canvasId, 'bar', {
      labels: labels,
      datasets: datasets
    });
  },

  /**
   * Create line chart
   */
  line: function(canvasId, labels, data, label = null) {
    const datasets = [{
      label: label || 'Data',
      data: data || [],
      backgroundColor: 'rgba(11, 60, 93, 0.2)',
      borderColor: '#0B3C5D',
      borderWidth: 2,
      fill: true,
      tension: 0.4
    }];

    return this.create(canvasId, 'line', {
      labels: labels,
      datasets: datasets,
      options: {
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  },

  /**
   * Export chart to PNG
   */
  exportPNG: function(canvasId, filename = null) {
    try {
      const canvas = document.getElementById(canvasId);
      if (!canvas) {
        console.error(`Canvas element with ID "${canvasId}" not found`);
        return;
      }

      const link = document.createElement('a');
      link.download = filename || (canvasId + '.png');
      link.href = canvas.toDataURL('image/png');
      link.click();
    } catch (error) {
      console.error('Error exporting PNG:', error);
      RentFlow.ui.showAlert('Failed to export PNG', 'danger');
    }
  },

  /**
   * Export chart to PDF (via API)
   */
  exportPDF: function(canvasId, filename = null) {
    try {
      const canvas = document.getElementById(canvasId);
      if (!canvas) {
        console.error(`Canvas element with ID "${canvasId}" not found`);
        return;
      }

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
      nameInput.value = filename || canvasId;
      form.appendChild(nameInput);

      document.body.appendChild(form);
      form.submit();
      document.body.removeChild(form);
    } catch (error) {
      console.error('Error exporting PDF:', error);
      RentFlow.ui.showAlert('Failed to export PDF', 'danger');
    }
  }
};

// ========== LEGACY ALIASES (For backward compatibility) ==========
function renderPie(canvasId, labels, series) {
  return RentFlow.chart.pie(canvasId, labels, series);
}

function renderDoughnut(canvasId, labels, series) {
  return RentFlow.chart.doughnut(canvasId, labels, series);
}

function renderBar(canvasId, labels, data, label) {
  return RentFlow.chart.bar(canvasId, labels, data, label);
}

function renderLine(canvasId, labels, data, label) {
  return RentFlow.chart.line(canvasId, labels, data, label);
}

function renderChart(canvasId, type, labels, data, label = null, series = null) {
  return RentFlow.chart.create(canvasId, type, {
    labels: labels,
    datasets: series ? 
      series.map(s => ({ label: s.label, data: s.data, backgroundColor: s.color })) :
      [{ label: label, data: data, backgroundColor: '#0B3C5D' }]
  });
}

function exportPNG(canvasId) {
  return RentFlow.chart.exportPNG(canvasId);
}

function exportPDF(canvasId) {
  return RentFlow.chart.exportPDF(canvasId);
}
