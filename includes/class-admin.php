<?php
namespace ABB\WCFE_BD;

if ( ! defined( 'ABSPATH' ) ) exit;

class Admin {

	const OPTION = 'abb_wcfe_settings_v1';

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'maybe_save' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin' ] );
	}

	public static function install_defaults() {
		if ( ! get_option( self::OPTION ) ) {
			update_option( self::OPTION, self::defaults() );
		}
	}

	public static function enqueue_admin( $hook ) {
		if ( $hook !== 'woocommerce_page_abb-checkout-field-editor-bd' ) return;
		wp_enqueue_style( 'abb-checkout-field-editor-bd-admin', ABB_WCFE_BD_URL . 'assets/css/admin.css', [], ABB_WCFE_BD_VER );
		wp_enqueue_script( 'abb-checkout-field-editor-bd-admin', ABB_WCFE_BD_URL . 'assets/js/admin.js', [], ABB_WCFE_BD_VER, true );
	}

	/** Deep-merge defaults so new keys (like width/state) appear for old installs */
	private static function deep_merge_defaults( array $defaults, array $saved ): array {
		foreach ( $defaults as $k => $v ) {
			if ( ! array_key_exists( $k, $saved ) ) { $saved[ $k ] = $v; continue; }
			if ( is_array( $v ) && is_array( $saved[ $k ] ) ) {
				$saved[ $k ] = self::deep_merge_defaults( $v, $saved[ $k ] );
			}
		}
		return $saved;
	}

	/** Whitelist & sanitize an imported settings array */
	private static function sanitize_import_array( array $maybe ): array {
		$defs = self::defaults();
		$out  = [];

		// core_fields
		if ( isset( $maybe['core_fields'] ) && is_array( $maybe['core_fields'] ) ) {
			$out['core_fields'] = [];
			foreach ( $defs['core_fields'] as $key => $def_cfg ) {
				$src = isset( $maybe['core_fields'][ $key ] ) && is_array( $maybe['core_fields'][ $key ] ) ? $maybe['core_fields'][ $key ] : [];
				$out['core_fields'][ $key ] = [
					'enabled'  => ! empty( $src['enabled'] ),
					'required' => ! empty( $src['required'] ),
					'priority' => isset( $src['priority'] ) ? absint( $src['priority'] ) : ( $def_cfg['priority'] ?? 10 ),
					'label'    => isset( $src['label'] ) ? sanitize_text_field( (string) $src['label'] ) : ( $def_cfg['label'] ?? $key ),
					'width'    => in_array( (string) ( $src['width'] ?? '' ), [ 'half', 'full' ], true ) ? (string) $src['width'] : ( $def_cfg['width'] ?? 'full' ),
				];
			}
		}

		// bd_fields
		if ( isset( $maybe['bd_fields'] ) && is_array( $maybe['bd_fields'] ) ) {
			$b = $maybe['bd_fields'];

			$placement = isset( $b['placement'] ) ? sanitize_key( (string) $b['placement'] ) : 'billing';
			if ( ! in_array( $placement, [ 'billing', 'shipping' ], true ) ) $placement = 'billing';

			$country_condition = isset( $b['country_condition'] ) ? sanitize_key( (string) $b['country_condition'] ) : 'any';
			if ( ! in_array( $country_condition, [ 'any', 'BD_only' ], true ) ) $country_condition = 'any';

			$out['bd_fields'] = [
				'enabled'                 => ! empty( $b['enabled'] ),
				'placement'               => $placement,
				'country_condition'       => $country_condition,
				'allow_custom_subdistrict'=> ! empty( $b['allow_custom_subdistrict'] ),
				'validate_from_list'      => ! empty( $b['validate_from_list'] ),
				'district'                => [
					'enabled'  => ! empty( $b['district']['enabled'] ?? false ),
					'required' => ! empty( $b['district']['required'] ?? false ),
					'label'    => isset( $b['district']['label'] ) ? sanitize_text_field( (string) $b['district']['label'] ) : ( $defs['bd_fields']['district']['label'] ?? __( 'District', 'abb-checkout-field-editor-bd' ) ),
				],
				'subdistrict'             => [
					'enabled'  => ! empty( $b['subdistrict']['enabled'] ?? false ),
					'required' => ! empty( $b['subdistrict']['required'] ?? false ),
					'label'    => isset( $b['subdistrict']['label'] ) ? sanitize_text_field( (string) $b['subdistrict']['label'] ) : ( $defs['bd_fields']['subdistrict']['label'] ?? __( 'Sub-district', 'abb-checkout-field-editor-bd' ) ),
				],
			];
		}

		return $out;
	}

	public static function get_settings(): array {
		$saved    = get_option( self::OPTION, [] );
		$defaults = self::defaults();
		return self::deep_merge_defaults( $defaults, is_array( $saved ) ? $saved : [] );
	}

	public static function defaults(): array {
		return [
			'version'     => 1,
			'core_fields' => [
				// billing
				'billing_first_name' => ['enabled'=>true,  'required'=>true,  'priority'=>10,  'label'=>'First name',    'width'=>'half'],
				'billing_last_name'  => ['enabled'=>true,  'required'=>true,  'priority'=>20,  'label'=>'Last name',     'width'=>'half'],
				'billing_company'    => ['enabled'=>false, 'required'=>false, 'priority'=>30,  'label'=>'Company',       'width'=>'full'],
				'billing_phone'      => ['enabled'=>true,  'required'=>true,  'priority'=>40,  'label'=>'Phone',         'width'=>'full'],
				'billing_email'      => ['enabled'=>true,  'required'=>true,  'priority'=>50,  'label'=>'Email',         'width'=>'full'],
				'billing_address_1'  => ['enabled'=>true,  'required'=>true,  'priority'=>60,  'label'=>'Address 1',     'width'=>'full'],
				'billing_address_2'  => ['enabled'=>true,  'required'=>false, 'priority'=>70,  'label'=>'Address 2',     'width'=>'full'],
				'billing_city'       => ['enabled'=>true,  'required'=>true,  'priority'=>80,  'label'=>'City',          'width'=>'half'],
				'billing_state'      => ['enabled'=>true,  'required'=>false, 'priority'=>85,  'label'=>'State/Province','width'=>'half'],
				'billing_postcode'   => ['enabled'=>true,  'required'=>false, 'priority'=>90,  'label'=>'Postcode',      'width'=>'half'],
				'billing_country'    => ['enabled'=>true,  'required'=>true,  'priority'=>100, 'label'=>'Country',       'width'=>'half'],

				// shipping
				'shipping_first_name'=> ['enabled'=>true,  'required'=>true,  'priority'=>10,  'label'=>'First name',    'width'=>'half'],
				'shipping_last_name' => ['enabled'=>true,  'required'=>true,  'priority'=>20,  'label'=>'Last name',     'width'=>'half'],
				'shipping_company'   => ['enabled'=>false, 'required'=>false, 'priority'=>30,  'label'=>'Company',       'width'=>'full'],
				'shipping_address_1' => ['enabled'=>true,  'required'=>true,  'priority'=>60,  'label'=>'Address 1',     'width'=>'full'],
				'shipping_address_2' => ['enabled'=>true,  'required'=>false, 'priority'=>70,  'label'=>'Address 2',     'width'=>'full'],
				'shipping_city'      => ['enabled'=>true,  'required'=>true,  'priority'=>80,  'label'=>'City',          'width'=>'half'],
				'shipping_state'     => ['enabled'=>true,  'required'=>false, 'priority'=>85,  'label'=>'State/Province','width'=>'half'],
				'shipping_postcode'  => ['enabled'=>true,  'required'=>false, 'priority'=>90,  'label'=>'Postcode',      'width'=>'half'],
				'shipping_country'   => ['enabled'=>true,  'required'=>true,  'priority'=>100, 'label'=>'Country',       'width'=>'half'],

				// order
				'order_comments'     => ['enabled'=>true,  'required'=>false, 'priority'=>110, 'label'=>'Order notes',   'width'=>'full'],
			],
			'bd_fields'   => [
				'enabled'                 => true,
				'placement'               => 'billing', // billing|shipping
				'district'                => ['enabled'=>true, 'required'=>true, 'label'=>__('District','abb-checkout-field-editor-bd')],
				'subdistrict'             => ['enabled'=>true, 'required'=>true, 'label'=>__('Sub-district','abb-checkout-field-editor-bd')],
				'allow_custom_subdistrict'=> false,
				'validate_from_list'      => true,
				'country_condition'       => 'any', // any|BD_only
			],
		];
	}

	public static function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Field Editor (BD)', 'abb-checkout-field-editor-bd' ),
			__( 'Field Editor (BD)', 'abb-checkout-field-editor-bd' ),
			'manage_woocommerce',
			'abb-checkout-field-editor-bd',
			[ __CLASS__, 'render_page' ]
		);
	}

	public static function maybe_save() {
		if ( ! isset( $_POST['abb_wcfe_bd_save'] ) ) return;
		if ( ! current_user_can( 'manage_woocommerce' ) ) return;
		check_admin_referer( 'abb_wcfe_bd_nonce', 'abb_wcfe_bd_nonce' );

		$settings = self::get_settings();

		// ---- Save core fields (unslash + sanitize) ----
		$posted_core = isset( $_POST['core_fields'] ) ? wp_unslash( $_POST['core_fields'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( is_array( $posted_core ) ) {
			foreach ( $settings['core_fields'] as $key => $cfg ) {
				$row = isset( $posted_core[ $key ] ) && is_array( $posted_core[ $key ] ) ? $posted_core[ $key ] : [];

				$width = isset( $row['width'] ) ? sanitize_key( (string) $row['width'] ) : ( $cfg['width'] ?? 'full' );
				if ( ! in_array( $width, [ 'half', 'full' ], true ) ) $width = 'full';

				$settings['core_fields'][ $key ] = [
					'enabled'  => ! empty( $row['enabled'] ),
					'required' => ! empty( $row['required'] ),
					'priority' => isset( $row['priority'] ) ? absint( $row['priority'] ) : ( $cfg['priority'] ?? 10 ),
					'label'    => isset( $row['label'] ) ? sanitize_text_field( (string) $row['label'] ) : ( $cfg['label'] ?? $key ),
					'width'    => $width,
				];
			}
		}

		// ---- BD fields (unslash + sanitize) ----
		$posted_bd = isset( $_POST['bd_fields'] ) ? wp_unslash( $_POST['bd_fields'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$posted_bd = is_array( $posted_bd ) ? $posted_bd : [];

		$settings['bd_fields']['enabled'] = ! empty( $posted_bd['enabled'] );

		$placement = isset( $posted_bd['placement'] ) ? sanitize_key( (string) $posted_bd['placement'] ) : 'billing';
		if ( ! in_array( $placement, [ 'billing', 'shipping' ], true ) ) $placement = 'billing';
		$settings['bd_fields']['placement'] = $placement;

		$country_condition = isset( $posted_bd['country_condition'] ) ? sanitize_key( (string) $posted_bd['country_condition'] ) : 'any';
		if ( ! in_array( $country_condition, [ 'any', 'BD_only' ], true ) ) $country_condition = 'any';
		$settings['bd_fields']['country_condition'] = $country_condition;

		foreach ( [ 'district', 'subdistrict' ] as $k ) {
			$row = isset( $posted_bd[ $k ] ) && is_array( $posted_bd[ $k ] ) ? $posted_bd[ $k ] : [];
			$settings['bd_fields'][ $k ]['enabled']  = ! empty( $row['enabled'] );
			$settings['bd_fields'][ $k ]['required'] = ! empty( $row['required'] );
			if ( isset( $row['label'] ) ) {
				$settings['bd_fields'][ $k ]['label'] = sanitize_text_field( (string) $row['label'] );
			}
		}

		$settings['bd_fields']['allow_custom_subdistrict'] = ! empty( $posted_bd['allow_custom_subdistrict'] );
		$settings['bd_fields']['validate_from_list']       = ! empty( $posted_bd['validate_from_list'] );

		// ---- Optional import (unslash + sanitize + deep merge) ----
		if ( isset( $_POST['import_json'] ) && is_string( $_POST['import_json'] ) ) {
			$raw   = wp_unslash( $_POST['import_json'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$maybe = json_decode( $raw, true );
			if ( is_array( $maybe ) ) {
				$sanitized = self::sanitize_import_array( $maybe );
				$settings  = self::deep_merge_defaults( $settings, $sanitized );
			}
		}

		update_option( self::OPTION, $settings );
		wp_safe_redirect( add_query_arg( [ 'page' => 'abb-checkout-field-editor-bd', 'updated' => '1' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) return;
		$s = self::get_settings();

		// Split + sort
		$billing = []; $shipping = []; $order = [];
		foreach ( $s['core_fields'] as $key => $cfg ) {
			if ( strpos( $key, 'billing_' ) === 0 )      { $billing[ $key ]  = $cfg; }
			elseif ( strpos( $key, 'shipping_' ) === 0 ) { $shipping[ $key ] = $cfg; }
			else                                         { $order[ $key ]    = $cfg; }
		}
		$sorter = fn($a,$b) => intval($a['priority']??0) <=> intval($b['priority']??0);
		uasort($billing,$sorter); uasort($shipping,$sorter); uasort($order,$sorter);

		?>
		<div class="abbwcfe-wrap">
			<header class="abbwcfe-header">
				<div>
					<h1>Woo Checkout Field Editor <span>(Bangladesh Ready)</span></h1>
				</div>
				<a href="https://absoftlab.com" target="_blank" rel="noopener"><img class="abb-bn-logo" src="<?php echo esc_url( ABB_WCFE_BD_URL . 'assets/img/logo.png' ); ?>" alt="<?php esc_attr_e('Brand logo','abb-checkout-field-editor-bd'); ?>" /></a>
			</header>

			<?php
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 'updated' is a benign UI flag (no sensitive action).
			$updated_flag = isset( $_GET['updated'] ) ? sanitize_text_field( wp_unslash( $_GET['updated'] ) ) : '';
			if ( $updated_flag ) :
			?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e('Settings saved.','abb-checkout-field-editor-bd');?></p></div>
			<?php endif; ?>

			<form method="post" class="abbwcfe-form" id="abbwcfe-form">
				<?php wp_nonce_field( 'abb_wcfe_bd_nonce', 'abb_wcfe_bd_nonce' ); ?>

				<nav class="abbwcfe-tabs" role="tablist">
					<button type="button" class="abbwcfe-tab is-active" data-tab="billing"><?php esc_html_e('Billing Fields','abb-checkout-field-editor-bd');?></button>
					<button type="button" class="abbwcfe-tab" data-tab="shipping"><?php esc_html_e('Shipping Fields','abb-checkout-field-editor-bd');?></button>
					<button type="button" class="abbwcfe-tab" data-tab="order"><?php esc_html_e('Order Notes','abb-checkout-field-editor-bd');?></button>
					<button type="button" class="abbwcfe-tab" data-tab="bd"><?php esc_html_e('Bangladesh Fields','abb-checkout-field-editor-bd');?></button>
					<button type="button" class="abbwcfe-tab" data-tab="tools"><?php esc_html_e('Tools','abb-checkout-field-editor-bd');?></button>
				</nav>
				<?php
				$render_grid = function( $section_key, $data ){
					?>
					<section class="abbwcfe-panel <?php echo $section_key==='billing' ? 'is-active' : ''; ?>" data-panel="<?php echo esc_attr($section_key); ?>">
						<div class="abbwcfe-toolbar">
							<input type="search" class="abbwcfe-search" placeholder="<?php esc_attr_e('Search fields…','abb-checkout-field-editor-bd');?>" data-scope="<?php echo esc_attr($section_key); ?>" />
							<div class="toggles">
								<label class="switch">
									<input type="checkbox" class="abbwcfe-opaque-toggle" data-scope="<?php echo esc_attr($section_key); ?>">
									<span class="slider"></span>
								</label>
								<span class="hint"><?php esc_html_e('Dim cards (view only)','abb-checkout-field-editor-bd');?></span>
							</div>
						</div>

						<p class="hint" style="margin:8px 0 12px;"><?php esc_html_e('Drag the cards to reorder fields. Order is saved as priority automatically.','abb-checkout-field-editor-bd');?></p>

						<div class="abbwcfe-grid abbwcfe-sortable" data-priority-step="10" data-scope="<?php echo esc_attr($section_key); ?>">
							<?php foreach ( $data as $key => $cfg ) : ?>
								<div class="abbwcfe-card" data-key="<?php echo esc_attr($key); ?>" draggable="true">
									<div class="card-head">
										<div class="title">
											<span class="drag-handle" title="<?php esc_attr_e('Drag to reorder','abb-checkout-field-editor-bd');?>">⋮⋮</span>
											<strong><?php echo esc_html( $cfg['label'] ?? $key ); ?></strong>
											<code><?php echo esc_html($key); ?></code>
										</div>
										<div class="badge"><?php echo intval($cfg['priority'] ?? 0); ?></div>
									</div>
									<div class="card-body">
										<div class="row">
											<label><?php esc_html_e('Enabled','abb-checkout-field-editor-bd'); ?></label>
											<label class="switch">
												<input type="checkbox" name="core_fields[<?php echo esc_attr($key); ?>][enabled]" <?php checked( ! empty($cfg['enabled']) ); ?>>
												<span class="slider"></span>
											</label>
										</div>
										<div class="row">
											<label><?php esc_html_e('Required','abb-checkout-field-editor-bd'); ?></label>
											<label class="switch">
												<input type="checkbox" name="core_fields[<?php echo esc_attr($key); ?>][required]" <?php checked( ! empty($cfg['required']) ); ?>>
												<span class="slider"></span>
											</label>
										</div>
										<div class="row">
											<label><?php esc_html_e('Priority','abb-checkout-field-editor-bd'); ?></label>
											<input type="number" name="core_fields[<?php echo esc_attr($key); ?>][priority]" value="<?php echo esc_attr( $cfg['priority'] ?? 10 ); ?>" class="input small js-priority">
										</div>
										<div class="row">
											<label><?php esc_html_e('Label (admin only)','abb-checkout-field-editor-bd'); ?></label>
											<input type="text" name="core_fields[<?php echo esc_attr($key); ?>][label]" value="<?php echo esc_attr( $cfg['label'] ?? '' ); ?>" class="input">
										</div>
										<div class="row">
											<label><?php esc_html_e('Width','abb-checkout-field-editor-bd'); ?></label>
											<div class="segmented">
												<label><input type="radio" name="core_fields[<?php echo esc_attr($key); ?>][width]" value="half" <?php checked( ($cfg['width'] ?? 'full'), 'half' ); ?>><span><?php esc_html_e('Half','abb-checkout-field-editor-bd'); ?></span></label>
												<label><input type="radio" name="core_fields[<?php echo esc_attr($key); ?>][width]" value="full" <?php checked( ($cfg['width'] ?? 'full'), 'full' ); ?>><span><?php esc_html_e('Full','abb-checkout-field-editor-bd'); ?></span></label>
											</div>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</section>
					<?php
				};

				$render_grid('billing',  $billing);
				$render_grid('shipping', $shipping);
				?>

				<section class="abbwcfe-panel" data-panel="order">
					<div class="abbwcfe-grid">
						<?php foreach ( $order as $key => $cfg ) : ?>
							<div class="abbwcfe-card" data-key="<?php echo esc_attr($key); ?>">
								<div class="card-head">
									<div class="title">
										<strong><?php echo esc_html( $cfg['label'] ?? $key ); ?></strong>
										<code><?php echo esc_html($key); ?></code>
									</div>
									<div class="badge"><?php echo intval($cfg['priority'] ?? 0); ?></div>
								</div>
								<div class="card-body">
									<div class="row">
										<label><?php esc_html_e('Enabled','abb-checkout-field-editor-bd'); ?></label>
										<label class="switch">
											<input type="checkbox" name="core_fields[<?php echo esc_attr($key); ?>][enabled]" <?php checked( ! empty($cfg['enabled']) ); ?>>
											<span class="slider"></span>
										</label>
									</div>
									<div class="row">
										<label><?php esc_html_e('Required','abb-checkout-field-editor-bd'); ?></label>
										<label class="switch">
											<input type="checkbox" name="core_fields[<?php echo esc_attr($key); ?>][required]" <?php checked( ! empty($cfg['required']) ); ?>>
											<span class="slider"></span>
										</label>
									</div>
									<div class="row">
										<label><?php esc_html_e('Priority','abb-checkout-field-editor-bd'); ?></label>
										<input type="number" name="core_fields[<?php echo esc_attr($key); ?>][priority]" value="<?php echo esc_attr( $cfg['priority'] ?? 10 ); ?>" class="input small js-priority">
									</div>
									<div class="row">
										<label><?php esc_html_e('Label (admin only)','abb-checkout-field-editor-bd'); ?></label>
										<input type="text" name="core_fields[<?php echo esc_attr($key); ?>][label]" value="<?php echo esc_attr( $cfg['label'] ?? '' ); ?>" class="input">
									</div>
									<div class="row">
										<label><?php esc_html_e('Width','abb-checkout-field-editor-bd'); ?></label>
										<div class="segmented">
											<label><input type="radio" name="core_fields[<?php echo esc_attr($key); ?>][width]" value="half" <?php checked( ($cfg['width'] ?? 'full'), 'half' ); ?>><span><?php esc_html_e('Half','abb-checkout-field-editor-bd'); ?></span></label>
											<label><input type="radio" name="core_fields[<?php echo esc_attr($key); ?>][width]" value="full" <?php checked( ($cfg['width'] ?? 'full'), 'full' ); ?>><span><?php esc_html_e('Full','abb-checkout-field-editor-bd'); ?></span></label>
										</div>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</section>

				<section class="abbwcfe-panel" data-panel="bd">
					<div class="abbwcfe-grid grid-2">
						<div class="abbwcfe-card">
							<div class="card-head"><strong><?php esc_html_e('Global Settings','abb-checkout-field-editor-bd');?></strong></div>
							<div class="card-body">
								<div class="row">
									<label><?php esc_html_e('Enable BD fields','abb-checkout-field-editor-bd'); ?></label>
									<label class="switch">
										<input type="checkbox" name="bd_fields[enabled]" <?php checked( ! empty( $s['bd_fields']['enabled'] ) ); ?>>
										<span class="slider"></span>
									</label>
								</div>
								<div class="row">
									<label><?php esc_html_e('Placement','abb-checkout-field-editor-bd'); ?></label>
									<div class="segmented">
										<label><input type="radio" name="bd_fields[placement]" value="billing" <?php checked( $s['bd_fields']['placement'], 'billing' ); ?>><span><?php esc_html_e('Billing','abb-checkout-field-editor-bd'); ?></span></label>
										<label><input type="radio" name="bd_fields[placement]" value="shipping" <?php checked( $s['bd_fields']['placement'], 'shipping' ); ?>><span><?php esc_html_e('Shipping','abb-checkout-field-editor-bd'); ?></span></label>
									</div>
								</div>
								<div class="row">
									<label><?php esc_html_e('Show only if Country = Bangladesh','abb-checkout-field-editor-bd'); ?></label>
									<div class="segmented">
										<label><input type="radio" name="bd_fields[country_condition]" value="any" <?php checked( $s['bd_fields']['country_condition'], 'any' ); ?>><span><?php esc_html_e('No (Always show)','abb-checkout-field-editor-bd'); ?></span></label>
										<label><input type="radio" name="bd_fields[country_condition]" value="BD_only" <?php checked( $s['bd_fields']['country_condition'], 'BD_only' ); ?>><span><?php esc_html_e('Yes (BD only)','abb-checkout-field-editor-bd'); ?></span></label>
									</div>
								</div>
							</div>
						</div>

						<div class="abbwcfe-card">
							<div class="card-head"><strong><?php esc_html_e('Validation & Behavior','abb-checkout-field-editor-bd');?></strong></div>
							<div class="card-body">
								<div class="row">
									<label><?php esc_html_e('Validate against dataset','abb-checkout-field-editor-bd'); ?></label>
									<label class="switch">
										<input type="checkbox" name="bd_fields[validate_from_list]" <?php checked( ! empty( $s['bd_fields']['validate_from_list'] ) ); ?>>
										<span class="slider"></span>
									</label>
								</div>
								<div class="row">
									<label><?php esc_html_e('Allow custom Sub-district','abb-checkout-field-editor-bd'); ?></label>
									<label class="switch">
										<input type="checkbox" name="bd_fields[allow_custom_subdistrict]" <?php checked( ! empty( $s['bd_fields']['allow_custom_subdistrict'] ) ); ?>>
										<span class="slider"></span>
									</label>
								</div>
							</div>
						</div>

						<div class="abbwcfe-card">
							<div class="card-head"><strong><?php esc_html_e('District Field','abb-checkout-field-editor-bd');?></strong></div>
							<div class="card-body">
								<div class="row">
									<label><?php esc_html_e('Enabled','abb-checkout-field-editor-bd'); ?></label>
									<label class="switch">
										<input type="checkbox" name="bd_fields[district][enabled]" <?php checked( ! empty( $s['bd_fields']['district']['enabled'] ) ); ?>>
										<span class="slider"></span>
									</label>
								</div>
								<div class="row">
									<label><?php esc_html_e('Required','abb-checkout-field-editor-bd'); ?></label>
									<label class="switch">
										<input type="checkbox" name="bd_fields[district][required]" <?php checked( ! empty( $s['bd_fields']['district']['required'] ) ); ?>>
										<span class="slider"></span>
									</label>
								</div>
								<div class="row">
									<label><?php esc_html_e('Label','abb-checkout-field-editor-bd'); ?></label>
									<input type="text" class="input" name="bd_fields[district][label]" value="<?php echo esc_attr( $s['bd_fields']['district']['label'] ); ?>">
								</div>
							</div>
						</div>

						<div class="abbwcfe-card">
							<div class="card-head"><strong><?php esc_html_e('Sub-district Field','abb-checkout-field-editor-bd');?></strong></div>
							<div class="card-body">
								<div class="row">
									<label><?php esc_html_e('Enabled','abb-checkout-field-editor-bd'); ?></label>
									<label class="switch">
										<input type="checkbox" name="bd_fields[subdistrict][enabled]" <?php checked( ! empty( $s['bd_fields']['subdistrict']['enabled'] ) ); ?>>
										<span class="slider"></span>
									</label>
								</div>
								<div class="row">
									<label><?php esc_html_e('Required','abb-checkout-field-editor-bd'); ?></label>
									<label class="switch">
										<input type="checkbox" name="bd_fields[subdistrict][required]" <?php checked( ! empty( $s['bd_fields']['subdistrict']['required'] ) ); ?>>
										<span class="slider"></span>
									</label>
								</div>
								<div class="row">
									<label><?php esc_html_e('Label','abb-checkout-field-editor-bd'); ?></label>
									<input type="text" class="input" name="bd_fields[subdistrict][label]" value="<?php echo esc_attr( $s['bd_fields']['subdistrict']['label'] ); ?>">
								</div>
							</div>
						</div>
					</div>
				</section>

				<section class="abbwcfe-panel" data-panel="tools">
					<div class="abbwcfe-card">
						<div class="card-head"><strong><?php esc_html_e('Export Settings','abb-checkout-field-editor-bd');?></strong></div>
						<div class="card-body">
							<textarea readonly rows="8" class="input codeblock"><?php echo esc_textarea( wp_json_encode( $s, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></textarea>
						</div>
					</div>
					<div class="abbwcfe-card">
						<div class="card-head"><strong><?php esc_html_e('Import Settings','abb-checkout-field-editor-bd');?></strong></div>
						<div class="card-body">
							<textarea name="import_json" rows="8" class="input" placeholder="<?php esc_attr_e('Paste settings JSON and Save Changes…','abb-checkout-field-editor-bd');?>"></textarea>
						</div>
					</div>
				</section>

				<footer class="abbwcfe-footer">
					<button type="submit" class="button button-primary" name="abb_wcfe_bd_save" value="1"><?php esc_html_e('Save Changes','abb-checkout-field-editor-bd'); ?></button>
				</footer>
			</form>
		</div>
		<?php
	}
}
