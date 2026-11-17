<?php
/**
 * Plugin Name: Annaadhya Firebase Auth (must-use)
 * Description: Firebase Authentication as the store's identity provider — "Continue with Google"
 *              (Firebase's own OAuth client: NO Google Cloud Console work) plus email/password
 *              with Firebase-powered verification mails (no SMTP server needed). The browser
 *              hands a Firebase ID token to admin-ajax; this plugin verifies it server-side
 *              (RS256 signature against Google's public certs + all Firebase claims), then
 *              creates or logs in the matching WordPress user and syncs the email-verified
 *              flag that gates ebook downloads.
 *
 *   wp-config.php constants:
 *     define( 'AA_FB_API_KEY', 'AIza…' );        // Firebase Web API key (public by design)
 *     define( 'AA_FB_PROJECT_ID', 'annaadhya' ); // Firebase project id
 *     optional: define( 'AA_FB_AUTH_DOMAIN', '<project>.firebaseapp.com' );
 *
 *   Free-tier facts (Firebase Spark plan): email/password + Google sign-in have no per-user
 *   charge up to 50,000 monthly actives; Firebase itself sends the verification and password
 *   reset mails (1,000/day free) — no SMTP, no sender verification, no Google Cloud Console.
 *   Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ------------------------------------------------------------------------
 * 1. Config
 * ---------------------------------------------------------------------- */

function aa_fb_active() {
	return defined( 'AA_FB_API_KEY' ) && AA_FB_API_KEY && defined( 'AA_FB_PROJECT_ID' ) && AA_FB_PROJECT_ID;
}

/** Public values the browser JS needs (Firebase web API keys are public). */
function aa_fb_js_config() {
	if ( ! aa_fb_active() ) { return array(); }
	return array(
		'apiKey'     => AA_FB_API_KEY,
		'projectId'  => AA_FB_PROJECT_ID,
		'authDomain' => defined( 'AA_FB_AUTH_DOMAIN' ) && AA_FB_AUTH_DOMAIN
			? AA_FB_AUTH_DOMAIN
			: AA_FB_PROJECT_ID . '.firebaseapp.com',
	);
}

/* ------------------------------------------------------------------------
 * 2. ID-token verification (RS256 JWT from https://securetoken.google.com/…).
 *    Signature is checked against Google's rotating x509 certs; every Firebase
 *    claim (iss, aud, exp, iat, auth_time, sub, email) is enforced.
 * ---------------------------------------------------------------------- */

function aa_fb_b64url_decode( $s ) {
	return base64_decode( strtr( (string) $s, '-_', '+/' ) . str_repeat( '=', ( 4 - strlen( $s ) % 4 ) % 4 ) );
}

/** kid => PEM public key/cert. Filterable for tests; cached 1h otherwise. */
function aa_fb_public_keys( $force_refresh = false ) {
	$filtered = apply_filters( 'aa_fb_public_keys', null );
	if ( is_array( $filtered ) && ! empty( $filtered ) ) { return $filtered; }

	if ( ! $force_refresh ) {
		$cache = get_transient( 'aa_fb_public_keys' );
		if ( is_array( $cache ) && ! empty( $cache ) ) { return $cache; }
	}

	$res = wp_remote_get(
		'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com',
		array( 'timeout' => 12 )
	);
	$keys = ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) )
		? array()
		: json_decode( wp_remote_retrieve_body( $res ), true );

	if ( is_array( $keys ) && ! empty( $keys ) ) {
		set_transient( 'aa_fb_public_keys', $keys, HOUR_IN_SECONDS );
		return $keys;
	}
	return $cache && is_array( $cache ) ? $cache : false;
}

/**
 * Verify a Firebase ID token. Returns the claims array on success, false on
 * failure (with $err set to a short reason).
 */
function aa_fb_verify_id_token( $token, &$err = '' ) {
	$err = '';
	if ( ! aa_fb_active() ) { $err = 'not-configured'; return false; }
	if ( ! is_string( $token ) || substr_count( $token, '.' ) !== 2 ) { $err = 'format'; return false; }

	list( $h_b64, $p_b64, $s_b64 ) = explode( '.', $token );
	$header = json_decode( aa_fb_b64url_decode( $h_b64 ), true );
	$claims = json_decode( aa_fb_b64url_decode( $p_b64 ), true );
	if ( ! is_array( $header ) || ! is_array( $claims ) ) { $err = 'format'; return false; }

	$alg = isset( $header['alg'] ) ? $header['alg'] : '';
	$kid = isset( $header['kid'] ) ? $header['kid'] : '';
	if ( 'RS256' !== $alg || '' === $kid ) { $err = 'alg'; return false; }

	$keys = aa_fb_public_keys();
	if ( ! is_array( $keys ) || ! isset( $keys[ $kid ] ) ) {
		// Google rotates keys — unknown kid usually means a stale cache.
		$keys = aa_fb_public_keys( true );
		if ( ! is_array( $keys ) || ! isset( $keys[ $kid ] ) ) { $err = 'keys'; return false; }
	}

	$ok = openssl_verify( $h_b64 . '.' . $p_b64, aa_fb_b64url_decode( $s_b64 ), $keys[ $kid ], OPENSSL_ALGO_SHA256 );
	if ( 1 !== $ok ) { $err = 'signature'; return false; }

	$now = time();
	if ( ( isset( $claims['exp'] ) ? (int) $claims['exp'] : 0 ) < $now - 300 ) { $err = 'expired'; return false; }
	if ( ( isset( $claims['iat'] ) ? (int) $claims['iat'] : 0 ) > $now + 300 ) { $err = 'future'; return false; }
	if ( ( isset( $claims['auth_time'] ) ? (int) $claims['auth_time'] : 0 ) > $now + 300 ) { $err = 'future'; return false; }

	if ( ( isset( $claims['iss'] ) ? $claims['iss'] : '' ) !== 'https://securetoken.google.com/' . AA_FB_PROJECT_ID ) { $err = 'iss'; return false; }
	if ( ( isset( $claims['aud'] ) ? $claims['aud'] : '' ) !== AA_FB_PROJECT_ID ) { $err = 'aud'; return false; }
	if ( empty( $claims['sub'] ) || ! is_string( $claims['sub'] ) ) { $err = 'sub'; return false; }
	if ( empty( $claims['email'] ) || ! is_email( $claims['email'] ) ) { $err = 'email'; return false; }

	return $claims;
}

/* ------------------------------------------------------------------------
 * 3. Handoff endpoint: browser posts the verified Firebase ID token here.
 * ---------------------------------------------------------------------- */

add_action( 'wp_ajax_aa_fb_login', 'aa_fb_login_ajax' );
add_action( 'wp_ajax_nopriv_aa_fb_login', 'aa_fb_login_ajax' );

function aa_fb_login_ajax() {
	if ( ! aa_fb_active() ) {
		wp_send_json( array( 'status' => 'error', 'msg' => 'Firebase sign-in is not configured on this server yet.' ), 400 );
	}
	if ( ! check_ajax_referer( 'aa_auth', 'nonce', false ) ) {
		wp_send_json( array( 'status' => 'error', 'msg' => 'Security check failed — reload the page and try again.' ), 403 );
	}

	$err    = '';
	$token  = isset( $_POST['idtoken'] ) ? (string) wp_unslash( $_POST['idtoken'] ) : '';
	$claims = aa_fb_verify_id_token( $token, $err );
	if ( ! $claims ) {
		wp_send_json( array( 'status' => 'error', 'msg' => 'Sign-in could not be verified — please try again.' ), 401 );
	}

	$email    = strtolower( $claims['email'] );
	$verified = ! empty( $claims['email_verified'] );

	// Never silently switch accounts: if a different reader is logged in, stop.
	$cur = get_current_user_id();
	if ( $cur ) {
		$cu = get_userdata( $cur );
		if ( $cu && strtolower( $cu->user_email ) !== $email ) {
			wp_send_json( array( 'status' => 'error', 'msg' => 'You are signed in with a different account — log out first.' ), 409 );
		}
	}

	$user = get_user_by( 'email', $email );
	if ( ! $user ) {
		$base = sanitize_user( strtolower( current( explode( '@', $email ) ) ), true );
		if ( '' === $base || ! validate_username( $base ) ) { $base = 'reader'; }
		$username = $base; $i = 1;
		while ( username_exists( $username ) ) { $i++; $username = $base . $i; }

		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		if ( '' === $name && ! empty( $claims['name'] ) ) { $name = sanitize_text_field( (string) $claims['name'] ); }

		$uid = wp_insert_user( array(
			'user_login'   => $username,
			'user_email'   => $email,
			'user_pass'    => wp_generate_password( 24, true, true ),
			'display_name' => $name ? $name : $base,
			'role'         => class_exists( 'WooCommerce' ) ? 'customer' : 'subscriber',
		) );
		if ( is_wp_error( $uid ) ) {
			wp_send_json( array( 'status' => 'error', 'msg' => 'Could not create the account — please try again.' ), 500 );
		}
		$user = get_userdata( $uid );
	} elseif ( ! empty( $_POST['name'] ) ) {
		$name = sanitize_text_field( wp_unslash( $_POST['name'] ) );
		if ( $name && $user->display_name !== $name ) {
			wp_update_user( array( 'ID' => $user->ID, 'display_name' => $name ) );
		}
	}

	update_user_meta( $user->ID, '_aa_fb_uid', $claims['sub'] );
	update_user_meta( $user->ID, '_aa_email_verified', $verified ? '1' : '0' );
	if ( $verified ) {
		update_user_meta( $user->ID, '_aa_oauth_verified', '1' );
	}

	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, true );

	wp_send_json( array(
		'status'   => 'ok',
		'verified' => $verified,
		'email'    => $email,
		'redirect' => home_url( '/library/' ),
	) );
}
