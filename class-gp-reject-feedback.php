<?php
/**
 * GP_Reject_Feedback class
 *
 * @package GP_Reject_Feedback
 */

/**
 * Main class for Reject with Feedback.
 *
 * @author     David Stone <david@nnucomputerwhiz.com>
 */
class GP_Reject_Feedback {


	/**
	 * Handles initialization of the plugin
	 */
	public function __construct() {

		require_once GP_FEEDBACK_PATH . 'templates/helper-functions.php';
		require_once GP_FEEDBACK_PATH . 'routes/class-gp-route-translation-feedback.php';

		add_action( 'template_redirect', array( $this, 'register_routes' ), 5 );
		add_action( 'gp_before_request', array( $this, 'before_request' ) );
	}

	/**
	 * Before request action which adds other hooks only when needed.
	 *
	 * @param string $class_name Class of route being requested.
	 * @return void
	 */
	public function before_request( $class_name ) {
		if ( ! is_a( $class_name, 'GP_Route_Translation', true ) ) {
			return;
		}

		add_action( 'gp_pre_tmpl_load', array( $this, 'pre_tmpl_load' ), 10, 2 );
		add_action( 'gp_post_tmpl_load', array( $this, 'post_tmpl_load' ), 10, 2 );
		add_action( 'gp_footer', array( $this, 'gp_footer' ) );
	}

	/**
	 * Hook to register styles and js but only on translations page.
	 *
	 * @param string $template Template file loaded.
	 * @param array  $args Template vars passed.
	 * @return void
	 */
	public function pre_tmpl_load( $template, $args ) {

		if ( 'translations' !== $template ) {
			return;
		}

		wp_register_style( 'gp-reject-feedback-css', plugins_url( 'assets/css/style.css', __FILE__ ) );
		gp_enqueue_style( 'gp-reject-feedback-css' );

		wp_register_script( 'gp-reject-feedback-editor', plugins_url( 'assets/js/editor.js', __FILE__ ), array( 'gp-editor' ), '2018-05-19' );
		gp_enqueue_script( 'gp-reject-feedback-editor' );

		wp_localize_script(
			'gp-reject-feedback-editor', '$gp_editor_feedback_options', array(
				'reject_feedback_url' => gp_url_project( $args['project'], gp_url_join( $args['locale']->slug, $args['translation_set']->slug, '-reject-feedback' ) ),
				'update_row_url'      => gp_url_project( $args['project'], gp_url_join( $args['locale']->slug, $args['translation_set']->slug, '-update-row' ) ),
				'keep_user_url'       => gp_url_project( $args['project'], gp_url_join( $args['locale']->slug, $args['translation_set']->slug, '-keep-user' ) ),
			)
		);
	}

	/**
	 * Ran after template loaded to append content.
	 *
	 * @param string $template Template file loaded.
	 * @param array  $args Template vars passed.
	 * @return void
	 */
	public function post_tmpl_load( $template, $args ) {
		if ( 'translations' !== $template ) {
			return;
		}

		gp_tmpl_load( 'reject-reasons-translations-footer', $args, GP_FEEDBACK_PATH . 'templates/' );
	}

	/**
	 * Hook to add routes. Called right before router runs.
	 */
	public function register_routes() {
		$dir      = '([^_/][^/]*)';
		$path     = '(.+?)';
		$projects = 'projects';
		$project  = $projects . '/' . $path;
		$locale   = '(' . implode( '|', wp_list_pluck( GP_Locales::locales(), 'slug' ) ) . ')';
		$set      = "$project/$locale/$dir";

		GP::$router->prepend( "/$set/-reject-feedback", array( 'GP_Route_Translation_Feedback', 'reject_feedback' ), 'post' );
		GP::$router->prepend( "/$set/-update-row", array( 'GP_Route_Translation_Feedback', 'update_row' ), 'get' );
		GP::$router->prepend( "/$set/-keep-user", array( 'GP_Route_Translation_Feedback', 'translations_keep_user_post' ), 'post' );
	}

	/**
	 * Add our template location to override core.
	 *
	 * @param array  $locations Array or current locations to search.
	 * @param string $template Current template loading.
	 * @return array
	 */
	public function template_load_locations( $locations, $template ) {
		if ( 'translation-row' === $template ) {
			array_unshift( $locations, GP_FEEDBACK_PATH . 'templates/' );
		}
		return $locations;
	}

	/**
	 * Adds our js template to footer.
	 */
	public function gp_footer() {
		gp_tmpl_load( 'reject-reasons-footer', array(), GP_FEEDBACK_PATH . 'templates/' );
	}
}
