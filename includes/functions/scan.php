<?php
/**
 * Scan plugin functionality.
 *
 * @package WpCleanMedia
 */

namespace WpCleanMedia\Scan;

use \WP_Error as WP_Error;
use WpCleanMedia\Core;

/**
 * Default setup routine
 *
 * @return void
 */
function setup() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	// init action.
	add_action( 'init', $n( 'init' ) );

	// scan attachments.
	add_action( 'wp_ajax_wpcm_scan', $n( 'scan_attachments' ) );

	// stop scan.
	add_action( 'wp_ajax_wpcm_stop_scan', $n( 'stop_scan' ) );

	// reset scan.
	add_action( 'wp_ajax_wpcm_reset_scan', $n( 'reset_scan' ) );
}

/**
 * Initializes the plugin and fires an action other plugins can hook into.
 *
 * @return void
 */
function init() {
}

/**
 * Check if an active scan exists
 *
 * @return bool
 */
function is_active() {
	if ( 'yes' === get_option( 'wpcm_scanning', 'no' ) ) {
		return true;
	}

	return false;
}

/**
 * Start scanning
 */
function start_scan() {
	if ( ! Core\user_has_permission() ) {
		return;
	}
	update_option( 'wpcm_scanning', 'yes' );
}

/**
 * End/Pause scan
 */
function stop_scan() {
	if ( ! Core\user_has_permission() ) {
		return;
	}
	update_option( 'wpcm_scanning', 'no' );
}

/**
 * Reset scan
 */
function reset_scan() {
	if ( ! Core\user_has_permission() ) {
		return;
	}
	stop_scan();

	global $wpdb;
	$wpdb->query( $wpdb->prepare( 'delete from ' . $wpdb->postmeta . ' where meta_key = %s', 'wpcm_used' ) ); // phpcs:ignore
}

/**
 * Get quick stats
 *
 * @return array
 */
function get_stats() {
	// get total attachments.
	$args        = array(
		'post_type'      => 'attachment',
		'post_status'    => array( 'inherit', 'private' ),
		'posts_per_page' => 1,
		'fields'         => 'ids',
	);
	$query       = new \WP_Query( $args );
	$total_posts = intval( $query->found_posts );

	// get total parsed.
	$args          = array(
		'post_type'      => 'attachment',
		'post_status'    => array( 'inherit', 'private' ),
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => array( // phpcs:ignore
			array(
				'key'     => 'wpcm_used',
				'compare' => 'EXISTS',
			),
		),
	);
	$query         = new \WP_Query( $args );
	$total_scanned = intval( $query->found_posts );

	return array(
		'total_posts'   => $total_posts,
		'total_scanned' => $total_scanned,
	);
}

/**
 * Scan attachments
 */
function scan_attachments() {
	if ( ! Core\user_has_permission() ) {
		return;
	}
	// check if this is a first request to scan.
	$start_scan = intval( filter_input( INPUT_POST, 'wpcm_initiate_scan', FILTER_SANITIZE_NUMBER_INT ) );
	if ( 1 === $start_scan ) {
		start_scan();
	}

	// check if scanning is active.
	if ( ! is_active() ) {
		return;
	}

	global $wpdb;

	$how_many_to_scan = 50;
	$args             = array(
		'post_type'      => 'attachment',
		'post_status'    => array( 'inherit', 'private' ),
		'posts_per_page' => ( $how_many_to_scan + 1 ),
		'fields'         => 'ids',
		'meta_query'     => array( // phpcs:ignore
			array(
				'key'     => 'wpcm_used',
				'compare' => 'NOT EXISTS',
			),
		),
	);
	$query            = new \WP_Query( $args );
	$posts            = $query->posts;
	if ( $posts ) {
		global $wpdb;
		$i = 1;
		foreach ( $posts as $post_id ) {
			if ( $i > $how_many_to_scan ) {
				break;
			}
			$used = find_file( $post_id );
			update_post_meta( $post_id, 'wpcm_used', $used ? 'yes' : 'no' );
			$i++;
		}
	}

	$next_page = false;
	if ( count( $posts ) > $how_many_to_scan ) {
		$next_page = true;
	}

	// get stats.
	$stats              = get_stats();
	$stats['next_page'] = $next_page;

	header( 'Content-Type: application/json' );
	wp_send_json( $stats );
	die;
}

/**
 * Find file
 *
 * @param int $post_id Attachment ID.
 *
 * @return bool
 */
function find_file( $post_id ) {
	global $wpdb;
	$found = false;

	$full_path = get_attached_file( $post_id );
	$main_file = clean_file_name( $full_path );

	if ( empty( $main_file ) ) {
		return false;
	}

	// check if it's beeing used.
	// check attachment to post.
	$results = $wpdb->get_results( $wpdb->prepare( ' select post_id from ' . $wpdb->postmeta . ' where meta_key = %s and meta_value = %d', '_thumbnail_id', $post_id ) ); // phpcs:ignore
	if ( $results ) {
		$posts_exists = false;
		foreach ( $results as $p ) {
			$posts_exists = get_post( $p->post_id );
			if ( $posts_exists ) {
				$found = true;
				break;
			}
		}
		if ( $posts_exists ) {
			$found = true;
		}
	} else {
		// check site options.
		$results = $wpdb->get_results( $wpdb->prepare( 'select * from ' . $wpdb->options . ' where option_value like %s or option_value = %d', '%' . $main_file . '%', $post_id ) ); // phpcs:ignore
		if ( $results ) {
			$found = true;
		} else {
			// check posts content.
			$file      = pathinfo( $main_file );
			$main_file = $file['dirname'] . '/' . $file['filename'] . '(-[0-9x]+)?.' . $file['extension'];
			$results   = $wpdb->get_results( $wpdb->prepare( 'select ID, post_title from ' . $wpdb->posts . ' where post_type in ( %s, %s, %s ) and post_content regexp %s limit %d', 'post', 'page', 'reusable-content', $main_file, 10 ) ); // phpcs:ignore
			if ( $results ) {
				$found = true;
			}
		}
	}

	return $found;
}

/**
 * From a fullpath to the shortened and cleaned path (for example '2013/02/file.png').
 *
 * @param string $path File path.
 *
 * @return string
 */
function clean_file_name( $path ) {
	$content_dir = substr( wp_upload_dir()['baseurl'], 1 + strlen( get_site_url() ) );
	$dir_index   = strpos( $path, $content_dir );
	if ( false === $dir_index ) {
		$file = $path;
	} else {
		// Remove first part of the path leaving yyyy/mm/filename.ext.
		$file = substr( $path, 1 + strlen( $content_dir ) + $dir_index );
	}
	if ( './' === substr( $file, 0, 2 ) ) {
		$file = substr( $file, 2 );
	}
	if ( '/' === substr( $file, 0, 1 ) ) {
		$file = substr( $file, 1 );
	}
	return $file;
}
