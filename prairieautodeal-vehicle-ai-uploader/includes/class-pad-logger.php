<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PAD_VAIU_Logger {
	public static function log( string $message, array $context = array() ) : void {
		$enabled = (bool) ( PAD_VAIU_Settings::get_settings()['logging_enabled'] ?? true );
		if ( ! $enabled ) {
			return;
		}
		$entry = sprintf('[PAD_VAIU] %s %s', $message, $context ? wp_json_encode( $context ) : '' );
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( $entry );
		}
	}
}
