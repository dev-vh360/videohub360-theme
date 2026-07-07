/* VideoHub360 patched: debug flag + vh360 namespace (non-destructive) */
if (typeof window !== 'undefined') {
  window.vh360 = window.vh360 || {};
  window.__VH360_DEBUG = window.__VH360_DEBUG || false;
}

// Debug logging helpers - only log when __VH360_DEBUG is enabled
// Define on window to avoid redeclaration errors when multiple scripts load
if (typeof window !== 'undefined') {
  window.vh360Log = window.vh360Log || ((...args) => { 
    if (window.__VH360_DEBUG) console.log(...args); 
  });
  window.vh360Warn = window.vh360Warn || ((...args) => { 
    if (window.__VH360_DEBUG) console.warn(...args); 
  });
  window.vh360Error = window.vh360Error || ((...args) => { 
    if (window.__VH360_DEBUG) console.error(...args); 
  });
}

// == Live Chat JS ==
(function() {
    // Generic chat elements (works for all livestream types)
    var chatForm = document.getElementById('vh360-chat-form');
    var chatInput = document.getElementById('vh360-chat-input');
    var chatMessages = document.getElementById('vh360-chat-messages');
    var chatEmojiBtn = document.getElementById('vh360-chat-emoji-btn');
    var chatSendBtn = document.getElementById('vh360-chat-send-btn');
    var chatPopup = document.getElementById('vh360-chat-popup');
    var chatOpenBtn = document.getElementById('videohub360-open-chat-btn');
    var chatInline = document.getElementById('vh360-chat-inline');
    
    var loginTriggers = document.querySelectorAll('.videohub360-chat-login-trigger');
    var loginModal = document.getElementById('videohub360-login-modal');
    var loginModalClose = document.getElementById('videohub360-login-modal-close');
    var loginModalBody = document.getElementById('videohub360-login-modal-body');

    // Check if we have chat elements before proceeding
    if (!chatMessages) {
        return; // No chat elements found, exit early
    }
    
    // Determine chat mode (popup or inline)
    var chatMode = chatPopup ? 'popup' : (chatInline ? 'inline' : null);
    
    if (window.__VH360_DEBUG) {
        window.vh360Log('VideoHub360: Chat mode detected:', chatMode);
    }
    
    // For popup mode, move chat popup to document.body to ensure it appears above all WordPress theme elements
    if (chatMode === 'popup' && chatPopup && chatPopup.parentNode) {
        document.body.appendChild(chatPopup);
        if (window.__VH360_DEBUG) console.log('VideoHub360: Chat popup moved to document.body');
    }
    
    // Helper function to close chat popup
    function closeChatPopup() {
        if (chatPopup) {
            chatPopup.classList.remove('is-open');
            chatPopup.classList.add('is-hidden');
            chatPopup.setAttribute('aria-hidden', 'true');
            if (chatOpenBtn) {
                chatOpenBtn.classList.remove('active');
            }
        }
    }
    
    // Helper function to open chat popup
    function openChatPopup() {
        if (chatPopup) {
            chatPopup.classList.remove('is-hidden');
            chatPopup.classList.add('is-open');
            chatPopup.setAttribute('aria-hidden', 'false');
            if (chatOpenBtn) {
                chatOpenBtn.classList.add('active');
            }
            // Focus management for accessibility
            // Timeout allows the popup animation to complete before setting focus
            // This ensures screen readers properly announce the chat interface
            if (chatInput && !chatInput.disabled) {
                setTimeout(function() {
                    chatInput.focus();
                }, 100);
            }
            scrollToBottom();
        }
    }
    
    // Chat popup toggle functionality (for popup mode)
    if (chatMode === 'popup' && chatOpenBtn && chatPopup) {
        chatOpenBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (chatPopup.classList.contains('is-hidden')) {
                openChatPopup();
            } else {
                closeChatPopup();
            }
        });
        
        // Close button in chat header
        var chatCloseBtn = document.getElementById('vh360-chat-close-btn');
        if (chatCloseBtn) {
            chatCloseBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeChatPopup();
            });
        }
        
        // Close chat popup when clicking on backdrop
        chatPopup.addEventListener('click', function(e) {
            // Only close if clicking directly on the backdrop, not on content
            if (e.target === chatPopup) {
                closeChatPopup();
            }
        });
        
        // Also keep the document-level click handler for other scenarios
        document.addEventListener('click', function(e) {
            // Don't close if clicking on any of these elements:
            // - Message menu
            // - Moderation modal
            // - Private message modal
            // - Emoji picker
            var isMessageMenu = e.target.closest('.videohub360-chat-message-menu');
            var isModerationModal = e.target.closest('.videohub360-moderation-modal');
            var isPrivateMessageModal = e.target.closest('.videohub360-private-message-modal');
            var isEmojiPicker = e.target.closest('.videohub360-emoji-picker');
            
            if (!chatPopup.classList.contains('is-hidden') && 
                !chatPopup.contains(e.target) && 
                (!chatOpenBtn || !chatOpenBtn.contains(e.target)) &&
                !isMessageMenu &&
                !isModerationModal &&
                !isPrivateMessageModal &&
                !isEmojiPicker) {
                closeChatPopup();
            }
        });
    }
    
    // Handle logout button click in chat (remove inline onclick handler)
    var chatLogoutBtn = document.querySelector('.videohub360-chat-logout-btn');
    if (chatLogoutBtn) {
        chatLogoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var logoutUrl = this.getAttribute('data-logout-url');
            if (logoutUrl) {
                window.location.href = logoutUrl;
            }
        });
    }

    // Check if vh360Data is available
    if (typeof vh360Data === 'undefined') {
        if (window.__VH360_DEBUG) console.warn('VideoHub360: vh360Data not found, chat functionality disabled');
        return;
    }

    var isUserLoggedIn = vh360Data.isUserLoggedIn;
    var currentUserName = vh360Data.userDisplayName;
    var currentUserAvatar = vh360Data.userAvatar;
    var currentUserId = vh360Data.userId;
    var postId = vh360Data.postId;
    var ajaxUrl = vh360Data.ajaxUrl;
    var chatNonce = vh360Data.chatNonce;
    var chatMessageLimit = vh360Data.chatMessageLimit || 500;
    var allowChatPolling = !!vh360Data.allowChatPolling;
    var allowChatPosting = !!vh360Data.allowChatPosting;
    var chatRuntimeMode = vh360Data.chatMode || 'active';
    if (window.__VH360_DEBUG) {
        window.vh360Log('VideoHub360: Chat runtime mode:', chatRuntimeMode, 'polling:', allowChatPolling, 'posting:', allowChatPosting);
    }

    var messages = [];
    var lastMessageId = 0;
    var isPolling = false;
    var pollInterval = null;
    var replyingTo = null;
    var privateMessagingAvailable = false; // Track if private messaging is available

    // Helper functions for chat
    function scrollToBottom() {
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }
    
    function updateLastMessageId() {
        // Find the highest message ID from all messages
        var messageElements = document.querySelectorAll('.videohub360-chat-message[data-message-id]');
        messageElements.forEach(function(element) {
            var messageId = parseInt(element.dataset.messageId);
            if (messageId > lastMessageId) {
                lastMessageId = messageId;
            }
        });
    }

    // Modal functions
    function showLoginModal() {
        // Check vh360Data for login modal settings
        if (typeof vh360Data !== 'undefined' && vh360Data.loginModalType) {
            switch (vh360Data.loginModalType) {
                case 'redirect':
                    handleRedirectLogin();
                    return;
                case 'javascript':
                    handleJavaScriptLogin();
                    return;
                case 'shortcode':
                    // Shortcode content is already rendered in PHP, just show modal
                    loginModal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                    break;
                default: // 'default'
                    loginModal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                    break;
            }
        } else {
            // Fallback to default behavior
            loginModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
    
    function handleRedirectLogin() {
        var redirectUrl = vh360Data.loginModalRedirectUrl || vh360Data.userLoginUrl;
        
        // Add return URL parameter
        var currentUrl = encodeURIComponent(window.location.href);
        var separator = redirectUrl.includes('?') ? '&' : '?';
        var finalUrl = redirectUrl + separator + 'redirect_to=' + currentUrl;
        
        window.location.href = finalUrl;
    }
    
    function handleJavaScriptLogin() {
        var jsFunction = vh360Data.loginModalJsFunction;
        
        if (jsFunction && typeof window[jsFunction] === 'function') {
            // Call the specified function
            try {
                window[jsFunction]();
            } catch (error) {
                if (window.__VH360_DEBUG) console.error('VideoHub360: Error calling login function ' + jsFunction + ':', error);
                // Fallback to event dispatch
                dispatchLoginEvent();
            }
        } else {
            // Dispatch custom event for theme integration
            dispatchLoginEvent();
        }
    }
    
    function dispatchLoginEvent() {
        // Dispatch custom event that themes can listen for
        var loginEvent = new CustomEvent('videohub360:login-required', {
            detail: {
                source: 'livechat',
                returnUrl: window.location.href,
                userId: vh360Data.userId || null,
                postId: vh360Data.postId || null
            }
        });
        
        document.dispatchEvent(loginEvent);
        window.dispatchEvent(loginEvent);
        
        // Also try dispatching on document.body for broader compatibility
        if (document.body) {
            document.body.dispatchEvent(loginEvent);
        }
        
        if (window.__VH360_DEBUG) console.log('VideoHub360: Dispatched login-required event for theme integration');
    }
    function closeLoginModal() {
        loginModal.classList.remove('active');
        document.body.style.overflow = '';
    }
    if (loginModalClose) loginModalClose.addEventListener('click', closeLoginModal);
    if (loginModal) {
        loginModal.addEventListener('click', function(e) {
            if (e.target === loginModal) closeLoginModal();
        });
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && loginModal.classList.contains('active')) closeLoginModal();
    });
    loginTriggers.forEach(function(trigger) {
        trigger.addEventListener('click', showLoginModal);
    });
    
    // Add event listener for the login action button (used in redirect/javascript modes)
    var loginActionBtn = document.getElementById('videohub360-login-action-btn');
    if (loginActionBtn) {
        loginActionBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (vh360Data.loginModalType === 'redirect') {
                handleRedirectLogin();
            } else if (vh360Data.loginModalType === 'javascript') {
                handleJavaScriptLogin();
            }
        });
    }

    // Built-in login form handler
    const builtinLoginForm = document.getElementById('videohub360-builtin-login-form');
    if (builtinLoginForm) {
        builtinLoginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form elements
            const submitBtn = document.getElementById('vh360-login-submit');
            const messageDiv = document.getElementById('vh360-login-message');
            const usernameInput = document.getElementById('vh360-username');
            const passwordInput = document.getElementById('vh360-password');
            const rememberCheckbox = document.getElementById('vh360-remember');
            
            // Get values
            const username = usernameInput.value.trim();
            const password = passwordInput.value;
            const remember = rememberCheckbox.checked ? '1' : '0';
            
            // Disable submit button and show loading state
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = vh360Data.loadingText || 'Logging in...';
            
            // Hide message and reset classes
            messageDiv.className = 'vh360-form-message';
            
            // Build form data
            const formData = new FormData();
            formData.append('action', 'videohub360_builtin_login');
            formData.append('videohub360_login_nonce', document.querySelector('[name="videohub360_login_nonce"]').value);
            formData.append('username', username);
            formData.append('password', password);
            formData.append('remember', remember);
            formData.append('redirect_to', window.location.href);
            
            // Send AJAX request
            fetch(vh360Data.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    messageDiv.className = 'vh360-form-message vh360-success vh360-visible';
                    messageDiv.textContent = data.data.message;
                    
                    // Redirect after short delay
                    setTimeout(function() {
                        window.location.href = data.data.redirect_to;
                    }, 500);
                } else {
                    // Show error message
                    messageDiv.className = 'vh360-form-message vh360-error vh360-visible';
                    messageDiv.textContent = data.data.message;
                    
                    // Re-enable submit button
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    
                    // Clear and focus password field
                    passwordInput.value = '';
                    passwordInput.focus();
                }
            })
            .catch(error => {
                if (window.__VH360_DEBUG) {
                    window.vh360Error('Login error:', error);
                }
                
                // Show generic error message
                messageDiv.className = 'vh360-form-message vh360-error vh360-visible';
                messageDiv.textContent = vh360Data.networkErrorText || 'Network error. Please try again.';
                
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    }

    // Chat AJAX
    function postMessageToServer(message) {
        if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: postMessageToServer called with message:', message);
        if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: replyingTo:', replyingTo);
        if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: isUserLoggedIn:', isUserLoggedIn);
        
        if (!isUserLoggedIn) {
            if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: User not logged in, showing login modal');
            showLoginModal();
            return;
        }
        
        // Optional: Check moderation status before sending (provides faster feedback)
        if (typeof checkChatModerationStatus === 'function') {
            checkChatModerationStatus().then(function(status) {
                if (!status.can_chat) {
                    showError(status.message || 'You are not allowed to post messages.');
                    return;
                }
                sendChatMessage(message);
            }).catch(function(error) {
                if (window.__VH360_DEBUG) console.warn('VideoHub360: Moderation check failed, sending anyway:', error);
                sendChatMessage(message);
            });
        } else {
            sendChatMessage(message);
        }
    }
    
    // Utility function to check chat moderation status
    function checkChatModerationStatus() {
        return new Promise(function(resolve, reject) {
            if (!isUserLoggedIn) {
                resolve({ can_chat: true }); // Non-logged-in users are handled server-side
                return;
            }
            
            var formData = new FormData();
            formData.append('action', 'videohub360_check_moderation_status');
            formData.append('post_id', postId);
            
            fetch(ajaxUrl, { method: 'POST', body: formData })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    resolve(data.data);
                } else {
                    reject(new Error(data.data || 'Moderation check failed'));
                }
            })
            .catch(reject);
        });
    }
    
    // Actual message sending function
    function sendChatMessage(message) {
        if (!allowChatPosting) {
            return;
        }
        // Test if this is a reply attempt
        if (replyingTo) {
            if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: This is a REPLY attempt. ReplyingTo message ID:', replyingTo);
        } else {
            if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: This is a REGULAR message (not a reply)');
        }
        
        // Disable chat input and emoji button
        if (chatInput) chatInput.disabled = true;
        if (chatEmojiBtn) chatEmojiBtn.disabled = true;
        
        var formData = new FormData();
        formData.append('action', 'videohub360_chat_post');
        formData.append('nonce', chatNonce);
        formData.append('post_id', postId);
        formData.append('message', message);
        if (replyingTo) {
            if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Adding reply_to to form data:', replyingTo);
            formData.append('reply_to', replyingTo);
        }
        
        if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Sending AJAX request to:', ajaxUrl);
        if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Form data contents:');
        for (var pair of formData.entries()) {
            if (window.__VH360_DEBUG) console.log('  ' + pair[0] + ': ' + pair[1]);
        }
        
        fetch(ajaxUrl, { method: 'POST', body: formData })
        .then(function(response) { 
            if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Response received, status:', response.status);
            return response.json(); 
        })
        .then(function(data) {
            if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Response data:', data);
            if (data.success) {
                if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Message posted successfully!');
                // Clear chat input
                if (chatInput) chatInput.value = '';
                cancelReply();
            } else {
                if (window.__VH360_DEBUG) console.error('VideoHub360 Chat Error:', data);
                showError(data.data || 'Failed to post message. Please try again.');
            }
        })
        .catch(function(error) {
            if (window.__VH360_DEBUG) console.error('VideoHub360 Network Error:', error);
            showError('Network error. Please check your connection and try again.');
        })
        .finally(function() {
            // Re-enable chat input and emoji button
            if (chatInput) chatInput.disabled = false;
            if (chatEmojiBtn) chatEmojiBtn.disabled = false;
            // Focus the input
            if (chatInput) chatInput.focus();
        });
    }
    
    function fetchMessagesFromServer() {
        var formData = new FormData();
        formData.append('action', 'videohub360_chat_fetch');
        formData.append('post_id', postId);
        formData.append('since_id', lastMessageId);
        fetch(ajaxUrl, { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success && data.data.messages && data.data.messages.length > 0) {
                data.data.messages.forEach(function(messageData) {
                    addChatMessage(messageData);
                });
            }
        })
        .catch(function(error) {
            if (window.__VH360_DEBUG) console.error('VideoHub360: Error fetching messages:', error);
        });
    }
    
    /**
     * Initial load of all chat messages with loading indicator and retry logic
     */
    function loadInitialMessages(retryCount) {
        retryCount = retryCount || 0;
        var maxRetries = 3;
        
        if (window.__VH360_DEBUG) console.log('VideoHub360: Loading initial messages (attempt ' + (retryCount + 1) + ')');
        
        // Show loading indicator
        showLoadingIndicator();
        
        var formData = new FormData();
        formData.append('action', 'videohub360_chat_fetch');
        formData.append('post_id', postId);
        formData.append('since_id', 0); // Fetch all messages
        
        fetch(ajaxUrl, { method: 'POST', body: formData })
        .then(function(response) { 
            if (!response.ok) {
                throw new Error('HTTP error ' + response.status);
            }
            return response.json(); 
        })
        .then(function(data) {
            hideLoadingIndicator();
            
            if (data.success) {
                if (data.data.messages && data.data.messages.length > 0) {
                    if (window.__VH360_DEBUG) console.log('VideoHub360: Loaded ' + data.data.messages.length + ' initial messages');
                    data.data.messages.forEach(function(messageData) {
                        addChatMessage(messageData);
                    });
                } else {
                    if (window.__VH360_DEBUG) console.log('VideoHub360: No messages to load');
                }
                
                // Start polling after successful initial load
                if (allowChatPolling) {
                    startPolling();
                }
            } else {
                throw new Error(data.data || 'Failed to fetch messages');
            }
        })
        .catch(function(error) {
            if (window.__VH360_DEBUG) console.error('VideoHub360: Error loading initial messages:', error);
            
            // Retry logic
            if (retryCount < maxRetries) {
                var retryDelay = Math.min(1000 * Math.pow(2, retryCount), 5000); // Exponential backoff, max 5s
                if (window.__VH360_DEBUG) console.log('VideoHub360: Retrying in ' + retryDelay + 'ms...');
                
                setTimeout(function() {
                    loadInitialMessages(retryCount + 1);
                }, retryDelay);
            } else {
                hideLoadingIndicator();
                showError('Failed to load chat messages. Please refresh the page.');
                if (window.__VH360_DEBUG) console.error('VideoHub360: Max retries reached, giving up');
            }
        });
    }
    
    /**
     * Show loading indicator in chat
     */
    function showLoadingIndicator() {
        if (!chatMessages) return;
        
        // Remove any existing loading indicator
        var existingIndicator = chatMessages.querySelector('.videohub360-chat-loading');
        if (existingIndicator) {
            existingIndicator.remove();
        }
        
        var loadingDiv = document.createElement('div');
        loadingDiv.className = 'videohub360-chat-loading';
        loadingDiv.innerHTML = '<div class="videohub360-chat-loading-spinner"></div><span>Loading messages...</span>';
        chatMessages.appendChild(loadingDiv);
    }
    
    /**
     * Hide loading indicator in chat
     */
    function hideLoadingIndicator() {
        if (!chatMessages) return;
        
        var loadingIndicator = chatMessages.querySelector('.videohub360-chat-loading');
        if (loadingIndicator) {
            loadingIndicator.remove();
        }
    }
    
    function addChatMessage(msg) {
        // Add to chat popup only
        if (!chatMessages) return;
        
        var div = document.createElement('div');
        div.className = 'videohub360-chat-message' + (msg.can_delete ? ' own' : '') + (msg.reply_to ? ' reply' : '') + (msg.is_pinned ? ' pinned' : '') + (msg.message_type === 'private' ? ' private' : '');
            div.dataset.messageId = msg.id;
            
            if (msg.is_pinned) {
                var pinIndicator = document.createElement('span');
                pinIndicator.className = 'videohub360-chat-pin-indicator';
                pinIndicator.textContent = '📌';
                div.appendChild(pinIndicator);
            }
            
            // Private message indicator
            if (msg.message_type === 'private') {
                var privateIndicator = document.createElement('span');
                privateIndicator.className = 'videohub360-chat-private-indicator';
                privateIndicator.textContent = '🔒';
                privateIndicator.title = 'Private Message';
                div.appendChild(privateIndicator);
                
                // Add recipient info for private messages
                var privateInfo = document.createElement('div');
                privateInfo.className = 'videohub360-chat-private-info';
                if (msg.user_id === currentUserId) {
                    privateInfo.textContent = '🔒 Private message to ' + (msg.recipient_username || 'User');
                } else {
                    privateInfo.textContent = '🔒 Private message from ' + msg.username;
                }
                div.appendChild(privateInfo);
            }
            
            if (msg.reply_to && msg.reply_to_username) {
                var replyInfo = document.createElement('div');
                replyInfo.className = 'videohub360-chat-reply-info';
                replyInfo.textContent = '↳ Replying to ' + msg.reply_to_username;
                div.appendChild(replyInfo);
            }
            
            if (msg.avatar) {
                var avatarDiv = document.createElement('div');
                avatarDiv.innerHTML = msg.avatar;
                var avatarImg = avatarDiv.querySelector('img');
                if (avatarImg) avatarImg.className = 'videohub360-chat-avatar';
                div.appendChild(avatarDiv);
            }
            
            var messageHeader = document.createElement('div');
            messageHeader.className = 'videohub360-chat-message-header';
            
            var usernameSpan = document.createElement('span');
            usernameSpan.className = 'videohub360-chat-username-display';
            usernameSpan.textContent = msg.username;
            messageHeader.appendChild(usernameSpan);
            
            var timestampSpan = document.createElement('span');
            timestampSpan.className = 'videohub360-chat-timestamp';
            timestampSpan.textContent = msg.timestamp;
            messageHeader.appendChild(timestampSpan);
            
            var contentDiv = document.createElement('div');
            contentDiv.className = 'videohub360-chat-message-content';
            contentDiv.innerHTML = msg.message.replace(/@(\w+)/g, '<span class="videohub360-chat-mention">@$1</span>');
            messageHeader.appendChild(contentDiv);
            
            // Add 3-dot menu button
            var menuBtn = document.createElement('button');
            menuBtn.className = 'videohub360-chat-menu-btn';
            menuBtn.innerHTML = '⋯';
            menuBtn.setAttribute('aria-label', 'Message options');
            menuBtn.setAttribute('tabindex', '0');
            menuBtn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                showMessageMenu(msg, menuBtn);
            };
            // Support keyboard navigation
            menuBtn.onkeydown = function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    e.stopPropagation();
                    showMessageMenu(msg, menuBtn);
                }
            };
            messageHeader.appendChild(menuBtn);
            
            div.appendChild(messageHeader);
            
            // Insert pinned messages at the top (after other pinned messages)
            // Non-pinned messages go at the bottom
            if (msg.is_pinned) {
                // Find the last pinned message
                var existingMessages = chatMessages.querySelectorAll('.videohub360-chat-message');
                var lastPinnedMessage = null;
                for (var i = 0; i < existingMessages.length; i++) {
                    if (existingMessages[i].classList.contains('pinned')) {
                        lastPinnedMessage = existingMessages[i];
                    } else {
                        // Stop when we hit the first non-pinned message
                        break;
                    }
                }
                
                if (lastPinnedMessage) {
                    // Insert after the last pinned message
                    lastPinnedMessage.parentNode.insertBefore(div, lastPinnedMessage.nextSibling);
                } else {
                    // No pinned messages yet, insert at the top
                    if (chatMessages.firstChild) {
                        chatMessages.insertBefore(div, chatMessages.firstChild);
                    } else {
                        chatMessages.appendChild(div);
                    }
                }
            } else {
                // Regular message, append to the end
                chatMessages.appendChild(div);
            }
            
            chatMessages.scrollTop = chatMessages.scrollHeight;
        
        messages.push(msg);
        if (msg.id > lastMessageId) lastMessageId = msg.id;
    }
    function showError(message) {
        // Show error in chat popup
        if (!chatMessages) return;
        
        var errorDiv = document.createElement('div');
        errorDiv.className = 'videohub360-chat-message system error';
        errorDiv.innerHTML = '<span style="color: #d32f2f; font-style: italic;">Error: ' + message + '</span>';
        chatMessages.appendChild(errorDiv);
        setTimeout(function() {
            if (errorDiv.parentNode) errorDiv.parentNode.removeChild(errorDiv);
        }, 5000);
    }
    function startPolling() {
        if (!allowChatPolling) {
            return;
        }
        if (isPolling) return;
        isPolling = true;
        pollInterval = setInterval(function() { fetchMessagesFromServer(); }, 2000);
    }
    function stopPolling() {
        isPolling = false;
        if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
    }
    function cancelReply() {
        replyingTo = null;
        // Update chat input placeholder
        if (chatInput) {
            chatInput.placeholder = isUserLoggedIn ? 'Type a message...' : 'Please log in to chat';
            chatInput.style.borderColor = '';
            chatInput.style.background = '';
        }
        // Remove reply indicator
        hideReplyIndicator();
    }
    function startReply(msg) {
        if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: startReply called with message:', msg);
        
        if (!isUserLoggedIn) {
            if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: User not logged in for reply');
            showLoginModal();
            return;
        }
        
        // Set the replying to state
        replyingTo = msg.id;
        if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Set replyingTo to:', replyingTo);
        
        // Update input placeholder for chat
        var replyPlaceholder = 'Replying to ' + msg.username + '...';
        if (chatInput) {
            chatInput.placeholder = replyPlaceholder;
            chatInput.style.borderColor = '#065fd4';
            chatInput.style.background = '#f3f8ff';
        }
        
        // Show visual reply indicator above input
        showReplyIndicator(msg.username);
        
        // Focus the chat input field
        if (chatInput) {
            if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Focusing chat input:', chatInput.id);
            chatInput.focus();
        }
    }
    function showReplyIndicator(username) {
        // Remove any existing reply indicator
        hideReplyIndicator();
        
        // Create reply indicator for chat
        if (chatForm) {
            var indicator = document.createElement('div');
            indicator.className = 'videohub360-chat-reply-indicator';
            indicator.innerHTML = 
                '<div class="reply-indicator-content">' +
                    '<span class="reply-icon">↩️</span>' +
                    '<span class="reply-text">Replying to <strong>' + username + '</strong></span>' +
                    '<button type="button" class="reply-cancel-btn" aria-label="Cancel reply">✕</button>' +
                '</div>';
            
            // Add click event listener to cancel button
            var cancelBtn = indicator.querySelector('.reply-cancel-btn');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    cancelReply();
                });
            }
            
            // Insert before the form
            chatForm.parentNode.insertBefore(indicator, chatForm);
        }
    }
    function hideReplyIndicator() {
        // Remove all existing reply indicators
        var indicators = document.querySelectorAll('.videohub360-chat-reply-indicator');
        indicators.forEach(function(indicator) {
            indicator.remove();
        });
    }
    // Message menu functions
    function showMessageMenu(msg, buttonElement) {
        // Remove any existing menu
        var existingMenu = document.querySelector('.videohub360-chat-message-menu');
        if (existingMenu) {
            existingMenu.remove();
        }
        
        var menu = document.createElement('div');
        menu.className = 'videohub360-chat-message-menu';
        menu.setAttribute('role', 'menu');
        menu.setAttribute('aria-label', 'Message options');
        
        // Determine chat container (popup only)
        var chatContainer = chatMessages;
        
        if (!chatContainer) {
            if (window.__VH360_DEBUG) console.warn('VideoHub360: Chat container not found');
            return;
        }
        
        // Position menu near the button relative to the correct chat container
        var buttonRect = buttonElement.getBoundingClientRect();
        var chatRect = chatContainer.getBoundingClientRect();
        menu.style.position = 'absolute';
        menu.style.top = (buttonRect.top - chatRect.top + buttonRect.height) + 'px';
        menu.style.right = (chatRect.right - buttonRect.right) + 'px';
        menu.style.zIndex = '1000';
        
        // Reply option (available to all logged-in users, appears first)
        if (isUserLoggedIn) {
            var replyOption = createMenuOption(
                'Reply',
                '↩️',
                function() {
                    if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Reply menu option clicked for message:', msg);
                    startReply(msg);
                    menu.remove();
                }
            );
            menu.appendChild(replyOption);
        }
        
        // Private Message option (available to all logged-in users for other users' messages, if feature is enabled)
        if (isUserLoggedIn && currentUserId && msg.user_id !== currentUserId && privateMessagingAvailable) {
            var privateMessageOption = createMenuOption(
                'Send Private Message',
                '💬',
                function() {
                    if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Private message option clicked for user:', msg.username);
                    showPrivateMessageModal(msg);
                    menu.remove();
                }
            );
            menu.appendChild(privateMessageOption);
        }
        
        // Pin Comment option
        if (msg.can_pin) {
            var pinOption = createMenuOption(
                msg.is_pinned ? 'Unpin Comment' : 'Pin Comment',
                msg.is_pinned ? '📌' : '📌',
                function() {
                    pinMessage(msg.id, msg.is_pinned ? 'unpin' : 'pin');
                    menu.remove();
                }
            );
            menu.appendChild(pinOption);
        }
        
        // Delete option (only for message author)
        if (msg.can_delete) {
            var deleteOption = createMenuOption(
                'Delete',
                '🗑️',
                function() {
                    if (confirm('Are you sure you want to delete this message?')) {
                        deleteMessage(msg.id);
                    }
                    menu.remove();
                }
            );
            menu.appendChild(deleteOption);
        }
        
        // Moderation options (only for moderators)
        if (isUserLoggedIn && currentUserId && msg.user_id !== currentUserId) {
            // Check if user has moderation permissions (we'll assume based on pin permission for now)
            if (msg.can_pin) {
                var banOption = createMenuOption(
                    'Ban User',
                    '🚫',
                    function() {
                        showModerationModal(msg, 'ban');
                        menu.remove();
                    }
                );
                menu.appendChild(banOption);
                
                var timeoutOption = createMenuOption(
                    'Timeout User',
                    '⏰',
                    function() {
                        showModerationModal(msg, 'timeout');
                        menu.remove();
                    }
                );
                menu.appendChild(timeoutOption);
            }
            
            // Report option (available to all users)
            var reportOption = createMenuOption(
                'Report Message',
                '⚠️',
                function() {
                    showModerationModal(msg, 'report');
                    menu.remove();
                }
            );
            menu.appendChild(reportOption);
        }
        
        // Add menu to the correct chat container
        chatContainer.style.position = 'relative';
        chatContainer.appendChild(menu);
        
        // Focus first menu item for accessibility
        var firstOption = menu.querySelector('.videohub360-chat-menu-option');
        if (firstOption) {
            firstOption.focus();
        }
        
        // Close menu when clicking outside or pressing Escape
        function closeMenu(e) {
            if (e.type === 'keydown' && e.key !== 'Escape') return;
            if (e.type === 'click' && menu.contains(e.target)) return;
            
            menu.remove();
            document.removeEventListener('click', closeMenu);
            document.removeEventListener('keydown', closeMenu);
            buttonElement.focus(); // Return focus to button
        }
        
        // Delay adding listeners to prevent immediate closure
        setTimeout(function() {
            document.addEventListener('click', closeMenu);
            document.addEventListener('keydown', closeMenu);
        }, 100);
    }
    
    function createMenuOption(text, icon, callback) {
        var option = document.createElement('button');
        option.className = 'videohub360-chat-menu-option';
        option.setAttribute('role', 'menuitem');
        option.setAttribute('tabindex', '0');
        option.innerHTML = '<span class="menu-icon">' + icon + '</span> ' + text;
        option.onclick = callback;
        option.onkeydown = function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                callback();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                var nextOption = option.nextElementSibling;
                if (nextOption) nextOption.focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                var prevOption = option.previousElementSibling;
                if (prevOption) prevOption.focus();
            }
        };
        return option;
    }
    
    function showModerationModal(msg, action) {
    var modal = document.createElement('div');
    modal.className = 'videohub360-moderation-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-labelledby', 'moderation-title');
    modal.setAttribute('aria-modal', 'true');

    var modalContent = document.createElement('div');
    // Align with CSS selector in chat.css
    modalContent.className = 'videohub360-moderation-content';

    // Modal header with close button (align with CSS)
    var header = document.createElement('div');
    header.className = 'videohub360-moderation-header';

    var title = document.createElement('h2'); // CSS styles h2 in header
    title.id = 'moderation-title';
    var actionTexts = {
        'ban': 'Ban User',
        'timeout': 'Timeout User',
        'report': 'Report Message'
    };
    title.textContent = actionTexts[action];
    header.appendChild(title);

    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    // Use the shared close button class styled in frontend.css
    closeBtn.className = 'videohub360-modal-close';
    closeBtn.innerHTML = '&times;';
    closeBtn.setAttribute('aria-label', 'Close modal');

    // Close helpers
    var observer;
    function cleanup() {
        document.removeEventListener('keydown', handleEscapeKey);
        if (observer) observer.disconnect();
        document.body.style.overflow = '';
    }
    function closeModerationModal() {
        if (modal && modal.parentNode) {
            modal.parentNode.removeChild(modal);
        }
        cleanup();
    }

    closeBtn.onclick = closeModerationModal;
    header.appendChild(closeBtn);

    modalContent.appendChild(header);

    var form = document.createElement('form');
    form.className = 'videohub360-moderation-form';

    // Message preview
    var preview = document.createElement('div');
    preview.className = 'message-preview';
    preview.innerHTML = '<strong>' + msg.username + ':</strong> ' + msg.message;
    form.appendChild(preview);

    // Duration input for timeout
    var durationSelect; // hoist for use in submit
    if (action === 'timeout') {
        var durationLabel = document.createElement('label');
        durationLabel.textContent = 'Timeout Duration (minutes):';
        durationLabel.setAttribute('for', 'timeout-duration');
        form.appendChild(durationLabel);

        durationSelect = document.createElement('select');
        durationSelect.id = 'timeout-duration';
        durationSelect.name = 'duration';
        durationSelect.required = true;

        var durations = [
            {value: 1, text: '1 minute'},
            {value: 5, text: '5 minutes'},
            {value: 10, text: '10 minutes'},
            {value: 30, text: '30 minutes'},
            {value: 60, text: '1 hour'},
            {value: 120, text: '2 hours'},
            {value: 360, text: '6 hours'},
            {value: 720, text: '12 hours'},
            {value: 1440, text: '24 hours'}
        ];

        durations.forEach(function(dur) {
            var opt = document.createElement('option');
            opt.value = dur.value;
            opt.textContent = dur.text;
            if (dur.value === 60) opt.selected = true; // Default 1 hour
            durationSelect.appendChild(opt);
        });
        form.appendChild(durationSelect);
    }

    // Reason input
    var reasonLabel = document.createElement('label');
    reasonLabel.textContent = 'Reason' + (action === 'report' ? ' (required)' : ' (optional)') + ':';
    reasonLabel.setAttribute('for', 'moderation-reason');
    form.appendChild(reasonLabel);

    var reasonInput = document.createElement('textarea');
    reasonInput.id = 'moderation-reason';
    reasonInput.name = 'reason';
    reasonInput.rows = 3;
    reasonInput.placeholder = 'Enter reason...';
    if (action === 'report') {
        reasonInput.required = true;
    }
    form.appendChild(reasonInput);

    // Buttons
    var buttonContainer = document.createElement('div');
    buttonContainer.className = 'moderation-buttons';

    var submitBtn = document.createElement('button');
    submitBtn.type = 'submit';
    submitBtn.className = 'videohub360-btn primary';
    submitBtn.textContent = actionTexts[action];
    buttonContainer.appendChild(submitBtn);

    var cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.className = 'videohub360-btn secondary';
    cancelBtn.textContent = 'Cancel';
    cancelBtn.onclick = closeModerationModal;
    buttonContainer.appendChild(cancelBtn);

    form.appendChild(buttonContainer);

    // Handle form submission
    form.onsubmit = function(e) {
        e.preventDefault();
        var formData = new FormData();
        formData.append('action', 'videohub360_chat_' + action);
        formData.append('nonce', chatNonce);
        formData.append('message_id', msg.id);
        formData.append('post_id', postId);
        formData.append('reason', reasonInput.value);

        if (action === 'timeout' && durationSelect) {
            formData.append('duration', durationSelect.value);
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (window.__VH360_DEBUG) console.log('VideoHub360: Moderation response:', data);
            if (data.success) {
                showSuccess(data.data.message);
                closeModerationModal();

                // Close any existing message menus since the context has changed
                var existingMenu = document.querySelector('.videohub360-chat-message-menu');
                if (existingMenu) existingMenu.remove();
            } else {
                var errorMessage = data.data;
                if (typeof errorMessage === 'object') {
                    errorMessage = errorMessage.message || 'Action failed. Please try again.';
                }
                showError(errorMessage || 'Action failed. Please try again.');
                submitBtn.disabled = false;
                submitBtn.textContent = actionTexts[action];
            }
        })
        .catch(function(error) {
            if (window.__VH360_DEBUG) console.error('VideoHub360: Moderation action error:', error);
            showError('Network error. Please try again.');
            submitBtn.disabled = false;
            submitBtn.textContent = actionTexts[action];
        });
    };

    modalContent.appendChild(form);
    modal.appendChild(modalContent);
    document.body.appendChild(modal);

    // ACTIVATE the modal and lock scroll (required to show it)
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Close modal when clicking outside the content
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModerationModal();
        }
    });

    // Handle escape key to close modal
    function handleEscapeKey(e) {
        if (e.key === 'Escape') {
            closeModerationModal();
        }
    }
    document.addEventListener('keydown', handleEscapeKey);

    // Fallback cleanup if something else removes the modal
    observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.removedNodes.forEach(function(node) {
                    if (node === modal) {
                        cleanup();
                    }
                });
            }
        });
    });
    observer.observe(document.body, { childList: true });

    // Focus first input
    reasonInput.focus();
}
    
    function showPrivateMessageModal(msg) {
        var modal = document.createElement('div');
        modal.className = 'videohub360-private-message-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-labelledby', 'private-message-title');
        modal.setAttribute('aria-modal', 'true');
        
        var modalContent = document.createElement('div');
        modalContent.className = 'videohub360-private-message-modal-content';
        
        // Modal header with close button
        var header = document.createElement('div');
        header.className = 'private-message-modal-header';
        
        var title = document.createElement('h3');
        title.id = 'private-message-title';
        title.textContent = 'Send Private Message to ' + msg.username;
        header.appendChild(title);
        
        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'private-message-close-btn';
        closeBtn.innerHTML = '&times;';
        closeBtn.onclick = function() { modal.remove(); };
        header.appendChild(closeBtn);
        
        modalContent.appendChild(header);
        
        // Modal body
        var body = document.createElement('div');
        body.className = 'private-message-modal-body';
        
        var messageLabel = document.createElement('label');
        messageLabel.textContent = 'Private Message:';
        messageLabel.setAttribute('for', 'private-message-input');
        body.appendChild(messageLabel);
        
        var messageInput = document.createElement('textarea');
        messageInput.id = 'private-message-input';
        messageInput.className = 'private-message-input';
        messageInput.placeholder = 'Type your private message...';
        messageInput.maxLength = chatMessageLimit;
        messageInput.rows = 4;
        body.appendChild(messageInput);
        
        var charCount = document.createElement('div');
        charCount.className = 'private-message-char-count';
        charCount.textContent = '0/' + chatMessageLimit;
        body.appendChild(charCount);
        
        // Update character count
        messageInput.addEventListener('input', function() {
            charCount.textContent = messageInput.value.length + '/' + chatMessageLimit;
        });
        
        modalContent.appendChild(body);
        
        // Modal footer with buttons
        var footer = document.createElement('div');
        footer.className = 'private-message-modal-footer';
        
        var cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'private-message-cancel-btn';
        cancelBtn.textContent = 'Cancel';
        cancelBtn.onclick = function() { modal.remove(); };
        footer.appendChild(cancelBtn);
        
        var sendBtn = document.createElement('button');
        sendBtn.type = 'button';
        sendBtn.className = 'private-message-send-btn';
        sendBtn.textContent = 'Send Private Message';
        sendBtn.onclick = function() {
            var message = messageInput.value.trim();
            if (!message) {
                showError('Please enter a message.');
                return;
            }
            sendPrivateMessage(msg.user_id, message);
            modal.remove();
        };
        footer.appendChild(sendBtn);
        
        modalContent.appendChild(footer);
        modal.appendChild(modalContent);
        document.body.appendChild(modal);
        
        // Close modal when clicking outside the content
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        });
        
        // Handle escape key to close modal
        function handleEscapeKey(e) {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', handleEscapeKey);
            }
        }
        document.addEventListener('keydown', handleEscapeKey);
        
        // Clean up event listener when modal is removed
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.removedNodes.forEach(function(node) {
                        if (node === modal) {
                            document.removeEventListener('keydown', handleEscapeKey);
                            observer.disconnect();
                        }
                    });
                }
            });
        });
        observer.observe(document.body, { childList: true });
        
        // Focus message input
        messageInput.focus();
    }
    
    function sendPrivateMessage(recipientId, message) {
        var formData = new FormData();
        formData.append('action', 'videohub360_chat_post');
        formData.append('nonce', chatNonce);
        formData.append('post_id', postId);
        formData.append('message', message);
        formData.append('message_type', 'private');
        formData.append('recipient_id', recipientId);
        
        fetch(ajaxUrl, { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Private message sent successfully:', data.data);
                // Add the message to chat immediately
                addChatMessage(data.data);
                showSuccess('Private message sent!');
            } else {
                showError(data.data || 'Failed to send private message.');
            }
        })
        .catch(function(error) {
            if (window.__VH360_DEBUG) console.error('VideoHub360 Error sending private message:', error);
            showError('Network error. Please try again.');
        });
    }
    
    function pinMessage(messageId, action) {
        var formData = new FormData();
        formData.append('action', 'videohub360_chat_pin');
        formData.append('nonce', chatNonce);
        formData.append('message_id', messageId);
        formData.append('pin_status', action);
        
        fetch(ajaxUrl, { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showSuccess(data.data.message);
                // Reload all messages to show updated pin status and reorder
                reloadAllMessages();
            } else {
                showError(data.data || 'Failed to pin message.');
            }
        })
        .catch(function(error) {
            showError('Network error. Please try again.');
        });
    }
    
    function reloadAllMessages() {
        // Clear existing messages from chat
        if (chatMessages) {
            chatMessages.innerHTML = '';
        }
        
        // Reset state
        messages = [];
        lastMessageId = 0;
        
        // Fetch all messages
        var formData = new FormData();
        formData.append('action', 'videohub360_chat_fetch');
        formData.append('post_id', postId);
        formData.append('since_id', 0); // Fetch all messages
        
        fetch(ajaxUrl, { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success && data.data.messages && data.data.messages.length > 0) {
                data.data.messages.forEach(function(messageData) {
                    addChatMessage(messageData);
                });
            }
        });
    }
    
    function deleteMessage(messageId) {
        var formData = new FormData();
        formData.append('action', 'videohub360_chat_delete');
        formData.append('nonce', chatNonce);
        formData.append('message_id', messageId);
        
        fetch(ajaxUrl, { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                // Remove message from DOM
                var messageEl = document.querySelector('[data-message-id="' + messageId + '"]');
                if (messageEl) {
                    messageEl.remove();
                }
                showSuccess(data.data.message);
            } else {
                showError(data.data || 'Failed to delete message.');
            }
        })
        .catch(function(error) {
            showError('Network error. Please try again.');
        });
    }
    
    function showSuccess(message) {
        // Show success in chat popup
        if (!chatMessages) return;
        
        var successDiv = document.createElement('div');
        successDiv.className = 'videohub360-chat-message system success';
        successDiv.innerHTML = '<span style="color: #2e7d32; font-style: italic;">✓ ' + message + '</span>';
        chatMessages.appendChild(successDiv);
        setTimeout(function() {
            if (successDiv.parentNode) successDiv.parentNode.removeChild(successDiv);
        }, 3000);
        scrollToBottom();
    }
    
    // Event handlers for popup chat
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!allowChatPosting) {
                return;
            }
            if (!isUserLoggedIn) {
                showLoginModal();
                return;
            }
            var message = chatInput.value.trim();
            if (!message) return;
            if (message.length > chatMessageLimit) {
                showError('Message is too long. Maximum ' + chatMessageLimit + ' characters allowed.');
                return;
            }
            postMessageToServer(message);
        });
    }
    
    // Click handler for non-logged in users
    if (chatInput && !isUserLoggedIn && allowChatPosting) {
        chatInput.addEventListener('click', function(e) {
            e.preventDefault();
            showLoginModal();
        });
    }
    
    // Keydown handler for chat input
    if (chatInput) {
        chatInput.addEventListener('keydown', function(e) {
            if (!allowChatPosting) {
                return;
            }
            if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: chatInput keydown event:', e.key, 'replyingTo:', replyingTo);
            
            if (e.key === 'Escape' && replyingTo) {
                e.preventDefault();
                if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Agora Escape pressed, canceling reply');
                cancelReply();
            } else if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Enter pressed for chat submission');
                
                // Directly trigger the form submission logic
                if (isUserLoggedIn) {
                    var message = chatInput.value.trim();
                    if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: message to send:', message, 'Length:', message.length, 'Limit:', chatMessageLimit);
                    if (message && message.length <= chatMessageLimit) {
                        postMessageToServer(message);
                    } else if (message.length > chatMessageLimit) {
                        showError('Message is too long. Maximum ' + chatMessageLimit + ' characters allowed.');
                    }
                } else {
                    if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Agora user not logged in for Enter key submission');
                    showLoginModal();
                }
            }
        });
    }
    
    // Enhanced Emoji picker functionality with comprehensive categories
    var emojiCategories = {
        'recent': {
            name: 'Recently Used',
            icon: '🕐',
            emojis: [] // Will be populated from localStorage
        },
        'faces': {
            name: 'Faces & People',
            icon: '😀',
            emojis: ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩', '🥳', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓', '🤗', '🤔', '🤭', '🤫', '🤥', '😶', '😐', '😑', '😬', '🙄', '😯', '😦', '😧', '😮', '😲', '🥱', '😴', '🤤', '😪', '😵', '🤐', '🥴', '🤢', '🤮', '🤧', '😷', '🤒', '🤕']
        },
        'hearts': {
            name: 'Hearts & Love',
            icon: '❤️',
            emojis: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🤎', '🖤', '🤍', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝']
        },
        'celebrations': {
            name: 'Celebrations',
            icon: '🎉',
            emojis: ['🎉', '🎊', '🎈', '🎁', '🎂', '🎆', '🎇', '✨', '🎃', '🎄', '🎅', '🤶', '🎯', '🎪', '🎭', '🎨']
        },
        'reactions': {
            name: 'Reactions',
            icon: '👍',
            emojis: ['👍', '👎', '👏', '🙌', '👐', '🤝', '👊', '✊', '🤛', '🤜', '🤞', '✌️', '🤟', '🤘', '👌', '🤏', '👈', '👉', '👆', '👇', '☝️', '✋', '🤚', '🖐️', '🖖', '👋', '🤙', '💪']
        },
        'animals': {
            name: 'Animals',
            icon: '🐶',
            emojis: ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🐯', '🦁', '🐮', '🐷', '🐽', '🐸', '🐵', '🙈', '🙉', '🙊']
        },
        'food': {
            name: 'Food',
            icon: '🍎',
            emojis: ['🍎', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🍈', '🍒', '🍑', '🥭', '🍍', '🥥', '🥝', '🍅', '🍆', '🥑', '🥦', '🥬', '🥒']
        }
    };
    
    // Load recent emojis from localStorage
    function loadRecentEmojis() {
        try {
            var recent = localStorage.getItem('vh360_recent_emojis');
            if (recent) {
                emojiCategories.recent.emojis = JSON.parse(recent);
            }
        } catch (e) {
            if (window.__VH360_DEBUG) console.warn('Failed to load recent emojis:', e);
        }
    }
    
    // Save recent emojis to localStorage
    function saveRecentEmojis() {
        try {
            localStorage.setItem('vh360_recent_emojis', JSON.stringify(emojiCategories.recent.emojis));
        } catch (e) {
            if (window.__VH360_DEBUG) console.warn('Failed to save recent emojis:', e);
        }
    }
    
    // Add emoji to recent list
    function addToRecentEmojis(emoji) {
        var recent = emojiCategories.recent.emojis;
        
        // Remove emoji if already exists
        var index = recent.indexOf(emoji);
        if (index > -1) {
            recent.splice(index, 1);
        }
        
        // Add to beginning
        recent.unshift(emoji);
        
        // Keep only last 20
        if (recent.length > 20) {
            recent.splice(20);
        }
        
        saveRecentEmojis();
    }
    
    // Initialize recent emojis
    loadRecentEmojis();
    
    function showEmojiPicker(button) {
        // Remove any existing emoji picker
        var existingPicker = document.querySelector('.videohub360-emoji-picker');
        if (existingPicker) {
            existingPicker.remove();
            return;
        }
        
        // Create picker container
        var picker = document.createElement('div');
        picker.className = 'videohub360-emoji-picker';
        
        // Create header container
        var header = document.createElement('div');
        header.className = 'emoji-picker-header';
        
        // Create top bar with search and close button
        var topBar = document.createElement('div');
        topBar.className = 'emoji-picker-top-bar';
        
        // Create search input
        var searchContainer = document.createElement('div');
        searchContainer.className = 'emoji-search-container';
        
        var searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Search Reactions';
        searchInput.className = 'emoji-search-input';
        searchContainer.appendChild(searchInput);
        
        // Create close button
        var closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'emoji-picker-close';
        closeButton.innerHTML = '✕';
        closeButton.title = 'Close emoji picker';
        closeButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            picker.remove();
        });
        
        topBar.appendChild(searchContainer);
        topBar.appendChild(closeButton);
        
        // Create tabs container
        var tabsContainer = document.createElement('div');
        tabsContainer.className = 'emoji-tabs';
        
        // Add search container and tabs to header
        header.appendChild(topBar);
        header.appendChild(tabsContainer);
        
        // Create content container
        var contentContainer = document.createElement('div');
        contentContainer.className = 'emoji-content';
        
        // Track current category
        var currentCategory = emojiCategories.recent.emojis.length > 0 ? 'recent' : 'faces';
        
        // Create tabs and content
        Object.keys(emojiCategories).forEach(function(categoryKey) {
            var category = emojiCategories[categoryKey];
            
            // Skip recent if empty
            if (categoryKey === 'recent' && category.emojis.length === 0) {
                return;
            }
            
            // Create tab button
            var tabButton = document.createElement('button');
            tabButton.type = 'button';
            tabButton.className = 'emoji-tab' + (categoryKey === currentCategory ? ' active' : '');
            tabButton.textContent = category.icon;
            tabButton.title = category.name;
            tabButton.dataset.category = categoryKey;
            
            tabButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                switchEmojiCategory(categoryKey);
            });
            
            tabsContainer.appendChild(tabButton);
        });
        
        // Function to render emoji grid with section headers
        function renderEmojiGrid(emojis, searchTerm, categoryKey) {
            contentContainer.innerHTML = '';
            
            if (emojis.length === 0) {
                var noResults = document.createElement('div');
                noResults.className = 'emoji-no-results';
                noResults.textContent = searchTerm ? 'No emojis found' : 'No recent emojis';
                contentContainer.appendChild(noResults);
                return;
            }
            
            // Add section header if not searching
            if (!searchTerm && categoryKey && emojiCategories[categoryKey]) {
                var sectionHeader = document.createElement('div');
                sectionHeader.className = 'emoji-section-header';
                var headerText = categoryKey === 'recent' ? 'RECENT' : emojiCategories[categoryKey].name.toUpperCase();
                sectionHeader.textContent = headerText;
                contentContainer.appendChild(sectionHeader);
            }
            
            var grid = document.createElement('div');
            grid.className = 'emoji-grid';
            
            emojis.forEach(function(emoji) {
                var emojiBtn = document.createElement('button');
                emojiBtn.type = 'button';
                emojiBtn.className = 'emoji-button';
                emojiBtn.textContent = emoji;
                emojiBtn.title = emoji;
                
                emojiBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    insertEmoji(emoji, button);
                    addToRecentEmojis(emoji);
                    // Don't close picker - allow multiple emoji selection
                });
                
                grid.appendChild(emojiBtn);
            });
            
            contentContainer.appendChild(grid);
        }
        
        // Function to switch category
        function switchEmojiCategory(categoryKey) {
            currentCategory = categoryKey;
            
            // Update tab active state
            tabsContainer.querySelectorAll('.emoji-tab').forEach(function(tab) {
                tab.classList.remove('active');
                if (tab.dataset.category === categoryKey) {
                    tab.classList.add('active');
                }
            });
            
            // Clear search
            searchInput.value = '';
            
            // Render emojis for selected category
            renderEmojiGrid(emojiCategories[categoryKey].emojis, '', categoryKey);
        }
        
        // Prevent search input from closing picker
        searchInput.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // Prevent search input focus from closing picker
        searchInput.addEventListener('focus', function(e) {
            e.stopPropagation();
        });
        
        // Search functionality with improved filtering
        var searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                var searchTerm = searchInput.value.toLowerCase().trim();
                
                if (!searchTerm) {
                    // Show current category
                    renderEmojiGrid(emojiCategories[currentCategory].emojis, '', currentCategory);
                    return;
                }
                
                // Create emoji keyword mapping for better search
                var emojiKeywords = {
                    '😀': ['smile', 'happy', 'grin', 'face'],
                    '😃': ['smile', 'happy', 'grin', 'big'],
                    '😄': ['smile', 'happy', 'laugh', 'joy'],
                    '😁': ['grin', 'smile', 'happy', 'teeth'],
                    '😆': ['laugh', 'happy', 'smile', 'squint'],
                    '😅': ['laugh', 'sweat', 'relief', 'nervous'],
                    '😂': ['laugh', 'tears', 'joy', 'funny'],
                    '🤣': ['rolling', 'laugh', 'floor', 'funny'],
                    '😊': ['smile', 'happy', 'blush', 'pleased'],
                    '😇': ['innocent', 'angel', 'halo', 'saint'],
                    '🙂': ['smile', 'happy', 'slight'],
                    '🙃': ['upside', 'down', 'silly', 'sarcasm'],
                    '😉': ['wink', 'flirt', 'joke', 'hint'],
                    '😌': ['relieved', 'content', 'pleased', 'satisfied'],
                    '😍': ['love', 'heart', 'eyes', 'adore'],
                    '🥰': ['love', 'hearts', 'adore', 'crush'],
                    '😘': ['kiss', 'love', 'heart', 'blow'],
                    '😗': ['kiss', 'whistle', 'lips'],
                    '😙': ['kiss', 'smile', 'whistle'],
                    '😚': ['kiss', 'closed', 'eyes'],
                    '😋': ['yum', 'tasty', 'delicious', 'tongue'],
                    '😛': ['tongue', 'playful', 'silly'],
                    '😝': ['tongue', 'wink', 'playful', 'silly'],
                    '😜': ['tongue', 'wink', 'joke', 'playful'],
                    '🤪': ['crazy', 'silly', 'wild', 'zany'],
                    '🤨': ['raised', 'eyebrow', 'suspicious', 'doubt'],
                    '🧐': ['monocle', 'thinking', 'examine', 'curious'],
                    '🤓': ['nerd', 'geek', 'glasses', 'smart'],
                    '😎': ['cool', 'sunglasses', 'awesome', 'boss'],
                    '🤩': ['star', 'struck', 'excited', 'wow'],
                    '🥳': ['party', 'celebrate', 'hat', 'birthday'],
                    '😏': ['smirk', 'sly', 'suggestive', 'confident'],
                    '😒': ['unamused', 'disappointed', 'meh', 'bored'],
                    '😞': ['disappointed', 'sad', 'dejected'],
                    '😔': ['pensive', 'sad', 'dejected', 'sorry'],
                    '😟': ['worried', 'concerned', 'anxious'],
                    '😕': ['confused', 'disappointed', 'slight', 'frown'],
                    '🙁': ['frown', 'sad', 'disappointed'],
                    '☹️': ['frown', 'sad', 'disappointed'],
                    '😣': ['persevering', 'struggle', 'frustrated'],
                    '😖': ['confounded', 'frustrated', 'annoyed'],
                    '😫': ['tired', 'frustrated', 'fed', 'up'],
                    '😩': ['weary', 'tired', 'frustrated'],
                    '🥺': ['pleading', 'puppy', 'dog', 'eyes', 'cute'],
                    '😢': ['cry', 'sad', 'tear', 'upset'],
                    '😭': ['crying', 'sob', 'tears', 'bawl'],
                    '😤': ['huffing', 'angry', 'frustrated', 'steam'],
                    '😠': ['angry', 'mad', 'annoyed', 'grumpy'],
                    '😡': ['rage', 'angry', 'mad', 'furious'],
                    '🤬': ['swearing', 'cursing', 'angry', 'symbols'],
                    '🤯': ['mind', 'blown', 'shocked', 'exploding'],
                    '😳': ['flushed', 'embarrassed', 'shy', 'surprised'],
                    '🥵': ['hot', 'sweating', 'heat', 'temperature'],
                    '🥶': ['cold', 'freezing', 'blue', 'temperature'],
                    '😱': ['scared', 'fear', 'shocked', 'surprised'],
                    '😨': ['fearful', 'scared', 'worried', 'anxious'],
                    '😰': ['anxious', 'sweat', 'nervous', 'worried'],
                    '😥': ['sad', 'relieved', 'disappointed', 'sweat'],
                    '😓': ['downcast', 'sweat', 'sad', 'tired'],
                    '🤗': ['hugging', 'hug', 'embrace', 'care'],
                    '🤔': ['thinking', 'hmm', 'consider', 'ponder'],
                    '🤭': ['giggle', 'chuckle', 'secret', 'oops'],
                    '🤫': ['shush', 'quiet', 'secret', 'silence'],
                    '🤥': ['lying', 'pinocchio', 'nose', 'dishonest'],
                    '😶': ['no', 'mouth', 'speechless', 'silent'],
                    '😐': ['neutral', 'expressionless', 'meh'],
                    '😑': ['expressionless', 'blank', 'meh'],
                    '😬': ['grimace', 'awkward', 'nervous', 'eek'],
                    '🙄': ['eye', 'roll', 'annoyed', 'whatever'],
                    '😯': ['hushed', 'surprised', 'quiet', 'wow'],
                    '😦': ['frowning', 'open', 'mouth', 'surprised'],
                    '😧': ['anguished', 'shocked', 'surprised'],
                    '😮': ['open', 'mouth', 'surprised', 'wow'],
                    '😲': ['astonished', 'shocked', 'surprised'],
                    '🥱': ['yawning', 'tired', 'sleepy', 'bored'],
                    '😴': ['sleeping', 'tired', 'zzz', 'nap'],
                    '🤤': ['drooling', 'sleep', 'delicious'],
                    '😪': ['sleepy', 'tired', 'drowsy'],
                    '😵': ['dizzy', 'dead', 'unconscious'],
                    '🤐': ['zipper', 'mouth', 'sealed', 'secret'],
                    '🥴': ['woozy', 'drunk', 'dizzy', 'intoxicated'],
                    '🤢': ['nauseated', 'sick', 'disgusted', 'ill'],
                    '🤮': ['vomiting', 'sick', 'puke', 'ill'],
                    '🤧': ['sneezing', 'sick', 'gesundheit', 'achoo'],
                    '😷': ['mask', 'sick', 'doctor', 'medical'],
                    '🤒': ['thermometer', 'sick', 'fever', 'ill'],
                    '🤕': ['bandage', 'hurt', 'injured', 'head'],
                    // Hearts & Love
                    '❤️': ['heart', 'love', 'red'],
                    '🧡': ['orange', 'heart', 'love'],
                    '💛': ['yellow', 'heart', 'love'],
                    '💚': ['green', 'heart', 'love'],
                    '💙': ['blue', 'heart', 'love'],
                    '💜': ['purple', 'heart', 'love'],
                    '🤎': ['brown', 'heart', 'love'],
                    '🖤': ['black', 'heart', 'love'],
                    '🤍': ['white', 'heart', 'love'],
                    '💔': ['broken', 'heart', 'sad', 'breakup'],
                    '❣️': ['exclamation', 'heart', 'love'],
                    '💕': ['two', 'hearts', 'love'],
                    '💞': ['revolving', 'hearts', 'love'],
                    '💓': ['beating', 'heart', 'love'],
                    '💗': ['growing', 'heart', 'love'],
                    '💖': ['sparkling', 'heart', 'love'],
                    '💘': ['cupid', 'arrow', 'heart', 'love'],
                    '💝': ['gift', 'heart', 'love', 'present'],
                    // Reactions
                    '👍': ['thumbs', 'up', 'good', 'yes', 'agree', 'like'],
                    '👎': ['thumbs', 'down', 'bad', 'no', 'disagree', 'dislike'],
                    '👏': ['clap', 'applause', 'bravo', 'good', 'job'],
                    '🙌': ['raised', 'hands', 'praise', 'celebration', 'hooray'],
                    '👐': ['open', 'hands', 'hug', 'embrace'],
                    '🤝': ['handshake', 'deal', 'agreement', 'meeting'],
                    '👊': ['fist', 'bump', 'punch', 'respect'],
                    '✊': ['raised', 'fist', 'power', 'solidarity'],
                    '🤛': ['left', 'fist', 'bump'],
                    '🤜': ['right', 'fist', 'bump'],
                    '🤞': ['crossed', 'fingers', 'luck', 'hope'],
                    '✌️': ['peace', 'victory', 'two'],
                    '🤟': ['love', 'you', 'sign', 'hand'],
                    '🤘': ['rock', 'on', 'sign', 'horns'],
                    '👌': ['ok', 'okay', 'perfect', 'good'],
                    '🤏': ['pinch', 'small', 'tiny'],
                    '👈': ['point', 'left', 'direction'],
                    '👉': ['point', 'right', 'direction'],
                    '👆': ['point', 'up', 'direction'],
                    '👇': ['point', 'down', 'direction'],
                    '☝️': ['index', 'pointing', 'up'],
                    '✋': ['raised', 'hand', 'stop', 'high', 'five'],
                    '🤚': ['raised', 'back', 'hand', 'stop'],
                    '🖐️': ['hand', 'splayed', 'five'],
                    '🖖': ['vulcan', 'salute', 'spock'],
                    '👋': ['wave', 'hello', 'goodbye', 'hi'],
                    '🤙': ['call', 'me', 'hang', 'loose'],
                    '💪': ['muscle', 'strong', 'flex', 'bicep']
                };
                
                // Search across all categories with keyword matching
                var searchResults = [];
                Object.keys(emojiCategories).forEach(function(categoryKey) {
                    if (categoryKey === 'recent') return; // Skip recent for search
                    
                    emojiCategories[categoryKey].emojis.forEach(function(emoji) {
                        var shouldInclude = false;
                        
                        // Check if emoji matches search term directly (for exact emoji search)
                        if (emoji.includes(searchTerm)) {
                            shouldInclude = true;
                        }
                        
                        // Check if any keywords match
                        if (!shouldInclude && emojiKeywords[emoji]) {
                            shouldInclude = emojiKeywords[emoji].some(function(keyword) {
                                return keyword.toLowerCase().includes(searchTerm);
                            });
                        }
                        
                        // Add basic fallback - if no keywords defined, include in search results
                        // This ensures all emojis are searchable even without specific keywords
                        if (!shouldInclude && !emojiKeywords[emoji]) {
                            shouldInclude = true;
                        }
                        
                        if (shouldInclude && searchResults.indexOf(emoji) === -1) {
                            searchResults.push(emoji);
                        }
                    });
                });
                
                renderEmojiGrid(searchResults, searchTerm);
            }, 300);
        });
        
        // Function to insert emoji into input
        function insertEmoji(emoji, button) {
            var targetInput = chatInput;
            if (targetInput) {
                var currentValue = targetInput.value;
                var cursorPos = targetInput.selectionStart;
                var newValue = currentValue.slice(0, cursorPos) + emoji + currentValue.slice(cursorPos);
                targetInput.value = newValue;
                targetInput.setSelectionRange(cursorPos + emoji.length, cursorPos + emoji.length);
                targetInput.focus();
            }
        }
        
        // Assemble picker
        picker.appendChild(header);
        picker.appendChild(contentContainer);
        
        // Initialize with current category
        renderEmojiGrid(emojiCategories[currentCategory].emojis, '', currentCategory);
        
        // Position picker relative to viewport, not within chat container
        var buttonRect = button.getBoundingClientRect();
        var viewportHeight = window.innerHeight;
        var viewportWidth = window.innerWidth;
        
        // Responsive picker dimensions
        var isMobileView = viewportWidth <= 768;
        var isSmallMobile = viewportWidth <= 480;
        
        var pickerWidth, pickerHeight;
        if (isSmallMobile) {
            pickerWidth = Math.min(280, viewportWidth * 0.95);
            pickerHeight = 320;
        } else if (isMobileView) {
            pickerWidth = Math.min(320, viewportWidth * 0.9);
            pickerHeight = 380;
        } else {
            pickerWidth = 352;
            pickerHeight = 435;
        }
        
        // Position above button if there's not enough space below
        var spaceBelow = viewportHeight - buttonRect.bottom;
        var spaceAbove = buttonRect.top;
        var showAbove = spaceBelow < pickerHeight && spaceAbove > spaceBelow;
        
        // Calculate horizontal position
        var leftPosition;
        if (isMobileView) {
            // Center on mobile
            leftPosition = Math.max(8, (viewportWidth - pickerWidth) / 2);
        } else {
            // Right-aligned on desktop but keep on screen
            var rightEdge = buttonRect.right;
            leftPosition = Math.max(8, Math.min(rightEdge - pickerWidth, viewportWidth - pickerWidth - 8));
        }
        
        // Calculate vertical position
        var topPosition;
        if (showAbove) {
            topPosition = Math.max(8, buttonRect.top - pickerHeight - 8);
        } else {
            topPosition = Math.min(buttonRect.bottom + 8, viewportHeight - pickerHeight - 8);
        }
        
        // Apply positioning
        picker.style.position = 'fixed';
        picker.style.left = leftPosition + 'px';
        picker.style.top = topPosition + 'px';
        picker.style.width = pickerWidth + 'px';
        picker.style.height = pickerHeight + 'px';
        // Read z-index from CSS variable --vh360-z-picker, fallback to 1000000 if not available
        // This ensures the emoji picker appears above the chat popup (z-index: 999999)
        var pickerZIndex = getComputedStyle(document.documentElement).getPropertyValue('--vh360-z-picker').trim() || '1000000';
        picker.style.zIndex = pickerZIndex;
        
        // Append to body to escape container constraints
        document.body.appendChild(picker);
        
        // Close picker when clicking outside
        setTimeout(function() {
            document.addEventListener('click', function closePicker(e) {
                if (!picker.contains(e.target) && e.target !== button) {
                    picker.remove();
                    document.removeEventListener('click', closePicker);
                }
            });
        }, 100);
        
        // Focus search input
        setTimeout(function() {
            searchInput.focus();
        }, 100);
    }
    
    // Add event listener for Agora emoji button
    if (chatEmojiBtn) {
        chatEmojiBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!allowChatPosting) {
                return;
            }
            if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Agora emoji button clicked');
            if (!isUserLoggedIn) {
                if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Agora user not logged in for emoji');
                showLoginModal();
                return;
            }
            showEmojiPicker(this);
        });
    }
    
    // Add event listener for Agora send button
    if (chatSendBtn) {
        chatSendBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!allowChatPosting) {
                return;
            }
            if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Agora send button clicked');
            if (!isUserLoggedIn) {
                if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Agora user not logged in for send button');
                showLoginModal();
                return;
            }
            var message = chatInput.value.trim();
            if (!message) {
                if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Agora empty message, not sending');
                return;
            }
            if (message.length > chatMessageLimit) {
                showError('Message is too long. Maximum ' + chatMessageLimit + ' characters allowed.');
                return;
            }
            postMessageToServer(message);
        });
    }
    
    // Check chat features availability
    function checkChatFeatures() {
        var formData = new FormData();
        formData.append('action', 'videohub360_chat_check_features');
        
        fetch(ajaxUrl, { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                privateMessagingAvailable = data.data.private_messaging_available || false;
                if (window.__VH360_DEBUG) console.log('VideoHub360 Debug: Private messaging available:', privateMessagingAvailable);
            } else {
                if (window.__VH360_DEBUG) console.warn('VideoHub360: Failed to check chat features:', data.data);
                privateMessagingAvailable = false;
            }
        })
        .catch(function(error) {
            if (window.__VH360_DEBUG) console.error('VideoHub360: Error checking chat features:', error);
            privateMessagingAvailable = false;
        });
    }
    
    // Upgrade database to enable private messaging (admin only)
    function upgradeDatabaseForPrivateMessaging() {
        var formData = new FormData();
        formData.append('action', 'videohub360_chat_upgrade_database');
        formData.append('nonce', chatNonce);
        
        fetch(ajaxUrl, { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                if (window.__VH360_DEBUG) console.log('VideoHub360: Database upgrade successful:', data.data.message);
                privateMessagingAvailable = data.data.private_messaging_available || false;
                showSuccess(data.data.message);
            } else {
                if (window.__VH360_DEBUG) console.error('VideoHub360: Database upgrade failed:', data.data);
                showError(data.data || 'Failed to upgrade database for private messaging.');
            }
        })
        .catch(function(error) {
            if (window.__VH360_DEBUG) console.error('VideoHub360: Error upgrading database:', error);
            showError('Network error during database upgrade.');
        });
    }
    
    // Make upgrade function available globally for debugging
    window.videohub360UpgradeDatabase = upgradeDatabaseForPrivateMessaging;
    
    window.addEventListener('beforeunload', function() { stopPolling(); });
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) stopPolling();
        else {
            // When page becomes visible again, refresh messages and resume polling only for active chat.
            if (allowChatPolling && !isPolling) {
                fetchMessagesFromServer();
                startPolling();
            } else if (!allowChatPolling) {
                fetchMessagesFromServer();
            }
        }
    });

    // Initialize chat features check and load initial messages
    // Note: loadInitialMessages() only starts polling for active chat mode.
    checkChatFeatures();
    loadInitialMessages();
})();



/* Namespacing compatibility: copy any existing window.* functions into window.vh360 (non-destructive) */
;(function(){
  window.vh360 = window.vh360 || {};
  var _names = ['addEventListener', 'dispatchEvent', 'innerHeight', 'innerWidth', 'videohub360UpgradeDatabase'];
  _names.forEach(function(n){ try{ if (window[n] && !window.vh360[n]) window.vh360[n] = window[n]; }catch(e){} });
})();
