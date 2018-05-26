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


if ( ! function_exists('gp_get_translation_row_classes') ) {
	/**
	 * Generates a list of classes to be added to the translation row, based on translation entry properties.
	 *
	 * Backported from 2.2 to provide support for older versions.
	 *
	 * @param Translation_Entry $translation The translation entry object for the row.
	 *
	 * @return array
	 */
	function gp_get_translation_row_classes( $translation ) {
		$classes = array();
		$classes[] = $translation->translation_status ? 'status-' . $translation->translation_status : 'untranslated';
		$classes[] = 'priority-' . gp_array_get( GP::$original->get_static( 'priorities' ), $translation->priority );
		$classes[] = $translation->warnings ? 'has-warnings' : 'no-warnings';
		/**
		 * Filters the list of CSS classes for a translation row
		 *
		 * @since 2.2.0
		 *
		 * @param array             $classes     An array of translation row classes.
		 * @param Translation_Entry $translation The translation entry object.
		 */
		$classes = apply_filters( 'gp_translation_row_classes', $classes, $translation );
		return $classes;
	}

}

if ( !function_exists( 'gp_translation_row_classes' ) ) {

	/**
	 * Outputs space separated list of classes for the translation row, based on translation entry properties.
	 *
	 * Backported from 2.2 to provide support for older versions.
	 *
	 * @param Translation_Entry $translation The translation entry object for the row.
	 *
	 * @return void
	 */
	function gp_translation_row_classes( $translation ) {
		$classes = gp_get_translation_row_classes( $translation );
		echo esc_attr( implode( ' ', $classes ) );
	}
}


if ( !function_exists( 'map_glossary_entries_to_translation_originals' ) ) {
	/**
	 * Add markup to a translation original to identify the glossary terms.
	 *
	 * Backported from core to provide support for older versions.
	 * 
	 * @param GP_Translation $translation            A GP Translation object.
	 * @param GP_Glossary    $glossary               A GP Glossary object.
	 * @param array          $glossary_entries_terms A list of terms to highligh.
	 *
	 * @return obj The marked up translation entry.
	 */
	function map_glossary_entries_to_translation_originals( $translation, $glossary, $glossary_entries_terms = null ) {
		$glossary_entries = $glossary->get_entries();
		if ( empty( $glossary_entries ) ) {
			return $translation;
		}
		if ( null === $glossary_entries_terms || ! is_array( $glossary_entries_terms ) ) {
			$glossary_entries_terms = gp_sort_glossary_entries_terms( $glossary_entries );
		}
		// Save our current singular/plural strings before attempting any markup change. Also escape now, since we're going to add some html.
		$translation->singular_glossary_markup = esc_translation( $translation->singular );
		$translation->plural_glossary_markup   = esc_translation( $translation->plural );
		// Search for glossary terms in our strings.
		$matching_entries = array();
		foreach ( $glossary_entries_terms as $i => $terms ) {
			$glossary_entry = $glossary_entries[ $i ];
				if ( preg_match_all( '/\b(' . $terms . ')\b/i', $translation->singular . ' ' . $translation->plural, $m ) ) {
				$locale_entry = '';
				if ( $glossary_entry->glossary_id !== $glossary->id ) {
					/* translators: Denotes an entry from the locale glossary in the tooltip */
					$locale_entry = _x( 'Locale Glossary', 'Bubble', 'glotpress' );
				}
				foreach ( $m[1] as $value ) {
					$matching_entries[ $value ][] = array(
						'translation'  => $glossary_entry->translation,
						'pos'          => $glossary_entry->part_of_speech,
						'comment'      => $glossary_entry->comment,
						'locale_entry' => $locale_entry,
						);
				}
			}
		}
		// Replace terms in strings with markup.
		foreach ( $matching_entries as $term => $glossary_data ) {
			$replacement = '<span class="glossary-word" data-translations="' . htmlspecialchars( wp_json_encode( $glossary_data ), ENT_QUOTES, 'UTF-8' ) . '">$1</span>';
			$regex = '/\b(' . preg_quote( $term, '/' ) . ')(?![^<]*<\/span>)\b/iu';
			$translation->singular_glossary_markup = preg_replace( $regex, $replacement, $translation->singular_glossary_markup );
			if ( $translation->plural ) {
				$translation->plural_glossary_markup = preg_replace( $regex, $replacement, $translation->plural_glossary_markup );
			}
		}
		return $translation;
	}
}