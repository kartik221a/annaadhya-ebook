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

/** Gold "check your email" note on the order-received screen. */
add_filter( 'woocommerce_thankyou_order_received_text', 'aa_thankyou_email_note', 20, 2 );

function aa_thankyou_email_note( $text, $order ) {
        $email = $order ? $order->get_billing_email() : '';
        if ( $email ) {
                $text .= ' <span class="aa-thanks-email" style="display:inline-flex;align-items:center;gap:8px;margin-left:12px;padding:10px 18px;border-radius:10px;background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.4);color:var(--aa-goldbg,#f59e0b);font-weight:700"><span class="aa-ms">mark_email_read</span><span>Your PDF is on its way to ' . esc_html( $email ) . '</span></span>';
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
        ob_start();
        ?>
<li class="aa-card" data-price="<?php echo esc_attr( $active ); ?>" data-title="<?php echo esc_attr( $name ); ?>">
        <div class="aa-media">
                <a class="aa-media-link" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $name ); ?>">
                        <img class="aa-cover" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $name ); ?> ebook cover">
                </a>
                <button type="button" class="aa-wishbtn" data-id="<?php echo esc_attr( $product_id ); ?>" aria-label="Save to wishlist"><span class="aa-ms">favorite</span></button>
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
 * Wishlist — browser-local (no accounts anywhere on the store). The heart
 * buttons in aa_footer_html() read/write localStorage; this AJAX endpoint
 * turns the saved ids back into Stitch book cards for /my-account/.
 * ---------------------------------------------------------------------- */

add_action( 'wp_ajax_aa_wishlist_render', 'aa_wishlist_render_ajax' );
add_action( 'wp_ajax_nopriv_aa_wishlist_render', 'aa_wishlist_render_ajax' );

function aa_wishlist_render_ajax() {
        $ids = isset( $_POST['ids'] ) ? array_map( 'absint', (array) $_POST['ids'] ) : array();
        $ids = array_values( array_unique( array_filter( array_slice( $ids, 0, 60 ) ) ) );
        $html = '';
        if ( $ids ) {
                $q = new WP_Query(
                        array(
                                'post_type'           => 'product',
                                'post_status'         => 'publish',
                                'post__in'            => $ids,
                                'orderby'             => 'post__in',
                                'posts_per_page'      => count( $ids ),
                                'ignore_sticky_posts' => true,
                        )
                );
                while ( $q->have_posts() ) {
                        $q->the_post();
                        $html .= aa_book_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput
                }
                wp_reset_postdata();
        }
        wp_send_json( array( 'status' => 'ok', 'count' => count( $ids ), 'html' => $html ) );
}

/* ------------------------------------------------------------------------
 * [aa_wishlist_page] — the /my-account/ screen. There is no sign-in any
 * more: the page renders the browser's saved wishlist, and the few
 * WooCommerce endpoints that still exist (orders, view-order...) keep
 * their normal chrome.
 * ---------------------------------------------------------------------- */

add_shortcode( 'aa_wishlist_page', 'aa_wishlist_page_shortcode' );

function aa_wishlist_page_shortcode() {
        if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url() ) {
                return '<div class="aa-woopage">' . do_shortcode( '[woocommerce_my_account]' ) . '</div>';
        }

        ob_start();
        ?>
<style>
.aa-wishpage{padding:48px 0 80px}
.aa-wishpage .aa-wrap{max-width:var(--aa-wrapw,1180px);margin:0 auto;padding:0 24px}
.aa-wish-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:22px;list-style:none;padding:0;margin:0}
.aa-account-empty{background:var(--aa-surface);border:1px solid var(--aa-line);border-radius:16px;padding:56px 28px;text-align:center}
.aa-account-empty .aa-ms{font-size:40px;color:var(--aa-goldbg)}
.aa-account-empty h3{color:var(--aa-tx);font-size:19px;margin:14px 0 8px}
.aa-account-empty p{color:var(--aa-dim);font-size:14px;line-height:1.7;margin:0 auto 22px;max-width:420px}
.aa-btn-gold{display:inline-flex;align-items:center;gap:8px;background:var(--aa-goldbg,#f59e0b);color:var(--aa-ongold,#472a00);font-weight:700;text-decoration:none!important;border-radius:10px;padding:13px 22px;font-size:14px}
.aa-wish-loading{color:var(--aa-dim);font-size:14px;padding:26px 0}
</style>
<section class="aa-wishpage">
        <div class="aa-wrap">
                <div data-aa-wishgrid><p class="aa-wish-loading">Loading your wishlist&hellip;</p></div>
        </div>
</section>
<script>
(function(){
        function ids(){try{return JSON.parse(localStorage.getItem("aa_wishlist")||"[]").map(Number)}catch(e){return[]}}
        var grid=document.querySelector("[data-aa-wishgrid]");
        function empty(){
                grid.innerHTML='<div class="aa-account-empty"><span class="aa-ms">favorite</span><h3>Nothing saved yet</h3><p>Tap the heart on any book cover to keep it here for later.</p><a class="aa-btn-gold" href="/shop/">Browse the Bookshelf</a></div>';
        }
        function mark(){
                var list=ids();
                grid.querySelectorAll(".aa-wishbtn").forEach(function(b){
                        b.classList.toggle("aa-on",list.indexOf(parseInt(b.getAttribute("data-id")||"0",10))>=0);
                });
        }
        function render(){
                var list=ids();
                if(!list.length){empty();return}
                var fd=new FormData();list.forEach(function(i){fd.append("ids[]",i)});
                fetch("/wp-admin/admin-ajax.php",{method:"POST",body:fd,credentials:"same-origin"})
                        .then(function(r){return r.json()})
                        .then(function(d){
                                if(d&&d.status==="ok"&&d.html){grid.innerHTML='<div class="aa-books"><ul class="aa-products aa-wish-grid">'+d.html+'</ul></div>';mark()}
                                else{empty()}
                        })
                        .catch(empty);
        }
        document.addEventListener("aa-wishlist-change",function(){render()});
        render();
})();
</script>
        <?php
        return ob_get_clean();
}

/* ------------------------------------------------------------------------
 * No accounts: hide the "Returning customer? Click here to login" block on
 * checkout — a guest only leaves a name and an email.
 * ---------------------------------------------------------------------- */

add_action( 'wp_loaded', 'aa_drop_checkout_login_prompt' );

function aa_drop_checkout_login_prompt() {
        remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
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
                                        <button type="button" class="aa-wishbtn aa-wishbtn-lg" data-id="<?php echo esc_attr( $pid ); ?>" aria-label="Save to wishlist"><span class="aa-ms">favorite</span></button>
                                </div>
                                <ul class="aa-pdp-trust">
                                        <li><span class="aa-ms">bolt</span>PDF lands in your email right after checkout</li>
                                        <li><span class="aa-ms">devices</span>Reads beautifully on phone, tablet &amp; Kindle</li>
                                        <li><span class="aa-ms">update</span>Yours forever &mdash; unlimited downloads, no account needed</li>
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
                                <li><span>Delivery</span><strong>Instant PDF by email</strong></li>
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
 * [aa_library] — legacy "My Library" page. There are no accounts any more:
 * books are delivered straight to the buyer's email after payment, so the
 * page now simply explains the flow.
 * ---------------------------------------------------------------------- */

add_shortcode( 'aa_library', 'aa_library_shortcode' );

function aa_library_shortcode() {
        $shop = get_permalink( wc_get_page_id( 'shop' ) );
        ob_start();
        ?>
<section class="aa-lib">
        <div class="aa-wrap">
                <div class="aa-lib-gate">
                        <span class="aa-ms">mark_email_read</span>
                        <h2>Your books arrive by email</h2>
                        <p>Annaadhya is fully account-free: buy any ebook as a guest, and seconds after payment the PDF files are attached to a delivery email sent to the address you used at checkout. The email is your receipt too &mdash; keep it, and re-download from its links whenever you like.</p>
                        <div class="aa-lib-gate-btns">
                                <a class="aa-btn-gold" href="<?php echo esc_url( $shop ); ?>"><span class="aa-ms">storefront</span><span>Browse the Bookshelf</span></a>
                        </div>
                </div>
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
                return 'This download link has stopped working. Open your delivery email and download the PDF again — the links there never expire.';
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
