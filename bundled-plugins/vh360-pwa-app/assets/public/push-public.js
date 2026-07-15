/* global VH360Push, OneSignalDeferred */
/**
 * VH360 Push Notifications - Public JavaScript
 */
(function() {
	'use strict';


function vh360GetScrollContext() {
	if (window.VH360ScrollContext) {
		return window.VH360ScrollContext;
	}

	return {
		getElement: function() {
			var shellScroller = document.querySelector('[data-vh360-pwa-scroll]');
			var standalone = document.documentElement.classList.contains('vh360-pwa-standalone') || window.navigator.standalone === true || (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches);
			var active = document.documentElement.classList.contains('vh360-pwa-app-shell-active');
			return active && shellScroller ? shellScroller : window;
		},
		getScrollTop: function() { var element = this.getElement(); return element === window ? (window.scrollY || window.pageYOffset || 0) : element.scrollTop; },
		getViewportHeight: function() { var element = this.getElement(); return element === window ? (window.innerHeight || document.documentElement.clientHeight) : element.clientHeight; },
		getScrollHeight: function() { var element = this.getElement(); return element === window ? Math.max(document.body.scrollHeight, document.documentElement.scrollHeight) : element.scrollHeight; }
	};
}

function vh360GetScrollEventTarget() {
	var element = vh360GetScrollContext().getElement();
	return element === window ? window : element;
}


	// Debug logging helper - only log when __VH360_DEBUG is enabled
	const vh360Log = (...args) => { if (window.__VH360_DEBUG) console.log(...args); };

	if (typeof VH360Push === 'undefined') {
		return;
	}

	/**
	 * Detect unsupported contexts (incognito, iOS, etc.)
	 */
	function detectUnsupportedContext() {
		var warnings = [];

		// Detect private/incognito mode
		try {
			// Check for IndexedDB support (disabled in many private modes)
			if (!window.indexedDB) {
				warnings.push({
					type: 'incognito',
					message: 'Private browsing detected. Push notifications are not available in private/incognito mode.'
				});
			} else {
				// Additional check using localStorage persistence
				try {
					var testKey = '__vh360_storage_test__';
					localStorage.setItem(testKey, '1');
					localStorage.removeItem(testKey);
				} catch (e) {
					warnings.push({
						type: 'incognito',
						message: 'Private browsing may be enabled. Push notifications might not work properly.'
					});
				}
			}
		} catch (e) {
			// Storage access error might indicate private mode
		}

		// Detect iOS Safari limitations
		var isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
		if (isIOS) {
			// iOS 16.4+ supports web push, but with limitations
			var match = navigator.userAgent.match(/OS (\d+)_(\d+)/);
			if (match) {
				var major = parseInt(match[1], 10);
				var minor = parseInt(match[2], 10);
				var version = major + (minor / 10);
				
				if (version < 16.4) {
					warnings.push({
						type: 'ios',
						message: 'Your iOS version does not support push notifications. Please update to iOS 16.4 or later.'
					});
				} else {
					warnings.push({
						type: 'ios_info',
						message: 'Note: On iOS, you must add this site to your Home Screen to enable push notifications.'
					});
				}
			}
		}

		// Check for service worker support
		if (!('serviceWorker' in navigator)) {
			warnings.push({
				type: 'no_sw',
				message: 'Your browser does not support push notifications.'
			});
		}

		// Check for HTTPS (required for push)
		if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
			warnings.push({
				type: 'no_https',
				message: 'Push notifications require a secure connection (HTTPS).'
			});
		}

		return warnings;
	}

	/**
	 * Display context warnings in subscription widgets
	 */
	function displayContextWarnings() {
		var warnings = detectUnsupportedContext();
		
		if (warnings.length === 0) {
			return;
		}

		var containers = document.querySelectorAll('[data-vh360-push-subscribe]');
		containers.forEach(function(container) {
			// Check if warning state div exists
			var warningState = container.querySelector('.vh360-push-warning-state');
			if (!warningState) {
				// Create warning state if it doesn't exist
				warningState = document.createElement('div');
				warningState.className = 'vh360-push-warning-state vh360-push-state';
				warningState.style.display = 'none';
				container.appendChild(warningState);
			}

			// Build warning content
			var warningHTML = '<div class="vh360-push-warning">';
			warnings.forEach(function(warning) {
				var icon = warning.type === 'ios_info' ? 'ℹ️' : '⚠️';
				var msgDiv = document.createElement('p');
				var iconSpan = document.createElement('span');
				iconSpan.className = 'vh360-push-warning-icon';
				iconSpan.textContent = icon;
				msgDiv.appendChild(iconSpan);
				msgDiv.appendChild(document.createTextNode(warning.message));
				warningHTML += msgDiv.outerHTML;
			});
			warningHTML += '</div>';

			warningState.innerHTML = warningHTML;

			// Show warning state if we have critical warnings
			var hasCritical = warnings.some(function(w) {
				return w.type !== 'ios_info';
			});

			if (hasCritical) {
				hideAllStates(container);
				warningState.style.display = 'block';
			}
		});
	}

	// Initialize OneSignal
	function initOneSignal() {
		if (typeof OneSignalDeferred === 'undefined') {
			console.error('OneSignal SDK not loaded');
			return;
		}

		window.OneSignalDeferred = window.OneSignalDeferred || [];
		OneSignalDeferred.push(async function(OneSignal) {
			try {
				await OneSignal.init({
					appId: VH360Push.appId,
					serviceWorkerParam: {
						scope: VH360Push.swScope || '/'
					},
					serviceWorkerPath: VH360Push.swPath || '/vh360-sw.js',
					serviceWorkerUpdaterPath: VH360Push.swUpdaterPath || (VH360Push.swPath || '/vh360-sw.js'),
					allowLocalhostAsSecureOrigin: true,
					autoResubscribe: true,
					autoRegister: false,
					notificationClickHandlerMatch: 'origin',
					notificationClickHandlerAction: 'navigate'
				});

				// Set external user ID if user is logged in
				if (VH360Push.currentUserId && VH360Push.currentUserId > 0) {
					try {
						await OneSignal.login(String(VH360Push.currentUserId));
						vh360Log('[VH360 Push] OneSignal external user ID set:', VH360Push.currentUserId);
					} catch (err) {
						console.error('[VH360 Push] Failed to set OneSignal external user ID:', err);
					}
				}

				// Update UI based on subscription state
				updateSubscriptionUI();

				// Listen for permission/subscription changes.
				if (OneSignal && OneSignal.Notifications && typeof OneSignal.Notifications.addEventListener === 'function') {
					try {
						OneSignal.Notifications.addEventListener('permissionChange', function() {
							updateSubscriptionUI();
						});
						OneSignal.Notifications.addEventListener('subscriptionChange', function() {
							updateSubscriptionUI();
						});
					} catch (e) {
						// no-op
					}
				}

				// Handle auto-prompt with delay
				if (VH360Push.autoPrompt && VH360Push.autoPromptDelay > 0) {
					setTimeout(function() {
						OneSignal.Slidedown.promptPush();
					}, VH360Push.autoPromptDelay * 1000);
				}
			
				// Handle auto-prompt on scroll (optional)
				if (VH360Push.autoPrompt && VH360Push.autoPromptScroll) {
					var vh360ScrollTriggered = false;
					var vh360PushScrollTarget = null;
					function vh360PushScrollHandler() {
						if (vh360ScrollTriggered) return;
						var scrollContext = vh360GetScrollContext();
						var docHeight = scrollContext.getScrollHeight();
						var winHeight = scrollContext.getViewportHeight();
						if (docHeight <= winHeight) return;
						var percent = (scrollContext.getScrollTop() / (docHeight - winHeight)) * 100;
						if (percent >= 50) {
							vh360ScrollTriggered = true;
							if (vh360PushScrollTarget) {
								vh360PushScrollTarget.removeEventListener('scroll', vh360PushScrollHandler);
							}
							OneSignal.Slidedown.promptPush();
						}
					}
					function vh360BindPushScrollTarget() {
						var nextTarget = vh360GetScrollEventTarget();
						if (vh360PushScrollTarget) {
							vh360PushScrollTarget.removeEventListener('scroll', vh360PushScrollHandler);
						}
						vh360PushScrollTarget = nextTarget;
						if (!vh360ScrollTriggered) {
							vh360PushScrollTarget.addEventListener('scroll', vh360PushScrollHandler, { passive: true });
						}
					}
					vh360BindPushScrollTarget();
					window.addEventListener('vh360:scrollcontextchange', vh360BindPushScrollTarget);
				}

				// Handle auto-prompt after login (optional)
				if (VH360Push.autoPrompt && VH360Push.autoPromptLogin) {
					document.addEventListener('vh360:user:login', function() {
						OneSignal.Slidedown.promptPush();
					});
				}

			} catch (error) {
				console.error('OneSignal initialization error:', error);
			}
		});
	}

	function getNativePermission(OneSignal) {
		try {
			if (!OneSignal || !OneSignal.Notifications) {
				return Promise.resolve('default');
			}

			var p = OneSignal.Notifications.permission;
			if (typeof p === 'string') return Promise.resolve(p);
			if (p && typeof p.then === 'function') return p;

			var pn = OneSignal.Notifications.permissionNative;
			if (typeof pn === 'string') return Promise.resolve(pn);
			if (pn && typeof pn.then === 'function') return pn;
			if (typeof pn === 'function') return Promise.resolve(pn.call(OneSignal.Notifications));
		} catch (e) {}
		return Promise.resolve('default');
	}

	// Update subscription UI
	function updateSubscriptionUI() {
		var containers = document.querySelectorAll('[data-vh360-push-subscribe]');
		if (!containers.length) {
			return;
		}

		if (typeof OneSignalDeferred === 'undefined' || typeof window.OneSignal === 'undefined') {
			// Show unsupported
			containers.forEach(function(container) {
				hideAllStates(container);
				showState(container, 'unsupported');
			});
			return;
		}

		OneSignalDeferred.push(async function(OneSignal) {
			try {
				var isPushSupported = OneSignal.Notifications.isPushSupported();
				
				if (!isPushSupported) {
					containers.forEach(function(container) {
						hideAllStates(container);
						showState(container, 'unsupported');
					});
					return;
				}

				var permission = await getNativePermission(OneSignal);
				var sub = await getSubscriptionState(OneSignal);
				
				containers.forEach(function(container) {
					hideAllStates(container);
					
					// If we're actually opted-in (or have a subscription id), show subscribed.
					if (sub && (sub.optedIn || sub.id)) {
						showState(container, 'subscribed');
					} else if (permission === 'granted') {
						// Permission granted but no subscription yet.
						showState(container, 'unsubscribed');
					} else if (permission === 'denied') {
						showState(container, 'blocked');
					} else {
						showState(container, 'unsubscribed');
					}
				});
			} catch (error) {
				console.error('Error checking subscription state:', error);
				containers.forEach(function(container) {
					hideAllStates(container);
					showState(container, 'unsupported');
				});
			}
		});
	}

	function hideAllStates(container) {
		var states = container.querySelectorAll('.vh360-push-state');
		states.forEach(function(state) {
			state.style.display = 'none';
		});
	}

	function showState(container, stateName) {
		var state = container.querySelector('.vh360-push-' + stateName);
		if (state) {
			state.style.display = 'block';
		}
	}

	// Handle subscribe button clicks (use closest() so clicks on inner elements still work)
	document.addEventListener('click', function(e) {
		var btn = null;
		if (e.target && typeof e.target.closest === 'function') {
			btn = e.target.closest('[data-vh360-push-action="subscribe"]');
		} else if (e.target && e.target.matches && e.target.matches('[data-vh360-push-action="subscribe"]')) {
			btn = e.target;
		}
		if (!btn) {
			return;
		}

		// Prevent any surrounding dashboard handlers from treating this like a form action.
		e.preventDefault();
		e.stopPropagation();
		if (typeof e.stopImmediatePropagation === 'function') {
			e.stopImmediatePropagation();
		}

		handleSubscribe(btn);
	});

	async function getSubscriptionState(OneSignal) {
		// We prefer real subscription state over permission-only checks.
		try {
			if (OneSignal && OneSignal.User && OneSignal.User.PushSubscription) {
				var ps = OneSignal.User.PushSubscription;
				var optedIn = ps.optedIn;
				if (typeof optedIn === 'function') {
					optedIn = await optedIn.call(ps);
				}
				var id = ps.id;
				if (typeof id === 'function') {
					id = await id.call(ps);
				}
				return {
					optedIn: !!optedIn,
					id: id || ''
				};
			}
		} catch (e) {}
		return { optedIn: false, id: '' };
	}

	function handleSubscribe(button) {
		if (typeof OneSignalDeferred === 'undefined') {
			alert('Push notifications are not available.');
			return;
		}

		var container = button.closest('[data-vh360-push-subscribe]');
		if (container) {
			hideAllStates(container);
			showState(container, 'loading');
		}

		OneSignalDeferred.push(async function(OneSignal) {
			try {
				// Prefer direct permission + opt-in when available (more reliable than slidedown alone)
				if (OneSignal && OneSignal.Notifications && typeof OneSignal.Notifications.requestPermission === 'function') {
					await OneSignal.Notifications.requestPermission();
				} else {
					await OneSignal.Slidedown.promptPush();
				}

				// Ensure we actually opt-in if permission is granted.
				try {
					var perm = await getNativePermission(OneSignal);
					if (perm === 'granted' && OneSignal && OneSignal.User && OneSignal.User.PushSubscription && typeof OneSignal.User.PushSubscription.optIn === 'function') {
						await OneSignal.User.PushSubscription.optIn();
					}
				} catch (e) {}

				// Refresh UI using subscription state (and retry a couple times for async registration)
				updateSubscriptionUI();
				setTimeout(updateSubscriptionUI, 600);
				setTimeout(updateSubscriptionUI, 1600);
			} catch (error) {
				console.error('Error requesting permission:', error);
				if (container) {
					hideAllStates(container);
					showState(container, 'unsupported');
				}
			}
		});
	}

	// Initialize on DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function() {
			displayContextWarnings();
			initOneSignal();
		});
	} else {
		displayContextWarnings();
		initOneSignal();
	}

})();
