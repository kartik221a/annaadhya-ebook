<?php
/**
 * Plugin Name: Annaadhya Auth Plus (must-use)
 * Description: Email verification for email/password sign-up (confirmation mail, resend, downloads gated until verified), a constants-driven Google/OIDC sign-in that needs NO Google Cloud Console (works with Google's free OAuth client or any OIDC provider such as Auth0's free tier), SMTP through wp-config constants, and a branded dark/gold wp-login.php.
 * Version:     1.0.0
 * Author:      Annaadhya
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ------------------------------------------------------------------------
 * 1. SMTP from wp-config constants (works with any free provider).
 *    define( 'AA_SMTP_HOST', 'smtp-relay.brevo.com' );
 *    define( 'AA_SMTP_PORT', 587 );
 *    define( 'AA_SMTP_USER', '...' );  define( 'AA_SMTP_PASS', '...' );
 *    define( 'AA_SMTP_SECURE', 'tls' );            // '' | 'ssl' | 'tls'
 *    define( 'AA_SMTP_FROM', 'store@yourdomain.com' );
 *    define( 'AA_SMTP_FROM_NAME', 'Annaadhya Ebooks' );
 * ---------------------------------------------------------------------- */

function aa_smtp_configured() {
        return defined( 'AA_SMTP_HOST' ) && AA_SMTP_HOST && defined( 'AA_SMTP_USER' ) && AA_SMTP_USER;
}

/* PHPMailer 7.x (bundled since WP 6.9) validates the From address strictly
 * inside wp_mail()'s own setFrom() call — which happens BEFORE the
 * phpmailer_init hook. A localhost default From (wordpress@localhost) is
 * rejected there and wp_mail() returns false before any SMTP transport is
 * applied. The canonical wp_mail_from filters run earlier, so the branded,
 * valid From from the AA_SMTP_* constants always wins. */
add_filter( 'wp_mail_from', 'aa_wp_mail_from' );
add_filter( 'wp_mail_from_name', 'aa_wp_mail_from_name' );

function aa_wp_mail_from( $from ) {
        if ( aa_smtp_configured() ) {
                return defined( 'AA_SMTP_FROM' ) && AA_SMTP_FROM ? AA_SMTP_FROM : AA_SMTP_USER;
        }
        return $from;
}

function aa_wp_mail_from_name( $name ) {
        if ( aa_smtp_configured() ) {
                return defined( 'AA_SMTP_FROM_NAME' ) && AA_SMTP_FROM_NAME ? AA_SMTP_FROM_NAME : 'Annaadhya Ebooks';
        }
        return $name;
}

add_action( 'phpmailer_init', 'aa_smtp_phpmailer' );

function aa_smtp_phpmailer( $phpmailer ) {
        if ( ! aa_smtp_configured() ) { return; }
        $phpmailer->isSMTP();
        $phpmailer->Host       = AA_SMTP_HOST;
        $phpmailer->Port       = defined( 'AA_SMTP_PORT' ) ? (int) AA_SMTP_PORT : 587;
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Username   = AA_SMTP_USER;
        $phpmailer->Password   = defined( 'AA_SMTP_PASS' ) ? AA_SMTP_PASS : '';
        if ( defined( 'AA_SMTP_SECURE' ) && '' !== AA_SMTP_SECURE ) {
                $phpmailer->SMTPSecure = AA_SMTP_SECURE;
        }
}

/* ------------------------------------------------------------------------
 * 2. Email verification state helpers.
 * ---------------------------------------------------------------------- */

function aa_email_is_verified( $user_id ) {
        $u = get_userdata( $user_id );
        if ( ! $u ) { return false; }
        // OAuth-verified providers (Google/SSO) prove email ownership upstream.
        if ( get_user_meta( $user_id, '_aa_oauth_verified', true ) ) { return true; }
        return '1' === (string) get_user_meta( $user_id, '_aa_email_verified', true );
}

function aa_email_verify_url( $user_id, $key ) {
        return add_query_arg(
                array( 'uid' => (int) $user_id, 'key' => $key ),
                home_url( '/verify-email/' )
        );
}

/** Send the confirmation mail (branded, dark/gold like the store). Returns wp_mail(). */
function aa_email_send_verification( $user_id ) {
        $user = get_userdata( $user_id );
        if ( ! $user ) { return false; }

        $key = wp_generate_password( 48, false, false );
        update_user_meta( $user_id, '_aa_email_verify_key', $key );
        update_user_meta( $user_id, '_aa_email_verify_sent', time() );

        $link = aa_email_verify_url( $user_id, $key );
        $name = $user->display_name ? $user->display_name : $user->user_login;

        $body = '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#0a0e13;font-family:Segoe UI,Helvetica,Arial,sans-serif;">'
                . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0a0e13;padding:36px 12px;"><tr><td align="center">'
                . '<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">'
                . '<tr><td style="padding:0 0 22px 8px;"><span style="display:inline-block;background:#1c2025;border:1px solid rgba(160,142,122,.4);color:#f59e0b;font-weight:700;font-size:15px;border-radius:10px;padding:9px 14px;">Annaadhya Ebooks</span></td></tr>'
                . '<tr><td style="background:#101419;border:1px solid rgba(160,142,122,.25);border-radius:14px;padding:34px;">'
                . '<h1 style="margin:0 0 14px;color:#e0e2ea;font-size:22px;">Confirm your email, ' . esc_html( $name ) . '</h1>'
                . '<p style="margin:0 0 18px;color:#d8c3ad;font-size:15px;line-height:1.7;">Welcome to Annaadhya! One tap below verifies this address and unlocks your ebook downloads &mdash; every purchase keeps 5 full PDF downloads in your account, forever.</p>'
                . '<p style="margin:0 0 26px;"><a href="' . esc_url( $link ) . '" style="background:#f59e0b;color:#472a00;font-weight:700;font-size:15px;text-decoration:none;border-radius:10px;padding:14px 26px;display:inline-block;">Verify my email</a></p>'
                . '<p style="margin:0;color:#8b8fa3;font-size:12.5px;line-height:1.7;">This link works once and is meant just for you. If you did not create an Annaadhya account you can ignore this mail.<br>Button not clicking? Paste this into your browser:<br><span style="color:#b9c8dc;word-break:break-all;">' . esc_html( $link ) . '</span></p>'
                . '</td></tr>'
                . '<tr><td style="padding:18px 8px;color:#8b8fa3;font-size:11.5px;">&copy; ' . esc_html( gmdate( 'Y' ) ) . ' Annaadhya Ebooks &middot; Instant PDF delivery for Indian readers</td></tr>'
                . '</table></td></tr></table></body></html>';

        add_filter( 'wp_mail_content_type', 'aa_email_html_type' );
        $ok = wp_mail( $user->user_email, 'Confirm your email — Annaadhya Ebooks', $body );
        remove_filter( 'wp_mail_content_type', 'aa_email_html_type' );
        return $ok;
}

function aa_email_html_type() { return 'text/html'; }

/* ------------------------------------------------------------------------
 * 3. /verify-email/ route — marks the account verified, signs the reader in,
 *    and shows a branded success screen.
 * ---------------------------------------------------------------------- */

add_action( 'template_redirect', 'aa_verify_email_route', 0 );

function aa_verify_email_route() {
        $path = trim( (string) parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
        if ( 'verify-email' !== $path ) { return; }

        status_header( 200 ); // WP routed us as a 404 query; this is a real screen.
        nocache_headers();
        $uid = isset( $_GET['uid'] ) ? absint( $_GET['uid'] ) : 0;
        $key = isset( $_GET['key'] ) ? (string) wp_unslash( $_GET['key'] ) : '';
        $u   = $uid ? get_userdata( $uid ) : null;
        $stored = $u ? (string) get_user_meta( $uid, '_aa_email_verify_key', true ) : '';

        if ( ! $u || '' === $key || '' === $stored || ! hash_equals( $stored, $key ) ) {
                aa_auth_screen(
                        'Link expired or invalid',
                        'That verification link is not valid anymore. Open My Account and hit “Resend confirmation email” for a fresh one.',
                        array( 'My Account' => home_url( '/my-account/' ), 'Back to store' => home_url( '/shop/' ) ),
                        'error'
                );
                exit;
        }

        update_user_meta( $uid, '_aa_email_verified', '1' );
        delete_user_meta( $uid, '_aa_email_verify_key' );

        if ( 0 === get_current_user_id() || get_current_user_id() !== $uid ) {
                wp_set_current_user( $uid );
                wp_set_auth_cookie( $uid, true );
        }

        aa_auth_screen(
                "Email verified — you're in",
                'Your address is confirmed. Downloads in My Library are unlocked, and every book you buy stays in your account forever with 5 full PDF downloads.',
                array( 'Open My Library' => home_url( '/library/' ), 'Browse the store' => home_url( '/shop/' ) ),
                'check'
        );
        exit;
}

/** Branded dark/gold message screen (shared by verify + oauth flows). */
function aa_auth_screen( $title, $msg, $buttons, $icon = 'check' ) {
        $btns = '';
        $i    = 0;
        foreach ( $buttons as $label => $url ) {
                $class = 0 === $i ? 'aa-btn-gold' : 'aa-btn-ghost';
                $btns .= '<a class="' . $class . '" href="' . esc_url( $url ) . '"><span>' . esc_html( $label ) . '</span></a>';
                $i++;
        }
        $glyph = 'check' === $icon ? 'check_circle' : 'cancel';
        $color = 'check' === $icon ? '#f59e0b' : '#ff8a80';
        echo '<!DOCTYPE html><html ';
        language_attributes();
        echo '><head><meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '"><meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>' . esc_html( $title ) . ' — Annaadhya Ebooks</title>';
        wp_head();
        echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">';
        echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap">';
        echo '<style>body{margin:0;background:#0a0e13;font-family:Plus Jakarta Sans,system-ui,sans-serif;color:#e0e2ea;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}'
        . '.aa-scr{max-width:470px;width:100%;background:#101419;border:1px solid rgba(160,142,122,.25);border-radius:16px;padding:38px 34px;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.45)}'
        . '.aa-scr .aa-ms{font-size:46px;color:' . $color . '}'
        . '.aa-scr h1{font-size:22px;margin:14px 0 10px;font-weight:700}'
        . '.aa-scr p{color:#d8c3ad;font-size:14.5px;line-height:1.75;margin:0 0 26px}'
        . '.aa-scr-nav{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}'
        . '.aa-btn-gold,.aa-btn-ghost{display:inline-flex;align-items:center;gap:8px;font-weight:700;font-size:14px;padding:13px 22px;border-radius:10px;text-decoration:none}'
        . '.aa-btn-gold{background:#f59e0b;color:#472a00}.aa-btn-ghost{background:#1c2025;border:1px solid rgba(160,142,122,.4);color:#e0e2ea}</style>';
        echo '</head><body ';
        body_class();
        echo '><div class="aa-scr"><span class="aa-ms">' . esc_html( $glyph ) . '</span><h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $msg ) . '</p><div class="aa-scr-nav">' . $btns . '</div></div>';
        wp_footer();
        echo '</body></html>';
}

/* ------------------------------------------------------------------------
 * 4. Downloads are gated until the buyer's email is verified.
 *    Runs on init priority 7: AFTER WooCommerce registers its post types
 *    (init 5 — wc_get_order() only works from then on) and BEFORE the
 *    download handler itself (init 10).
 * ---------------------------------------------------------------------- */

add_action( 'init', 'aa_gate_downloads_on_verify', 7 );

function aa_gate_downloads_on_verify() {
        if ( is_admin() || wp_doing_ajax() ) { return; }
        if ( empty( $_GET['download_file'] ) || empty( $_GET['order'] ) ) { return; }

        $order_key = wc_clean( wp_unslash( $_GET['order'] ) );
        $order_id  = function_exists( 'wc_get_order_id_by_order_key' ) ? wc_get_order_id_by_order_key( $order_key ) : 0;
        $order     = $order_id ? wc_get_order( $order_id ) : null;
        if ( ! $order ) { return; }

        $uid = (int) $order->get_customer_id();
        if ( ! $uid || aa_email_is_verified( $uid ) ) { return; }

        wp_safe_redirect( add_query_arg( 'aa_verify', 'needed', home_url( '/library/' ) ) );
        exit;
}

/* ------------------------------------------------------------------------
 * 5. Resend-confirmation AJAX + the banner used across the account pages.
 * ---------------------------------------------------------------------- */

add_action( 'wp_ajax_aa_resend_verify', 'aa_resend_verify_ajax' );
add_action( 'wp_ajax_nopriv_aa_resend_verify', 'aa_resend_verify_ajax' );

function aa_resend_verify_ajax() {
        $uid = get_current_user_id();
        if ( ! $uid ) {
                wp_send_json( array( 'status' => 'error', 'msg' => 'Sign in first.' ), 401 );
        }
        if ( aa_email_is_verified( $uid ) ) {
                wp_send_json( array( 'status' => 'ok', 'msg' => 'Already verified — downloads are unlocked.' ) );
        }
        $last = (int) get_user_meta( $uid, '_aa_email_verify_sent', true );
        if ( $last && ( time() - $last ) < 60 ) {
                wp_send_json( array( 'status' => 'error', 'msg' => 'Just sent it — wait a minute and check spam.' ), 429 );
        }
        $ok = aa_email_send_verification( $uid );
        if ( $ok ) {
                wp_send_json( array( 'status' => 'ok', 'msg' => 'Confirmation email sent — check your inbox (and spam).' ) );
        }
        wp_send_json( array(
                'status' => 'error',
                'msg'    => 'The server could not send email yet. SMTP is not configured — add the free AA_SMTP_* constants to wp-config (Brevo free tier works great), or ask the store admin to verify your account manually.',
        ), 500 );
}

/** Amber verification banner — shown on Library + profile when unverified. */
function aa_verify_banner_html() {
        $uid  = get_current_user_id();
        if ( ! $uid || aa_email_is_verified( $uid ) ) { return ''; }
        $u = get_userdata( $uid );
        $needed = isset( $_GET['aa_verify'] ) && 'needed' === $_GET['aa_verify'];
        $ajax = esc_url( admin_url( 'admin-ajax.php' ) );
        return '<style>.aa-vbanner{display:flex;gap:14px;align-items:flex-start;background:rgba(255,181,84,.08);border:1px solid rgba(245,158,11,.45);border-radius:14px;padding:18px 22px;margin:0 0 26px}'
                . '.aa-vbanner>.aa-ms{color:var(--aa-goldbg,#f59e0b);font-size:26px;margin-top:2px}'
                . '.aa-vbanner strong{display:block;color:#e0e2ea;font-size:15px;margin-bottom:4px}'
                . '.aa-vbanner p{color:#d8c3ad;font-size:13.5px;line-height:1.65;margin:0 0 10px}'
                . '.aa-vbanner-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap}'
                . '.aa-vbanner-btn{display:inline-flex;align-items:center;gap:8px;background:#f59e0b;color:#472a00;font-weight:700;font-size:13px;padding:10px 16px;border-radius:10px;border:0;cursor:pointer;font-family:inherit}'
                . '.aa-vbanner-btn:disabled{opacity:.65;cursor:progress}'
                . '.aa-vbanner-btn .aa-ms{font-size:16px}'
                . '.aa-vbanner-note{font-size:12.5px;color:#8b8fa3;max-width:520px}</style>'
                . '<div class="aa-vbanner" data-aa-vbanner>'
                . '<span class="aa-ms">mark_email_unread</span>'
                . '<div><strong>' . ( $needed ? 'One step left — verify your email to unlock your downloads' : 'Verify your email to unlock downloads' ) . '</strong>'
                . '<p>We sent a confirmation link to ' . esc_html( $u->user_email ) . '. Click it once and every download in your library unlocks &mdash; attempts before that are paused, not lost.</p>'
                . '<div class="aa-vbanner-row"><button type="button" class="aa-vbanner-btn" data-aa-vresend><span class="aa-ms">forward_to_inbox</span><span>Resend confirmation email</span></button>'
                . '<span class="aa-vbanner-note" data-aa-vnote></span></div></div>'
                . '<script>(function(){var b=document.querySelector("[data-aa-vresend]");if(!b)return;b.addEventListener("click",function(){b.disabled=true;var n=document.querySelector("[data-aa-vnote]");if(n)n.textContent="Sending\\u2026";var fd=new FormData();fd.append("action","aa_resend_verify");fetch("' . $ajax . '",{method:"POST",body:fd,credentials:"same-origin"}).then(function(r){return r.json()}).then(function(d){if(n){n.textContent=(d&&d.msg)||"Sent.";n.style.color=(d&&d.status==="ok")?"#34d399":"#ff8a80"}setTimeout(function(){b.disabled=false},3000)}).catch(function(){if(n)n.textContent="Network error — try again.";b.disabled=false})})})();</script>'
                . '</div>';
}

/* ------------------------------------------------------------------------
 * 6. Google / OIDC sign-in driven by wp-config constants — no Google Cloud
 *    Console required (use Google's free OAuth client, or point AA_OAUTH_*
 *    at any OIDC provider such as Auth0's free tier which ships its own
 *    Google dev keys).
 *
 *    Option A — native Google (client id/secret from a free OAuth client):
 *      define( 'AA_GOOGLE_CLIENT_ID', '...apps.googleusercontent.com' );
 *      define( 'AA_GOOGLE_CLIENT_SECRET', '...' );
 *    Option B — any OpenID Connect provider (discovery-based):
 *      define( 'AA_OAUTH_ISSUER', 'https://your-tenant.auth0.com/' );
 *      define( 'AA_OAUTH_CLIENT_ID', '...' );
 *      define( 'AA_OAUTH_CLIENT_SECRET', '...' );
 * ---------------------------------------------------------------------- */

function aa_oauth_config() {
        if ( defined( 'AA_GOOGLE_CLIENT_ID' ) && AA_GOOGLE_CLIENT_ID && defined( 'AA_GOOGLE_CLIENT_SECRET' ) && AA_GOOGLE_CLIENT_SECRET ) {
                return array(
                        'client_id'     => AA_GOOGLE_CLIENT_ID,
                        'client_secret' => AA_GOOGLE_CLIENT_SECRET,
                        'authorize'     => 'https://accounts.google.com/o/oauth2/v2/auth',
                        'token'         => 'https://oauth2.googleapis.com/token',
                        'userinfo'      => 'https://openidconnect.googleapis.com/v1/userinfo',
                        'scope'         => 'openid email profile',
                        'label'         => 'google',
                );
        }
        if ( defined( 'AA_OAUTH_CLIENT_ID' ) && AA_OAUTH_CLIENT_ID && defined( 'AA_OAUTH_CLIENT_SECRET' ) && AA_OAUTH_CLIENT_SECRET && defined( 'AA_OAUTH_ISSUER' ) && AA_OAUTH_ISSUER ) {
                $iss = untrailingslashit( AA_OAUTH_ISSUER );
                $disc = get_transient( 'aa_oauth_disc_' . md5( $iss ) );
                if ( ! $disc ) {
                        $res = wp_remote_get( $iss . '/.well-known/openid-configuration', array( 'timeout' => 12 ) );
                        $disc = ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) )
                                ? array()
                                : json_decode( wp_remote_retrieve_body( $res ), true );
                        if ( ! empty( $disc['authorization_endpoint'] ) ) {
                                set_transient( 'aa_oauth_disc_' . md5( $iss ), $disc, HOUR_IN_SECONDS );
                        }
                }
                if ( empty( $disc['authorization_endpoint'] ) || empty( $disc['token_endpoint'] ) ) { return false; }
                return array(
                        'client_id'     => AA_OAUTH_CLIENT_ID,
                        'client_secret' => AA_OAUTH_CLIENT_SECRET,
                        'authorize'     => $disc['authorization_endpoint'],
                        'token'         => $disc['token_endpoint'],
                        'userinfo'      => isset( $disc['userinfo_endpoint'] ) ? $disc['userinfo_endpoint'] : '',
                        'scope'         => 'openid email profile',
                        'label'         => 'sso',
                );
        }
        return false;
}

/** Signed state: base64url(payload) + HMAC so /oauth/callback can trust it. */
function aa_oauth_state_sign( $payload ) {
        $body = rtrim( strtr( base64_encode( wp_json_encode( $payload ) ), '+/', '-_' ), '=' );
        $sig  = hash_hmac( 'sha256', $body, wp_salt( 'auth' ) );
        return $body . '.' . $sig;
}

function aa_oauth_state_open( $state ) {
        if ( ! is_string( $state ) || 2 !== substr_count( $state, '.' ) - 1 ) { return false; }
        $parts = explode( '.', $state );
        if ( 2 !== count( $parts ) ) { return false; }
        list( $body, $sig ) = $parts;
        $expected = hash_hmac( 'sha256', $body, wp_salt( 'auth' ) );
        if ( ! hash_equals( $expected, $sig ) ) { return false; }
        $json = base64_decode( strtr( $body, '-_', '+/' ) );
        $data = $json ? json_decode( $json, true ) : null;
        if ( ! is_array( $data ) || empty( $data['ts'] ) || ( time() - (int) $data['ts'] ) > 600 ) { return false; }
        return $data;
}

add_action( 'template_redirect', 'aa_oauth_routes', 0 );

function aa_oauth_routes() {
        $path = trim( (string) parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
        if ( 0 !== strpos( $path, 'oauth/' ) ) { return; }

        status_header( 200 ); // WP routed us as a 404 query; this is a real screen.
        nocache_headers();
        $cfg = aa_oauth_config();
        if ( ! $cfg ) {
                aa_auth_screen(
                        'Google sign-in is not wired up yet',
                        'The store owner has not added the OAuth constants to wp-config.php yet. Email sign-in works right now — it takes seconds.',
                        array( 'Sign in with email' => home_url( '/my-account/' ), 'Back to store' => home_url( '/' ) ),
                        'error'
                );
                exit;
        }

        $redirect_to = home_url( '/library/' );

        if ( 'oauth/start' === $path ) {
                if ( isset( $_GET['redirect_to'] ) ) {
                        $r = esc_url_raw( wp_unslash( $_GET['redirect_to'] ) );
                        if ( $r && 0 === strpos( $r, home_url( '/' ) ) ) { $redirect_to = $r; }
                }
                $state = aa_oauth_state_sign( array( 'ts' => time(), 'redirect_to' => $redirect_to, 'r' => uniqid( '', true ) ) );
                $auth = add_query_arg(
                        array(
                                'client_id'     => $cfg['client_id'],
                                'redirect_uri'  => home_url( '/oauth/callback' ),
                                'response_type' => 'code',
                                'scope'         => $cfg['scope'],
                                'state'         => $state,
                                'prompt'        => 'select_account',
                        ),
                        $cfg['authorize']
                );
                wp_redirect( $auth ); // provider-controlled URL
                exit;
        }

        if ( 'oauth/callback' === $path ) {
                $err = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : '';
                if ( $err ) {
                        aa_auth_screen( 'Sign-in cancelled', 'The ' . ( 'google' === $cfg['label'] ? 'Google' : 'provider' ) . ' sign-in was cancelled or denied. You can try again or use email.', array( 'Try again' => home_url( '/oauth/start' ), 'Email sign-in' => home_url( '/my-account/' ) ), 'error' );
                        exit;
                }
                $code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
                $state = isset( $_GET['state'] ) ? wp_unslash( $_GET['state'] ) : '';
                $data  = aa_oauth_state_open( $state );
                if ( '' === $code || ! $data ) {
                        aa_auth_screen( 'Sign-in session expired', 'That link took too long or was used twice. Please start again — it only takes a second.', array( 'Try again' => home_url( '/oauth/start' ), 'Email sign-in' => home_url( '/my-account/' ) ), 'error' );
                        exit;
                }

                $token_res = wp_remote_post(
                        $cfg['token'],
                        array(
                                'timeout' => 20,
                                'body'    => array(
                                        'code'          => $code,
                                        'client_id'     => $cfg['client_id'],
                                        'client_secret' => $cfg['client_secret'],
                                        'redirect_uri'  => home_url( '/oauth/callback' ),
                                        'grant_type'    => 'authorization_code',
                                ),
                        )
                );
                $token = json_decode( wp_remote_retrieve_body( $token_res ), true );
                if ( empty( $token['access_token'] ) ) {
                        aa_auth_screen( 'Sign-in hiccup', 'The provider did not return an access token. Please try again — email sign-in also always works.', array( 'Try again' => home_url( '/oauth/start' ), 'Email sign-in' => home_url( '/my-account/' ) ), 'error' );
                        exit;
                }

                $info = array();
                if ( $cfg['userinfo'] ) {
                        $info_res = wp_remote_get(
                                $cfg['userinfo'],
                                array( 'timeout' => 20, 'headers' => array( 'Authorization' => 'Bearer ' . $token['access_token'] ) )
                        );
                        $info = json_decode( wp_remote_retrieve_body( $info_res ), true );
                }
                if ( empty( $info['email'] ) && ! empty( $token['id_token'] ) ) {
                        // Decode the id_token payload (no signature check needed:
                        // we received it directly over TLS from the token endpoint).
                        $seg = explode( '.', $token['id_token'] );
                        if ( 3 === count( $seg ) ) {
                                $payload = json_decode( base64_decode( strtr( $seg[1], '-_', '+/' ) ), true );
                                if ( is_array( $payload ) ) { $info = array_merge( $payload, $info ); }
                        }
                }
                if ( empty( $info['email'] ) || ! is_email( $info['email'] ) ) {
                        aa_auth_screen( 'No email shared', 'The provider did not share an email address, which the store needs for your library. Try email sign-in instead.', array( 'Email sign-in' => home_url( '/my-account/' ) ), 'error' );
                        exit;
                }
                if ( isset( $info['email_verified'] ) && ! $info['email_verified'] ) {
                        aa_auth_screen( 'Email not verified', 'Your ' . ( 'google' === $cfg['label'] ? 'Google' : 'provider' ) . ' email is not verified yet — verify it there first, or sign in with email.', array( 'Email sign-in' => home_url( '/my-account/' ) ), 'error' );
                        exit;
                }

                $email = strtolower( $info['email'] );
                $user  = get_user_by( 'email', $email );
                if ( ! $user ) {
                        $base = sanitize_user( strtolower( current( explode( '@', $email ) ) ), true );
                        if ( '' === $base || ! validate_username( $base ) ) { $base = 'reader'; }
                        $username = $base; $i = 1;
                        while ( username_exists( $username ) ) { $i++; $username = $base . $i; }
                        $uid = wp_insert_user(
                                array(
                                        'user_login'   => $username,
                                        'user_email'   => $email,
                                        'user_pass'    => wp_generate_password( 24, true, true ),
                                        'display_name' => isset( $info['name'] ) && $info['name'] ? sanitize_text_field( $info['name'] ) : $base,
                                        'role'         => class_exists( 'WooCommerce' ) ? 'customer' : 'subscriber',
                                )
                        );
                        if ( is_wp_error( $uid ) ) {
                                aa_auth_screen( 'Could not create the account', 'Something went wrong creating your reader account. Please try email sign-in.', array( 'Email sign-in' => home_url( '/my-account/' ) ), 'error' );
                                exit;
                        }
                        $user = get_userdata( $uid );
                }

                update_user_meta( $user->ID, '_aa_email_verified', '1' );
                update_user_meta( $user->ID, '_aa_oauth_verified', '1' );
                update_user_meta( $user->ID, '_aa_oauth_provider', $cfg['label'] );
                wp_set_current_user( $user->ID );
                wp_set_auth_cookie( $user->ID, true );

                wp_safe_redirect( isset( $data['redirect_to'] ) ? $data['redirect_to'] : home_url( '/library/' ) );
                exit;
        }
}

/** True when an OAuth provider is configured — drives the auth card button. */
function aa_oauth_active() {
        return (bool) aa_oauth_config();
}

/* ------------------------------------------------------------------------
 * 7. Branded wp-login.php (lost-password flow keeps the store's look).
 * ---------------------------------------------------------------------- */

add_action( 'login_enqueue_scripts', 'aa_login_style' );

function aa_login_style() {
        echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">'
                . '<style>body.login{background:#0a0e13;font-family:Plus Jakarta Sans,system-ui,sans-serif}'
                . 'body.login #login{padding-top:12vh}'
                . 'body.login h1 a{background:none;text-indent:0;width:auto;height:auto;color:#f59e0b;font-size:26px;font-weight:800;letter-spacing:-.02em}'
                . 'body.login h1 a:after{content:" Ebooks";color:#e0e2ea;font-weight:600}'
                . 'body.login #backtoblog a,body.login #nav a{color:#8b8fa3}'
                . 'body.login #backtoblog a:hover,body.login #nav a:hover{color:#f59e0b}'
                . 'body.login .button-primary{background:#f59e0b;border-color:#f59e0b;color:#472a00;text-shadow:none;font-weight:700;border-radius:8px}'
                . 'body.login .button-primary:hover{background:#ffc174;border-color:#ffc174;color:#472a00}'
                . 'body.login form{background:#101419;border:1px solid rgba(160,142,122,.25);border-radius:14px;box-shadow:0 24px 60px rgba(0,0,0,.45)}'
                . 'body.login form input{background:#181c21!important;border-color:rgba(160,142,122,.3)!important;color:#e0e2ea!important}'
                . 'body.login label{color:#d8c3ad}body.login .message,body.login #login_error{background:#181c21;border-left:4px solid #f59e0b;color:#e0e2ea}</style>';
}

add_filter( 'login_headerurl', 'aa_login_url' );
function aa_login_url() { return home_url( '/' ); }

add_filter( 'login_headertitle', 'aa_login_title' );
function aa_login_title() { return 'Annaadhya'; }
