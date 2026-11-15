<?php
/**
 * wp eval-file helper: print basename of a product's first downloadable file.
 * Usage: wp eval-file get_dlfile.php <product_id>
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$p = wc_get_product( (int) $args[0] );
$d = ( $p && ! is_wp_error( $p ) ) ? $p->get_downloads() : array();
echo $d ? basename( reset( $d )->get_file() ) : 'NONE';
