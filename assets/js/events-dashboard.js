/**
 * Events Dashboard JavaScript
 *
 * Handles CRUD operations for events in the dashboard.
 *
 * @package Videohub360_Theme
 * @since 1.3.0
 */

(function($) {
    'use strict';

    /**
     * Events Dashboard handler
     */
    const VH360EventsDashboard = {
        
        /**
         * Current event ID being edited
         */
        currentEventId: null,

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Create event button - use namespaced events
            $(document).on('click.vh360events', '.vh360-create-event-btn', this.showCreateModal.bind(this));
            
            // Edit event button
            $(document).on('click.vh360events', '.vh360-edit-event-btn', this.showEditModal.bind(this));
            
            // Delete event button
            $(document).on('click.vh360events', '.vh360-delete-event-btn', this.deleteEvent.bind(this));
            
            // View RSVPs button
            $(document).on('click.vh360events', '.vh360-view-rsvps-btn', this.showRsvpModal.bind(this));
            
            // Form submission
            $('#vh360-event-form').on('submit.vh360events', this.handleSubmit.bind(this));
            
            // Modal close - specific to event modals only
            $(document).on('click.vh360events', '#vh360-event-editor-modal .vh360-modal-close, #vh360-rsvp-list-modal .vh360-modal-close', this.closeModal);
            $(document).on('click.vh360events', '#vh360-event-editor-modal, #vh360-rsvp-list-modal', function(e) {
                if ($(e.target).hasClass('vh360-modal-overlay')) {
                    VH360EventsDashboard.closeModal();
                }
            });
            
            // Location type toggle
            $('input[name="location_type"]').on('change.vh360events', this.toggleLocationFields);
            
            // Cost type toggle
            $('#vh360-event-cost-type').on('change.vh360events', this.toggleCostAmount);
            
            // Featured image upload
            $(document).on('click.vh360events', '#vh360-event-upload-trigger', function(e) {
                e.preventDefault();
                $('#vh360-event-featured-image').click();
            });
            
            $(document).on('change.vh360events', '#vh360-event-featured-image', this.handleImageSelect.bind(this));
            
            $(document).on('click.vh360events', '#vh360-event-remove-image', function(e) {
                e.preventDefault();
                VH360EventsDashboard.removeImage();
            });
        },

        /**
         * Show create event modal
         */
        showCreateModal: function(e) {
            e.preventDefault();
            
            this.currentEventId = null;
            
            // Reset form
            $('#vh360-event-form')[0].reset();
            $('#vh360-event-id').val('');
            
            // Reset image
            this.removeImage();
            
            // Update modal title
            $('#vh360-event-modal-title').text(vh360EventsDashboard.i18n.createEvent);
            $('#vh360-event-submit-btn .vh360-btn-text').text(vh360EventsDashboard.i18n.createEvent);
            
            // Show modal with correct approach
            $('#vh360-event-editor-modal').css('display', 'flex').addClass('show');
            $('body').addClass('vh360-modal-open');
        },

        /**
         * Show edit event modal
         */
        showEditModal: function(e) {
            e.preventDefault();
            
            const eventId = $(e.currentTarget).data('event-id');
            this.currentEventId = eventId;
            
            // Show modal with correct approach
            $('#vh360-event-editor-modal').css('display', 'flex').addClass('show');
            $('body').addClass('vh360-modal-open');
            
            // Load event data
            $.ajax({
                url: vh360EventsDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_get_event',
                    nonce: vh360EventsDashboard.nonce,
                    event_id: eventId
                },
                success: function(response) {
                    if (response.success) {
                        VH360EventsDashboard.populateForm(response.data);
                        $('#vh360-event-modal-title').text(vh360EventsDashboard.i18n.editEvent);
                        $('#vh360-event-submit-btn .vh360-btn-text').text(vh360EventsDashboard.i18n.updateEvent);
                    } else {
                        VH360EventsDashboard.showNotice('error', response.data.message || vh360EventsDashboard.i18n.error);
                        VH360EventsDashboard.closeModal();
                    }
                },
                error: function() {
                    VH360EventsDashboard.showNotice('error', vh360EventsDashboard.i18n.error);
                    VH360EventsDashboard.closeModal();
                }
            });
        },

        /**
         * Populate form with event data
         */
        populateForm: function(data) {
            $('#vh360-event-id').val(data.id);
            $('#vh360-event-title').val(data.title);
            $('#vh360-event-description').val(data.content);
            $('#vh360-event-excerpt').val(data.excerpt);
            $('#vh360-event-start-date').val(data.start_date);
            $('#vh360-event-start-time').val(data.start_time);
            $('#vh360-event-end-date').val(data.end_date);
            $('#vh360-event-end-time').val(data.end_time);
            
            // Location type
            $('input[name="location_type"][value="' + (data.location_type || 'physical') + '"]').prop('checked', true).trigger('change');
            
            $('#vh360-event-venue-name').val(data.venue_name);
            $('#vh360-event-venue-address').val(data.venue_address);
            $('#vh360-event-venue-city').val(data.venue_city);
            $('#vh360-event-venue-state').val(data.venue_state);
            $('#vh360-event-online-url').val(data.online_url);
            
            // Registration & Cost
            $('input[name="registration_required"]').prop('checked', data.registration_required == '1');
            $('#vh360-event-cost-type').val(data.cost_type || 'free').trigger('change');
            $('#vh360-event-cost-amount').val(data.cost_amount);
            
            // Status
            $('#vh360-event-event-status').val(data.event_status || 'scheduled');
            $('#vh360-event-post-status').val(data.status || 'draft');
            
            // Featured image
            if (data.featured_image_id && data.featured_image_url) {
                $('#vh360-event-featured-image-id').val(data.featured_image_id);
                $('#vh360-event-preview-img').attr('src', data.featured_image_url);
                $('#vh360-event-image-preview').show();
            } else {
                this.removeImage();
            }
        },

        /**
         * Handle form submission
         */
        handleSubmit: function(e) {
            e.preventDefault();
            
            const $form = $('#vh360-event-form');
            const $submitBtn = $('#vh360-event-submit-btn');
            const formData = new FormData($form[0]);
            
            // Determine action
            const action = this.currentEventId ? 'vh360_update_event' : 'vh360_create_event';
            formData.append('action', action);
            formData.append('nonce', vh360EventsDashboard.nonce);
            
            if (this.currentEventId) {
                formData.append('event_id', this.currentEventId);
            }
            
            // Show loading
            $submitBtn.prop('disabled', true);
            $submitBtn.find('.vh360-btn-text').hide();
            $submitBtn.find('.vh360-btn-loading').show();
            
            $.ajax({
                url: vh360EventsDashboard.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        VH360EventsDashboard.showNotice('success', response.data.message);
                        
                        // Close modal
                        VH360EventsDashboard.closeModal();
                        
                        // Reload page to show updated event
                        setTimeout(function() {
                            window.location.reload();
                        }, 500);
                    } else {
                        VH360EventsDashboard.showNotice('error', response.data.message || vh360EventsDashboard.i18n.error);
                    }
                },
                error: function() {
                    VH360EventsDashboard.showNotice('error', vh360EventsDashboard.i18n.error);
                },
                complete: function() {
                    $submitBtn.prop('disabled', false);
                    $submitBtn.find('.vh360-btn-text').show();
                    $submitBtn.find('.vh360-btn-loading').hide();
                }
            });
        },

        /**
         * Delete event
         */
        deleteEvent: function(e) {
            e.preventDefault();
            
            if (!confirm(vh360EventsDashboard.i18n.confirmDelete)) {
                return;
            }
            
            const eventId = $(e.currentTarget).data('event-id');
            const $card = $(e.currentTarget).closest('.vh360-dashboard-event-card');
            
            $.ajax({
                url: vh360EventsDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_delete_event',
                    nonce: vh360EventsDashboard.nonce,
                    event_id: eventId
                },
                success: function(response) {
                    if (response.success) {
                        // Fade out and remove card
                        $card.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if no events left
                            if ($('.vh360-dashboard-event-card').length === 0) {
                                window.location.reload();
                            }
                        });
                        
                        VH360EventsDashboard.showNotice('success', response.data.message);
                    } else {
                        VH360EventsDashboard.showNotice('error', response.data.message || vh360EventsDashboard.i18n.error);
                    }
                },
                error: function() {
                    VH360EventsDashboard.showNotice('error', vh360EventsDashboard.i18n.error);
                }
            });
        },

        /**
         * Show RSVP list modal
         */
        showRsvpModal: function(e) {
            e.preventDefault();
            
            const eventId = $(e.currentTarget).data('event-id');
            
            // Show modal with correct approach
            $('#vh360-rsvp-list-modal').css('display', 'flex').addClass('show');
            $('body').addClass('vh360-modal-open');
            
            // Show loading
            $('#vh360-rsvp-list-content').html('<div class="vh360-loading"><svg class="vh360-spinner" width="32" height="32" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="4" fill="none" stroke-linecap="round"></path></svg></div>');
            
            // Load RSVPs
            $.ajax({
                url: vh360EventsDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_get_event_rsvps',
                    nonce: vh360EventsDashboard.nonce,
                    event_id: eventId
                },
                success: function(response) {
                    if (response.success) {
                        $('#vh360-rsvp-list-content').html(response.data.html);
                    } else {
                        $('#vh360-rsvp-list-content').html('<p>' + (response.data.message || vh360EventsDashboard.i18n.error) + '</p>');
                    }
                },
                error: function() {
                    $('#vh360-rsvp-list-content').html('<p>' + vh360EventsDashboard.i18n.error + '</p>');
                }
            });
        },

        /**
         * Close modal - specific to event modals only
         */
        closeModal: function() {
            // Only close event-related modals, not gallery or other modals
            var $eventModals = $('#vh360-event-editor-modal, #vh360-rsvp-list-modal');
            $eventModals.removeClass('show');
            setTimeout(function() {
                $eventModals.css('display', 'none');
            }, 300);
            
            // Only remove modal-open class if no event modals are visible
            // This allows other modals (like gallery) to keep the body class if they're open
            if ($('#vh360-event-editor-modal.show, #vh360-rsvp-list-modal.show').length === 0) {
                // Check if there are any other modals still open before removing the class
                var otherModalsOpen = $('.vh360-modal-overlay.show').not('#vh360-event-editor-modal, #vh360-rsvp-list-modal').length > 0;
                if (!otherModalsOpen) {
                    $('body').removeClass('vh360-modal-open');
                }
            }
        },

        /**
         * Toggle location fields based on type
         */
        toggleLocationFields: function() {
            const locationType = $('input[name="location_type"]:checked').val();
            
            $('#vh360-physical-location').hide();
            $('#vh360-online-location').hide();
            
            if (locationType === 'physical') {
                $('#vh360-physical-location').show();
            } else if (locationType === 'online') {
                $('#vh360-online-location').show();
            } else if (locationType === 'both') {
                $('#vh360-physical-location').show();
                $('#vh360-online-location').show();
            }
        },

        /**
         * Toggle cost amount field
         */
        toggleCostAmount: function() {
            const costType = $('#vh360-event-cost-type').val();
            
            if (costType === 'free') {
                $('#vh360-cost-amount-group').hide();
            } else {
                $('#vh360-cost-amount-group').show();
            }
        },

        /**
         * Handle featured image selection
         */
        handleImageSelect: function(e) {
            const file = e.target.files[0];
            
            if (!file) {
                return;
            }
            
            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                this.showNotice('error', vh360EventsDashboard.i18n.invalidFileType || 'Invalid file type. Please upload JPG, PNG, GIF, or WebP.');
                $('#vh360-event-featured-image').val('');
                return;
            }
            
            // Validate file size (5MB max)
            const maxSize = 5 * 1024 * 1024;
            if (file.size > maxSize) {
                this.showNotice('error', vh360EventsDashboard.i18n.fileTooLarge || 'File size too large. Maximum 5MB allowed.');
                $('#vh360-event-featured-image').val('');
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#vh360-event-preview-img').attr('src', e.target.result);
                $('#vh360-event-image-preview').show();
            };
            reader.readAsDataURL(file);
            
            // Upload image via AJAX
            const formData = new FormData();
            formData.append('action', 'vh360_upload_event_image');
            formData.append('nonce', vh360EventsDashboard.nonce);
            formData.append('image', file);
            
            $.ajax({
                url: vh360EventsDashboard.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $('#vh360-event-featured-image-id').val(response.data.attachment_id);
                    } else {
                        VH360EventsDashboard.showNotice('error', response.data.message || vh360EventsDashboard.i18n.error);
                        VH360EventsDashboard.removeImage();
                    }
                },
                error: function() {
                    VH360EventsDashboard.showNotice('error', vh360EventsDashboard.i18n.error);
                    VH360EventsDashboard.removeImage();
                }
            });
        },

        /**
         * Remove featured image
         */
        removeImage: function() {
            $('#vh360-event-featured-image').val('');
            $('#vh360-event-featured-image-id').val('');
            $('#vh360-event-preview-img').attr('src', '');
            $('#vh360-event-image-preview').hide();
        },

        /**
         * Show notice message
         */
        showNotice: function(type, message) {
            // Create notice element
            const $notice = $('<div class="vh360-dashboard-notice vh360-dashboard-notice-' + type + ' vh360-notice-fade-in">' + 
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                (type === 'success' ? 
                    '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>' :
                    '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>') +
                '</svg>' + message + '</div>');
            
            // Append to dashboard
            $('.vh360-dashboard-events').prepend($notice);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        if (typeof vh360EventsDashboard !== 'undefined') {
            VH360EventsDashboard.init();
        }
    });

})(jQuery);
