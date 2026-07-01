/**
 * Bulletin Dashboard JavaScript
 *
 * Handles CRUD operations for bulletins in the dashboard.
 *
 * @package Videohub360_Theme
 * @since 1.3.0
 */

(function($) {
    'use strict';

    /**
     * Bulletin Dashboard handler
     */
    const VH360BulletinDashboard = {
        
        /**
         * Current bulletin ID being edited
         */
        currentBulletinId: null,

        /**
         * Initialized flag
         */
        initialized: false,

        /**
         * Search timeout
         */
        searchTimeout: null,

        /**
         * Initialize
         */
        init: function() {
            if (this.initialized) {
                return;
            }
            this.initialized = true;
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Create bulletin button
            $(document).on('click.vh360bulletins', '.vh360-create-bulletin-btn', this.openCreateModal.bind(this));
            
            // Edit bulletin button
            $(document).on('click.vh360bulletins', '.vh360-edit-bulletin-btn', this.openEditModal.bind(this));
            
            // Delete bulletin button
            $(document).on('click.vh360bulletins', '.vh360-delete-bulletin-btn', this.confirmDelete.bind(this));
            
            // Form submission
            $('#vh360-bulletin-form').on('submit.vh360bulletins', this.saveBulletin.bind(this));
            
            // Modal close
            $(document).on('click.vh360bulletins', '#vh360-bulletin-editor-modal .vh360-modal-close', this.closeModal.bind(this));
            $(document).on('click.vh360bulletins', '#vh360-bulletin-editor-modal', function(e) {
                if ($(e.target).hasClass('vh360-modal-overlay')) {
                    VH360BulletinDashboard.closeModal();
                }
            });
            
            // Featured image upload
            $(document).on('click.vh360bulletins', '#vh360-bulletin-upload-trigger', function(e) {
                e.preventDefault();
                $('#vh360-bulletin-featured-image').val('').trigger('click');
            });
            
            $(document).on('change.vh360bulletins', '#vh360-bulletin-featured-image', this.handleImageSelect.bind(this));
            
            $(document).on('click.vh360bulletins', '#vh360-bulletin-remove-image', function(e) {
                e.preventDefault();
                VH360BulletinDashboard.removeImage();
            });
            
            // Audience field handler - show/hide target field
            $(document).on('change.vh360bulletins', '#vh360-bulletin-audience', function() {
                var audience = $(this).val();
                var $targetWrapper = $('#vh360-bulletin-target-wrapper');
                var $targetHelp = $('#vh360-bulletin-target-help');
                
                if (audience === 'site_wide') {
                    $targetWrapper.hide();
                    $('#vh360-bulletin-target').val('');
                } else {
                    $targetWrapper.show();
                    if (audience === 'role') {
                        $targetHelp.text('Enter role slug (e.g., subscriber, editor, author)');
                        $('#vh360-bulletin-target').attr('placeholder', 'e.g., subscriber');
                    } else if (audience === 'user') {
                        $targetHelp.text('Enter numeric user ID');
                        $('#vh360-bulletin-target').attr('placeholder', 'e.g., 123');
                    }
                }
                
                // Update banner checkbox state
                VH360BulletinDashboard.updateBannerCheckbox();
            });
            
            // Priority field handler - update banner checkbox state
            $(document).on('change.vh360bulletins', '#vh360-bulletin-priority', function() {
                VH360BulletinDashboard.updateBannerCheckbox();
            });
        },

        /**
         * Open create bulletin modal
         */
        openCreateModal: function(e) {
            e.preventDefault();
            
            this.currentBulletinId = null;
            
            // Reset form
            $('#vh360-bulletin-form')[0].reset();
            $('#vh360-bulletin-id').val('');
            
            // Reset image
            this.removeImage();
            
            // Update modal title
            $('#vh360-bulletin-modal-title').text(vh360BulletinDashboard.i18n.createBulletin || 'Create Bulletin');
            $('#vh360-bulletin-submit-btn .vh360-btn-text').text(vh360BulletinDashboard.i18n.createBulletin || 'Create Bulletin');
            
            // Show modal
            $('#vh360-bulletin-editor-modal').css('display', 'flex').addClass('show');
            $('body').addClass('vh360-modal-open');
        },

        /**
         * Open edit bulletin modal
         */
        openEditModal: function(e) {
            e.preventDefault();
            
            const bulletinId = $(e.currentTarget).data('bulletin-id');
            this.currentBulletinId = bulletinId;
            
            // Show loading
            this.showLoading();
            
            // Load bulletin data
            $.ajax({
                url: vh360BulletinDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_get_bulletin',
                    nonce: vh360BulletinDashboard.nonce,
                    bulletin_id: bulletinId
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const data = response.data;
                        
                        // Populate form
                        $('#vh360-bulletin-id').val(bulletinId);
                        $('#vh360-bulletin-title').val(data.title || '');
                        $('#vh360-bulletin-content').val(data.content || '');
                        $('#vh360-bulletin-excerpt').val(data.excerpt || '');
                        $('#vh360-bulletin-priority').val(data.priority || 'normal');
                        $('#vh360-bulletin-type').val(data.type || 'info');
                        $('#vh360-bulletin-audience').val(data.audience_type || 'site_wide').trigger('change');
                        $('#vh360-bulletin-target').val(data.audience_target || '');
                        $('#vh360-bulletin-expiry-date').val(data.expiry_date || '');
                        $('#vh360-bulletin-dismissible').prop('checked', data.dismissible === '1');
                        
                        // Set banner checkbox if it exists
                        if ($('#vh360-bulletin-show-banner').length) {
                            $('#vh360-bulletin-show-banner').prop('checked', data.show_banner === '1');
                        }
                        
                        // Handle featured image
                        if (data.featured_image_id && data.featured_image_url) {
                            $('#vh360-bulletin-featured-image-id').val(data.featured_image_id);
                            $('#vh360-bulletin-preview-img').attr('src', data.featured_image_url);
                            $('#vh360-bulletin-image-preview').show();
                            $('#vh360-bulletin-upload-trigger').hide();
                        } else {
                            VH360BulletinDashboard.removeImage();
                        }
                        
                        // Update banner checkbox state
                        VH360BulletinDashboard.updateBannerCheckbox();
                        
                        // Update modal title
                        $('#vh360-bulletin-modal-title').text(vh360BulletinDashboard.i18n.editBulletin || 'Edit Bulletin');
                        $('#vh360-bulletin-submit-btn .vh360-btn-text').text(vh360BulletinDashboard.i18n.updateBulletin || 'Update Bulletin');
                        
                        // Show modal
                        $('#vh360-bulletin-editor-modal').css('display', 'flex').addClass('show');
                        $('body').addClass('vh360-modal-open');
                    } else {
                        VH360BulletinDashboard.showNotice('error', response.data?.message || vh360BulletinDashboard.i18n.loadError);
                    }
                },
                error: function() {
                    VH360BulletinDashboard.showNotice('error', vh360BulletinDashboard.i18n.loadError);
                },
                complete: function() {
                    VH360BulletinDashboard.hideLoading();
                }
            });
        },

        /**
         * Close modal
         */
        closeModal: function() {
            $('#vh360-bulletin-editor-modal').removeClass('show').fadeOut(200);
            $('body').removeClass('vh360-modal-open');
            
            // Reset form after animation
            setTimeout(function() {
                $('#vh360-bulletin-form')[0].reset();
                VH360BulletinDashboard.removeImage();
                VH360BulletinDashboard.currentBulletinId = null;
            }, 200);
        },

        /**
         * Handle image selection
         */
        handleImageSelect: function(e) {
            const file = e.target.files[0];
            
            if (!file) {
                return;
            }
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                this.showNotice('error', vh360BulletinDashboard.i18n.invalidFileType);
                e.target.value = '';
                return;
            }
            
            // Validate file size (5MB)
            const maxSize = 5 * 1024 * 1024;
            if (file.size > maxSize) {
                this.showNotice('error', vh360BulletinDashboard.i18n.fileTooLarge);
                e.target.value = '';
                return;
            }
            
            // Show instant preview using FileReader
            const reader = new FileReader();
            reader.onload = function(event) {
                $('#vh360-bulletin-preview-img').attr('src', event.target.result);
                $('#vh360-bulletin-image-preview').show();
                $('#vh360-bulletin-upload-trigger').hide();
            };
            reader.readAsDataURL(file);
            
            // Upload image via AJAX
            const formData = new FormData();
            formData.append('action', 'vh360_upload_bulletin_image');
            formData.append('nonce', vh360BulletinDashboard.nonce);
            formData.append('image', file);
            
            $.ajax({
                url: vh360BulletinDashboard.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success && response.data) {
                        $('#vh360-bulletin-featured-image-id').val(response.data.attachment_id);
                        $('#vh360-bulletin-preview-img').attr('src', response.data.attachment_url);
                    } else {
                        VH360BulletinDashboard.showNotice('error', response.data?.message || vh360BulletinDashboard.i18n.uploadError);
                        VH360BulletinDashboard.removeImage();
                    }
                },
                error: function() {
                    VH360BulletinDashboard.showNotice('error', vh360BulletinDashboard.i18n.uploadError);
                    VH360BulletinDashboard.removeImage();
                }
            });
        },

        /**
         * Remove image
         */
        removeImage: function() {
            $('#vh360-bulletin-featured-image').val('');
            $('#vh360-bulletin-featured-image-id').val('');
            $('#vh360-bulletin-preview-img').attr('src', '');
            $('#vh360-bulletin-image-preview').hide();
            $('#vh360-bulletin-upload-trigger').show();
        },

        /**
         * Update banner checkbox enabled/disabled state
         * Banner only available for urgent + site_wide bulletins
         */
        updateBannerCheckbox: function() {
            var $checkbox = $('#vh360-bulletin-show-banner');
            if ($checkbox.length === 0) {
                return; // User doesn't have banner permission
            }
            
            var priority = $('#vh360-bulletin-priority').val();
            var audience = $('#vh360-bulletin-audience').val();
            
            // Banner only available for urgent + site_wide
            if (priority === 'urgent' && audience === 'site_wide') {
                $checkbox.prop('disabled', false);
            } else {
                $checkbox.prop('disabled', true).prop('checked', false);
            }
        },

        /**
         * Save bulletin
         */
        saveBulletin: function(e) {
            e.preventDefault();
            
            // Validate form
            const title = $('#vh360-bulletin-title').val().trim();
            if (!title) {
                this.showNotice('error', vh360BulletinDashboard.i18n.titleRequired || 'Please enter a title');
                return;
            }
            
            // Disable submit button
            const $submitBtn = $('#vh360-bulletin-submit-btn');
            $submitBtn.prop('disabled', true);
            $submitBtn.find('.vh360-btn-text').hide();
            $submitBtn.find('.vh360-btn-loading').show();
            
            // Collect form data
            const formData = {
                action: 'vh360_save_bulletin',
                nonce: vh360BulletinDashboard.nonce,
                bulletin_id: $('#vh360-bulletin-id').val(),
                title: title,
                content: $('#vh360-bulletin-content').val(),
                excerpt: $('#vh360-bulletin-excerpt').val(),
                featured_image_id: $('#vh360-bulletin-featured-image-id').val(),
                priority: $('#vh360-bulletin-priority').val(),
                type: $('#vh360-bulletin-type').val(),
                audience_type: $('#vh360-bulletin-audience').val(),
                audience_target: $('#vh360-bulletin-target').val(),
                expiry_date: $('#vh360-bulletin-expiry-date').val(),
                dismissible: $('#vh360-bulletin-dismissible').is(':checked') ? '1' : '0',
                show_banner: $('#vh360-bulletin-show-banner').is(':checked') ? '1' : '0'
            };
            
            // Submit via AJAX
            $.ajax({
                url: vh360BulletinDashboard.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        VH360BulletinDashboard.showNotice('success', response.data?.message || vh360BulletinDashboard.i18n.success);
                        VH360BulletinDashboard.closeModal();
                        
                        // Reload page to show updated bulletin (avoiding complex DOM manipulation for MVP)
                        // TODO: Consider dynamically updating the DOM in future iterations
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        VH360BulletinDashboard.showNotice('error', response.data?.message || vh360BulletinDashboard.i18n.saveError);
                    }
                },
                error: function() {
                    VH360BulletinDashboard.showNotice('error', vh360BulletinDashboard.i18n.saveError);
                },
                complete: function() {
                    // Re-enable submit button
                    $submitBtn.prop('disabled', false);
                    $submitBtn.find('.vh360-btn-text').show();
                    $submitBtn.find('.vh360-btn-loading').hide();
                }
            });
        },

        /**
         * Confirm delete bulletin
         */
        confirmDelete: function(e) {
            e.preventDefault();
            
            const bulletinId = $(e.currentTarget).data('bulletin-id');
            
            if (confirm(vh360BulletinDashboard.i18n.confirmDelete)) {
                this.deleteBulletin(bulletinId);
            }
        },

        /**
         * Delete bulletin
         */
        deleteBulletin: function(bulletinId) {
            this.showLoading();
            
            $.ajax({
                url: vh360BulletinDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_delete_bulletin',
                    nonce: vh360BulletinDashboard.nonce,
                    bulletin_id: bulletinId
                },
                success: function(response) {
                    if (response.success) {
                        VH360BulletinDashboard.showNotice('success', response.data?.message || vh360BulletinDashboard.i18n.deleteSuccess || 'Bulletin deleted successfully');
                        
                        // Remove card from DOM
                        $('.vh360-dashboard-bulletin-card[data-bulletin-id="' + bulletinId + '"]').fadeOut(300, function() {
                            $(this).remove();
                            
                            // Show empty state if no bulletins left
                            if ($('.vh360-dashboard-bulletin-card').length === 0) {
                                window.location.reload();
                            }
                        });
                    } else {
                        VH360BulletinDashboard.showNotice('error', response.data?.message || vh360BulletinDashboard.i18n.deleteError);
                    }
                },
                error: function() {
                    VH360BulletinDashboard.showNotice('error', vh360BulletinDashboard.i18n.deleteError);
                },
                complete: function() {
                    VH360BulletinDashboard.hideLoading();
                }
            });
        },

        /**
         * Show notice/toast message
         */
        showNotice: function(type, message) {
            // Remove existing notices
            $('.vh360-toast-notice').remove();
            
            const iconSvg = type === 'success' 
                ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>'
                : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
            
            const $notice = $('<div class="vh360-toast-notice vh360-toast-' + type + '">' +
                iconSvg +
                '<span>' + message + '</span>' +
                '</div>');
            
            $('body').append($notice);
            
            // Trigger animation
            setTimeout(function() {
                $notice.addClass('show');
            }, 10);
            
            // Auto-hide after 4 seconds
            setTimeout(function() {
                $notice.removeClass('show');
                setTimeout(function() {
                    $notice.remove();
                }, 300);
            }, 4000);
        },

        /**
         * Show loading overlay
         */
        showLoading: function() {
            if ($('.vh360-loading-overlay').length === 0) {
                $('body').append('<div class="vh360-loading-overlay"><div class="vh360-loading-spinner"></div></div>');
            }
        },

        /**
         * Hide loading overlay
         */
        hideLoading: function() {
            $('.vh360-loading-overlay').remove();
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        VH360BulletinDashboard.init();
    });

})(jQuery);
