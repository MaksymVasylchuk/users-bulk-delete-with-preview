(function ($) {
    'use strict';

    const {__, _x, _n, _nx} = wp.i18n;

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
                "url": localizedData.ajaxurl,   // URL to fetch data from
                "data": {
                    "action": 'logs_datatables', // Action to be handled by the server-side script
                    "logs_datatable_nonce": $('#logs_datatable_nonce').val()
                },
                "dataSrc": function ( json ) {
                  if(typeof json.success !== 'undefined' && !json.success) {
                      createWordpressError( json.data.message || __( 'An unexpected error occurred.', 'users-bulk-delete-with-preview' ) );
                  }
                  return json.data;
                },
                "error": function (xhr, error, code) {
                    createWordpressError( error || __( 'An unexpected error occurred.', 'users-bulk-delete-with-preview' ) );
                }
            },
            "language": {
                "emptyTable": localizedData.emptyTable,
                "info": localizedData.info,
                "infoEmpty": localizedData.infoEmpty,
                "infoFiltered": localizedData.infoFiltered,
                "lengthMenu": localizedData.lengthMenu,
                "loadingRecords": localizedData.loadingRecords,
                "processing": localizedData.processing,
                "search": localizedData.search,
                "zeroRecords": localizedData.zeroRecords
            },

            // Define columns in the DataTable
            "columns": [
                {"data": 0},            // Data for the first column
                {"data": 1},            // Data for the second column
                {"data": 2},            // Data for the third column
                {"data": 3},            // Data for the fourth column
                {"data": 4}             // Data for the fifth column
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
                html: '<span class="screen-reader-text">Dismiss this notice.</span>',
                click: () => errorDiv.hide()
            }
        );

        errorDiv.append( messageParagraph, dismissButton );
        $( '#notices' ).html( errorDiv );
    }

})( jQuery );