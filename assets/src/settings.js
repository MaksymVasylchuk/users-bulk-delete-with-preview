/**
 * Users Bulk Delete With Preview: settings page script.
 *
 * Built with @wordpress/scripts into assets/build/settings.js.
 */
import $ from 'jquery';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Send a privacy request with the nonce and handle errors
 *
 * @param {object} data - Request data
 * @param {function} onSuccess - Called with the response data
 */
const privacyRequest = (data, onSuccess) => {
    $.post( ubdwpData.ajaxurl, Object.assign( {privacy_nonce: $( '#ubdwp_privacy_nonce' ).val()}, data ) ).done(
        response => {
            if (response && response.success) {
                onSuccess( response.data );
            } else {
                createWordpressError( (response && response.data && response.data.message) || __( 'An unexpected error occurred.', 'users-bulk-delete-with-preview' ) );
            }
        }
    ).fail( () => createWordpressError( __( 'An unexpected error occurred.', 'users-bulk-delete-with-preview' ) ) );
};

$( document ).on(
    'click',
    '.ubdwp-privacy-save',
    () => privacyRequest(
        {action: 'ubdwp_privacy_save', retention_days: $( '#ubdwp_retention_days' ).val(), log_email: $( '#ubdwp_log_email' ).val()},
        () => createWordpressError( __( 'Privacy settings saved.', 'users-bulk-delete-with-preview' ), 'success' )
    )
);

$( document ).on(
    'click',
    '.ubdwp-logs-anonymize',
    () => {
        // eslint-disable-next-line no-alert
        if (window.confirm( __( 'Apply the saved email setting to all existing log entries? Masked or removed data cannot be restored.', 'users-bulk-delete-with-preview' ) )) {
            privacyRequest(
                {action: 'ubdwp_logs_anonymize'},
                data => createWordpressError( sprintf( __( 'Updated log entries: %d.', 'users-bulk-delete-with-preview' ), data.updated ), 'success' )
            );
        }
    }
);

$( document ).on(
    'click',
    '.ubdwp-logs-purge-old',
    () => {
        const days = parseInt( $( '#ubdwp_retention_days' ).val(), 10 ) || 0;

        if (days < 1) {
            createWordpressError( __( 'Please enter a number of days.', 'users-bulk-delete-with-preview' ) );
            return;
        }

        // eslint-disable-next-line no-alert
        if (window.confirm( sprintf( __( 'Delete log entries older than %d days? This cannot be undone.', 'users-bulk-delete-with-preview' ), days ) )) {
            privacyRequest(
                {action: 'ubdwp_logs_purge', older_than_days: days},
                data => createWordpressError( sprintf( __( 'Deleted log entries: %d.', 'users-bulk-delete-with-preview' ), data.deleted ), 'success' )
            );
        }
    }
);

$( document ).on(
    'click',
    '.ubdwp-logs-purge-all',
    () => {
        // eslint-disable-next-line no-alert
        if (window.confirm( __( 'Delete the whole log? This cannot be undone.', 'users-bulk-delete-with-preview' ) )) {
            privacyRequest(
                {action: 'ubdwp_logs_purge', confirm: 'all'},
                data => createWordpressError( sprintf( __( 'Deleted log entries: %d.', 'users-bulk-delete-with-preview' ), data.deleted ), 'success' )
            );
        }
    }
);

/**
 * Create a WordPress styled notice
 *
 * @param {string} message - The message
 * @param {string} type - "error" or "success"
 */
function createWordpressError(message, type = 'error') {
    const errorDiv         = $( '<div>', {class: 'notice notice-' + ('success' === type ? 'success' : 'error') + ' is-dismissible'} );
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
    $( '#ubdwp_notices' ).html( errorDiv );
}
