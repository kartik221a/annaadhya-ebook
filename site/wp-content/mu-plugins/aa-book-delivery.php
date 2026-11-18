<?php
/**
 * Plugin Name: Annaadhya Book Delivery (must-use)
 * Description: No accounts, no download limits — the whole delivery is one email. As soon as an order is paid, the customer's billing email receives a branded "Obsidian & Gold" message with the PDF file of every purchased book ATTACHED (plus permanent re-download links as a fallback). Guests checkout with just a name and an email; there is nothing to sign into.
 * Version:     1.0.0
 * Author:      Annaadhya
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ------------------------------------------------------------------------
 * Triggers. Real gateways (Razorpay/UPI, Stripe...) call payment_complete(),
 * which fires woocommerce_payment_complete and usually moves a digital-only
 * order straight to "completed". COD / bank-transfer / manual orders go
 * through the status hooks instead. One guard meta keeps it single-shot.
 * ---------------------------------------------------------------------- */

add_action( 'woocommerce_payment_complete', 'aa_book_delivery_send', 20 );
add_action( 'woocommerce_order_status_completed', 'aa_book_delivery_send', 20 );
add_action( 'woocommerce_order_status_processing', 'aa_book_delivery_send', 20 );

/** Send the book email once per order. */
function aa_book_delivery_send( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order || $order->get_meta( '_aa_books_emailed' ) ) { return; }

        $email = $order->get_billing_email();
        if ( ! $email || ! is_email( $email ) ) { return; }

        // Collect every downloadable file attached to items in this order.
        $files    = array();  // local paths we can attach
        $linkonly = array();  // remote/unreadable files -> links instead
        $books    = array();  // display rows for the email body

        foreach ( $order->get_items() as $item ) {
                $product = $item->get_product();
                if ( ! $product ) { $product = wc_get_product( $item->get_product_id() ); }
                if ( ! $product || ! $product->is_downloadable() ) { continue; }

                $row = array(
                        'title'  => $item->get_name(),
                        'author' => (string) get_post_meta( $product->get_id(), '_aa_author', true ),
                        'cover'  => get_the_post_thumbnail_url( $product->get_id(), 'medium' ),
                        'files'  => array(),
                );

                foreach ( $product->get_downloads() as $dl_key => $dl ) {
                        $path = $product->get_file_download_path( $dl_key );
                        if ( $path && '/' !== substr( $path, 0, 1 ) && ! preg_match( '#^[a-z][a-z0-9+.\-]*:#i', $path ) ) {
                                $path = trailingslashit( ABSPATH ) . $path; // relative to WP root -> absolute
                        }
                        if ( $path && @is_readable( $path ) && @filesize( $path ) <= 20 * 1024 * 1024 ) {
                                $files[] = $path;
                        } else {
                                $linkonly[] = array( 'name' => $item->get_name(), 'url' => $dl->get_file() );
                        }
                        $row['files'][] = basename( $dl->get_file() );
                }
                $books[] = $row;
        }

        if ( empty( $books ) ) { return; } // nothing downloadable — let WooCommerce's own emails handle it

        $order_url = $order->get_checkout_order_received_url();

        $body  = aa_book_delivery_email_html( $order, $books, $order_url, ! empty( $linkonly ) );
        $subject = sprintf( 'Your Annaadhya ebooks are here — order #%s (PDFs attached)', $order->get_order_number() );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        $sent    = wp_mail( $email, $subject, $body, $headers, $files );

        $order->update_meta_data( '_aa_books_emailed', $sent ? time() : 0 );
        if ( ! $sent ) {
                $order->update_meta_data( '_aa_books_email_error', 'wp_mail returned false — check SMTP/mail logs on the host' );
        }
        $order->save();
}

/* ------------------------------------------------------------------------
 * The email itself: self-contained, table-based, inline styles — renders
 * the same in Gmail, Apple Mail, Outlook and on phones. Obsidian & Gold.
 * ---------------------------------------------------------------------- */

function aa_book_delivery_email_html( $order, $books, $order_url, $has_linkonly = false ) {
        $name  = $order->get_billing_first_name();
        $total = $order->get_formatted_order_total();
        $num   = $order->get_order_number();
        $date  = $order->get_date_created() ? $order->get_date_created()->date_i18n( 'F j, Y' ) : '';

        $rows = '';
        foreach ( $books as $b ) {
                $author = $b['author'] ? '<div style="font-size:12px;color:#8b8fa3;padding-top:2px;">' . esc_html( $b['author'] ) . '</div>' : '';
                $fmt    = array( 'PDF' );
                $cover  = $b['cover']
                        ? '<td width="86" valign="top" style="padding:14px 0 14px 18px;"><img src="' . esc_url( $b['cover'] ) . '" width="84" alt="' . esc_attr( $b['title'] ) . '" style="display:block;width:84px;border-radius:8px;border:1px solid rgba(160,142,122,.4);" /></td>'
                        : '';
                $rows .= '
<tr>
        <td style="border-bottom:1px solid rgba(160,142,122,.18);">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
                <td valign="top" style="padding:14px 0 14px 18px;color:#e0e2ea;font-size:15px;font-weight:700;">' . esc_html( $b['title'] ) . $author . '
                        <div style="padding-top:8px;"><span style="display:inline-block;font-size:10px;font-weight:800;letter-spacing:.08em;color:#f59e0b;background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.4);border-radius:6px;padding:4px 9px;text-transform:uppercase;">Attached to this email</span></div>
                </td>' . $cover . '</tr></table>
        </td>
</tr>';
        }

        $fallback = '';
        if ( $has_linkonly || true ) {
                $fallback = '
<tr><td style="padding:8px 18px 22px;">
        <p style="margin:0 0 10px;color:#d8c3ad;font-size:13px;line-height:1.7;">Need to download again later — on another device, a Kindle, a new phone? Open your order page any time:</p>
        <a href="' . esc_url( $order_url ) . '" style="display:inline-block;background:#f59e0b;color:#472a00;font-weight:700;font-size:13px;text-decoration:none;border-radius:10px;padding:12px 22px;">Open my order &amp; download links</a>
        <p style="margin:12px 0 0;color:#8b8fa3;font-size:12px;">Those links never expire and there is no download limit.</p>
</td></tr>';
        }

        return '<!DOCTYPE html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Your Annaadhya ebooks</title></head>
<body style="margin:0;padding:0;background:#0a0e13;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#0a0e13" style="background:#0a0e13;">
<tr><td align="center" style="padding:28px 12px 40px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#101419;border:1px solid rgba(160,142,122,.25);border-radius:16px;overflow:hidden;">

<!-- brand band -->
<tr><td align="center" style="padding:26px 18px 18px;background:#0a0e13;">
        <div style="width:44px;height:44px;border-radius:12px;background:rgba(245,158,11,.14);border:1px solid rgba(245,158,11,.4);color:#f59e0b;font-size:22px;line-height:42px;text-align:center;">A</div>
        <div style="padding-top:10px;color:#ffc174;font-size:19px;font-weight:800;letter-spacing:.04em;">Annaadhya</div>
        <div style="color:#8b8fa3;font-size:10px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;padding-top:4px;">Instant ebooks, delivered by email</div>
</td></tr>

<!-- hero -->
<tr><td align="center" style="padding:20px 30px 6px;">
        <h1 style="margin:0;color:#e0e2ea;font-size:24px;line-height:1.3;">Thank you for your purchase' . ( $name ? ', ' . esc_html( $name ) : '' ) . '!</h1>
        <p style="margin:12px 0 0;color:#d8c3ad;font-size:14px;line-height:1.7;">Your book' . ( count( $books ) > 1 ? 's are' : ' is' ) . ' attached to this email as PDF file' . ( count( $books ) > 1 ? 's' : '' ) . '. Just tap the attachments to download — no account, no password, nothing to set up.</p>
</td></tr>

<!-- order card -->
<tr><td style="padding:22px 18px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#181c21;border:1px solid rgba(160,142,122,.22);border-radius:12px;">
        <tr><td style="padding:14px 18px 4px;color:#8b8fa3;font-size:10px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;">Order #' . esc_html( $num ) . ' &middot; ' . esc_html( $date ) . '</td></tr>
        ' . $rows . '
        <tr><td style="padding:14px 18px;color:#e0e2ea;font-size:14px;font-weight:700;text-align:right;">Total &nbsp;' . wp_kses_post( $total ) . '</td></tr>
</table>
</td></tr>

<!-- fallback + help -->
' . $fallback . '
<tr><td style="padding:6px 18px 24px;">
        <p style="margin:0;color:#d8c3ad;font-size:13px;line-height:1.7;">This email is your receipt as well — keep it safe. If anything looks wrong, just reply to this email; a human reads every message.</p>
</td></tr>

<!-- footer -->
<tr><td align="center" style="padding:20px 18px 26px;background:#0a0e13;border-top:1px solid rgba(160,142,122,.18);">
        <div style="color:#8b8fa3;font-size:12px;line-height:1.7;">&copy; ' . date_i18n( 'Y' ) . ' Annaadhya &middot; Empowering Indian readers with accessible digital knowledge.<br>PDFs optimized for phones, tablets, Kindle and iPads.</div>
</td></tr>

</table>
</td></tr></table>
</body></html>';
}
