<?php
/**
 * Plugin Name: Annaadhya Store Tweaks (must-use)
 * Description: Stitch "Obsidian & Gold" storefront plumbing: canvas renderer for all core pages (home, shop, search, product, cart, checkout, account, library, about, contact, legal), [aa_books] product grid, [aa_product_single], [aa_library] (download-limit aware), [aa_auth] (Google + email/password), [aa_contact_form], address-free digital checkout, wishlist (account endpoint + AJAX), newsletter + contact AJAX handlers, cart badge.
 * Version:     2.1.0
 * Author:      Annaadhya
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ------------------------------------------------------------------------
 * Portable URLs — the same repo runs untouched on localhost:8080, a LAN IP,
 * a preview tunnel or a production domain. scheme://host follows the incoming
 * request whenever the request host differs from the configured site URL.
 * This is what keeps /web-admin (and every storefront link) reachable no
 * matter how the store is opened.
 * ---------------------------------------------------------------------- */
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) {
        $_SERVER['HTTPS'] = 'on'; // trust the edge proxy's TLS flag for is_ssl()
}

add_filter( 'pre_option_home', 'aa_portable_url' );
add_filter( 'pre_option_siteurl', 'aa_portable_url' );

function aa_portable_url( $value ) {
        if ( wp_installing() || ( defined( 'WP_CLI' ) && WP_CLI ) ) { return $value; }
        if ( empty( $_SERVER['HTTP_HOST'] ) ) { return $value; } // wp-cli / cron without host
        $host = (string) $_SERVER['HTTP_HOST'];
        if ( ! preg_match( '/^[a-z0-9.\-]+(:[0-9]{1,5})?$/i', $host ) ) { return $value; }
        $scheme = is_ssl() ? 'https' : 'http';
        return $scheme . '://' . $host;
}

/* ------------------------------------------------------------------------
 * Canvas renderer
 *
 * Storefront pages are authored as self-contained Elementor "html" widgets
 * (tools/set_stitch_pages.php + tools/build_pages_v2.php) and printed through
 * a minimal canvas shell of our own, bypassing Astra/Woo archive chrome.
 * ---------------------------------------------------------------------- */

add_filter( 'template_include', 'aa_canvas_for_storefront', 99 );

function aa_canvas_for_storefront( $tpl ) {
        if ( is_admin() || ! is_main_query() ) { return $tpl; }

        if ( function_exists( 'wc_get_page_id' ) ) {
                $front = (int) get_option( 'page_on_front' );
                if ( $front && is_page( $front ) ) {
                        aa_render_canvas( $front );
                }

                $shop = (int) wc_get_page_id( 'shop' );
                if ( $shop > 0 && is_shop() ) {
                        aa_render_canvas( $shop );
                }
                // Site search (?s=) renders through the shop canvas — its [aa_books]
                // grid reads the search term, so results keep the Stitch shell.
                if ( $shop > 0 && is_search() && '' !== get_search_query( false ) ) {
                        aa_render_canvas( $shop );
                }

                $cart_id = (int) wc_get_page_id( 'cart' );
                if ( $cart_id > 0 && is_cart() ) {
                        aa_render_canvas( $cart_id );
                }

                // [woocommerce_checkout] itself renders order-pay + order-received views.
                $co = (int) wc_get_page_id( 'checkout' );
                if ( $co > 0 && is_checkout() ) {
                        aa_render_canvas( $co );
                }

                $acc = (int) wc_get_page_id( 'myaccount' );
                if ( $acc > 0 && is_account_page() ) {
                        aa_render_canvas( $acc );
                }

                // Every single-product URL renders through one hidden template page
                // whose [aa_product_single] shortcode reads the queried product.
                $pd = (int) get_option( 'aa_product_page', 0 );
                if ( $pd > 0 && is_product() ) {
                        $GLOBALS['aa_current_product'] = get_queried_object_id();
                        aa_render_canvas( $pd );
                }

                // Every product-category archive renders through one hidden template
                // page: the Stitch hero comes from [aa_category_hero], the grid from
                // [aa_books] (both read the queried term).
                if ( is_product_category() ) {
                        $catpage = (int) get_option( 'aa_category_page', 0 );
                        if ( $catpage > 0 ) {
                                $GLOBALS['aa_current_cat'] = get_queried_object();
                                aa_render_canvas( $catpage );
                        }
                }
        }

        // Any normal page flagged with _aa_canvas renders through our shell.
        if ( is_page() ) {
                $pid = get_queried_object_id();
                if ( $pid && get_post_meta( $pid, '_aa_canvas', true ) ) {
                        aa_render_canvas( $pid );
                }
        }

        return $tpl;
}

/** Print a minimal canvas shell + the Elementor content of one page. */
function aa_render_canvas( $post_id ) {
        if ( ! get_post( $post_id ) ) { return; }
        if ( ! class_exists( '\Elementor\Plugin' ) ) { return; }

        status_header( 200 );
        nocache_headers();

        echo '<!DOCTYPE html><html ';
        language_attributes();
        echo '><head><meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        wp_head();
        echo '</head><body ';
        body_class( 'aa-canvas' );
        echo '>';
        wp_body_open();
        echo '<div class="elementor elementor-' . esc_attr( $post_id ) . '">';

        // get_builder_content() (not _for_display) — the display variant bails out
        // with an "avoid recursion" guard when get_the_ID() equals the rendered ID,
        // which is exactly our case (we render the queried page itself).
        echo \Elementor\Plugin::$instance->frontend->get_builder_content( $post_id, false );

        echo '</div>';
        wp_footer();
        echo '</body></html>';
        exit;
}

/* ------------------------------------------------------------------------
 * Cart badge plumbing for the custom Stitch header.
 * ---------------------------------------------------------------------- */

add_filter( 'woocommerce_add_to_cart_fragments', 'aa_cart_count_fragment' );

function aa_cart_count_fragment( $fragments ) {
        $count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
        $hidden = $count < 1 ? ' hidden' : '';
        $fragments['[data-aa-cartcount]'] = '<span class="aa-cartcount" data-aa-cartcount' . $hidden . '>' . esc_html( $count ) . '</span>';
        return $fragments;
}

add_action( 'wc_ajax_aa_cart_count', 'aa_cart_count_endpoint' );

function aa_cart_count_endpoint() {
        $count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
        wp_send_json( array( 'count' => $count ) );
}

/* ------------------------------------------------------------------------
 * Digital-only storefront: checkout is address-free.
 *
 * We sell downloadable PDFs — the customer keeps a name and an email and
 * nothing else. All billing address fields are dropped, the shipping block
 * and order-notes field are removed, and purchases always belong to an
 * account (guest checkout is disabled store-wide) so downloads stay in the
 * customer's library forever.
 * ---------------------------------------------------------------------- */

add_filter( 'woocommerce_checkout_fields', 'aa_address_free_checkout_fields', 20 );

function aa_address_free_checkout_fields( $fields ) {
        $keep = array( 'billing_first_name', 'billing_last_name', 'billing_email' );
        if ( isset( $fields['billing'] ) && is_array( $fields['billing'] ) ) {
                foreach ( array_keys( $fields['billing'] ) as $key ) {
                        if ( ! in_array( $key, $keep, true ) ) { unset( $fields['billing'][ $key ] ); }
                }
                if ( isset( $fields['billing']['billing_first_name'] ) ) { $fields['billing']['billing_first_name']['priority'] = 10; }
                if ( isset( $fields['billing']['billing_last_name'] ) ) { $fields['billing']['billing_last_name']['priority'] = 20; }
                if ( isset( $fields['billing']['billing_email'] ) ) { $fields['billing']['billing_email']['priority'] = 30; }
        }
        unset( $fields['shipping'] );
        return $fields;
}

add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );

add_filter( 'woocommerce_default_address_fields', 'aa_drop_address_fields' );

function aa_drop_address_fields( $fields ) {
        foreach ( array( 'address_1', 'address_2', 'city', 'postcode', 'country', 'state', 'company' ) as $key ) {
                unset( $fields[ $key ] );
        }
        return $fields;
}

/** Gold "Open My Library" CTA on the order-received screen. */
add_filter( 'woocommerce_thankyou_order_received_text', 'aa_thankyou_library_cta', 20, 2 );

function aa_thankyou_library_cta( $text, $order ) {
        $lib = get_permalink( get_page_by_path( 'library' ) );
        if ( $lib ) {
                $text .= ' <a class="aa-btn-gold" style="display:inline-flex;align-items:center;gap:8px;margin-left:12px;padding:10px 18px;border-radius:10px;background:var(--aa-goldbg,#f59e0b);color:var(--aa-ongold,#472a00);font-weight:700;text-decoration:none" href="' . esc_url( $lib ) . '"><span class="aa-ms">download</span><span>Open My Library to download</span></a>';
        }
        return $text;
}

/* ------------------------------------------------------------------------
 * [aa_books limit="6" category="" paginate="no"]
 * Stitch-style book cards from live WooCommerce products.
 * ---------------------------------------------------------------------- */

add_shortcode( 'aa_books', 'aa_books_shortcode' );

function aa_books_shortcode( $atts ) {
        $a = shortcode_atts(
                array(
                        'limit'    => '6',
                        'category' => '',
                        'paginate' => 'no',
                        'orderby'  => 'menu_order title',
                        'order'    => 'ASC',
                        'exclude'  => '',
                ),
                $atts,
                'aa_books'
        );

        $args = array(
                'post_type'           => 'product',
                'post_status'         => 'publish',
                'posts_per_page'      => (int) $a['limit'],
                'orderby'             => $a['orderby'],
                'order'               => $a['order'],
                'ignore_sticky_posts' => true,
                'no_found_rows'       => ( $a['paginate'] !== 'yes' ),
        );

        if ( 'yes' === $a['paginate'] ) {
                $args['paged'] = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
        }

        $s = isset( $_GET['book'] ) ? sanitize_text_field( wp_unslash( $_GET['book'] ) ) : '';
        if ( '' === $s && isset( $_GET['s'] ) ) {
                $s = sanitize_text_field( wp_unslash( $_GET['s'] ) );
        }
        if ( '' !== $s ) {
                $args['s'] = $s;
        }

        if ( '' !== $a['category'] ) {
                $args['tax_query'] = array(
                        array(
                                'taxonomy' => 'product_cat',
                                'field'    => 'slug',
                                'terms'    => array_map( 'trim', explode( ',', $a['category'] ) ),
                        ),
                );
        } elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
                // Category archive: the [aa_books] grid follows the queried term.
                $term = get_queried_object();
                if ( $term && isset( $term->taxonomy ) && 'product_cat' === $term->taxonomy ) {
                        $args['tax_query'] = array(
                                array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => array( $term->slug ) ),
                        );
                }
        }

        $exclude = array_filter( array_map( 'absint', explode( ',', $a['exclude'] ) ) );
        if ( $exclude ) {
                $args['post__not_in'] = $exclude;
        }

        $q = new WP_Query( $args );

        $cards = array();
        while ( $q->have_posts() ) {
                $q->the_post();
                $cards[] = aa_book_card( get_the_ID() );
        }
        wp_reset_postdata();

        if ( empty( $cards ) ) {
                $empty = '' === $s
                        ? 'No books on the shelf yet — new titles are added regularly.'
                        : 'No books matched your search. Try a different keyword.';
                return '<div class="aa-empty">' . esc_html( $empty ) . '</div>';
        }

        $html  = '<div class="aa-books"><ul class="aa-products">';
        $html .= implode( '', $cards );
        $html .= '</ul>';

        if ( 'yes' === $a['paginate'] && $q->max_num_pages > 1 ) {
                $links = paginate_links(
                        array(
                                'total'     => $q->max_num_pages,
                                'current'   => max( 1, (int) $args['paged'] ),
                                'mid_size'  => 1,
                                'format'    => '?paged=%#%',
                                'prev_text' => '<span class="aa-ms">arrow_back</span><span>Previous</span>',
                                'next_text' => '<span>Next</span><span class="aa-ms">arrow_forward</span>',
                                'type'      => 'list',
                        )
                );
                if ( $links ) {
                        $html .= '<nav class="aa-pager-wrap"><p class="aa-pager-info">Page <strong>' . esc_html( (int) $args['paged'] ) . '</strong> of <strong>' . esc_html( (int) $q->max_num_pages ) . '</strong></p>' . $links . '</nav>';
                }
        }

        $html .= '</div>';
        return $html;
}

/* ------------------------------------------------------------------------
 * [aa_category_hero] — Stitch category hero for the queried product_cat term.
 * One motif panel per category (ported 1:1 from the four Stitch category
 * screens), dynamic title/description/count from the term + term meta.
 * ---------------------------------------------------------------------- */

add_shortcode( 'aa_category_hero', 'aa_category_hero_shortcode' );

function aa_category_hero_shortcode( $atts ) {
        $term = $GLOBALS['aa_current_cat'] ?? null;
        if ( ! $term || empty( $term->taxonomy ) || 'product_cat' !== $term->taxonomy ) {
                return '';
        }

        $slug = $term->slug;
        $name = $term->name;

        // Per-slug copy (fallback when the term description is empty) + motif id.
        $copy = array(
                'technology' => array(
                        'sub'   => 'AI, WordPress and the craft of shipping digital products — practical, battle-tested guides for indie builders and software engineers.',
                        'motif' => 'technology',
                ),
                'business'   => array(
                        'sub'   => 'Lean side hustles, product launches and the playbooks that actually pay — field notes for founders and operators.',
                        'motif' => 'business',
                ),
                'self-help'  => array(
                        'sub'   => 'Attention, discipline and calm — honest systems for doing deep work in a noisy world.',
                        'motif' => 'selfhelp',
                ),
                'fiction'    => array(
                        'sub'   => 'Stories that travel — from Himalayan trails to far shores and quiet rooms, told by voices worth staying up for.',
                        'motif' => 'fiction',
                ),
        );
        $c      = isset( $copy[ $slug ] ) ? $copy[ $slug ] : array( 'sub' => 'Curated ebooks for instant PDF download.', 'motif' => 'technology' );
        $sub    = trim( (string) $term->description );
        if ( '' === $sub ) { $sub = $c['sub']; }

        $count  = (int) $term->count;
        $nword  = $count . ( 1 === $count ? ' ebook' : ' ebooks' );

        // Gold-accent the last word of the headline, like the Stitch screens.
        $words  = explode( ' ', $name );
        $last   = array_pop( $words );
        $h1     = esc_html( implode( ' ', $words ) );
        if ( '' !== $h1 ) { $h1 .= ' '; }
        $h1    .= '<span class="aa-cath-gold">' . esc_html( $last ) . '</span>';

        $motif = aa_category_motif( $c['motif'], $nword, $slug );

        return '<section class="aa-cath"><div class="aa-wrap"><div class="aa-cath-band">'
                . '<div class="aa-cath-glow" aria-hidden="true"></div>'
                . '<div class="aa-cath-grid">'
                . '<div class="aa-cath-copy">'
                . '<nav class="aa-cath-crumb"><a href="/">Home</a><span>/</span><a href="/shop/">Store</a><span>/</span><em>' . esc_html( $name ) . '</em></nav>'
                . '<div class="aa-cath-eyebrow"><span class="aa-ms">auto_awesome</span><span>CURATED COLLECTION</span></div>'
                . '<h1>' . $h1 . '</h1>'
                . '<p class="aa-cath-sub">' . esc_html( $sub ) . '</p>'
                . '<div class="aa-cath-pills">'
                . '<span class="aa-cath-pill"><span class="aa-ms">menu_book</span>' . esc_html( $nword ) . '</span>'
                . '<span class="aa-cath-pill"><span class="aa-ms">bolt</span>Instant PDF delivery</span>'
                . '<span class="aa-cath-pill"><span class="aa-ms">devices</span>Read on any device</span>'
                . '</div>'
                . '<a class="aa-btn-ghost aa-cath-cta" href="#aa-catgrid"><span>Browse all titles</span><span class="aa-ms">arrow_forward</span></a>'
                . '</div>'
                . '<div class="aa-cath-art">' . $motif . '</div>'
                . '</div></div></div></section>';
}

/** Per-category motif panel (right side of the hero), ported from Stitch. */
function aa_category_motif( $motif, $nword, $slug ) {
        $cap = '<div class="aa-motif-top"><span class="aa-motif-dots"><i></i><i class="g"></i><i class="s"></i></span>';
        switch ( $motif ) {
                case 'business':
                        return $cap . '<span class="aa-motif-caps">EXEC_PLAYBOOK_METRICS.INR</span></div>'
                                . '<div class="aa-motif-body">'
                                . '<div class="aa-biz-stat"><span class="aa-biz-ic"><span class="aa-ms">rocket_launch</span></span>'
                                . '<span class="aa-biz-stat-tx"><em>Median Bootstrapped Launch</em><strong>&#8377;2.4L / Month Run Rate</strong></span>'
                                . '<span class="aa-biz-badge">+48% MoM</span></div>'
                                . '<div class="aa-biz-chart"><div class="aa-biz-chart-h"><span>CAPITAL EFFICIENCY INDEX</span><b>98.4% ROI</b></div>'
                                . '<div class="aa-biz-bars"><i style="height:30%"></i><i style="height:45%"></i><i style="height:40%"></i><i style="height:65%"></i><i style="height:80%"></i><i class="hot" style="height:95%"></i></div></div>'
                                . '<div class="aa-biz-strip"><span><span class="aa-ms">verified</span> Founder-tested playbooks</span><span><span class="aa-ms">payments</span> UPI ready</span></div>'
                                . '</div>';
                case 'selfhelp':
                        return '<div class="aa-sh-wrap">'
                                . '<div class="aa-sh-halo" aria-hidden="true"></div>'
                                . $cap . '<span class="aa-motif-caps">FOCUS_DISCIPLINE_METRICS</span><i class="aa-sh-live"></i></div>'
                                . '<div class="aa-sh-art">'
                                . '<i class="aa-ring r1" aria-hidden="true"></i><i class="aa-ring r2" aria-hidden="true"></i><i class="aa-ring r3" aria-hidden="true"></i><i class="aa-ring r4" aria-hidden="true"></i>'
                                . '<svg class="aa-sh-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
                                . '<path d="M50 20C54.4 20 58 16.4 58 12C58 7.6 54.4 4 50 4C45.6 4 42 7.6 42 12C42 16.4 45.6 20 50 20Z" fill="currentColor" fill-opacity=".85"/>'
                                . '<path d="M50 24C41 24 35 29 32 37L28 47C26 52 29 57 34 57H66C71 57 74 52 72 47L68 37C65 29 59 24 50 24Z" fill="currentColor" fill-opacity=".4"/>'
                                . '<path d="M22 68C22 61 27 57 33 57H67C73 57 78 61 78 68C78 74 72 80 62 82L66 90H34L38 82C28 80 22 74 22 68Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>'
                                . '<path d="M50 86C50 86 52 76 60 76C68 76 66 86 66 86C66 86 58 87 50 86Z" fill="#ffc174" fill-opacity=".3"/>'
                                . '</svg>'
                                . '</div>'
                                . '<div class="aa-sh-stats"><div><em>Deep-work sessions</em><strong>2h 40m avg</strong></div><div><em>Focus streak</em><strong>' . esc_html( $nword ) . '</strong></div></div>'
                                . '</div>';
                case 'fiction':
                        return '<div class="aa-fic-wrap">'
                                . $cap . '<span class="aa-motif-caps">LITERARY_JOURNEYS.INR</span><span class="aa-fic-ed">EDITION 2026</span></div>'
                                . '<div class="aa-fic-art">'
                                . '<svg class="aa-fic-svg" viewBox="0 0 400 180" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" aria-hidden="true">'
                                . '<path d="M-20 180L80 90L160 140L240 60L340 150L420 100V180H-20Z" fill="#181c21" stroke="#ffc174" stroke-opacity=".3" stroke-width=".75"/>'
                                . '<path d="M40 180L140 110L220 150L300 80L380 180H40Z" fill="#121820" stroke="#f59e0b" stroke-opacity=".5" stroke-width=".75"/>'
                                . '<circle cx="320" cy="40" fill="#ffc174" fill-opacity=".15" r="14"/>'
                                . '<path d="M326 30C320 33 316 38 316 46C316 54 322 59 328 61C320 62 312 55 312 46C312 37 319 31 326 30Z" fill="#ffc174"/>'
                                . '<circle cx="120" cy="45" fill="#ffddb8" r="1.5"/><circle cx="200" cy="35" fill="#ffddb8" r="1"/><circle cx="260" cy="55" fill="#ffddb8" r="1"/>'
                                . '<path d="M120 180C140 160 180 150 190 135C200 120 220 115 240 60" stroke="#f59e0b" stroke-dasharray="3 3" stroke-opacity=".8" stroke-width="1.2"/>'
                                . '</svg>'
                                . '<div class="aa-fic-node"><span class="aa-ms">auto_stories</span></div>'
                                . '</div>'
                                . '<div class="aa-fic-stats"><div><em>Bedtime picks</em><strong>' . esc_html( $nword ) . '</strong></div><div><em>Avg. read time</em><strong>4h 12m</strong></div></div>'
                                . '</div>';
                default: // technology — terminal window
                        return $cap . '<span class="aa-motif-caps">annaadhya-kernel :: v2.6.0</span><span class="aa-ms aa-motif-ti">terminal</span></div>'
                                . '<div class="aa-term">'
                                . '<div class="aa-term-line"><span class="aa-term-p">$</span><span>fetch --category "' . esc_html( $slug ) . '" --store annaadhya</span></div>'
                                . '<div class="aa-term-out"><p class="g">&gt; Found ' . esc_html( $nword ) . ' verified technical masterworks</p><p>&gt; Formats: PDF (DRM-Free) + EPUB + Code Kits</p><p>&gt; Currency: INR (&#8377;) Instant UPI enabled</p></div>'
                                . '<div class="aa-term-foot"><span class="aa-term-tag"><span class="aa-ms">code</span><b>&lt;Build / Ship / Scale&gt;</b></span><span class="aa-term-chip">100% WATERMARKED</span></div>'
                                . '<div class="aa-term-strip"><span><span class="aa-ms">verified</span> Authentic Indian Authors</span><span><span class="aa-ms">bolt</span> Lifetime updates</span></div>'
                                . '</div>';
        }
}

/** [aa_category_count] — "Showing N ebooks" for the category toolbar. */
add_shortcode( 'aa_category_count', 'aa_category_count_shortcode' );

function aa_category_count_shortcode( $atts ) {
        $term = $GLOBALS['aa_current_cat'] ?? null;
        if ( ! $term || empty( $term->taxonomy ) || 'product_cat' !== $term->taxonomy ) { return ''; }
        $n = (int) $term->count;
        return '<span class="aa-showing">Showing <strong>' . esc_html( $n ) . '</strong> ' . ( 1 === $n ? 'ebook' : 'ebooks' ) . '</span>';
}

/** Wishlist ids for the current user (empty array when guest). */
function aa_get_wishlist() {
        if ( ! is_user_logged_in() ) { return array(); }
        $wl = get_user_meta( get_current_user_id(), '_aa_wishlist', true );
        return is_array( $wl ) ? array_map( 'absint', $wl ) : array();
}

/** Render one Stitch-style book card for a product. */
function aa_book_card( $product_id ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) { return ''; }

        $permalink = get_permalink( $product_id );
        $name      = get_the_title( $product_id );
        $img       = get_the_post_thumbnail_url( $product_id, 'large' );
        if ( ! $img ) { $img = wc_placeholder_img_src(); }

        $cat_terms = get_the_terms( $product_id, 'product_cat' );
        $cat       = '';
        if ( $cat_terms && ! is_wp_error( $cat_terms ) ) {
                foreach ( $cat_terms as $t ) {
                        if ( 'uncategorized' !== $t->slug ) { $cat = $t->name; break; }
                }
                if ( '' === $cat ) { $cat = $cat_terms[0]->name; }
        }

        $author = (string) get_post_meta( $product_id, '_aa_author', true );
        $badge  = (string) get_post_meta( $product_id, '_aa_badge', true );
        $format = (string) get_post_meta( $product_id, '_aa_format', true );
        if ( '' === $format ) { $format = 'PDF'; }
        $rating = (string) get_post_meta( $product_id, '_aa_rating', true );

        $regular = (float) $product->get_regular_price( 'edit' );
        $active  = (float) $product->get_price( 'edit' );
        $on_sale = $product->is_on_sale( 'edit' ) && $regular > 0 && $active < $regular;
        $save    = $on_sale ? round( ( 1 - $active / $regular ) * 100 ) : 0;

        if ( $on_sale ) {
                $price_html = '<del>' . wp_kses_post( wc_price( $regular ) ) . '</del><ins>' . wp_kses_post( wc_price( $active ) ) . '</ins>';
                $save_html  = 'Save ' . $save . '%';
        } else {
                $price_html = '<span class="aa-only">' . wp_kses_post( wc_price( $active ) ) . '</span>';
                $save_html  = '';
        }

        $stars = '';
        for ( $i = 0; $i < 5; $i++ ) { $stars .= '<span class="aa-ms aa-star">star</span>'; }
        $rate_row = $rating
                ? '<div class="aa-stars">' . $stars . '<span class="aa-rate">' . esc_html( $rating ) . '/5</span></div>'
                : '';

        $add_url  = add_query_arg( 'add-to-cart', $product_id, home_url( '/' ) );
        $cart_cls = $product->supports( 'ajax_add_to_cart' ) ? 'add_to_cart_button ajax_add_to_cart' : 'add_to_cart_button';
        $in_wish  = in_array( $product_id, aa_get_wishlist(), true );

        ob_start();
        ?>
<li class="aa-card" data-price="<?php echo esc_attr( $active ); ?>" data-title="<?php echo esc_attr( $name ); ?>">
        <div class="aa-media">
                <a class="aa-media-link" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $name ); ?>">
                        <img class="aa-cover" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $name ); ?> ebook cover">
                </a>
                <button type="button" class="aa-wishbtn<?php echo $in_wish ? ' aa-on' : ''; ?>" data-id="<?php echo esc_attr( $product_id ); ?>" aria-label="Save to wishlist"><span class="aa-ms">favorite</span></button>
                <span class="aa-fmt"><?php echo esc_html( $format ); ?></span>
                <?php if ( $badge ) : ?>
                        <span class="aa-badge"><?php echo esc_html( $badge ); ?></span>
                <?php elseif ( $on_sale && $save >= 40 ) : ?>
                        <span class="aa-badge aa-badge-sale">SAVE <?php echo esc_html( $save ); ?>%</span>
                <?php endif; ?>
        </div>
        <div class="aa-meta">
                <span class="aa-mcat"><?php echo esc_html( strtoupper( $cat ) ); ?></span>
                <?php if ( $author ) : ?><span class="aa-mauth"><?php echo esc_html( $author ); ?></span><?php endif; ?>
        </div>
        <h3 class="aa-title"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $name ); ?></a></h3>
        <?php echo $rate_row; // phpcs:ignore WordPress.Security.EscapeOutput ?>
        <div class="aa-foot">
                <div class="aa-pricing">
                        <span class="aa-now"><?php echo $price_html; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
                        <?php if ( $save_html ) : ?><span class="aa-save"><?php echo esc_html( $save_html ); ?></span><?php endif; ?>
                </div>
                <a class="aa-add <?php echo esc_attr( $cart_cls ); ?>" data-product_id="<?php echo esc_attr( $product_id ); ?>" data-quantity="1" href="<?php echo esc_url( $add_url ); ?>">
                        <span class="aa-ms">shopping_cart</span><span>Add to cart</span>
                </a>
        </div>
</li>
        <?php
        return ob_get_clean();
}

/* ------------------------------------------------------------------------
 * Wishlist: AJAX toggle + My Account endpoint.
 * ---------------------------------------------------------------------- */

add_action( 'wp_ajax_aa_wishlist_toggle', 'aa_wishlist_toggle_ajax' );
add_action( 'wp_ajax_nopriv_aa_wishlist_toggle', 'aa_wishlist_toggle_ajax' );

function aa_wishlist_toggle_ajax() {
        if ( ! is_user_logged_in() ) {
                wp_send_json( array( 'status' => 'login' ) );
        }
        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        if ( ! $id || 'product' !== get_post_type( $id ) ) {
                wp_send_json( array( 'status' => 'error' ), 400 );
        }
        $uid = get_current_user_id();
        $wl  = aa_get_wishlist();
        $on  = true;
        $key = array_search( $id, $wl, true );
        if ( false !== $key ) {
                unset( $wl[ $key ] );
                $wl = array_values( $wl );
                $on = false;
        } else {
                $wl[] = $id;
        }
        update_user_meta( $uid, '_aa_wishlist', $wl );
        wp_send_json( array( 'status' => 'ok', 'on' => $on, 'count' => count( $wl ) ) );
}

add_action( 'init', 'aa_register_wishlist_endpoint' );

function aa_register_wishlist_endpoint() {
        add_rewrite_endpoint( 'wishlist', EP_ROOT | EP_PAGES );
}

add_filter( 'woocommerce_account_menu_items', 'aa_wishlist_menu_item' );

function aa_wishlist_menu_item( $items ) {
        if ( isset( $items['downloads'] ) ) {
                $new = array();
                foreach ( $items as $k => $v ) {
                        $new[ $k ] = $v;
                        if ( 'downloads' === $k ) { $new['wishlist'] = __( 'Wishlist', 'aa' ); }
                }
                return $new;
        }
        $items['wishlist'] = __( 'Wishlist', 'aa' );
        return $items;
}

add_action( 'woocommerce_account_wishlist_endpoint', 'aa_wishlist_endpoint_content' );

function aa_wishlist_endpoint_content() {
        $wl = aa_get_wishlist();
        if ( empty( $wl ) ) {
                echo '<div class="aa-account-empty"><span class="aa-ms">favorite</span><h3>Nothing saved yet</h3><p>Tap the heart on any book cover to keep it here for later.</p><a class="aa-btn-gold" href="' . esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ) . '">Browse the Bookshelf</a></div>';
                return;
        }
        $q = new WP_Query(
                array(
                        'post_type'           => 'product',
                        'post_status'         => 'publish',
                        'post__in'            => $wl,
                        'orderby'             => 'post__in',
                        'posts_per_page'      => count( $wl ),
                        'ignore_sticky_posts' => true,
                )
        );
        echo '<div class="aa-books aa-wishlist-grid"><ul class="aa-products">';
        while ( $q->have_posts() ) {
                $q->the_post();
                echo aa_book_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput
        }
        wp_reset_postdata();
        echo '</ul></div>';
}

/* ------------------------------------------------------------------------
 * [aa_auth] — Stitch auth screen: Google + email/password, and the
 * signed-in profile dashboard. Woo endpoints (orders, edit-account,
 * wishlist…) still render through [woocommerce_my_account].
 * ---------------------------------------------------------------------- */

add_shortcode( 'aa_auth', 'aa_auth_shortcode' );

function aa_auth_is_woo_endpoint() {
        if ( ! function_exists( 'is_wc_endpoint_url' ) ) { return false; }
        foreach ( array( 'orders', 'view-order', 'edit-account', 'edit-address', 'downloads', 'wishlist', 'lost-password', 'customer-logout' ) as $ep ) {
                if ( is_wc_endpoint_url( $ep ) ) { return true; }
        }
        return false;
}

/**
 * Google sign-in goes live the moment Nextend Social Login has the Google
 * provider configured (Client ID + Secret) and enabled. Until then the auth
 * card shows the friendly "use email meanwhile" fallback, so the design is
 * final but nothing half-works.
 */
function aa_auth_google_active() {
        if ( ! class_exists( 'NextendSocialLogin', false ) && ! did_action( 'nsl_init' ) ) { return false; }
        $main = get_option( 'nextend_social_login' );
        if ( ! is_array( $main ) || empty( $main['enabled'] ) || ! in_array( 'google', (array) $main['enabled'], true ) ) { return false; }
        $g = get_option( 'nsl_google' );
        if ( ! is_array( $g ) || empty( $g['client_id'] ) || empty( $g['client_secret'] ) ) { return false; }
        return true;
}

function aa_auth_shortcode() {
        if ( aa_auth_is_woo_endpoint() ) {
                return '<div class="aa-woopage">' . do_shortcode( '[woocommerce_my_account]' ) . '</div>';
        }

        if ( is_user_logged_in() ) { return aa_profile_dashboard_html(); }

        $nonce  = wp_create_nonce( 'aa_auth' );
        $google = function_exists( 'aa_oauth_active' ) ? aa_oauth_active() : aa_auth_google_active();
        $g_url  = $google ? esc_url( home_url( '/oauth/start?redirect_to=' . urlencode( home_url( '/library/' ) ) ) ) : '';

        ob_start();
        ?>
<style>
.aa-auth{padding:56px 0 72px;background:radial-gradient(closest-side,rgba(245,158,11,.07),transparent 70%) no-repeat 50% 0/var(--aa-wrapw,1px) 420px,var(--aa-bg)}
.aa-auth-card{max-width:480px;margin:0 auto;background:var(--aa-surface);border:1px solid var(--aa-line);border-radius:16px;padding:34px 34px 30px;box-shadow:0 24px 60px rgba(0,0,0,.45)}
.aa-auth-tabs{display:flex;gap:24px;border-bottom:1px solid var(--aa-line);margin-bottom:26px}
.aa-auth-tab{background:none;border:0;color:var(--aa-dim);font:600 14px/1 inherit;font-family:inherit;padding:0 2px 12px;cursor:pointer;border-bottom:2px solid transparent;letter-spacing:.02em}
.aa-auth-tab.aa-on{color:var(--aa-tx);border-bottom-color:var(--aa-goldbg)}
.aa-auth-sub{color:var(--aa-dim);font-size:13px;line-height:1.6;margin:-8px 0 22px}
.aa-gbtn{display:flex;align-items:center;justify-content:center;gap:12px;width:100%;background:#fff;color:#1f1f1f;font:700 14px/1 inherit;font-family:inherit;border:0;border-radius:10px;padding:14px 18px;cursor:pointer;text-decoration:none!important}
.aa-gbtn:hover{background:#f3f4f6}
.aa-gbtn svg{width:19px;height:19px}
.aa-auth-div{display:flex;align-items:center;gap:12px;margin:20px 0;color:var(--aa-dim);font-size:11px;font-weight:700;letter-spacing:.09em;text-transform:uppercase}
.aa-auth-div::before,.aa-auth-div::after{content:"";flex:1;height:1px;background:var(--aa-line)}
.aa-field{display:block;margin-bottom:14px}
.aa-field>span{display:block;font-size:12px;font-weight:700;color:var(--aa-warm);margin-bottom:7px;letter-spacing:.02em}
.aa-field input{width:100%;background:var(--aa-low);border:1px solid var(--aa-line);border-radius:10px;color:var(--aa-tx);padding:13px 14px;font-size:14px;font-family:inherit;outline:none}
.aa-field input:focus{border-color:var(--aa-goldbg)}
.aa-auth-row{display:flex;align-items:center;justify-content:space-between;margin:4px 0 18px;font-size:13px;color:var(--aa-dim)}
.aa-auth-row label{display:flex;align-items:center;gap:8px;cursor:pointer}
.aa-auth-row a{color:var(--aa-goldbg);text-decoration:none;font-weight:600}
.aa-auth-btn{width:100%;display:flex;align-items:center;justify-content:center;gap:10px;background:var(--aa-goldbg);color:var(--aa-ongold);font:700 14.5px/1 inherit;font-family:inherit;border:0;border-radius:10px;padding:15px 18px;cursor:pointer}
.aa-auth-btn:hover{background:var(--aa-gold)}
.aa-auth-fine{color:var(--aa-dim);font-size:12px;line-height:1.7;margin:18px 0 0;text-align:center}
.aa-auth-fine a{color:var(--aa-goldbg);text-decoration:none}
.aa-auth-pane[hidden]{display:none}
.aa-auth-err{display:none;background:rgba(255,77,77,.08);border:1px solid rgba(255,77,77,.35);color:#ff8a80;border-radius:10px;padding:11px 14px;font-size:13px;margin-bottom:16px}
.aa-auth-err.aa-show{display:block}
.aa-verify-ic{width:64px;height:64px;border-radius:50%;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.4);display:flex;align-items:center;justify-content:center;margin:6px auto 18px}
.aa-verify-ic .aa-ms{font-size:32px;color:var(--aa-goldbg)}
.aa-auth-continue{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;margin-top:14px;background:var(--aa-cont);border:1px solid var(--aa-line2);color:var(--aa-tx)!important;font-weight:600;font-size:14px;padding:13px 18px;border-radius:10px;text-decoration:none!important}
.aa-auth-continue:hover{border-color:var(--aa-gold);color:var(--aa-gold)!important}
.aa-toast{position:fixed;left:50%;bottom:28px;transform:translateX(-50%);background:var(--aa-high);color:var(--aa-tx);border:1px solid var(--aa-line);border-radius:12px;padding:13px 20px;font-size:13.5px;z-index:9999;box-shadow:0 12px 40px rgba(0,0,0,.5);display:none;max-width:88vw;text-align:center}
@media(max-width:560px){.aa-auth-card{padding:26px 20px}}
</style>
<section class="aa-auth"><div class="aa-wrap"><div class="aa-auth-card" data-aa-authcard data-nonce="<?php echo esc_attr( $nonce ); ?>">
        <div class="aa-auth-tabs">
                <button type="button" class="aa-auth-tab aa-on" data-pane="signin">Sign in</button>
                <button type="button" class="aa-auth-tab" data-pane="register">Create account</button>
        </div>
        <p class="aa-auth-sub">Your ebooks stay in your account forever &mdash; each purchase includes 5 full downloads.</p>
        <div class="aa-auth-err" data-aa-auth-err></div>

        <div class="aa-auth-pane" data-aa-pane="signin">
                <?php if ( $g_url ) : ?>
                        <a class="aa-gbtn" href="<?php echo $g_url; ?>">
                                <svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9 3.5l6.7-6.7C35.6 2.4 30.2 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.8 6.1C12.3 13.2 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.1 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.4c-.5 2.9-2.2 5.3-4.6 7l7.6 5.9c4.4-4.1 6.7-10.1 6.7-17.4z"/><path fill="#FBBC05" d="M10.4 28.7a14.5 14.5 0 0 1 0-9.4l-7.8-6.1a24 24 0 0 0 0 21.6l7.8-6.1z"/><path fill="#34A853" d="M24 48c6.2 0 11.4-2 15.4-5.6l-7.6-5.9c-2.1 1.4-4.8 2.3-7.8 2.3-6.3 0-11.7-3.7-13.6-8.9l-7.8 6.1C6.5 42.6 14.6 48 24 48z"/></svg>
                                <span>Continue with Google</span>
                        </a>
                        <div class="aa-auth-div"><span>or continue with email</span></div>
                <?php else : ?>
                        <button type="button" class="aa-gbtn" data-aa-google="0">
                                <svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9 3.5l6.7-6.7C35.6 2.4 30.2 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.8 6.1C12.3 13.2 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.1 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.4c-.5 2.9-2.2 5.3-4.6 7l7.6 5.9c4.4-4.1 6.7-10.1 6.7-17.4z"/><path fill="#FBBC05" d="M10.4 28.7a14.5 14.5 0 0 1 0-9.4l-7.8-6.1a24 24 0 0 0 0 21.6l7.8-6.1z"/><path fill="#34A853" d="M24 48c6.2 0 11.4-2 15.4-5.6l-7.6-5.9c-2.1 1.4-4.8 2.3-7.8 2.3-6.3 0-11.7-3.7-13.6-8.9l-7.8 6.1C6.5 42.6 14.6 48 24 48z"/></svg>
                                <span>Continue with Google</span>
                        </button>
                        <div class="aa-auth-div"><span>or continue with email</span></div>
                <?php endif; ?>
                <form method="post" data-aa-login novalidate>
                        <label class="aa-field"><span>Email or username</span><input type="text" name="aa_login_id" autocomplete="username" placeholder="you@example.com" required></label>
                        <label class="aa-field"><span>Password</span><input type="password" name="aa_login_pass" autocomplete="current-password" placeholder="Your password" required></label>
                        <div class="aa-auth-row">
                                <label><input type="checkbox" name="aa_remember" checked> Remember me</label>
                                <a href="<?php echo esc_url( wp_lostpassword_url( home_url( '/my-account/' ) ) ); ?>">Forgot password?</a>
                        </div>
                        <button class="aa-auth-btn" type="submit"><span class="aa-ms">login</span><span>Sign in</span></button>
                </form>
                <p class="aa-auth-fine">New to Annaadhya? <a href="#" data-switch="register">Create an account</a></p>
        </div>

        <div class="aa-auth-pane" data-aa-pane="register" hidden>
                <form method="post" data-aa-register novalidate>
                        <label class="aa-field"><span>Full name</span><input type="text" name="aa_reg_name" autocomplete="name" placeholder="Ananya Sharma" required></label>
                        <label class="aa-field"><span>Email address</span><input type="email" name="aa_reg_email" autocomplete="email" placeholder="you@example.com" required></label>
                        <label class="aa-field"><span>Password <em style="color:var(--aa-dim);font-style:normal">(min 8 characters)</em></span><input type="password" name="aa_reg_pass" autocomplete="new-password" minlength="8" placeholder="Create a password" required></label>
                        <button class="aa-auth-btn" type="submit"><span class="aa-ms">person_add</span><span>Create account</span></button>
                </form>
                <p class="aa-auth-fine">By creating an account you agree to our <a href="/terms/">Terms</a> and <a href="/privacy-policy/">Privacy Policy</a>.</p>
        </div>

        <div class="aa-auth-pane" data-aa-pane="verify" hidden>
                <div class="aa-verify-ic"><span class="aa-ms">mark_email_read</span></div>
                <h3 style="color:var(--aa-tx);font-size:19px;margin:0 0 10px;text-align:center">Check your inbox</h3>
                <p class="aa-auth-sub" style="text-align:center">We sent a confirmation link to <strong style="color:var(--aa-gold)" data-aa-vemail></strong>. Click it once to verify your email — that unlocks ebook downloads in your account.</p>
                <p class="aa-auth-sub" data-aa-vmail style="display:none;background:rgba(255,180,80,.08);border:1px solid rgba(245,158,11,.35);border-radius:10px;padding:11px 14px;color:var(--aa-warm)"></p>
                <button class="aa-auth-btn" type="button" data-aa-resend><span class="aa-ms">forward_to_inbox</span><span>Resend confirmation email</span></button>
                <p style="text-align:center;margin:12px 0 0"><span data-aa-resend-note style="font-size:12.5px;color:var(--aa-dim)"></span></p>
                <a class="aa-auth-continue" href="/library/"><span>Continue to the store &rarr;</span></a>
        </div>
</div></div></section>
<script>
(function(){
        var card=document.querySelector("[data-aa-authcard]");if(!card)return;
        var err=card.querySelector("[data-aa-auth-err]");
        function showErr(m){if(err){err.textContent=m;err.classList.add("aa-show");}}
        function hideErr(){if(err)err.classList.remove("aa-show");}
        var tabs=card.querySelectorAll(".aa-auth-tab"),panes=card.querySelectorAll("[data-aa-pane]");
        function pane(id){tabs.forEach(function(t){t.classList.toggle("aa-on",t.dataset.pane===id)});panes.forEach(function(p){p.hidden=(p.dataset.aaPane!==id)});hideErr();}
        tabs.forEach(function(t){t.addEventListener("click",function(){pane(t.dataset.pane)})});
        card.querySelectorAll("[data-switch]").forEach(function(a){a.addEventListener("click",function(e){e.preventDefault();pane(a.dataset.switch)})});
        var g=card.querySelector("[data-aa-google]");
        if(g){g.addEventListener("click",function(){if(window.aaToast)aaToast("Google sign-in needs a free one-time setup (see README → Sign-in options). Email sign-in works right now.");else showErr("Google sign-in needs a free one-time setup. Email sign-in works right now.")})}
        function post(fd){fd.append("nonce",card.dataset.nonce);return fetch("/wp-admin/admin-ajax.php",{method:"POST",body:fd,credentials:"same-origin"}).then(function(r){return r.json()})}
        var lf=card.querySelector("[data-aa-login]");
        if(lf){lf.addEventListener("submit",function(e){e.preventDefault();hideErr();var fd=new FormData(lf);fd.append("action","aa_login_submit");post(fd).then(function(d){if(d&&d.status==="ok"){location.href=(d.redirect)||location.href}else{showErr((d&&d.msg)||"Sign-in failed — check your details.")}}).catch(function(){showErr("Network error — please try again.")})})}
        var rf=card.querySelector("[data-aa-register]");
        if(rf){rf.addEventListener("submit",function(e){e.preventDefault();hideErr();var fd=new FormData(rf);fd.append("action","aa_register_submit");post(fd).then(function(d){if(d&&d.status==="ok"&&d.verified===false){var em=card.querySelector("[data-aa-vemail]");if(em)em.textContent=d.email||"";var mv=card.querySelector("[data-aa-vmail]");if(mv&&d.mail===false){mv.textContent="Heads up: this server cannot send email yet (SMTP not configured). The admin can verify your account from the admin panel, or set the free AA_SMTP_* constants in wp-config.";mv.style.display="block"}pane("verify")}else if(d&&d.status==="ok"){location.href=(d.redirect)||location.href}else{showErr((d&&d.msg)||"Could not create the account.")}}).catch(function(){showErr("Network error — please try again.")})})}
        var rs=card.querySelector("[data-aa-resend]");
        if(rs){rs.addEventListener("click",function(){rs.disabled=true;var n=card.querySelector("[data-aa-resend-note]");if(n)n.textContent="Sending…";var fd=new FormData();fd.append("action","aa_resend_verify");fetch("/wp-admin/admin-ajax.php",{method:"POST",body:fd,credentials:"same-origin"}).then(function(r){return r.json()}).then(function(d){if(n){n.textContent=(d&&d.msg)||"Sent.";n.style.color=(d&&d.status==="ok")?"#34d399":"#ff8a80"}setTimeout(function(){rs.disabled=false},4000)}).catch(function(){if(n)n.textContent="Network error.";rs.disabled=false})})}
})();
</script>
        <?php
        return ob_get_clean();
}

function aa_profile_dashboard_html() {
        $uid    = get_current_user_id();
        $user   = get_userdata( $uid );
        $wl     = aa_get_wishlist();
        $orders = function_exists( 'wc_get_orders' ) ? wc_get_orders( array( 'customer' => $uid, 'limit' => 5 ) ) : array();
        global $wpdb;
        $owned = 0;
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'woocommerce_downloadable_product_permissions' ) ) ) {
                $owned = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT product_id) FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE user_id = %d", $uid ) );
        }
        $lib_url = get_permalink( get_page_by_path( 'library' ) );

        ob_start();
        ?>
<style>
.aa-prof{padding:46px 0 72px}
.aa-prof-hero{display:flex;align-items:center;gap:18px;background:var(--aa-surface);border:1px solid var(--aa-line);border-radius:16px;padding:24px 26px;margin-bottom:16px}
.aa-prof-hero img{width:58px;height:58px;border-radius:50%;border:2px solid var(--aa-goldbg)}
.aa-prof-hero h2{color:var(--aa-tx);font-size:21px;margin:0 0 3px}
.aa-prof-hero p{color:var(--aa-dim);font-size:13.5px;margin:0}
.aa-prof-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px}
@media(max-width:640px){.aa-prof-stats{grid-template-columns:1fr 1fr}}
.aa-prof-stat{background:var(--aa-low);border:1px solid var(--aa-line);border-radius:12px;padding:18px 20px}
.aa-prof-stat strong{display:block;color:var(--aa-goldbg);font-size:24px}
.aa-prof-stat span{color:var(--aa-dim);font-size:12.5px;font-weight:700;letter-spacing:.05em;text-transform:uppercase}
.aa-prof-tiles{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;margin-bottom:26px}
.aa-prof-tile{display:flex;align-items:center;gap:14px;background:var(--aa-low);border:1px solid var(--aa-line);border-radius:12px;padding:18px 20px;color:var(--aa-tx);text-decoration:none;font-weight:600;font-size:14.5px}
.aa-prof-tile .aa-ms{color:var(--aa-goldbg);font-size:22px}
.aa-prof-tile:hover{border-color:var(--aa-goldbg)}
.aa-prof-orders{background:var(--aa-low);border:1px solid var(--aa-line);border-radius:12px;padding:20px}
.aa-prof-orders h3{color:var(--aa-tx);font-size:15px;margin:0 0 12px}
.aa-prof-orders table{width:100%;border-collapse:collapse;font-size:13.5px}
.aa-prof-orders th{text-align:left;color:var(--aa-dim);font-size:11px;text-transform:uppercase;letter-spacing:.06em;padding:6px 8px;border-bottom:1px solid var(--aa-line)}
.aa-prof-orders td{padding:9px 8px;border-bottom:1px solid var(--aa-line);color:var(--aa-tx)}
.aa-prof-logout{display:inline-flex;align-items:center;gap:8px;margin-top:18px;color:#ff8a80;font-size:13.5px;font-weight:600;text-decoration:none}
</style>
<section class="aa-prof"><div class="aa-wrap">
        <?php echo function_exists( 'aa_verify_banner_html' ) ? aa_verify_banner_html() : ''; ?>
        <div class="aa-prof-hero">
                <?php echo get_avatar( $uid, 58 ); ?>
                <div><h2>Hello, <?php echo esc_html( $user->display_name ? $user->display_name : $user->user_login ); ?></h2>
                <p><?php echo esc_html( $user->user_email ); ?> &middot; member since <?php echo esc_html( mysql2date( 'M Y', $user->user_registered ) ); ?></p></div>
        </div>
        <div class="aa-prof-stats">
                <div class="aa-prof-stat"><strong><?php echo esc_html( $owned ); ?></strong><span>Books owned</span></div>
                <div class="aa-prof-stat"><strong><?php echo esc_html( count( $orders ) ); ?></strong><span>Orders</span></div>
                <div class="aa-prof-stat"><strong><?php echo esc_html( count( $wl ) ); ?></strong><span>Wishlist</span></div>
        </div>
        <div class="aa-prof-tiles">
                <a class="aa-prof-tile" href="<?php echo esc_url( $lib_url ); ?>"><span class="aa-ms">download</span><span>My Library &amp; downloads</span></a>
                <a class="aa-prof-tile" href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>"><span class="aa-ms">receipt_long</span><span>Orders</span></a>
                <a class="aa-prof-tile" href="<?php echo esc_url( wc_get_account_endpoint_url( 'wishlist' ) ); ?>"><span class="aa-ms">favorite</span><span>Wishlist</span></a>
                <a class="aa-prof-tile" href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>"><span class="aa-ms">settings</span><span>Profile &amp; password</span></a>
        </div>
        <div class="aa-prof-orders">
                <h3>Recent orders</h3>
                <?php if ( $orders ) : ?><table><thead><tr><th>Order</th><th>Date</th><th>Total</th><th>Status</th></tr></thead><tbody>
                <?php foreach ( $orders as $o ) : ?><tr>
                        <td><a href="<?php echo esc_url( $o->get_view_order_url() ); ?>" style="color:var(--aa-goldbg);text-decoration:none">#<?php echo esc_html( $o->get_order_number() ); ?></a></td>
                        <td><?php echo esc_html( $o->get_date_created() ? $o->get_date_created()->date_i18n( 'M j, Y' ) : '' ); ?></td>
                        <td><?php echo wp_kses_post( $o->get_formatted_order_total() ); ?></td>
                        <td><?php echo esc_html( ucfirst( $o->get_status() ) ); ?></td>
                </tr><?php endforeach; ?>
                </tbody></table>
                <?php else : ?><p style="color:var(--aa-dim);font-size:13.5px;margin:0">No orders yet — <a href="/shop/" style="color:var(--aa-goldbg);text-decoration:none">find your first read</a>.</p><?php endif; ?>
        </div>
        <a class="aa-prof-logout" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><span class="aa-ms">logout</span><span>Log out</span></a>
</div></section>
        <?php
        return ob_get_clean();
}

/* AJAX: email/password sign-in + registration for [aa_auth]. */

add_action( 'wp_ajax_aa_login_submit', 'aa_login_submit_ajax' );
add_action( 'wp_ajax_nopriv_aa_login_submit', 'aa_login_submit_ajax' );

function aa_login_submit_ajax() {
        if ( ! check_ajax_referer( 'aa_auth', 'nonce', false ) ) {
                wp_send_json( array( 'status' => 'error', 'msg' => 'Security check failed — reload the page and try again.' ), 403 );
        }
        $id  = isset( $_POST['aa_login_id'] ) ? sanitize_text_field( wp_unslash( $_POST['aa_login_id'] ) ) : '';
        $pw  = isset( $_POST['aa_login_pass'] ) ? (string) wp_unslash( $_POST['aa_login_pass'] ) : '';
        if ( '' === $id || '' === $pw ) {
                wp_send_json( array( 'status' => 'error', 'msg' => 'Enter your email and password.' ), 400 );
        }
        if ( is_email( $id ) ) {
                $u = get_user_by( 'email', $id );
                if ( $u ) { $id = $u->user_login; }
        }
        $creds = array( 'user_login' => $id, 'user_password' => $pw, 'remember' => ! empty( $_POST['aa_remember'] ) );
        $user  = wp_signon( $creds, true );
        if ( is_wp_error( $user ) ) {
                wp_send_json( array( 'status' => 'error', 'msg' => 'Wrong email or password — try again.' ), 401 );
        }
        wp_send_json( array( 'status' => 'ok', 'redirect' => home_url( '/library/' ) ) );
}

add_action( 'wp_ajax_aa_register_submit', 'aa_register_submit_ajax' );
add_action( 'wp_ajax_nopriv_aa_register_submit', 'aa_register_submit_ajax' );

function aa_register_submit_ajax() {
        if ( ! check_ajax_referer( 'aa_auth', 'nonce', false ) ) {
                wp_send_json( array( 'status' => 'error', 'msg' => 'Security check failed — reload the page and try again.' ), 403 );
        }
        $name  = isset( $_POST['aa_reg_name'] ) ? sanitize_text_field( wp_unslash( $_POST['aa_reg_name'] ) ) : '';
        $email = isset( $_POST['aa_reg_email'] ) ? sanitize_email( wp_unslash( $_POST['aa_reg_email'] ) ) : '';
        $pw    = isset( $_POST['aa_reg_pass'] ) ? (string) wp_unslash( $_POST['aa_reg_pass'] ) : '';
        if ( '' === $name || ! is_email( $email ) || strlen( $pw ) < 8 ) {
                wp_send_json( array( 'status' => 'error', 'msg' => 'Fill your name, a valid email and a password of at least 8 characters.' ), 400 );
        }
        if ( email_exists( $email ) ) {
                wp_send_json( array( 'status' => 'error', 'msg' => 'An account with this email already exists — sign in instead.' ), 409 );
        }
        $base = sanitize_user( strtolower( current( explode( '@', $email ) ) ), true );
        if ( '' === $base || ! validate_username( $base ) ) { $base = 'reader'; }
        $username = $base; $i = 1;
        while ( username_exists( $username ) ) { $i++; $username = $base . $i; }
        $uid = wp_create_user( $username, $pw, $email );
        if ( is_wp_error( $uid ) ) {
                wp_send_json( array( 'status' => 'error', 'msg' => 'Could not create the account — try a different email.' ), 500 );
        }
        wp_update_user( array( 'ID' => $uid, 'display_name' => $name ) );
        // Confirmation email first — the account can browse immediately, but
        // downloads stay locked until the address is verified.
        $mail_ok = function_exists( 'aa_email_send_verification' ) ? aa_email_send_verification( $uid ) : false;
        update_user_meta( $uid, '_aa_email_verified', '0' );
        wp_signon( array( 'user_login' => $username, 'user_password' => $pw, 'remember' => true ), true );
        wp_send_json( array( 'status' => 'ok', 'verified' => false, 'mail' => (bool) $mail_ok, 'email' => $email, 'redirect' => home_url( '/library/' ) ) );
}

add_shortcode( 'aa_product_single', 'aa_product_single_shortcode' );

function aa_product_single_shortcode() {
        $pid = isset( $GLOBALS['aa_current_product'] ) ? (int) $GLOBALS['aa_current_product'] : (int) get_queried_object_id();
        $product = wc_get_product( $pid );
        if ( ! $product ) {
                return '<div class="aa-empty">Book not found.</div>';
        }

        $name    = get_the_title( $pid );
        $img     = get_the_post_thumbnail_url( $pid, 'full' );
        if ( ! $img ) { $img = wc_placeholder_img_src(); }

        $author  = (string) get_post_meta( $pid, '_aa_author', true );
        $format  = (string) get_post_meta( $pid, '_aa_format', true );
        if ( '' === $format ) { $format = 'PDF'; }
        $rating  = (string) get_post_meta( $pid, '_aa_rating', true );
        $pages   = (string) get_post_meta( $pid, '_aa_pages', true );
        $lang    = (string) get_post_meta( $pid, '_aa_lang', true );
        if ( '' === $lang ) { $lang = 'English'; }
        $badge   = (string) get_post_meta( $pid, '_aa_badge', true );

        $cat_terms = get_the_terms( $pid, 'product_cat' );
        $cat       = '';
        if ( $cat_terms && ! is_wp_error( $cat_terms ) ) {
                foreach ( $cat_terms as $t ) {
                        if ( 'uncategorized' !== $t->slug ) { $cat = $t->name; break; }
                }
                if ( '' === $cat ) { $cat = $cat_terms[0]->name; }
        }

        $regular = (float) $product->get_regular_price( 'edit' );
        $active  = (float) $product->get_price( 'edit' );
        $on_sale = $product->is_on_sale( 'edit' ) && $regular > 0 && $active < $regular;
        $save    = $on_sale ? round( ( 1 - $active / $regular ) * 100 ) : 0;

        if ( $on_sale ) {
                $price_html = '<del>' . wp_kses_post( wc_price( $regular ) ) . '</del><ins>' . wp_kses_post( wc_price( $active ) ) . '</ins>';
                $save_html  = '<span class="aa-savepill">Save ' . $save . '%</span>';
        } else {
                $price_html = '<span class="aa-now">' . wp_kses_post( wc_price( $active ) ) . '</span>';
                $save_html  = '';
        }

        $add_url  = add_query_arg( 'add-to-cart', $pid, home_url( '/' ) );
        $cart_cls = $product->supports( 'ajax_add_to_cart' ) ? 'add_to_cart_button ajax_add_to_cart' : 'add_to_cart_button';

        // Demo store: the bundled PDF doubles as the free sample chapter.
        $sample_url = '';
        $downloads  = $product->get_downloads();
        if ( $downloads ) {
                $dl         = reset( $downloads );
                $sample_url = $dl->get_file();
        }
        $in_wish = in_array( $pid, aa_get_wishlist(), true );

        $stars = '';
        for ( $i = 0; $i < 5; $i++ ) { $stars .= '<span class="aa-ms aa-star">star</span>'; }

        // Reviews (Woo comments on the product).
        $reviews = get_comments( array( 'post_id' => $pid, 'status' => 'approve', 'post_type' => 'product', 'number' => 10 ) );
        $rev_html = '';
        if ( $reviews ) {
                foreach ( $reviews as $rv ) {
                        $rev_html .= '<div class="aa-review"><div class="aa-review-top"><span class="aa-av">' . esc_html( strtoupper( substr( $rv->comment_author, 0, 1 ) ) ) . '</span><div><strong>' . esc_html( $rv->comment_author ) . '</strong><div class="aa-stars aa-stars-sm">' . $stars . '</div></div><span class="aa-when">' . esc_html( human_time_diff( strtotime( $rv->comment_date_gmt ) ) . ' ago' ) . '</span></div><p>' . esc_html( wp_strip_all_tags( $rv->comment_content ) ) . '</p></div>';
                }
        } else {
                $rev_html = '<div class="aa-review aa-review-none"><span class="aa-ms">rate_review</span><p>No reviews yet — be the first to share your reading experience.</p></div>';
        }

        // Related books: same category first, newest to fill.
        $related = array();
        $rel_q   = new WP_Query(
                array(
                        'post_type'           => 'product',
                        'post_status'         => 'publish',
                        'posts_per_page'      => 8,
                        'post__not_in'        => array( $pid ),
                        'ignore_sticky_posts' => true,
                        'no_found_rows'       => true,
                        'orderby'             => 'rand',
                )
        );
        while ( $rel_q->have_posts() ) {
                $rel_q->the_post();
                if ( count( $related ) < 3 ) { $related[] = get_the_ID(); } // 3 = one full grid row
        }
        wp_reset_postdata();
        $rel_html = '';
        foreach ( $related as $rid ) { $rel_html .= aa_book_card( $rid ); }

        $description = apply_filters( 'the_content', get_post_field( 'post_content', $pid ) );
        if ( '' === trim( wp_strip_all_tags( $description ) ) ) {
                $description = '<p>' . esc_html( $name ) . ' — a carefully edited Annaadhya ebook, delivered as an instantly downloadable PDF optimized for phones, tablets, Kindle and iPads. Your purchase includes lifetime access and free future updates.</p>';
        }

        ob_start();
        ?>
<section class="aa-pdp">
        <div class="aa-wrap aa-pdp-grid">
                <div class="aa-pdp-media">
                        <div class="aa-pdp-coverwrap"><img class="aa-pdp-cover" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $name ); ?> ebook cover"></div>
                </div>
                <div class="aa-pdp-info">
                        <nav class="aa-crumb"><a href="/">Home</a><span class="aa-ms">chevron_right</span><a href="/shop/">Shop</a><span class="aa-ms">chevron_right</span><span class="aa-cur"><?php echo esc_html( $cat ? $cat : 'Ebook' ); ?></span></nav>
                        <?php if ( $badge ) : ?><span class="aa-badge aa-badge-lg"><?php echo esc_html( $badge ); ?></span><?php endif; ?>
                        <h1 class="aa-pdp-title"><?php echo esc_html( $name ); ?></h1>
                        <?php if ( $author ) : ?><p class="aa-pdp-author">by <strong><?php echo esc_html( $author ); ?></strong></p><?php endif; ?>
                        <div class="aa-pdp-rate">
                                <?php if ( $rating ) : ?>
                                        <div class="aa-stars"><?php echo $stars; // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="aa-rate"><?php echo esc_html( $rating ); ?>/5</span></div>
                                <?php endif; ?>
                                <span class="aa-dot">&middot;</span><span class="aa-fmtchip"><?php echo esc_html( $format ); ?></span>
                        </div>
                        <div class="aa-pdp-pricebox">
                                <div class="aa-pdp-price"><?php echo $price_html; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo $save_html; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
                                <div class="aa-pdp-cta">
                                        <a class="aa-btn-gold <?php echo esc_attr( $cart_cls ); ?>" data-product_id="<?php echo esc_attr( $pid ); ?>" data-quantity="1" href="<?php echo esc_url( $add_url ); ?>"><span class="aa-ms">shopping_cart</span><span>Add to cart</span></a>
                                        <?php if ( $sample_url ) : ?><a class="aa-btn-ghost" href="<?php echo esc_url( $sample_url ); ?>" target="_blank" rel="noopener"><span class="aa-ms">menu_book</span><span>Read sample</span></a><?php endif; ?>
                                        <button type="button" class="aa-wishbtn aa-wishbtn-lg<?php echo $in_wish ? ' aa-on' : ''; ?>" data-id="<?php echo esc_attr( $pid ); ?>" aria-label="Save to wishlist"><span class="aa-ms">favorite</span></button>
                                </div>
                                <ul class="aa-pdp-trust">
                                        <li><span class="aa-ms">bolt</span>Instant PDF download after checkout</li>
                                        <li><span class="aa-ms">devices</span>Reads beautifully on phone, tablet &amp; Kindle</li>
                                        <li><span class="aa-ms">update</span>Yours forever &mdash; 5 full downloads per purchase</li>
                                </ul>
                        </div>
                </div>
        </div>
        <div class="aa-wrap aa-pdp-body">
                <div class="aa-pdp-desc">
                        <h2>About this ebook</h2>
                        <div class="aa-prose"><?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
                </div>
                <aside class="aa-pdp-side">
                        <h3>Book details</h3>
                        <ul>
                                <li><span>Format</span><strong><?php echo esc_html( $format ); ?></strong></li>
                                <?php if ( $pages ) : ?><li><span>Pages</span><strong><?php echo esc_html( $pages ); ?></strong></li><?php endif; ?>
                                <li><span>Language</span><strong><?php echo esc_html( $lang ); ?></strong></li>
                                <?php if ( $cat ) : ?><li><span>Category</span><strong><?php echo esc_html( $cat ); ?></strong></li><?php endif; ?>
                                <li><span>Delivery</span><strong>Instant download</strong></li>
                                <li><span>Publisher</span><strong>Annaadhya Ebooks</strong></li>
                        </ul>
                </aside>
        </div>
        <div class="aa-wrap aa-pdp-reviews">
                <h2>Reader reviews <?php if ( $rating ) : ?><span class="aa-ratepill"><span class="aa-ms">star</span><?php echo esc_html( $rating ); ?>/5</span><?php endif; ?></h2>
                <div class="aa-review-list"><?php echo $rev_html; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
        </div>
        <?php if ( $rel_html ) : ?>
        <div class="aa-pdp-related">
                <div class="aa-wrap">
                        <h2>You may also like</h2>
                        <div class="aa-books"><ul class="aa-products"><?php echo $rel_html; // phpcs:ignore WordPress.Security.EscapeOutput ?></ul></div>
                </div>
        </div>
        <?php endif; ?>
</section>
        <?php
        return ob_get_clean();
}

/* ------------------------------------------------------------------------
 * [aa_library] — customer's purchased ebooks + purchase history.
 * ---------------------------------------------------------------------- */

add_shortcode( 'aa_library', 'aa_library_shortcode' );

function aa_library_shortcode() {
        if ( ! is_user_logged_in() ) {
                ob_start();
                ?>
<section class="aa-lib">
        <div class="aa-wrap">
                <div class="aa-lib-gate">
                        <span class="aa-ms">lock</span>
                        <h2>Your library awaits</h2>
                        <p>Sign in with the account you used at checkout to read, download and re-download every ebook you own — anytime, on any device.</p>
                        <div class="aa-lib-gate-btns">
                                <a class="aa-btn-gold" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'myaccount' ) ) ); ?>"><span class="aa-ms">login</span><span>Sign in</span></a>
                                <a class="aa-btn-ghost" href="/shop/"><span>Browse the Bookshelf</span></a>
                        </div>
                </div>
        </div>
</section>
                <?php
                return ob_get_clean();
        }

        $uid       = get_current_user_id();
        $downloads = function_exists( 'wc_get_customer_available_downloads' ) ? wc_get_customer_available_downloads( $uid ) : array();
        $orders    = function_exists( 'wc_get_orders' ) ? wc_get_orders( array( 'customer' => $uid, 'limit' => 24 ) ) : array();

        // Raw permissions table: usage counters + exhausted grants (not in the
        // "available" list) so the library can show re-buy states.
        global $wpdb;
        $perm = array();
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'woocommerce_downloadable_product_permissions' ) ) ) {
                $rows = $wpdb->get_results( $wpdb->prepare( "SELECT download_id, product_id, order_id, download_count, downloads_remaining, access_granted FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE user_id = %d ORDER BY order_id DESC", $uid ) );
                foreach ( (array) $rows as $r ) { $perm[ $r->download_id ] = $r; }
        }
        $active_ids    = array();
        $exhausted_ids = array();
        foreach ( $downloads as $dl ) { $active_ids[] = $dl['download_id']; }
        foreach ( $perm as $pid => $r ) {
                if ( in_array( $pid, $active_ids, true ) ) { continue; }
                if ( '' !== $r->downloads_remaining && (int) $r->downloads_remaining < 1 && ! in_array( $r->product_id, $exhausted_ids, true ) ) {
                        $exhausted_ids[] = $r->product_id;
                }
        }
        $exhausted = array();
        foreach ( $exhausted_ids as $epid ) {
                $p = wc_get_product( $epid );
                if ( $p ) { $exhausted[] = $p; }
        }

        ob_start();
        ?>
<style>
.aa-dl-banner{display:flex;gap:14px;align-items:flex-start;background:linear-gradient(90deg,rgba(245,158,11,.12),rgba(245,158,11,.03));border:1px solid rgba(245,158,11,.4);border-radius:14px;padding:18px 22px;margin:0 0 26px}
.aa-dl-banner .aa-ms{color:var(--aa-goldbg);font-size:24px;margin-top:2px}
.aa-dl-banner strong{display:block;color:var(--aa-tx);font-size:15px;margin-bottom:4px}
.aa-dl-banner p{color:var(--aa-warm);font-size:13.5px;line-height:1.65;margin:0}
.aa-lib-card{background:var(--aa-low);border:1px solid var(--aa-line);border-radius:14px;padding:18px;display:flex;gap:16px}
.aa-lib-card img{width:84px;height:112px;object-fit:cover;border-radius:8px;flex-shrink:0}
.aa-lib-card-mid{flex:1;min-width:0}
.aa-lib-card-mid h3{color:var(--aa-tx);font-size:15.5px;font-weight:700;margin:0 0 3px}
.aa-lib-card-mid .aa-lib-sub{color:var(--aa-dim);font-size:12.5px;margin:0 0 12px}
.aa-dl-meter{margin-bottom:10px}
.aa-dl-meter-top{display:flex;justify-content:space-between;align-items:center;font-size:12px;margin-bottom:6px}
.aa-dl-meter-top span:first-child{color:var(--aa-warm);font-weight:600}
.aa-dl-chip{background:var(--aa-cont);border:1px solid var(--aa-line);color:var(--aa-goldbg);border-radius:999px;padding:2px 10px;font-weight:700;font-size:11px;white-space:nowrap}
.aa-dl-chip.aa-out{color:#ff8a80;border-color:rgba(255,138,128,.4)}
.aa-dl-bar{height:6px;background:var(--aa-cont);border-radius:999px;overflow:hidden}
.aa-dl-bar i{display:block;height:100%;background:linear-gradient(90deg,var(--aa-goldbg),var(--aa-gold));border-radius:999px}
.aa-dl-bar i.aa-full{background:linear-gradient(90deg,#b3543f,#ff8a80)}
.aa-lib-card-act{display:flex;flex-direction:column;justify-content:center;gap:8px;flex-shrink:0}
.aa-lib-out{color:#ff8a80;font-size:12px;font-weight:700;display:flex;align-items:center;gap:6px}
.aa-lib-btn{display:inline-flex;align-items:center;gap:8px;background:var(--aa-goldbg);color:var(--aa-ongold);font:700 13px/1 inherit;font-family:inherit;border:0;border-radius:10px;padding:12px 18px;text-decoration:none!important;white-space:nowrap}
.aa-lib-btn:hover{background:var(--aa-gold)}
.aa-lib-btn.aa-ghost{background:transparent;border:1px solid var(--aa-goldbg);color:var(--aa-goldbg)}
.aa-lib-btn.aa-ghost:hover{background:rgba(245,158,11,.1)}
@media(max-width:640px){.aa-lib-card{flex-wrap:wrap}.aa-lib-card-act{width:100%;flex-direction:row;align-items:center;justify-content:space-between}}
</style>
<section class="aa-lib">
        <div class="aa-wrap">
                <?php echo function_exists( 'aa_verify_banner_html' ) ? aa_verify_banner_html() : ''; ?>
                <div class="aa-dl-banner">
                        <span class="aa-ms">cloud_download</span>
                        <div>
                                <strong>Every purchase includes 5 full PDF downloads</strong>
                                <p>Used all 5? Buy again to restore access &mdash; your books never expire, only the download counter does. Open on any device, keep forever.</p>
                        </div>
                </div>
                <?php if ( $downloads || $exhausted ) : ?>
                <div class="aa-lib-grid" style="grid-template-columns:1fr;gap:14px">
                        <?php foreach ( $downloads as $dl ) :
                                $r       = isset( $perm[ $dl['download_id'] ] ) ? $perm[ $dl['download_id'] ] : null;
                                $product = wc_get_product( $dl['product_id'] );
                                $cover   = $product ? ( get_the_post_thumbnail_url( $dl['product_id'], 'medium' ) ?: wc_placeholder_img_src() ) : wc_placeholder_img_src();
                                $limit   = $product ? (int) $product->get_download_limit() : 5;
                                $rem     = $dl['downloads_remaining'];
                                $unlim   = ( '' === $rem || $limit < 1 );
                                $remn    = $unlim ? 0 : (int) $rem;
                                $used    = $unlim ? ( $r ? (int) $r->download_count : 0 ) : max( 0, $limit - $remn );
                                $pct     = $unlim ? 0 : ( $limit ? min( 100, round( $used / $limit * 100 ) ) : 0 );
                                $author  = $product ? (string) get_post_meta( $dl['product_id'], '_aa_author', true ) : '';
                                $granted = $r && $r->access_granted ? mysql2date( 'M j, Y', $r->access_granted ) : '';
                                ?>
                        <div class="aa-lib-card">
                                <img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( $dl['product_name'] ); ?> cover">
                                <div class="aa-lib-card-mid">
                                        <h3><?php echo esc_html( $dl['product_name'] ); ?></h3>
                                        <p class="aa-lib-sub"><?php echo $author ? esc_html( $author ) . ' &middot; ' : ''; ?><?php echo $granted ? 'Delivered ' . esc_html( $granted ) : 'PDF'; ?> &middot; yours forever</p>
                                        <div class="aa-dl-meter">
                                                <div class="aa-dl-meter-top">
                                                        <span><?php echo $unlim ? 'Unlimited re-downloads' : esc_html( $used ) . ' of ' . esc_html( $limit ) . ' downloads used'; ?></span>
                                                        <span class="aa-dl-chip"><?php echo $unlim ? '∞' : esc_html( $remn ) . ' left'; ?></span>
                                                </div>
                                                <?php if ( ! $unlim ) : ?><div class="aa-dl-bar"><i style="width:<?php echo esc_attr( $pct ); ?>%"></i></div><?php endif; ?>
                                        </div>
                                </div>
                                <div class="aa-lib-card-act">
                                        <a class="aa-lib-btn" href="<?php echo esc_url( $dl['download_url'] ); ?>"><span class="aa-ms">download</span><span>Download PDF</span></a>
                                </div>
                        </div>
                        <?php endforeach; ?>
                        <?php foreach ( $exhausted as $p ) :
                                $cover = get_the_post_thumbnail_url( $p->get_id(), 'medium' ) ?: wc_placeholder_img_src();
                                $price = wp_kses_post( wc_price( (float) $p->get_price( 'edit' ) ) );
                                ?>
                        <div class="aa-lib-card" style="border-color:rgba(255,138,128,.35)">
                                <img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( $p->get_name() ); ?> cover">
                                <div class="aa-lib-card-mid">
                                        <h3><?php echo esc_html( $p->get_name() ); ?></h3>
                                        <p class="aa-lib-sub"><?php echo esc_html( (string) get_post_meta( $p->get_id(), '_aa_author', true ) ); ?> &middot; PDF &middot; yours forever</p>
                                        <div class="aa-dl-meter">
                                                <div class="aa-dl-meter-top">
                                                        <span>5 of 5 downloads used</span>
                                                        <span class="aa-dl-chip aa-out">0 left</span>
                                                </div>
                                                <div class="aa-dl-bar"><i class="aa-full" style="width:100%"></i></div>
                                        </div>
                                </div>
                                <div class="aa-lib-card-act">
                                        <span class="aa-lib-out"><span class="aa-ms">block</span>Downloads used up</span>
                                        <a class="aa-lib-btn aa-ghost" href="<?php echo esc_url( add_query_arg( 'add-to-cart', $p->get_id(), get_permalink( $p->get_id() ) ) ); ?>"><span class="aa-ms">shopping_cart</span><span>Buy again &middot; <?php echo $price; ?></span></a>
                                </div>
                        </div>
                        <?php endforeach; ?>
                </div>
                <?php else : ?>
                <div class="aa-lib-gate aa-lib-gate-lite">
                        <span class="aa-ms">auto_stories</span>
                        <h2>No books yet</h2>
                        <p>Every ebook you purchase lands here as an instant, re-downloadable PDF — with 5 full downloads included.</p>
                        <a class="aa-btn-gold" href="/shop/"><span>Find your first read</span><span class="aa-ms">arrow_forward</span></a>
                </div>
                <?php endif; ?>

                <h2 class="aa-lib-h2">Purchase history</h2>
                <?php if ( $orders ) : ?>
                <div class="aa-lib-tablewrap">
                <table class="aa-lib-table">
                        <thead><tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ( $orders as $order ) : ?>
                        <tr>
                                <td><a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">#<?php echo esc_html( $order->get_order_number() ); ?></a></td>
                                <td><?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date_i18n( 'M j, Y' ) : '' ); ?></td>
                                <td><?php echo esc_html( count( $order->get_items() ) ); ?> item<?php echo count( $order->get_items() ) === 1 ? '' : 's'; ?></td>
                                <td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
                                <td><span class="aa-status aa-status-<?php echo esc_attr( $order->get_status() ); ?>"><?php echo esc_html( ucfirst( $order->get_status() ) ); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                </table>
                </div>
                <?php else : ?>
                <p class="aa-lib-none">No orders yet — your purchase history will appear here after checkout.</p>
                <?php endif; ?>
        </div>
</section>
        <?php
        return ob_get_clean();
}

/* ------------------------------------------------------------------------
 * Download-limit guard message (when a used-up file URL is hit directly).
 * Woo's handler wp_die()s a fixed string with no message filter, so we
 * swap the translatable string instead.
 * ---------------------------------------------------------------------- */

add_filter( 'gettext', 'aa_download_limit_message', 10, 3 );

function aa_download_limit_message( $translation, $text, $domain ) {
        if ( 'woocommerce' === $domain && 'Sorry, you have reached your download limit for this file' === $text ) {
                $lib = get_permalink( get_page_by_path( 'library' ) );
                return 'You have used all 5 downloads for this book. Buy it again to restore access — the book itself never expires.' . ( $lib ? ' <a href="' . esc_url( $lib ) . '">Open My Library</a>' : '' );
        }
        return $translation;
}

/* ------------------------------------------------------------------------
 * Contact message CPT + [aa_contact_form].
 * ---------------------------------------------------------------------- */

add_action( 'init', 'aa_register_message_cpt' );

function aa_register_message_cpt() {
        register_post_type(
                'aa_message',
                array(
                        'labels'       => array(
                                'name'          => 'Contact Messages',
                                'singular_name' => 'Contact Message',
                        ),
                        'public'       => false,
                        'show_ui'      => false,
                        'show_in_rest' => false,
                        'supports'     => array( 'title', 'editor', 'custom-fields' ),
                        'capability_type' => 'post',
                )
        );
}

add_shortcode( 'aa_contact_form', 'aa_contact_form_shortcode' );

function aa_contact_form_shortcode() {
        ob_start();
        ?>
<form class="aa-cform" data-aa-cform novalidate>
        <input type="text" name="aa_hp" value="" tabindex="-1" aria-hidden="true" style="position:absolute;left:-9999px;height:0;width:0;opacity:0">
        <div class="aa-cform-grid">
                <label class="aa-field"><span>Your name *</span><input type="text" name="name" placeholder="Ananya Sharma" required></label>
                <label class="aa-field"><span>Email address *</span><input type="email" name="email" placeholder="you@example.com" required></label>
        </div>
        <label class="aa-field"><span>How can we help?</span>
                <select name="topic">
                        <option value="Order support">Order support</option>
                        <option value="Download help">Download help</option>
                        <option value="Refund request">Refund request</option>
                        <option value="Author submission">Author submission</option>
                        <option value="Other">Something else</option>
                </select>
        </label>
        <label class="aa-field"><span>Message *</span><textarea name="message" rows="6" placeholder="Tell us what happened — include your order number for order-related questions." required></textarea></label>
        <p class="aa-cform-done" data-aa-cform-done hidden><span class="aa-ms">check_circle</span>Message received — our support team replies within one working day.</p>
        <p class="aa-cform-err" data-aa-cform-err hidden></p>
        <button class="aa-btn-gold" type="submit"><span class="aa-ms">send</span><span>Send message</span></button>
</form>
<script>
(function(){
        var f=document.querySelector("[data-aa-cform]");
        if(!f)return;
        f.addEventListener("submit",function(e){
                e.preventDefault();
                var done=f.querySelector("[data-aa-cform-done]"),err=f.querySelector("[data-aa-cform-err]");
                if(done)done.hidden=true;if(err)err.hidden=true;
                var fd=new FormData(f);fd.append("action","aa_contact_submit");
                fetch("/wp-admin/admin-ajax.php",{method:"POST",body:fd})
                .then(function(r){return r.json()})
                .then(function(d){
                        if(d&&d.status==="ok"){
                                f.reset();
                                if(done)done.hidden=false;
                                if(window.aaToast)window.aaToast("Message sent!");
                        }else{
                                if(err){err.hidden=false;err.textContent=(d&&d.msg)||"Please check the form and try again."}
                        }
                }).catch(function(){if(err){err.hidden=false;err.textContent="Network error — please try again."}});
        });
})();
</script>
        <?php
        return ob_get_clean();
}

/* Public AJAX: contact + newsletter (honeypot-guarded, no nonce needed for guests). */

add_action( 'wp_ajax_aa_contact_submit', 'aa_contact_submit_ajax' );
add_action( 'wp_ajax_nopriv_aa_contact_submit', 'aa_contact_submit_ajax' );

function aa_contact_submit_ajax() {
        $hp = isset( $_POST['aa_hp'] ) ? sanitize_text_field( wp_unslash( $_POST['aa_hp'] ) ) : '';
        if ( '' !== $hp ) { wp_send_json( array( 'status' => 'ok' ) ); }

        $name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $topic = isset( $_POST['topic'] ) ? sanitize_text_field( wp_unslash( $_POST['topic'] ) ) : 'Other';
        $msg   = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

        $topics = array( 'Order support', 'Download help', 'Refund request', 'Author submission', 'Other' );
        if ( ! in_array( $topic, $topics, true ) ) { $topic = 'Other'; }

        if ( '' === $name || '' === $msg || ! is_email( $email ) ) {
                wp_send_json( array( 'status' => 'error', 'msg' => 'Please fill your name, a valid email and a short message.' ), 400 );
        }

        $post_id = wp_insert_post(
                array(
                        'post_type'    => 'aa_message',
                        'post_title'   => '[' . $topic . '] ' . $name,
                        'post_content' => $msg,
                        'post_status'  => 'publish',
                ),
                true
        );
        if ( is_wp_error( $post_id ) ) {
                wp_send_json( array( 'status' => 'error', 'msg' => 'Could not save your message. Please try again.' ), 500 );
        }
        update_post_meta( $post_id, '_aa_msg_name', $name );
        update_post_meta( $post_id, '_aa_msg_email', $email );
        update_post_meta( $post_id, '_aa_msg_topic', $topic );
        update_post_meta( $post_id, '_aa_msg_read', 0 );
        wp_send_json( array( 'status' => 'ok' ) );
}

add_action( 'wp_ajax_aa_newsletter_submit', 'aa_newsletter_submit_ajax' );
add_action( 'wp_ajax_nopriv_aa_newsletter_submit', 'aa_newsletter_submit_ajax' );

function aa_newsletter_submit_ajax() {
        $hp = isset( $_POST['hp'] ) ? sanitize_text_field( wp_unslash( $_POST['hp'] ) ) : '';
        if ( '' !== $hp ) { wp_send_json( array( 'status' => 'ok' ) ); }

        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        if ( ! is_email( $email ) ) {
                wp_send_json( array( 'status' => 'error', 'msg' => 'Please enter a valid email address.' ), 400 );
        }
        $subs = get_option( 'aa_subscribers', array() );
        if ( ! is_array( $subs ) ) { $subs = array(); }
        foreach ( $subs as $s ) {
                if ( isset( $s['email'] ) && 0 === strcasecmp( $s['email'], $email ) ) {
                        wp_send_json( array( 'status' => 'ok', 'dup' => true ) );
                }
        }
        $subs[] = array( 'email' => $email, 'time' => time() );
        update_option( 'aa_subscribers', $subs, false );
        wp_send_json( array( 'status' => 'ok' ) );
}
