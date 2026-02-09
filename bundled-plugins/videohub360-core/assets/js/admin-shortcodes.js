/**
 * VideoHub360 Admin Shortcodes Page JavaScript
 * 
 * Handles shortcode builder, search, copy history, and interactive features
 */

(function($) {
    'use strict';
    
    const VH360Shortcodes = {
        
        // Configuration
        config: {
            searchDebounceDelay: 300,
            copyHistoryLimit: 5,
            toastDuration: 3000,
            localStorageKey: 'vh360_copy_history'
        },
        
        // State
        state: {
            searchTerm: '',
            searchDebounceTimer: null,
            copyHistory: [],
            currentShortcodeType: null
        },
        
        /**
         * Initialize all functionality
         */
        init: function() {
            this.loadCopyHistory();
            this.initShortcodeBuilder();
            this.initSearch();
            this.initCopyButtons();
            this.initTaxonomyReference();
            this.initAccordions();
            this.initPreviewButtons();
            this.initExportImport();
            this.renderCopyHistory();
            this.initSmoothScroll();
            this.initTooltips();
        },
        
        /**
         * Initialize shortcode builder
         */
        initShortcodeBuilder: function() {
            const self = this;
            const $builder = $('#vh360-shortcode-builder');
            
            if (!$builder.length) return;
            
            // Shortcode type selection
            $('#vh360-shortcode-type').on('change', function() {
                const type = $(this).val();
                self.state.currentShortcodeType = type;
                
                // Show/hide parameter sections
                $('.vh360-builder-params').hide();
                if (type) {
                    $('#vh360-params-' + type).show();
                }
                
                // Clear generated shortcode
                $('#vh360-generated-shortcode').val('').parent().hide();
            });
            
            // Generate shortcode button
            $('#vh360-generate-shortcode').on('click', function(e) {
                e.preventDefault();
                self.generateShortcode();
            });
            
            // Reset builder button
            $('#vh360-reset-builder').on('click', function(e) {
                e.preventDefault();
                self.resetBuilder();
            });
            
            // Copy generated shortcode button
            $('#vh360-copy-generated').on('click', function(e) {
                e.preventDefault();
                const shortcode = $('#vh360-generated-shortcode').val();
                self.copyToClipboard(shortcode, $(this));
            });
            
            // Validate number inputs
            $builder.find('input[type="number"]').on('input', function() {
                self.validateNumberInput($(this));
            });
        },
        
        /**
         * Generate shortcode from builder inputs
         */
        generateShortcode: function() {
            const type = this.state.currentShortcodeType;
            if (!type) {
                this.showToast('Please select a shortcode type first', 'error');
                return;
            }
            
            let shortcode = '';
            const $paramsContainer = $('#vh360-params-' + type);
            
            if (type === 'hero') {
                shortcode = '[videohub360_hero';
                
                $paramsContainer.find('input, select').each(function() {
                    const $field = $(this);
                    const value = $field.val().trim();
                    const defaultValue = $field.data('default');
                    
                    // Only include if value is different from default and not empty
                    if (value && value !== defaultValue) {
                        const paramName = $field.attr('name').replace('hero_', '');
                        shortcode += ` ${paramName}="${value}"`;
                    }
                });
                
                shortcode += ']';
            } else if (type === 'videos') {
                shortcode = '[videohub360_videos';
                
                $paramsContainer.find('input, select').each(function() {
                    const $field = $(this);
                    const value = $field.val().trim();
                    const defaultValue = $field.data('default');
                    
                    // Only include if value is different from default and not empty
                    if (value && value !== defaultValue) {
                        const paramName = $field.attr('name').replace('videos_', '');
                        shortcode += ` ${paramName}="${value}"`;
                    }
                });
                
                shortcode += ']';
            }
            
            // Display generated shortcode
            $('#vh360-generated-shortcode').val(shortcode).parent().show();
            
            // Scroll to generated shortcode
            $('html, body').animate({
                scrollTop: $('#vh360-generated-shortcode').offset().top - 100
            }, 500);
            
            this.showToast('Shortcode generated successfully!', 'success');
        },
        
        /**
         * Reset builder form
         */
        resetBuilder: function() {
            $('#vh360-shortcode-type').val('').trigger('change');
            $('.vh360-builder-params').find('input, select').each(function() {
                const $field = $(this);
                const defaultValue = $field.data('default') || '';
                $field.val(defaultValue);
            });
            $('#vh360-generated-shortcode').val('').parent().hide();
            this.showToast('Builder reset', 'info');
        },
        
        /**
         * Validate number input
         */
        validateNumberInput: function($input) {
            const value = parseInt($input.val());
            const min = $input.attr('min');
            const max = $input.attr('max');
            
            let message = '';
            let isValid = true;
            
            if (isNaN(value)) {
                return;
            }
            
            if (min !== undefined && !isNaN(parseInt(min)) && value < parseInt(min)) {
                message = `Value must be at least ${min}`;
                isValid = false;
            } else if (max !== undefined && !isNaN(parseInt(max)) && value > parseInt(max)) {
                message = `Value must be at most ${max}`;
                isValid = false;
            }
            
            // Show/hide validation message
            let $validationMsg = $input.next('.vh360-validation-msg');
            if (!$validationMsg.length) {
                $validationMsg = $('<span class="vh360-validation-msg"></span>');
                $input.after($validationMsg);
            }
            
            if (isValid) {
                $validationMsg.hide();
            } else {
                $validationMsg.text(message).show();
            }
        },
        
        /**
         * Initialize search functionality
         */
        initSearch: function() {
            const self = this;
            const $searchInput = $('#vh360-search-shortcodes');
            
            if (!$searchInput.length) return;
            
            $searchInput.on('input', function() {
                const searchTerm = $(this).val();
                
                // Clear existing timer
                clearTimeout(self.state.searchDebounceTimer);
                
                // Set new debounce timer
                self.state.searchDebounceTimer = setTimeout(function() {
                    self.performSearch(searchTerm);
                }, self.config.searchDebounceDelay);
            });
            
            // Clear search button
            $('#vh360-clear-search').on('click', function(e) {
                e.preventDefault();
                $searchInput.val('').trigger('input');
            });
            
            // Clear search button in no results message
            $(document).on('click', '.vh360-clear-search-btn', function(e) {
                e.preventDefault();
                $searchInput.val('').trigger('input');
            });
        },
        
        /**
         * Perform search filtering
         */
        performSearch: function(searchTerm) {
            this.state.searchTerm = searchTerm.toLowerCase().trim();
            
            if (!this.state.searchTerm) {
                // Show all sections if search is empty
                $('.vh360-shortcode-item, .vh360-shortcode-category').show();
                $('.vh360-search-highlight').contents().unwrap();
                $('#vh360-no-results').hide();
                return;
            }
            
            let hasResults = false;
            
            // Search through shortcode items
            $('.vh360-shortcode-item').each((index, item) => {
                const $item = $(item);
                const text = $item.text().toLowerCase();
                const codeText = $item.find('.vh360-code-block').text().toLowerCase();
                
                if (text.includes(this.state.searchTerm) || codeText.includes(this.state.searchTerm)) {
                    $item.show();
                    hasResults = true;
                    this.highlightSearchTerm($item);
                } else {
                    $item.hide();
                }
            });
            
            // Show/hide categories based on visible items
            $('.vh360-shortcode-category').each((index, category) => {
                const $category = $(category);
                const visibleItems = $category.find('.vh360-shortcode-item:visible').length;
                
                if (visibleItems > 0) {
                    $category.show();
                } else {
                    $category.hide();
                }
            });
            
            // Show/hide no results message
            if (hasResults) {
                $('#vh360-no-results').hide();
            } else {
                $('#vh360-no-results').show();
            }
        },
        
        /**
         * Highlight search term in results
         */
        highlightSearchTerm: function($element) {
            // Remove existing highlights
            $element.find('.vh360-search-highlight').contents().unwrap();
            
            if (!this.state.searchTerm) return;
            
            // Highlight text in h3 and description
            const $textElements = $element.find('h3, > p');
            
            $textElements.each((index, el) => {
                const $el = $(el);
                const text = $el.text();
                
                // Escape HTML entities in the original text first
                const escapedText = this.escapeHtml(text);
                
                // Escape the search term for use in regex
                const regex = new RegExp(`(${this.escapeRegex(this.state.searchTerm)})`, 'gi');
                
                // Replace with highlighted version
                const highlightedText = escapedText.replace(regex, '<span class="vh360-search-highlight">$1</span>');
                
                if (escapedText !== highlightedText) {
                    $el.html(highlightedText);
                }
            });
        },
        
        /**
         * Escape special characters for regex
         */
        escapeRegex: function(str) {
            return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        },
        
        /**
         * Initialize copy to clipboard functionality
         */
        initCopyButtons: function() {
            const self = this;
            
            $(document).on('click', '.vh360-copy-btn, .vh360-copy-slug', function(e) {
                e.preventDefault();
                const $button = $(this);
                const shortcode = $button.data('shortcode') || $button.data('slug') || '';
                
                if (shortcode) {
                    self.copyToClipboard(shortcode, $button);
                }
            });
        },
        
        /**
         * Copy text to clipboard with enhanced UX
         */
        copyToClipboard: function(text, $button) {
            const self = this;
            
            // Copy to clipboard
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    self.onCopySuccess(text, $button);
                }).catch(function(err) {
                    console.error('Clipboard API failed:', err);
                    self.copyFallback(text, $button);
                });
            } else {
                self.copyFallback(text, $button);
            }
        },
        
        /**
         * Fallback copy method
         */
        copyFallback: function(text, $button) {
            const $temp = $('<textarea>').val(text).css({
                position: 'absolute',
                left: '-9999px'
            }).appendTo('body');
            
            $temp.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    this.onCopySuccess(text, $button);
                }
            } catch(err) {
                console.error('Copy failed:', err);
            }
            
            $temp.remove();
        },
        
        /**
         * Handle successful copy
         */
        onCopySuccess: function(text, $button) {
            // Visual feedback on button
            const originalHtml = $button.html();
            $button.html('<span class="dashicons dashicons-yes"></span> ' + vh360Admin.copiedText)
                   .addClass('vh360-copied');
            
            setTimeout(function() {
                $button.html(originalHtml).removeClass('vh360-copied');
            }, 2000);
            
            // Show toast notification
            this.showToast('Shortcode copied to clipboard!', 'success');
            
            // Add to copy history
            this.addToCopyHistory(text);
        },
        
        /**
         * Show toast notification
         */
        showToast: function(message, type = 'info') {
            const $toast = $('<div class="vh360-toast vh360-toast-' + type + '"></div>')
                .html('<span class="vh360-toast-message">' + message + '</span>')
                .appendTo('body');
            
            // Animate in
            setTimeout(function() {
                $toast.addClass('vh360-toast-show');
            }, 10);
            
            // Auto-hide
            setTimeout(function() {
                $toast.removeClass('vh360-toast-show');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, this.config.toastDuration);
        },
        
        /**
         * Load copy history from localStorage
         */
        loadCopyHistory: function() {
            try {
                const stored = localStorage.getItem(this.config.localStorageKey);
                if (stored) {
                    this.state.copyHistory = JSON.parse(stored);
                }
            } catch(e) {
                console.error('Failed to load copy history:', e);
                this.state.copyHistory = [];
            }
        },
        
        /**
         * Add item to copy history
         */
        addToCopyHistory: function(text) {
            const timestamp = new Date().toISOString();
            
            // Remove duplicate if exists
            this.state.copyHistory = this.state.copyHistory.filter(item => item.text !== text);
            
            // Add to beginning
            this.state.copyHistory.unshift({
                text: text,
                timestamp: timestamp
            });
            
            // Limit to max items
            this.state.copyHistory = this.state.copyHistory.slice(0, this.config.copyHistoryLimit);
            
            // Save to localStorage
            try {
                localStorage.setItem(this.config.localStorageKey, JSON.stringify(this.state.copyHistory));
            } catch(e) {
                console.error('Failed to save copy history:', e);
            }
            
            // Re-render history
            this.renderCopyHistory();
        },
        
        /**
         * Render copy history dropdown
         */
        renderCopyHistory: function() {
            const $historyContainer = $('#vh360-copy-history-items');
            if (!$historyContainer.length) return;
            
            $historyContainer.empty();
            
            if (this.state.copyHistory.length === 0) {
                $historyContainer.html('<div class="vh360-history-empty">No recent copies</div>');
                return;
            }
            
            this.state.copyHistory.forEach((item, index) => {
                const date = new Date(item.timestamp);
                const timeAgo = this.getTimeAgo(date);
                const shortText = item.text.length > 60 ? item.text.substring(0, 60) + '...' : item.text;
                
                const $item = $(`
                    <div class="vh360-history-item">
                        <div class="vh360-history-text">${this.escapeHtml(shortText)}</div>
                        <div class="vh360-history-meta">
                            <span class="vh360-history-time">${timeAgo}</span>
                            <button type="button" class="button button-small vh360-recopy-btn" data-index="${index}">
                                <span class="dashicons dashicons-clipboard"></span> Copy
                            </button>
                        </div>
                    </div>
                `);
                
                $historyContainer.append($item);
            });
            
            // Re-copy from history
            $('.vh360-recopy-btn').on('click', (e) => {
                e.preventDefault();
                const index = $(e.currentTarget).data('index');
                const item = this.state.copyHistory[index];
                if (item) {
                    this.copyToClipboard(item.text, $(e.currentTarget));
                }
            });
        },
        
        /**
         * Clear copy history
         */
        clearCopyHistory: function() {
            this.state.copyHistory = [];
            try {
                localStorage.removeItem(this.config.localStorageKey);
            } catch(e) {
                console.error('Failed to clear copy history:', e);
            }
            this.renderCopyHistory();
            this.showToast('Copy history cleared', 'info');
        },
        
        /**
         * Get relative time string
         */
        getTimeAgo: function(date) {
            const seconds = Math.floor((new Date() - date) / 1000);
            
            if (seconds < 60) return 'Just now';
            if (seconds < 3600) return Math.floor(seconds / 60) + ' min ago';
            if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
            return Math.floor(seconds / 86400) + ' days ago';
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        },
        
        /**
         * Initialize taxonomy reference tool
         */
        initTaxonomyReference: function() {
            const self = this;
            const $container = $('#vh360-taxonomy-reference');
            
            if (!$container.length) return;
            
            // Load taxonomies via AJAX
            $.ajax({
                url: vh360ShortcodeBuilder.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_get_taxonomy_terms',
                    nonce: vh360ShortcodeBuilder.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.renderTaxonomyReference(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Failed to load taxonomies:', error);
                }
            });
        },
        
        /**
         * Render taxonomy reference
         */
        renderTaxonomyReference: function(taxonomies) {
            const $container = $('#vh360-taxonomy-reference');
            
            Object.keys(taxonomies).forEach(taxKey => {
                const tax = taxonomies[taxKey];
                const $section = $(`
                    <div class="vh360-taxonomy-section">
                        <h4>${tax.label}</h4>
                        <div class="vh360-taxonomy-terms"></div>
                    </div>
                `);
                
                const $termsContainer = $section.find('.vh360-taxonomy-terms');
                
                if (tax.terms.length === 0) {
                    $termsContainer.html(`
                        <p class="vh360-no-terms">
                            No terms found. <a href="edit-tags.php?taxonomy=${taxKey}&post_type=videohub360">Create some terms</a>
                        </p>
                    `);
                } else {
                    tax.terms.forEach(term => {
                        const $term = $(`
                            <div class="vh360-term-item">
                                <span class="vh360-term-name">${this.escapeHtml(term.name)}</span>
                                <code class="vh360-term-slug">${term.slug}</code>
                                <button type="button" class="button button-small vh360-copy-slug" data-slug="${term.slug}" title="Copy slug">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                                <span class="vh360-term-count">${term.count} videos</span>
                            </div>
                        `);
                        $termsContainer.append($term);
                    });
                }
                
                $container.append($section);
            });
        },
        
        /**
         * Initialize accordions
         */
        initAccordions: function() {
            $('.vh360-accordion-header').on('click', function() {
                const $header = $(this);
                const $accordion = $header.closest('.vh360-accordion');
                const $content = $accordion.find('.vh360-accordion-content');
                
                $accordion.toggleClass('vh360-accordion-open');
                $content.slideToggle(300);
            });
        },
        
        /**
         * Initialize preview buttons
         */
        initPreviewButtons: function() {
            const self = this;
            
            $('.vh360-preview-btn').on('click', function(e) {
                e.preventDefault();
                const $button = $(this);
                const shortcode = $button.data('shortcode');
                
                self.showPreview(shortcode, $button);
            });
        },
        
        /**
         * Show shortcode preview
         */
        showPreview: function(shortcode, $button) {
            const $previewContainer = $button.closest('.vh360-shortcode-item').find('.vh360-preview-container');
            
            if ($previewContainer.is(':visible')) {
                $previewContainer.slideUp(300);
                $button.text('Preview');
                return;
            }
            
            // Show loading
            $previewContainer.html('<div class="vh360-preview-loading"><span class="spinner is-active"></span> Loading preview...</div>').slideDown(300);
            $button.text('Close Preview');
            
            // Load preview via AJAX
            $.ajax({
                url: vh360ShortcodeBuilder.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_preview_shortcode',
                    shortcode: shortcode,
                    nonce: vh360ShortcodeBuilder.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $previewContainer.html(`
                            <div class="vh360-preview-content">
                                <div class="vh360-preview-note">
                                    <strong>Note:</strong> This preview uses actual data from your site. If no content exists, it may appear empty.
                                </div>
                                <div class="vh360-preview-output">${response.data.html}</div>
                            </div>
                        `);
                    } else {
                        $previewContainer.html(`
                            <div class="vh360-preview-error">
                                Failed to load preview: ${response.data.message || 'Unknown error'}
                            </div>
                        `);
                    }
                },
                error: function() {
                    $previewContainer.html(`
                        <div class="vh360-preview-error">
                            Failed to load preview. Please try again.
                        </div>
                    `);
                }
            });
        },
        
        /**
         * Initialize export/import functionality
         */
        initExportImport: function() {
            const self = this;
            
            // Export configuration
            $('#vh360-export-config').on('click', function(e) {
                e.preventDefault();
                self.exportConfiguration();
            });
            
            // Import configuration
            $('#vh360-import-config').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    self.importConfiguration(file);
                }
            });
            
            // Trigger file input
            $('#vh360-import-btn').on('click', function(e) {
                e.preventDefault();
                $('#vh360-import-config').click();
            });
        },
        
        /**
         * Export configuration
         */
        exportConfiguration: function() {
            const config = {
                version: '1.0',
                timestamp: new Date().toISOString(),
                shortcodeType: this.state.currentShortcodeType,
                heroParams: {},
                videosParams: {}
            };
            
            // Collect hero parameters
            $('#vh360-params-hero input, #vh360-params-hero select').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                config.heroParams[name] = $field.val();
            });
            
            // Collect videos parameters
            $('#vh360-params-videos input, #vh360-params-videos select').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                config.videosParams[name] = $field.val();
            });
            
            // Create download
            const blob = new Blob([JSON.stringify(config, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'vh360-shortcode-config-' + Date.now() + '.json';
            a.click();
            URL.revokeObjectURL(url);
            
            this.showToast('Configuration exported successfully!', 'success');
        },
        
        /**
         * Import configuration
         */
        importConfiguration: function(file) {
            const self = this;
            const reader = new FileReader();
            
            reader.onload = function(e) {
                try {
                    const config = JSON.parse(e.target.result);
                    
                    // Validate configuration
                    if (!config.version || !config.heroParams || !config.videosParams) {
                        throw new Error('Invalid configuration file');
                    }
                    
                    // Apply configuration
                    if (config.shortcodeType) {
                        $('#vh360-shortcode-type').val(config.shortcodeType).trigger('change');
                    }
                    
                    // Apply hero parameters
                    Object.keys(config.heroParams).forEach(name => {
                        $(`#vh360-params-hero [name="${name}"]`).val(config.heroParams[name]);
                    });
                    
                    // Apply videos parameters
                    Object.keys(config.videosParams).forEach(name => {
                        $(`#vh360-params-videos [name="${name}"]`).val(config.videosParams[name]);
                    });
                    
                    self.showToast('Configuration imported successfully!', 'success');
                } catch(err) {
                    console.error('Import error:', err);
                    self.showToast('Failed to import configuration: ' + err.message, 'error');
                }
            };
            
            reader.readAsText(file);
        },
        
        /**
         * Initialize smooth scroll
         */
        initSmoothScroll: function() {
            $('a[href^="#"]').on('click', function(e) {
                const target = $(this).attr('href');
                
                if (target.length > 1) {
                    const $target = $(target);
                    
                    if ($target.length) {
                        e.preventDefault();
                        $('html, body').animate({
                            scrollTop: $target.offset().top - 100
                        }, 500);
                    }
                }
            });
        },
        
        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            $('.vh360-tooltip-trigger').on('mouseenter', function() {
                const $trigger = $(this);
                const text = $trigger.data('tooltip');
                
                const $tooltip = $('<div class="vh360-tooltip"></div>').text(text);
                $('body').append($tooltip);
                
                const offset = $trigger.offset();
                $tooltip.css({
                    top: offset.top - $tooltip.outerHeight() - 10,
                    left: offset.left + ($trigger.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                }).fadeIn(200);
                
                $trigger.data('tooltip-element', $tooltip);
            }).on('mouseleave', function() {
                const $trigger = $(this);
                const $tooltip = $trigger.data('tooltip-element');
                
                if ($tooltip) {
                    $tooltip.fadeOut(200, function() {
                        $tooltip.remove();
                    });
                }
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.vh360-shortcodes-page').length) {
            VH360Shortcodes.init();
            
            // Clear copy history button
            $('#vh360-clear-history').on('click', function(e) {
                e.preventDefault();
                VH360Shortcodes.clearCopyHistory();
            });
        }
    });
    
})(jQuery);
