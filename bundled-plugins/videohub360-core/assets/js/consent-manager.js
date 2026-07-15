(function(){
  'use strict';
  var cfg = window.VH360ConsentConfig || {};
  var state = resolveState();
  var memory = {};
  var keys = {};
  var rootSelector = '[data-vh360-consent-root]';
  var lastFocus = null;
  var bannerWasVisible = false;
  var inerted = [];

  function readConsentCookie(){
    var name = (cfg.cookieName || 'vh360_consent') + '=';
    var parts = document.cookie ? document.cookie.split(';') : [];
    for(var i = 0; i < parts.length; i++){
      var part = parts[i].trim();
      if(part.indexOf(name) === 0){
        try { return JSON.parse(decodeURIComponent(part.slice(name.length))); } catch(e) { try { return JSON.parse(part.slice(name.length)); } catch(e2) { return {}; } }
      }
    }
    return {};
  }
  function resolveState(){
    var settings = cfg.settings || {};
    var mode = settings.mode || 'disabled';
    var cookie = readConsentCookie();
    var validPolicy = cookie && cookie.policy_version === String(settings.policy_version || '1');
    var choices = { necessary:true, preferences:true, analytics:true, advertising:true };
    if(mode === 'strict') choices = { necessary:true, preferences:false, analytics:false, advertising:false };
    if(validPolicy && cookie.choices){ ['preferences','analytics','advertising'].forEach(function(cat){ choices[cat] = !!cookie.choices[cat]; }); }
    if(mode === 'disabled' || mode === 'notice') choices = { necessary:true, preferences:true, analytics:true, advertising:true };
    var gpc = navigator.globalPrivacyControl === true;
    if(gpc) choices.advertising = false;
    return { mode:mode, enabled:mode !== 'disabled', policy_version:String(settings.policy_version || '1'), choices:choices, gpc:gpc, needs_choice:(mode !== 'disabled' && (!validPolicy || (mode === 'notice' && !cookie.notice_acknowledged))) };
  }
  function syncWpConsentApi(){
    var map = { necessary:'functional', preferences:'preferences', analytics:'statistics-anonymous', advertising:'marketing' };
    var detail = {};
    var hasWpSetter = typeof window.wp_set_consent === 'function';
    Object.keys(map).forEach(function(cat){
      var value = cat === 'necessary' || !!(state.choices && state.choices[cat]);
      detail[map[cat]] = value ? 'allow' : 'deny';
      if(hasWpSetter){
        try { window.wp_set_consent(map[cat], detail[map[cat]]); } catch(e) {}
      }
    });
    if(!hasWpSetter){
      document.dispatchEvent(new CustomEvent('wp_listen_for_consent_change', { detail: detail }));
    }
  }

  var knownKeys = [
    'vh360_members_view', 'vh360_recent_emojis', 'vh360_quality_prefs', 'vh360-layout-view-preference', 'vh360StudioMode',
    'vh360_community_menu_expanded', 'vh360_pwa_install_banner_dismissed_until',
    'vh360_studio_camera_device_id', 'vh360_studio_camera_sources', 'vh360_studio_microphone_device_id', 'vh360_studio_audio_inputs',
    'vh360_studio_lower_dock_layout_v1', 'vh360_studio_overlays_width', 'vh360_studio_overlays_collapsed',
    'vh360_studio_overlays_active_module', 'vh360_studio_overlays_active_section'
  ];

  function has(cat){
    if(cat === 'necessary') return true;
    if((navigator.globalPrivacyControl === true || state.gpc) && cat === 'advertising') return false;
    return !!(state.choices && state.choices[cat]);
  }
  function isNotice(){ return state.mode === 'notice' || (cfg.settings && cfg.settings.mode === 'notice'); }
  function roots(){ return Array.prototype.slice.call(document.querySelectorAll(rootSelector)); }
  function banner(root){ return root ? root.querySelector('.vh360-consent-banner') : null; }
  function modal(){ return document.querySelector('.vh360-consent-modal'); }
  function focusables(container){ return Array.prototype.slice.call(container.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])')).filter(function(el){ return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length); }); }
  function setRootVisible(showBanner){ roots().forEach(function(r){ r.hidden = false; var b = banner(r); if(b) b.hidden = !showBanner; }); }
  function hideRoots(){ roots().forEach(function(r){ var b = banner(r); if(b) b.hidden = true; r.hidden = true; }); }
  function dismissBanner(){ roots().forEach(function(r){ var b = banner(r); if(b) b.hidden = true; }); }

  function showError(message){ document.querySelectorAll('.vh360-consent-error').forEach(function(el){ el.textContent = message || 'Your privacy choice could not be saved. Please try again.'; el.hidden = false; }); }
  function clearError(){ document.querySelectorAll('.vh360-consent-error').forEach(function(el){ el.textContent = ''; el.hidden = true; }); }
  function save(choices, notice){
    var fd = new FormData();
    fd.append('action','vh360_save_consent'); fd.append('notice_acknowledged', notice ? '1' : '0');
    ['preferences','analytics','advertising'].forEach(function(c){ fd.append('choices[' + c + ']', choices[c] ? '1' : '0'); });
    clearError();
    return fetch(cfg.ajaxUrl,{ method:'POST', credentials:'same-origin', body:fd }).then(function(r){ return r.json(); }).then(function(res){
      if(res && res.success){ state = resolveState(); cleanup(); fire(); syncWpConsentApi(); return state; }
      throw new Error((res && res.data && res.data.message) || 'save_failed');
    }).catch(function(error){ showError(error && error.message ? error.message : 'save_failed'); throw error; });
  }
  function refreshServerState(){ var fd = new FormData(); fd.append('action','vh360_consent_state'); return fetch(cfg.ajaxUrl,{ method:'POST', credentials:'same-origin', body:fd }).then(function(r){ return r.json(); }).then(function(res){ if(res && res.success && res.data && res.data.gpc){ state.gpc = true; state.choices.advertising = false; } return state; }).catch(function(){ return state; }); }
  function fire(){ loadActivityAds(); document.dispatchEvent(new CustomEvent('vh360:consent-changed',{ detail: state })); activateScripts(); }
  function cleanup(){ if(has('preferences')) return; Object.keys(keys).forEach(function(k){ try{ localStorage.removeItem(k); }catch(e){} }); }

  function inertBackground(on){
    if(on){
      inerted = [];
      Array.prototype.slice.call(document.body.children).forEach(function(el){
        if(el.matches(rootSelector) || el.classList.contains('vh360-consent-floating')) return;
        inerted.push({ el: el, inert: el.inert, aria: el.getAttribute('aria-hidden') });
        try { el.inert = true; } catch(e) {}
        el.setAttribute('aria-hidden','true');
      });
      return;
    }
    inerted.forEach(function(item){ try { item.el.inert = item.inert; } catch(e) {} if(item.aria === null){ item.el.removeAttribute('aria-hidden'); } else { item.el.setAttribute('aria-hidden', item.aria); } });
    inerted = [];
  }
  function openPrefs(){
    var m = modal(); if(!m) return;
    bannerWasVisible = roots().some(function(r){ var b = banner(r); return b && !r.hidden && !b.hidden; });
    setRootVisible(false);
    updateUi();
    lastFocus = document.activeElement;
    m.hidden = false;
    inertBackground(true);
    document.documentElement.classList.add('vh360-consent-modal-open');
    var list = focusables(m); if(list[0]) list[0].focus();
  }
  function closePrefs(dismiss){
    var m = modal(); if(m) m.hidden = true;
    inertBackground(false);
    document.documentElement.classList.remove('vh360-consent-modal-open');
    if(dismiss) { hideRoots(); }
    else if(bannerWasVisible) { setRootVisible(true); }
    if(lastFocus && typeof lastFocus.focus === 'function' && (!lastFocus.closest || !lastFocus.closest('[hidden]'))) lastFocus.focus();
  }
  function updateUi(){
    document.querySelectorAll('[data-vh360-consent-category]').forEach(function(i){
      var cat = i.getAttribute('data-vh360-consent-category');
      i.checked = has(cat);
      i.disabled = cat === 'advertising' && (navigator.globalPrivacyControl === true || state.gpc);
    });
    document.querySelectorAll('.vh360-consent-gpc').forEach(function(e){ e.hidden = !(navigator.globalPrivacyControl === true || state.gpc); });
    document.querySelectorAll('[data-vh360-consent-notice-only]').forEach(function(e){ e.hidden = !isNotice(); });
    document.querySelectorAll('[data-vh360-consent-full-controls]').forEach(function(e){ e.hidden = isNotice(); });
  }

  function activateScriptElement(source){
    return new Promise(function(resolve){
      var n = document.createElement('script');
      Array.prototype.slice.call(source.attributes).forEach(function(a){ if(a.name.indexOf('data-vh360') !== 0 && a.name !== 'type') n.setAttribute(a.name,a.value); });
      n.async = false;
      n.onload = n.onerror = resolve;
      if(source.dataset.vh360Src) n.src = source.dataset.vh360Src;
      n.text = source.text || source.textContent;
      source.dataset.vh360Activated = '1';
      source.parentNode.insertBefore(n, source.nextSibling);
      if(!n.src) resolve();
    });
  }
  function runSequential(nodes){
    return nodes.reduce(function(p, node){ return p.then(function(){ return activateScriptElement(node); }); }, Promise.resolve());
  }
  function activateScripts(){
    var pending = Array.prototype.slice.call(document.querySelectorAll('script[type="text/plain"][data-vh360-consent-category],script[type="text/plain"][data-vh360-consent-service]')).filter(function(s){
      if(s.dataset.vh360Activated) return false;
      return s.dataset.vh360ConsentService ? api.hasService(s.dataset.vh360ConsentService) : has(s.dataset.vh360ConsentCategory);
    });
    runSequential(pending);
  }
  function executeInsertedScripts(slot){
    var scripts = Array.prototype.slice.call(slot.querySelectorAll('script'));
    scripts.forEach(function(old){
      var placeholder = document.createElement('script');
      placeholder.type = 'text/plain';
      if(old.src) placeholder.dataset.vh360Src = old.src;
      Array.prototype.slice.call(old.attributes).forEach(function(a){ if(a.name !== 'src' && a.name !== 'type') placeholder.setAttribute(a.name,a.value); });
      placeholder.text = old.text || old.textContent;
      old.parentNode.replaceChild(placeholder, old);
    });
    runSequential(Array.prototype.slice.call(slot.querySelectorAll('script[type="text/plain"]')));
  }
  function loadActivityAds(){
    if(!api.hasService('activity-feed-ad-slot')) return;
    document.querySelectorAll('[data-vh360-activity-ad-blocked]').forEach(function(slot){
      if(slot.dataset.vh360Loaded) return;
      var fd = new FormData(); fd.append('action','vh360_activity_ad_markup');
      fetch(cfg.ajaxUrl,{ method:'POST', credentials:'same-origin', body:fd }).then(function(r){ return r.json(); }).then(function(res){
        if(!res || !res.success || !res.data || !res.data.html) return;
        slot.dataset.vh360Loaded = '1';
        var wrap = document.createElement('div'); wrap.innerHTML = res.data.html;
        slot.replaceChildren.apply(slot, Array.prototype.slice.call(wrap.childNodes));
        executeInsertedScripts(slot);
      });
    });
  }

  var api = window.VH360Consent = { has:has, hasService:function(slug){ var svc = (cfg.services || {})[slug]; return svc ? has(svc.category) : true; }, getState:function(){ return state; }, openPreferences:openPrefs, savePreferences:function(c){ return save(c,false); } };
  window.VH360Storage = { registerPreferenceKey:function(k){ if(k) keys[k] = true; }, getPreference:function(k,d){ keys[k] = true; if(has('preferences')){ try{ var v = localStorage.getItem(k); return v === null ? d : v; }catch(e){} } return Object.prototype.hasOwnProperty.call(memory,k) ? memory[k] : d; }, setPreference:function(k,v){ keys[k] = true; memory[k] = v; if(has('preferences')) try{ localStorage.setItem(k,v); }catch(e){} }, removePreference:function(k){ keys[k] = true; delete memory[k]; try{ localStorage.removeItem(k); }catch(e){} } };

  document.addEventListener('click',function(e){
    var t = e.target.closest && e.target.closest('[data-vh360-consent-action],.vh360-consent-open,.vh360-consent-close,.vh360-consent-modal-close'); if(!t) return;
    if(t.classList.contains('vh360-consent-open') || t.dataset.vh360ConsentAction === 'manage'){ e.preventDefault(); openPrefs(); }
    if(t.classList.contains('vh360-consent-close')){ e.preventDefault(); hideRoots(); }
    if(t.classList.contains('vh360-consent-modal-close')){ e.preventDefault(); closePrefs(false); }
    if(t.dataset.vh360ConsentAction === 'acknowledge'){ e.preventDefault(); save({ preferences:true, analytics:true, advertising:!(navigator.globalPrivacyControl === true) }, true).then(function(){ closePrefs(true); hideRoots(); }).catch(function(){}); }
    if(t.dataset.vh360ConsentAction === 'accept-all'){ e.preventDefault(); save({ preferences:true, analytics:true, advertising:!(navigator.globalPrivacyControl === true) }, true).then(function(){ closePrefs(true); hideRoots(); }).catch(function(){}); }
    if(t.dataset.vh360ConsentAction === 'reject-optional'){ e.preventDefault(); save({ preferences:false, analytics:false, advertising:false }, true).then(function(){ closePrefs(true); hideRoots(); }).catch(function(){}); }
    if(t.dataset.vh360ConsentAction === 'save'){ e.preventDefault(); var c = {}; document.querySelectorAll('[data-vh360-consent-category]').forEach(function(i){ c[i.getAttribute('data-vh360-consent-category')] = i.checked; }); save(c,true).then(function(){ closePrefs(true); }).catch(function(){}); }
  });
  document.addEventListener('keydown',function(e){
    var m = modal(); if(!m || m.hidden) return;
    if(e.key === 'Escape'){ e.preventDefault(); closePrefs(false); return; }
    if(e.key !== 'Tab') return;
    var list = focusables(m); if(!list.length) return;
    var first = list[0], last = list[list.length - 1];
    if(e.shiftKey && document.activeElement === first){ e.preventDefault(); last.focus(); }
    else if(!e.shiftKey && document.activeElement === last){ e.preventDefault(); first.focus(); }
  });
  document.addEventListener('DOMContentLoaded',function(){ state = resolveState(); knownKeys.forEach(window.VH360Storage.registerPreferenceKey); refreshServerState().then(function(){ syncWpConsentApi(); if(state.needs_choice) setRootVisible(true); updateUi(); cleanup(); loadActivityAds(); activateScripts(); }); });
})();
