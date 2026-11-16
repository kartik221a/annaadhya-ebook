<?php
/**
 * Layout helpers: full HTML shell, sidebar, login page, 404 page.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function aa_admin_assets_url( $file ) {
        return AA_ADMIN_ASSETS . '/' . $file;
}

function aa_admin_head() {
        $css = aa_admin_assets_url( 'admin.css' ) . '?v=' . AA_ADMIN_VER;
        $js  = aa_admin_assets_url( 'admin.js' ) . '?v=' . AA_ADMIN_VER;
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
        echo '<meta name="robots" content="noindex,nofollow">' . "\n";
        echo '<link rel="stylesheet" href="' . esc_url( $css ) . '">' . "\n";
        echo '<script src="' . esc_url( $js ) . '" defer></script>' . "\n";
}

function aa_admin_icon( $name ) {
        $icons = array(
                'dash'    => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>',
                'book'    => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4h7a3 3 0 0 1 3 3v13a2.5 2.5 0 0 0-2.5-2.5H2z"/><path d="M22 4h-7a3 3 0 0 0-3 3v13a2.5 2.5 0 0 1 2.5-2.5H22z"/></svg>',
                'tag'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.6 13.4 12 22l-9-9V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z"/><circle cx="8" cy="8" r="1.4"/></svg>',
                'bag'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 7h12l1 14H5Z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>',
                'mail'    => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>',
                'users'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.8-3.4 3.4-5 6.5-5s5.7 1.6 6.5 5"/><circle cx="17" cy="9" r="2.5"/><path d="M17 14.5c2.4.2 4 1.6 4.5 4"/></svg>',
                'store'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16l1.5 6H2.5Z"/><path d="M4 10v10h16V10"/><path d="M9 20v-6h6v6"/></svg>',
                'wp'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M4.5 8.5h5M8 8.5 12 18l3.4-8"/><path d="M14.5 8.5H19M16.6 10.5 19.8 18"/></svg>',
                'out'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>',
                'plus'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>',
                'search'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.8-3.8"/></svg>',
                'download' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>',
                'file'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>',
        );
        return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
}

function aa_admin_layout( $title, $body, $active = 'dashboard' ) {
        $user = wp_get_current_user();
        $nav  = array(
                'dashboard'  => array( aa_admin_url(), 'Dashboard', 'dash' ),
                'products'   => array( aa_admin_url( 'products' ), 'Books', 'book' ),
                'categories' => array( aa_admin_url( 'categories' ), 'Categories', 'tag' ),
                'orders'     => array( aa_admin_url( 'orders' ), 'Orders', 'bag' ),
                'downloads'  => array( aa_admin_url( 'downloads' ), 'Downloads', 'download' ),
                'messages'   => array( aa_admin_url( 'messages' ), 'Messages', 'mail' ),
                'subscribers' => array( aa_admin_url( 'subscribers' ), 'Subscribers', 'users' ),
        );
        ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<title><?php echo esc_html( $title ); ?> — Annaadhya Admin</title>
<?php aa_admin_head(); ?>
</head>
<body>
<div class="layout">
        <aside class="side">
                <a class="brand" href="<?php echo esc_url( aa_admin_url() ); ?>">
                        <span class="brand-mark">A</span> Annaadhya <em>admin</em>
                </a>
                <nav class="nav">
                        <?php foreach ( $nav as $key => $item ) : ?>
                                <a class="<?php echo $active === $key ? 'on' : ''; ?>" href="<?php echo esc_url( $item[0] ); ?>">
                                        <?php echo aa_admin_icon( $item[2] ); ?><span><?php echo esc_html( $item[1] ); ?></span>
                                </a>
                        <?php endforeach; ?>
                </nav>
                <div class="sep"></div>
                <nav class="nav">
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">
                                <?php echo aa_admin_icon( 'store' ); ?><span>View store</span>
                        </a>
                        <a href="<?php echo esc_url( admin_url() ); ?>" target="_blank" rel="noopener">
                                <?php echo aa_admin_icon( 'wp' ); ?><span>wp-admin</span>
                        </a>
                        <a href="<?php echo esc_url( wp_logout_url( aa_admin_url() ) ); ?>">
                                <?php echo aa_admin_icon( 'out' ); ?><span>Log out</span>
                        </a>
                </nav>
                <div class="side-foot">v<?php echo esc_html( AA_ADMIN_VER ); ?> · <?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
        </aside>
        <main class="main">
                <div class="topbar">
                        <h1><?php echo esc_html( $title ); ?></h1>
                        <span class="who"><?php echo get_avatar( $user->ID, 30 ); ?> <?php echo esc_html( $user->display_name ?: $user->user_login ); ?></span>
                </div>
                <?php echo aa_admin_flash(); // phpcs:ignore ?>
                <?php echo $body; // phpcs:ignore ?>
        </main>
</div>
</body>
</html>
        <?php
}

function aa_admin_login_page() {
        $msg = '';
        if ( isset( $_GET['aa_msg'] ) && 'login_failed' === sanitize_key( $_GET['aa_msg'] ) ) {
                $msg = '<div class="flash err">Wrong username or password. Try again.</div>';
        }
        ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<title>Sign in — Annaadhya Admin</title>
<?php aa_admin_head(); ?>
</head>
<body class="login-body">
        <form class="login-card" method="post" action="<?php echo esc_url( aa_admin_url() ); ?>">
                <div class="login-brand"><span class="brand-mark">A</span> Annaadhya <em>admin</em></div>
                <p class="login-sub">Sign in to manage the bookstore.</p>
                <?php echo $msg; // phpcs:ignore ?>
                <input type="hidden" name="aa_login" value="1">
                <?php wp_nonce_field( 'aa_login' ); ?>
                <label>Username</label>
                <input type="text" name="aa_user" autocomplete="username" required autofocus>
                <label>Password</label>
                <input type="password" name="aa_pass" autocomplete="current-password" required>
                <button class="btn pri wide" type="submit">Sign in</button>
                <a class="login-back" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to the store</a>
        </form>
</body>
</html>
        <?php
}

function aa_admin_not_found() {
        $body = '<div class="card empty"><h2>Page not found</h2><p>That admin page does not exist.</p><a class="btn pri" href="' . esc_url( aa_admin_url() ) . '">Back to dashboard</a></div>';
        aa_admin_layout( 'Not found', $body, 'dashboard' );
}
