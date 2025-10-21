<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PAD_VAIU_Metaboxes {
	public static function register_metaboxes() : void {
		add_meta_box(
			'pad_vaiu_details',
			__( 'Vehicle Details (AI + Manual)', 'prairieautodeal-vehicle-ai' ),
			array( __CLASS__, 'render_details' ),
			'vehicle_listing',
			'normal',
			'high'
		);

		add_meta_box(
			'pad_vaiu_images',
			__( 'Vehicle Images', 'prairieautodeal-vehicle-ai' ),
			array( __CLASS__, 'render_images' ),
			'vehicle_listing',
			'normal',
			'default'
		);
	}

	public static function render_details( WP_Post $post ) : void {
		$meta = array(
			'make' => get_post_meta( $post->ID, '_make', true ),
			'model' => get_post_meta( $post->ID, '_model', true ),
			'year' => get_post_meta( $post->ID, '_year', true ),
			'body_type' => get_post_meta( $post->ID, '_body_type', true ),
			'color' => get_post_meta( $post->ID, '_color', true ),
			'mileage' => get_post_meta( $post->ID, '_mileage', true ),
			'fuel_type' => get_post_meta( $post->ID, '_fuel_type', true ),
			'transmission' => get_post_meta( $post->ID, '_transmission', true ),
			'vin' => get_post_meta( $post->ID, '_vin', true ),
			'price' => get_post_meta( $post->ID, '_price', true ),
			'ai_confidence' => (array) get_post_meta( $post->ID, '_ai_confidence', true ),
			'ai_status' => get_post_meta( $post->ID, '_ai_status', true ),
			'ai_extracted_at' => get_post_meta( $post->ID, '_ai_extracted_at', true ),
		);
		$settings = PAD_VAIU_Settings::get_settings();
		$threshold = (float) ( $settings['confidence_threshold'] ?? 0.8 );
		wp_nonce_field( 'pad_vaiu_save_meta', 'pad_vaiu_nonce' );
		?>
		<div class="pad-vaiu-fields">
			<style>.pad-low-confidence{outline:2px solid #d63638;}</style>
			<table class="form-table">
				<tbody>
					<?php
					$fields = array(
						'make' => 'Make',
						'model' => 'Model',
						'year' => 'Year',
						'body_type' => 'Body Type',
						'color' => 'Color',
						'mileage' => 'Mileage (km)',
						'fuel_type' => 'Fuel Type',
						'transmission' => 'Transmission',
						'vin' => 'VIN',
						'price' => 'Price',
					);
					foreach ( $fields as $key => $label ) {
						$value = $meta[ $key ] ?? '';
						$conf = isset( $meta['ai_confidence'][ $key ] ) ? (float) $meta['ai_confidence'][ $key ] : null;
						$low = $conf !== null && $conf < $threshold ? ' pad-low-confidence' : '';
						echo '<tr><th><label>' . esc_html( $label ) . '</label></th><td>';
						$type = in_array( $key, array( 'year', 'mileage', 'price' ), true ) ? 'number' : 'text';
						echo '<input class="regular-text' . esc_attr( $low ) . '" type="' . esc_attr( $type ) . '" name="pad_' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
						if ( $conf !== null ) {
							echo '<p class="description">' . esc_html__( 'AI confidence: ', 'prairieautodeal-vehicle-ai' ) . esc_html( number_format_i18n( $conf * 100, 0 ) ) . '%</p>';
						}
						echo '</td></tr>';
					}
					?>
				</tbody>
			</table>
			<p>
				<button type="button" class="button button-secondary" id="pad-run-ai" data-post="<?php echo esc_attr( $post->ID ); ?>"><?php esc_html_e( 'Run AI Extraction', 'prairieautodeal-vehicle-ai' ); ?></button>
				<span class="pad-ai-status"><?php echo esc_html( $meta['ai_status'] ?: '—' ); ?><?php if ( ! empty( $meta['ai_extracted_at'] ) ) { echo ' · ' . esc_html( $meta['ai_extracted_at'] ); } ?></span>
			</p>
		</div>
		<?php
	}

	public static function render_images( WP_Post $post ) : void {
		$ids = (array) get_post_meta( $post->ID, '_images', true );
		?>
		<div id="pad-images-wrap">
			<button type="button" class="button" id="pad-add-images" data-post="<?php echo esc_attr( $post->ID ); ?>"><?php esc_html_e( 'Add Images', 'prairieautodeal-vehicle-ai' ); ?></button>
			<ul id="pad-image-list">
				<?php foreach ( $ids as $id ) : $src = wp_get_attachment_image_src( $id, 'thumbnail' ); if ( ! $src ) continue; ?>
				<li class="pad-image-item" data-id="<?php echo esc_attr( $id ); ?>">
					<img src="<?php echo esc_url( $src[0] ); ?>" alt="" />
				</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	public static function save_meta( int $post_id, WP_Post $post ) : void {
		if ( ! isset( $_POST['pad_vaiu_nonce'] ) || ! wp_verify_nonce( $_POST['pad_vaiu_nonce'], 'pad_vaiu_save_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
		if ( ! current_user_can( 'edit_vehicle_listing', $post_id ) ) { return; }

		$map = array(
			'_make' => 'pad_make',
			'_model' => 'pad_model',
			'_year' => 'pad_year',
			'_body_type' => 'pad_body_type',
			'_color' => 'pad_color',
			'_mileage' => 'pad_mileage',
			'_fuel_type' => 'pad_fuel_type',
			'_transmission' => 'pad_transmission',
			'_vin' => 'pad_vin',
			'_price' => 'pad_price',
		);
		foreach ( $map as $meta_key => $field_key ) {
			if ( ! isset( $_POST[ $field_key ] ) ) { continue; }
			$val = $_POST[ $field_key ];
			if ( in_array( $meta_key, array( '_year', '_mileage' ), true ) ) {
				$val = (int) $val;
			} elseif ( $meta_key === '_price' ) {
				$val = (float) $val;
			} else {
				$val = sanitize_text_field( $val );
			}
			update_post_meta( $post_id, $meta_key, $val );
		}
	}
}
