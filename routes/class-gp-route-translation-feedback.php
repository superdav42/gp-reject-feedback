<?php
/**
 * GP_Reject_Feedback class
 *
 * @package GP_Reject_Feedback
 */

/**
 * Route to save feedback. Extends base GP_Route_Translation.
 */
class GP_Route_Translation_Feedback extends GP_Route_Translation {

	/**
	 * Action to save reject reasons, feedback and set status.
	 *
	 * @param string $project_path Project path from route.
	 * @param string $locale_slug Locale slug from route.
	 * @param string $translation_set_slug Translation set slug from route.
	 * @return null
	 */
	public function reject_feedback( $project_path, $locale_slug, $translation_set_slug ) {
		$status         = 'rejected';
		$translation_id = gp_post( 'translation_id' );
		if ( ! $this->verify_nonce( 'update-translation-status-' . $status . '_' . $translation_id ) ) {
			return $this->die_with_error( __( 'An error has occurred. Please try again.', 'glotpress' ), 403 );
		}
		$edit_callback = function ( $project, $locale, $translation_set, $translation ) {
			$res = $translation->set_status( 'rejected' );
			if ( ! $res ) {
				return $this->die_with_error( 'Error in saving the translation status!' );
			}
			$reasons  = gp_post( 'reasons' );
			$feedback = trim( gp_post( 'feedback' ) );
			if ( $reasons ) {
				gp_update_meta( $translation->id, 'reject_reasons', $reasons, 'translation' );
			}
			if ( $feedback ) {
				gp_update_meta( $translation->id, 'reject_feedback', $feedback, 'translation' );
			}
		};
		return $this->edit_single_translation( $project_path, $locale_slug, $translation_set_slug, $edit_callback );
	}

	/**
	 * Copied from base since it's private.
	 *
	 * @param string   $project_path
	 * @param string   $locale_slug
	 * @param string   $translation_set_slug
	 * @param callable $edit_function
	 * @return string
	 */
	private function edit_single_translation( $project_path, $locale_slug, $translation_set_slug, $edit_function ) {
		$project = GP::$project->by_path( $project_path );
		$locale  = GP_Locales::by_slug( $locale_slug );

		if ( ! $project || ! $locale ) {
			return $this->die_with_404();
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );

		if ( ! $translation_set ) {
			return $this->die_with_404();
		}

		if ( method_exists( $this, 'get_extended_glossary' ) ) {
			$glossary = $this->get_extended_glossary( $translation_set, $project );
		} else {
			$glossary = false;
		}

		$translation = GP::$translation->get( gp_post( 'translation_id' ) );

		if ( ! $translation ) {
			return $this->die_with_error( 'Translation doesn&#8217;t exist!' );
		}

		$this->can_approve_translation_or_forbidden( $translation );

		call_user_func( $edit_function, $project, $locale, $translation_set, $translation );

		$translations = GP::$translation->for_translation(
			$project, $translation_set, 'no-limit', array(
				'translation_id' => $translation->id,
				'status'         => 'either',
			), array()
		);
		if ( ! empty( $translations ) ) {
			$t = $translations[0];

			$can_edit                = $this->can( 'edit', 'translation-set', $translation_set->id );
			$can_write               = $this->can( 'write', 'project', $project->id );
			$can_approve             = $this->can( 'approve', 'translation-set', $translation_set->id );
			$can_approve_translation = $this->can( 'approve', 'translation', $t->id, array( 'translation' => $t ) );

			$this->tmpl( 'translation-row', get_defined_vars() );
		} else {
			return $this->die_with_error( 'Error in retrieving translation!' );
		}
	}

	/**
	 * Copied from base since it's private.
	 *
	 * @param object $translation
	 * @return void
	 */
	private function can_approve_translation_or_forbidden( $translation ) {
		$can_reject_self = ( get_current_user_id() === (int) $translation->user_id && 'waiting' === $translation->status );
		if ( $can_reject_self ) {
			return;
		}
		$this->can_or_forbidden( 'approve', 'translation', $translation->id, null, array( 'translation' => $translation ) );
	}

	/**
	 * Copied form base tranlations_post() with a few changes to keep the current user_id.
	 *
	 * @param string $project_path
	 * @param string $locale_slug
	 * @param string $translation_set_slug
	 * @return string
	 */
	public function translations_keep_user_post( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale  = GP_Locales::by_slug( $locale_slug );
		if ( ! $project || ! $locale ) {
			return $this->die_with_404();
		}
		$original_id = gp_post( 'original_id' );
		if ( ! $this->verify_nonce( 'add-translation_' . $original_id ) ) {
			return $this->die_with_error( __( 'An error has occurred. Please try again.', 'glotpress' ), 403 );
		}
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		$this->can_or_forbidden( 'edit', 'translation-set', $translation_set->id );
		if ( ! $translation_set ) {
			return $this->die_with_404();
		}
		$glossary = $this->get_extended_glossary( $translation_set, $project );
		$output   = array();
		foreach ( gp_post( 'translation', array() ) as $original_id => $translations ) {
			$data                       = compact( 'original_id' );
			$data['user_id']            = get_current_user_id();
			$data['translation_set_id'] = $translation_set->id;
			// Reduce range by one since we're starting at 0, see GH#516.
			foreach ( range( 0, GP::$translation->get_static( 'number_of_plural_translations' ) - 1 ) as $i ) {
				if ( isset( $translations[ $i ] ) ) {
					$data[ "translation_$i" ] = $translations[ $i ];
				}
			}
			if ( isset( $data['status'] ) ) {
				$set_status = $data['status'];
			} else {
				$set_status = 'waiting';
			}
			$data['status'] = 'waiting';
			if ( $this->can( 'approve', 'translation-set', $translation_set->id ) || $this->can( 'write', 'project', $project->id ) ) {
				$set_status = 'current';
				$user_id    = gp_post( 'user_id' );
				if ( $user_id ) {
					// We are fixing a translation and preserving the author.
					$data['user_id_last_modified'] = $data['user_id'];
					$data['user_id']               = $user_id;
				}
			} else {
				$set_status = 'waiting';
			}
			$original              = GP::$original->get( $original_id );
			$data['warnings']      = GP::$translation_warnings->check( $original->singular, $original->plural, $translations, $locale );
			$existing_translations = GP::$translation->for_translation(
				$project, $translation_set, 'no-limit', array(
					'original_id' => $original_id,
					'status'      => 'current_or_waiting',
				), array()
			);
			foreach ( $existing_translations as $e ) {
				if ( array_pad( $translations, $locale->nplurals, null ) == $e->translations ) {
					return $this->die_with_error( __( 'Identical current or waiting translation already exists.', 'glotpress' ), 200 );
				}
			}
			$translation = GP::$translation->create( $data );
			if ( ! $translation ) {
				return $this->die_with_error( __( 'Error in saving the translation!', 'glotpress' ) );
			}
			if ( ! $translation->validate() ) {
				$error_output = '<ul>';
				foreach ( $translation->errors as $error ) {
					$error_output .= '<li>' . $error . '</li>';
				}
				$error_output .= '</ul>';
				$translation->delete();
				return $this->die_with_error( $error_output, 200 );
			} else {
				if ( 'current' === $set_status ) {
					$translation->set_status( 'current' );
				}
				$translations = GP::$translation->for_translation( $project, $translation_set, 'no-limit', array( 'translation_id' => $translation->id ), array() );
				if ( ! empty( $translations ) ) {
					$t                       = $translations[0];
					$can_edit                = $this->can( 'edit', 'translation-set', $translation_set->id );
					$can_write               = $this->can( 'write', 'project', $project->id );
					$can_approve             = $this->can( 'approve', 'translation-set', $translation_set->id );
					$can_approve_translation = $this->can( 'approve', 'translation', $t->id, array( 'translation' => $t ) );
					$output[ $original_id ]  = gp_tmpl_get_output( 'translation-row', get_defined_vars() );
				} else {
					$output[ $original_id ] = false;
				}
			}
		}
		echo wp_json_encode( $output );
	}
}
