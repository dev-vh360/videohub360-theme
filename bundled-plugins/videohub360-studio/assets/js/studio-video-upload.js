(function(window){
  'use strict';
  function requestError(payload, status){
    var message = payload && payload.message ? payload.message : 'Video upload request failed.';
    var error = new Error(message);
    error.code = payload && payload.code ? payload.code : 'vh360_video_upload_request_failed';
    error.status = status || 0;
    error.data = payload && payload.data ? payload.data : {};
    error.response = payload || {};
    return error;
  }
  function request(method, url, data, progress, headers){
    return new Promise(function(resolve, reject){
      var xhr = new XMLHttpRequest();
      xhr.open(method, url, true);
      headers = headers || {};
      Object.keys(headers).forEach(function(key){ xhr.setRequestHeader(key, headers[key]); });
      if (!headers['X-WP-Nonce'] && url.indexOf((window.vh360StudioVideoUpload||{}).restRoot || '') === 0) {
        xhr.setRequestHeader('X-WP-Nonce', (window.vh360StudioVideoUpload||{}).nonce || '');
      }
      xhr.onload = function(){
        var json = {};
        try {
          json = JSON.parse(xhr.responseText || '{}');
        } catch(e) {
          reject(requestError({message:'Video upload returned an invalid response.'}, xhr.status));
          return;
        }
        if (xhr.status >= 200 && xhr.status < 300) resolve(json);
        else reject(requestError(json, xhr.status));
      };
      xhr.onerror = function(){ reject(requestError({message:'A network error interrupted the video upload.'}, xhr.status)); };
      if (progress && xhr.upload) xhr.upload.onprogress = progress;
      if (data && !(data instanceof FormData) && !(data instanceof File) && !(data instanceof Blob)) { xhr.setRequestHeader('Content-Type','application/json'); data = JSON.stringify(data); }
      xhr.send(data || null);
    });
  }
  function directFormData(file, upload){
    var fd = new FormData();
    var fields = upload.fields || {};
    Object.keys(fields).forEach(function(key){ fd.append(key, fields[key]); });
    fd.append(upload.fileField || 'file', file);
    return fd;
  }
  function StudioVideoUpload(config){ this.config = config || window.vh360StudioVideoUpload || {}; }
  StudioVideoUpload.prototype.upload = function(file, options){
    options = options || {};
    var context = options.context || 'video', root = (this.config.restRoot || '').replace(/\/$/, '');
    var createdAssetUuid = '';
    return request('POST', root + '/video-assets', {context:context, filename:file.name, mime_type:file.type, file_size:file.size}).then(function(asset){
      createdAssetUuid = asset && asset.asset_uuid ? asset.asset_uuid : '';
      var upload = asset.upload || {method:'server'};
      if (upload.method && upload.method !== 'server' && upload.url) {
        return request(upload.httpMethod || upload.method.toUpperCase(), upload.url, directFormData(file, upload), options.onProgress, upload.headers || {}).then(function(response){
          var completePayload = response || {}; if (upload.direct_upload_token) completePayload.direct_upload_token = upload.direct_upload_token; return request('POST', root + '/video-assets/' + asset.asset_uuid + '/complete', completePayload);
        });
      }
      var fd = new FormData(); fd.append('file', file);
      return request('POST', root + '/video-assets/' + asset.asset_uuid + '/upload', fd, options.onProgress);
    }).then(function(asset){
      if (!options.waitForReady) return asset;
      var started = Date.now();
      var timeout = options.timeout || 120000;
      function poll(){
        if (asset.status === 'ready' || asset.status === 'failed' || asset.status === 'cancelled' || asset.status === 'deleted') return Promise.resolve(asset);
        if (Date.now() - started > timeout) return Promise.resolve(asset);
        return new Promise(function(resolve){ setTimeout(resolve, options.pollInterval || 3000); }).then(function(){ return request('GET', root + '/video-assets/' + asset.asset_uuid).then(function(next){ asset = next; return poll(); }); });
      }
      return poll();
    }).catch(function(error){
      if (!createdAssetUuid) throw error;
      return request('DELETE', root + '/video-assets/' + createdAssetUuid).catch(function(){ return null; }).then(function(){ throw error; });
    });
  };
  StudioVideoUpload.prototype.cancel = function(uuid){ return request('DELETE', this.config.restRoot.replace(/\/$/, '') + '/video-assets/' + uuid); };
  StudioVideoUpload.prototype.retry = function(uuid){ return request('POST', this.config.restRoot.replace(/\/$/, '') + '/video-assets/' + uuid + '/retry'); };
  window.VH360StudioVideoUpload = StudioVideoUpload;
})(window);
