/**
 * Starter Sites Admin JavaScript
 *
 * @package VideoHub360_Starter_Sites
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    var VH360StarterSitesAdmin = {
        demos: [],
        currentImport: null,
        
        init: function() {
            this.bindEvents();
            this.fetchDemos();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Refresh cache button
            $('#vh360-ss-refresh-cache').on('click', function(e) {
                e.preventDefault();
                self.fetchDemos(true);
            });
            
            // Import button (delegated)
            $(document).on('click', '.vh360-ss-import-btn', function(e) {
                e.preventDefault();
                var demoId = $(this).data('demo-id');
                self.confirmImport(demoId);
            });
            
            // View log button
            $('#vh360-ss-view-log').on('click', function(e) {
                e.preventDefault();
                $('#vh360-ss-log-details').slideToggle();
            });
            
            // Complete modal buttons
            $('#vh360-ss-complete-close').on('click', function(e) {
                e.preventDefault();
                self.closeCompleteModal();
            });
            
            $('#vh360-ss-complete-view-site').on('click', function(e) {
                e.preventDefault();
                window.open(vh360StarterSites.siteUrl || '/', '_blank');
            });
            
            $('#vh360-ss-complete-view-log').on('click', function(e) {
                e.preventDefault();
                $('#vh360-ss-complete-log').slideToggle();
            });
        },
        
        fetchDemos: function(forceRefresh) {
            var self = this;
            
            $('#vh360-ss-demos-loading').show();
            $('#vh360-ss-demos-error').hide();
            $('#vh360-ss-demos-grid').html('');
            
            $.ajax({
                url: vh360StarterSites.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_ss_fetch_demos',
                    nonce: vh360StarterSites.nonce,
                    force_refresh: forceRefresh ? 'true' : 'false'
                },
                success: function(response) {
                    $('#vh360-ss-demos-loading').hide();
                    
                    if (response.success && response.data.demos) {
                        self.demos = response.data.demos;
                        self.renderDemos();
                    } else {
                        self.showError(response.data.message || vh360StarterSites.strings.fetchError);
                    }
                },
                error: function() {
                    $('#vh360-ss-demos-loading').hide();
                    self.showError(vh360StarterSites.strings.fetchError);
                }
            });
        },
        
        renderDemos: function() {
            var self = this;
            var $grid = $('#vh360-ss-demos-grid');
            
            if (!this.demos || this.demos.length === 0) {
                $grid.html('<p>' + vh360StarterSites.strings.noDemos + '</p>');
                return;
            }
            
            $.each(this.demos, function(index, demo) {
                var $card = self.createDemoCard(demo);
                $grid.append($card);
            });
        },
        
        createDemoCard: function(demo) {
            var template = $('#vh360-ss-demo-card-template').html();
            
            // Simple template replacement (would use Handlebars in production)
            var html = template
                .replace(/\{\{id\}\}/g, demo.id)
                .replace(/\{\{name\}\}/g, demo.name)
                .replace(/\{\{label\}\}/g, demo.label || '')
                .replace(/\{\{description\}\}/g, demo.description || '')
                .replace(/\{\{version\}\}/g, demo.version)
                .replace(/\{\{category\}\}/g, demo.category || '')
                .replace(/\{\{thumbnail\}\}/g, demo.thumbnail || '');
            
            // Handle conditional preview URL
            if (demo.preview_url) {
                html = html.replace(/\{\{#if preview_url\}\}/g, '').replace(/\{\{\/if\}\}/g, '');
                html = html.replace(/\{\{preview_url\}\}/g, demo.preview_url);
            } else {
                html = html.replace(/\{\{#if preview_url\}\}[\s\S]*?\{\{\/if\}\}/g, '');
            }
            
            // Handle required plugins list
            if (demo.required_plugins && demo.required_plugins.length > 0) {
                var pluginsList = '';
                $.each(demo.required_plugins, function(i, plugin) {
                    pluginsList += '<li>' + plugin + '</li>';
                });
                html = html.replace(/\{\{#if required_plugins\.length\}\}/g, '')
                          .replace(/\{\{\/if\}\}/g, '')
                          .replace(/\{\{#each required_plugins\}\}[\s\S]*?\{\{\/each\}\}/g, pluginsList);
            } else {
                html = html.replace(/\{\{#if required_plugins\.length\}\}[\s\S]*?\{\{\/if\}\}/g, '');
            }
            
            return $(html);
        },
        
        showError: function(message) {
            $('#vh360-ss-demos-error p').text(message);
            $('#vh360-ss-demos-error').show();
        },
        
        confirmImport: function(demoId) {
            var self = this;
            var demo = this.getDemoById(demoId);
            
            if (!demo) {
                alert('Demo not found');
                return;
            }
            
            if (confirm(vh360StarterSites.strings.confirmImport)) {
                this.startImport(demoId, demo.name);
            }
        },
        
        getDemoById: function(demoId) {
            return this.demos.find(function(demo) {
                return demo.id === demoId;
            });
        },
        
        startImport: function(demoId, demoName) {
            var self = this;
            
            this.currentImport = demoId;
            
            // Show progress modal
            $('#vh360-ss-import-progress').fadeIn();
            $('#vh360-ss-progress-demo-name').text(demoName);
            this.updateProgress(0, 'Initializing import...');
            
            // Start import
            $.ajax({
                url: vh360StarterSites.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_ss_import_demo',
                    nonce: vh360StarterSites.nonce,
                    demo_id: demoId
                },
                timeout: 600000, // 10 minutes
                success: function(response) {
                    if (response.success) {
                        self.handleImportSuccess(response.data);
                    } else {
                        self.handleImportError(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    // Handle different error scenarios
                    var errorData = {
                        message: 'Import failed: ' + error
                    };
                    
                    // Try to parse JSON error response
                    if (xhr.responseJSON) {
                        errorData = xhr.responseJSON.data || errorData;
                    } else if (xhr.responseText) {
                        try {
                            var parsed = JSON.parse(xhr.responseText);
                            if (parsed.data) {
                                errorData = parsed.data;
                            }
                        } catch (e) {
                            // Not JSON, use plain text
                            errorData.message = 'Server error (HTTP ' + xhr.status + ')';
                            errorData.details = xhr.responseText.substring(0, 500);
                        }
                    }
                    
                    // Add diagnostic info
                    errorData.http_status = xhr.status;
                    errorData.http_status_text = xhr.statusText;
                    errorData.ajax_status = status;
                    
                    self.handleImportError(errorData);
                }
            });
            
            // Simulate progress (actual progress tracking would require more complex implementation)
            this.simulateProgress();
        },
        
        simulateProgress: function() {
            var self = this;
            var progress = 0;
            var phases = [
                'validate',
                'download',
                'plugins',
                'content',
                'widgets',
                'customizer',
                'elementor',
                'theme-options',
                'post-import',
                'complete'
            ];
            var currentPhase = 0;
            
            var interval = setInterval(function() {
                if (progress >= 90) {
                    clearInterval(interval);
                    return;
                }
                
                progress += Math.random() * 10;
                if (progress > 90) progress = 90;
                
                // Update current phase
                var phaseIndex = Math.floor(progress / 10);
                if (phaseIndex !== currentPhase && phaseIndex < phases.length) {
                    currentPhase = phaseIndex;
                    self.updatePhase(phases[currentPhase], 'active');
                    
                    if (currentPhase > 0) {
                        self.updatePhase(phases[currentPhase - 1], 'completed');
                    }
                }
                
                self.updateProgress(progress, 'Importing...');
            }, 1000);
        },
        
        updateProgress: function(percent, message) {
            $('#vh360-ss-progress-bar').css('width', percent + '%');
            $('#vh360-ss-progress-status .status-text').text(message);
        },
        
        updatePhase: function(phase, status) {
            var $phase = $('.phase-item[data-phase="' + phase + '"]');
            $phase.removeClass('phase-active phase-completed phase-error');
            
            if (status === 'active') {
                $phase.addClass('phase-active');
                $phase.find('.phase-icon').text('⋯');
            } else if (status === 'completed') {
                $phase.addClass('phase-completed');
                $phase.find('.phase-icon').text('✓');
            } else if (status === 'error') {
                $phase.addClass('phase-error');
                $phase.find('.phase-icon').text('✗');
            }
        },
        
        handleImportSuccess: function(data) {
            var self = this;
            
            // Hide progress modal
            $('#vh360-ss-import-progress').fadeOut(function() {
                // Show complete modal
                self.showCompleteModal(true, data);
            });
        },
        
        handleImportError: function(data) {
            var self = this;
            
            // Hide progress modal
            $('#vh360-ss-import-progress').fadeOut(function() {
                // Show complete modal with error
                self.showCompleteModal(false, data);
            });
        },
        
        showCompleteModal: function(success, data) {
            var $modal = $('#vh360-ss-import-complete');
            var $icon = $('#vh360-ss-complete-icon');
            var $title = $('#vh360-ss-complete-title');
            var $message = $('#vh360-ss-complete-message');
            
            if (success) {
                $icon.html('✓').addClass('success').removeClass('error');
                $title.text('Import Completed Successfully!');
                $message.html('<p>The demo has been imported successfully. You can now view your site or customize it further.</p>');
                
                // Show stats
                if (data.log) {
                    var duration = (typeof data.duration !== 'undefined') ? data.duration : (data.log.duration || 0);
                    $('#vh360-ss-stat-duration').text(this.formatDuration(duration));
                    $('#vh360-ss-stat-demo').text(data.demo_name || data.demo_id);
                    $('#vh360-ss-stat-errors').text(data.log.error_count || 0);
                    $('#vh360-ss-complete-stats').show();
                }
                
                $('#vh360-ss-complete-view-site').show();
            } else {
                $icon.html('✗').addClass('error').removeClass('success');
                $title.text('Import Failed');
                
                // Build detailed error message
                var errorHtml = '<p>' + (data.message || 'An error occurred during import.') + '</p>';
                
                // Add diagnostic information if available
                if (data.last_step) {
                    errorHtml += '<p><strong>Last successful step:</strong> ' + data.last_step + '</p>';
                }
                
                if (data.error_type) {
                    errorHtml += '<p><strong>Error type:</strong> ' + data.error_type + '</p>';
                }
                
                if (data.file && data.line) {
                    errorHtml += '<p><strong>Location:</strong> ' + data.file + ':' + data.line + '</p>';
                }
                
                if (data.memory_peak) {
                    var memoryMB = Math.round(data.memory_peak / 1024 / 1024);
                    errorHtml += '<p><strong>Peak memory:</strong> ' + memoryMB + ' MB</p>';
                }
                
                if (data.http_status) {
                    errorHtml += '<p><strong>HTTP Status:</strong> ' + data.http_status + ' ' + (data.http_status_text || '') + '</p>';
                }
                
                $message.html(errorHtml);
                $('#vh360-ss-complete-stats').hide();
                $('#vh360-ss-complete-view-site').hide();
            }
            
            // Show log if available
            if (data.log && data.log.entries) {
                this.renderLog(data.log.entries);
            }
            
            $modal.fadeIn();
        },
        
        renderLog: function(entries) {
            var $logContainer = $('#vh360-ss-complete-log .log-entries');
            $logContainer.empty();
            
            $.each(entries, function(i, entry) {
                var $entry = $('<div>')
                    .addClass('vh360-ss-log-entry')
                    .addClass('log-' + entry.level)
                    .html(
                        '<span class="log-timestamp">[' + entry.timestamp + ']</span> ' +
                        '<span class="log-level">[' + entry.level.toUpperCase() + ']</span> ' +
                        '<span class="log-message">' + entry.message + '</span>'
                    );
                $logContainer.append($entry);
            });
        },
        
        closeCompleteModal: function() {
            $('#vh360-ss-import-complete').fadeOut();
            this.currentImport = null;
            
            // Reload page to show updated status
            location.reload();
        },
        
        formatDuration: function(seconds) {
            if (seconds < 60) {
                return seconds + 's';
            }
            var minutes = Math.floor(seconds / 60);
            var secs = seconds % 60;
            return minutes + 'm ' + secs + 's';
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        VH360StarterSitesAdmin.init();
    });
    
})(jQuery);
