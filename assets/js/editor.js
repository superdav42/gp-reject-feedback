(function( $, $gp ) {

	$gp.editor.hooks.set_status_rejected = function() {
		$gp.editor.open_reject_reasons( $( this ) );
		return false;
	};

	var updateEachRow = function () {
		$( '#editor-' + $( this ).data( 'replace-id' ) + ' ' + $( this ).data( 'selector' ) ).replaceWith( $( this ) );
	};

	$(
		function () {
			$( $gp.editor.table )
			.off( 'keydown', 'tr.editor textarea', $gp.editor.hooks.keydown ) // remove, then add so ours will be ran first.
			.on( 'keydown', 'tr.editor textarea', $gp.editor.reject_keydown )
			.on( 'keydown', 'tr.editor textarea', $gp.editor.hooks.keydown )
			.on(
				'click', 'button.fix', function  () {
					$gp.editor.fix_save( $( this ) );
					return false;
				}
			);

			$( '#js-replace-content div' ).each( updateEachRow );
			$( '#js-replace-content' ).remove();
		}
	);

	var parent_replace_current = $gp.editor.replace_current;

	$gp.editor.replace_current = function( html ) {
		parent_replace_current( html );

		if ( ! $gp.editor.current ) {
			return;
		}

		$.ajax(
			{
				type: 'GET',
				url: $gp_editor_feedback_options.update_row_url,
				data: {
					row_id: $( html ).attr( 'row' )
				},
				success: function( html ) {
					$( html ).find( 'div' ).each( updateEachRow );
				}
			}
		);
	};

	$gp.editor.reject_keydown = function ( e ) {
		var reject, pos;
		if ( 13 === e.keyCode && 'feedback' === $( e.target ).attr( 'name' ) ) { // Enter = reject with feedback.
			// We are on rejection feedback input.
			$( '.editor:visible' ).find( '.reject-submit' ).trigger( 'click' );
		} else if ( ( 109 === e.keyCode && e.ctrlKey ) || ( 82 === e.keyCode && e.shiftKey && e.ctrlKey ) ) { // Ctrl-- or Ctrl-Shift-R = Reject.
			reject = $( '.editor:visible' ).find( '.reject' );

			if ( reject.length > 0 ) {
				if ( reject.hasClass( 'opened' ) ) {
					$( '.editor:visible' ).find( '.reject-submit' ).trigger( 'click' );
				}
			}
		} else if ( e.ctrlKey && ( ( 49 <= e.keyCode && e.keyCode <= 57 ) || ( 97 <= e.keyCode && e.keyCode <= 105 ) ) ) { // Ctrl-1 - Ctrl-9 on numpad or top row.
			reject = $( '.editor:visible' ).find( '.reject' );

			if ( 49 <= e.keyCode && e.keyCode <= 57 ) {
				pos = e.keyCode - 49; // Top row.
			} else {
				pos = e.keyCode - 97; // Numpad.
			}

			if ( reject.length > 0 ) {
				if ( ! reject.hasClass( 'opened' ) ) {
					reject.trigger( 'click' );
				}

				$( '.editor:visible' )
					.find( 'input[name=\'reject_reason[]\']' )
					.eq( pos )
					.trigger( 'click' );
			}
		} else {
			return true;
		}
	};

	$gp.editor.open_reject_reasons = function( button ) {
		var data, selected_reasons = [];
		if ( ! $gp.editor.current || ! $gp.editor.current.translation_id ) {
			return;
		}

		if ( button.hasClass( 'opened' ) ) {
			button.html( button.data( 'open' ) );
			button.closest( 'dl' ).next( 'dl' ).remove();
			button.removeClass( 'opened' );
			return;
		}

		button.addClass( 'opened' );

		button.data( 'open', button.html() );
		button.html( button.data( 'close' ) );

		button.closest( 'dl' ).after( $( '#reject-reasons-template' ).html() );

		$( '.editor:visible [name=feedback]' ).focus();

		$( '.editor:visible' ).find( '.reject-submit' ).on(
			'click', function() {
				$gp.notices.notice( 'Setting status to &#8220;' + status + '&#8221;&hellip;' );

				$( '.editor:visible input[name=\'reject_reason[]\']:checked' ).each(
					function() {
						selected_reasons.push( this.getAttribute( 'value' ) );
					}
				);

				data = {
					translation_id: $gp.editor.current.translation_id,
					status: 'rejected',
					reasons: selected_reasons,
					feedback: $( '.editor:visible [name=feedback]' ).val(),
					_gp_route_nonce: button.data( 'nonce' )
				};

					$.ajax(
						{
							type: 'POST',
							url: $gp_editor_feedback_options.reject_feedback_url,
							data: data,
							success: function( data ) {
								button.prop( 'disabled', false );
								$gp.notices.success( 'Translation Rejected!' );
								$gp.editor.replace_current( data );
								$gp.editor.next();
							},
							error: function( xhr, msg ) {
								button.prop( 'disabled', false );
								msg = xhr.responseText ? 'Error: ' + xhr.responseText : 'Error setting the status!';
								$gp.notices.error( msg );
							}
						}
					);
			}
		);
	};

	$gp.editor.fix_save = function( button ) {
		var editor, textareaName, data = [], translations;

		if ( ! $gp.editor.current ) {
			return;
		}

		editor = $gp.editor.current;
		button.prop( 'disabled', true );
		$gp.notices.notice( 'Saving&hellip;' );

		data = {
			original_id: editor.original_id,
			user_id: button.data( 'user-id' ),
			_gp_route_nonce: button.data( 'nonce' )
		};

		textareaName = 'translation[' + editor.original_id + '][]';
		translations = $( 'textarea[name="' + textareaName + '"]', editor ).map(
			function() {
					return this.value;
			}
		).get();

		data[ textareaName ] = translations;

		$.ajax(
			{
				type: 'POST',
				url: $gp_editor_feedback_options.keep_user_url,
				data: data,
				dataType: 'json',
				success: function( data ) {
					var original_id;

					button.prop( 'disabled', false );
					$gp.notices.success( 'Saved!' );

					for ( original_id in data ) {
						$gp.editor.replace_current( data[ original_id ] );
					}

					if ( $gp.editor.current.hasClass( 'no-warnings' ) ) {
						$gp.editor.next();
					}
				},
				error: function( xhr, msg ) {
					button.prop( 'disabled', false );
					msg = xhr.responseText ? 'Error: ' + xhr.responseText : 'Error saving the translation!';
					$gp.notices.error( msg );
				}
			}
		);
	};

}(jQuery, $gp)
);
