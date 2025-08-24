<?php
/**
 * Plugin Name: Woo Checkout Field Editor (Bangladesh Ready)
 * Description: Toggle WooCommerce checkout fields (show/hide, required/optional) + Bangladesh District & Sub-district dependent dropdowns.
 * Version: 1.0.8
 * Author: absoftlab
 * Author URI: https://absoftlab.com
 * Text Domain: abb-wcfe-bd
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ABB_WCFE_BD_VER', '1.0.8' );
define( 'ABB_WCFE_BD_FILE', __FILE__ );
define( 'ABB_WCFE_BD_DIR', plugin_dir_path( __FILE__ ) );
define( 'ABB_WCFE_BD_URL', plugin_dir_url( __FILE__ ) );

require_once ABB_WCFE_BD_DIR . 'includes/class-dataset.php';
require_once ABB_WCFE_BD_DIR . 'includes/class-admin.php';
require_once ABB_WCFE_BD_DIR . 'includes/class-frontend.php';

register_activation_hook( __FILE__, function() {
	\ABB\WCFE_BD\Admin::install_defaults();
});

add_action( 'plugins_loaded', function() {
	load_plugin_textdomain( 'abb-wcfe-bd', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	\ABB\WCFE_BD\Admin::init();
	\ABB\WCFE_BD\Frontend::init();
});
