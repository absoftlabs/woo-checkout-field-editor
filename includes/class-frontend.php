<?php
namespace ABB\WCFE_BD;

if ( ! defined( 'ABSPATH' ) ) exit;

class Frontend {

	public static function init() {
		add_filter( 'woocommerce_checkout_fields', [ __CLASS__, 'filter_checkout_fields' ], 20 );
		add_action( 'woocommerce_checkout_process', [ __CLASS__, 'validate_checkout' ] );
		add_action( 'woocommerce_checkout_update_order_meta', [ __CLASS__, 'save_order_meta' ], 10, 2 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue' ] );

		add_action( 'woocommerce_admin_order_data_after_billing_address', [ __CLASS__, 'admin_show_billing' ] );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', [ __CLASS__, 'admin_show_shipping' ] );
	}

	public static function enqueue() {
		if ( function_exists('is_checkout') && is_checkout() && ! is_order_received_page() ) {
			$s = Admin::get_settings();
			wp_register_script( 'abb-wcfe-bd', ABB_WCFE_BD_URL . 'assets/js/checkout-bd.js', [], ABB_WCFE_BD_VER, true );
			wp_localize_script( 'abb-wcfe-bd', 'ABB_WCFE_BD', [
				'geo'           => Dataset::get_geo(),
				'i18n'          => [
					'select_district'    => __( 'Select District', 'abb-wcfe-bd' ),
					'select_subdistrict' => __( 'Select Sub-district', 'abb-wcfe-bd' ),
				],
				'placement'     => $s['bd_fields']['placement'],
				'allow_custom'  => (bool) $s['bd_fields']['allow_custom_subdistrict'],
				'country_mode'  => $s['bd_fields']['country_condition'],
			]);
			wp_enqueue_script( 'abb-wcfe-bd' );
			wp_enqueue_style( 'abb-wcfe-bd', ABB_WCFE_BD_URL . 'assets/css/checkout-bd.css', [], ABB_WCFE_BD_VER );
		}
	}

	/** Core blueprints so we can restore missing fields if other code removes them */
	private static function field_blueprints(): array {
		$common = [ 'class' => [ 'form-row-wide' ], 'autocomplete' => '' ];
		return [
			// Billing
			'billing_first_name' => [ 'type'=>'text','label'=>__( 'First name','woocommerce' ),'autocomplete'=>'given-name','class'=>['form-row-first'] ],
			'billing_last_name'  => [ 'type'=>'text','label'=>__( 'Last name','woocommerce' ), 'autocomplete'=>'family-name','class'=>['form-row-last'] ],
			'billing_company'    => [ 'type'=>'text','label'=>__( 'Company name','woocommerce' ) ] + $common,
			'billing_phone'      => [ 'type'=>'tel', 'label'=>__( 'Phone','woocommerce' ), 'validate'=>['phone'],'autocomplete'=>'tel' ] + $common,
			'billing_email'      => [ 'type'=>'email','label'=>__( 'Email address','woocommerce' ), 'autocomplete'=>'email' ] + $common,
			'billing_address_1'  => [ 'type'=>'text','label'=>__( 'Street address','woocommerce' ), 'autocomplete'=>'address-line1' ] + $common,
			'billing_address_2'  => [ 'type'=>'text','label'=>__( 'Apartment, suite, unit, etc.','woocommerce' ), 'autocomplete'=>'address-line2' ] + $common,
			'billing_city'       => [ 'type'=>'text','label'=>__( 'Town / City','woocommerce' ), 'autocomplete'=>'address-level2' ] + $common,
			'billing_state'      => [ 'type'=>'state','label'=>__( 'State / County','woocommerce' ) ] + $common,
			'billing_postcode'   => [ 'type'=>'text','label'=>__( 'Postcode / ZIP','woocommerce' ), 'autocomplete'=>'postal-code' ] + $common,
			'billing_country'    => [ 'type'=>'country','label'=>__( 'Country / Region','woocommerce' ) ] + $common,

			// Shipping
			'shipping_first_name'=> [ 'type'=>'text','label'=>__( 'First name','woocommerce' ), 'class'=>['form-row-first'] ],
			'shipping_last_name' => [ 'type'=>'text','label'=>__( 'Last name','woocommerce' ),  'class'=>['form-row-last'] ],
			'shipping_company'   => [ 'type'=>'text','label'=>__( 'Company name','woocommerce' ) ] + $common,
			'shipping_address_1' => [ 'type'=>'text','label'=>__( 'Street address','woocommerce' ) ] + $common,
			'shipping_address_2' => [ 'type'=>'text','label'=>__( 'Apartment, suite, unit, etc.','woocommerce' ) ] + $common,
			'shipping_city'      => [ 'type'=>'text','label'=>__( 'Town / City','woocommerce' ) ] + $common,
			'shipping_state'     => [ 'type'=>'state','label'=>__( 'State / County','woocommerce' ) ] + $common,
			'shipping_postcode'  => [ 'type'=>'text','label'=>__( 'Postcode / ZIP','woocommerce' ) ] + $common,
			'shipping_country'   => [ 'type'=>'country','label'=>__( 'Country / Region','woocommerce' ) ] + $common,

			// Order
			'order_comments'     => [ 'type'=>'textarea','label'=>__( 'Order notes','woocommerce' ), 'class'=>['notes'] ],
		];
	}

	public static function filter_checkout_fields( $fields ) {
		$s          = Admin::get_settings();
		$blueprints = self::field_blueprints();

		$widths = []; // capture widths per key to post-process layout

		// Apply core toggles, restore missing, stash width
		foreach ( $s['core_fields'] as $key => $cfg ) {
			$section = strpos( $key, 'billing_' ) === 0 ? 'billing' : ( strpos( $key, 'shipping_' ) === 0 ? 'shipping' : 'order' );
			$widths[ $key ] = $cfg['width'] ?? 'full';

			if ( isset( $fields[ $section ][ $key ] ) ) {
				if ( empty( $cfg['enabled'] ) ) {
					unset( $fields[ $section ][ $key ] );
					continue;
				}
				$fields[ $section ][ $key ]['required'] = ! empty( $cfg['required'] );
				$fields[ $section ][ $key ]['priority'] = intval( $cfg['priority'] ?? 10 );
				if ( ! empty( $cfg['label'] ) ) $fields[ $section ][ $key ]['label'] = $cfg['label'];
			} else {
				if ( ! empty( $cfg['enabled'] ) && isset( $blueprints[ $key ] ) ) {
					$def = $blueprints[ $key ];
					$def['required'] = ! empty( $cfg['required'] );
					$def['priority'] = intval( $cfg['priority'] ?? 10 );
					if ( ! empty( $cfg['label'] ) ) $def['label'] = $cfg['label'];
					if ( ! isset( $fields[ $section ] ) || ! is_array( $fields[ $section ] ) ) $fields[ $section ] = [];
					$fields[ $section ][ $key ] = $def;
				}
			}
		}

		// Inject Bangladesh fields (unchanged)
		$s_bd_enabled = ! empty( $s['bd_fields']['enabled'] );
		if ( $s_bd_enabled ) {
			$placement = $s['bd_fields']['placement'] === 'shipping' ? 'shipping' : 'billing';
			$d_key = $placement . '_bd_district';
			$u_key = $placement . '_bd_subdistrict';

			if ( ! empty( $s['bd_fields']['district']['enabled'] ) ) {
				$fields[ $placement ][ $d_key ] = [
					'type'        => 'select',
					'label'       => sanitize_text_field( $s['bd_fields']['district']['label'] ),
					'required'    => ! empty( $s['bd_fields']['district']['required'] ),
					'options'     => Dataset::get_districts_assoc(),
					'class'       => [ 'form-row-wide', 'abb-bd-field' ],
					'priority'    => 65,
					'id'          => $d_key,
				];
				$widths[ $d_key ] = 'full';
			}
			if ( ! empty( $s['bd_fields']['subdistrict']['enabled'] ) ) {
				$fields[ $placement ][ $u_key ] = [
					'type'        => 'select',
					'label'       => sanitize_text_field( $s['bd_fields']['subdistrict']['label'] ),
					'required'    => ! empty( $s['bd_fields']['subdistrict']['required'] ),
					'options'     => [ '' => __( 'Select District first', 'abb-wcfe-bd' ) ],
					'class'       => [ 'form-row-wide', 'abb-bd-field' ],
					'priority'    => 66,
					'id'          => $u_key,
				];
				$widths[ $u_key ] = 'full';
			}
		}

		// ---- Width layout post-process (alternate first/last for half-width fields) ----
		foreach ( ['billing','shipping','order'] as $section ) {
			if ( empty( $fields[ $section ] ) || ! is_array( $fields[ $section ] ) ) continue;

			// sort keys by priority to determine order
			$keys = array_keys( $fields[ $section ] );
			usort( $keys, function($a,$b) use ($fields,$section){
				$pa = intval( $fields[$section][$a]['priority'] ?? 0 );
				$pb = intval( $fields[$section][$b]['priority'] ?? 0 );
				return $pa <=> $pb;
			});

			$half_index = 0;
			foreach ( $keys as $key ) {
				$field =& $fields[ $section ][ $key ];
				$width = $widths[ $key ] ?? 'full';

				// Normalize classes
				$cls = isset( $field['class'] ) && is_array( $field['class'] ) ? $field['class'] : [];
				// remove any prior form-row-* marker
				$cls = array_values( array_filter( $cls, function($c){
					return ! in_array( $c, ['form-row-wide','form-row-first','form-row-last'], true );
				}));

				if ( $width === 'half' ) {
					$cls[] = ( $half_index % 2 === 0 ) ? 'form-row-first' : 'form-row-last';
					$half_index++;
				} else {
					$cls[] = 'form-row-wide';
					// reset half index so next half starts a new row nicely
					if ( $half_index % 2 === 1 ) $half_index++;
				}
				$field['class'] = $cls;
				unset( $field ); // break reference
			}
		}

		return $fields;
	}

	public static function validate_checkout() {
		$s = Admin::get_settings();
		if ( empty( $s['bd_fields']['enabled'] ) ) return;

		$placement = $s['bd_fields']['placement'] === 'shipping' ? 'shipping' : 'billing';
		$d_key = $placement . '_bd_district';
		$u_key = $placement . '_bd_subdistrict';

		$district_enabled    = ! empty( $s['bd_fields']['district']['enabled'] );
		$district_required   = ! empty( $s['bd_fields']['district']['required'] );
		$subdistrict_enabled = ! empty( $s['bd_fields']['subdistrict']['enabled'] );
		$subdistrict_required= ! empty( $s['bd_fields']['subdistrict']['required'] );

		$district  = isset($_POST[ $d_key ]) ? wc_clean( wp_unslash( $_POST[ $d_key ] ) ) : '';
		$upazila   = isset($_POST[ $u_key ]) ? wc_clean( wp_unslash( $_POST[ $u_key ] ) ) : '';

		if ( $district_enabled && $district_required && $district === '' ) {
			wc_add_notice( sprintf( __( '%s is a required field.', 'abb-wcfe-bd' ), esc_html( $s['bd_fields']['district']['label'] ) ), 'error' );
		}
		if ( $subdistrict_enabled && $subdistrict_required && $upazila === '' ) {
			wc_add_notice( sprintf( __( '%s is a required field.', 'abb-wcfe-bd' ), esc_html( $s['bd_fields']['subdistrict']['label'] ) ), 'error' );
		}

		if ( ! empty( $s['bd_fields']['validate_from_list'] ) ) {
			if ( $district_enabled && $district !== '' ) {
				$allowed_d = Dataset::get_districts();
				if ( ! in_array( $district, $allowed_d, true ) ) {
					wc_add_notice( __( 'Invalid District selected.', 'abb-wcfe-bd' ), 'error' );
				}
			}
			if ( $subdistrict_enabled && $upazila !== '' && empty( $s['bd_fields']['allow_custom_subdistrict'] ) ) {
				$allowed_u = Dataset::get_subdistricts( $district );
				if ( ! in_array( $upazila, $allowed_u, true ) ) {
					wc_add_notice( __( 'Invalid Sub-district selected.', 'abb-wcfe-bd' ), 'error' );
				}
			}
		}
	}

	public static function save_order_meta( $order_id, $data ) {
		$s = Admin::get_settings();
		if ( empty( $s['bd_fields']['enabled'] ) ) return;

		$placement = $s['bd_fields']['placement'] === 'shipping' ? 'shipping' : 'billing';
		$d_key = $placement . '_bd_district';
		$u_key = $placement . '_bd_subdistrict';

		$district = isset($_POST[ $d_key ]) ? sanitize_text_field( wp_unslash( $_POST[ $d_key ] ) ) : '';
		$upazila  = isset($_POST[ $u_key ]) ? sanitize_text_field( wp_unslash( $_POST[ $u_key ] ) ) : '';

		if ( $placement === 'billing' ) {
			if ( $district !== '' ) update_post_meta( $order_id, '_billing_bd_district', $district );
			if ( $upazila  !== '' ) update_post_meta( $order_id, '_billing_bd_subdistrict', $upazila );
		} else {
			if ( $district !== '' ) update_post_meta( $order_id, '_shipping_bd_district', $district );
			if ( $upazila  !== '' ) update_post_meta( $order_id, '_shipping_bd_subdistrict', $upazila );
		}
	}

	public static function admin_show_billing( $order ) {
		$district = get_post_meta( $order->get_id(), '_billing_bd_district', true );
		$upazila  = get_post_meta( $order->get_id(), '_billing_bd_subdistrict', true );
		if ( $district || $upazila ) {
			echo '<p><strong>' . esc_html__( 'BD District/Sub-district', 'abb-wcfe-bd' ) . ':</strong> ' . esc_html( trim( $district . ( $upazila ? ' — ' . $upazila : '' ) ) ) . '</p>';
		}
	}

	public static function admin_show_shipping( $order ) {
		$district = get_post_meta( $order->get_id(), '_shipping_bd_district', true );
		$upazila  = get_post_meta( $order->get_id(), '_shipping_bd_subdistrict', true );
		if ( $district || $upazila ) {
			echo '<p><strong>' . esc_html__( 'BD District/Sub-district', 'abb-wcfe-bd' ) . ':</strong> ' . esc_html( trim( $district . ( $upazila ? ' — ' . $upazila : '' ) ) ) . '</p>';
		}
	}
}
