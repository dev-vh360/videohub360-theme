/* global VH360Push, OneSignalDeferred */
/**
 * VH360 Push Notifications - Public JavaScript
 */
(function() {
	'use strict';

var VH360StorageCompat = window.VH360Storage || (function(){
  var memory = {};
  function persistentAllowed(){ return !window.VH360ConsentExpected; }
  return {
    getPreference: function(key, def){ if(!persistentAllowed()) { return Object.prototype.hasOwnProperty.call(memory, key) ? memory[key] : def; } try { var value = window['localStorage'].getItem(key); return value === null ? def : value; } catch (e) { return def; } },
    setPreference: function(key, value){ memory[key] = value; if(!persistentAllowed()) { return; } try { window['localStorage'].setItem(key, value); } catch (e) {} },
    removePreference: function(key){ delete memory[key]; if(!persistentAllowed()) { return; } try { window['localStorage'].removeItem(key); } catch (e) {} },
    registerPreferenceKey: function(){}
  };
})();

	// Debug logging helper - only log when __VH360_DEBUG is enabled
	const vh360Log = (...args) => { if (window.__VH360_DEBUG) console.log(...args); };
	var vh360OneSignalInitStarted = false;
	var vh360OneSignalInitialized = false;
	var vh360OneSignalConsentActive = false;
	var vh360OneSignalSdkLoading = null;
	var vh360OneSignalSdkLoaded = typeof window.OneSignal !== 'undefined';
	var vh360OneSignalDeferredOwned = false;

	if (typeof VH360Push === 'undefined') {
		return;
	}


	function hasPreferenceConsent() {
		return !window.VH360Consent || window.VH360Consent.has('preferences');
	}

	function openConsentPreferences() {
		if (window.VH360Consent && typeof window.VH360Consent.openPreferences === 'function') {
			window.VH360Consent.openPreferences();
		}
	}

	function resetOneSignalSdkLoader() {
		vh360OneSignalSdkLoading = null;
		vh360OneSignalSdkLoaded = typeof window.OneSignal !== 'undefined';
		if (!vh360OneSignalSdkLoaded && vh360OneSignalDeferredOwned && Array.isArray(window.OneSignalDeferred)) {
			try {
				delete window.OneSignalDeferred;
			} catch (e) {
				window.OneSignalDeferred = undefined;
			}
			vh360OneSignalDeferredOwned = false;
		}
	}

	function loadOneSignalSdk() {
		if (vh360OneSignalSdkLoaded && typeof window.OneSignal !== 'undefined') {
			return Promise.resolve();
		}
		if (vh360OneSignalSdkLoading) {
			return vh360OneSignalSdkLoading;
		}
		if (!VH360Push.sdkUrl) {
			return Promise.reject(new Error('Push SDK URL is unavailable.'));
		}
		window.OneSignalDeferred = [];
		vh360OneSignalDeferredOwned = true;
		vh360OneSignalSdkLoading = new Promise(function(resolve, reject) {
			var script = document.createElement('script');
			script.src = VH360Push.sdkUrl;
			script.async = true;
			script.setAttribute('data-vh360-onesignal-sdk', '1');
			script.onload = function() {
				vh360OneSignalSdkLoaded = true;
				vh360OneSignalSdkLoading = null;
				resolve();
			};
			script.onerror = function(error) {
				if (script.parentNode) {
					script.parentNode.removeChild(script);
				}
				vh360OneSignalInitStarted = false;
				resetOneSignalSdkLoader();
				reject(error);
			};
			document.head.appendChild(script);
		});
		return vh360OneSignalSdkLoading;
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
					VH360StorageCompat.setPreference(testKey, '1');
					VH360StorageCompat.removePreference(testKey);
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

	function activateOneSignalConsent(OneSignal) {
		return (async function() {
			if (!hasPreferenceConsent()) {
				return;
			}
			if (OneSignal && typeof OneSignal.setConsentRequired === 'function') { OneSignal.setConsentRequired(true); }
			if (OneSignal && typeof OneSignal.setConsentGiven === 'function') { OneSignal.setConsentGiven(true); }
			vh360OneSignalConsentActive = true;
			if (VH360Push.currentUserId && VH360Push.currentUserId > 0 && OneSignal && typeof OneSignal.login === 'function') {
				try {
					await OneSignal.login(String(VH360Push.currentUserId));
					vh360Log('[VH360 Push] OneSignal external user ID set:', VH360Push.currentUserId);
				} catch (err) {
					console.error('[VH360 Push] Failed to set OneSignal external user ID:', err);
				}
			}
			updateSubscriptionUI();
		})();
	}

	function registerOneSignalListeners(OneSignal) {
		if (OneSignal && OneSignal.Notifications && typeof OneSignal.Notifications.addEventListener === 'function') {
			try {
				OneSignal.Notifications.addEventListener('permissionChange', updateSubscriptionUI);
			} catch (e) {}
		}
		if (OneSignal && OneSignal.User && OneSignal.User.PushSubscription && typeof OneSignal.User.PushSubscription.addEventListener === 'function') {
			try {
				OneSignal.User.PushSubscription.addEventListener('change', updateSubscriptionUI);
			} catch (e2) {}
		}
	}

	// Initialize OneSignal once per page, then toggle its consent state.
	function initOneSignal() {
		if (!hasPreferenceConsent()) {
			vh360Log('[VH360 Push] Waiting for preferences consent before initializing push provider.');
			return;
		}
		if (vh360OneSignalInitialized && typeof OneSignalDeferred !== 'undefined') {
			OneSignalDeferred.push(function(OneSignal) { activateOneSignalConsent(OneSignal); });
			return;
		}
		if (vh360OneSignalInitStarted) {
			return;
		}
		vh360OneSignalInitStarted = true;
		loadOneSignalSdk().then(function() {
			if (!hasPreferenceConsent()) {
				vh360OneSignalInitStarted = false;
				return;
			}
			window.OneSignalDeferred = window.OneSignalDeferred || [];
			OneSignalDeferred.push(async function(OneSignal) {
				try {
					if (!hasPreferenceConsent()) {
						vh360OneSignalInitStarted = false;
						return;
					}
					if (OneSignal && typeof OneSignal.setConsentRequired === 'function') { OneSignal.setConsentRequired(true); }
					if (OneSignal && typeof OneSignal.setConsentGiven === 'function') { OneSignal.setConsentGiven(true); }
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

					if (!hasPreferenceConsent()) {
						if (OneSignal && typeof OneSignal.setConsentGiven === 'function') { OneSignal.setConsentGiven(false); }
						vh360OneSignalInitStarted = false;
						return;
					}

					vh360OneSignalInitialized = true;
					vh360OneSignalInitStarted = false;
					registerOneSignalListeners(OneSignal);
					await activateOneSignalConsent(OneSignal);
					// Automatic browser notification permission prompts are intentionally disabled.
				} catch (error) {
					vh360OneSignalInitStarted = false;
					vh360OneSignalInitialized = false;
					vh360OneSignalConsentActive = false;
					console.error('OneSignal initialization error:', error);
				}
			});
		}).catch(function(error) {
			vh360OneSignalInitStarted = false;
			vh360Log('Push setup is waiting for preferences consent or provider SDK availability.', error);
		});
	}

	function deactivateOneSignal() {
		vh360OneSignalInitStarted = false;
		if (typeof OneSignalDeferred === 'undefined') {
			updateSubscriptionUI();
			return;
		}
		try {
			OneSignalDeferred.push(async function(OneSignal) {
				try {
					if (OneSignal && typeof OneSignal.logout === 'function') {
						await OneSignal.logout();
					}
				} catch (e) {}
				try {
					if (OneSignal && OneSignal.User && OneSignal.User.PushSubscription && typeof OneSignal.User.PushSubscription.optOut === 'function') {
						await OneSignal.User.PushSubscription.optOut();
					}
				} catch (e2) {}
				if (OneSignal && typeof OneSignal.setConsentGiven === 'function') { OneSignal.setConsentGiven(false); }
				vh360OneSignalConsentActive = false;
				updateSubscriptionUI();
			});
		} catch (e3) {
			vh360OneSignalConsentActive = false;
			updateSubscriptionUI();
		}
	}

	function handlePushConsentChange() {
		if (hasPreferenceConsent()) {
			initOneSignal();
		} else {
			deactivateOneSignal();
		}
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

		if (!hasPreferenceConsent()) {
			containers.forEach(function(container) { hideAllStates(container); showState(container, 'unsubscribed'); });
			return;
		}

		if (typeof OneSignalDeferred === 'undefined' || typeof window.OneSignal === 'undefined') {
			initOneSignal();
			// Show unsupported until the provider SDK finishes loading
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
		if (!hasPreferenceConsent()) {
			openConsentPreferences();
			return;
		}
		if (typeof OneSignalDeferred === 'undefined') {
			initOneSignal();
		}

		var container = button.closest('[data-vh360-push-subscribe]');
		if (container) {
			hideAllStates(container);
			showState(container, 'loading');
		}

		loadOneSignalSdk().then(function() {
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
		}).catch(function(error) {
			console.error('Error loading push provider SDK:', error);
			if (container) { hideAllStates(container); showState(container, 'unsupported'); }
		});
	}

	// Initialize on DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function() {
			displayContextWarnings();
			handlePushConsentChange();
			document.addEventListener('vh360:consent-changed', handlePushConsentChange);
		});
	} else {
		displayContextWarnings();
		handlePushConsentChange();
		document.addEventListener('vh360:consent-changed', handlePushConsentChange);
	}

})();
