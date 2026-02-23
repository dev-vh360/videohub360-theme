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
                console.error('vh360BusinessBooking object not found');
                return;
            }
            
            this.bindEvents();
            
            // Auto-load slots for today's date on page load
            const today = new Date().toISOString().split('T')[0];
            $('#vh360-booking-date-picker').val(today);
            this.loadSlots(today);
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
                    
                    // Debug logging
                    console.log('Slots AJAX response:', response);
                    
                    if (response.success && response.data.slots) {
                        console.log('Found ' + response.data.slots.length + ' slots for date ' + date);
                        VH360BusinessBooking.renderSlots(response.data.slots, date);
                    } else {
                        console.log('No slots found or error:', response.data);
                        $container.html('<p class="vh360-booking-no-slots">' + (vh360BusinessBooking.i18n.noSlots || 'No slots available for this date.') + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    $loading.hide();
                    console.error('Error loading slots:', error);
                    console.error('XHR response:', xhr.responseText);
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
            
            // Group slots by date
            const slotsByDate = {};
            slots.forEach(function(slot) {
                if (!slotsByDate[slot.date]) {
                    slotsByDate[slot.date] = [];
                }
                slotsByDate[slot.date].push(slot);
            });
            
            let html = '';
            
            // Render slots for each date
            Object.keys(slotsByDate).sort().forEach(function(date) {
                const dateSlots = slotsByDate[date];
                const dateObj = new Date(date + 'T00:00:00');
                const formattedDate = dateObj.toLocaleDateString(undefined, { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
                
                html += '<div class="vh360-booking-date-group">';
                html += '<h3 class="vh360-booking-date-header">' + formattedDate + '</h3>';
                html += '<div class="vh360-booking-slots-grid">';
                
                dateSlots.forEach(function(slot) {
                    html += '<div class="vh360-booking-slot">';
                    html += '<div class="vh360-booking-slot-time">' + slot.start + ' - ' + slot.end + '</div>';
                    
                    if (vh360BusinessBooking.isLoggedIn) {
                        html += '<button class="vh360-book-slot-btn" data-datetime="' + slot.datetime + '" data-date="' + slot.date + '" data-start="' + slot.start + '" data-end="' + slot.end + '">';
                        html += (vh360BusinessBooking.i18n.bookButton || 'Book');
                        html += '</button>';
                    } else {
                        html += '<a href="' + vh360BusinessBooking.loginUrl + '" class="vh360-book-slot-login">';
                        html += (vh360BusinessBooking.i18n.loginToBook || 'Login to Book');
                        html += '</a>';
                    }
                    
                    html += '</div>';
                });
                
                html += '</div>';
                html += '</div>';
            });
            
            $container.html(html);
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
                    console.log('Booking response:', response);
                    
                    if (response.success) {
                        // Show success message
                        VH360BusinessBooking.showMessage('success', response.data.message || vh360BusinessBooking.i18n.bookingSuccess);
                        
                        // Update button to show "Booked" state immediately
                        $btn.text(vh360BusinessBooking.i18n.booked || 'Booked').addClass('vh360-slot-booked');
                        
                        // Remove the slot from UI after a moment
                        setTimeout(function() {
                            $btn.closest('.vh360-booking-slot').fadeOut(function() {
                                $(this).remove();
                                
                                // Check if any slots remain
                                const $slotsContainer = $('#vh360-booking-slots');
                                if ($slotsContainer.find('.vh360-booking-slot').length === 0) {
                                    $slotsContainer.html('<p>' + (vh360BusinessBooking.i18n.noSlots || 'No appointment slots available for this date.') + '</p>');
                                }
                            });
                        }, 1500);
                        
                        // Optionally redirect to appointment details
                        if (response.data.event_url) {
                            setTimeout(function() {
                                window.location.href = response.data.event_url;
                            }, 2500);
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
                    console.error('Booking error:', error);
                    console.error('XHR response:', xhr.responseText);
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
