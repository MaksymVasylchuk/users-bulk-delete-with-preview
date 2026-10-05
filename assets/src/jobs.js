/**
 * Users Bulk Delete With Preview: jobs page script.
 *
 * Built with @wordpress/scripts into assets/build/jobs.js.
 */
import $ from 'jquery';
import { __ } from '@wordpress/i18n';

// Refresh the list while a job is running, so its progress stays current
if ($( '.ubdwp-cancel-job' ).length) {
    setTimeout( () => window.location.reload(), 10000 );
}

// Cancel a deletion job: users already deleted stay deleted
$( document ).on(
    'click',
    '.ubdwp-cancel-job',
    function () {
        const $button = $( this ).prop( 'disabled', true );

        $.post(
            ubdwpData.ajaxurl,
            {
                action: 'ubdwp_job_cancel',
                delete_users_nonce: ubdwpData.jobsNonce,
                job_id: $button.data( 'job-id' )
            }
        ).done(
            response => {
                if (response && response.success) {
                    window.location.reload();
                } else {
                    $button.prop( 'disabled', false );
                    createWordpressError( (response && response.data && response.data.message) || __( 'An unexpected error occurred.', 'users-bulk-delete-with-preview' ) );
                }
            }
        ).fail(
            () => {
                $button.prop( 'disabled', false );
                createWordpressError( __( 'An unexpected error occurred.', 'users-bulk-delete-with-preview' ) );
            }
        );
    }
);

/**
 * Create a WordPress styled error message
 *
 * @param {string} message - The error message
 */
function createWordpressError(message) {
    const errorDiv         = $( '<div>', {class: 'notice notice-error is-dismissible'} );
    const messageParagraph = $( '<p>' ).text( message );
    const dismissButton    = $(
        '<button>',
        {
            class: 'notice-dismiss',
            html: $( '<span class="screen-reader-text">' ).text( __( 'Dismiss this notice.', 'users-bulk-delete-with-preview' ) ),
            click: () => errorDiv.hide()
        }
    );

    errorDiv.append( messageParagraph, dismissButton );
    $( '#notices' ).html( errorDiv );
}
