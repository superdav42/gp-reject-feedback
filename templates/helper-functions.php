<?php
/**
 * Helper functions used by templates.
 *
 * @package GP_Reject_Feedback
 */

/**
 * Get predefined reject reasons.
 *
 * @return array Array of code=>reason pairs.
 */
function get_reject_reasons() {
	$reject_reasons = array(
		'style-guide' => __( 'Style Guide', 'glotpress' ),
		'glossary'    => __( 'Glossary', 'glotpress' ),
		'grammar'     => __( 'Grammar', 'glotpress' ),
		'punctuation' => __( 'Punctuation', 'glotpress' ),
		'branding'    => __( 'Branding', 'glotpress' ),
		'typos'       => __( 'Typos', 'glotpress' ),
	);
	/**
	 * Filter whether to show references of a translation string on a translation row.
	 *
	 * @since 3.2.0
	 *
	 * @param array         $reasons    Array with reject reasons.
	 */
	return apply_filters( 'gp_reject_reasons', $reject_reasons );
}
