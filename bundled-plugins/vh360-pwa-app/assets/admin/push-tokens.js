/**
 * VH360 Push Tokens Admin JavaScript
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// View token modal
		$('.vh360-view-token').on('click', function(e) {
			e.preventDefault();
			
			var tokenId = $(this).data('token-id');
			var modal = $('#vh360-token-modal');
			var modalBody = $('#vh360-token-modal-body');
			
			// Show modal with loading state
			modalBody.html('<div class="vh360-modal-loading">Loading...</div>');
			modal.show();
			
			// Fetch token details
			$.ajax({
				url: VH360PushTokens.ajaxurl,
				type: 'POST',
				data: {
					action: 'vh360_get_token_details',
					nonce: VH360PushTokens.nonce,
					token_id: tokenId
				},
				success: function(response) {
					if (response.success) {
						modalBody.html(response.data.html);
					} else {
						modalBody.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
					}
				},
				error: function() {
					modalBody.html('<div class="notice notice-error"><p>Failed to load token details.</p></div>');
				}
			});
		});
		
		// Close modal
		$('.vh360-modal-close').on('click', function() {
			$('#vh360-token-modal').hide();
		});
		
		// Close modal on outside click
		$(window).on('click', function(e) {
			if ($(e.target).is('#vh360-token-modal')) {
				$('#vh360-token-modal').hide();
			}
		});
		
		// Escape key closes modal
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape') {
				$('#vh360-token-modal').hide();
			}
		});
	});

})(jQuery);
