/* VideoHub360 patched: vh360 namespace (non-destructive) */
if (typeof window !== 'undefined') {
  window.vh360 = window.vh360 || {};
}

/**
 * VideoHub360 Video Player Component
 * 
 * Handles video playback with ad integration (preroll, midroll, postroll)
 * Extracted from main frontend.js for better organization
 */

// Video Player Ad and Main Video Logic
(function() {
    var playBtn = document.getElementById('videohub360-static-play-btn');
    var posterWrap = document.getElementById('videohub360-static-poster-wrap');
    var adContainer = document.getElementById('videohub360-ad-container');
    var adVideo = document.getElementById('videohub360-ad-video');
    var skipBtn = document.getElementById('videohub360-ad-skip-btn');
    var skipMsg = document.getElementById('videohub360-ad-skip-msg');
    var adLabel = document.getElementById('videohub360-ad-label');
    var mainContainer = document.getElementById('videohub360-main-container');
    var mainVideo = document.getElementById('videohub360-main-video');
    var adUrl = adVideo && adVideo.querySelector('source') ? adVideo.querySelector('source').src : '';
    var hasAd = !!(adUrl && typeof adUrl === 'string' && adUrl.trim() !== '');
    var skipDelay = 5;
    var skipSecondsLeft = skipDelay;
    var skipInterval;

    // New variables for mid-roll and post-roll functionality
    var midrollAdUrl = '';
    var midrollTiming = [];
    var postrollAdUrl = '';
    var postrollEnabled = false;
    var shownMidrollAds = [];
    var isPlayingAd = false;
    var pausedVideoTime = 0;
    var currentAdType = 'preroll'; // 'preroll', 'midroll', 'postroll'
    var hasInitializedAdTracking = false;
    
    // Ad click-through variables
    var adClickUrls = {
        preroll: '',
        midroll: '',
        postroll: ''
    };
    var adClickNewTab = true;
    var adClickTrackingEnabled = false;
    var currentPostId = 0;

    // Fullscreen utility functions for cross-browser compatibility
    function isInFullscreen() {
        return !!(document.fullscreenElement || 
                 document.webkitFullscreenElement || 
                 document.mozFullScreenElement || 
                 document.msFullscreenElement);
    }

    function exitFullscreen() {
        if (!isInFullscreen()) return Promise.resolve();
        
        if (document.exitFullscreen) {
            return document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            return document.webkitExitFullscreen();
        } else if (document.mozCancelFullScreen) {
            return document.mozCancelFullScreen();
        } else if (document.msExitFullscreen) {
            return document.msExitFullscreen();
        }
        return Promise.resolve();
    }

    function enterFullscreen(element) {
        if (isInFullscreen()) return Promise.resolve();
        
        if (!element) return Promise.reject(new Error('No element provided'));
        
        if (element.requestFullscreen) {
            return element.requestFullscreen();
        } else if (element.webkitRequestFullscreen) {
            return element.webkitRequestFullscreen();
        } else if (element.mozRequestFullScreen) {
            return element.mozRequestFullScreen();
        } else if (element.msRequestFullscreen) {
            return element.msRequestFullscreen();
        }
        return Promise.reject(new Error('Fullscreen not supported on this element'));
    }

    // Attach fullscreen functions to window for global access
    window.isInFullscreen = isInFullscreen;
    window.exitFullscreen = exitFullscreen;
    window.enterFullscreen = enterFullscreen;

    // Get ad data from video elements or global settings
    function initializeAdData() {
        if (hasInitializedAdTracking) return;
        
        // Get ad click-through URLs and settings from ad container
        if (adContainer) {
            adClickUrls.preroll = adContainer.getAttribute('data-ad-url') || '';
            adClickNewTab = adContainer.getAttribute('data-ad-new-tab') === '1';
            adClickTrackingEnabled = adContainer.getAttribute('data-tracking-enabled') === '1';
            currentPostId = parseInt(adContainer.getAttribute('data-post-id')) || 0;
        }
        
        // Get mid-roll click URL
        var midrollClickElement = document.querySelector('#videohub360-midroll-click-url');
        if (midrollClickElement) {
            adClickUrls.midroll = midrollClickElement.textContent || '';
        }
        
        // Get post-roll click URL
        var postrollClickElement = document.querySelector('#videohub360-postroll-click-url');
        if (postrollClickElement) {
            adClickUrls.postroll = postrollClickElement.textContent || '';
        }
        
        // Get mid-roll ad URL (per-video or global)
        var midrollAdSource = document.querySelector('#videohub360-midroll-ad-source');
        if (midrollAdSource) {
            var rawMidrollUrl = midrollAdSource.src || midrollAdSource.textContent || '';
            midrollAdUrl = (rawMidrollUrl && typeof rawMidrollUrl === 'string' && rawMidrollUrl.trim() !== '') ? rawMidrollUrl.trim() : '';
        }
        
        // Get mid-roll timing (per-video or global)
        var midrollTimingElement = document.querySelector('#videohub360-midroll-timing');
        if (midrollTimingElement) {
            var timingStr = midrollTimingElement.textContent || '';
            if (timingStr) {
                midrollTiming = timingStr.split(',').map(function(t) {
                    return parseInt(t.trim());
                }).filter(function(t) {
                    return !isNaN(t) && t > 0;
                });
            }
        }
        
        // Get post-roll ad URL (per-video or global)
        var postrollAdSource = document.querySelector('#videohub360-postroll-ad-source');
        if (postrollAdSource) {
            var rawPostrollUrl = postrollAdSource.src || postrollAdSource.textContent || '';
            postrollAdUrl = (rawPostrollUrl && typeof rawPostrollUrl === 'string' && rawPostrollUrl.trim() !== '') ? rawPostrollUrl.trim() : '';
        }
        
        // Get post-roll enabled status (per-video or global)
        var postrollEnabledElement = document.querySelector('#videohub360-postroll-enabled');
        if (postrollEnabledElement) {
            postrollEnabled = postrollEnabledElement.textContent === 'yes';
        }
        
        hasInitializedAdTracking = true;
    }

    function showMainContent() {
        // Stop ad video playback and reset
        if (adVideo) {
            adVideo.pause();
            adVideo.currentTime = 0;
        }
        
        if (adContainer) adContainer.classList.add('videohub360-hide');
        if (mainContainer) mainContainer.classList.remove('videohub360-hide');
        if (mainVideo) {
            if (isPlayingAd && pausedVideoTime > 0) {
                // Resume from where we paused for mid-roll ad
                mainVideo.currentTime = pausedVideoTime;
                pausedVideoTime = 0;
            }
            var playPromise = mainVideo.play && mainVideo.play();
            if (playPromise) playPromise.catch(function(){});
        }
        isPlayingAd = false;
    }

    function showAd(adType, adUrl) {
        // Strengthen validation to catch empty strings, whitespace, and invalid URLs
        if (!adUrl || typeof adUrl !== 'string' || adUrl.trim() === '') {
            return false;
        }
        
        // Exit fullscreen if currently in fullscreen mode before showing ad
        if (isInFullscreen()) {
            exitFullscreen().then(function() {
                // Continue with ad display after exiting fullscreen
                displayAd(adType, adUrl);
            }).catch(function() {
                // If exit fullscreen fails, still try to show the ad
                displayAd(adType, adUrl);
            });
            return true;
        }
        
        // If not in fullscreen, show ad immediately
        return displayAd(adType, adUrl);
    }

    function displayAd(adType, adUrl) {
        adType = adType || 'preroll';
        currentAdType = adType;
        isPlayingAd = true;
        
        // Update ad container's click URL for the current ad type
        if (adContainer) {
            var clickUrl = adClickUrls[adType] || '';
            adContainer.setAttribute('data-ad-url', clickUrl);
            adContainer.setAttribute('data-ad-type', adType);
            
            // Update clickable class
            if (clickUrl) {
                adContainer.classList.add('videohub360-ad-clickable');
            } else {
                adContainer.classList.remove('videohub360-ad-clickable');
            }
        }
        
        // Update ad label if needed
        if (adLabel) {
            var labelText = 'Advertisement';
            if (adType === 'midroll') labelText = 'Advertisement';
            else if (adType === 'postroll') labelText = 'Advertisement';
            adLabel.textContent = labelText;
        }
        
        // Hide poster/main content and show ad
        if (posterWrap) posterWrap.classList.add('videohub360-hide');
        if (mainContainer) mainContainer.classList.add('videohub360-hide');
        if (adContainer) adContainer.classList.remove('videohub360-hide');
        
        if (adVideo) {
            // Update ad video source if different
            var currentAdSource = adVideo.querySelector('source');
            if (currentAdSource && currentAdSource.src !== adUrl) {
                currentAdSource.src = adUrl;
                adVideo.load();
            }
            
            adVideo.currentTime = 0;
            adVideo.muted = false;
            var playPromise = adVideo.play && adVideo.play();
            if (playPromise) playPromise.catch(function(){});
        }
        
        // Set up skip countdown
        skipSecondsLeft = skipDelay;
        if (skipMsg) {
            skipMsg.textContent = "Skip this ad in " + skipSecondsLeft + " seconds";
            skipMsg.style.display = 'inline-block';
        }
        if (skipBtn) skipBtn.style.display = 'none';
        
        if (skipInterval) clearInterval(skipInterval);
        skipInterval = setInterval(function() {
            skipSecondsLeft--;
            if (skipSecondsLeft > 0) {
                if (skipMsg) skipMsg.textContent = "Skip this ad in " + skipSecondsLeft + " second" + (skipSecondsLeft !== 1 ? "s" : "");
            } else {
                if (skipBtn) skipBtn.style.display = 'block';
                if (skipMsg) skipMsg.style.display = 'none';
                clearInterval(skipInterval);
            }
        }, 1000);
        
        return true;
    }

    function handleAdEnd() {
        if (currentAdType === 'postroll') {
            // Post-roll ended, video is complete
            // Stop ad video playback and reset
            if (adVideo) {
                adVideo.pause();
                adVideo.currentTime = 0;
            }
            
            if (adContainer) adContainer.classList.add('videohub360-hide');
            if (mainContainer) mainContainer.classList.remove('videohub360-hide');
            // Don't resume main video since it's already ended
            isPlayingAd = false;
        } else {
            // Pre-roll or mid-roll ended, continue main video
            showMainContent();
        }
    }

    function showMidrollAd(timeSeconds) {
        if (!midrollAdUrl || typeof midrollAdUrl !== 'string' || midrollAdUrl.trim() === '' || shownMidrollAds.indexOf(timeSeconds) !== -1) {
            return false; // No ad URL or already shown this ad
        }
        
        // Pause main video and store time
        if (mainVideo && !mainVideo.paused) {
            pausedVideoTime = mainVideo.currentTime;
            mainVideo.pause();
        }
        
        // Mark this mid-roll as shown
        shownMidrollAds.push(timeSeconds);
        
        // Show the mid-roll ad
        return showAd('midroll', midrollAdUrl);
    }

    function showPostrollAd() {
        if (!postrollEnabled || !postrollAdUrl || typeof postrollAdUrl !== 'string' || postrollAdUrl.trim() === '') {
            return false;
        }
        
        return showAd('postroll', postrollAdUrl);
    }

    function checkForMidrollAds() {
        if (!mainVideo || isPlayingAd || midrollTiming.length === 0) return;
        
        var currentTime = Math.floor(mainVideo.currentTime);
        
        // Check if we've reached any mid-roll timing
        for (var i = 0; i < midrollTiming.length; i++) {
            var adTime = midrollTiming[i];
            // Show ad if we're at or past the ad time and haven't shown it yet
            if (currentTime >= adTime && shownMidrollAds.indexOf(adTime) === -1) {
                showMidrollAd(adTime);
                break; // Only show one ad at a time
            }
        }
    }

    function handleVideoSeeking() {
        // If user seeks past mid-roll points, mark them as shown to avoid interruption
        if (!mainVideo || midrollTiming.length === 0) return;
        
        var currentTime = Math.floor(mainVideo.currentTime);
        
        for (var i = 0; i < midrollTiming.length; i++) {
            var adTime = midrollTiming[i];
            if (currentTime > adTime && shownMidrollAds.indexOf(adTime) === -1) {
                shownMidrollAds.push(adTime);
            }
        }
    }

    function showMainDirect() {
        if (posterWrap) posterWrap.classList.add('videohub360-hide');
        if (mainContainer) mainContainer.classList.remove('videohub360-hide');
        if (mainVideo) {
            var playPromise = mainVideo.play && mainVideo.play();
            if (playPromise) playPromise.catch(function(){});
        }
    }

    // Check if this is a custom HTML video
    function isCustomHtmlVideo() {
        return document.querySelector('.videohub360-custom-embed-container') !== null;
    }

    // Set up video event listeners for mid-roll and post-roll
    function setupVideoEventListeners() {
        // Skip event listener setup for custom HTML videos
        if (isCustomHtmlVideo()) {
            // Still set up ad control event listeners for pre-roll ads
            if (skipBtn) {
                skipBtn.onclick = function() {
                    clearInterval(skipInterval);
                    if (skipMsg) skipMsg.style.display = 'none';
                    handleAdEnd();
                };
            }
            
            // Set up ad click handler
            setupAdClickHandler();
            
            if (adVideo) {
                adVideo.addEventListener('ended', function() {
                    clearInterval(skipInterval);
                    if (skipMsg) skipMsg.style.display = 'none';
                    handleAdEnd();
                });
            }
            return;
        }
        
        if (!mainVideo) return;
        
        // Monitor video time for mid-roll ads
        mainVideo.addEventListener('timeupdate', function() {
            if (!isPlayingAd) {
                checkForMidrollAds();
            }
        });
        
        // Handle video seeking
        mainVideo.addEventListener('seeked', function() {
            if (!isPlayingAd) {
                handleVideoSeeking();
            }
        });
        
        // Handle video end for post-roll
        mainVideo.addEventListener('ended', function() {
            if (!isPlayingAd) {
                showPostrollAd();
            }
        });
        
        // Set up ad control event listeners (only once)
        if (skipBtn) {
            skipBtn.onclick = function() {
                clearInterval(skipInterval);
                if (skipMsg) skipMsg.style.display = 'none';
                handleAdEnd();
            };
        }
        
        // Set up ad click handler
        setupAdClickHandler();
        
        if (adVideo) {
            adVideo.addEventListener('ended', function() {
                clearInterval(skipInterval);
                if (skipMsg) skipMsg.style.display = 'none';
                handleAdEnd();
            });
        }
    }
    
    // Set up ad click-through handler
    function setupAdClickHandler() {
        if (!adContainer) return;
        
        var adClickOverlay = adContainer.querySelector('.videohub360-ad-click-overlay');
        if (!adClickOverlay) return;
        
        // Handle click on overlay
        adClickOverlay.addEventListener('click', function(e) {
            handleAdClick(e);
        });
        
        // Handle keyboard navigation (Enter or Space)
        adClickOverlay.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                handleAdClick(e);
            }
        });
    }
    
    // Handle ad click
    function handleAdClick(e) {
        var clickUrl = adContainer.getAttribute('data-ad-url');
        var adType = adContainer.getAttribute('data-ad-type') || 'preroll';
        var newTab = adContainer.getAttribute('data-ad-new-tab') === '1';
        var trackingEnabled = adContainer.getAttribute('data-tracking-enabled') === '1';
        var postId = parseInt(adContainer.getAttribute('data-post-id')) || 0;
        
        // Validate click URL
        if (!clickUrl || clickUrl.trim() === '') {
            return;
        }
        
        // Track the click if enabled
        if (trackingEnabled && postId > 0) {
            trackAdClick(postId, adType);
        }
        
        // Open the URL
        if (newTab) {
            window.open(clickUrl, '_blank', 'noopener,noreferrer');
        } else {
            window.location.href = clickUrl;
        }
    }
    
    // Track ad click via AJAX
    function trackAdClick(postId, adType) {
        // Fire and forget - don't block navigation
        if (!window.vh360Ajax || !window.vh360Ajax.ajaxurl) {
            return;
        }
        
        // Prepare data as URL-encoded string for both sendBeacon and fetch
        var params = new URLSearchParams();
        params.append('action', 'vh360_track_ad_click');
        params.append('post_id', postId);
        params.append('ad_type', adType);
        params.append('nonce', window.vh360Ajax.nonce || '');
        
        // Use sendBeacon if available (recommended for tracking on navigation)
        if (navigator.sendBeacon) {
            // sendBeacon with application/x-www-form-urlencoded content type
            var blob = new Blob([params.toString()], { type: 'application/x-www-form-urlencoded' });
            navigator.sendBeacon(window.vh360Ajax.ajaxurl, blob);
        } else {
            // Fallback to fetch with keepalive
            fetch(window.vh360Ajax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: params.toString(),
                keepalive: true
            }).catch(function() {
                // Silently fail - tracking is not critical
            });
        }
    }

    // Initialize on play button click
    if (playBtn) {
        playBtn.addEventListener('click', function() {
            initializeAdData();
            setupVideoEventListeners();
            
            if (hasAd) {
                showAd('preroll', adUrl);
            } else {
                showMainDirect();
            }
        });
    }
})();

/* Namespacing compatibility: copy any existing window.* functions into window.vh360 (non-destructive) */
;(function(){
  window.vh360 = window.vh360 || {};
  var _names = ['enterFullscreen', 'exitFullscreen', 'isInFullscreen'];
  _names.forEach(function(n){ try{ if (window[n] && !window.vh360[n]) window.vh360[n] = window[n]; }catch(e){} });
})();
