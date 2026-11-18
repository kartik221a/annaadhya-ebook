<?php
/**
 * Email Delivery: the store's whole fulfilment is one email.
 *
 * Every paid order sends the PDFs straight to the customer's email (see
 * mu-plugin aa-book-delivery.php — no accounts, no download limits). This
 * section shows the delivery status of each order and lets the admin RESEND
 * the book email with one click (e.g. "the email never arrived" support).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @param string $sub '' (kept for routing compatibility)
 */
function aa_admin_downloads_router( $sub = '' ) {

        /* ---- POST action: resend the delivery email for one order ---------- */
        $action = isset( $_POST['aa_action'] ) ? sanitize_key( $_POST['aa_action'] ) : '';
        if ( 'resend' === $action ) {
                $oid  = absint( $_POST['aa_order_id'] ?? 0 );
                $ok   = $oid && wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'aa_delivery' );
                if ( ! $ok || ! ( $order = wc_get_order( $oid ) ) ) {
                        wp_safe_redirect( aa_admin_url( 'downloads' ) . '?aa_msg=denied' );
                        exit;
                }
                $order->delete_meta_data( '_aa_books_emailed' );
                $order->delete_meta_data( '_aa_books_email_error' );
                $order->save();
                aa_book_delivery_send( $oid );
                $order = wc_get_order( $oid );
                $sent  = $order && $order->get_meta( '_aa_books_emailed' );
                wp_safe_redirect( aa_admin_url( 'downloads' ) . ( $sent ? '?aa_msg=dl_resent' : '?aa_msg=dl_failed' ) );
                exit;
        }

        aa_admin_delivery_list();
}

function aa_admin_delivery_list() {
        $orders = wc_get_orders(
                array(
                        'limit' => 40,
                        'orderby' => 'date',
                        'order' => 'DESC',
                )
        );

        ob_start();
        ?>
        <div class="card-note" style="background:#101419;border:1px solid rgba(160,142,122,.25);border-radius:10px;padding:14px 18px;margin-bottom:16px;color:#d8c3ad;font-size:13.5px;line-height:1.7">
                When an order is paid, the PDFs are attached to a branded email and sent to the customer's billing address automatically.
                No accounts are involved. Use <strong>Resend</strong> if a customer says the email never arrived.
        </div>
        <div class="tbl-wrap">
                <table class="tbl">
                        <thead><tr><th>Order</th><th>Date</th><th>Customer</th><th>Books</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php if ( $orders ) : ?>
                                <?php foreach ( $orders as $o ) :
                                        $emailed  = (int) $o->get_meta( '_aa_books_emailed' );
                                        $err      = (string) $o->get_meta( '_aa_books_email_error' );
                                        $paid     = $o->is_paid() || in_array( $o->get_status(), array( 'processing', 'completed' ), true );
                                        $books    = array();
                                        foreach ( $o->get_items() as $item ) { $books[] = $item->get_name(); }
                                        $books_txt = $books ? implode( ', ', array_slice( $books, 0, 3 ) ) : '—';
                                        ?>
                                        <tr>
                                                <td><strong>#<?php echo esc_html( $o->get_order_number() ); ?></strong></td>
                                                <td class="muted"><?php echo esc_html( $o->get_date_created() ? $o->get_date_created()->date_i18n( 'M j, Y H:i' ) : '—' ); ?></td>
                                                <td><?php echo esc_html( $o->get_billing_email() ?: '—' ); ?></td>
                                                <td><?php echo esc_html( $books_txt ); ?></td>
                                                <td>
                                                        <?php if ( $emailed ) : ?>
                                                                <span class="pill dl-active">Sent<?php echo $err ? '' : ''; ?></span>
                                                        <?php elseif ( ! $paid ) : ?>
                                                                <span class="pill">Awaiting payment</span>
                                                        <?php elseif ( $err ) : ?>
                                                                <span class="pill dl-out" title="<?php echo esc_attr( $err ); ?>">Failed</span>
                                                        <?php else : ?>
                                                                <span class="pill dl-out">Not sent yet</span>
                                                        <?php endif; ?>
                                                </td>
                                                <td class="c-act">
                                                        <?php if ( $paid ) : ?>
                                                        <form method="post" action="<?php echo esc_url( aa_admin_url( 'downloads' ) ); ?>" style="display:inline">
                                                                <input type="hidden" name="aa_action" value="resend">
                                                                <input type="hidden" name="aa_order_id" value="<?php echo (int) $o->get_id(); ?>">
                                                                <?php wp_nonce_field( 'aa_delivery' ); ?>
                                                                <button class="btn sm<?php echo $emailed ? ' ghost' : ''; ?>" type="submit" title="Send the book email to <?php echo esc_attr( $o->get_billing_email() ); ?> again">Resend email</button>
                                                        </form>
                                                        <?php endif; ?>
                                                </td>
                                        </tr>
                                <?php endforeach; ?>
                        <?php else : ?>
                                <tr><td colspan="6" class="muted center">No orders yet — delivery emails will show up here the moment someone buys a book.</td></tr>
                        <?php endif; ?>
                        </tbody>
                </table>
        </div>
        <?php
        aa_admin_layout( 'Email Delivery', ob_get_clean(), 'downloads' );
}
