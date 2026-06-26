/**
 * Notification System JavaScript
 *
 * Handles AJAX operations, dropdown interactions, and polling for notifications.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    window.VH360Notifications = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bell = $('#vh360-notification-bell');
            this.bellBtn = $('.vh360-notification-bell-btn');
            this.dropdown = $('#vh360-notification-dropdown');
            this.badge = $('.vh360-notification-badge');
            this.notificationList = $('#vh360-notification-list');
            this.markAllReadBtn = $('#vh360-mark-all-read');
            this.pollingTimer = null;
            this.countRequestInFlight = false;
            
            if (!this.bellBtn.length || !this.dropdown.length) {
                return;
            }
            
            this.bindEvents();
            this.startPolling();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;
            
            // Toggle dropdown on bell click
            this.bellBtn.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.toggleDropdown();
            });
            
            // Mark all as read
            this.markAllReadBtn.on('click', function(e) {
                e.preventDefault();
                self.markAllAsRead();
            });
            
            // Mark as read when clicking notification item
            $(document).on('click', '.vh360-notification-item-link', function(e) {
                const notificationId = $(this).closest('.vh360-notification-item').data('notification-id');
                const isUnread = $(this).closest('.vh360-notification-item').hasClass('vh360-notification-item--unread');
                
                if (isUnread && notificationId) {
                    self.markAsRead(notificationId);
                }
            });
            
            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!self.bell.is(e.target) && self.bell.has(e.target).length === 0) {
                    self.closeDropdown();
                }
            });
            
            // Close dropdown on Escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.dropdown.is(':visible')) {
                    self.closeDropdown();
                    self.bellBtn.focus();
                }
            });
            
            // Pause polling while the tab is hidden and resume cleanly when visible.
            $(document).on('visibilitychange', function() {
                if (document.hidden) {
                    self.stopPolling();
                    return;
                }
                
                self.updateCount();
                self.startPolling();
            });
        },
        
        /**
         * Toggle dropdown visibility
         */
        toggleDropdown: function() {
            if (this.dropdown.is(':visible')) {
                this.closeDropdown();
            } else {
                this.openDropdown();
            }
        },
        
        /**
         * Open dropdown
         */
        openDropdown: function() {
            this.dropdown.show();
            this.bellBtn.attr('aria-expanded', 'true');
            this.loadNotifications();
        },
        
        /**
         * Close dropdown
         */
        closeDropdown: function() {
            this.dropdown.hide();
            this.bellBtn.attr('aria-expanded', 'false');
        },
        
        /**
         * Load notifications via AJAX
         */
        loadNotifications: function() {
            const self = this;
            
            $.ajax({
                url: vh360Notifications.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_get_notifications',
                    nonce: vh360Notifications.nonce,
                    limit: 5,
                    offset: 0
                },
                success: function(response) {
                    if (response.success && response.data.notifications) {
                        self.renderNotifications(response.data.notifications);
                    }
                },
                error: function() {
                    console.error('Failed to load notifications');
                }
            });
        },
        
        /**
         * Render notifications in dropdown
         */
        renderNotifications: function(notifications) {
            if (notifications.length === 0) {
                this.notificationList.html(this.getEmptyStateHTML());
                this.markAllReadBtn.hide();
                return;
            }
            
            let html = '';
            notifications.forEach(function(notification) {
                html += window.VH360Notifications.getNotificationItemHTML(notification);
            });
            
            this.notificationList.html(html);
            
            // Show/hide mark all as read button
            const hasUnread = notifications.some(function(n) { return !n.is_read; });
            this.markAllReadBtn.toggle(hasUnread);
        },
        
        /**
         * Get notification item HTML
         */
        getNotificationItemHTML: function(notification) {
            const readClass = notification.is_read ? 'vh360-notification-item--read' : 'vh360-notification-item--unread';
            const indicatorHTML = !notification.is_read ? '<div class="vh360-notification-item-indicator" aria-label="Unread"></div>' : '';
            
            return `
                <div class="vh360-notification-item ${readClass}" data-notification-id="${notification.id}">
                    <a href="${notification.link}" class="vh360-notification-item-link">
                        <div class="vh360-notification-item-avatar">
                            <img src="${notification.actor_avatar}" alt="${notification.actor_name}" width="40" height="40">
                        </div>
                        <div class="vh360-notification-item-content">
                            <div class="vh360-notification-item-message">
                                ${notification.message}
                            </div>
                            <div class="vh360-notification-item-time">
                                ${notification.time_ago} ${vh360Notifications.i18n.ago || 'ago'}
                            </div>
                        </div>
                        ${indicatorHTML}
                    </a>
                </div>
            `;
        },
        
        /**
         * Get empty state HTML
         */
        getEmptyStateHTML: function() {
            return `
                <div class="vh360-notification-empty">
                    <svg class="vh360-notification-empty-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 17H20L18.5951 15.5951C18.2141 15.2141 18 14.6973 18 14.1585V11C18 8.38757 16.3304 6.16509 14 5.34142V5C14 3.89543 13.1046 3 12 3C10.8954 3 10 3.89543 10 5V5.34142C7.66962 6.16509 6 8.38757 6 11V14.1585C6 14.6973 5.78595 15.2141 5.40493 15.5951L4 17H9M15 17V18C15 19.6569 13.6569 21 12 21C10.3431 21 9 19.6569 9 18V17M15 17H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p>${vh360Notifications.i18n.noNotifications || 'No notifications yet'}</p>
                </div>
            `;
        },
        
        /**
         * Update notification count
         */
        updateCount: function() {
            const self = this;
            
            if (document.hidden || this.countRequestInFlight) {
                return;
            }
            
            this.countRequestInFlight = true;
            
            $.ajax({
                url: vh360Notifications.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_get_notification_count',
                    nonce: vh360Notifications.nonce
                },
                success: function(response) {
                    if (response.success && typeof response.data.count !== 'undefined') {
                        self.updateBadge(response.data.count);
                    }
                },
                error: function() {
                    console.error('Failed to update notification count');
                },
                complete: function() {
                    self.countRequestInFlight = false;
                }
            });
        },
        
        /**
         * Update badge display
         */
        updateBadge: function(count) {
            if (count > 0) {
                const displayCount = count > 99 ? '99+' : count;
                if (this.badge.length) {
                    this.badge.attr('data-count', count).text(displayCount);
                } else {
                    this.bellBtn.append(`<span class="vh360-notification-badge" data-count="${count}">${displayCount}</span>`);
                    this.badge = $('.vh360-notification-badge');
                }
            } else {
                this.badge.remove();
                this.badge = $();
            }
        },
        
        /**
         * Mark notification as read
         */
        markAsRead: function(notificationId) {
            const self = this;
            
            $.ajax({
                url: vh360Notifications.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_mark_notification_read',
                    nonce: vh360Notifications.nonce,
                    notification_id: notificationId
                },
                success: function(response) {
                    if (response.success) {
                        self.updateBadge(response.data.count);
                    }
                },
                error: function() {
                    console.error('Failed to mark notification as read');
                }
            });
        },
        
        /**
         * Mark all notifications as read
         */
        markAllAsRead: function() {
            const self = this;
            
            $.ajax({
                url: vh360Notifications.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_mark_all_notifications_read',
                    nonce: vh360Notifications.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateBadge(0);
                        self.loadNotifications();
                    }
                },
                error: function() {
                    console.error('Failed to mark all notifications as read');
                }
            });
        },
        
        /**
         * Start polling for new notifications
         */
        startPolling: function() {
            const self = this;
            const interval = vh360Notifications.pollInterval || 60000; // Default 60 seconds
            
            if (document.hidden || this.pollingTimer) {
                return;
            }
            
            this.pollingTimer = window.setTimeout(function pollNotifications() {
                self.pollingTimer = null;
                self.updateCount();
                self.startPolling();
            }, interval);
        },
        
        /**
         * Stop polling for new notifications
         */
        stopPolling: function() {
            if (this.pollingTimer) {
                window.clearTimeout(this.pollingTimer);
                this.pollingTimer = null;
            }
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        window.VH360Notifications.init();
    });
    
})(jQuery);
