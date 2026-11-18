<?php
/**
 * v3 page build: rebuilds the pages touched by the digital-delivery +
 * auth requirements, using the fresh Stitch screens (auth card + library
 * download meters) as the design source of truth.
 *
 *   - my-account   → [aa_auth] (Google + email/password sign in / register,
 *                    profile dashboard, Woo endpoints unchanged)
 *   - library      → [aa_library] (5-download meters, re-buy states)
 *   - checkout     → copy now advertises the address-free flow
 *   - digital-delivery-policy → documents the 5-download rule
 *
 * Run:  wp eval 'require "tools/build_pages_v3.php";'
 */

/* Build as an admin (see set_stitch_pages.php header) — must run BEFORE the
 * requires so the internal Home/Shop rebuild inherits the admin user. */
if ( ! current_user_can( 'unfiltered_html' ) ) {
        $aa_build_admins = get_users( array( 'role' => 'Administrator', 'number' => 1, 'fields' => 'ID', 'orderby' => 'ID', 'order' => 'ASC' ) );
        if ( $aa_build_admins ) { wp_set_current_user( (int) reset( $aa_build_admins ) ); }
}

require __DIR__ . '/set_stitch_pages.php';   /* tokens/nav/footer + Home & Shop */
require __DIR__ . '/stitch_sections_v2.php'; /* v2 section library ($aa_woo_css, $aa_lib_css …) */

/* ---------------------------------------------------------------- helpers */
/* Same helpers as build_pages_v2.php (duplicated here so v3 can run alone). */

if ( ! function_exists( 'aa_page_head' ) ) {
        function aa_page_head( $crumb, $title, $sub = '' ) {
                $sub_html = '' !== $sub ? '<p class="aa-chead-sub">' . $sub . '</p>' : '';
                $style    = '<style>.aa-chead{position:relative;padding:46px 0 40px;border-bottom:1px solid var(--aa-line);background:var(--aa-bg);overflow:hidden}'
                        . '.aa-chead-glow{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:480px;height:220px;background:radial-gradient(closest-side,rgba(245,158,11,.1),transparent 70%);filter:blur(46px);pointer-events:none}'
                        . '.aa-chead .aa-crumb{display:flex;align-items:center;gap:8px;margin-bottom:18px}'
                        . '.aa-chead .aa-crumb a{font-size:11px;font-weight:700;letter-spacing:.08em;color:#a08e7a!important;text-decoration:none!important;text-transform:uppercase}'
                        . '.aa-chead .aa-crumb span.aa-cur{font-size:11px;font-weight:700;letter-spacing:.08em;color:var(--aa-goldbg);text-transform:uppercase}'
                        . '.aa-chead .aa-crumb .aa-ms{font-size:13px;color:#a08e7a}'
                        . '.aa-chead h1{font-size:clamp(30px,4vw,44px);font-weight:600;letter-spacing:-.02em;color:var(--aa-tx);margin:0}'
                        . '.aa-chead .aa-chead-sub{font-size:15px;color:var(--aa-warm);margin:12px 0 0}'
                        . '.aa-chead .aa-grad{background:linear-gradient(90deg,var(--aa-gold),var(--aa-tert));-webkit-background-clip:text;background-clip:text;color:transparent}'
                        . '.aa-chead .aa-wrap{position:relative}</style>';

                return $style . '<section class="aa-chead"><div class="aa-chead-glow"></div><div class="aa-wrap">'
                        . '<nav class="aa-crumb"><a href="/">Home</a><span class="aa-ms">chevron_right</span><span class="aa-cur">' . esc_html( $crumb ) . '</span></nav>'
                        . '<h1>' . $title . '</h1>' . $sub_html . '</div></section>';
        }
}

if ( ! function_exists( 'aa_upsert_page' ) ) {
        function aa_upsert_page( $slug, $title, $sections, $content = '', $args = array() ) {
                $page = get_page_by_path( $slug );
                $postarr = array(
                        'post_title'   => $title,
                        'post_name'    => $slug,
                        'post_content' => '' !== $content ? $content : $title,
                        'post_status'  => isset( $args['status'] ) ? $args['status'] : 'publish',
                        'post_type'    => 'page',
                );
                if ( $page ) {
                        $postarr['ID'] = $page->ID;
                        $page_id       = wp_update_post( $postarr, true );
                } else {
                        $page_id = wp_insert_post( $postarr, true );
                }
                if ( is_wp_error( $page_id ) ) {
                        echo 'ERR building ' . $slug . ': ' . $page_id->get_error_message() . "\n";
                        return 0;
                }

                $data = array();
                foreach ( $sections as $i => $h ) { $data[] = aa_section( $h, $i + 100 ); }

                update_post_meta( $page_id, '_aa_canvas', 1 );
                update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
                update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
                update_post_meta( $page_id, '_elementor_page_template', 'elementor_canvas' );
                update_post_meta( $page_id, '_elementor_version', '4.4.0' );
                update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
                update_post_meta( $page_id, 'site-post-title', 'disabled' );
                delete_post_meta( $page_id, '_elementor_element_cache' );
                delete_post_meta( $page_id, '_elementor_css' );

                echo 'OK ' . $slug . ' (id ' . $page_id . ', ' . count( $sections ) . " sections)\n";
                return (int) $page_id;
        }
}

/* ------------------------------------------------------------ 7. My Account */

aa_upsert_page( 'my-account', 'Wishlist', array(
        $aa_common_html . aa_nav_html( 'account' ),
        aa_page_head( 'Wishlist', 'My <span class="aa-grad">Wishlist</span>', 'Books you hearted are saved right in your browser &mdash; tap the heart on any cover to add one.' ),
        $aa_woo_css . '<section class="aa-woopage-wrap">[aa_wishlist_page]</section>',
        aa_footer_html(),
), '[aa_wishlist_page]' );

/* ---------------------------------------------------------- 6. My Library */

aa_upsert_page( 'library', 'Books by Email', array(
        $aa_common_html . aa_nav_html( '' ),
        aa_page_head( 'Books by Email', 'Books by <span class="aa-grad">Email</span>', 'No account needed &mdash; after checkout the PDF files land in your inbox instantly, attachments included.' ),
        $aa_lib_css . '<section class="aa-lib-wrap">[aa_library]</section>',
        aa_footer_html(),
), '[aa_library]' );

/* ------------------------------------------------------------ 5. Checkout */

aa_upsert_page( 'checkout', 'Checkout', array(
        $aa_common_html . aa_nav_html( '' ),
        aa_page_head( 'Checkout', 'Secure <span class="aa-grad">Checkout</span>', '<span class="aa-ms" style="font-size:16px;color:var(--aa-goldbg);vertical-align:-3px">lock</span> 256-bit encrypted &middot; no address needed &mdash; it&rsquo;s all digital &middot; your PDFs arrive in your email instantly' ),
        $aa_woo_css . '<section class="aa-woopage">[woocommerce_checkout]</section>',
        aa_footer_html(),
), '[woocommerce_checkout]' );

/* ------------------------------------------------- Digital delivery policy */

$dfile = __DIR__ . '/legal-content/digital-delivery-policy.php';
if ( file_exists( $dfile ) ) {
        $body = include $dfile;
        aa_upsert_page( 'digital-delivery-policy', 'Digital Product Delivery Policy', array(
                $aa_common_html . aa_nav_html( '' ),
                aa_page_head( 'Digital Product Delivery Policy', 'Annaadhya <span class="aa-grad">Digital Product Delivery Policy</span>', 'How your ebook reaches your device seconds after payment &mdash; and what to do if a link ever fails.' ),
                $aa_legal_css . '<section class="aa-legal"><div class="aa-legal-in"><div class="aa-legal-meta"><span><span class="aa-ms">event</span>Last updated: November 16, 2026</span><span><span class="aa-ms">cloud_download</span>Annaadhya Ebooks</span></div>' . $body . '</div>' . aa_legal_related() . '</section>',
                aa_footer_html(),
        ), 'The official Digital Product Delivery Policy of Annaadhya Ebooks.' );
}

echo "PAGES-V3-OK\n";
