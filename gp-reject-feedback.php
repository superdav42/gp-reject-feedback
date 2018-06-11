<?php
/*
Plugin Name: GP Reject With Feedback
Description: Reviewers can provide rejection reasons and feedback when rejecting a translation.
Version: 1.0.0
Author: David Stone
Tags: glotpress, glotpress plugin
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

define( 'GP_FEEDBACK_PATH', __DIR__ . '/' );
define( 'GP_FEEDBACK_FILE', __FILE__ );
// Add an action to WordPress's init hook to setup the plugin.  Don't just setup the plugin here as the GlotPress plugin may not have loaded yet.
add_action( 'gp_init', 'gp_single_click_edit_init' );

/**
 * This function creates the plugin.
 */
function gp_single_click_edit_init() {
	require GP_FEEDBACK_PATH . 'class-gp-reject-feedback.php';
	new GP_Reject_Feedback();
}
