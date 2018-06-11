<?php
/**
 * Template reject-reasons-translations-footer
 *
 * @package GP_Reject_Feedback
 */

$user = wp_get_current_user();
?>

<div id="js-replace-content" style="display: none;">
<?php
foreach ( $translations as $t ) :
		$t->translation_set_id   = $translation_set->id;
		$can_reject_self         = ( isset( $t->user->user_login ) && $user->user_login === $t->user->user_login && 'waiting' === $t->translation_status );
		$can_approve_translation = GP::$permission->current_user_can( 'approve', 'translation', $t->id, array( 'translation' => $t ) );
?>
	<div data-replace-id="<?php echo esc_attr( $t->row_id ); ?>" data-selector=".meta dl:first-of-type">
		<dl>
			<dt><?php _e( 'Status:', 'glotpress' ); ?></dt>
			<dd>
				<?php echo display_status( $t->translation_status ); ?>
				<?php if ( $t->translation_status ) : ?>
					<?php if ( 'rejected' === $t->translation_status ) : ?>
						<?php
							$reasons = gp_get_meta( 'translation', $t->id, 'reject_reasons' );
						if ( $reasons ) {
							if ( empty( $reject_reasons ) ) {
								$reject_reasons = get_reject_reasons();
							}

							$mapped_reasons = array_map(
								function ( $reason_code ) use ( $reject_reasons ) {
									return $reject_reasons[ $reason_code ];
								},
								$reasons
							);
						} else {
							$mapped_reasons = false;
						}
						?>
						<?php if ( $mapped_reasons ) : ?>
							(<?php echo esc_html( implode( ', ', $mapped_reasons ) ); ?>)
						<?php endif; ?>
					<?php endif; ?>
					<?php if ( $can_approve_translation ) : ?>
						<?php if ( 'current' !== $t->translation_status ) : ?>
						<button class="approve" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-current_' . $t->id ) ); ?>" title="<?php echo esc_attr_e( 'Approve this translation. Any existing translation will be kept as part of the translation history.', 'glotpress' ); ?>"><strong>+</strong> <?php _ex( 'Approve', 'Action', 'glotpress' ); ?></button>
						<?php endif; ?>
						<?php if ( 'rejected' !== $t->translation_status ) : ?>
						<button class="reject" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-rejected_' . $t->id ) ); ?>" title="<?php echo esc_attr_e( 'Reject this translation. The reason for rejection can be selected next.', 'glotpress' ); ?>" data-close="<?php _ex( 'Close', 'Action', 'glotpress' ); ?>"><strong>&minus;</strong> <?php _ex( 'Reject', 'Action', 'glotpress' ); ?></button>
						<?php endif; ?>
						<?php if ( 'fuzzy' !== $t->translation_status ) : ?>
						<button class="fuzzy" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-fuzzy_' . $t->id ) ); ?>" title="<?php echo esc_attr_e( 'Mark this translation as fuzzy for further review.', 'glotpress' ); ?>"><strong>~</strong> <?php _ex( 'Fuzzy', 'Action', 'glotpress' ); ?></button>
						<?php endif; ?>
					<?php elseif ( $can_reject_self ) : ?>
						<button class="reject" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-rejected_' . $t->id ) ); ?>" title="<?php echo esc_attr_e( 'Reject this translation. The reason for rejection can be selected next.', 'glotpress' ); ?>" data-close="<?php _ex( 'Close', 'Action', 'glotpress' ); ?>"><strong>&minus;</strong> <?php _ex( 'Reject', 'Action', 'glotpress' ); ?></button>
						<button class="fuzzy" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-fuzzy_' . $t->id ) ); ?>" title="<?php echo esc_attr_e( 'Mark this translation as fuzzy for further review.', 'glotpress' ); ?>"><strong>~</strong> <?php _ex( 'Fuzzy', 'Action', 'glotpress' ); ?></button>
					<?php endif; ?>
				<?php endif; ?>
			</dd>
		</dl>
		<?php if ( 'rejected' === $t->translation_status ) : ?>
		<?php
			$feedback = gp_get_meta( 'translation', $t->id, 'reject_feedback' );
		?>
			<?php if ( $feedback ) : ?>
				<dl>
					<dt><?php _e( 'Translator Feedback:', 'glotpress' ); ?></dt>
					<dd><?php echo esc_html( $feedback ); ?></dd>
				</dl>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<div data-replace-id="<?php echo esc_attr( $t->row_id ); ?>" data-selector=".actions">
		<div class="actions">
			<?php if ( $can_edit ) : ?>
				<button class="ok" data-nonce="<?php echo esc_attr( wp_create_nonce( 'add-translation_' . $t->original_id ) ); ?>">
					<?php echo $can_approve_translation ? __( 'Add translation &rarr;', 'glotpress' ) : __( 'Suggest new translation &rarr;', 'glotpress' ); ?>
				</button>
				<?php if ( $can_approve_translation && $t->user_id && get_current_user_id() !== (int) $t->user_id ) : ?>
					<?php _e( 'or', 'glotpress' ); ?>
					<button class="fix" data-nonce="<?php echo esc_attr( wp_create_nonce( 'add-translation_' . $t->original_id ) ); ?>" data-user-id="<?php echo esc_attr( $t->user_id ); ?>" title="<?php echo esc_attr_e( 'Fixing a translation will keep credit for the original author.', 'glotpress' ); ?>">
						<?php _e( 'Fix translation &rarr;', 'glotpress' ); ?>
					</button>
				<?php endif; ?>
			<?php endif; ?>
			<?php _e( 'or', 'glotpress' ); ?> <a href="#" class="close"><?php _e( 'Cancel', 'glotpress' ); ?></a>
		</div>
	</div>
<?php endforeach; ?>
</div>
