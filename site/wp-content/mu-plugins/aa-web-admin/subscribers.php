<?php
/**
 * Subscribers: newsletter list (option aa_subscribers) — list, remove, export CSV.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function aa_admin_subscribers_router() {
	if ( isset( $_GET['export'] ) && 'csv' === sanitize_key( $_GET['export'] ) ) {
		aa_admin_subscribers_export_csv();
		exit;
	}

	if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
		aa_admin_subscribers_handle_post();
	}

	$subs = get_option( 'aa_subscribers', array() );
	if ( ! is_array( $subs ) ) { $subs = array(); }
	$subs = array_reverse( $subs ); // newest first

	ob_start();
	?>
	<div class="dash-cols" style="margin-bottom:18px">
		<div class="card stat"><b><?php echo count( $subs ); ?></b><span>Newsletter subscribers</span></div>
		<div class="card" style="display:flex;align-items:center;gap:12px">
			<a class="btn pri" href="<?php echo esc_url( aa_admin_url( 'subscribers' ) ); ?>?export=csv"><?php echo aa_admin_icon( 'tag' ); ?> Export CSV</a>
			<span class="muted small">Opens in spreadsheets — email + signup date.</span>
		</div>
	</div>
	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr><th>Email</th><th>Signed up</th><th></th></tr></thead>
			<tbody>
			<?php if ( $subs ) : ?>
				<?php foreach ( $subs as $i => $s ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $s['email'] ?? '' ); ?></strong></td>
						<td class="muted"><?php echo esc_html( ! empty( $s['time'] ) ? date_i18n( 'M j, Y g:i a', (int) $s['time'] ) : '—' ); ?></td>
						<td>
							<form method="post" action="<?php echo esc_url( aa_admin_url( 'subscribers' ) ); ?>" data-confirm="Remove <?php echo esc_attr( $s['email'] ?? 'this subscriber' ); ?> from the list?">
								<?php wp_nonce_field( 'aa_subscribers' ); ?>
								<input type="hidden" name="aa_action" value="remove">
								<input type="hidden" name="aa_email" value="<?php echo esc_attr( $s['email'] ?? '' ); ?>">
								<button class="btn sm ghost" type="submit">Remove</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td colspan="3" class="muted center">No subscribers yet. The footer newsletter form adds them here automatically.</td></tr>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
	aa_admin_layout( 'Subscribers', ob_get_clean(), 'subscribers' );
}

function aa_admin_subscribers_handle_post() {
	if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'aa_subscribers' ) ) {
		wp_safe_redirect( aa_admin_url( 'subscribers' ) . '?aa_msg=denied' );
		exit;
	}
	$action = sanitize_key( $_POST['aa_action'] ?? '' );
	$email  = isset( $_POST['aa_email'] ) ? sanitize_email( wp_unslash( $_POST['aa_email'] ) ) : '';

	if ( 'remove' === $action && is_email( $email ) ) {
		$subs = get_option( 'aa_subscribers', array() );
		if ( ! is_array( $subs ) ) { $subs = array(); }
		$kept = array();
		foreach ( $subs as $s ) {
			if ( ! isset( $s['email'] ) || 0 !== strcasecmp( $s['email'], $email ) ) { $kept[] = $s; }
		}
		update_option( 'aa_subscribers', $kept, false );
		wp_safe_redirect( aa_admin_url( 'subscribers' ) . '?aa_msg=subscriber_removed' );
		exit;
	}

	wp_safe_redirect( aa_admin_url( 'subscribers' ) . '?aa_msg=error' );
	exit;
}

function aa_admin_subscribers_export_csv() {
	$subs = get_option( 'aa_subscribers', array() );
	if ( ! is_array( $subs ) ) { $subs = array(); }

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=annaadhya-subscribers-' . gmdate( 'Ymd' ) . '.csv' );

	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'email', 'subscribed_at' ) );
	foreach ( $subs as $s ) {
		fputcsv( $out, array(
			$s['email'] ?? '',
			! empty( $s['time'] ) ? gmdate( 'c', (int) $s['time'] ) : '',
		) );
	}
	fclose( $out );
}
