<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PAD_VAIU_AI_Client {
    public static function extract_from_images( int $post_id, array $attachment_ids ) : array {
		$settings = PAD_VAIU_Settings::get_settings();
		$api_url = trailingslashit( $settings['api_url'] );
		$api_key = $settings['api_key'];

		if ( empty( $api_url ) || empty( $api_key ) ) {
			return array( 'error' => 'API not configured' );
		}

		$endpoint = $api_url . 'extract';

		$body = array();
		$files = array();
		foreach ( $attachment_ids as $id ) {
			$url = wp_get_attachment_url( $id );
			if ( ! $url ) { continue; }
			// Send URLs for microservice to fetch, avoids PHP memory spikes.
			$body['image_urls'][] = $url;
		}

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type' => 'application/json',
			),
			'timeout' => 30,
			'body' => wp_json_encode( $body ),
		);

        $attempts = 0;
        $max_attempts = 3;
        $backoff = 1; // seconds
        do {
            $attempts++;
            $response = wp_remote_post( $endpoint, $args );
            if ( is_wp_error( $response ) ) {
                PAD_VAIU_Logger::log( 'AI request WP_Error', array( 'attempt' => $attempts, 'error' => $response->get_error_message() ) );
                sleep( $backoff );
                $backoff *= 2;
                continue;
            }
            $code = wp_remote_retrieve_response_code( $response );
            $raw  = wp_remote_retrieve_body( $response );
            $data = json_decode( $raw, true );
            if ( $code >= 500 ) {
                PAD_VAIU_Logger::log( 'AI request server error, retrying', array( 'attempt' => $attempts, 'code' => $code ) );
                sleep( $backoff );
                $backoff *= 2;
                continue;
            }
            if ( $code >= 400 || ! is_array( $data ) ) {
                PAD_VAIU_Logger::log( 'AI request failed', array( 'code' => $code, 'body' => $raw ) );
                return array( 'error' => 'AI service error', 'code' => $code );
            }
            return $data;
        } while ( $attempts < $max_attempts );

        return array( 'error' => 'AI service unavailable' );
	}
}
