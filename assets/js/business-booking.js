/**
 * Business Booking JavaScript
 *
 * Handles dynamic appointment slot fetching and booking for Business profiles.
 *
 * @package Videohub360_Theme
 * @since 1.5.0
 */

(function($) {
    'use strict';
    
    const VH360BusinessBooking = {
        
        /**
         * Initialize
         */
        init: function() {
            if (typeof vh360BusinessBooking === 'undefined') {
                return;
            }
            
            this.bindEvents();
            this.initCollapsible();
            
            // Auto-load slots for today's date when the booking panel is present.
            const today = new Date().toISOString().split('T')[0];
            const $datePicker = $('#vh360-booking-date-picker');

            if ($datePicker.length) {
                $datePicker.val(today);
                this.loadSlots(today);
            }
        },
        
        /**
         * Initialize collapsible booking section
         */
        initCollapsible: function() {
            const $toggle = $('#vh360-booking-toggle');
            const $content = $('#vh360-booking-content');
            
            if (!$toggle.length || !$content.length) {
                return;
            }

            if ($toggle.length && $content.length) {
                // Set initial state
                $content.addClass('vh360-booking-collapsed').hide();
                
                // Toggle click handler
                $toggle.on('click', function() {
                    const isExpanded = $toggle.attr('aria-expanded') === 'true';
                    
                    if (isExpanded) {
                        // Collapse
                        $toggle.attr('aria-expanded', 'false');
                        $content.slideUp(300, function() {
                            $content.addClass('vh360-booking-collapsed').removeClass('vh360-booking-expanded');
                        });
                    } else {
                        // Expand
                        $toggle.attr('aria-expanded', 'true');
                        $content.removeClass('vh360-booking-collapsed').addClass('vh360-booking-expanded').slideDown(300);
                        
                        // Load slots if not already loaded
                        const $slotsContainer = $('#vh360-booking-slots-container');
                        if ($slotsContainer.children().length === 0) {
                            const selectedDate = $('#vh360-booking-date-picker').val();
                            if (selectedDate) {
                                VH360BusinessBooking.loadSlots(selectedDate);
                            }
                        }
                    }
                });
                
                // Keyboard accessibility
                $toggle.on('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        $(this).click();
                    }
                });
            }
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Date picker change
            $(document).on('change', '#vh360-booking-date-picker', function() {
                const selectedDate = $(this).val();
                if (selectedDate) {
                    VH360BusinessBooking.loadSlots(selectedDate);
                }
            });
            
            // Book slot button
            $(document).on('click', '.vh360-book-slot-btn', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const slotDatetime = $btn.data('datetime');
                
                if (!slotDatetime) {
                    alert(vh360BusinessBooking.i18n.invalidSlot || 'Invalid slot');
                    return;
                }
                
                VH360BusinessBooking.bookSlot(slotDatetime, $btn);
            });
        },
        
        /**
         * Load available slots for a date
         */
        loadSlots: function(date) {
            const $container = $('#vh360-booking-slots-container');
            const $loading = $('#vh360-booking-loading');
            
            // Show loading state
            $loading.show();
            $container.html('');
            
            $.ajax({
                url: vh360BusinessBooking.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_get_professional_slots',
                    nonce: vh360BusinessBooking.nonce,
                    professional_id: vh360BusinessBooking.professionalId,
                    date: date
                },
                success: function(response) {
                    $loading.hide();
                    
                    if (response.success && response.data.slots) {
                        VH360BusinessBooking.renderSlots(response.data.slots, date);
                    } else {
                        $container.html('<p class="vh360-booking-no-slots">' + (vh360BusinessBooking.i18n.noSlots || 'No slots available for this date.') + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    $loading.hide();
                    $container.html('<p class="vh360-booking-error">' + (vh360BusinessBooking.i18n.loadError || 'Error loading available times.') + '</p>');
                }
            });
        },
        
        /**
         * Render slots in the UI
         */
        renderSlots: function(slots, selectedDate) {
            const $container = $('#vh360-booking-slots-container');

            if (!slots || slots.length === 0) {
                $container.html('<p class="vh360-booking-no-slots">' + (vh360BusinessBooking.i18n.noSlots || 'No slots available for this date.') + '</p>');
                return;
            }

            const visibleSlots = slots.filter(function(slot) {
                return slot.date === selectedDate;
            });

            if (visibleSlots.length === 0) {
                $container.html('<p class="vh360-booking-no-slots">' + (vh360BusinessBooking.i18n.noSlots || 'No slots available for this date.') + '</p>');
                return;
            }

            const dateObj = new Date(selectedDate + 'T00:00:00');
            const formattedDate = dateObj.toLocaleDateString(undefined, {
                weekday: 'long',
                month: 'long',
                day: 'numeric'
            });

            let html = '';

            html += '<div class="vh360-booking-date-group">';
            html += '<h3 class="vh360-booking-date-header">' + this.escapeHtml(formattedDate) + '</h3>';
            html += '<div class="vh360-booking-time-chips">';

            visibleSlots.forEach(function(slot) {
                const slotStart = VH360BusinessBooking.escapeHtml(slot.start);
                const slotEnd = VH360BusinessBooking.escapeHtml(slot.end);
                const slotDatetime = VH360BusinessBooking.escapeHtml(slot.datetime);
                const slotDate = VH360BusinessBooking.escapeHtml(slot.date);

                if (vh360BusinessBooking.isLoggedIn) {
                    html += '<button class="vh360-book-slot-btn vh360-booking-time-chip" data-datetime="' + slotDatetime + '" data-date="' + slotDate + '" data-start="' + slotStart + '" data-end="' + slotEnd + '">';
                    html += slotStart;
                    html += '</button>';
                } else {
                    html += '<a href="' + VH360BusinessBooking.escapeHtml(vh360BusinessBooking.loginUrl) + '" class="vh360-book-slot-login vh360-booking-time-chip">';
                    html += slotStart;
                    html += '</a>';
                }
            });

            html += '</div>';
            html += '</div>';

            $container.html(html);
        },

        /**
         * Escape HTML before inserting dynamic slot values.
         */
        escapeHtml: function(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },
        
        /**
         * Book a slot
         */
        bookSlot: function(slotDatetime, $btn) {
            if (!vh360BusinessBooking.isLoggedIn) {
                window.location.href = vh360BusinessBooking.loginUrl;
                return;
            }
            
            // Disable button
            $btn.prop('disabled', true);
            const originalText = $btn.text();
            $btn.text(vh360BusinessBooking.i18n.booking || 'Booking...');
            
            $.ajax({
                url: vh360BusinessBooking.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_book_appointment_slot',
                    nonce: vh360BusinessBooking.nonce,
                    professional_id: vh360BusinessBooking.professionalId,
                    slot_datetime: slotDatetime,
                    slot_duration: vh360BusinessBooking.slotDuration
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        VH360BusinessBooking.showMessage('success', response.data.message || vh360BusinessBooking.i18n.bookingSuccess);
                        
                        // Update button to show "Booked" state immediately
                        $btn.text(vh360BusinessBooking.i18n.booked || 'Booked').addClass('vh360-slot-booked');
                        
                        // Remove the booked chip/card from UI after a moment
                        setTimeout(function() {
                            const $slotsContainer = $('#vh360-booking-slots-container');
                            const $slotElement = $btn.closest('.vh360-booking-slot').length ? $btn.closest('.vh360-booking-slot') : $btn;

                            $slotElement.fadeOut(function() {
                                $(this).remove();

                                // Check if any slots remain
                                if ($slotsContainer.find('.vh360-booking-time-chip, .vh360-booking-slot').length === 0) {
                                    $slotsContainer.html('<p class="vh360-booking-no-slots">' + (vh360BusinessBooking.i18n.noSlots || 'No appointment slots available for this date.') + '</p>');
                                }
                            });
                        }, 1500);
                        
                        // Show scheduled session confirmation instead of immediate join link
                        if (response.data.live_room_url) {
                            const scheduledMessage = '<div class="vh360-booking-success-info">' +
                                '<p>' + (vh360BusinessBooking.i18n.appointmentBooked || 'Your appointment has been booked successfully!') + '</p>' +
                                '<p>' + (vh360BusinessBooking.i18n.sessionLinkAvailable || 'The session link will be available from your Appointments dashboard.') + '</p>' +
                                '<p>' + (vh360BusinessBooking.i18n.sessionOpensEarly || 'The session will open shortly before the scheduled time.') + '</p>' +
                                '</div>';
                            
                            setTimeout(function() {
                                VH360BusinessBooking.showMessage('success', scheduledMessage);
                            }, 2000);
                        }
                    } else {
                        $btn.prop('disabled', false);
                        $btn.text(originalText);
                        VH360BusinessBooking.showMessage('error', response.data.message || vh360BusinessBooking.i18n.bookingError);
                    }
                },
                error: function(xhr, status, error) {
                    $btn.prop('disabled', false);
                    $btn.text(originalText);
                    VH360BusinessBooking.showMessage('error', vh360BusinessBooking.i18n.bookingError || 'Error booking appointment.');
                }
            });
        },
        
        /**
         * Show a message to the user
         */
        showMessage: function(type, message) {
            const $container = $('#vh360-booking-messages');
            const messageClass = type === 'success' ? 'vh360-booking-message-success' : 'vh360-booking-message-error';
            
            const $message = $('<div class="vh360-booking-message ' + messageClass + '">' + message + '</div>');
            $container.append($message);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        if ($('#vh360-booking-date-picker').length > 0) {
            VH360BusinessBooking.init();
        }
    });
    
})(jQuery);
