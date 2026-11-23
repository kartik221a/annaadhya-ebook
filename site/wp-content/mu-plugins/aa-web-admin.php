<?php
/**
 * Plugin Name: Annaadhya Web Admin (must-use)
 * Description: Custom admin panel at /web-admin — manage books, categories and orders without wp-admin. Ships as a must-use plugin (always active) and routes through template_redirect, so it needs no rewrite rules and no database state: it works identically on every checkout of this repo.
 * Version:     1.4.0
 * Author:      Annaadhya
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'AA_ADMIN_VER', '1.4.0' );
define( 'AA_ADMIN_BASE', '/web-admin' );
// Asset base is built at runtime through home_url() so it follows the incoming
// host (see aa_portable_url()) — WPMU_PLUGIN_URL is a constant frozen before
// mu-plugins load and would keep a stale host on LAN/tunnel deployments.

/** Asset URL inside the panel's assets folder. */
function aa_admin_asset( $file = '' ) {
        $base = untrailingslashit( home_url( '/wp-content/mu-plugins/aa-web-admin/assets' ) );
        return $base . ( $file ? '/' . ltrim( $file, '/' ) : '' );
}

require_once __DIR__ . '/aa-web-admin/auth.php';
require_once __DIR__ . '/aa-web-admin/render.php';
require_once __DIR__ . '/aa-web-admin/dashboard.php';
require_once __DIR__ . '/aa-web-admin/products.php';
require_once __DIR__ . '/aa-web-admin/categories.php';
require_once __DIR__ . '/aa-web-admin/hero.php';
require_once __DIR__ . '/aa-web-admin/orders.php';
require_once __DIR__ . '/aa-web-admin/downloads.php';
require_once __DIR__ . '/aa-web-admin/messages.php';
require_once __DIR__ . '/aa-web-admin/subscribers.php';

/** Panel URL builder: /web-admin, /web-admin/products/5/edit, ... */
function aa_admin_url( $path = '' ) {
        return untrailingslashit( home_url( AA_ADMIN_BASE ) ) . ( $path ? '/' . ltrim( $path, '/' ) : '' );
}

/** Map a ?aa_msg= flash code to a banner. */
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
                'hero_saved'       => array( 'Hero image updated — the home page shows it immediately.', 'ok' ),
                'hero_book_saved'  => array( 'Featured book updated — the home page hero shows it immediately.', 'ok' ),
                'hero_reset'       => array( 'Back to the bundled hero image.', 'ok' ),
                'hero_nofile'      => array( 'Pick an image file first, then click Save.', 'err' ),
                'hero_badfile'     => array( 'That file is not a supported image (use JPG, PNG, WebP or GIF).', 'err' ),
                'hero_toolarge'    => array( 'That image is too large — keep it under 8 MB.', 'err' ),
                'message_deleted'  => array( 'Message deleted.', 'ok' ),
                'message_updated'  => array( 'Message marked as handled.', 'ok' ),
                'subscriber_removed' => array( 'Subscriber removed.', 'ok' ),
                'dl_resent'          => array( 'Delivery email re-sent to the customer.', 'ok' ),
                'dl_failed'          => array( 'Resend failed — check the mail server logs on your host.', 'err' ),
                'error'            => array( 'Something went wrong — check the form and try again.', 'err' ),
                'login_failed'     => array( 'Wrong username or password. Try again.', 'err' ),
                'denied'           => array( 'Action blocked: invalid or expired security token.', 'err' ),
        );
        if ( ! isset( $map[ $code ] ) ) return '';
        list( $text, $type ) = $map[ $code ];
        return '<div class="flash ' . esc_attr( $type ) . '">' . esc_html( $text ) . '</div>';
}

/**
 * Intercept the panel route in template_redirect (priority 1) — this runs
 * before redirect_canonical (10) and before WordPress core's built-in
 * "/admin -> wp-admin" redirect (priority 1000). Routing is done on the raw
 * REQUEST_URI: no rewrite rules, no flush_rewrite_rules(), nothing stored in
 * the database — so the panel works on any checkout of this repo.
 */
add_action( 'template_redirect', 'aa_admin_intercept', 1 );

function aa_admin_intercept() {
        $uri  = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
        $base = trim( parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );

        // Strip a subdirectory install prefix, e.g. /blog/web-admin -> /web-admin.
        if ( '' !== $base ) {
                $prefix = '/' . $base;
                if ( $uri === $prefix || 0 === strpos( $uri, $prefix . '/' ) ) {
                        $uri = substr( $uri, strlen( $prefix ) );
                }
        }

        $path = trim( (string) $uri, '/' );

        // Legacy /admin links: WordPress core otherwise hijacks them to wp-admin.
        // Redirect to the new panel location instead — never wp-admin.
        if ( 'admin' === $path || 0 === strpos( $path, 'admin/' ) ) {
                wp_safe_redirect( aa_admin_url() . '/', 302 );
                exit;
        }

        if ( 'web-admin' !== $path && 0 !== strpos( $path, 'web-admin/' ) ) {
                return;
        }

        aa_admin_panel( $path );
}

/** Authenticate + dispatch the panel. */
function aa_admin_panel( $path ) {
        nocache_headers();
        status_header( 200 ); // WP routed us as a 404 query; the panel is a real page.

        if ( isset( $_POST['aa_login'] ) ) {
                aa_admin_handle_login(); // redirects + exits on every branch
        }

        if ( ! is_user_logged_in() ) {
                aa_admin_login_page();
                exit;
        }

        if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
                wp_die( 'Your account does not have permission to open the Annaadhya admin panel.', 'Annaadhya Admin', array( 'response' => 403 ) );
        }

        $segs    = explode( '/', trim( $path, '/' ) ); // [0] = "web-admin"
        $section = isset( $segs[1] ) ? $segs[1] : '';
        $sub     = isset( $segs[2] ) ? $segs[2] : '';
        $sub2    = isset( $segs[3] ) ? $segs[3] : '';

        switch ( $section ) {
                case '':
                case 'dashboard':
                        aa_admin_dashboard();
                        break;
                case 'products':
                        aa_admin_products_router( $sub, $sub2 );
                        break;
                case 'categories':
                        aa_admin_categories_router( $sub, $sub2 );
                        break;
                case 'hero':
                        aa_admin_hero_router();
                        break;
                case 'orders':
                        aa_admin_orders_router( $sub );
                        break;
                case 'downloads':
                        aa_admin_downloads_router( $sub );
                        break;
                case 'messages':
                        aa_admin_messages_router( $sub );
                        break;
                case 'subscribers':
                        aa_admin_subscribers_router();
                        break;
                default:
                        aa_admin_not_found();
        }

        exit;
}
