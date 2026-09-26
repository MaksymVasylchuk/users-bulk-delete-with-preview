(function ($) {
    'use strict';

    const {__, _x, _n, _nx} = wp.i18n;
    const translations = ubdwpData.translations || {};
    const escapeText   = $.fn.dataTable.render.text();

    // Initialize DataTable for logs
    $( '#logs' ).DataTable(
        {
            "processing": true,           // Show processing indicator while data is loading
            "serverSide": true,           // Enable server-side processing
            "dataType": "json",           // Data type expected from the server
            "contentType": "application/json", // Content type of the request
            "responsive": true,          // Make the table responsive to different screen sizes
            "ordering": false,           // Disable column ordering
            "ajax": {
                "url": ubdwpData.ajaxurl,   // URL to fetch data from
                "data": {
                    "action": 'ubdwp_logs_datatables', // Action to be handled by the server-side script
                    "logs_datatable_nonce": $('#logs_datatable_nonce').val()
                },
                "dataSrc": function ( json ) {
                  if(typeof json.success !== 'undefined' && !json.success) {
                      createWordpressError( json.data.message || __( 'An unexpected error occurred.', 'users-bulk-delete-with-preview' ) );
                  }

                    json.draw = json.data.draw;
                    json.recordsTotal = json.data.recordsTotal;
                    json.recordsFiltered = json.data.recordsFiltered;

                  return json.data.data || [];
                },
                "error": function (xhr, error, code) {
                    createWordpressError( error || __( 'An unexpected error occurred.', 'users-bulk-delete-with-preview' ) );
                }
            },
            "language": {
                "emptyTable": translations.emptyTable,
                "info": translations.info,
                "infoEmpty": translations.infoEmpty,
                "infoFiltered": translations.infoFiltered,
                "lengthMenu": translations.lengthMenu,
                "loadingRecords": translations.loadingRecords,
                "processing": translations.processing,
                "search": translations.search,
                "zeroRecords": translations.zeroRecords
            },

            // Define columns in the DataTable
            "columns": [
                {"data": 0, "render": escapeText},            // Data for the first column
                {"data": 1, "render": escapeText},            // Data for the second column
                {"data": 2, "render": escapeText},            // Data for the third column
                {"data": 3, "render": escapeText},            // Data for the fourth column
                {"data": 4, "render": escapeText}             // Data for the fifth column
            ]
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

})( jQuery );