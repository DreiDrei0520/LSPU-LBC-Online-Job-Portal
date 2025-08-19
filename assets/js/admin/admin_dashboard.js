 document.addEventListener('DOMContentLoaded', function() {
      // Initialize AOS
      AOS.init({
        duration: 800,
        once: true
      });



      // Pie Chart Options
      const pieChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right',
            labels: {
              padding: 20,
              usePointStyle: true,
              pointStyle: 'circle'
            }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const label = context.label || '';
                const value = context.raw || 0;
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = Math.round((value / total) * 100);
                return `${label}: ${value} (${percentage}%)`;
              }
            }
          }
        }
      };

      // Create Pie Chart
      const pieCtx = document.getElementById('statusPieChart').getContext('2d');
      new Chart(pieCtx, {
        type: 'pie',
        data: pieChartData,
        options: pieChartOptions
      });



      // Bar Chart Options
      const barChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          }
        },
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                return `${context.dataset.label}: ${context.raw}`;
              }
            }
          }
        }
      };

      // Create Bar Chart
      const barCtx = document.getElementById('statusBarChart').getContext('2d');
      new Chart(barCtx, {
        type: 'bar',
        data: barChartData,
        options: barChartOptions
      });

      // Show mobile toggle button only on mobile
      function checkMobileView() {
        if (window.innerWidth < 992) {
          document.getElementById('mobileSidebarToggle').style.display = 'flex';
        } else {
          document.getElementById('mobileSidebarToggle').style.display = 'none';
        }
      }
      
      // Initial check
      checkMobileView();
      
      // Check on resize
      window.addEventListener('resize', checkMobileView);
    });