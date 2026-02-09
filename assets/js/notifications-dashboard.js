/**
 * Notifications Dashboard JavaScript
 *
 * Handles dashboard notification tab functionality including filtering,
 * pagination, and bulk actions.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    const VH360NotificationsDashboard = {
        
        /**
         * Current page
         */
        currentPage: 1,
        
        /**
         * Items per page
         */
        perPage: 20,
        
        /**
         * Current filters
         */
        filters: {
            type: '',
            date: '',
            status: '',
        },
        
        /**
         * Has more items
         */
        hasMore: false,
        
        /**
         * Initialize
         */
        init: function() {
            this.container = $('#vh360-notification-list-dashboard');
            this.loadingEl = $('.vh360-notification-loading');
            this.emptyEl = $('.vh360-notification-empty');
            this.errorEl = $('.vh360-notification-error');
            this.loadMoreBtn = $('#vh360-load-more-notifications');
            
            this.bindEvents();
            this.loadNotifications();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;
            
            // Filter changes
            $('#notification-type-filter, #notification-date-filter, #notification-status-filter').on('change', function() {
                self.filters.type = $('#notification-type-filter').val();
                self.filters.date = $('#notification-date-filter').val();
                self.filters.status = $('#notification-status-filter').val();
                self.currentPage = 1;
                self.loadNotifications(true);
            });
            
            // Mark all as read
            $(document).on('click', '.vh360-mark-all-read-btn', function(e) {
                e.preventDefault();
                self.markAllAsRead();
            });
            
            // Delete read notifications
            $(document).on('click', '[data-action="delete-read"]', function(e) {
                e.preventDefault();
                if (confirm(vh360NotificationsDashboard.strings.confirmDeleteRead)) {
                    self.deleteReadNotifications();
                }
            });
            
            // Clear all notifications
            $(document).on('click', '[data-action="clear-all"]', function(e) {
                e.preventDefault();
                if (confirm(vh360NotificationsDashboard.strings.confirmClearAll)) {
                    self.clearAllNotifications();
                }
            });
            
            // Delete individual notification
            $(document).on('click', '.vh360-notification-delete', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const notificationId = $(this).closest('.vh360-notification-item-dashboard').data('notification-id');
                self.deleteNotification(notificationId);
            });
            
            // Mark as read when clicking notification
            $(document).on('click', '.vh360-notification-item-dashboard', function(e) {
                if (!$(e.target).closest('.vh360-notification-delete').length) {
                    const notificationId = $(this).data('notification-id');
                    const isUnread = $(this).hasClass('vh360-notification-item--unread');
                    
                    if (isUnread && notificationId) {
                        self.markAsRead(notificationId);
                    }
                }
            });
            
            // Load more
            this.loadMoreBtn.on('click', function(e) {
                e.preventDefault();
                self.currentPage++;
                self.loadNotifications(false);
            });
        },
        
        /**
         * Load notifications
         */
        loadNotifications: function(reset) {
            const self = this;
            
            if (reset === true) {
                this.container.empty();
            }
            
            this.showLoading();
            
            const data = {
                action: 'vh360_get_notifications_dashboard',
                nonce: vh360NotificationsDashboard.nonce,
                page: this.currentPage,
                per_page: this.perPage,
                type: this.filters.type,
                date: this.filters.date,
                status: this.filters.status,
            };
            
            $.ajax({
                url: vh360NotificationsDashboard.ajaxurl,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    self.hideLoading();
                    
                    if (response.success && response.data.notifications) {
                        if (response.data.notifications.length > 0) {
                            self.renderNotifications(response.data.notifications, reset);
                            self.hasMore = response.data.has_more;
                            
                            if (self.hasMore) {
                                self.loadMoreBtn.show();
                            } else {
                                self.loadMoreBtn.hide();
                            }
                        } else {
                            if (reset === true || self.currentPage === 1) {
                                self.showEmpty();
                            }
                            self.loadMoreBtn.hide();
                        }
                    } else {
                        self.showError();
                    }
                },
                error: function() {
                    self.hideLoading();
                    self.showError();
                }
            });
        },
        
        /**
         * Render notifications
         */
        renderNotifications: function(notifications, reset) {
            if (reset === true) {
                this.container.empty();
            }
            
            this.hideEmpty();
            this.hideError();
            
            notifications.forEach(function(notification) {
                const item = $('<div>', {
                    'class': 'vh360-notification-item-dashboard' + (notification.is_read ? '' : ' vh360-notification-item--unread'),
                    'data-notification-id': notification.id
                });
                
                const iconDiv = $('<div>', {'class': 'vh360-notification-icon'});
                if (notification.icon) {
                    iconDiv.html(notification.icon); // Icon is already sanitized SVG from server
                }
                
                const content = $('<div>', {'class': 'vh360-notification-content'});
                content.append(iconDiv);
                
                const details = $('<div>', {'class': 'vh360-notification-details'});
                
                // Safely add avatar if present
                if (notification.actor_avatar) {
                    const avatarContainer = $('<div>', {'class': 'vh360-notification-actor-avatar'});
                    const avatarImg = $('<img>', {
                        'src': notification.actor_avatar,
                        'alt': $('<div>').text(notification.actor_name).html(), // Escape for attribute
                        'class': 'vh360-notification-avatar'
                    });
                    avatarContainer.append(avatarImg);
                    details.append(avatarContainer);
                }
                
                // Message is already sanitized HTML from server
                const messageDiv = $('<div>', {'class': 'vh360-notification-message'});
                messageDiv.html(notification.message);
                details.append(messageDiv);
                
                // Time is plain text
                const timeDiv = $('<div>', {'class': 'vh360-notification-time'});
                timeDiv.text(notification.time_ago);
                details.append(timeDiv);
                
                content.append(details);
                
                const actions = $('<div>', {'class': 'vh360-notification-actions'});
                actions.append(
                    '<button type="button" class="vh360-notification-delete" aria-label="' + 
                    vh360NotificationsDashboard.strings.deleteNotification + '">' +
                    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                    '<polyline points="3 6 5 6 21 6"></polyline>' +
                    '<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>' +
                    '</svg>' +
                    '</button>'
                );
                
                if (!notification.is_read) {
                    item.append('<div class="vh360-notification-unread-indicator"></div>');
                }
                
                item.append(content).append(actions);
                
                // Add click handler for navigation
                if (notification.link && notification.link !== '#') {
                    item.css('cursor', 'pointer');
                    item.on('click', function(e) {
                        if (!$(e.target).closest('.vh360-notification-delete').length) {
                            window.location.href = notification.link;
                        }
                    });
                }
                
                this.container.append(item);
            }.bind(this));
        },
        
        /**
         * Mark notification as read
         */
        markAsRead: function(notificationId) {
            const self = this;
            
            $.ajax({
                url: vh360NotificationsDashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_mark_notification_read',
                    nonce: vh360NotificationsDashboard.nonce,
                    notification_id: notificationId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('[data-notification-id="' + notificationId + '"]')
                            .removeClass('vh360-notification-item--unread')
                            .find('.vh360-notification-unread-indicator').remove();
                        
                        self.updateStats();
                    }
                }
            });
        },
        
        /**
         * Mark all as read
         */
        markAllAsRead: function() {
            const self = this;
            
            $.ajax({
                url: vh360NotificationsDashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_mark_all_notifications_read',
                    nonce: vh360NotificationsDashboard.nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('.vh360-notification-item-dashboard')
                            .removeClass('vh360-notification-item--unread')
                            .find('.vh360-notification-unread-indicator').remove();
                        
                        self.updateStats();
                        self.showNotification(vh360NotificationsDashboard.strings.markedAllRead, 'success');
                    } else {
                        self.showNotification(response.data.message || vh360NotificationsDashboard.strings.error, 'error');
                    }
                }
            });
        },
        
        /**
         * Delete notification
         */
        deleteNotification: function(notificationId) {
            const self = this;
            
            $.ajax({
                url: vh360NotificationsDashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_delete_notification',
                    nonce: vh360NotificationsDashboard.nonce,
                    notification_id: notificationId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('[data-notification-id="' + notificationId + '"]')
                            .fadeOut(300, function() {
                                $(this).remove();
                                if (self.container.children().length === 0) {
                                    self.showEmpty();
                                }
                            });
                        
                        self.updateStats();
                    } else {
                        self.showNotification(response.data.message || vh360NotificationsDashboard.strings.error, 'error');
                    }
                }
            });
        },
        
        /**
         * Delete read notifications
         */
        deleteReadNotifications: function() {
            const self = this;
            
            $.ajax({
                url: vh360NotificationsDashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_delete_read_notifications',
                    nonce: vh360NotificationsDashboard.nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        self.currentPage = 1;
                        self.loadNotifications(true);
                        self.updateStats();
                        self.showNotification(vh360NotificationsDashboard.strings.deletedRead, 'success');
                    } else {
                        self.showNotification(response.data.message || vh360NotificationsDashboard.strings.error, 'error');
                    }
                }
            });
        },
        
        /**
         * Clear all notifications
         */
        clearAllNotifications: function() {
            const self = this;
            
            $.ajax({
                url: vh360NotificationsDashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_clear_all_notifications',
                    nonce: vh360NotificationsDashboard.nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        self.container.empty();
                        self.showEmpty();
                        self.updateStats();
                        self.showNotification(vh360NotificationsDashboard.strings.clearedAll, 'success');
                    } else {
                        self.showNotification(response.data.message || vh360NotificationsDashboard.strings.error, 'error');
                    }
                }
            });
        },
        
        /**
         * Update statistics
         */
        updateStats: function() {
            $.ajax({
                url: vh360NotificationsDashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_get_notification_stats',
                    nonce: vh360NotificationsDashboard.nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.stats) {
                        const stats = response.data.stats;
                        $('.vh360-notification-stat-value').eq(0).text(stats.total);
                        $('.vh360-notification-stat-value').eq(1).text(stats.unread);
                        $('.vh360-notification-stat-value').eq(2).text(stats.read);
                        
                        // Update badge in header if exists
                        if (typeof VH360Notifications !== 'undefined' && VH360Notifications.updateBadge) {
                            VH360Notifications.updateBadge(stats.unread);
                        }
                    }
                }
            });
        },
        
        /**
         * Show loading state
         */
        showLoading: function() {
            this.loadingEl.show();
            this.emptyEl.hide();
            this.errorEl.hide();
        },
        
        /**
         * Hide loading state
         */
        hideLoading: function() {
            this.loadingEl.hide();
        },
        
        /**
         * Show empty state
         */
        showEmpty: function() {
            this.emptyEl.show();
            this.errorEl.hide();
            this.container.hide();
        },
        
        /**
         * Hide empty state
         */
        hideEmpty: function() {
            this.emptyEl.hide();
            this.container.show();
        },
        
        /**
         * Show error state
         */
        showError: function() {
            this.errorEl.show();
            this.emptyEl.hide();
            this.container.hide();
        },
        
        /**
         * Hide error state
         */
        hideError: function() {
            this.errorEl.hide();
        },
        
        /**
         * Show notification message
         */
        showNotification: function(message, type) {
            // Use dashboard notification system if available
            if (typeof VH360Dashboard !== 'undefined' && VH360Dashboard.showNotification) {
                VH360Dashboard.showNotification(message, type);
            } else {
                alert(message);
            }
        }
    };
    
    // Initialize when dashboard notifications tab is visible
    $(document).ready(function() {
        // Check if we're on the notifications tab
        if ($('#notifications').hasClass('active') || window.location.hash === '#notifications') {
            VH360NotificationsDashboard.init();
        }
        
        // Initialize when tab is activated
        $(document).on('click', '[data-tab="notifications"]', function() {
            if (!VH360NotificationsDashboard.container || VH360NotificationsDashboard.container.length === 0) {
                setTimeout(function() {
                    VH360NotificationsDashboard.init();
                }, 100);
            }
        });
    });
    
})(jQuery);
