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

	if (typeof VH360Push === 'undefined') {
		return;
	}
	if (window.__VH360_PUSH_PUBLIC_RUNTIME_ACTIVE__) {
		return;
	}
	window.__VH360_PUSH_PUBLIC_RUNTIME_ACTIVE__ = true;

	var vh360OneSignalInitialized = false;
	var vh360OneSignalSdkLoading = null;
	var vh360OneSignalInitPromise = null;
	var vh360OneSignalTransitionId = 0;
	var vh360OneSignalListenersRegistered = false;
	var vh360OneSignalLifecyclePromise = Promise.resolve();
	var vh360OneSignalLastReconciledConsent = null;
	var vh360OneSignalLastLoggedInUserId = null;
	var vh360OneSignalLastLoggedInIdentity = '';
	var vh360OneSignalReconcilePromise = null;

	function hasPreferenceConsent() {
		if (window.VH360ConsentExpected && !window.VH360Consent) {
			return false;
		}
		if (!window.VH360Consent) {
			return true;
		}
		return window.VH360Consent.has('preferences');
	}

	function openConsentPreferences() {
		if (window.VH360Consent && typeof window.VH360Consent.openPreferences === 'function') {
			window.VH360Consent.openPreferences();
		}
	}

	function resetOneSignalSdkLoader() {
		vh360OneSignalSdkLoading = null;
	}

	function loadOneSignalSdk() {
		if (vh360OneSignalSdkLoading) {
			return vh360OneSignalSdkLoading;
		}
		if (!VH360Push.sdkUrl) {
			return Promise.reject(new Error('Push SDK URL is unavailable.'));
		}
		window.OneSignalDeferred = window.OneSignalDeferred || [];
		vh360OneSignalSdkLoading = new Promise(function(resolve, reject) {
			var scripts = Array.prototype.slice.call(document.getElementsByTagName('script'));
			var configuredUrl = new URL(VH360Push.sdkUrl, document.baseURI).href;
			var script = document.querySelector('script[data-vh360-onesignal-sdk]') || scripts.find(function(candidate) {
				try { return new URL(candidate.src, document.baseURI).href === configuredUrl; } catch (e) { return false; }
			});
			var isNewScript = !script;
			if (!script) {
				script = document.createElement('script');
				script.src = VH360Push.sdkUrl;
				script.async = true;
				script.setAttribute('data-vh360-onesignal-sdk', '1');
			}
			var handleLoad = function() {
				resolve();
			};
			var handleError = function(error) {
				if (isNewScript && script.parentNode) {
					script.parentNode.removeChild(script);
				}
				resetOneSignalSdkLoader();
				reject(error);
			};
			script.addEventListener('load', handleLoad, { once: true });
			script.addEventListener('error', handleError, { once: true });
			if (isNewScript) {
				document.head.appendChild(script);
			} else {
				// The shared deferred queue is the readiness mechanism. An existing
				// script may already have fired its load event before this runtime.
				resolve();
			}
		});
		return vh360OneSignalSdkLoading;
	}

	/**
	 * Detect unsupported contexts (incognito, iOS, etc.)
	 */
	function isIOSDevice() {
		var platform = navigator.platform || '';
		return /iPad|iPhone|iPod/.test(navigator.userAgent) ||
			(platform === 'MacIntel' && navigator.maxTouchPoints > 1);
	}

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
		var isIOS = isIOSDevice();
		if (isIOS) {
			var standalone = window.matchMedia && window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
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
				} else if (!standalone) {
					warnings.push({
						type: 'ios_home',
						message: 'Open the installed app from your Home Screen to enable notifications.'
					});
				}
			} else if (!standalone) {
				// Desktop-style iPadOS user agents do not expose a usable iOS
				// version, but modern iPads still require Home Screen installation.
				warnings.push({
					type: 'ios_home',
					message: 'Open the installed app from your Home Screen to enable notifications.'
				});
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

	function requiresIOSHomeScreen() {
		if (!isIOSDevice()) return false;
		var match = navigator.userAgent.match(/OS (\d+)_(\d+)/);
		if (match) {
			var major = parseInt(match[1], 10);
			var minor = parseInt(match[2], 10);
			if (major < 16 || (major === 16 && minor < 4)) return false;
		}
		var standalone = window.matchMedia && window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
		return !standalone;
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

	function withOneSignal(callback) {
		return new Promise(function(resolve, reject) {
			if (typeof window.OneSignalDeferred === 'undefined') {
				reject(new Error('Push provider SDK is not loaded.'));
				return;
			}
			window.OneSignalDeferred.push(async function(OneSignal) {
				try {
					resolve(await callback(OneSignal));
				} catch (error) {
					reject(error);
				}
			});
		});
	}

	async function applyOneSignalConsentState(OneSignal, consentActive) {
		if (consentActive) {
			if (OneSignal && typeof OneSignal.setConsentGiven === 'function' && vh360OneSignalLastReconciledConsent !== true) {
				await Promise.resolve(OneSignal.setConsentGiven(true));
			}
			await loginCurrentOneSignalUser(OneSignal, false);
			vh360OneSignalLastReconciledConsent = true;
			return;
		}

		if (OneSignal && typeof OneSignal.setConsentGiven === 'function') {
			await Promise.resolve(OneSignal.setConsentGiven(false));
		}
		vh360OneSignalLastReconciledConsent = false;
	}

	async function readSdkValue(owner, name) {
		if (!owner) return undefined;
		var value = owner[name];
		if (typeof value === 'function') value = value.call(owner);
		return Promise.resolve(value);
	}

	function normalizePermission(nativePermission, providerPermission) {
		if (nativePermission === 'granted' || nativePermission === 'denied' || nativePermission === 'default') return nativePermission;
		if (providerPermission === 'granted' || providerPermission === true) return 'granted';
		if (providerPermission === 'denied') return 'denied';
		return 'default';
	}

	async function getCurrentPushState(OneSignal) {
		var notifications = OneSignal && OneSignal.Notifications;
		var subscription = OneSignal && OneSignal.User && OneSignal.User.PushSubscription;
		var supported = false;
		var providerPermission;
		var optedIn = false;
		var id = '';
		var token = '';
		try { supported = !!(await readSdkValue(notifications, 'isPushSupported')); } catch (e) {}
		try { providerPermission = await readSdkValue(notifications, 'permission'); } catch (e2) {}
		try { optedIn = !!(await readSdkValue(subscription, 'optedIn')); } catch (e3) {}
		try { id = (await readSdkValue(subscription, 'id')) || ''; } catch (e4) {}
		try { token = (await readSdkValue(subscription, 'token')) || ''; } catch (e5) {}
		var nativePermission = window.Notification && window.Notification.permission;
		var permission = normalizePermission(nativePermission, providerPermission);
		return {
			supported: supported,
			permission: permission,
			permissionGranted: permission === 'granted',
			permissionDenied: permission === 'denied',
			optedIn: optedIn,
			id: String(id),
			token: String(token),
			subscribed: supported && permission === 'granted' && optedIn && String(token) !== ''
		};
	}

	async function loginCurrentOneSignalUser(OneSignal, force, state) {
		var userId = VH360Push.currentUserId && VH360Push.currentUserId > 0 ? String(VH360Push.currentUserId) : null;
		if (!userId || !hasPreferenceConsent() || !OneSignal || typeof OneSignal.login !== 'function') return;
		state = state || await getCurrentPushState(OneSignal);
		var identity = state.id + '|' + state.token;
		if (!force && vh360OneSignalLastLoggedInUserId === userId && vh360OneSignalLastLoggedInIdentity === identity) return;
		try {
			await OneSignal.login(userId);
			vh360OneSignalLastLoggedInUserId = userId;
			vh360OneSignalLastLoggedInIdentity = identity;
		} catch (error) { vh360Log('[VH360 Push] Failed to link OneSignal user:', error); }
	}

	async function waitForValidSubscription(OneSignal) {
		var state;
		for (var attempt = 0; attempt < 10; attempt++) {
			state = await getCurrentPushState(OneSignal);
			if (state.subscribed || state.permissionDenied) return state;
			await new Promise(function(resolve) { setTimeout(resolve, 400); });
		}
		return getCurrentPushState(OneSignal);
	}

	function reconcileCurrentDeviceSubscription(OneSignal) {
		if (vh360OneSignalReconcilePromise) return vh360OneSignalReconcilePromise;
		vh360OneSignalReconcilePromise = (async function() {
			var state = await getCurrentPushState(OneSignal);
			// A token with optedIn=false is an intentional provider opt-out. Only
			// silently repair an interrupted subscription which has lost its token.
			if (hasPreferenceConsent() && state.supported && state.permissionGranted && !state.token &&
				OneSignal.User && OneSignal.User.PushSubscription && typeof OneSignal.User.PushSubscription.optIn === 'function') {
				await OneSignal.User.PushSubscription.optIn();
				state = await waitForValidSubscription(OneSignal);
			}
			if (state.subscribed) await loginCurrentOneSignalUser(OneSignal, false, state);
			return state;
		})().catch(function(error) {
			vh360Log('[VH360 Push] Device reconciliation failed:', error);
			return getCurrentPushState(OneSignal);
		}).finally(function() { vh360OneSignalReconcilePromise = null; });
		return vh360OneSignalReconcilePromise;
	}

	function registerOneSignalListeners(OneSignal) {
		if (vh360OneSignalListenersRegistered) {
			return;
		}
		vh360OneSignalListenersRegistered = true;
		if (OneSignal && OneSignal.Notifications && typeof OneSignal.Notifications.addEventListener === 'function') {
			try {
				OneSignal.Notifications.addEventListener('permissionChange', function() {
					reconcileCurrentDeviceSubscription(OneSignal).then(updateSubscriptionUI);
				});
			} catch (e) {}
		}
		if (OneSignal && OneSignal.User && OneSignal.User.PushSubscription && typeof OneSignal.User.PushSubscription.addEventListener === 'function') {
			try {
				OneSignal.User.PushSubscription.addEventListener('change', function(event) {
					var previous = event && event.previous;
					var current = event && event.current;
					var changed = !!(previous && current && (previous.id !== current.id || previous.token !== current.token));
					reconcileCurrentDeviceSubscription(OneSignal).then(function(state) {
						return loginCurrentOneSignalUser(OneSignal, changed, state);
					}).then(updateSubscriptionUI);
				});
			} catch (e2) {}
		}
	}

	// Initialize OneSignal once per page. Consent changes are serialized separately.
	function initOneSignal() {
		if (vh360OneSignalInitPromise) {
			return vh360OneSignalInitPromise;
		}
		if (!hasPreferenceConsent()) {
			vh360Log('[VH360 Push] Waiting for preferences consent before initializing push provider.');
			return Promise.resolve(null);
		}
		vh360OneSignalInitPromise = loadOneSignalSdk().then(function() {
			return withOneSignal(async function(OneSignal) {
				if (OneSignal && typeof OneSignal.setConsentRequired === 'function') {
					await Promise.resolve(OneSignal.setConsentRequired(true));
				}
				await OneSignal.init({
					appId: VH360Push.appId,
					serviceWorkerParam: {
						scope: VH360Push.swScope || '/push/onesignal/'
					},
					serviceWorkerPath: VH360Push.swPath || 'push/onesignal/OneSignalSDKWorker.js',
					autoResubscribe: true,
					notificationClickHandlerMatch: 'origin',
					notificationClickHandlerAction: 'navigate'
				});
				if (OneSignal && typeof OneSignal.setConsentGiven === 'function') {
					await Promise.resolve(OneSignal.setConsentGiven(true));
				}
				vh360OneSignalInitialized = true;
				registerOneSignalListeners(OneSignal);
				return OneSignal;
			});
		}).catch(function(error) {
			vh360OneSignalInitialized = false;
			vh360OneSignalInitPromise = null;
			console.error('OneSignal initialization error:', error);
			throw error;
		});
		return vh360OneSignalInitPromise;
	}

	async function getOneSignalForCurrentConsent(consentActive) {
		if (consentActive) {
			return initOneSignal();
		}
		if (vh360OneSignalInitPromise) {
			return vh360OneSignalInitPromise;
		}
		return null;
	}

	async function reconcilePushConsent(requestedTransition) {
		var consentActive = hasPreferenceConsent();
		var OneSignal = null;
		try {
			OneSignal = await getOneSignalForCurrentConsent(consentActive);
		} catch (error) {
			vh360Log('Push setup is waiting for preferences consent or provider SDK availability.', error);
			updateSubscriptionUI().catch(function() {});
			return null;
		}
		consentActive = hasPreferenceConsent();
		if (!OneSignal) {
			updateSubscriptionUI().catch(function() {});
			return null;
		}
		await applyOneSignalConsentState(OneSignal, consentActive);
		if (requestedTransition !== vh360OneSignalTransitionId) {
			return reconcilePushConsent(vh360OneSignalTransitionId);
		}
		if (consentActive) await reconcileCurrentDeviceSubscription(OneSignal);
		updateSubscriptionUI().catch(function() {});
		return OneSignal;
	}

	function queuePushConsentReconciliation() {
		var requestedTransition = ++vh360OneSignalTransitionId;
		vh360OneSignalLifecyclePromise = vh360OneSignalLifecyclePromise
			.catch(function() {})
			.then(function() {
				return reconcilePushConsent(requestedTransition);
			});
		return vh360OneSignalLifecyclePromise;
	}

	function handlePushConsentChange() {
		queuePushConsentReconciliation().catch(function(error) {
			vh360Log('[VH360 Push] Consent reconciliation failed:', error);
		});
	}

	// Update subscription UI
	function updateSubscriptionUI() {
		var containers = document.querySelectorAll('[data-vh360-push-subscribe]');
		if (!containers.length) {
			return Promise.resolve();
		}

		if (requiresIOSHomeScreen()) {
			containers.forEach(function(container) { hideAllStates(container); showState(container, 'ios-home'); });
			return Promise.resolve();
		}

		if (!hasPreferenceConsent()) {
			containers.forEach(function(container) { hideAllStates(container); showState(container, 'unsubscribed'); });
			return Promise.resolve();
		}

		return initOneSignal().then(async function(OneSignal) {
			try {
				if (!OneSignal || !OneSignal.Notifications) throw new Error('Push provider is unavailable.');
				var state = await getCurrentPushState(OneSignal);
				if (!state.supported) {
					containers.forEach(function(container) {
						hideAllStates(container);
						showState(container, 'unsupported');
					});
					return;
				}

				containers.forEach(function(container) {
					hideAllStates(container);
					if (state.subscribed) {
						showState(container, 'subscribed');
					} else if (state.permissionGranted) {
						showState(container, 'reconnect');
					} else if (state.permissionDenied) {
						showState(container, 'blocked');
					} else {
						showState(container, 'unsubscribed');
					}
				});
			} catch (error) {
				vh360Log('[VH360 Push] Error checking subscription state:', error);
				containers.forEach(function(container) {
					hideAllStates(container);
					showState(container, 'unsupported');
				});
			}
		}).catch(function(error) {
			vh360Log('[VH360 Push] Subscription UI refresh failed:', error);
			containers.forEach(function(container) { hideAllStates(container); showState(container, 'unsupported'); });
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

	function handleSubscribe(button) {
		if (!hasPreferenceConsent()) {
			openConsentPreferences();
			return;
		}
		var container = button.closest('[data-vh360-push-subscribe]');
		if (container) {
			hideAllStates(container);
			showState(container, 'loading');
		}

		queuePushConsentReconciliation().then(async function() {
			try {
				await vh360OneSignalLifecyclePromise.catch(function() {});
				if (!hasPreferenceConsent()) {
					openConsentPreferences();
					return;
				}
				var OneSignal = await initOneSignal();
				if (!OneSignal || !hasPreferenceConsent()) {
					openConsentPreferences();
					return;
				}
				var state = await getCurrentPushState(OneSignal);
				if (!state.supported) throw new Error('Push is unsupported.');
				if (!state.permissionGranted && !state.permissionDenied) {
					if (OneSignal.Notifications && typeof OneSignal.Notifications.requestPermission === 'function') {
						await OneSignal.Notifications.requestPermission();
					} else if (OneSignal.Slidedown && typeof OneSignal.Slidedown.promptPush === 'function') {
						await OneSignal.Slidedown.promptPush();
					}
					state = await getCurrentPushState(OneSignal);
				}
				if (state.permissionDenied) {
					await updateSubscriptionUI();
					return;
				}
				if (state.permissionGranted && OneSignal.User && OneSignal.User.PushSubscription && typeof OneSignal.User.PushSubscription.optIn === 'function') {
					await OneSignal.User.PushSubscription.optIn();
					state = await waitForValidSubscription(OneSignal);
				}
				if (state.subscribed) await loginCurrentOneSignalUser(OneSignal, true, state);
				await updateSubscriptionUI();
			} catch (error) {
				vh360Log('[VH360 Push] Error requesting permission:', error);
				try {
					var recoverableState = typeof OneSignal !== 'undefined' ? await getCurrentPushState(OneSignal) : null;
					if (recoverableState && recoverableState.supported && recoverableState.permissionGranted) {
						await updateSubscriptionUI();
						return;
					}
				} catch (stateError) {}
				if (container) { hideAllStates(container); showState(container, 'unsupported'); }
			}
		}).catch(function(error) {
			vh360Log('[VH360 Push] Error loading push provider SDK:', error);
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
