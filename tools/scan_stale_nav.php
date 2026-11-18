<?php
/**
 * Scan every Elementor page's widget HTML for strings that must change now
 * that the store is account-free (books by email, no library/auth UI).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$needles = array(
        '[aa_auth]',
        '/library/',
        '/my-account/wishlist/',
        '5 full downloads',
        'Sign in with Google',
        'My Library',
        'My Account',
        'download-limit aware',
        'Returning customer',
        'auto_stories</span><span>Library</span>',
        '<span>Account</span>',
        'downloads appear in My Library',
        'Open My Library',
        'class="aa-actbtn"',
);

$pages = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) );
foreach ( $pages as $pid ) {
        $data = get_post_meta( $pid, '_elementor_data', true );
        if ( ! $data ) { continue; }
        $hits = array();
        foreach ( $needles as $n ) {
                $c = substr_count( $data, $n );
                if ( $c ) { $hits[] = "$n x$c"; }
        }
        if ( $hits ) {
                echo "#$pid " . get_the_title( $pid ) . ": " . implode( ' | ', $hits ) . "\n";
        }
}
echo "SCAN-DONE\n";
