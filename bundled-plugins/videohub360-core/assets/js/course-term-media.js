/**
 * Course Term Media Uploader
 *
 * Handles the WordPress Media Library integration for the Course
 * Featured Image field on the Series taxonomy admin screens.
 *
 * @package VideoHub360_Core
 * @since   2.5.0
 */

(function ($) {
    'use strict';

    var frame;

    $(document).on('click', '.vh360-course-image-upload', function (e) {
        e.preventDefault();

        var $button  = $(this);
        var $field   = $button.closest('.vh360-course-image-field');
        var $input   = $field.find('.vh360-course-image-id');
        var $preview = $field.find('.vh360-course-image-preview');
        var $remove  = $field.find('.vh360-course-image-remove');

        frame = wp.media({
            title: 'Select Course Featured Image',
            button: {
                text: 'Use this image'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            var imageUrl   = '';

            if (attachment.sizes && attachment.sizes.medium) {
                imageUrl = attachment.sizes.medium.url;
            } else if (attachment.url) {
                imageUrl = attachment.url;
            }

            $input.val(attachment.id);

            if (imageUrl) {
                $preview.html(
                    '<img src="' + imageUrl + '" alt="" style="max-width:220px;height:auto;border:1px solid #ccd0d4;border-radius:4px;display:block;">'
                );
            }

            $remove.show();
        });

        frame.open();
    });

    $(document).on('click', '.vh360-course-image-remove', function (e) {
        e.preventDefault();

        var $button = $(this);
        var $field  = $button.closest('.vh360-course-image-field');

        $field.find('.vh360-course-image-id').val('');
        $field.find('.vh360-course-image-preview').empty();

        $button.hide();
    });

})(jQuery);
