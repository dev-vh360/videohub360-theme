(function(window){
  'use strict';
  function request(method, url, data, progress){
    return new Promise(function(resolve, reject){
      var xhr = new XMLHttpRequest();
      xhr.open(method, url, true);
      xhr.setRequestHeader('X-WP-Nonce', (window.vh360StudioVideoUpload||{}).nonce || '');
      xhr.onload = function(){ try { var json = JSON.parse(xhr.responseText || '{}'); if (xhr.status >= 200 && xhr.status < 300) resolve(json); else reject(json); } catch(e){ reject(e); } };
      xhr.onerror = reject;
      if (progress && xhr.upload) xhr.upload.onprogress = progress;
      if (data && !(data instanceof FormData)) { xhr.setRequestHeader('Content-Type','application/json'); data = JSON.stringify(data); }
      xhr.send(data || null);
    });
  }
  function StudioVideoUpload(config){ this.config = config || window.vh360StudioVideoUpload || {}; }
  StudioVideoUpload.prototype.upload = function(file, options){
    var self = this, context = (options && options.context) || 'video', root = (this.config.restRoot || '').replace(/\/$/, '');
    return request('POST', root + '/video-assets', {context:context, filename:file.name, mime_type:file.type, file_size:file.size}).then(function(asset){
      var upload = asset.upload || {method:'server'};
      if (upload.method && upload.method !== 'server' && upload.url) {
        return request(upload.method.toUpperCase(), upload.url, file, options && options.onProgress).then(function(response){
          return request('POST', root + '/video-assets/' + asset.asset_uuid + '/complete', response || {});
        });
      }
      var fd = new FormData(); fd.append('file', file);
      return request('POST', root + '/video-assets/' + asset.asset_uuid + '/upload', fd, options && options.onProgress);
    }).then(function(asset){
      function poll(){
        if (asset.status === 'ready' || asset.status === 'failed' || asset.status === 'cancelled' || asset.status === 'deleted') return Promise.resolve(asset);
        return new Promise(function(resolve){ setTimeout(resolve, 3000); }).then(function(){ return request('GET', root + '/video-assets/' + asset.asset_uuid).then(function(next){ asset = next; return poll(); }); });
      }
      return poll();
    });
  };
  StudioVideoUpload.prototype.cancel = function(uuid){ return request('DELETE', this.config.restRoot.replace(/\/$/, '') + '/video-assets/' + uuid); };
  StudioVideoUpload.prototype.retry = function(uuid){ return request('POST', this.config.restRoot.replace(/\/$/, '') + '/video-assets/' + uuid + '/retry'); };
  window.VH360StudioVideoUpload = StudioVideoUpload;
})(window);
