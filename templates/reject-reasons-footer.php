<?php
/**
 * Template 'reject-reasons-footer'
 *
 * @package GP_Reject_Feedback
 */

$reject_reasons = get_reject_reasons();
?>

<script type="text/template" id="reject-reasons-template">
	<dl class="reject-reasons">
		<dt>
			<?php _e( 'Reason:', 'glotpress' ); ?>
		</dt>
		<dd>
			<?php foreach ( $reject_reasons as $reason_code => $reason_text ) : ?>
				<label>
					<input type="checkbox" name="reject_reason[]" value="<?php echo esc_attr( $reason_code ); ?>" />
					<?php echo esc_html( $reason_text ); ?>
				</label>
			<?php endforeach; ?>
			<label class="feedback">
				<?php _e( 'Feedback:', 'glotpress' ); ?>
				<textarea name="feedback"></textarea>
			</label>
			<button class="reject-submit" title="<?php echo esc_attr_e( 'Reject this translation. The existing translation will be kept as part of the translation history.', 'glotpress' ); ?>"><strong>&minus;</strong> <?php _ex( 'Reject', 'Action', 'glotpress' ); ?></button>
		</dd>
	</dl>
</script>
