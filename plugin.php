<?php
/**
 * Plugin Name: WP Clean Media
 * Plugin URI: https://github.com/eflorea/wp-clean-media
 * Description: A small WP plugin to clean media files
 * Version:     1.0.3
 * Author:      Eduard Florea
 * Author URI:  https://florea.com
 * Text Domain: wp-clean-media
 * Domain Path: /languages
 *
 * @package WpCleanMedia
 */

// Useful global constants.
define( 'WP_CLEAN_MEDIA_VERSION', '1.0.3' );
define( 'WP_CLEAN_MEDIA_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_CLEAN_MEDIA_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_CLEAN_MEDIA_INC', WP_CLEAN_MEDIA_PATH . 'includes/' );

// Include files.
require_once WP_CLEAN_MEDIA_INC . 'functions/core.php';
require_once WP_CLEAN_MEDIA_INC . 'functions/scan.php';

// Activation/Deactivation.
register_activation_hook( __FILE__, '\WpCleanMedia\Core\activate' );
register_deactivation_hook( __FILE__, '\WpCleanMedia\Core\deactivate' );

// Bootstrap.
WpCleanMedia\Core\setup();
WpCleanMedia\Scan\setup();

// Require Composer autoloader if it exists.
if ( file_exists( WP_CLEAN_MEDIA_PATH . '/vendor/autoload.php' ) ) {
	require_once WP_CLEAN_MEDIA_PATH . 'vendor/autoload.php';
}
