<?php
/**
 * Plugin Name: Annaadhya Admin
 * Description: Custom admin panel at /admin — manage books (products), categories and orders without touching wp-admin.
 * Version:     1.0.0
 * Author:      Annaadhya
 * Text Domain: annaadhya-admin
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'AA_ADMIN_VER', '1.0.0' );
define( 'AA_ADMIN_DIR', plugin_dir_path( __FILE__ ) );

require_once AA_ADMIN_DIR . 'includes/render.php';
require_once AA_ADMIN_DIR . 'includes/auth.php';
require_once AA_ADMIN_DIR . 'includes/dashboard.php';
require_once AA_ADMIN_DIR . 'includes/products.php';
require_once AA_ADMIN_DIR . 'includes/categories.php';
require_once AA_ADMIN_DIR . 'includes/orders.php';

/** Rewrite rules for /admin and /admin/<section>/<sub>/<id> */
function aa_admin_rules() {
	add_rewrite_rule( '^admin/?$', 'index.php?aa_admin=dashboard', 'top' );
	add_rewrite_rule( '^admin/([^/]+)/?$', 'index.php?aa_admin=$matches[1]', 'top' );
	add_rewrite_rule( '^admin/([^/]+)/([^/]+)/?$', 'index.php?aa_admin=$matches[1]&aa_sub=$matches[2]', 'top' );
	add_rewrite_rule( '^admin/([^/]+)/([^/]+)/([^/]+)/?$', 'index.php?aa_admin=$matches[1]&aa_sub=$matches[2]&aa_id=$matches[3]', 'top' );
}
add_action( 'init', 'aa_admin_rules' );

register_activation_hook( __FILE__, function () {
	aa_admin_rules();
	flush_rewrite_rules();
} );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'aa_admin';
	$vars[] = 'aa_sub';
	$vars[] = 'aa_id';
	return $vars;
} );

/** Never canonical-redirect our custom /admin routes back to the homepage. */
add_filter( 'redirect_canonical', function ( $url ) {
	if ( get_query_var( 'aa_admin' ) ) return false;
	return $url;
} );

/** Take over rendering for every /admin route. */
add_action( 'template_include', function ( $tpl ) {
	if ( ! get_query_var( 'aa_admin' ) ) return $tpl;
	aa_admin_router();
	exit;
}, 1 );

function aa_admin_router() {
	// Handle the login POST before anything else.
	if ( isset( $_POST['aa_login'] ) ) {
		aa_admin_handle_login();
	}

	if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
		aa_admin_login_page();
		return;
	}

	$section = sanitize_key( get_query_var( 'aa_admin' ) ?: 'dashboard' );

	switch ( $section ) {
		case 'dashboard':
			aa_admin_dashboard();
			break;
		case 'products':
			aa_admin_products_router();
			break;
		case 'categories':
			aa_admin_categories_router();
			break;
		case 'orders':
			aa_admin_orders_router();
			break;
		default:
			aa_admin_not_found();
	}
}

/** Shared admin base URL. */
function aa_admin_url( $path = '' ) {
	return untrailingslashit( home_url( '/admin' ) ) . ( $path ? '/' . ltrim( $path, '/' ) : '' );
}

/** Map a flash code to a banner. */
function aa_admin_flash() {
	if ( empty( $_GET['aa_msg'] ) ) return '';
	$code = sanitize_key( $_GET['aa_msg'] );
	$map  = array(
		'product_created'  => array( 'Book added to the store.', 'ok' ),
		'product_updated'  => array( 'Book updated.', 'ok' ),
		'product_deleted'  => array( 'Book deleted.', 'ok' ),
		'category_created' => array( 'Category created.', 'ok' ),
		'category_updated' => array( 'Category updated.', 'ok' ),
		'category_deleted' => array( 'Category deleted.', 'ok' ),
		'error'            => array( 'Something went wrong — check the form and try again.', 'err' ),
		'login_failed'     => array( 'Wrong username or password. Try again.', 'err' ),
		'denied'           => array( 'Action blocked: invalid or expired security token.', 'err' ),
	);
	if ( ! isset( $map[ $code ] ) ) return '';
	list( $text, $type ) = $map[ $code ];
	return '<div class="flash ' . esc_attr( $type ) . '">' . esc_html( $text ) . '</div>';
}
