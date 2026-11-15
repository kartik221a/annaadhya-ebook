<?php
/**
 * Messages: contact-form submissions (CPT aa_message) — list, view, mark handled, delete.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @param string $sub '' | '<message-id>'
 */
function aa_admin_messages_router( $sub = '' ) {
	if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
		aa_admin_messages_handle_post();
	}

	if ( $sub && absint( $sub ) ) {
		aa_admin_message_detail( absint( $sub ) );
		return;
	}

	$messages = get_posts(
		array(
			'post_type'      => 'aa_message',
			'posts_per_page' => 60,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
	$unread = 0;
	foreach ( $messages as $m ) {
		if ( ! (int) get_post_meta( $m->ID, '_aa_msg_read', true ) ) { $unread++; }
	}

	ob_start();
	?>
	<?php if ( $unread ) : ?>
	<div class="flash warn"><?php echo (int) $unread; ?> unread message<?php echo $unread === 1 ? '' : 's'; ?> — replies are promised within one working day.</div>
	<?php endif; ?>
	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr><th>Status</th><th>Message</th><th>Topic</th><th>From</th><th>Received</th><th></th></tr></thead>
			<tbody>
			<?php if ( $messages ) : ?>
				<?php foreach ( $messages as $m ) : ?>
					<?php
					$read  = (int) get_post_meta( $m->ID, '_aa_msg_read', true );
					$topic = get_post_meta( $m->ID, '_aa_msg_topic', true );
					$email = get_post_meta( $m->ID, '_aa_msg_email', true );
					?>
					<tr>
						<td><span class="pill <?php echo $read ? 's-completed' : 's-on-hold'; ?>"><?php echo $read ? 'Handled' : 'New'; ?></span></td>
						<td><a class="rowlink" href="<?php echo esc_url( aa_admin_url( 'messages/' . $m->ID ) ); ?>"><?php echo esc_html( wp_trim_words( $m->post_content, 8 ) ); ?></a></td>
						<td class="muted"><?php echo esc_html( $topic ?: 'Other' ); ?></td>
						<td>
							<?php echo esc_html( get_post_meta( $m->ID, '_aa_msg_name', true ) ); ?>
							<span class="muted small"><?php echo esc_html( $email ); ?></span>
						</td>
						<td class="muted"><?php echo esc_html( get_the_date( 'M j, Y g:i a', $m ) ); ?></td>
						<td>
							<form method="post" action="<?php echo esc_url( aa_admin_url( 'messages' ) ); ?>" data-confirm="Delete this message permanently?">
								<?php wp_nonce_field( 'aa_messages' ); ?>
								<input type="hidden" name="aa_action" value="delete">
								<input type="hidden" name="aa_id" value="<?php echo (int) $m->ID; ?>">
								<button class="btn sm ghost" type="submit">Delete</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td colspan="6" class="muted center">No messages yet. Contact-form submissions land here.</td></tr>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
	aa_admin_layout( 'Messages', ob_get_clean(), 'messages' );
}

function aa_admin_message_detail( $id ) {
	$m = get_post( $id );
	if ( ! $m || 'aa_message' !== $m->post_type ) {
		aa_admin_not_found();
		return;
	}
	$name  = get_post_meta( $id, '_aa_msg_name', true );
	$email = get_post_meta( $id, '_aa_msg_email', true );
	$topic = get_post_meta( $id, '_aa_msg_topic', true );

	// Viewing a message marks it as handled.
	if ( ! (int) get_post_meta( $id, '_aa_msg_read', true ) ) {
		update_post_meta( $id, '_aa_msg_read', 1 );
	}

	ob_start();
	?>
	<div class="order-head">
		<a class="btn sm ghost" href="<?php echo esc_url( aa_admin_url( 'messages' ) ); ?>">&larr; All messages</a>
		<span class="pill s-on-hold"><?php echo esc_html( $topic ?: 'Other' ); ?></span>
	</div>
	<div class="card msg-card">
		<h2><?php echo esc_html( wp_strip_all_tags( $m->post_content ) ? '[Topic above] ' : '' ); ?><?php echo esc_html( $name ); ?></h2>
		<p class="muted">
			<strong><?php echo esc_html( $email ); ?></strong>
			&middot; received <?php echo esc_html( get_the_date( 'M j, Y g:i a', $m ) ); ?>
			&middot; <a href="mailto:<?php echo esc_attr( $email ); ?>?subject=Re: your Annaadhya support request">Reply by email</a>
		</p>
		<div class="msg-body"><?php echo esc_html( $m->post_content ); ?></div>
	</div>
	<?php
	aa_admin_layout( 'Message from ' . ( $name ?: 'customer' ), ob_get_clean(), 'messages' );
}

function aa_admin_messages_handle_post() {
	if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'aa_messages' ) ) {
		wp_safe_redirect( aa_admin_url( 'messages' ) . '?aa_msg=denied' );
		exit;
	}
	$action = sanitize_key( $_POST['aa_action'] ?? '' );
	$id     = absint( $_POST['aa_id'] ?? 0 );

	if ( 'delete' === $action && $id && 'aa_message' === get_post_type( $id ) ) {
		wp_delete_post( $id, true );
		wp_safe_redirect( aa_admin_url( 'messages' ) . '?aa_msg=message_deleted' );
		exit;
	}

	if ( 'mark_read' === $action && $id && 'aa_message' === get_post_type( $id ) ) {
		update_post_meta( $id, '_aa_msg_read', 1 );
		wp_safe_redirect( aa_admin_url( 'messages' ) . '?aa_msg=message_updated' );
		exit;
	}

	wp_safe_redirect( aa_admin_url( 'messages' ) . '?aa_msg=error' );
	exit;
}
