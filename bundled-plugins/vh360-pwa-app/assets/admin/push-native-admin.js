/**
 * Native Push Admin JavaScript
 * 
 * Handles AJAX interactions for APNs/FCM testing and device test sends.
 */
(function($) {
	'use strict';

	$(document).ready(function() {
		// Test APNs Connection
		$('#vh360_test_apns').on('click', function() {
			var $btn = $(this);
			var $result = $('#vh360_apns_test_result');
			
			$btn.prop('disabled', true).text('Testing...');
			$result.html('');
			
			$.ajax({
				url: VH360PushAdmin.ajaxurl,
				type: 'POST',
				data: {
					action: 'vh360_test_apns',
					nonce: VH360PushAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						$result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
					} else {
						$result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
					}
				},
				error: function() {
					$result.html('<div class="notice notice-error inline"><p>An error occurred. Please try again.</p></div>');
				},
				complete: function() {
					$btn.prop('disabled', false).text('Test APNs Connection');
				}
			});
		});

		// Test FCM Connection
		$('#vh360_test_fcm').on('click', function() {
			var $btn = $(this);
			var $result = $('#vh360_fcm_test_result');
			
			$btn.prop('disabled', true).text('Testing...');
			$result.html('');
			
			$.ajax({
				url: VH360PushAdmin.ajaxurl,
				type: 'POST',
				data: {
					action: 'vh360_test_fcm',
					nonce: VH360PushAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						$result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
					} else {
						$result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
					}
				},
				error: function() {
					$result.html('<div class="notice notice-error inline"><p>An error occurred. Please try again.</p></div>');
				},
				complete: function() {
					$btn.prop('disabled', false).text('Test FCM Connection');
				}
			});
		});

		// Send Test to Device
		$('#vh360_send_test_device_btn').on('click', function() {
			var $btn = $(this);
			var $result = $('#vh360_test_device_result');
			
			var deviceToken = $('#vh360_test_device_token').val().trim();
			var platform = $('#vh360_test_device_platform').val();
			var title = $('#vh360_test_device_title').val().trim();
			var body = $('#vh360_test_device_body').val().trim();
			
			// Validate input
			if (!deviceToken) {
				$result.html('<div class="notice notice-error inline"><p>Please enter a device token.</p></div>');
				return;
			}
			
			if (!title) {
				$result.html('<div class="notice notice-error inline"><p>Please enter a title.</p></div>');
				return;
			}
			
			if (!body) {
				$result.html('<div class="notice notice-error inline"><p>Please enter a body.</p></div>');
				return;
			}
			
			$btn.prop('disabled', true).text('Sending...');
			$result.html('');
			
			$.ajax({
				url: VH360PushAdmin.ajaxurl,
				type: 'POST',
				data: {
					action: 'vh360_send_test_device',
					nonce: VH360PushAdmin.nonce,
					device_token: deviceToken,
					platform: platform,
					title: title,
					body: body
				},
				success: function(response) {
					if (response.success) {
						$result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
					} else {
						$result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
					}
				},
				error: function() {
					$result.html('<div class="notice notice-error inline"><p>An error occurred. Please try again.</p></div>');
				},
				complete: function() {
					$btn.prop('disabled', false).text('Send Test Push');
				}
			});
		});
	});

})(jQuery);
