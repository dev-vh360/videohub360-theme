/**
 * Avatar Cropper
 * 
 * Handles client-side avatar cropping using Cropper.js
 * 
 * @package Videohub360_Theme
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Avatar cropper instance
    let cropperInstance = null;
    let currentImageURL = null;

    /**
     * Initialize avatar cropper on page load
     */
    $(document).ready(function() {
        initAvatarCropper();
    });

    /**
     * Initialize avatar cropper functionality
     */
    function initAvatarCropper() {
        const $fileInput = $('#profile_picture, input[name="profile_picture"]');
        
        if ($fileInput.length === 0) {
            return;
        }

        // Listen for file selection
        $fileInput.on('change', function(e) {
            const file = e.target.files[0];
            
            if (!file) {
                return;
            }

            // Validate file type using both MIME type and file extension
            // Some browsers (especially on iOS/Android) may report non-standard MIME types
            // for valid images (e.g. image/jpg instead of image/jpeg), so we check both.
            const fileName = file.name || '';
            const fileType = (file.type || '').toLowerCase();

            const allowedMimeTypes = [
                'image/jpeg',
                'image/jpg',
                'image/pjpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/heic',
                'image/heif',
                'image/heic-sequence',
                'image/heif-sequence'
            ];

            const allowedExtensions = /\.(jpe?g|png|gif|webp|heic|heif)$/i;

            if (!allowedMimeTypes.includes(fileType) && !allowedExtensions.test(fileName)) {
                alert(vh360AvatarCropper.i18n.invalidFileType || 'Invalid file type. Please upload a JPG, PNG, GIF, WebP, HEIC, or HEIF image.');
                e.target.value = '';
                return;
            }

            // Validate file size
            const maxSize = (vh360AvatarCropper.maxSize || 2) * 1024 * 1024;
            if (file.size > maxSize) {
                alert(vh360AvatarCropper.i18n.fileTooLarge || 'File size exceeds maximum allowed size.');
                e.target.value = '';
                return;
            }

            // HEIC/HEIF files cannot be previewed by most browsers inside an <img> tag.
            // Skip the cropper and submit directly to the backend, which will convert
            // the image to JPEG and apply a server-side center crop as the fallback.
            const heicExtensions = /\.(heic|heif)$/i;
            const heicMimeTypes = [
                'image/heic',
                'image/heif',
                'image/heic-sequence',
                'image/heif-sequence'
            ];

            if (heicMimeTypes.includes(fileType) || heicExtensions.test(fileName)) {
                ensureCropFields();

                $('input[name="avatar_crop_x"]').val('');
                $('input[name="avatar_crop_y"]').val('');
                $('input[name="avatar_crop_width"]').val('');
                $('input[name="avatar_crop_height"]').val('');
                $('input[name="avatar_source_width"]').val('');
                $('input[name="avatar_source_height"]').val('');

                return;
            }

            // Show crop modal and initialize cropper
            showCropModal(file);
        });
    }

    /**
     * Show crop modal with cropper interface
     */
    function showCropModal(file) {
        // Create URL for the selected file
        if (currentImageURL) {
            URL.revokeObjectURL(currentImageURL);
        }
        currentImageURL = URL.createObjectURL(file);

        // Create or get crop modal
        let $modal = $('#vh360-avatar-crop-modal');
        
        if ($modal.length === 0) {
            $modal = createCropModal();
            $('body').append($modal);
        }

        // Set image source
        const $cropImage = $modal.find('#vh360-crop-image');
        $cropImage.attr('src', currentImageURL);

        // Show modal
        $modal.fadeIn(200);

        // Initialize cropper after image loads
        $cropImage.off('load').on('load', function() {
            initCropper(this);
        });

        // Handle modal close
        $modal.find('.vh360-crop-modal-close, .vh360-crop-cancel').off('click').on('click', function() {
            closeCropModal();
        });

        // Handle crop confirmation
        $modal.find('.vh360-crop-apply').off('click').on('click', function() {
            applyCrop();
        });

        // Handle modal background click
        $modal.off('click').on('click', function(e) {
            if ($(e.target).is($modal)) {
                closeCropModal();
            }
        });
    }

    /**
     * Create crop modal HTML
     */
    function createCropModal() {
        const modalHTML = `
            <div id="vh360-avatar-crop-modal" class="vh360-crop-modal" style="display: none;">
                <div class="vh360-crop-modal-content">
                    <div class="vh360-crop-modal-header">
                        <h3>${vh360AvatarCropper.i18n.cropTitle || 'Crop Your Avatar'}</h3>
                        <button type="button" class="vh360-crop-modal-close" aria-label="${vh360AvatarCropper.i18n.close || 'Close'}">&times;</button>
                    </div>
                    <div class="vh360-crop-modal-body">
                        <div class="vh360-crop-container">
                            <img id="vh360-crop-image" src="" alt="${vh360AvatarCropper.i18n.cropImageAlt || 'Image to crop'}">
                        </div>
                        <div class="vh360-crop-preview-container">
                            <div class="vh360-crop-preview-label">${vh360AvatarCropper.i18n.previewLabel || 'Preview'}</div>
                            <div id="vh360-crop-preview" class="vh360-crop-preview"></div>
                        </div>
                    </div>
                    <div class="vh360-crop-modal-footer">
                        <button type="button" class="vh360-crop-cancel vh360-btn-secondary">${vh360AvatarCropper.i18n.cancel || 'Cancel'}</button>
                        <button type="button" class="vh360-crop-apply vh360-btn-primary">${vh360AvatarCropper.i18n.apply || 'Apply Crop'}</button>
                    </div>
                </div>
            </div>
        `;
        
        return $(modalHTML);
    }

    /**
     * Initialize Cropper.js on image
     */
    function initCropper(imageElement) {
        // Destroy existing cropper instance if any
        if (cropperInstance) {
            cropperInstance.destroy();
            cropperInstance = null;
        }

        const $preview = $('#vh360-crop-preview');

        // Initialize Cropper.js
        cropperInstance = new Cropper(imageElement, {
            aspectRatio: 1, // Square aspect ratio for avatars
            viewMode: 2, // Restrict crop box to not exceed the size of the canvas
            dragMode: 'move', // Allow dragging the image
            autoCropArea: 0.8, // Default crop area is 80% of the image
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
            minContainerWidth: 300,
            minContainerHeight: 300,
            preview: $preview[0], // Use Cropper.js built-in preview
            crop: function(event) {
                // Update hidden fields with crop data
                updateCropFields(event.detail);
            }
        });
    }

    /**
     * Update hidden crop coordinate fields
     */
    function updateCropFields(cropData) {
        // Ensure hidden fields exist
        ensureCropFields();

        // Round crop coordinates to integers for precision
        $('input[name="avatar_crop_x"]').val(Math.round(cropData.x));
        $('input[name="avatar_crop_y"]').val(Math.round(cropData.y));
        $('input[name="avatar_crop_width"]').val(Math.round(cropData.width));
        $('input[name="avatar_crop_height"]').val(Math.round(cropData.height));
    }

    /**
     * Ensure crop coordinate hidden fields exist in the form
     */
    function ensureCropFields() {
        const $form = $('#profile_picture, input[name="profile_picture"]').closest('form');
        
        if ($form.length === 0) {
            return;
        }

        // Create hidden fields if they don't exist
        const fields = ['avatar_crop_x', 'avatar_crop_y', 'avatar_crop_width', 'avatar_crop_height', 'avatar_source_width', 'avatar_source_height'];
        
        fields.forEach(function(fieldName) {
            if ($form.find(`input[name="${fieldName}"]`).length === 0) {
                $form.append(`<input type="hidden" name="${fieldName}" value="">`);
            }
        });
    }

    /**
     * Apply crop and close modal
     */
    function applyCrop() {
        if (!cropperInstance) {
            closeCropModal();
            return;
        }

        // Get crop data
        const cropData = cropperInstance.getData(true); // true = rounded values
        const imageData = cropperInstance.getImageData();

        // Update hidden fields
        updateCropFields(cropData);
        
        // Update source dimensions (naturalWidth/Height are already integers)
        $('input[name="avatar_source_width"]').val(imageData.naturalWidth);
        $('input[name="avatar_source_height"]').val(imageData.naturalHeight);

        // Get cropped canvas and convert to blob
        const canvas = cropperInstance.getCroppedCanvas({
            width: vh360AvatarCropper.outputSize || 300,
            height: vh360AvatarCropper.outputSize || 300,
            imageSmoothingQuality: 'high',
        });

        // Update preview in the form
        canvas.toBlob(function(blob) {
            if (blob) {
                const previewURL = URL.createObjectURL(blob);
                updateAvatarPreview(previewURL);
            }
            closeCropModal();
        }, 'image/jpeg', (vh360AvatarCropper.quality || 90) / 100);
    }

    /**
     * Update avatar preview in the form
     */
    function updateAvatarPreview(imageURL) {
        // Find avatar preview elements
        let $preview = $('.vh360-avatar-preview img, .vh360-avatar-upload img').first();
        
        if ($preview.length === 0) {
            // Create preview if it doesn't exist
            const $container = $('.vh360-avatar-upload, .vh360-avatar-preview').first();
            if ($container.length > 0) {
                $container.html(`<img src="${imageURL}" alt="${vh360AvatarCropper.i18n.previewAlt || 'Avatar preview'}" style="max-width: 150px; border-radius: 50%;">`);
            }
        } else {
            $preview.attr('src', imageURL);
        }
    }

    /**
     * Close crop modal and clean up
     */
    function closeCropModal() {
        const $modal = $('#vh360-avatar-crop-modal');
        
        // Fade out modal
        $modal.fadeOut(200, function() {
            // Destroy cropper instance
            if (cropperInstance) {
                cropperInstance.destroy();
                cropperInstance = null;
            }

            // Clear image source
            $modal.find('#vh360-crop-image').attr('src', '');
        });

        // Revoke object URL to free memory
        if (currentImageURL) {
            URL.revokeObjectURL(currentImageURL);
            currentImageURL = null;
        }

        // Reset file input only if crop was cancelled (no crop data)
        if (!$('input[name="avatar_crop_x"]').val()) {
            $('#profile_picture, input[name="profile_picture"]').val('');
        }
    }

})(jQuery);
