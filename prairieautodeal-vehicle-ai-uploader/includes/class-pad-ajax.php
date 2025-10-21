<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PAD_VAIU_Ajax {
	public static function register() : void {
		add_action( 'wp_ajax_pad_upload_vehicle_images', array( __CLASS__, 'upload_images' ) );
		add_action( 'wp_ajax_pad_run_vehicle_ai', array( __CLASS__, 'run_ai' ) );
		add_action( 'wp_ajax_pad_reorder_images', array( __CLASS__, 'reorder_images' ) );
		add_action( 'wp_ajax_pad_bulk_create_listing', array( __CLASS__, 'bulk_create_listing' ) );
	}

	private static function verify_nonce_and_caps( string $action_cap = 'upload_vehicle_images' ) : void {
		check_ajax_referer( 'pad_vaiu_admin', 'nonce' );
		if ( ! current_user_can( $action_cap ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'prairieautodeal-vehicle-ai' ) ), 403 );
		}
	}

	public static function upload_images() : void {
		self::verify_nonce_and_caps( 'upload_vehicle_images' );

		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		if ( ! $post_id || get_post_type( $post_id ) !== 'vehicle_listing' ) {
			wp_send_json_error( array( 'message' => 'Invalid post' ), 400 );
		}

		// Expect media library selection IDs or raw uploads via media modal.
		$ids = isset( $_POST['attachment_ids'] ) ? array_map( 'intval', (array) $_POST['attachment_ids'] ) : array();
		if ( empty( $ids ) ) {
			wp_send_json_error( array( 'message' => 'No images provided' ), 400 );
		}

		$settings = PAD_VAIU_Settings::get_settings();
		$max_images = (int) ( $settings['max_images'] ?? 10 );
		$max_size_mb = (int) ( $settings['max_size_mb'] ?? 10 );
		$max_size = $max_size_mb * 1024 * 1024;

		// Validate each image (mime and size)
		foreach ( $ids as $aid ) {
			if ( ! wp_attachment_is_image( $aid ) ) {
				wp_send_json_error( array( 'message' => 'One or more files are not images' ), 400 );
			}
			$file_path = get_attached_file( $aid );
			if ( ! $file_path || ! file_exists( $file_path ) ) {
				wp_send_json_error( array( 'message' => 'Attachment file missing' ), 400 );
			}
			$type = wp_check_filetype( $file_path );
			if ( empty( $type['type'] ) || ! in_array( $type['type'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
				wp_send_json_error( array( 'message' => 'Unsupported image type' ), 400 );
			}
			$size = filesize( $file_path );
			if ( $size !== false && $size > $max_size ) {
				wp_send_json_error( array( 'message' => 'Image exceeds max size' ), 400 );
			}
		}

		$existing = (array) get_post_meta( $post_id, '_images', true );
		$merged = array_values( array_unique( array_merge( $existing, $ids ) ) );
		if ( count( $merged ) > $max_images ) {
			$merged = array_slice( $merged, 0, $max_images );
		}
		update_post_meta( $post_id, '_images', $merged );

		wp_send_json_success( array( 'images' => $merged ) );
	}

	public static function reorder_images() : void {
		self::verify_nonce_and_caps( 'edit_vehicle_listing' );
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		$order = isset( $_POST['order'] ) ? array_map( 'intval', (array) $_POST['order'] ) : array();
		if ( ! $post_id || get_post_type( $post_id ) !== 'vehicle_listing' ) {
			wp_send_json_error( array( 'message' => 'Invalid post' ), 400 );
		}
		update_post_meta( $post_id, '_images', $order );
		wp_send_json_success( array( 'images' => $order ) );
	}

	public static function run_ai() : void {
		self::verify_nonce_and_caps( 'run_vehicle_ai' );
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		if ( ! $post_id || get_post_type( $post_id ) !== 'vehicle_listing' ) {
			wp_send_json_error( array( 'message' => 'Invalid post' ), 400 );
		}
		$images = (array) get_post_meta( $post_id, '_images', true );
		if ( empty( $images ) ) {
			wp_send_json_error( array( 'message' => 'No images to analyze' ), 400 );
		}

		update_post_meta( $post_id, '_ai_status', 'Pending' );

		$data = PAD_VAIU_AI_Client::extract_from_images( $post_id, $images );
		if ( isset( $data['error'] ) ) {
			update_post_meta( $post_id, '_ai_status', 'Error' );
			PAD_VAIU_Logger::log( 'AI extraction error', array( 'post_id' => $post_id, 'error' => $data['error'] ) );
			wp_send_json_error( array( 'message' => $data['error'] ), 500 );
		}

		$map = array(
			'_make' => $data['make'] ?? '',
			'_model' => $data['model'] ?? '',
			'_year' => isset( $data['year'] ) ? (int) $data['year'] : '',
			'_body_type' => $data['body_type'] ?? '',
			'_color' => $data['color'] ?? '',
			'_mileage' => isset( $data['mileage'] ) ? (int) $data['mileage'] : '',
			'_source' => 'AI extracted from image',
			'_ai_confidence' => $data['confidence'] ?? array(),
			'_ai_extracted_at' => current_time( 'mysql' ),
			'_ai_status' => 'Completed',
		);
		foreach ( $map as $k => $v ) {
			update_post_meta( $post_id, $k, $v );
		}

		wp_send_json_success( array( 'data' => $map ) );
	}

	public static function bulk_create_listing() : void {
		self::verify_nonce_and_caps( 'publish_vehicle_listings' );
		$ids = isset( $_POST['attachment_ids'] ) ? array_map( 'intval', (array) $_POST['attachment_ids'] ) : array();
		$run_ai = ! empty( $_POST['run_ai'] );
		if ( empty( $ids ) ) {
			wp_send_json_error( array( 'message' => 'No images provided' ), 400 );
		}

		$title = 'Vehicle ' . current_time( 'Y-m-d H:i:s' );
		$post_id = wp_insert_post( array(
			'post_type' => 'vehicle_listing',
			'post_status' => 'publish',
			'post_title' => sanitize_text_field( $title ),
		) );
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			wp_send_json_error( array( 'message' => 'Failed to create listing' ), 500 );
		}

		update_post_meta( $post_id, '_images', $ids );
		update_post_meta( $post_id, '_dealer_id', get_current_user_id() );

		$result = array( 'post_id' => $post_id );
		if ( $run_ai ) {
			update_post_meta( $post_id, '_ai_status', 'Pending' );
			$data = PAD_VAIU_AI_Client::extract_from_images( $post_id, $ids );
			if ( empty( $data['error'] ) ) {
				$map = array(
					'_make' => $data['make'] ?? '',
					'_model' => $data['model'] ?? '',
					'_year' => isset( $data['year'] ) ? (int) $data['year'] : '',
					'_body_type' => $data['body_type'] ?? '',
					'_color' => $data['color'] ?? '',
					'_mileage' => isset( $data['mileage'] ) ? (int) $data['mileage'] : '',
					'_source' => 'AI extracted from image',
					'_ai_confidence' => $data['confidence'] ?? array(),
					'_ai_extracted_at' => current_time( 'mysql' ),
					'_ai_status' => 'Completed',
				);
				foreach ( $map as $k => $v ) { update_post_meta( $post_id, $k, $v ); }
				$result['ai'] = $map;
			} else {
				update_post_meta( $post_id, '_ai_status', 'Error' );
				$result['ai_error'] = $data['error'] ?? 'AI error';
			}
		}

		wp_send_json_success( $result );
	}
}
