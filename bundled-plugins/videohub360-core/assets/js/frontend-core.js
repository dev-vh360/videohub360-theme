/* VideoHub360 patched: vh360 namespace (non-destructive) */
if (typeof window !== 'undefined') {
  window.vh360 = window.vh360 || {};
}

/**
 * VideoHub360 Frontend Core Component
 * 
 * Contains core frontend functionality including live stats and archive filtering
 * 
 * Note: Share modal logic has been moved to frontend.js to avoid duplication
 * and is handled there with proper initialization guards.
 */

// Live Viewer Count AJAX
(function(){
    var countEl = document.getElementById('vh360-viewer-count-meta');
    var pageId = vh360Data.postId;
    function fetchViewerCount(){
        if (!countEl) return;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', vh360Data.ajaxUrl);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function(){
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success && data.data && typeof data.data.count !== 'undefined') {
                        countEl.textContent = data.data.count;
                    }
                } catch(e){}
            }
        };
        xhr.send('action=vh360_live_viewers&page_id=' + encodeURIComponent(pageId) + '&nonce=' + encodeURIComponent(vh360Data.chatNonce));
    }
    if (countEl) {
        fetchViewerCount();
        (function(){ var __id = setInterval(fetchViewerCount, 20000); (window.__vh360Intervals = window.__vh360Intervals || []).push(__id); return __id; })();
    }
})();

// Live Start Time Elapsed
(function(){
    var startedEl = document.getElementById('vh360-stream-started-meta');
    var endedEl = document.getElementById('vh360-stream-ended-meta');
    var targetEl = startedEl || endedEl;
    if(targetEl && targetEl.dataset.start){
        function updateStarted() {
            var start = targetEl.dataset.start;
            var startTime = new Date(start);
            var now = new Date();
            var diffMs = now - startTime;
            if (isNaN(diffMs) || diffMs < 0) {
                targetEl.textContent = '';
                return;
            }
            var totalSeconds = Math.floor(diffMs/1000);
            var totalMinutes = Math.floor(totalSeconds/60);
            var hours = Math.floor(totalMinutes/60);
            var days = Math.floor(hours/24);
            var display = targetEl === startedEl ? 'Started streaming ' : 'Streamed ';
            if (days > 0) display += days + ' day' + (days > 1 ? 's' : '') + ' ago';
            else if (hours > 0) display += hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
            else if (totalMinutes > 0) display += totalMinutes + ' minute' + (totalMinutes > 1 ? 's' : '') + ' ago';
            else display += 'just now';
            targetEl.textContent = display;
        }
        updateStarted();
        (function(){ var __id = setInterval(updateStarted, 60000); (window.__vh360Intervals = window.__vh360Intervals || []).push(__id); return __id; })();
    }
})();

// VideoHub360 Archive Page: Responsive Filter Sidebar
(function(){
    var toggleBtn = document.getElementById('videohub360-filter-toggle-btn');
    var sidebar = document.getElementById('videohub360-sidebar');
    var overlay = document.getElementById('videohub360-filter-overlay');
    var closeBtn = document.getElementById('videohub360-filter-close-btn');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('videohub360-show');
        if (overlay) overlay.classList.add('videohub360-show');
        document.body.classList.add('videohub360-filter-open');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
        if (sidebar) sidebar.focus();
    }
    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('videohub360-show');
        if (overlay) overlay.classList.remove('videohub360-show');
        document.body.classList.remove('videohub360-filter-open');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
        if (toggleBtn) toggleBtn.focus();
    }
    if (toggleBtn && sidebar && overlay && closeBtn) {
        toggleBtn.addEventListener('click', openSidebar);
        closeBtn.addEventListener('click', closeSidebar);
        overlay.addEventListener('click', closeSidebar);
        document.addEventListener('keydown', function(e){
            if ((sidebar.classList.contains('videohub360-show') || overlay.classList.contains('videohub360-show')) && e.key === "Escape") {
                closeSidebar();
            }
        });
    }
})();


/* Namespacing compatibility: copy any existing window.* functions into window.vh360 (non-destructive) */
;(function(){
  window.vh360 = window.vh360 || {};
  var _names = ['addEventListener'];
  _names.forEach(function(n){ try{ if (window[n] && !window.vh360[n]) window.vh360[n] = window[n]; }catch(e){} });
})();

// Batch Live Viewer Count Polling for Widget Cards
(function(){
    // Function to update live viewer counts for all visible badges
    function updateBatchLiveViewers() {
        var badges = document.querySelectorAll('.vh360-live-viewers-badge');
        if (!badges || badges.length === 0) return;
        
        var pageIds = [];
        var badgeMap = {};
        
        // Collect all page IDs and map them to their badge elements
        // Note: Multiple badges can have the same post ID if video appears in multiple widgets
        badges.forEach(function(badge) {
            var postId = badge.getAttribute('data-post-id');
            if (postId) {
                postId = parseInt(postId, 10);
                if (!isNaN(postId) && postId > 0) {
                    // Add to pageIds array if not already present (avoid duplicate AJAX requests)
                    if (pageIds.indexOf(postId) === -1) {
                        pageIds.push(postId);
                    }
                    // Store badges in an array for each post ID to handle duplicates
                    if (!badgeMap[postId]) {
                        badgeMap[postId] = [];
                    }
                    badgeMap[postId].push(badge);
                }
            }
        });
        
        if (pageIds.length === 0) return;
        
        // Send batch request
        var xhr = new XMLHttpRequest();
        xhr.open('POST', vh360Data.ajaxUrl);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function(){
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    
                    if (data.success && data.data && data.data.counts) {
                        var counts = data.data.counts;
                        
                        // Update each badge with its count
                        for (var postId in counts) {
                            if (counts.hasOwnProperty(postId) && badgeMap[postId]) {
                                var count = counts[postId];
                                // Update all badges for this post ID (handles duplicates)
                                badgeMap[postId].forEach(function(badge) {
                                    var countEl = badge.querySelector('.vh360-viewer-count');
                                    if (countEl) {
                                        countEl.textContent = count;
                                    }
                                });
                            }
                        }
                    }
                } catch(e) {
                    // Silently fail - badges will retry on next interval
                }
            }
        };
        
        // Encode page IDs as array
        var params = 'action=vh360_live_viewers_batch&nonce=' + encodeURIComponent(vh360Data.chatNonce);
        pageIds.forEach(function(id) {
            params += '&page_ids[]=' + encodeURIComponent(id);
        });
        
        xhr.send(params);
    }
    
    // Run on DOM ready and then every 15 seconds
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            updateBatchLiveViewers();
            (function(){ 
                var __id = setInterval(updateBatchLiveViewers, 15000); 
                (window.__vh360Intervals = window.__vh360Intervals || []).push(__id); 
                return __id; 
            })();
        });
    } else {
        updateBatchLiveViewers();
        (function(){ 
            var __id = setInterval(updateBatchLiveViewers, 15000); 
            (window.__vh360Intervals = window.__vh360Intervals || []).push(__id); 
            return __id; 
        })();
    }
})();
