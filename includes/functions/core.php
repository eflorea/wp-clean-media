<?php
/**
 * Core plugin functionality.
 *
 * @package WpCleanMedia
 */

namespace WpCleanMedia\Core;

use \WP_Error as WP_Error;

/**
 * Default setup routine
 *
 * @return void
 */
function setup() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	// enqueue admin scripts.
	add_action( 'admin_enqueue_scripts', $n( 'admin_scripts' ) );

	// add link to admin menu.
	add_action( 'admin_menu', $n( 'admin_menu' ) );

	// pre filter posts to show only unused files.
	add_action( 'pre_get_posts', $n( 'filter_posts' ) );
}

/**
 * Filter posts
 *
 * @param object $query Current query.
 *
 * @return object
 */
function filter_posts( $query ) {
	if ( is_admin() && $query->is_main_query() ) {
		$attachment_filter = filter_input( INPUT_GET, 'attachment-filter', FILTER_SANITIZE_STRING );
		if ( 'unused' === $attachment_filter ) {
			$meta_query         = array();
			$meta_query[]       = array(
				'key'     => 'wpcm_used',
				'value'   => 'no',
				'compare' => '=',
			);
			$current_meta_query = $query->meta_query;
			if ( $current_meta_query ) {
				$meta_query = array_merge( $current_meta_query, $meta_query );
			}
			$query->set( 'meta_query', $meta_query );
		}
	}
	return $query;
}

/**
 * Add Link to Admin Menu
 *
 * @return void
 */
function admin_menu() {
	add_media_page(
		__( 'WP Clean Media', 'wpcm' ),
		__( 'Clean Files', 'wpcm' ),
		'delete_others_pages',
		'wp-clean-media',
		__NAMESPACE__ . '\clean_media_screen'
	);
};

/**
 * Check permissions.
 */
function user_has_permission() {
	return current_user_can( 'delete_others_pages' );
}

/**
 * Clean Media Screen
 */
function clean_media_screen() {
	// check permissions.
	if ( ! user_has_permission() ) {
		return wp_die( 'Permission denied.' );
	}
	$stats = \WpCleanMedia\Scan\get_stats();
	wp_localize_script(
		'wp_clean_media_admin',
		'wpcm_is_running',
		\WpCleanMedia\Scan\is_active() ? '1' : '0'
	);
	$scanning_active = \WpCleanMedia\Scan\is_active();
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'WP Clean Media', 'wpcm' ); ?></h1>
		<p><?php echo esc_html__( 'In this section you will be able to scan the entire site for files that are not being used. Please use this plugin with caution.', 'wpcm' ); ?></p>
		<h2><?php echo esc_html__( 'Quick stats:', 'wpcm' ); ?></h2>
		<div id="dashboard-widgets">
			<div class="postbox-container">
				<div class="postbox">
					<div class="inside">
						<div class="main">
							<ul>
								<li><?php echo esc_html__( 'Total Attachments:', 'wpcm' ); ?> <span id="wpcm-total-attachments"><?php echo esc_html( $stats['total_posts'] ); ?></span></li>
								<li><?php echo esc_html__( 'Scanned', 'wpcm' ); ?> <span id="wpcm-total-scanned"><?php echo esc_html( $stats['total_scanned'] ); ?></span></li>
							</ul>
							<div id="wpcm-scanning" <?php if ( ! $scanning_active ) : ?>class="hide"<?php endif; // phpcs:ignore ?>>
								<span class="spinner is-active"></span> <?php echo esc_html__( 'Scan in progress. The scan ONLY runs as long as this page is opened.', 'wpcm' ); ?>
							</div>
							<button type="button" class="button button-primary <?php if ( $scanning_active ) : ?>hide<?php endif; // phpcs:ignore ?>" id="wpcm-scan-now"><?php echo esc_html__( 'Scan Now', 'wpcm' ); ?></button>
							<button type="button" class="button button-primary <?php if ( ! $scanning_active ) : ?>hide<?php endif; // phpcs:ignore ?>" id="wpcm-stop-now"><?php echo esc_html__( 'Stop Scan', 'wpcm' ); ?></button>
							<button type="button" class="button" id="wpcm-reset-now"><?php echo esc_html__( 'Reset Data', 'wpcm' ); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Activate the plugin
 *
 * @return void
 */
function activate() {
}

/**
 * Deactivate the plugin
 *
 * Uninstall routines should be in uninstall.php
 *
 * @return void
 */
function deactivate() {
}

/**
 * Enqueue scripts for admin.
 *
 * @return void
 */
function admin_scripts() {

	wp_enqueue_script(
		'wp_clean_media_admin',
		WP_CLEAN_MEDIA_URL . 'assets/js/admin.js',
		array(),
		WP_CLEAN_MEDIA_VERSION,
		true
	);

	wp_enqueue_style(
		'wp_clean_media_admin',
		WP_CLEAN_MEDIA_URL . 'assets/css/admin-style.css',
		array(),
		WP_CLEAN_MEDIA_VERSION
	);

}
