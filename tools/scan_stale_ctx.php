<?php
/** Print context around every 'My Library' / '5 full downloads' occurrence outside header/footer widgets. */
if ( ! defined( 'ABSPATH' ) ) exit;

$pages = array( 91, 57, 56, 63, 26, 24, 7 );
foreach ( $pages as $pid ) {
        $data = get_post_meta( $pid, '_elementor_data', true );
        if ( ! $data ) { continue; }
        $arr = json_decode( $data, true );
        if ( ! is_array( $arr ) ) { echo "#$pid JSON-DECODE-FAIL\n"; continue; }
        foreach ( $arr as $i => $sec ) {
                foreach ( $sec['elements'] as $col ) {
                        foreach ( $col['elements'] as $w ) {
                                $h = isset( $w['settings']['html'] ) ? $w['settings']['html'] : '';
                                if ( strpos( $h, '<header class="aa-nav"' ) !== false || strpos( $h, '<footer class="aa-footer"' ) !== false ) { continue; }
                                foreach ( array( 'My Library', '5 full downloads', 'My Account', 'Sign in' ) as $n ) {
                                        $pos = 0;
                                        while ( ( $p = strpos( $h, $n, $pos ) ) !== false ) {
                                                $ctx = str_replace( array( "\n", "\r" ), ' ', substr( $h, max( 0, $p - 80 ), 180 ) );
                                                echo "#$pid sec$i [$n] ...$ctx...\n";
                                                $pos = $p + strlen( $n );
                                        }
                                }
                        }
                }
        }
}
echo "CTX-DONE\n";
