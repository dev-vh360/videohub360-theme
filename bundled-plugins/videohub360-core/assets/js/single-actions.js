
// == Share Modal Logic ==
/**
 * VideoHub360 frontend script
 *
 * This file powers much of the runtime behaviour for VideoHub360.  A
 * number of debug statements are scattered throughout the codebase to
 * aid development and troubleshooting.  To prevent those messages
 * from cluttering end‑user browser consoles in production, this
 * wrapper checks for a global `__VH360_DEBUG` flag.  When the flag is
 * undefined or falsy, it temporarily overrides `console.log` and
 * `console.warn` so that debug statements become no‑ops.  Errors
 * (`console.error`) are left untouched so that critical issues still
 * surface during execution.
 */

// Debug logging helpers - only log when __VH360_DEBUG is enabled
// Define on window to avoid redeclaration errors when multiple scripts load
if (typeof window !== 'undefined') {
  window.vh360IsDebug = window.vh360IsDebug || (() =>
    (typeof window !== 'undefined' && window.__VH360_DEBUG === true));

  window.vh360Log = window.vh360Log || ((...args) => {
    if (window.vh360IsDebug()) {
      console.log(...args);
    }
  });

  window.vh360Warn = window.vh360Warn || ((...args) => {
    if (window.vh360IsDebug()) {
      console.warn(...args);
    }
  });
}

(function() {
    // Guard against double initialization
    if (window.__vh360ShareModalInitialized) {
        return;
    }
    window.__vh360ShareModalInitialized = true;


    var shareBtn = document.getElementById('videohub360-share-btn');
    var modalOverlay = document.getElementById('videohub360-modal-overlay');
    var modalClose = document.getElementById('videohub360-modal-close');
    var copyBtn = document.getElementById('videohub360-copy-btn');
    var linkInput = document.getElementById('videohub360-link-input');
    var emailForm = document.getElementById('videohub360-email-form');
    var emailInput = document.getElementById('videohub360-email-input');
    var fromNameInput = document.getElementById('videohub360-from-name-input');
    var messageInput = document.getElementById('videohub360-message-input');
    var sendBtn = document.getElementById('videohub360-send-btn');

    /**
     * Detect if the current device is running iOS. This helper checks the user
     * agent for iPhone/iPad/iPod identifiers and also accounts for iPadOS 13+
     * devices which report themselves as "Mac" but expose touch events. We
     * intentionally avoid caching the result in case the page is loaded in
     * different contexts (e.g. inside an iframe) where the user agent string
     * could differ. Using a dedicated helper keeps the detection logic in one
     * place and makes it easy to update in the future.
     *
     * @returns {boolean} True if the device appears to be running iOS.
     */
    function vh360IsIOSDevice() {
        try {
            const ua = navigator.userAgent || navigator.vendor || window.opera;
            // Standard iOS detection (iPhone, iPad, iPod)
            const iOSIdentifiers = /iPad|iPhone|iPod/;
            // iPad on iOS 13+ reports itself as Mac; use touch support to detect
            const isiPadOS13 = ua.includes('Mac') && 'ontouchend' in document;
            return iOSIdentifiers.test(ua) || isiPadOS13;
        } catch (e) {
            return false;
        }
    }

    // Expose the iOS detection helper globally so that other modules and
    // closures (e.g. bindMobileFullscreenEvents in the Agora code) can
    // reference it. Without this assignment, vh360IsIOSDevice remains scoped
    // to this IIFE and calls outside of this closure will result in a
    // ReferenceError, as observed on iOS when the fullscreen button was
    // clicked. Attaching it to the window makes it accessible globally.
    window.vh360IsIOSDevice = vh360IsIOSDevice;
    var emailMessage = document.getElementById('videohub360-email-message');
    var emailToggle = document.getElementById('videohub360-email-toggle');
    var emailFormContainer = document.getElementById('videohub360-email-form-container');

    if (shareBtn) {
        shareBtn.addEventListener('click', function() {
            modalOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }
    function closeModal() {
        modalOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) closeModal();
        });
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modalOverlay.classList.contains('active')) closeModal();
    });
    if (copyBtn && linkInput) {
        copyBtn.addEventListener('click', function() {
            linkInput.select();
            linkInput.setSelectionRange(0, 99999);
            try {
                document.execCommand('copy');
                copyBtn.textContent = 'Copied!';
                copyBtn.classList.add('copied');
                setTimeout(function() {
                    copyBtn.textContent = 'Copy';
                    copyBtn.classList.remove('copied');
                }, 2000);
            } catch (err) {
                window.vh360Error('Failed to copy: ', err);
            }
        });
    }

    // Email form toggle functionality
    if (emailToggle && emailFormContainer) {
        emailToggle.addEventListener('click', function() {
            var isExpanded = emailFormContainer.classList.contains('expanded');

            if (isExpanded) {
                emailFormContainer.classList.remove('expanded');
                emailToggle.classList.remove('expanded');
            } else {
                emailFormContainer.classList.add('expanded');
                emailToggle.classList.add('expanded');
                // Focus on first input when expanded
                setTimeout(function() {
                    if (fromNameInput) fromNameInput.focus();
                }, 300);
            }
        });
    }

    if (emailForm) {
        emailForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var email = emailInput.value.trim();
            var fromName = fromNameInput.value.trim();
            var message = messageInput.value.trim();

            if (!email) {
                showEmailMessage('Please enter an email address.', 'error');
                return;
            }
            if (!fromName) {
                showEmailMessage('Please enter your name.', 'error');
                return;
            }

            // Provide default message if none provided
            if (!message) {
                message = 'Check out this video I thought you might enjoy!';
            }

            sendBtn.disabled = true;
            sendBtn.textContent = 'Sending...';
            var formData = new FormData();
            formData.append('action', 'videohub360_share_email');
            formData.append('to_email', email);
            formData.append('from_name', fromName);
            formData.append('message', message);
            formData.append('post_id', vh360Data.postId);
            formData.append('nonce', vh360Data.shareEmailNonce);
            fetch(vh360Data.ajaxUrl, { method: 'POST', body: formData })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    showEmailMessage(data.data || 'Email sent successfully!', 'success');
                    emailInput.value = '';
                    messageInput.value = 'Check out this video I thought you might enjoy!'; // Reset to default

                    // Change button text to indicate success
                    sendBtn.textContent = 'Sent!';

                    // Auto-close modal after showing success message briefly
                    setTimeout(function() {
                        closeModal();
                    }, 1500);
                } else {
                    // Enhanced error handling with specific messages
                    let errorMsg = data.data || 'Failed to send email. Please try again.';

                    // Provide more helpful error messages
                    if (errorMsg.includes('Rate limit')) {
                        showEmailMessage(errorMsg, 'warning');
                    } else if (errorMsg.includes('SMTP')) {
                        showEmailMessage(errorMsg + ' Check the email configuration in admin settings.', 'error');
                    } else if (errorMsg.includes('configuration error')) {
                        showEmailMessage(errorMsg + ' Please contact the site administrator.', 'error');
                    } else {
                        showEmailMessage(errorMsg, 'error');
                    }
                }
            })
            .catch(function(error) {
                window.vh360Error('Email sharing error:', error);
                showEmailMessage('Network error while sending email. Please check your connection and try again.', 'error');
            })
            .finally(function() {
                sendBtn.disabled = false;
                sendBtn.textContent = 'Send Link';
            });
        });
    }
    function showEmailMessage(message, type) {
        emailMessage.textContent = message;
        emailMessage.className = 'videohub360-email-message ' + type;
        emailMessage.style.display = 'block';

        // Add accessibility attribute for screen readers
        emailMessage.setAttribute('role', 'alert');
        emailMessage.setAttribute('aria-live', 'polite');

        // Ensure email form container is expanded so message is visible
        if (emailFormContainer && !emailFormContainer.classList.contains('expanded')) {
            emailFormContainer.classList.add('expanded');
            emailToggle.classList.add('expanded');
        }

        // Different timeout for different message types
        let timeout = 5000; // Default 5 seconds
        if (type === 'warning') timeout = 8000; // 8 seconds for warnings
        if (type === 'error') timeout = 10000; // 10 seconds for errors

        setTimeout(function() {
            emailMessage.style.display = 'none';
            emailMessage.removeAttribute('role');
            emailMessage.removeAttribute('aria-live');
        }, timeout);
    }
})();



