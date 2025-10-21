<?php
/**
 * Plugin Name: PrairieAutoDeal Vehicle AI Uploader
 * Description: Upload vehicle images, extract data via AI (Make/Model/Year/etc.), and manage vehicle listings with review UI.
 * Version: 0.1.0
 * Author: PrairieAutoDeal
 * License: GPL-2.0-or-later
 * Text Domain: prairieautodeal-vehicle-ai
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Constants.
if ( ! defined( 'PAD_VAIU_VERSION' ) ) {
	define( 'PAD_VAIU_VERSION' , '0.1.0' );
}
if ( ! defined( 'PAD_VAIU_FILE' ) ) {
	define( 'PAD_VAIU_FILE', __FILE__ );
}
if ( ! defined( 'PAD_VAIU_DIR' ) ) {
	define( 'PAD_VAIU_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'PAD_VAIU_URL' ) ) {
	define( 'PAD_VAIU_URL', plugin_dir_url( __FILE__ ) );
}

// Includes.
require_once PAD_VAIU_DIR . 'includes/class-pad-logger.php';
require_once PAD_VAIU_DIR . 'includes/class-pad-roles.php';
require_once PAD_VAIU_DIR . 'includes/class-pad-cpt.php';
require_once PAD_VAIU_DIR . 'includes/class-pad-settings.php';
require_once PAD_VAIU_DIR . 'includes/class-pad-ai-client.php';
require_once PAD_VAIU_DIR . 'includes/class-pad-ajax.php';
require_once PAD_VAIU_DIR . 'includes/class-pad-metaboxes.php';
require_once PAD_VAIU_DIR . 'includes/class-pad-rest.php';
require_once PAD_VAIU_DIR . 'includes/class-pad-bulk-upload.php';

/**
 * Main plugin bootstrap.
 */
final class PAD_VAIU_Plugin {
	public function init() : void {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( 'PAD_VAIU_CPT', 'register' ) );
		add_action( 'admin_init', array( 'PAD_VAIU_Settings', 'register_settings' ) );
		add_action( 'admin_menu', array( 'PAD_VAIU_Settings', 'add_settings_page' ) );
		add_action( 'add_meta_boxes', array( 'PAD_VAIU_Metaboxes', 'register_metaboxes' ) );
		add_action( 'save_post_vehicle_listing', array( 'PAD_VAIU_Metaboxes', 'save_meta' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		PAD_VAIU_Ajax::register();
		PAD_VAIU_Rest::register_routes();
		PAD_VAIU_Bulk_Upload::register_admin_page();
	}

	public function load_textdomain() : void {
		load_plugin_textdomain( 'prairieautodeal-vehicle-ai', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public function enqueue_admin_assets( string $hook_suffix ) : void {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$enqueue = in_array( $screen->post_type ?? '', array( 'vehicle_listing' ), true )
			|| 'settings_page_prairieautodeal-vehicle-ai' === $hook_suffix
			|| 'tools_page_pad-vehicle-bulk-upload' === $hook_suffix;

		if ( ! $enqueue ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_style( 'pad-vaiu-admin', PAD_VAIU_URL . 'assets/css/pad-admin.css', array(), PAD_VAIU_VERSION );
		wp_enqueue_script( 'pad-vaiu-admin', PAD_VAIU_URL . 'assets/js/pad-admin.js', array( 'jquery', 'jquery-ui-sortable' ), PAD_VAIU_VERSION, true );

		$settings = PAD_VAIU_Settings::get_settings();
		wp_localize_script( 'pad-vaiu-admin', 'PAD_VAIU', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'pad_vaiu_admin' ),
			'maxImages' => (int) ( $settings['max_images'] ?? 10 ),
			'maxSizeMb' => (int) ( $settings['max_size_mb'] ?? 10 ),
			'confThreshold' => (float) ( $settings['confidence_threshold'] ?? 0.8 ),
		) );
	}
}

/**
 * Activation/Deactivation hooks.
 */
function pad_vaiu_activate() : void {
	// Ensure CPT exists before mapping caps.
	PAD_VAIU_CPT::register();
	PAD_VAIU_Roles::add_roles_and_caps();
	flush_rewrite_rules();
}

function pad_vaiu_deactivate() : void {
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'pad_vaiu_activate' );
register_deactivation_hook( __FILE__, 'pad_vaiu_deactivate' );

// Bootstrap plugin.
( new PAD_VAIU_Plugin() )->init();
