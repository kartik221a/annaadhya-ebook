<?php
/**
 * Router for running the site without Docker, using PHP's built-in server.
 *
 * Usage from the repo root:
 *   php -S 127.0.0.1:8080 -t site router.php
 * Then open http://127.0.0.1:8080
 */

$root = __DIR__ . '/site';
$path = urldecode( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
$file = $root . $path;

// Serve existing non-PHP files (images, CSS, JS, downloads) directly.
if ( $path !== '/' && $path !== '' && file_exists( $file ) && ! is_dir( $file )
	&& pathinfo( $file, PATHINFO_EXTENSION ) !== 'php' ) {
	return false;
}

// Execute requested PHP files directly (wp-login.php, wp-cron.php, ...).
if ( $path !== '/' && $path !== '' && file_exists( $file ) && ! is_dir( $file )
	&& pathinfo( $file, PATHINFO_EXTENSION ) === 'php' ) {
	chdir( dirname( $file ) );
	require $file;
	return true;
}

// Directory URLs resolve to their index.php (e.g. /wp-admin/ -> wp-admin/index.php).
if ( $path !== '/' && $path !== '' && is_dir( $file ) && file_exists( $file . '/index.php' ) ) {
	chdir( $file );
	require $file . '/index.php';
	return true;
}

// Everything else goes through the WordPress front controller.
chdir( $root );
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $root . '/index.php';
