<?php
/**
 * One-shot page updater for the account-free flow:
 *  1. every canvas page gets the NEW header (no Library/Account links) and
 *     footer (Help column, localStorage wishlist JS) from stitch_lib.php;
 *  2. stale content widgets get exact-string copy replacements;
 *  3. page 8 (My Account) becomes the wishlist page, page 56 the
 *     "books by email" explainer, legal/FAQ copy matches email delivery.
 *
 * Run:  wp eval-file ../tools/update_nav.php   (from site/)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/stitch_lib.php';

/* old-nav active detection: which link carries class="aa-on"? */
function aa_detect_active( $html ) {
        if ( strpos( $html, 'class="aa-on">Home<' ) !== false ) { return 'home'; }
        if ( strpos( $html, 'class="aa-on">Shop<' ) !== false ) { return 'shop'; }
        if ( strpos( $html, 'class="aa-on">About<' ) !== false ) { return 'about'; }
        if ( strpos( $html, 'class="aa-on">Contact<' ) !== false ) { return 'contact'; }
        if ( strpos( $html, 'aa-on"><span class="aa-ms">auto_stories</span><span>Library</span>' ) !== false ) { return ''; } // library tab is gone
        if ( preg_match( '/class="aa-on"[^>]*><span class="aa-ms">person<\/span><span>Account<\/span>/' , $html ) ) { return 'account'; }
        return '';
}

/* exact copy replacements applied to every widget */
$REPL = array(
        /* page 56 — My Library head */
        '<span class="aa-cur">My Library</span></nav><h1>My <span class="aa-grad">Library</span></h1><p class="aa-chead-sub">Every ebook you own &mdash; 5 full downloads per purchase, re-download anytime, on any device.</p>'
        => '<span class="aa-cur">Books by Email</span></nav><h1>Books by <span class="aa-grad">Email</span></h1><p class="aa-chead-sub">No account needed &mdash; after checkout the PDF files land in your inbox instantly, attachments included.</p>',

        /* page 8 — My Account head + shortcode */
        'Sign in with Google or email &mdash; your ebooks stay in your account forever.'
        => 'Books you hearted are saved right in your browser &mdash; tap the heart on any cover to add one.',
        '[aa_auth]' => '[aa_wishlist_page]',

        /* page 61 — terms */
        'your download becomes available in <strong>My Library</strong> and by email.'
        => 'your PDF is delivered by email, attachment included.',

        /* page 7 — checkout head */
        'downloads appear in My Library instantly' => 'your PDFs arrive in your email instantly',

        /* page 63 — digital delivery policy */
        'Your download is ready <strong>seconds after payment</strong> &mdash; in My Library and by email.'
        => 'Your download is ready <strong>seconds after payment</strong> &mdash; delivered by email, PDF attached.',
        '<h2>3. Download limits &mdash; the 5-download rule</h2>'
        => '<h2>3. Downloads &mdash; no limits</h2>',
        'your ebook is added to <strong><a href="/library/">My Library</a></strong> under your account, and an order email with your download link is sent to the address you used at checkout.'
        => 'the PDF is attached to a delivery email sent to the address you used at checkout.',
        'Each purchase of an ebook includes <strong>5 full downloads</strong> of that title&rsquo;s PDF. Every completed download counts once, so you can keep a copy on your phone, tablet and desktop with plenty of headroom for re-downloads. A live counter (&ldquo;3 of 5 downloads used&rdquo;) is always visible next to each book in <a href="/library/">My Library</a>.'
        => 'Each purchase of an ebook includes the PDF of that title, delivered as an attachment to a branded email. The email stays in your inbox forever, so you can keep a copy on your phone, tablet and desktop and re-download from it any time. Permanent download links for your order are also available on the order page linked from your confirmation email.',
        '<p><strong>Used all 5?</strong> The book is not gone: it stays in your account, but new downloads stop until access is restored. Buy the title again (one click on the &ldquo;Buy again&rdquo; button in My Library) and a fresh set of 5 downloads is granted instantly &mdash; at the current listed price. We do not re-issue free downloads once a counter is exhausted, because each download is a full copy of the file.</p>'
        => '<p><strong>Lost the email?</strong> The book is not gone: every order keeps permanent download links on its order page, and support can re-send the delivery email any time &mdash; just ask via the contact form with your order number. We never cap the number of downloads.</p>',
        'An internet connection to download, a PDF reader to read, and the email address you paid with to access My Library. That&rsquo;s the entire technical requirement list.'
        => 'An internet connection to download, a PDF reader to read, and an email address to receive your books. That&rsquo;s the entire technical requirement list.',

        /* page 26 — contact FAQ */
        'Open <a href="/library/" style="color:var(--aa-gold)">My Library</a> and sign in with the email you used at checkout &mdash; your ebooks appear there within seconds of payment. Still nothing? Send us a message with your order number and we will fix it the same day.'
        => 'Seconds after payment the PDF is attached to a delivery email sent to the address you used at checkout &mdash; check spam first, then send us a message with your order number and we will re-send it the same day.',
        'Of course. Downloads never expire from My Library, and the account page can issue fresh links any time. See our <a href="/digital-delivery-policy/" style="color:var(--aa-gold)">Digital Delivery Policy</a> for the details.'
        => 'Of course. The links in your delivery email never expire and there is no download limit &mdash; open the order page from your confirmation email and download again any time. See our <a href="/digital-delivery-policy/" style="color:var(--aa-gold)">Digital Delivery Policy</a> for the details.',
);

$pages = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) );
$nav_done = 0; $foot_done = 0; $repl_done = 0;

foreach ( $pages as $pid ) {
        $raw = get_post_meta( $pid, '_elementor_data', true );
        if ( ! $raw ) { continue; }
        $data = json_decode( $raw, true );
        if ( ! is_array( $data ) ) { echo "#$pid DECODE-FAIL, skipped\n"; continue; }

        $changed = false;
        foreach ( $data as $si => $sec ) {
                if ( empty( $sec['elements'] ) ) { continue; }
                foreach ( $sec['elements'] as $ci => $col ) {
                        if ( empty( $col['elements'] ) ) { continue; }
                        foreach ( $col['elements'] as $wi => $w ) {
                                if ( ( $w['widgetType'] ?? '' ) !== 'html' ) { continue; }
                                $h = $w['settings']['html'] ?? '';
                                $new = $h;
                                /* 1. header widget -> fresh nav */
                                if ( strpos( $h, '<header class="aa-nav"' ) !== false ) {
                                        $new = $aa_common_html . aa_nav_html( aa_detect_active( $h ) );
                                        if ( $new !== $h ) { $nav_done++; $changed = true; }
                                }
                                /* 2. footer widget -> fresh footer */
                                elseif ( strpos( $h, '<footer class="aa-footer"' ) !== false ) {
                                        $new = aa_footer_html();
                                        if ( $new !== $h ) { $foot_done++; $changed = true; }
                                }
                                /* 3. copy replacements */
                                foreach ( $REPL as $from => $to ) {
                                        if ( strpos( $new, $from ) !== false ) {
                                                $new = str_replace( $from, $to, $new );
                                                $repl_done++; $changed = true;
                                        }
                                }
                                /* page 8: any remaining "My Account" copy -> Wishlist */
                                if ( 8 === $pid && strpos( $new, 'My Account' ) !== false ) {
                                        $new = str_replace( 'My Account', 'Wishlist', $new );
                                        $changed = true;
                                }
                                if ( $new !== $h ) {
                                        $data[ $si ]['elements'][ $ci ]['elements'][ $wi ]['settings']['html'] = $new;
                                }
                        }
                }
        }

        if ( $changed ) {
                update_post_meta( $pid, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
                delete_post_meta( $pid, '_elementor_element_cache' );
                delete_post_meta( $pid, '_elementor_css' );
                delete_post_meta( $pid, '_elementor_page_assets' );
                echo "#$pid " . get_the_title( $pid ) . ": updated\n";
        }
}

echo "NAV widgets: $nav_done, FOOTER widgets: $foot_done, REPLACEMENTS: $repl_done\nUPDATE-NAV-DONE\n";
