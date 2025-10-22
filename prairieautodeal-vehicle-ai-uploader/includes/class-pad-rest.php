<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PAD_VAIU_Rest {
	public static function register_routes() : void {
		add_action( 'rest_api_init', function() {
			register_rest_route( 'pad/v1', '/vehicle-listings', array(
				'methods' => 'GET',
				'callback' => array( __CLASS__, 'get_listings' ),
				'permission_callback' => function() { return current_user_can( 'edit_vehicle_listings' ); },
			) );
		} );
	}

	public static function get_listings( WP_REST_Request $request ) : WP_REST_Response {
		$args = array(
			'post_type' => 'vehicle_listing',
			'posts_per_page' => 50,
			'post_status' => array( 'publish', 'draft', 'pending' ),
		);
		$meta_query = array();
		if ( $request['ai_status'] ) {
			$meta_query[] = array(
				'key' => '_ai_status',
				'value' => sanitize_text_field( (string) $request['ai_status'] ),
			);
		}
		if ( $request['dealer_id'] ) {
			$meta_query[] = array(
				'key' => '_dealer_id',
				'value' => (int) $request['dealer_id'],
				'compare' => '=',
				'type' => 'NUMERIC',
			);
		}
		if ( $meta_query ) { $args['meta_query'] = $meta_query; }

		$q = new WP_Query( $args );
		$items = array();
		foreach ( $q->posts as $p ) {
			$ids = (array) get_post_meta( $p->ID, '_images', true );
			$items[] = array(
				'id' => $p->ID,
				'title' => $p->post_title,
				'images' => array_map( 'wp_get_attachment_url', $ids ),
				'meta' => array(
					'make' => get_post_meta( $p->ID, '_make', true ),
					'model' => get_post_meta( $p->ID, '_model', true ),
					'year' => (int) get_post_meta( $p->ID, '_year', true ),
					'body_type' => get_post_meta( $p->ID, '_body_type', true ),
					'color' => get_post_meta( $p->ID, '_color', true ),
					'mileage' => (int) get_post_meta( $p->ID, '_mileage', true ),
					'fuel_type' => get_post_meta( $p->ID, '_fuel_type', true ),
					'transmission' => get_post_meta( $p->ID, '_transmission', true ),
					'vin' => get_post_meta( $p->ID, '_vin', true ),
					'price' => (float) get_post_meta( $p->ID, '_price', true ),
					'ai_status' => get_post_meta( $p->ID, '_ai_status', true ),
					'ai_extracted_at' => get_post_meta( $p->ID, '_ai_extracted_at', true ),
				),
			);
		}

		return new WP_REST_Response( array( 'items' => $items, 'total' => (int) $q->found_posts ), 200 );
	}
}
