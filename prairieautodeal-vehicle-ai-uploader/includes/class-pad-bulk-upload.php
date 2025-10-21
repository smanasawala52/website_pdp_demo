<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PAD_VAIU_Bulk_Upload {
	public static function register_admin_page() : void {
		add_action( 'admin_menu', function() {
			$settings = PAD_VAIU_Settings::get_settings();
			if ( empty( $settings['enable_bulk'] ) ) { return; }
			add_management_page(
				__( 'Vehicle Bulk Upload (AI)', 'prairieautodeal-vehicle-ai' ),
				__( 'Vehicle Bulk Upload (AI)', 'prairieautodeal-vehicle-ai' ),
				'publish_vehicle_listings',
				'pad-vehicle-bulk-upload',
				array( __CLASS__, 'render' )
			);
		});
	}

	public static function render() : void {
		if ( ! current_user_can( 'publish_vehicle_listings' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'prairieautodeal-vehicle-ai' ) );
		}
		$settings = PAD_VAIU_Settings::get_settings();
		?>
		<div class="wrap" id="pad-bulk-page">
			<h1><?php echo esc_html__( 'Vehicle Bulk Upload (AI)', 'prairieautodeal-vehicle-ai' ); ?></h1>
			<p><?php esc_html_e( 'Select multiple images to create multiple listings. Choose how many images per listing, then optionally run AI automatically.', 'prairieautodeal-vehicle-ai' ); ?></p>
			<p>
				<label><?php esc_html_e( 'Images per listing:', 'prairieautodeal-vehicle-ai' ); ?>
					<input type="number" id="pad-bulk-images-per-listing" min="1" value="5" />
				</label>
				<label style="margin-left:1rem;">
					<input type="checkbox" id="pad-bulk-run-ai" checked /> <?php esc_html_e( 'Run AI after creating', 'prairieautodeal-vehicle-ai' ); ?>
				</label>
			</p>
			<p>
				<button type="button" class="button button-secondary" id="pad-bulk-select-images"><?php esc_html_e( 'Select Images', 'prairieautodeal-vehicle-ai' ); ?></button>
				<button type="button" class="button button-primary" id="pad-bulk-create" disabled><?php esc_html_e( 'Create Listings', 'prairieautodeal-vehicle-ai' ); ?></button>
			</p>
			<div id="pad-bulk-summary"></div>
			<ol id="pad-bulk-progress"></ol>
		</div>
		<?php
	}
}
