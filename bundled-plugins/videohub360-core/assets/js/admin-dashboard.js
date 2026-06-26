/**
 * VideoHub360 Dashboard JavaScript
 * 
 * Handles interactive dashboard features and chart visualization
 * 
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Dashboard object
     */
    const VH360Dashboard = {
        
        refreshInterval: null,
        
        /**
         * Initialize dashboard
         */
        init: function() {
            this.initChart();
            this.addMobileTableLabels();
            this.setupAutoRefresh();
            this.handlePageVisibility();
        },
        
        /**
         * Setup auto-refresh with cleanup
         */
        setupAutoRefresh: function() {
            // Auto-refresh stats every 5 minutes
            this.refreshInterval = setInterval(() => {
                this.refreshStats();
            }, 300000);
        },
        
        /**
         * Handle page visibility changes to prevent unnecessary requests
         */
        handlePageVisibility: function() {
            const self = this;
            
            // Pause refresh when page is hidden
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    // Page is hidden, clear interval
                    if (self.refreshInterval) {
                        clearInterval(self.refreshInterval);
                        self.refreshInterval = null;
                    }
                } else {
                    // Page is visible again, restart interval
                    if (!self.refreshInterval) {
                        self.setupAutoRefresh();
                    }
                }
            });
            
            // Clean up on page unload
            window.addEventListener('beforeunload', function() {
                if (self.refreshInterval) {
                    clearInterval(self.refreshInterval);
                    self.refreshInterval = null;
                }
            });
        },
        
        /**
         * Initialize views chart
         */
        initChart: function() {
            const canvas = document.getElementById('vh360-views-chart');
            if (!canvas) return;
            
            // Get chart data via AJAX
            $.ajax({
                url: vh360Dashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_get_chart_data',
                    nonce: vh360Dashboard.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        VH360Dashboard.renderChart(canvas, response.data);
                    }
                },
                error: function() {
                    console.error('Failed to load chart data');
                }
            });
        },
        
        /**
         * Render chart with data
         */
        renderChart: function(canvas, data) {
            const ctx = canvas.getContext('2d');
            
            // Create gradient
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(34, 113, 177, 0.5)');
            gradient.addColorStop(1, 'rgba(34, 113, 177, 0.05)');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Views',
                        data: data.views,
                        backgroundColor: gradient,
                        borderColor: '#2271b1',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#2271b1',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 12,
                            cornerRadius: 4,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return 'Views: ' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        },
        
        /**
         * Refresh dashboard statistics
         */
        refreshStats: function() {
            $.ajax({
                url: vh360Dashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_refresh_stats',
                    nonce: vh360Dashboard.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        VH360Dashboard.updateStats(response.data);
                    }
                }
            });
        },
        
        /**
         * Update statistics on page
         */
        updateStats: function(stats) {
            if (stats.total_videos !== undefined) {
                $('.vh360-stat-videos .vh360-stat-number').text(
                    stats.total_videos.toLocaleString()
                );
            }
            
            if (stats.total_views !== undefined) {
                $('.vh360-stat-views .vh360-stat-number').text(
                    stats.total_views.toLocaleString()
                );
            }
            
            if (stats.live_streams !== undefined) {
                $('.vh360-stat-live .vh360-stat-number').text(
                    stats.live_streams.toLocaleString()
                );
            }
            
            if (stats.categories !== undefined) {
                $('.vh360-stat-categories .vh360-stat-number').text(
                    stats.categories.toLocaleString()
                );
            }
        },
        
        /**
         * Add data labels for mobile responsive tables
         */
        addMobileTableLabels: function() {
            $('.vh360-dashboard-table tbody tr').each(function() {
                const $row = $(this);
                const headers = [];
                
                // Get headers
                $row.closest('table').find('thead th').each(function() {
                    headers.push($(this).text());
                });
                
                // Add data-label attributes
                $row.find('td').each(function(index) {
                    if (headers[index]) {
                        $(this).attr('data-label', headers[index]);
                    }
                });
            });
        }
    };
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        VH360Dashboard.init();
    });
    
})(jQuery);
