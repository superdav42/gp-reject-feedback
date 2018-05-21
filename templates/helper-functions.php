<?php

/**
 * Get predefined reject reasons.
 *
 * @param GP_Project $project Current project option.
 * @return array Array of code=>reason pairs.
 */
function get_reject_reasons( $project ) {
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
	 * @param GP_Project    $project    The current project.
	 */
	return apply_filters( 'gp_reject_reasons', $reject_reasons, $project );
}
