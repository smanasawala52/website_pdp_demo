<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PAD_VAIU_Roles {
	const ROLE_DEALER = 'pad_dealer';

	public static function add_roles_and_caps() : void {
		add_role( self::ROLE_DEALER, __( 'Dealer', 'prairieautodeal-vehicle-ai' ), array(
			'read' => true,
			'upload_files' => true,
		) );

		$admin = get_role( 'administrator' );
		$dealer = get_role( self::ROLE_DEALER );

		$caps = array(
			'edit_vehicle_listings',
			'edit_others_vehicle_listings',
			'publish_vehicle_listings',
			'edit_vehicle_listing',
			'edit_others_vehicle_listing',
			'edit_private_vehicle_listings',
			'edit_published_vehicle_listings',
			'upload_vehicle_images',
			'run_vehicle_ai',
			'manage_vehicle_ai_settings',
		);

		foreach ( $caps as $cap ) {
			if ( $admin ) { $admin->add_cap( $cap ); }
			if ( $dealer ) {
				// Dealers: create/edit own listings, upload images, run AI
				if ( in_array( $cap, array( 'edit_vehicle_listings', 'edit_vehicle_listing', 'edit_published_vehicle_listings', 'publish_vehicle_listings', 'upload_vehicle_images', 'run_vehicle_ai' ), true ) ) {
					$dealer->add_cap( $cap );
				}
			}
		}
	}
}
