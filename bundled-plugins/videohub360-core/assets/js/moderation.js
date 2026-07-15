/* VideoHub360 patched: debug flag + vh360 namespace (non-destructive) */
if (typeof window !== 'undefined') {
  window.vh360 = window.vh360 || {};
  window.__VH360_DEBUG = window.__VH360_DEBUG || false;
}

// == VideoHub360 Moderation Panel ==
(function() {
    if (window.__VH360_DEBUG) console.log('VideoHub360: Initializing moderation panel');
    if (window.__VH360_DEBUG) console.log('VideoHub360: vh360Data available:', !!vh360Data);
    if (window.__VH360_DEBUG) console.log('VideoHub360: canModerate:', vh360Data ? vh360Data.canModerate : 'vh360Data not available');
    
    // Only initialize if user has moderation permissions
    if (!vh360Data || !vh360Data.canModerate) {
        if (window.__VH360_DEBUG) console.log('VideoHub360: Moderation panel not initialized - no permissions');
        return;
    }
    
    if (window.__VH360_DEBUG) console.log('VideoHub360: Moderation panel initialization continuing...');

    var moderationPanelBtns = document.querySelectorAll('.videohub360-moderation-panel-btn');
    var moderationModal = document.getElementById('vh360-moderation-modal');
    var moderationModalClose = document.getElementById('vh360-moderation-modal-close');
    var moderationLoading = document.getElementById('vh360-moderation-loading');
    var moderationContent = document.getElementById('vh360-moderation-content');
    var moderationError = document.getElementById('vh360-moderation-error');
    var moderationRetry = document.getElementById('vh360-moderation-retry');

    // Data containers - updated for unified interface
    var chatBannedUsersList = document.getElementById('vh360-chat-banned-users-list');
    var chatTimeoutUsersList = document.getElementById('vh360-chat-timeout-users-list');
    var agoraBannedUsersList = document.getElementById('vh360-agora-banned-users-list');
    var agoraTimeoutUsersList = document.getElementById('vh360-agora-timeout-users-list');
    
    var chatBannedCount = document.getElementById('vh360-chat-banned-count');
    var chatTimeoutCount = document.getElementById('vh360-chat-timeout-count');
    var agoraBannedCount = document.getElementById('vh360-agora-banned-count');
    var agoraTimeoutCount = document.getElementById('vh360-agora-timeout-count');
    
    // Legacy elements for backward compatibility
    var bannedUsersList = document.getElementById('vh360-banned-users-list');
    var timeoutUsersList = document.getElementById('vh360-timeout-users-list');
    var bannedCount = document.getElementById('vh360-banned-count');
    var timeoutCount = document.getElementById('vh360-timeout-count');

    if (!moderationModal) {
        return; // No moderation modal found
    }

    // Handle fullscreen changes - move moderation modal inside/outside fullscreen element
    var agoraPlayer = document.getElementById('vh360-agora-player');
    if (agoraPlayer && moderationModal) {
        // Helper function to check if we're in fullscreen mode
        function isInFullscreenMode() {
            return !!(document.fullscreenElement || 
                     document.webkitFullscreenElement || 
                     document.mozFullScreenElement || 
                     document.msFullscreenElement);
        }
        
        // Helper function to handle fullscreen change
        function handleFullscreenChange() {
            if (isInFullscreenMode()) {
                // Entering fullscreen - move moderation modal inside the fullscreen element
                var fullscreenElement = document.fullscreenElement || 
                                       document.webkitFullscreenElement || 
                                       document.mozFullScreenElement || 
                                       document.msFullscreenElement;
                
                if (fullscreenElement && fullscreenElement.id === 'vh360-agora-player') {
                    fullscreenElement.appendChild(moderationModal);
                    if (window.__VH360_DEBUG) console.log('VideoHub360: Moderation modal moved inside fullscreen element');
                }
            } else {
                // Exiting fullscreen - move moderation modal back to document.body
                if (moderationModal.parentNode !== document.body) {
                    document.body.appendChild(moderationModal);
                    if (window.__VH360_DEBUG) console.log('VideoHub360: Moderation modal moved back to document.body');
                }
            }
        }
        
        // Listen for all fullscreen change events (cross-browser)
        document.addEventListener('fullscreenchange', handleFullscreenChange);
        document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
        document.addEventListener('mozfullscreenchange', handleFullscreenChange);
        document.addEventListener('MSFullscreenChange', handleFullscreenChange);
        
        if (window.__VH360_DEBUG) console.log('VideoHub360: Fullscreen change listeners added for moderation modal');
    }

    var cachedModerationData = null;
    var isModalOpen = false;
    var refreshInterval = null;

    // Open moderation panel
    function openModerationPanel() {
        if (isModalOpen) return;
        
        isModalOpen = true;
        moderationModal.style.display = 'flex';
        if (window.VH360ScrollContext && window.VH360ScrollContext.lock) { window.VH360ScrollContext.lock('moderation-panel'); } else { document.body.style.overflow = 'hidden'; }
        
        // Show loading state
        showLoadingState();
        
        // Fetch moderation data
        fetchModerationData();
        
        // Set up auto-refresh every 5 seconds
        refreshInterval = (function(){ var __id = setInterval(fetchModerationData, 5000); (window.__vh360Intervals = window.__vh360Intervals || []).push(__id); return __id; })();
    }

    // Close moderation panel
    function closeModerationPanel() {
        if (!isModalOpen) return;
        
        isModalOpen = false;
        moderationModal.style.display = 'none';
        if (window.VH360ScrollContext && window.VH360ScrollContext.unlock) { window.VH360ScrollContext.unlock('moderation-panel'); } else { document.body.style.overflow = ''; }
        
        // Clear auto-refresh
        if (refreshInterval) {
            clearInterval(refreshInterval);
            refreshInterval = null;
        }
    }

    // Show loading state
    function showLoadingState() {
        if (moderationLoading) moderationLoading.style.display = 'block';
        if (moderationContent) moderationContent.style.display = 'none';
        if (moderationError) moderationError.style.display = 'none';
    }

    // Show content state
    function showContentState() {
        if (moderationLoading) moderationLoading.style.display = 'none';
        if (moderationContent) moderationContent.style.display = 'block';
        if (moderationError) moderationError.style.display = 'none';
    }

    // Show error state
    function showErrorState() {
        if (moderationLoading) moderationLoading.style.display = 'none';
        if (moderationContent) moderationContent.style.display = 'none';
        if (moderationError) moderationError.style.display = 'block';
    }

    // Fetch moderation data
    function fetchModerationData() {
        var formData = new FormData();
        formData.append('action', 'videohub360_get_moderated_users');
        formData.append('nonce', vh360Data.moderationNonce || vh360Data.chatNonce);
        formData.append('post_id', vh360Data.postId);

        fetch(vh360Data.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                cachedModerationData = data.data;
                updateModerationDisplay(data.data);
                showContentState();
            } else {
                if (window.__VH360_DEBUG) console.error('VideoHub360: Failed to fetch moderation data:', data.data);
                showErrorState();
            }
        })
        .catch(function(error) {
            if (window.__VH360_DEBUG) console.error('VideoHub360: Error fetching moderation data:', error);
            showErrorState();
        });
    }

    // Update moderation display - enhanced for unified interface
    function updateModerationDisplay(data) {
        // Update chat counts
        if (chatBannedCount) {
            chatBannedCount.textContent = data.chat_banned_count || 0;
        }
        if (chatTimeoutCount) {
            chatTimeoutCount.textContent = data.chat_timeout_count || 0;
        }
        
        // Update Agora counts
        if (agoraBannedCount) {
            agoraBannedCount.textContent = data.agora_banned_count || 0;
        }
        if (agoraTimeoutCount) {
            agoraTimeoutCount.textContent = data.agora_timeout_count || 0;
        }
        
        // Legacy count updates for backward compatibility
        if (bannedCount) {
            bannedCount.textContent = data.total_banned || 0;
        }
        if (timeoutCount) {
            timeoutCount.textContent = data.total_timeouts || 0;
        }

        // Update chat banned users list
        if (chatBannedUsersList) {
            if (!data.chat_banned_users || data.chat_banned_users.length === 0) {
                chatBannedUsersList.innerHTML = '<p class="vh360-no-items">No chat banned users</p>';
            } else {
                chatBannedUsersList.innerHTML = '';
                data.chat_banned_users.forEach(function(user) {
                    var userElement = createModerationUserElement(user, 'ban', 'chat');
                    chatBannedUsersList.appendChild(userElement);
                });
            }
        }

        // Update chat timed out users list
        if (chatTimeoutUsersList) {
            if (!data.chat_timed_out_users || data.chat_timed_out_users.length === 0) {
                chatTimeoutUsersList.innerHTML = '<p class="vh360-no-items">No chat timed out users</p>';
            } else {
                chatTimeoutUsersList.innerHTML = '';
                data.chat_timed_out_users.forEach(function(user) {
                    var userElement = createModerationUserElement(user, 'timeout', 'chat');
                    chatTimeoutUsersList.appendChild(userElement);
                });
            }
        }
        
        // Update Agora banned users list
        if (agoraBannedUsersList) {
            if (!data.agora_banned_users || data.agora_banned_users.length === 0) {
                agoraBannedUsersList.innerHTML = '<p class="vh360-no-items">No video banned users</p>';
            } else {
                agoraBannedUsersList.innerHTML = '';
                data.agora_banned_users.forEach(function(user) {
                    var userElement = createModerationUserElement(user, 'ban', 'agora');
                    agoraBannedUsersList.appendChild(userElement);
                });
            }
        }

        // Update Agora timed out users list
        if (agoraTimeoutUsersList) {
            if (!data.agora_timed_out_users || data.agora_timed_out_users.length === 0) {
                agoraTimeoutUsersList.innerHTML = '<p class="vh360-no-items">No video timed out users</p>';
            } else {
                agoraTimeoutUsersList.innerHTML = '';
                data.agora_timed_out_users.forEach(function(user) {
                    var userElement = createModerationUserElement(user, 'timeout', 'agora');
                    agoraTimeoutUsersList.appendChild(userElement);
                });
            }
        }
        
        // Legacy list updates for backward compatibility
        if (bannedUsersList) {
            if (!data.banned_users || data.banned_users.length === 0) {
                bannedUsersList.innerHTML = '<p class="vh360-no-items">No banned users</p>';
            } else {
                bannedUsersList.innerHTML = '';
                data.banned_users.forEach(function(user) {
                    var userElement = createModerationUserElement(user, 'ban');
                    bannedUsersList.appendChild(userElement);
                });
            }
        }

        if (timeoutUsersList) {
            if (!data.timed_out_users || data.timed_out_users.length === 0) {
                timeoutUsersList.innerHTML = '<p class="vh360-no-items">No timed out users</p>';
            } else {
                timeoutUsersList.innerHTML = '';
                data.timed_out_users.forEach(function(user) {
                    var userElement = createModerationUserElement(user, 'timeout');
                    timeoutUsersList.appendChild(userElement);
                });
            }
        }
    }

    // Create moderation user element - enhanced for unified interface
    function createModerationUserElement(user, type, sourceType) {
        var div = document.createElement('div');
        div.className = 'vh360-moderation-item';
        div.dataset.moderationId = user.id;
        div.dataset.userId = user.target_user_id;
        div.dataset.sourceType = sourceType || user.source_type || 'chat';

        var header = document.createElement('div');
        header.className = 'vh360-moderation-item-header';

        var info = document.createElement('div');
        info.className = 'vh360-moderation-item-info';

        var username = document.createElement('h5');
        username.className = 'vh360-moderation-username';
        
        // Add source type indicator to username
        var actualSourceType = sourceType || user.source_type || 'chat';
        var sourceIcon = actualSourceType === 'agora' ? '🎥' : '💬';
        var sourceLabel = actualSourceType === 'agora' ? 'Video Chat' : 'Text Chat';
        username.textContent = user.username + ' (' + sourceLabel + ')';
        
        info.appendChild(username);

        if (user.reason) {
            var reason = document.createElement('p');
            reason.className = 'vh360-moderation-reason';
            reason.textContent = 'Reason: ' + user.reason;
            info.appendChild(reason);
        }

        var meta = document.createElement('div');
        meta.className = 'vh360-moderation-meta';
        
        var dateSpan = document.createElement('span');
        dateSpan.textContent = 'Date: ' + user.date;
        meta.appendChild(dateSpan);

        var moderatorSpan = document.createElement('span');
        moderatorSpan.textContent = 'By: ' + user.moderator;
        meta.appendChild(moderatorSpan);

        if (type === 'timeout' && user.expiration) {
            var expirationSpan = document.createElement('span');
            expirationSpan.className = 'vh360-moderation-expiration';
            expirationSpan.textContent = 'Expires: ' + user.expiration;
            
            // Check if expired
            if (user.expiration_time) {
                var expireDate = new Date(user.expiration_time);
                var now = new Date();
                if (expireDate <= now) {
                    expirationSpan.className += ' expired';
                    expirationSpan.textContent = 'Expired: ' + user.expiration;
                }
            }
            
            meta.appendChild(expirationSpan);
        }

        info.appendChild(meta);
        header.appendChild(info);

        // Action buttons with context-specific text
        var actions = document.createElement('div');
        actions.className = 'vh360-moderation-actions';

        if (type === 'ban') {
            var unbanBtn = document.createElement('button');
            unbanBtn.className = 'vh360-moderation-action-btn vh360-unban-btn';
            unbanBtn.textContent = actualSourceType === 'agora' ? 'Unban from Video' : 'Unban from Chat';
            unbanBtn.onclick = function() {
                performModerationAction('unban', user.id, user.username, actualSourceType);
            };
            actions.appendChild(unbanBtn);
        } else if (type === 'timeout') {
            var removeTimeoutBtn = document.createElement('button');
            removeTimeoutBtn.className = 'vh360-moderation-action-btn vh360-remove-timeout-btn';
            removeTimeoutBtn.textContent = actualSourceType === 'agora' ? 'Remove Video Timeout' : 'Remove Chat Timeout';
            removeTimeoutBtn.onclick = function() {
                performModerationAction('remove_timeout', user.id, user.username, actualSourceType);
            };
            actions.appendChild(removeTimeoutBtn);
        }

        header.appendChild(actions);
        div.appendChild(header);

        return div;
    }

    // Perform moderation action - enhanced for unified interface
    function performModerationAction(action, moderationId, username, sourceType) {
        sourceType = sourceType || 'chat';
        var actionText = action === 'unban' ? 'unban' : 'remove timeout for';
        var sourceText = sourceType === 'agora' ? ' from video chat' : ' from text chat';
        
        if (!confirm('Are you sure you want to ' + actionText + ' user "' + username + '"' + sourceText + '?')) {
            return;
        }

        var actionElement = document.querySelector('[data-moderation-id="' + moderationId + '"]');
        if (actionElement) {
            actionElement.classList.add('processing');
        }

        var actionName = action === 'unban' ? 'videohub360_unban_user' : 'videohub360_remove_timeout';
        var paramName = action === 'unban' ? 'ban_id' : 'timeout_id';

        var formData = new FormData();
        formData.append('action', actionName);
        formData.append('nonce', vh360Data.moderationNonce || vh360Data.chatNonce);
        formData.append('post_id', vh360Data.postId);
        formData.append(paramName, moderationId);

        fetch(vh360Data.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                var successMessage = data.data.message || 'Action completed successfully.';
                showModerationMessage(successMessage, 'success');
                
                // Remove the item from the UI immediately
                if (actionElement) {
                    actionElement.remove();
                }
                
                // Refresh data to update counts
                fetchModerationData();
            } else {
                showModerationMessage(data.data || 'Action failed. Please try again.', 'error');
                if (actionElement) {
                    actionElement.classList.remove('processing');
                }
            }
        })
        .catch(function(error) {
            if (window.__VH360_DEBUG) console.error('VideoHub360: Moderation action error:', error);
            showModerationMessage('Network error. Please try again.', 'error');
            if (actionElement) {
                actionElement.classList.remove('processing');
            }
        });
    }

    // Show moderation message
    function showModerationMessage(message, type) {
        var messageEl = document.createElement('div');
        messageEl.className = 'vh360-moderation-message ' + type;
        messageEl.textContent = message;
        
        document.body.appendChild(messageEl);
        
        setTimeout(function() {
            if (messageEl.parentNode) {
                messageEl.remove();
            }
        }, 5000);
    }

    // Event listeners
    moderationPanelBtns.forEach(function(btn) {
        btn.addEventListener('click', openModerationPanel);
    });

    if (moderationModalClose) {
        moderationModalClose.addEventListener('click', closeModerationPanel);
    }

    if (moderationRetry) {
        moderationRetry.addEventListener('click', function() {
            showLoadingState();
            fetchModerationData();
        });
    }

    // Close modal when clicking outside
    if (moderationModal) {
        moderationModal.addEventListener('click', function(e) {
            if (e.target === moderationModal) {
                closeModerationPanel();
            }
        });
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isModalOpen) {
            closeModerationPanel();
        }
    });

    // Window resize handling for mobile
    window.addEventListener('resize', function() {
        if (isModalOpen && window.innerWidth <= 768) {
            // Adjust modal positioning for mobile if needed
        }
    });

    // Expose moderation panel API for debugging
    window.vh360ModerationPanel = {
        open: openModerationPanel,
        close: closeModerationPanel,
        refresh: fetchModerationData,
        getCachedData: function() { return cachedModerationData; },
        isOpen: function() { return isModalOpen; }
    };
})();


/* Namespacing compatibility: copy any existing window.* functions into window.vh360 (non-destructive) */
;(function(){
  window.vh360 = window.vh360 || {};
  var _names = ['addEventListener', 'innerWidth', 'vh360ModerationPanel'];
  _names.forEach(function(n){ try{ if (window[n] && !window.vh360[n]) window.vh360[n] = window[n]; }catch(e){} });
})();
