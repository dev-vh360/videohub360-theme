// == Save to Playlist Logic ==
(function() {
    'use strict';

    // Guard against double initialization
    if (window.__vh360PlaylistsInitialized) {
        return;
    }
    window.__vh360PlaylistsInitialized = true;

    const saveBtn = document.getElementById('vh360-save-btn');
    const modalOverlay = document.getElementById('vh360-playlist-modal-overlay');
    const modalClose = document.getElementById('vh360-playlist-modal-close');
    const playlistList = document.getElementById('vh360-playlist-list');
    const createToggle = document.getElementById('vh360-create-playlist-toggle');
    const createForm = document.getElementById('vh360-create-playlist-form');
    const cancelCreate = document.getElementById('vh360-cancel-create-playlist');
    const submitCreate = document.getElementById('vh360-submit-create-playlist');
    const playlistTitle = document.getElementById('vh360-new-playlist-title');

    if (!saveBtn || !modalOverlay) {
        return;
    }

    let currentVideoId = null;
    let userPlaylists = [];
    let playlistsWithVideo = [];

    // Open modal
    saveBtn.addEventListener('click', function(e) {
        e.preventDefault();

        // Check if login is required
        if (saveBtn.getAttribute('data-login-required') === 'true') {
            if (typeof window.vh360ShowLoginModal === 'function') {
                window.vh360ShowLoginModal();
            } else if (typeof vh360Data !== 'undefined' && vh360Data.userLoginUrl) {
                window.location.href = vh360Data.userLoginUrl;
            }
            return;
        }

        currentVideoId = saveBtn.getAttribute('data-video-id');
        modalOverlay.style.display = 'flex';
        loadPlaylists();
    });

    // Close modal
    if (modalClose) {
        modalClose.addEventListener('click', function() {
            modalOverlay.style.display = 'none';
            createForm.style.display = 'none';
            playlistTitle.value = '';
        });
    }

    // Close on overlay click
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
            modalOverlay.style.display = 'none';
            createForm.style.display = 'none';
            playlistTitle.value = '';
        }
    });

    // Toggle create playlist form
    if (createToggle) {
        createToggle.addEventListener('click', function() {
            createForm.style.display = createForm.style.display === 'none' ? 'block' : 'none';
            if (createForm.style.display === 'block') {
                playlistTitle.focus();
            }
        });
    }

    // Cancel create playlist
    if (cancelCreate) {
        cancelCreate.addEventListener('click', function() {
            createForm.style.display = 'none';
            playlistTitle.value = '';
        });
    }

    // Submit create playlist
    if (submitCreate) {
        submitCreate.addEventListener('click', function() {
            createPlaylist();
        });
    }

    // Submit on Enter key
    if (playlistTitle) {
        playlistTitle.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                createPlaylist();
            }
        });
    }

    function loadPlaylists() {
        if (typeof vh360Data === 'undefined' || !vh360Data.ajaxUrl) {
            window.vh360Warn('VideoHub360: vh360Data not available');
            return;
        }

        playlistList.innerHTML = '<p class="vh360-playlist-loading">Loading playlists...</p>';

        const formData = new FormData();
        formData.append('action', 'vh360_get_my_playlists');
        formData.append('nonce', vh360Data.playlistNonce);

        fetch(vh360Data.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                userPlaylists = data.data.playlists || [];
                loadPlaylistsWithVideo();
            } else {
                playlistList.innerHTML = '<p class="vh360-playlist-error">Failed to load playlists.</p>';
            }
        })
        .catch(error => {
            window.vh360Warn('VideoHub360: Load playlists error:', error);
            playlistList.innerHTML = '<p class="vh360-playlist-error">Failed to load playlists.</p>';
        });
    }

    function loadPlaylistsWithVideo() {
        const formData = new FormData();
        formData.append('action', 'vh360_get_playlists_with_video');
        formData.append('video_id', currentVideoId);
        formData.append('nonce', vh360Data.playlistNonce);

        fetch(vh360Data.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                playlistsWithVideo = data.data.playlist_ids || [];
                renderPlaylists();
            } else {
                renderPlaylists();
            }
        })
        .catch(error => {
            window.vh360Warn('VideoHub360: Load playlists with video error:', error);
            renderPlaylists();
        });
    }

    function renderPlaylists() {
        if (userPlaylists.length === 0) {
            playlistList.innerHTML = '<p class="vh360-playlist-empty">You don\'t have any playlists yet. Create one below!</p>';
            return;
        }

        let html = '<div class="vh360-playlist-items">';
        userPlaylists.forEach(function(playlist) {
            const isChecked = playlistsWithVideo.includes(parseInt(playlist.id));
            html += '<label class="vh360-playlist-item">';
            html += '<input type="checkbox" class="vh360-playlist-checkbox" ';
            html += 'data-playlist-id="' + playlist.id + '" ';
            html += (isChecked ? 'checked' : '') + '>';
            html += '<span class="vh360-playlist-name">' + escapeHtml(playlist.title) + '</span>';
            html += '<span class="vh360-playlist-count">(' + playlist.video_count + ')</span>';
            html += '</label>';
        });
        html += '</div>';

        playlistList.innerHTML = html;

        // Add event listeners to checkboxes
        const checkboxes = playlistList.querySelectorAll('.vh360-playlist-checkbox');
        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const playlistId = checkbox.getAttribute('data-playlist-id');
                if (checkbox.checked) {
                    addToPlaylist(playlistId, checkbox);
                } else {
                    removeFromPlaylist(playlistId, checkbox);
                }
            });
        });
    }

    function createPlaylist() {
        const title = playlistTitle.value.trim();
        if (!title) {
            return;
        }

        if (typeof vh360Data === 'undefined' || !vh360Data.ajaxUrl) {
            window.vh360Warn('VideoHub360: vh360Data not available');
            return;
        }

        submitCreate.disabled = true;

        const formData = new FormData();
        formData.append('action', 'vh360_create_playlist');
        formData.append('title', title);
        formData.append('nonce', vh360Data.playlistNonce);

        fetch(vh360Data.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                playlistTitle.value = '';
                createForm.style.display = 'none';
                loadPlaylists();
            } else {
                window.vh360Warn('VideoHub360: Create playlist failed:', data.data);
            }
        })
        .catch(error => {
            window.vh360Warn('VideoHub360: Create playlist error:', error);
        })
        .finally(() => {
            submitCreate.disabled = false;
        });
    }

    function addToPlaylist(playlistId, checkbox) {
        if (typeof vh360Data === 'undefined' || !vh360Data.ajaxUrl) {
            window.vh360Warn('VideoHub360: vh360Data not available');
            return;
        }

        checkbox.disabled = true;

        const formData = new FormData();
        formData.append('action', 'vh360_add_to_playlist');
        formData.append('playlist_id', playlistId);
        formData.append('video_id', currentVideoId);
        formData.append('nonce', vh360Data.playlistNonce);

        fetch(vh360Data.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                playlistsWithVideo.push(parseInt(playlistId));
            } else {
                checkbox.checked = false;
                window.vh360Warn('VideoHub360: Add to playlist failed:', data.data);
            }
        })
        .catch(error => {
            checkbox.checked = false;
            window.vh360Warn('VideoHub360: Add to playlist error:', error);
        })
        .finally(() => {
            checkbox.disabled = false;
        });
    }

    function removeFromPlaylist(playlistId, checkbox) {
        if (typeof vh360Data === 'undefined' || !vh360Data.ajaxUrl) {
            window.vh360Warn('VideoHub360: vh360Data not available');
            return;
        }

        checkbox.disabled = true;

        const formData = new FormData();
        formData.append('action', 'vh360_remove_from_playlist');
        formData.append('playlist_id', playlistId);
        formData.append('video_id', currentVideoId);
        formData.append('nonce', vh360Data.playlistNonce);

        fetch(vh360Data.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const index = playlistsWithVideo.indexOf(parseInt(playlistId));
                if (index > -1) {
                    playlistsWithVideo.splice(index, 1);
                }
            } else {
                checkbox.checked = true;
                window.vh360Warn('VideoHub360: Remove from playlist failed:', data.data);
            }
        })
        .catch(error => {
            checkbox.checked = true;
            window.vh360Warn('VideoHub360: Remove from playlist error:', error);
        })
        .finally(() => {
            checkbox.disabled = false;
        });
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    window.vh360Log('VideoHub360: Playlists initialized');
})();
