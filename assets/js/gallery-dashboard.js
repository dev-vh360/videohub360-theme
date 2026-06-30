/**
 * Gallery Dashboard JavaScript
 *
 * Handles gallery management in the frontend dashboard including
 * Dropzone uploads, Sortable reordering, and AJAX operations.
 *
 * @package Videohub360_Theme
 * @since 1.2.0
 */

(function($) {
    'use strict';

    // Debug logging helper - only log when __VH360_DEBUG is enabled
    const vh360Warn = (...args) => { if (window.__VH360_DEBUG) console.warn(...args); };

    // Ensure vh360Gallery object exists with defaults
    if (typeof window.vh360Gallery === 'undefined') {
        window.vh360Gallery = {
            ajaxUrl: (typeof ajaxurl !== 'undefined') ? ajaxurl : '/wp-admin/admin-ajax.php',
            nonce: '',
            maxFileSize: 5,
            maxImages: 50,
            acceptedFiles: 'image/*',
            i18n: {
                createGallery: 'Create Gallery',
                editGallery: 'Edit Gallery',
                saveChanges: 'Save Changes',
                saving: 'Saving...',
                deleting: 'Deleting...',
                delete: 'Delete',
                noGalleries: 'No galleries yet',
                createFirstGallery: 'Create your first gallery to showcase your photos!'
            }
        };
    }

    /**
     * Gallery Dashboard object
     */
    var VH360GalleryDashboard = {

        dropzone: null,
        sortable: null,
        currentGalleryId: 0,
        uploadedImages: [],
        initialized: false,
        eventsbound: false,

        /**
         * Initialize gallery dashboard
         */
        init: function() {
            if (this.initialized) {
                return;
            }
            this.initialized = true;
            this.bindEvents();
            // Don't auto-initialize dropzone - wait for modal to open
        },

        /**
         * Bind event handlers - only bind once
         */
        bindEvents: function() {
            // Prevent binding events multiple times
            if (this.eventsbound) {
                return;
            }
            this.eventsbound = true;
            
            var self = this;

            // Create gallery button - use specific namespaced event
            $(document).on('click.vh360gallery', '.vh360-gallery-create-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.openCreateModal();
            });

            // Edit gallery button
            $(document).on('click.vh360gallery', '.vh360-gallery-edit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var galleryId = $(this).data('gallery-id');
                self.openEditModal(galleryId);
            });

            // Delete gallery button
            $(document).on('click.vh360gallery', '.vh360-gallery-delete', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var galleryId = $(this).data('gallery-id');
                self.confirmDelete(galleryId);
            });

            // Close modal - only for gallery modals
            $(document).on('click.vh360gallery', '.vh360-gallery-modal .vh360-modal-close, .vh360-gallery-modal .vh360-modal-cancel', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.closeModals();
            });

            // Close modal on overlay click - only for gallery modals
            $(document).on('click.vh360gallery', '.vh360-gallery-modal', function(e) {
                if (e.target === this) {
                    e.stopPropagation();
                    self.closeModals();
                }
            });

            // Close gallery modals on escape - check if gallery modal is open
            $(document).on('keydown.vh360gallery', function(e) {
                if (e.key === 'Escape' && $('.vh360-gallery-modal.show').length > 0) {
                    self.closeModals();
                }
            });

            // Submit gallery form
            $(document).on('submit.vh360gallery', '#vh360-gallery-form', function(e) {
                e.preventDefault();
                self.saveGallery();
            });

            // Confirm delete
            $(document).on('click.vh360gallery', '#vh360-gallery-confirm-delete', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.deleteGallery();
            });

            // Filter tabs - only for gallery filter tabs
            $(document).on('click.vh360gallery', '.vh360-gallery-stats ~ .vh360-dashboard-filters .vh360-dashboard-filter-tab', function(e) {
                e.preventDefault();
                var status = $(this).data('status');
                self.filterGalleries(status);
                $(this).closest('.vh360-dashboard-filter-tabs').find('.vh360-dashboard-filter-tab').removeClass('active');
                $(this).addClass('active');
            });

            // Search galleries
            var searchTimeout;
            $(document).on('input.vh360gallery', '#vh360-gallery-search', function() {
                var query = $(this).val().toLowerCase();
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    self.searchGalleries(query);
                }, 300);
            });

            // Remove image from preview
            $(document).on('click.vh360gallery', '.vh360-preview-remove', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $item = $(this).closest('.vh360-preview-item');
                var imageId = $item.data('id');
                self.removeImageFromPreview(imageId, $item);
            });

            // Set cover image
            $(document).on('click.vh360gallery', '.vh360-preview-cover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $item = $(this).closest('.vh360-preview-item');
                self.setCoverImage($item);
            });
            
            // Direct click handler for dropzone - always trigger file input for reliable click-to-upload
            $(document).on('click.vh360gallery', '#vh360-gallery-dropzone, #vh360-gallery-dropzone *', function(e) {
                // Only trigger if click is on the dropzone or its children, not on the hidden file input
                if (e.target.tagName === 'INPUT' && e.target.type === 'file') {
                    return;
                }
                
                e.preventDefault();
                e.stopPropagation();
                
                // Always trigger the manual file input for reliable behavior
                var $fileInput = $('#vh360-gallery-file-input');
                if ($fileInput.length) {
                    $fileInput.val('').trigger('click');
                }
            });
            
            // Handle manual file input change
            $(document).on('change.vh360gallery', '#vh360-gallery-file-input', function(e) {
                var files = e.target.files;
                if (files.length && self.dropzone) {
                    // Add files to dropzone
                    for (var i = 0; i < files.length; i++) {
                        self.dropzone.addFile(files[i]);
                    }
                    // Reset the input so the same file can be selected again
                    $(this).val('');
                }
            });
        },

        /**
         * Initialize Dropzone
         */
        initDropzone: function() {
            var self = this;

            // Check if Dropzone is available
            if (typeof Dropzone === 'undefined') {
                vh360Warn('Dropzone.js is not loaded');
                return;
            }

            // Disable auto discover
            Dropzone.autoDiscover = false;

            // Get settings from localized data
            var maxFileSize = vh360Gallery && vh360Gallery.maxFileSize ? vh360Gallery.maxFileSize : 5;
            var acceptedFiles = vh360Gallery && vh360Gallery.acceptedFiles ? vh360Gallery.acceptedFiles : 'image/*';

            // Initialize dropzone if element exists
            var dropzoneEl = document.getElementById('vh360-gallery-dropzone');
            if (!dropzoneEl) {
                return;
            }

            // Destroy existing dropzone if any
            if (self.dropzone) {
                self.dropzone.destroy();
            }

            self.dropzone = new Dropzone('#vh360-gallery-dropzone', {
                url: vh360Gallery.ajaxUrl,
                paramName: 'images',
                uploadMultiple: true,
                parallelUploads: 5,
                maxFilesize: maxFileSize,
                acceptedFiles: acceptedFiles,
                addRemoveLinks: false,
                createImageThumbnails: true,
                thumbnailWidth: 150,
                thumbnailHeight: 150,
                autoProcessQueue: false,
                clickable: false,  // Disable Dropzone's click handling - we handle it ourselves
                dictDefaultMessage: '',
                previewsContainer: false,  // Disable default previews since we have custom ones

                init: function() {
                    var dz = this;
                    
                    // Ensure the dropzone element is set up for clicks
                    var element = dz.element;
                    if (element) {
                        element.style.cursor = 'pointer';
                    }

                    this.on('addedfile', function(file) {
                        self.addFilePreview(file);
                    });

                    this.on('thumbnail', function(file, dataUrl) {
                        // Update preview thumbnail
                        var $preview = $('#vh360-gallery-images-preview').find('[data-uuid="' + file.upload.uuid + '"]');
                        if ($preview.length) {
                            $preview.find('img').attr('src', dataUrl);
                        }
                    });

                    this.on('error', function(file, message) {
                        console.error('Upload error:', message);
                        self.showNotification(message, 'error');
                        dz.removeFile(file);
                    });

                    this.on('queuecomplete', function() {
                        // Queue processing complete
                    });
                },

                sending: function(file, xhr, formData) {
                    formData.append('action', 'vh360_upload_gallery_images');
                    formData.append('nonce', vh360Gallery.nonce);
                    formData.append('gallery_id', self.currentGalleryId);
                },

                success: function(file, response) {
                    if (response.success && response.data.images) {
                        response.data.images.forEach(function(img) {
                            self.uploadedImages.push(img);
                            // Update preview with actual ID
                            var $preview = $('#vh360-gallery-images-preview').find('[data-uuid="' + file.upload.uuid + '"]');
                            if ($preview.length) {
                                $preview.attr('data-id', img.id);
                                $preview.removeClass('uploading');
                            }
                        });
                    }
                }
            });
        },

        /**
         * Add file preview
         */
        addFilePreview: function(file) {
            var previewHtml = '<div class="vh360-preview-item uploading" data-uuid="' + file.upload.uuid + '">' +
                '<div class="vh360-preview-image">' +
                '<img src="" alt="">' +
                '<div class="vh360-preview-overlay">' +
                '<button type="button" class="vh360-preview-cover" title="Set as cover">' +
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>' +
                '</button>' +
                '<button type="button" class="vh360-preview-remove" title="Remove">' +
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
                '</button>' +
                '</div>' +
                '</div>' +
                '<div class="vh360-preview-loading"></div>' +
                '</div>';

            $('#vh360-gallery-images-preview').append(previewHtml);
            this.initSortable();
        },

        /**
         * Initialize Sortable for image reordering
         */
        initSortable: function() {
            var self = this;
            var container = document.getElementById('vh360-gallery-images-preview');

            if (!container) {
                return;
            }

            // Check if Sortable is available
            if (typeof Sortable === 'undefined') {
                vh360Warn('Sortable.js is not loaded');
                return;
            }

            // Destroy existing sortable
            if (self.sortable) {
                self.sortable.destroy();
            }

            self.sortable = new Sortable(container, {
                animation: 150,
                ghostClass: 'vh360-preview-ghost',
                chosenClass: 'vh360-preview-chosen',
                dragClass: 'vh360-preview-drag',
                handle: '.vh360-preview-image',
                onEnd: function(evt) {
                    // Reorder happened, update order if we have a gallery ID
                    if (self.currentGalleryId) {
                        self.saveImageOrder();
                    }
                }
            });
        },

        /**
         * Open create gallery modal
         */
        openCreateModal: function() {
            var self = this;
            this.currentGalleryId = 0;
            this.uploadedImages = [];

            // Reset form safely
            var $form = $('#vh360-gallery-form');
            if ($form.length && $form[0] && typeof $form[0].reset === 'function') {
                try {
                    $form[0].reset();
                } catch (e) {
                    // Fallback: clear inputs manually
                    $form.find('input:not([type="hidden"]):not([type="checkbox"])').val('');
                    $form.find('textarea').val('');
                    $form.find('select').prop('selectedIndex', 0);
                    // Keep lightbox checkbox checked by default (better UX)
                    $form.find('#vh360-gallery-lightbox').prop('checked', true);
                }
            }
            
            $('#vh360-gallery-id').val(0);
            $('#vh360-gallery-images-preview').empty();
            
            // Update modal text
            var createText = (window.vh360Gallery && window.vh360Gallery.i18n && window.vh360Gallery.i18n.createGallery) 
                ? window.vh360Gallery.i18n.createGallery 
                : 'Create Gallery';
            $('#vh360-gallery-modal-title').text(createText);
            $('#vh360-gallery-submit').text(createText);

            // Show modal first
            var $modal = $('#vh360-gallery-modal');
            if ($modal.length) {
                $modal.addClass('show');
                $('body').addClass('vh360-modal-open');
                
                // Initialize dropzone after modal is visible
                setTimeout(function() {
                    self.initDropzone();
                }, 100);
            }
        },

        /**
         * Open edit gallery modal
         */
        openEditModal: function(galleryId) {
            var self = this;
            this.currentGalleryId = galleryId;
            this.uploadedImages = [];

            // Show loading
            $('#vh360-gallery-modal').addClass('show');
            $('body').addClass('vh360-modal-open');

            // Fetch gallery data
            $.ajax({
                url: vh360Gallery.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_get_gallery',
                    nonce: vh360Gallery.nonce,
                    gallery_id: galleryId
                },
                success: function(response) {
                    if (response.success) {
                        self.populateEditForm(response.data.gallery);
                    } else {
                        self.showNotification(response.data.message || 'Failed to load gallery', 'error');
                        self.closeModals();
                    }
                },
                error: function() {
                    self.showNotification('Failed to load gallery', 'error');
                    self.closeModals();
                }
            });
        },

        /**
         * Populate edit form with gallery data
         */
        populateEditForm: function(gallery) {
            var self = this;

            $('#vh360-gallery-id').val(gallery.id);
            $('#vh360-gallery-title').val(gallery.title);
            $('#vh360-gallery-description').val(gallery.excerpt);
            $('#vh360-gallery-layout').val(gallery.layout);
            $('#vh360-gallery-columns').val(gallery.columns);
            $('#vh360-gallery-status').val(gallery.status);
            $('#vh360-gallery-lightbox').prop('checked', gallery.lightbox);

            // Set category if exists
            if (gallery.categories && gallery.categories.length > 0) {
                // Find the category by name and select it
                $('#vh360-gallery-category option').each(function() {
                    if (gallery.categories.indexOf($(this).text()) > -1) {
                        $(this).prop('selected', true);
                    }
                });
            }

            // Set tags
            if (gallery.tags && gallery.tags.length > 0) {
                $('#vh360-gallery-tags').val(gallery.tags.join(', '));
            }

            // Update modal title and button
            $('#vh360-gallery-modal-title').text(vh360Gallery.i18n.editGallery || 'Edit Gallery');
            $('#vh360-gallery-submit').text(vh360Gallery.i18n.saveChanges || 'Save Changes');

            // Load existing images
            $('#vh360-gallery-images-preview').empty();
            if (gallery.images && gallery.images.length > 0) {
                gallery.images.forEach(function(img) {
                    self.uploadedImages.push(img);
                    var isCover = gallery.cover && gallery.cover.indexOf(img.src) > -1;
                    var previewHtml = '<div class="vh360-preview-item' + (isCover ? ' is-cover' : '') + '" data-id="' + img.id + '">' +
                        '<div class="vh360-preview-image">' +
                        '<img src="' + img.src + '" alt="">' +
                        '<div class="vh360-preview-overlay">' +
                        '<button type="button" class="vh360-preview-cover" title="Set as cover">' +
                        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>' +
                        '</button>' +
                        '<button type="button" class="vh360-preview-remove" title="Remove">' +
                        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
                        '</button>' +
                        '</div>' +
                        '</div>' +
                        '</div>';
                    $('#vh360-gallery-images-preview').append(previewHtml);
                });
            }

            // Initialize sortable for existing images
            this.initSortable();
            
            // Reinitialize dropzone
            this.initDropzone();
        },

        /**
         * Save gallery
         */
        saveGallery: function() {
            var self = this;
            var $form = $('#vh360-gallery-form');
            var $submit = $('#vh360-gallery-submit');
            var isEdit = this.currentGalleryId > 0;

            // Validate
            var title = $('#vh360-gallery-title').val().trim();
            if (!title) {
                self.showNotification('Gallery title is required', 'error');
                return;
            }

            // Disable submit button
            $submit.prop('disabled', true).text(vh360Gallery.i18n.saving || 'Saving...');

            // Build form data
            var formData = {
                action: isEdit ? 'vh360_update_gallery' : 'vh360_create_gallery',
                nonce: vh360Gallery.nonce,
                title: title,
                description: $('#vh360-gallery-description').val(),
                layout: $('#vh360-gallery-layout').val(),
                columns: $('#vh360-gallery-columns').val(),
                status: $('#vh360-gallery-status').val(),
                lightbox: $('#vh360-gallery-lightbox').is(':checked'),
                tags: $('#vh360-gallery-tags').val()
            };

            // Add gallery ID for edit
            if (isEdit) {
                formData.gallery_id = this.currentGalleryId;
            }

            // Add category
            var category = $('#vh360-gallery-category').val();
            if (category) {
                formData.categories = [category];
            }
            
            // Add cover image ID if one is selected
            var $coverItem = $('#vh360-gallery-images-preview .vh360-preview-item.is-cover');
            if ($coverItem.length && $coverItem.data('id')) {
                formData.cover_image_id = $coverItem.data('id');
            }

            // Create gallery first if new
            $.ajax({
                url: vh360Gallery.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        var galleryId = response.data.gallery_id || response.data.gallery.id;
                        self.currentGalleryId = galleryId;

                        // Now upload any queued images
                        if (self.dropzone && self.dropzone.getQueuedFiles().length > 0) {
                            self.dropzone.processQueue();
                            
                            // Wait for uploads to complete
                            self.dropzone.on('queuecomplete', function() {
                                self.saveImageOrder();
                                // Set cover image after upload if one was selected
                                var $newCoverItem = $('#vh360-gallery-images-preview .vh360-preview-item.is-cover');
                                if ($newCoverItem.length && $newCoverItem.data('id')) {
                                    self.saveCoverImageToServer($newCoverItem.data('id'));
                                }
                                self.showNotification(response.data.message, 'success');
                                self.closeModals();
                                self.refreshGalleryGrid();
                            });
                        } else if (isEdit) {
                            // Save image order for existing gallery
                            self.saveImageOrder();
                            self.showNotification(response.data.message, 'success');
                            self.closeModals();
                            self.refreshGalleryGrid();
                        } else {
                            self.showNotification(response.data.message, 'success');
                            self.closeModals();
                            self.refreshGalleryGrid();
                        }
                    } else {
                        self.showNotification(response.data.message || 'Failed to save gallery', 'error');
                    }
                },
                error: function() {
                    self.showNotification('An error occurred', 'error');
                },
                complete: function() {
                    $submit.prop('disabled', false).text(isEdit ? (vh360Gallery.i18n.saveChanges || 'Save Changes') : (vh360Gallery.i18n.createGallery || 'Create Gallery'));
                }
            });
        },

        /**
         * Save cover image to server
         */
        saveCoverImageToServer: function(imageId) {
            if (!imageId || !this.currentGalleryId) {
                return;
            }

            $.ajax({
                url: vh360Gallery.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_set_gallery_cover',
                    nonce: vh360Gallery.nonce,
                    gallery_id: this.currentGalleryId,
                    attachment_id: imageId
                }
            });
        },

        /**
         * Save image order
         */
        saveImageOrder: function() {
            var self = this;
            var order = [];

            $('#vh360-gallery-images-preview .vh360-preview-item').each(function() {
                var id = $(this).data('id');
                if (id) {
                    order.push(id);
                }
            });

            if (order.length === 0 || !this.currentGalleryId) {
                return;
            }

            $.ajax({
                url: vh360Gallery.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_reorder_gallery_images',
                    nonce: vh360Gallery.nonce,
                    gallery_id: this.currentGalleryId,
                    order: order
                }
            });
        },

        /**
         * Remove image from preview
         */
        removeImageFromPreview: function(imageId, $item) {
            var self = this;

            if (imageId && this.currentGalleryId) {
                // Remove from server
                $.ajax({
                    url: vh360Gallery.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'vh360_delete_gallery_image',
                        nonce: vh360Gallery.nonce,
                        gallery_id: this.currentGalleryId,
                        attachment_id: imageId
                    },
                    success: function(response) {
                        if (response.success) {
                            $item.fadeOut(200, function() {
                                $(this).remove();
                            });
                            // Remove from local array
                            self.uploadedImages = self.uploadedImages.filter(function(img) {
                                return img.id !== imageId;
                            });
                        } else {
                            self.showNotification(response.data.message || 'Failed to remove image', 'error');
                        }
                    }
                });
            } else {
                // Just remove from preview (not yet saved)
                $item.fadeOut(200, function() {
                    $(this).remove();
                });
                
                // Remove from dropzone queue if applicable
                var uuid = $item.data('uuid');
                if (uuid && self.dropzone) {
                    var files = self.dropzone.files;
                    for (var i = 0; i < files.length; i++) {
                        if (files[i].upload && files[i].upload.uuid === uuid) {
                            self.dropzone.removeFile(files[i]);
                            break;
                        }
                    }
                }
            }
        },

        /**
         * Set cover image
         */
        setCoverImage: function($item) {
            var self = this;
            var imageId = $item.data('id');

            if (!imageId || !this.currentGalleryId) {
                // Just update UI for new gallery
                $('.vh360-preview-item').removeClass('is-cover');
                $item.addClass('is-cover');
                return;
            }

            $.ajax({
                url: vh360Gallery.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_set_gallery_cover',
                    nonce: vh360Gallery.nonce,
                    gallery_id: this.currentGalleryId,
                    attachment_id: imageId
                },
                success: function(response) {
                    if (response.success) {
                        $('.vh360-preview-item').removeClass('is-cover');
                        $item.addClass('is-cover');
                        self.showNotification('Cover image updated', 'success');
                    } else {
                        self.showNotification(response.data.message || 'Failed to set cover', 'error');
                    }
                }
            });
        },

        /**
         * Confirm delete gallery
         */
        confirmDelete: function(galleryId) {
            this.currentGalleryId = galleryId;
            $('#vh360-gallery-delete-modal').addClass('show');
        },

        /**
         * Delete gallery
         */
        deleteGallery: function() {
            var self = this;
            var $btn = $('#vh360-gallery-confirm-delete');

            $btn.prop('disabled', true).text(vh360Gallery.i18n.deleting || 'Deleting...');

            $.ajax({
                url: vh360Gallery.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_delete_gallery',
                    nonce: vh360Gallery.nonce,
                    gallery_id: this.currentGalleryId
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(response.data.message, 'success');
                        // Remove card from grid
                        $('.vh360-gallery-card[data-gallery-id="' + self.currentGalleryId + '"]').fadeOut(300, function() {
                            $(this).remove();
                            // Check if grid is empty
                            if ($('.vh360-gallery-card').length === 0) {
                                self.showEmptyState();
                            }
                        });
                        self.closeModals();
                    } else {
                        self.showNotification(response.data.message || 'Failed to delete gallery', 'error');
                    }
                },
                error: function() {
                    self.showNotification('An error occurred', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(vh360Gallery.i18n.delete || 'Delete');
                }
            });
        },

        /**
         * Close all gallery modals
         */
        closeModals: function() {
            // Only close gallery-specific modals
            $('.vh360-gallery-modal').removeClass('show');
            
            // Only remove body class if no other modals are open
            if ($('.vh360-modal-overlay.show').length === 0) {
                $('body').removeClass('vh360-modal-open');
            }
            
            this.currentGalleryId = 0;
        },

        /**
         * Filter galleries by status
         */
        filterGalleries: function(status) {
            if (status === 'all') {
                $('.vh360-gallery-card').show();
            } else {
                $('.vh360-gallery-card').hide();
                $('.vh360-gallery-card[data-status="' + status + '"]').show();
            }
        },

        /**
         * Search galleries
         */
        searchGalleries: function(query) {
            if (!query) {
                $('.vh360-gallery-card').show();
                return;
            }

            $('.vh360-gallery-card').each(function() {
                var title = $(this).find('.vh360-gallery-card-title').text().toLowerCase();
                if (title.indexOf(query) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },

        /**
         * Refresh gallery grid
         */
        refreshGalleryGrid: function() {
            // Reload the page to refresh the grid
            // In a more advanced implementation, we'd use AJAX to update the grid
            location.reload();
        },

        /**
         * Show empty state
         */
        showEmptyState: function() {
            var emptyHtml = '<div class="vh360-dashboard-empty">' +
                '<div class="vh360-dashboard-empty-icon">' +
                '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">' +
                '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>' +
                '<circle cx="8.5" cy="8.5" r="1.5"></circle>' +
                '<polyline points="21 15 16 10 5 21"></polyline>' +
                '</svg>' +
                '</div>' +
                '<p class="vh360-dashboard-empty-title">' + (vh360Gallery.i18n.noGalleries || 'No galleries yet') + '</p>' +
                '<p class="vh360-dashboard-empty-text">' + (vh360Gallery.i18n.createFirstGallery || 'Create your first gallery to showcase your photos!') + '</p>' +
                '<button type="button" class="vh360-dashboard-btn vh360-gallery-create-btn">' +
                (vh360Gallery.i18n.createGallery || 'Create Gallery') +
                '</button>' +
                '</div>';

            $('#vh360-galleries-grid').html(emptyHtml);
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            // Use existing notification system if available
            if (typeof VH360Dashboard !== 'undefined' && VH360Dashboard.showNotification) {
                VH360Dashboard.showNotification(message, type);
                return;
            }

            var iconSvg = type === 'success' 
                ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'
                : type === 'error'
                ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
                : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
            
            var $notification = $('<div class="vh360-notification vh360-notification-' + type + '">' + iconSvg + '<span>' + message + '</span></div>');
            
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.addClass('show');
            }, 100);
            
            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 3000);
        }
    };

    // Initialize on document ready - with safety checks
    $(document).ready(function() {
        // Only initialize if we're on a page with gallery elements
        var hasGalleryElements = $('#galleries').length > 0 || 
                                  $('#vh360-gallery-modal').length > 0 || 
                                  $('.vh360-gallery-create-btn').length > 0;
        
        if (hasGalleryElements) {
            // Use try-catch to prevent any errors from breaking the page
            try {
                VH360GalleryDashboard.init();
            } catch (e) {
                vh360Warn('Gallery Dashboard initialization error:', e);
            }
        }
    });

    // Export for external access - do NOT add extra click handlers for tab switching
    // The main dashboard.js handles all tab switching. We only need to initialize once.
    window.VH360GalleryDashboard = VH360GalleryDashboard;

})(jQuery);
