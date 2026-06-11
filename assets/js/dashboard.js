/**
 * Dashboard JavaScript
 *
 * Handles dashboard functionality including mobile menu toggle,
 * tab switching, AJAX handlers, and form validation.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Dashboard object
     */
    var VH360Dashboard = {

        /**
         * Initialize dashboard functionality
         */
        init: function() {
            this.tabSwitching();
            this.setupAjax();
            this.formValidation();
            this.loadMoreVideos();
            this.loadMoreActivities();
            this.handleEditVideo();
            this.handleVideosPagination();
            this.setupVideoUpload();
            this.initCoursesForm();
            this.handleCourseActions();
            
            // Handle video deletion
            var self = this;
            $(document).on('click', '.vh360-video-delete', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.deleteVideo($(this));
            });
            
            // Handle account delete button click
            $(document).on('click', '.vh360-account-delete-btn', function(e) {
                e.preventDefault();
                var message = $(this).data('message') || 'Account deletion feature coming soon. Please contact support.';
                self.showNotification(message, 'info');
            });
        },

        /**
         * Activate a specific dashboard tab
         * @param {string} tabId - The tab identifier to activate (should be already sanitized)
         * @param {boolean} updateHistory - Whether to update browser history
         */
        activateTab: function(tabId, updateHistory) {
            // Sanitize tabId for safe use in selectors (defense in depth)
            // Only allow alphanumeric, dash, and underscore - safe for jQuery selectors
            tabId = String(tabId).replace(/[^a-zA-Z0-9_-]/g, '');
            if (!tabId) {
                return false;
            }
            
            // After sanitization, tabId is safe to use in jQuery selectors (no escaping needed)
            var $targetTab = $('.vh360-dashboard-tab[data-tab="' + tabId + '"]');
            var $targetContent = $(document.getElementById(tabId));

            // Require at least a matching content pane to activate.
            // This allows tabs like "membership" to activate from ?tab= even
            // when the sidebar menu does not contain a matching nav item.
            if (!$targetTab.length && !$targetContent.length) {
                return false;
            }

            // Remove active class from all tabs and content
            $('.vh360-dashboard-tab').removeClass('active');
            $('.vh360-dashboard-tab-content').removeClass('active');

            // Activate nav item if it exists (may not for programmatic tabs)
            if ($targetTab.length) {
                $targetTab.addClass('active');
            }

            // Activate content pane
            if ($targetContent.length) {
                $targetContent.addClass('active');
            }

            // Update URL hash if requested
            if (updateHistory && history.pushState) {
                history.pushState(null, null, '#' + tabId);
            }

            return true;
        },

        /**
         * Tab switching functionality
         */
        tabSwitching: function() {
            var self = this;

            $('.vh360-dashboard-tab').on('click', function(e) {
                e.preventDefault();
                
                var $tab = $(this);
                var targetId = $tab.data('tab');

                // Activate the tab and update history
                self.activateTab(targetId, true);
            });

            // Profile tabs (for sub-tabs within profile section)
            $('.vh360-profile-tab').on('click', function(e) {
                e.preventDefault();
                
                var $tab = $(this);
                var targetId = $tab.data('tab');

                // Sanitize targetId for safe use (defense in depth)
                targetId = String(targetId).replace(/[^a-zA-Z0-9_-]/g, '');
                if (!targetId) {
                    return;
                }

                // Remove active class from all tabs and content
                $('.vh360-profile-tab').removeClass('active');
                $('.vh360-profile-tab-content').removeClass('active');

                // Add active class to clicked tab and corresponding content
                $tab.addClass('active');
                $(document.getElementById(targetId)).addClass('active');
            });

            // Activate tab from URL hash OR query parameter on page load
            var hash = window.location.hash;
            var tabParam = null;
            
            // Get query parameter with fallback for older browsers
            try {
                if (window.URLSearchParams) {
                    var urlParams = new URLSearchParams(window.location.search);
                    tabParam = urlParams.get('tab');
                }
            } catch (e) {
                // URLSearchParams failed (partial implementation in some older browsers)
                // Will use regex fallback below
            }
            
            // Fallback for older browsers or if URLSearchParams failed
            if (!tabParam) {
                var match = window.location.search.match(/[?&]tab=([^&]*)/);
                if (match) {
                    tabParam = decodeURIComponent(match[1]);
                }
            }

            // Determine tab ID from hash or query parameter
            var hashId = hash ? hash.substring(1) : '';
            var tabId = hashId || tabParam || '';

            if (tabId) {
                // Sanitize tabId to only allow alphanumeric, dash, and underscore
                tabId = tabId.replace(/[^a-zA-Z0-9_-]/g, '');
                
                // Double-check after sanitization (empty if input contained only invalid chars)
                if (tabId) {
                    // Activate the tab without updating history (we're already on this URL)
                    if (self.activateTab(tabId, false)) {
                        // Clean up URL if query parameter was used (replace with hash)
                        if (tabParam && history.replaceState) {
                            history.replaceState(null, null, '#' + tabId);
                        }
                    }
                }
            }
        },

        /**
         * Setup AJAX handlers
         */
        setupAjax: function() {
            var self = this;

            // Join group
            $(document).on('click', '.vh360-group-join-btn', function(e) {
                e.preventDefault();
                self.joinGroup($(this));
            });

            // Leave group
            $(document).on('click', '.vh360-group-leave-btn', function(e) {
                e.preventDefault();
                self.leaveGroup($(this));
            });

            // Subscribe/Unsubscribe
            $(document).on('click', '.vh360-subscribe-btn', function(e) {
                e.preventDefault();
                self.toggleSubscription($(this));
            });
        },

        /**
         * Join a group
         */
        joinGroup: function($button) {
            var groupId = $button.data('group-id');

            if (!groupId) {
                return;
            }

            $button.addClass('vh360-group-btn-loading').text('Joining...');

            $.ajax({
                url: vh360Dashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_join_group',
                    nonce: vh360Dashboard.nonce,
                    group_id: groupId
                },
                success: function(response) {
                    if (response.success) {
                        $button.removeClass('vh360-group-join-btn vh360-group-btn-loading')
                               .addClass('vh360-group-leave-btn')
                               .text('Leave');
                        
                        // Update member count if element exists
                        var $memberCount = $button.closest('.vh360-group-card').find('.vh360-group-members-count');
                        if ($memberCount.length) {
                            var currentCount = parseInt($memberCount.text().replace(/[^0-9]/g, ''));
                            $memberCount.text((currentCount + 1) + ' members');
                        }
                    } else {
                        alert(response.data.message || 'Failed to join group');
                        $button.removeClass('vh360-group-btn-loading').text('Join');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $button.removeClass('vh360-group-btn-loading').text('Join');
                }
            });
        },

        /**
         * Leave a group
         */
        leaveGroup: function($button) {
            var groupId = $button.data('group-id');

            if (!groupId) {
                return;
            }

            if (!confirm('Are you sure you want to leave this group?')) {
                return;
            }

            $button.addClass('vh360-group-btn-loading').text('Leaving...');

            $.ajax({
                url: vh360Dashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_leave_group',
                    nonce: vh360Dashboard.nonce,
                    group_id: groupId
                },
                success: function(response) {
                    if (response.success) {
                        $button.removeClass('vh360-group-leave-btn vh360-group-btn-loading')
                               .addClass('vh360-group-join-btn')
                               .text('Join');
                        
                        // Update member count if element exists
                        var $memberCount = $button.closest('.vh360-group-card').find('.vh360-group-members-count');
                        if ($memberCount.length) {
                            var currentCount = parseInt($memberCount.text().replace(/[^0-9]/g, ''));
                            if (currentCount > 0) {
                                $memberCount.text((currentCount - 1) + ' members');
                            }
                        }
                    } else {
                        alert(response.data.message || 'Failed to leave group');
                        $button.removeClass('vh360-group-btn-loading').text('Leave');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $button.removeClass('vh360-group-btn-loading').text('Leave');
                }
            });
        },

        /**
         * Toggle subscription
         */
        toggleSubscription: function($button) {
            var userId = $button.data('user-id');
            var isSubscribed = $button.hasClass('subscribed');

            if (!userId) {
                return;
            }

            var originalText = $button.text();
            $button.text('Loading...').prop('disabled', true);

            $.ajax({
                url: vh360Dashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_toggle_subscription',
                    nonce: vh360Dashboard.nonce,
                    user_id: userId,
                    subscribe: !isSubscribed
                },
                success: function(response) {
                    if (response.success) {
                        $button.toggleClass('subscribed');
                        $button.text(isSubscribed ? 'Subscribe' : 'Unsubscribe');
                        
                        // Update subscriber count if element exists
                        var $subscriberCount = $('.vh360-profile-stat[data-stat="subscribers"] .vh360-profile-stat-value');
                        if ($subscriberCount.length) {
                            var currentCount = parseInt($subscriberCount.text().replace(/[^0-9]/g, ''));
                            $subscriberCount.text(isSubscribed ? currentCount - 1 : currentCount + 1);
                        }
                    } else {
                        alert(response.data.message || 'Failed to update subscription');
                        $button.text(originalText);
                    }
                    $button.prop('disabled', false);
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },

        /**
         * Load more videos (infinite scroll)
         */
        loadMoreVideos: function() {
            var $loadMoreBtn = $('.vh360-load-more-videos');
            
            if (!$loadMoreBtn.length) {
                return;
            }

            $loadMoreBtn.on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var page = parseInt($button.data('page')) || 1;
                var maxPages = parseInt($button.data('max-pages')) || 1;
                var category = $button.data('category') || '';
                var $container = $('.vh360-videos-container');

                if (page >= maxPages) {
                    $button.remove();
                    return;
                }

                page++;
                $button.text('Loading...').prop('disabled', true);

                $.ajax({
                    url: vh360Dashboard.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vh360_load_more_videos',
                        nonce: vh360Dashboard.nonce,
                        page: page,
                        category: category,
                        posts_per_page: 12
                    },
                    success: function(response) {
                        if (response.success) {
                            $container.append(response.data.html);
                            $button.data('page', page);
                            $button.text('Load More').prop('disabled', false);

                            if (page >= response.data.max_pages) {
                                $button.remove();
                            }
                        } else {
                            $button.remove();
                        }
                    },
                    error: function() {
                        alert('Failed to load more videos.');
                        $button.text('Load More').prop('disabled', false);
                    }
                });
            });
        },

        /**
         * Show notification message
         */
        showNotification: function(message, type) {
            type = type || 'info';
            var iconSvg = type === 'success' 
                ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'
                : type === 'error'
                ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
                : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
            
            var $notification = $('<div class="vh360-notification vh360-notification-' + type + '">' + iconSvg + '<span>' + message + '</span></div>');
            
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.addClass('show');
            }, 100);
            
            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 3000);
        },

        /**
         * Confirm action with custom modal
         */
        confirmAction: function(message, callback) {
            var self = this;
            var $modal = $(
                '<div class="vh360-modal-overlay">' +
                    '<div class="vh360-modal">' +
                        '<div class="vh360-modal-content">' +
                            '<p>' + message + '</p>' +
                        '</div>' +
                        '<div class="vh360-modal-actions">' +
                            '<button class="vh360-dashboard-btn vh360-dashboard-btn-secondary vh360-modal-cancel">Cancel</button>' +
                            '<button class="vh360-dashboard-btn vh360-danger-btn vh360-modal-confirm">Confirm</button>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
            
            $('body').append($modal);
            
            setTimeout(function() {
                $modal.addClass('show');
            }, 100);
            
            $modal.find('.vh360-modal-confirm').on('click', function() {
                $modal.removeClass('show');
                setTimeout(function() {
                    $modal.remove();
                }, 300);
                if (callback) callback(true);
            });
            
            $modal.find('.vh360-modal-cancel, .vh360-modal-overlay').on('click', function(e) {
                if (e.target === this) {
                    $modal.removeClass('show');
                    setTimeout(function() {
                        $modal.remove();
                    }, 300);
                    if (callback) callback(false);
                }
            });
        },

        /**
         * Delete video
         */
        deleteVideo: function($button) {
            var self = this;
            var videoId = $button.data('video-id');
            var nonce = $button.data('nonce');

            if (!videoId || !nonce) {
                return;
            }

            var labels = (vh360Dashboard.contentLabels) || {};

            this.confirmAction(labels.deleteConfirm || 'Are you sure you want to delete this video? This action cannot be undone.', function(confirmed) {
                if (!confirmed) return;
                
                $button.prop('disabled', true).css('opacity', '0.5');

                $.ajax({
                    url: vh360Dashboard.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vh360_delete_video',
                        video_id: videoId,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            self.showNotification(labels.deletedSuccess || 'Video deleted successfully', 'success');
                            
                            // Remove the video card with animation
                            $button.closest('.vh360-video-grid-item').fadeOut(300, function() {
                                $(this).remove();
                                
                                // Check if grid is empty
                                if ($('.vh360-videos-grid .vh360-video-grid-item').length === 0) {
                                    $('.vh360-videos-grid').html(
                                        '<div class="vh360-dashboard-empty">' +
                                        '<div class="vh360-dashboard-empty-icon">📹</div>' +
                                        '<p class="vh360-dashboard-empty-title">' + (labels.emptyTitle || 'No videos yet') + '</p>' +
                                        '<p class="vh360-dashboard-empty-text">' + (labels.emptyText || 'Upload your first video to get started!') + '</p>' +
                                        '</div>'
                                    );
                                }
                            });
                        } else {
                            self.showNotification(response.data.message || labels.deleteFailed || 'Failed to delete video', 'error');
                            $button.prop('disabled', false).css('opacity', '1');
                        }
                    },
                    error: function() {
                        self.showNotification('An error occurred. Please try again.', 'error');
                        $button.prop('disabled', false).css('opacity', '1');
                    }
                });
            });
        },

        /**
         * Load more activities
         */
        loadMoreActivities: function() {
            var $button = $('.vh360-load-more-activity');
            
            if (!$button.length) {
                return;
            }

            $button.on('click', function(e) {
                e.preventDefault();
                
                var offset = parseInt($button.data('offset')) || 0;
                var filter = $button.data('filter') || 'all';
                var nonce = $button.data('nonce');
                
                $button.text('Loading...').prop('disabled', true);

                $.ajax({
                    url: vh360Dashboard.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vh360_load_activities',
                        offset: offset,
                        type: filter,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.html) {
                            $('.vh360-activity-feed').append(response.data.html);
                            $button.data('offset', response.data.offset);
                            $button.text('Load More').prop('disabled', false);
                            
                            if (response.data.count < 20) {
                                $button.remove();
                            }
                        } else {
                            $button.remove();
                        }
                    },
                    error: function() {
                        alert('Failed to load more activities.');
                        $button.text('Load More').prop('disabled', false);
                    }
                });
            });
        },

        /**
         * Form validation helpers
         */
        formValidation: function() {
            // Profile form validation
            $('.vh360-profile-form').on('submit', function(e) {
                var isValid = true;
                var $form = $(this);

                // Clear previous errors
                $form.find('.error-message').remove();
                $form.find('.error').removeClass('error');

                // Validate required fields
                $form.find('[required]').each(function() {
                    var $field = $(this);
                    if (!$field.val().trim()) {
                        isValid = false;
                        $field.addClass('error');
                        $field.after('<span class="error-message">This field is required.</span>');
                    }
                });

                // Validate email
                var $email = $form.find('input[type="email"]');
                if ($email.length && $email.val()) {
                    var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test($email.val())) {
                        isValid = false;
                        $email.addClass('error');
                        $email.after('<span class="error-message">Please enter a valid email address.</span>');
                    }
                }

                // Validate URL
                var $url = $form.find('input[type="url"]');
                if ($url.length && $url.val()) {
                    try {
                        new URL($url.val());
                    } catch (_) {
                        isValid = false;
                        $url.addClass('error');
                        $url.after('<span class="error-message">Please enter a valid URL.</span>');
                    }
                }

                if (!isValid) {
                    e.preventDefault();
                    // Scroll to first error
                    var $firstError = $form.find('.error').first();
                    if ($firstError.length) {
                        $('html, body').animate({
                            scrollTop: $firstError.offset().top - 100
                        }, 300);
                    }
                }
            });
        },

        /**
         * Initialize create video form functionality
         */
        initCreateVideoForm: function() {
            var self = this;
            var $form = $('#vh360-create-video-form');
            
            if (!$form.length) return;

            // Character counters
            $('#vh360_video_title').on('input', function() {
                var length = $(this).val().length;
                $('#vh360-title-count').text(length);
            });

            $('#vh360_video_excerpt').on('input', function() {
                var length = $(this).val().length;
                $('#vh360-excerpt-count').text(length);
            });

            // Image upload preview (scoped to create-video form only)
            $('#vh360-create-video-form').on('click', '#vh360-upload-trigger', function(e) {
                e.preventDefault();
                $('#vh360_featured_image').click();
            });

            $('#vh360-create-video-form').on('change', '#vh360_featured_image', function() {
                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#vh360-preview-img').attr('src', e.target.result);
                        $('#vh360-image-preview').show();
                    };
                    reader.readAsDataURL(file);
                }
            });

            $('#vh360-create-video-form').on('click', '#vh360-remove-image', function(e) {
                e.preventDefault();
                $('#vh360_featured_image').val('');
                $('#vh360-image-preview').hide();
                $('#vh360-preview-img').attr('src', '');
            });

            // Toggle collapsible sections
            $('.vh360-section-toggle').on('click', function() {
                var $section = $(this).closest('.vh360-form-section-collapsible');
                var $content = $section.find('.vh360-section-content');
                var $icon = $(this).find('.vh360-toggle-icon');
                
                $content.slideToggle(300);
                $icon.toggleClass('rotated');
            });

            // Show/hide quality fields based on override checkbox
            $('#vh360_override_quality').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#vh360-quality-fields').slideDown(300);
                } else {
                    $('#vh360-quality-fields').slideUp(300);
                }
            });

            // Livestream settings: Show/hide stream type specific fields
            function toggleStreamTypeFields() {
                var selectedType = $('#vh360_type').val();
                $('.vh360-stream-type-field').hide();
                $('.vh360-stream-type-field[data-type="' + selectedType + '"]').show();
            }
            
            $('#vh360_type').on('change', toggleStreamTypeFields);
            toggleStreamTypeFields(); // Initialize on load

            // Livestream settings: Passcode field toggle
            $('#vh360_require_passcode').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#vh360-passcode-field').slideDown(300);
                } else {
                    $('#vh360-passcode-field').slideUp(300);
                }
            });

            // Livestream settings: Mutual exclusivity for everyone_is_host and passcode
            $('#vh360_agora_everyone_is_host').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#vh360_require_passcode').prop('checked', false);
                    $('#vh360-passcode-field').slideUp(300);
                }
            });

            $('#vh360_require_passcode').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#vh360_agora_everyone_is_host').prop('checked', false);
                }
            });

            // Form submission
            $form.on('submit', function(e) {
                e.preventDefault();

                // Clear previous messages
                $('#vh360-form-message').hide().removeClass('success error');
                
                // Get the button that submitted the form. Prefer the native submitter
                // because document.activeElement can be unreliable after uploads or other UI interactions.
                var submitter = e.originalEvent && e.originalEvent.submitter
                    ? e.originalEvent.submitter
                    : document.activeElement;
                var $submitBtn = $(submitter);
                var action = $submitBtn.val();

                if (action !== 'publish' && action !== 'draft') {
                    action = $('input[name="video_id"]').length > 0 ? '' : 'draft';
                }

                var createFormLabels = (window.vh360Dashboard && vh360Dashboard.createForm) || {};
                
                // Validate required fields
                var title = $('#vh360_video_title').val().trim();
                
                if (!title) {
                    self.showFormMessage(createFormLabels.titleRequired || 'Please provide a video title.', 'error');
                    $('#vh360_video_title').focus();
                    return false;
                }
                
                // No validation for video source - matching backend behavior
                // Backend allows creating videos without video URL/embed code

                // Disable submit buttons
                var $submitButtons = $('#vh360-publish-btn, #vh360-draft-btn');
                $submitButtons.each(function() {
                    var $button = $(this);
                    if (!$button.data('original-html')) {
                        $button.data('original-html', $button.html());
                    }
                });

                $submitButtons.prop('disabled', true).addClass('loading');

                if ($submitBtn.is('#vh360-publish-btn')) {
                    var isEditMode = $('input[name="video_id"]').length > 0;
                    var loadingLabel = isEditMode ? createFormLabels.updating : createFormLabels.publishing;

                    if (loadingLabel) {
                        $submitBtn.text(loadingLabel);
                    }
                }
                
                // Prepare form data
                var formData = new FormData(this);
                formData.append('action', 'vh360_create_video_frontend');
                formData.append('vh360_action', action);

                // Send AJAX request
                $.ajax({
                    url: vh360Dashboard.ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            self.showFormMessage(response.data.message, 'success');
                            
                            // Reset form
                            $form[0].reset();
                            $('#vh360-image-preview').hide();
                            $('#vh360-quality-fields').hide();
                            $('#vh360_override_quality').prop('checked', false);
                            $('#vh360-title-count, #vh360-excerpt-count').text('0');
                            
                            // Redirect to videos tab after short delay
                            setTimeout(function() {
                                self.activateTab('videos', true);
                                
                                // Show success notification
                                var viewLabel = createFormLabels.viewItem || 'View Video';
                                self.showNotification(response.data.message + ' <a href="' + response.data.permalink + '" target="_blank">' + viewLabel + '</a>', 'success');
                            }, 2000);
                        } else {
                            self.showFormMessage(response.data.message || 'An error occurred. Please try again.', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        self.showFormMessage('An error occurred. Please try again.', 'error');
                    },
                    complete: function() {
                        // Re-enable submit buttons
                        $('#vh360-publish-btn, #vh360-draft-btn').each(function() {
                            var $button = $(this);
                            $button.prop('disabled', false).removeClass('loading');

                            if ($button.data('original-html')) {
                                $button.html($button.data('original-html'));
                            }
                        });
                    }
                });

                return false;
            });
        },

        /**
         * Show form message
         */
        showFormMessage: function(message, type) {
            var $messageDiv = $('#vh360-form-message');
            $messageDiv
                .removeClass('success error')
                .addClass(type)
                .html(message)
                .slideDown(300);
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $messageDiv.offset().top - 100
            }, 300);
            
            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(function() {
                    $messageDiv.slideUp(300);
                }, 5000);
            }
        },
        
        /**
         * Handle edit video button clicks
         */
        handleEditVideo: function() {
            var self = this;
            
            $(document).on('click', '.vh360-edit-video', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var videoId = $(this).data('video-id');
                
                // Navigate to create-video tab with edit parameter
                // Using full page URL with query parameter so PHP can detect edit mode
                var currentUrl = window.location.pathname;
                var newUrl = currentUrl + '?edit=' + videoId + '#create-video';
                window.location.href = newUrl;
            });
        },
        
        /**
         * Handle pagination clicks in Videos tab
         */
        handleVideosPagination: function() {
            var self = this;
            
            // Handle pagination link clicks
            $(document).on('click', '.vh360-dashboard-videos .vh360-dashboard-pagination a', function(e) {
                e.preventDefault();
                
                var url = $(this).attr('href');
                if (!url || url === '#') return;
                
                // Extract page number from URL (with fallback for older browsers)
                var page = 1;
                try {
                    if (window.URLSearchParams) {
                        var urlParams = new URLSearchParams(url.split('?')[1] || '');
                        page = urlParams.get('paged') || 1;
                    } else {
                        // Fallback for browsers without URLSearchParams
                        var match = url.match(/paged=(\d+)/);
                        if (match) {
                            page = match[1];
                        }
                    }
                } catch (e) {
                    // Fallback if URLSearchParams fails
                    var match = url.match(/paged=(\d+)/);
                    if (match) {
                        page = match[1];
                    }
                }
                
                // Get current filters
                var status = $('#vh360-video-status').val() || 'publish';
                var search = $('#vh360-video-search').val() || '';
                
                // Update URL without page reload
                var newUrl = window.location.pathname + window.location.hash.split('?')[0];
                if (page > 1 || status !== 'publish' || search) {
                    var params = [];
                    if (page > 1) params.push('paged=' + page);
                    if (status !== 'publish') params.push('video_status=' + encodeURIComponent(status));
                    if (search) params.push('video_search=' + encodeURIComponent(search));
                    newUrl += '?' + params.join('&');
                }
                window.history.pushState({}, '', newUrl);
                
                // Reload videos tab content
                self.loadVideosTabContent(page, status, search);
                
                // Scroll to top of videos grid
                $('.vh360-dashboard-videos').animate({ scrollTop: 0 }, 300);
            });
        },
        
        /**
         * Load videos tab content via AJAX (for pagination)
         */
        loadVideosTabContent: function(page, status, search) {
            var self = this;
            var $videosGrid = $('.vh360-videos-grid');
            var $pagination = $('.vh360-dashboard-pagination');
            var labels = (vh360Dashboard.contentLabels) || {};
            
            // Show loading state
            $videosGrid.css('opacity', '0.5');
            
            // Make AJAX request
            $.ajax({
                url: vh360Dashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_load_videos_tab',
                    nonce: vh360Dashboard.nonce,
                    page: page,
                    status: status,
                    search: search
                },
                success: function(response) {
                    if (response.success) {
                        $videosGrid.html(response.data.videos_html);
                        $pagination.html(response.data.pagination_html);
                    } else {
                        self.showNotification(response.data.message || labels.loadFailed || 'Failed to load videos', 'error');
                    }
                },
                error: function() {
                    self.showNotification(labels.loadError || 'An error occurred while loading videos', 'error');
                },
                complete: function() {
                    $videosGrid.css('opacity', '1');
                }
            });
        },

        /**
         * Setup video upload functionality
         */
        setupVideoUpload: function() {
            var self = this;
            
            // Source type radio toggle
            $(document).on('change', '.vh360-source-type-radio', function() {
                var sourceType = $(this).val();
                
                // Hide all source fields
                $('.vh360-source-field').hide();
                
                // Show selected source field
                $('.vh360-source-field[data-source="' + sourceType + '"]').show();
            });
            
            // Upload button trigger
            $(document).on('click', '#vh360-video-upload-trigger', function(e) {
                e.preventDefault();
                $('#vh360_video_file').click();
            });
            
            // File selection handler
            $(document).on('change', '#vh360_video_file', function(e) {
                var file = this.files[0];
                if (!file) return;
                
                self.handleVideoFileSelection(file);
            });
            
            // Remove file button
            $(document).on('click', '#vh360-remove-video', function(e) {
                e.preventDefault();
                self.clearVideoUpload();
            });
            
            // Initialize on page load
            var initialSource = $('.vh360-source-type-radio:checked').val() || 'url';
            $('.vh360-source-field').hide();
            $('.vh360-source-field[data-source="' + initialSource + '"]').show();
        },

        /**
         * Handle video file selection
         */
        handleVideoFileSelection: function(file) {
            var self = this;
            
            // Validate file size on client side
            var maxSize = 500; // Default, will be checked server-side too
            var helpText = $('.vh360-source-field[data-source="upload"] .vh360-form-help').text();
            var maxSizeMatch = helpText.match(/(\d+)\s*MB/);
            if (maxSizeMatch) {
                maxSize = parseInt(maxSizeMatch[1]);
            }
            
            var fileSizeMB = file.size / 1024 / 1024;
            if (fileSizeMB > maxSize) {
                self.showNotification('File size exceeds maximum allowed (' + maxSize + ' MB)', 'error');
                $('#vh360_video_file').val('');
                return;
            }
            
            // Show file preview
            $('#vh360-video-file-name').text(file.name);
            $('#vh360-video-file-size').text(fileSizeMB.toFixed(2) + ' MB');
            $('#vh360-video-preview').show();
            $('#vh360-video-upload-trigger').hide();
            
            // Upload file
            self.uploadVideoFile(file);
        },

        /**
         * Upload video file via AJAX
         */
        uploadVideoFile: function(file) {
            var self = this;
            
            // Validate nonce is available
            if (typeof vh360Dashboard === 'undefined' || !vh360Dashboard.videoUploadNonce) {
                self.showNotification('Security token not available. Please refresh the page.', 'error');
                self.clearVideoUpload();
                return;
            }
            
            // Validate AJAX URL is available
            if (typeof vh360Dashboard === 'undefined' || !vh360Dashboard.ajaxurl) {
                self.showNotification('Upload configuration error. Please refresh the page.', 'error');
                self.clearVideoUpload();
                return;
            }
            
            var formData = new FormData();
            
            var createFormLabels = (window.vh360Dashboard && vh360Dashboard.createForm) || {};
            formData.append('action', 'vh360_upload_video_file');
            formData.append('nonce', vh360Dashboard.videoUploadNonce);
            formData.append('vh360_create_context', createFormLabels.isLessonContext ? 'lesson' : 'video');
            formData.append('vh360_video_file', file);
            
            // Show progress bar
            $('#vh360-video-upload-progress').show();
            
            $.ajax({
                url: vh360Dashboard.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    
                    // Upload progress
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            var percentComplete = Math.round((e.loaded / e.total) * 100);
                            $('#vh360-video-progress-fill').css('width', percentComplete + '%');
                            $('#vh360-video-progress-text').text(percentComplete + '%');
                        }
                    }, false);
                    
                    return xhr;
                },
                success: function(response) {
                    if (response.success) {
                        // Auto-populate video URL field
                        $('#vh360_video_url').val(response.data.video_url);
                        
                        self.showNotification(response.data.message || createFormLabels.uploadSuccess || 'Video uploaded successfully!', 'success');
                        
                        // Hide progress bar after a moment
                        setTimeout(function() {
                            $('#vh360-video-upload-progress').fadeOut();
                        }, 1000);
                    } else {
                        self.showNotification(response.data.message || 'Upload failed', 'error');
                        self.clearVideoUpload();
                    }
                },
                error: function() {
                    self.showNotification('An error occurred during upload', 'error');
                    self.clearVideoUpload();
                }
            });
        },

        /**
         * Clear video upload
         */
        clearVideoUpload: function() {
            $('#vh360_video_file').val('');
            $('#vh360-video-preview').hide();
            $('#vh360-video-upload-progress').hide();
            $('#vh360-video-upload-trigger').show();
            $('#vh360-video-progress-fill').css('width', '0%');
            $('#vh360-video-progress-text').text('0%');
        },

        /**
         * Initialize My Courses form functionality
         */
        initCoursesForm: function() {
            var self = this;
            var $formWrap = $('#vh360-course-form-wrap');
            var $form     = $('#vh360-course-form');

            if (!$form.length) return;

            // Toggle form open/close with the "New Course" button.
            $(document).on('click', '#vh360-course-create-toggle', function() {
                // Reset to create mode.
                self.resetCourseForm();
                $formWrap.slideToggle(300);
            });

            // Cancel button hides the form.
            $(document).on('click', '#vh360-course-form-cancel', function() {
                $formWrap.slideUp(300);
                self.resetCourseForm();
            });

            // Featured image file input preview.
            $(document).on('change', '#vh360_course_featured_image', function() {
                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#vh360-course-preview-img').attr('src', e.target.result);
                        $('#vh360-course-image-preview').show();
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Remove newly selected image.
            $(document).on('click', '#vh360-remove-course-image', function(e) {
                e.preventDefault();
                $('#vh360_course_featured_image').val('');
                $('#vh360-course-image-preview').hide();
                $('#vh360-course-preview-img').attr('src', '');
            });

            // Remove existing (already-saved) image.
            $(document).on('click', '#vh360-remove-existing-course-image', function(e) {
                e.preventDefault();
                $('#vh360-course-existing-image').hide();
                $('#vh360_remove_course_image').val('1');
            });

            // Form submission.
            $form.on('submit', function(e) {
                e.preventDefault();

                var courseName = $('#vh360_course_name').val().trim();
                if (!courseName) {
                    self.showNotification('Please provide a course name.', 'error');
                    $('#vh360_course_name').focus();
                    return false;
                }

                var $submitBtn = $('#vh360-save-course-btn');
                $submitBtn.prop('disabled', true).addClass('loading');

                var formData = new FormData(this);
                formData.append('action', 'vh360_save_course_frontend');
                formData.append('nonce', vh360Dashboard.nonce);

                $.ajax({
                    url: vh360Dashboard.ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            self.showNotification(response.data.message, 'success');
                            $formWrap.slideUp(300);
                            self.resetCourseForm();
                            // Reload page to refresh courses list.
                            setTimeout(function() {
                                window.location.reload();
                            }, 1200);
                        } else {
                            self.showNotification(response.data.message || 'An error occurred. Please try again.', 'error');
                        }
                    },
                    error: function() {
                        self.showNotification('An error occurred. Please try again.', 'error');
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).removeClass('loading');
                    }
                });

                return false;
            });
        },

        /**
         * Reset the course form to create mode.
         */
        resetCourseForm: function() {
            var $form = $('#vh360-course-form');
            $form[0].reset();
            $('#vh360_course_id').val('');
            $('#vh360_remove_course_image').val('');
            $('#vh360-course-image-preview').hide();
            $('#vh360-course-preview-img').attr('src', '');
            $('#vh360-course-existing-image').hide();
            $('#vh360-course-existing-img').attr('src', '');
            $('#vh360-course-form-heading').text(
                typeof vh360Dashboard !== 'undefined' && vh360Dashboard.i18n && vh360Dashboard.i18n.createCourse
                    ? vh360Dashboard.i18n.createCourse
                    : 'Create Course'
            );
        },

        /**
         * Handle course edit and delete button actions.
         */
        handleCourseActions: function() {
            var self = this;
            var $formWrap = $('#vh360-course-form-wrap');

            // Edit button — prefill form with existing data.
            $(document).on('click', '.vh360-course-edit-btn', function() {
                var courseData = $(this).data('course');
                if (!courseData) return;

                // Populate form fields.
                $('#vh360_course_id').val(courseData.id);
                $('#vh360_course_name').val(courseData.name);
                $('#vh360_course_description').val(courseData.description);
                $('#vh360_course_subtitle').val(courseData.subtitle);
                $('#vh360_course_short_description').val(courseData.short_desc);
                $('#vh360_course_level').val(courseData.level);
                $('#vh360_course_duration').val(courseData.duration);
                $('#vh360_course_purchase_mode').val(courseData.purchase_mode || 'none');
                $('#vh360_course_required_membership').val(courseData.membership);
                $('#vh360_course_product_id').val(courseData.product_id || '');
                $('#vh360_course_cta_text').val(courseData.cta_text);
                $('#vh360_course_cta_url').val(courseData.cta_url);
                $('#vh360_course_order').val(courseData.order);

                // Reset image state.
                $('#vh360_remove_course_image').val('');
                $('#vh360-course-image-preview').hide();
                $('#vh360-course-preview-img').attr('src', '');

                if (courseData.image_url) {
                    $('#vh360-course-existing-img').attr('src', courseData.image_url);
                    $('#vh360-course-existing-image').show();
                } else {
                    $('#vh360-course-existing-image').hide();
                }

                // Update form heading to "Edit Course".
                var heading = typeof vh360Dashboard !== 'undefined' && vh360Dashboard.i18n && vh360Dashboard.i18n.editCourse
                    ? vh360Dashboard.i18n.editCourse
                    : 'Edit Course';
                $('#vh360-course-form-heading').text(heading);

                // Show form and scroll to it.
                $formWrap.slideDown(300);
                $('html, body').animate({ scrollTop: $formWrap.offset().top - 80 }, 300);
            });

            // Delete button.
            $(document).on('click', '.vh360-course-delete-btn', function() {
                var courseId   = $(this).data('course-id');
                var courseName = $(this).data('course-name');

                self.confirmAction(
                    'Are you sure you want to delete "' + courseName + '"? This action cannot be undone.',
                    function(confirmed) {
                        if (!confirmed) return;

                        $.ajax({
                            url: vh360Dashboard.ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'vh360_delete_course_frontend',
                                nonce: vh360Dashboard.nonce,
                                course_id: courseId
                            },
                            success: function(response) {
                                if (response.success) {
                                    self.showNotification(response.data.message, 'success');
                                    setTimeout(function() {
                                        window.location.reload();
                                    }, 1200);
                                } else {
                                    self.showNotification(response.data.message || 'Failed to delete course.', 'error');
                                }
                            },
                            error: function() {
                                self.showNotification('An error occurred. Please try again.', 'error');
                            }
                        });
                    }
                );
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        VH360Dashboard.init();
        VH360Dashboard.initCreateVideoForm();
    });

})(jQuery);

// Playlist Dashboard Functionality
(function($) {
    'use strict';
    
    var PlaylistDashboard = {
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Create playlist button (dashboard version)
            $(document).on('click', '.vh360-create-playlist-dashboard-btn', function(e) {
                e.preventDefault();
                self.showCreateModal();
            });
            
            // Close create modal
            $(document).on('click', '#vh360-create-playlist-dashboard-close, #vh360-dashboard-cancel-playlist', function(e) {
                e.preventDefault();
                self.hideCreateModal();
            });
            
            // Submit create playlist
            $(document).on('click', '#vh360-dashboard-submit-playlist', function(e) {
                e.preventDefault();
                self.createPlaylist();
            });
            
            // Delete playlist
            $(document).on('click', '.vh360-delete-playlist-btn', function(e) {
                e.preventDefault();
                var playlistId = $(this).data('playlist-id');
                self.deletePlaylist(playlistId);
            });
            
            // Remove video from playlist
            $(document).on('click', '.vh360-remove-from-playlist-btn', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var playlistId = $btn.data('playlist-id');
                var videoId = $btn.data('video-id');
                self.removeFromPlaylist(playlistId, videoId, $btn);
            });
            
            // Enter key submits create form
            $(document).on('keypress', '#vh360-dashboard-playlist-title', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    self.createPlaylist();
                }
            });
            
            // Close modal on overlay click
            $(document).on('click', '#vh360-create-playlist-dashboard-modal', function(e) {
                if (e.target === this) {
                    self.hideCreateModal();
                }
            });
        },
        
        showCreateModal: function() {
            $('#vh360-create-playlist-dashboard-modal').fadeIn(200);
            $('#vh360-dashboard-playlist-title').focus();
        },
        
        hideCreateModal: function() {
            $('#vh360-create-playlist-dashboard-modal').fadeOut(200);
            $('#vh360-dashboard-playlist-title').val('');
            $('#vh360-dashboard-playlist-description').val('');
        },
        
        createPlaylist: function() {
            var title = $('#vh360-dashboard-playlist-title').val().trim();
            var description = $('#vh360-dashboard-playlist-description').val().trim();
            
            if (!title) {
                return;
            }
            
            if (typeof vh360Dashboard === 'undefined' || !vh360Dashboard.ajaxurl) {
                return;
            }
            
            var $submitBtn = $('#vh360-dashboard-submit-playlist');
            $submitBtn.prop('disabled', true);
            
            $.ajax({
                url: vh360Dashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_create_playlist',
                    title: title,
                    description: description,
                    nonce: vh360Dashboard.playlistNonce
                },
                success: function(response) {
                    if (response.success) {
                        // Reload page to show new playlist
                        window.location.reload();
                    }
                },
                error: function() {
                    $submitBtn.prop('disabled', false);
                },
                complete: function() {
                    $submitBtn.prop('disabled', false);
                }
            });
        },
        
        deletePlaylist: function(playlistId) {
            if (!confirm('Are you sure you want to delete this playlist? This action cannot be undone.')) {
                return;
            }
            
            if (typeof vh360Dashboard === 'undefined' || !vh360Dashboard.ajaxurl) {
                return;
            }
            
            $.ajax({
                url: vh360Dashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_delete_playlist',
                    playlist_id: playlistId,
                    nonce: vh360Dashboard.playlistNonce
                },
                success: function(response) {
                    if (response.success) {
                        // Redirect back to playlists list
                        window.location.href = '?tab=playlists';
                    }
                }
            });
        },
        
        removeFromPlaylist: function(playlistId, videoId, $btn) {
            if (!confirm('Remove this video from the playlist?')) {
                return;
            }
            
            if (typeof vh360Dashboard === 'undefined' || !vh360Dashboard.ajaxurl) {
                return;
            }
            
            $btn.prop('disabled', true);
            
            $.ajax({
                url: vh360Dashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_remove_from_playlist',
                    playlist_id: playlistId,
                    video_id: videoId,
                    nonce: vh360Dashboard.playlistNonce
                },
                success: function(response) {
                    if (response.success) {
                        // Remove the video item from DOM
                        $btn.closest('.vh360-playlist-video-item').fadeOut(300, function() {
                            $(this).remove();
                            // If no more videos, show empty state
                            if ($('.vh360-playlist-video-item').length === 0) {
                                window.location.reload();
                            }
                        });
                    } else {
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                }
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        PlaylistDashboard.init();
    });
    
})(jQuery);
