<?php
/**
 * VH360 PWA Rewrite Rules Monitor
 * 
 * Ensures rewrite rules stay registered even after WordPress/plugin updates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VH360_PWA_Rewrite_Monitor {
	
	const OPTION_KEY = 'vh360_pwa_rewrite_version';
	
	/**
	 * Register hooks
	 */
	public function register() : void {
		add_action( 'init', array( $this, 'maybe_flush_rules' ), 999 );
	}
	
	/**
	 * Check if rewrite rules need to be flushed
	 */
	public function maybe_flush_rules() : void {
		$stored_version = get_option( self::OPTION_KEY, '0' );
		$current_version = VH360_PWA_APP_VERSION;
		
		if ( version_compare( $stored_version, $current_version, '<' ) ) {
			VH360_PWA_Endpoints::add_rewrite_rules();
			flush_rewrite_rules( false );
			update_option( self::OPTION_KEY, $current_version );
		}
	}
}
