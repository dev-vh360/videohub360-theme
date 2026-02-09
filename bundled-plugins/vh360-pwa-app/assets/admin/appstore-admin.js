(function($) {
	'use strict';
	
	/**
	 * Initialize character counters for text fields with limits
	 */
	function initCharacterCounters() {
		$('.vh360-char-counter').each(function() {
			const $field = $(this);
			const max = parseInt($field.data('max') || $field.attr('maxlength') || 0, 10);
			
			if (max === 0) {
				return;
			}
			
			// Update counter on input
			$field.on('input', function() {
				updateCharCounter($field, max);
			});
			
			// Initialize counter on page load
			updateCharCounter($field, max);
		});
	}
	
	/**
	 * Update character counter display
	 */
	function updateCharCounter($field, max) {
		const current = $field.val().length;
		const $row = $field.closest('td');
		const $counter = $row.find('.char-count');
		
		if ($counter.length === 0) {
			return;
		}
		
		// Update text
		$counter.text(current + '/' + max);
		
		// Update styling based on usage
		$counter.removeClass('warning error');
		
		const percentage = (current / max) * 100;
		if (percentage >= 100) {
			$counter.addClass('error');
		} else if (percentage >= 80) {
			$counter.addClass('warning');
		}
	}
	
	/**
	 * Form validation before submit
	 */
	function initFormValidation() {
		$('.vh360-appstore-metadata-form').on('submit', function(e) {
			const $form = $(this);
			let hasErrors = false;
			
			// Check required fields
			$form.find('[required]').each(function() {
				const $field = $(this);
				const value = $field.val().trim();
				
				if (value === '') {
					hasErrors = true;
					$field.addClass('vh360-field-error');
					
					// Show error message
					const $row = $field.closest('tr');
					if ($row.find('.validation-error').length === 0) {
						$row.find('td').append(
							'<p class="validation-error" style="color: #dc3232; margin-top: 5px;">This field is required.</p>'
						);
					}
				} else {
					$field.removeClass('vh360-field-error');
					$field.closest('tr').find('.validation-error').remove();
				}
			});
			
			// Check email format
			$form.find('input[type="email"]').each(function() {
				const $field = $(this);
				const value = $field.val().trim();
				
				if (value !== '' && !isValidEmail(value)) {
					hasErrors = true;
					$field.addClass('vh360-field-error');
					
					const $row = $field.closest('tr');
					if ($row.find('.validation-error').length === 0) {
						$row.find('td').append(
							'<p class="validation-error" style="color: #dc3232; margin-top: 5px;">Please enter a valid email address.</p>'
						);
					}
				}
			});
			
			// Check URL format
			$form.find('input[type="url"]').each(function() {
				const $field = $(this);
				const value = $field.val().trim();
				
				if (value !== '' && !isValidUrl(value)) {
					hasErrors = true;
					$field.addClass('vh360-field-error');
					
					const $row = $field.closest('tr');
					if ($row.find('.validation-error').length === 0) {
						$row.find('td').append(
							'<p class="validation-error" style="color: #dc3232; margin-top: 5px;">Please enter a valid URL starting with http:// or https://</p>'
						);
					}
				}
			});
			
			if (hasErrors) {
				e.preventDefault();
				
				// Scroll to first error
				const $firstError = $form.find('.vh360-field-error').first();
				if ($firstError.length > 0) {
					$('html, body').animate({
						scrollTop: $firstError.offset().top - 100
					}, 300);
				}
				
				// Show general error notice
				showNotice('error', 'Please fix the errors below before saving.');
				
				return false;
			}
		});
	}
	
	/**
	 * Validate email format
	 */
	function isValidEmail(email) {
		const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return re.test(email);
	}
	
	/**
	 * Validate URL format
	 */
	function isValidUrl(url) {
		try {
			const urlObj = new URL(url);
			return urlObj.protocol === 'http:' || urlObj.protocol === 'https:';
		} catch (e) {
			return false;
		}
	}
	
	/**
	 * Show admin notice
	 */
	function showNotice(type, message) {
		const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
		$('.vh360-pwa-appstore h1').after($notice);
		
		// Auto-dismiss after 5 seconds
		setTimeout(function() {
			$notice.fadeOut(function() {
				$(this).remove();
			});
		}, 5000);
	}
	
	/**
	 * Initialize export confirmation
	 */
	function initExportButtons() {
		// Already handled by inline onclick in template
		// This function is here for future enhancements if needed
	}
	
	/**
	 * Initialize on document ready
	 */
	$(function() {
		// Only run on App Store admin page
		if (!$('.vh360-pwa-appstore').length) {
			return;
		}
		
		initCharacterCounters();
		initFormValidation();
		initExportButtons();
	});
	
})(jQuery);
