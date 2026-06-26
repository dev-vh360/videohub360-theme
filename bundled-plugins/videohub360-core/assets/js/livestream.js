/**
 * VideoHub360 Livestream Bootstrap
 * 
 * This file initializes the Agora player using configuration data
 * provided via wp_localize_script() from WordPress backend.
 * 
 * The configuration is passed through window.vh360Livestream object.
 */

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

document.addEventListener("DOMContentLoaded", function() {
    // Check if bootstrap data is available from wp_localize_script
    if (typeof window.vh360Livestream !== 'undefined') {
        window.currentRole = window.vh360Livestream.role;
        window.config = window.vh360Livestream;
        
        // Debug logging
        window.vh360Log("VideoHub360: Global variables initialized from wp_localize_script");
        window.vh360Log("Debug info from PHP:", window.vh360Livestream.debugInfo);
        window.vh360Log("Current role:", window.currentRole);
        window.vh360Log("Config:", window.config);
        window.vh360Log("vh360Data available:", typeof window.vh360Data !== "undefined");
        
        // Validate that admin users are getting host role in broadcast mode
        if (window.config.agoraMode === "broadcast" && window.config.isOriginalHost && window.currentRole !== "host") {
            console.error("CRITICAL ERROR: Admin user should be host in broadcast mode but got role:", window.currentRole);
            console.error("This will cause the publish error. Check user capabilities and role logic.");
        }
        
        // Initialize Agora player
        if (typeof window.initializeAgoraPlayer === "function") {
            window.initializeAgoraPlayer(window.config);
        } else {
            window.vh360Warn("VideoHub360: initializeAgoraPlayer function not yet available, waiting...");
            var playerElement = document.getElementById("vh360-agora-player");
            if (playerElement) {
                playerElement.innerHTML = "<div class=\"vh360-loading-message\">Loading...</div>";
            }
            // Retry after a short delay to allow agora-player.js to load
            setTimeout(function() {
                if (typeof window.initializeAgoraPlayer === "function") {
                    window.initializeAgoraPlayer(window.config);
                } else {
                    console.error("VideoHub360: Failed to load Agora player. Reloading page...");
                    location.reload();
                }
            }, 3000);
        }
    } else {
        window.vh360Warn("VideoHub360: No livestream bootstrap data found (window.vh360Livestream is undefined)");
    }
});
