<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PAD_VAIU_CPT {
	public static function register() : void {
		$labels = array(
			'name' => __( 'Vehicle Listings', 'prairieautodeal-vehicle-ai' ),
			'singular_name' => __( 'Vehicle Listing', 'prairieautodeal-vehicle-ai' ),
			'add_new_item' => __( 'Add New Vehicle Listing', 'prairieautodeal-vehicle-ai' ),
			'edit_item' => __( 'Edit Vehicle Listing', 'prairieautodeal-vehicle-ai' ),
		);

		$caps = array(
			'edit_post'          => 'edit_vehicle_listing',
			'edit_posts'         => 'edit_vehicle_listings',
			'edit_others_posts'  => 'edit_others_vehicle_listings',
			'publish_posts'      => 'publish_vehicle_listings',
			'edit_published_posts' => 'edit_published_vehicle_listings',
		);

		$args = array(
			'labels' => $labels,
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'menu_icon' => 'dashicons-car',
			'supports' => array( 'title', 'thumbnail' ),
			'capability_type' => array( 'vehicle_listing', 'vehicle_listings' ),
			'map_meta_cap' => true,
			'capabilities' => $caps,
			'show_in_rest' => false,
		);

		register_post_type( 'vehicle_listing', $args );

		// Register meta fields.
		$meta_keys = array(
			'_make' => 'string',
			'_model' => 'string',
			'_year' => 'integer',
			'_body_type' => 'string',
			'_color' => 'string',
			'_mileage' => 'integer',
			'_fuel_type' => 'string',
			'_transmission' => 'string',
			'_vin' => 'string',
			'_price' => 'number',
			'_images' => 'array',
			'_dealer_id' => 'integer',
			'_ai_status' => 'string',
			'_source' => 'string',
			'_ai_confidence' => 'object',
			'_ai_extracted_at' => 'string',
		);

		foreach ( $meta_keys as $key => $type ) {
			register_post_meta( 'vehicle_listing', $key, array(
				'show_in_rest' => false,
				'single' => true,
				'type' => $type,
				'auth_callback' => function() { return current_user_can( 'edit_vehicle_listings' ); },
			));
		}
	}
}
