<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PAD_VAIU_Settings {
	const OPTION_KEY = 'pad_vaiu_settings';

	public static function get_settings() : array {
		$defaults = array(
			'api_url' => '',
			'api_key' => '',
			'max_images' => 10,
			'max_size_mb' => 10,
			'confidence_threshold' => 0.8,
			'enable_bulk' => 1,
			'logging_enabled' => 1,
		);
		$opts = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( is_array( $opts ) ? $opts : array(), $defaults );
	}

	public static function register_settings() : void {
		register_setting( 'pad_vaiu', self::OPTION_KEY, array( __CLASS__, 'sanitize' ) );

		add_settings_section( 'pad_vaiu_main', __( 'AI Service', 'prairieautodeal-vehicle-ai' ), '__return_false', 'pad_vaiu' );
		add_settings_field( 'api_url', __( 'API URL', 'prairieautodeal-vehicle-ai' ), array( __CLASS__, 'field_text' ), 'pad_vaiu', 'pad_vaiu_main', array( 'key' => 'api_url' ) );
		add_settings_field( 'api_key', __( 'API Key', 'prairieautodeal-vehicle-ai' ), array( __CLASS__, 'field_text' ), 'pad_vaiu', 'pad_vaiu_main', array( 'key' => 'api_key' ) );

		add_settings_section( 'pad_vaiu_limits', __( 'Limits & Thresholds', 'prairieautodeal-vehicle-ai' ), '__return_false', 'pad_vaiu' );
		add_settings_field( 'max_images', __( 'Max images per upload', 'prairieautodeal-vehicle-ai' ), array( __CLASS__, 'field_number' ), 'pad_vaiu', 'pad_vaiu_limits', array( 'key' => 'max_images', 'min' => 1 ) );
		add_settings_field( 'max_size_mb', __( 'Max image size (MB)', 'prairieautodeal-vehicle-ai' ), array( __CLASS__, 'field_number' ), 'pad_vaiu', 'pad_vaiu_limits', array( 'key' => 'max_size_mb', 'min' => 1 ) );
		add_settings_field( 'confidence_threshold', __( 'Confidence threshold', 'prairieautodeal-vehicle-ai' ), array( __CLASS__, 'field_number' ), 'pad_vaiu', 'pad_vaiu_limits', array( 'key' => 'confidence_threshold', 'step' => '0.01', 'min' => '0', 'max' => '1' ) );

		add_settings_section( 'pad_vaiu_features', __( 'Features', 'prairieautodeal-vehicle-ai' ), '__return_false', 'pad_vaiu' );
		add_settings_field( 'enable_bulk', __( 'Enable bulk uploads', 'prairieautodeal-vehicle-ai' ), array( __CLASS__, 'field_checkbox' ), 'pad_vaiu', 'pad_vaiu_features', array( 'key' => 'enable_bulk' ) );
		add_settings_field( 'logging_enabled', __( 'Enable logging', 'prairieautodeal-vehicle-ai' ), array( __CLASS__, 'field_checkbox' ), 'pad_vaiu', 'pad_vaiu_features', array( 'key' => 'logging_enabled' ) );
	}

	public static function add_settings_page() : void {
		add_options_page(
			__( 'PrairieAutoDeal Vehicle AI', 'prairieautodeal-vehicle-ai' ),
			__( 'PrairieAutoDeal Vehicle AI', 'prairieautodeal-vehicle-ai' ),
			'manage_vehicle_ai_settings',
			'prairieautodeal-vehicle-ai',
			array( __CLASS__, 'render' )
		);
	}

	public static function render() : void {
		if ( ! current_user_can( 'manage_vehicle_ai_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'prairieautodeal-vehicle-ai' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'PrairieAutoDeal Vehicle AI Settings', 'prairieautodeal-vehicle-ai' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'pad_vaiu' ); ?>
				<?php do_settings_sections( 'pad_vaiu' ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public static function sanitize( $input ) : array {
		$input = is_array( $input ) ? $input : array();
		$out = array();
		$out['api_url'] = esc_url_raw( $input['api_url'] ?? '' );
		$out['api_key'] = sanitize_text_field( $input['api_key'] ?? '' );
		$out['max_images'] = max( 1, (int) ( $input['max_images'] ?? 10 ) );
		$out['max_size_mb'] = max( 1, (int) ( $input['max_size_mb'] ?? 10 ) );
		$out['confidence_threshold'] = min( 1, max( 0, (float) ( $input['confidence_threshold'] ?? 0.8 ) ) );
		$out['enable_bulk'] = empty( $input['enable_bulk'] ) ? 0 : 1;
		$out['logging_enabled'] = empty( $input['logging_enabled'] ) ? 0 : 1;
		return $out;
	}

	public static function field_text( array $args ) : void {
		$key = $args['key'];
		$val = esc_attr( self::get_settings()[ $key ] ?? '' );
		echo '<input type="text" class="regular-text" name="' . esc_attr( self::OPTION_KEY . '[' . $key . ']' ) . '" value="' . $val . '" />';
	}

	public static function field_number( array $args ) : void {
		$key = $args['key'];
		$settings = self::get_settings();
		$attrs = '';
		foreach ( array( 'min', 'max', 'step' ) as $a ) {
			if ( isset( $args[ $a ] ) ) { $attrs .= ' ' . $a . '="' . esc_attr( $args[ $a ] ) . '"'; }
		}
		echo '<input type="number" name="' . esc_attr( self::OPTION_KEY . '[' . $key . ']' ) . '" value="' . esc_attr( $settings[ $key ] ?? '' ) . '"' . $attrs . ' />';
	}

	public static function field_checkbox( array $args ) : void {
		$key = $args['key'];
		$settings = self::get_settings();
		$checked = ! empty( $settings[ $key ] ) ? 'checked' : '';
		echo '<label><input type="checkbox" name="' . esc_attr( self::OPTION_KEY . '[' . $key . ']' ) . '" value="1" ' . $checked . ' /> ' . esc_html__( 'Enabled', 'prairieautodeal-vehicle-ai' ) . '</label>';
	}
}
