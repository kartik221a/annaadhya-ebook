<?php
/**
 * Build all 10 storefront page groups from the Stitch "Obsidian & Gold Lit"
 * design system: Home, Shop, Product details, Cart, Checkout, My Library,
 * My Account, About, Contact & Support, and the 5 legal pages.
 *
 * Run:  wp eval 'require "tools/build_pages_v2.php";'
 */

require __DIR__ . '/set_stitch_pages.php';   /* shared tokens/nav/footer + rebuilds Home & Shop */
require __DIR__ . '/stitch_sections_v2.php'; /* v2 section library                              */

/* ---------------------------------------------------------------- helpers */

/** Page header band (crumb + title + sub). Self-contained CSS. */
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

/** Upsert a WordPress page, flag it for the canvas shell, build Elementor data. */
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

/* ---------------------------------------------------------------- 4. Cart */

aa_upsert_page( 'cart', 'Cart', array(
	$aa_common_html . aa_nav_html( '' ),
	aa_page_head( 'Cart', 'Your <span class="aa-grad">Cart</span>', 'Review your picks &mdash; instant PDF download the moment you check out.' ),
	$aa_woo_css . '<section class="aa-woopage">[woocommerce_cart]</section>',
	aa_footer_html(),
), '[woocommerce_cart]' );

/* ------------------------------------------------------------ 5. Checkout */

aa_upsert_page( 'checkout', 'Checkout', array(
	$aa_common_html . aa_nav_html( '' ),
	aa_page_head( 'Checkout', 'Secure <span class="aa-grad">Checkout</span>', '<span class="aa-ms" style="font-size:16px;color:var(--aa-goldbg);vertical-align:-3px">lock</span> 256-bit encrypted &middot; UPI &amp; RuPay ready &middot; download link delivered instantly' ),
	$aa_woo_css . '<section class="aa-woopage">[woocommerce_checkout]</section>',
	aa_footer_html(),
), '[woocommerce_checkout]' );

/* ---------------------------------------------------------- 7. My Account */

aa_upsert_page( 'my-account', 'My Account', array(
	$aa_common_html . aa_nav_html( 'account' ),
	aa_page_head( 'My Account', 'My <span class="aa-grad">Account</span>', 'Sign in, track orders, manage downloads and keep your wishlist handy.' ),
	$aa_woo_css . '<section class="aa-woopage">[woocommerce_my_account]</section>',
	aa_footer_html(),
), '[woocommerce_my_account]' );

/* ---------------------------------------------------------- 6. My Library */

aa_upsert_page( 'library', 'My Library', array(
	$aa_common_html . aa_nav_html( 'library' ),
	aa_page_head( 'My Library', 'My <span class="aa-grad">Library</span>', 'Every ebook you own &mdash; re-download anytime, read on any device, yours forever.' ),
	$aa_lib_css . '<section class="aa-lib-wrap">[aa_library]</section>',
	$aa_lib_perks,
	aa_footer_html(),
), '[aa_library]' );

/* ------------------------------------------------------------- 3. Product */

$pd_id = aa_upsert_page( 'aa-product-single', 'Product Single', array(
	$aa_common_html . aa_nav_html( '' ),
	$aa_pdp_css . '<section class="aa-pdp-shell">[aa_product_single]</section>',
	aa_footer_html(),
), '[aa_product_single]', array( 'status' => 'private' ) );
if ( $pd_id ) { update_option( 'aa_product_page', $pd_id ); }

/* ------------------------------------------------------------- 8. About */

aa_upsert_page( 'about', 'About Us', array(
	$aa_common_html . aa_nav_html( 'about' ),
	$aa_about_hero,
	$aa_about_mission,
	$aa_about_values,
	$aa_about_authors,
	$aa_about_stats,
	aa_footer_html(),
), 'The story, philosophy and people behind Annaadhya Ebooks.' );

/* ------------------------------------------------- 9. Contact & Support */

aa_upsert_page( 'contact', 'Contact & Support', array(
	$aa_common_html . aa_nav_html( 'contact' ),
	aa_page_head( 'Contact & Support', 'We&rsquo;re <span class="aa-grad">here to help</span>', 'Real humans, one working day, every order and download question answered.' ),
	$aa_contact_css . $aa_contact_main,
	$aa_contact_faq,
	aa_footer_html(),
), 'Contact the Annaadhya support team: order help, download assistance, refunds and author submissions. Plus FAQs for instant answers.' );

/* ------------------------------------------------------- 10. Legal pages */

$legal_pages = array(
	'privacy-policy'          => array( 'Privacy Policy', 'shield', 'How we collect, use and protect your data &mdash; written in plain language, aligned with India&rsquo;s DPDP Act 2023.' ),
	'terms'                   => array( 'Terms & Conditions', 'description', 'The agreement between you and Annaadhya Ebooks when you browse, buy and read on our store.' ),
	'refund-policy'           => array( 'Refund Policy', 'currency_rupee', 'When digital goods go wrong, we make it right. Simple rules, fast refunds.' ),
	'digital-delivery-policy' => array( 'Digital Product Delivery Policy', 'cloud_download', 'How your ebook reaches your device seconds after payment &mdash; and what to do if a link ever fails.' ),
	'copyright-policy'        => array( 'Copyright Policy', 'copyright', 'Who owns what on Annaadhya, how you may use your ebooks, and how we fight piracy.' ),
);

foreach ( $legal_pages as $slug => $meta ) {
	list( $title, $icon, $sub ) = $meta;
	$file = __DIR__ . '/legal-content/' . $slug . '.php';
	if ( ! file_exists( $file ) ) {
		echo 'ERR missing legal content: ' . $slug . "\n";
		continue;
	}
	$body = include $file;

	aa_upsert_page( $slug, $title, array(
		$aa_common_html . aa_nav_html( '' ),
		aa_page_head( $title, 'Annaadhya <span class="aa-grad">' . $title . '</span>', $sub ),
		$aa_legal_css . '<section class="aa-legal"><div class="aa-legal-in"><div class="aa-legal-meta"><span><span class="aa-ms">event</span>Last updated: November 15, 2026</span><span><span class="aa-ms">' . $icon . '</span>Annaadhya Ebooks</span></div>' . $body . '</div>' . aa_legal_related() . '</section>',
		aa_footer_html(),
	), 'The official ' . $title . ' of Annaadhya Ebooks.' );
}

echo "PAGES-V2-OK\n";
