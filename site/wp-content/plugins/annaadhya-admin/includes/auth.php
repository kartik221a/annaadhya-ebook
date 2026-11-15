<?php
/**
 * Login handling for the custom admin panel.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function aa_admin_handle_login() {
	if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'aa_login' ) ) {
		wp_safe_redirect( aa_admin_url( 'login' ) );
		exit;
	}
	$user = wp_signon( array(
		'user_login'    => sanitize_user( wp_unslash( $_POST['aa_user'] ?? '' ) ),
		'user_password' => (string) ( $_POST['aa_pass'] ?? '' ),
		'remember'      => true,
	), false );

	if ( is_wp_error( $user ) ) {
		wp_safe_redirect( aa_admin_url() . '?aa_msg=login_failed' );
		exit;
	}
	wp_safe_redirect( aa_admin_url() );
	exit;
}
