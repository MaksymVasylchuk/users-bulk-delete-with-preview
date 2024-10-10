(function ($) {
    'use strict';

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
                "url": myAjax.ajaxurl,   // URL to fetch data from
                "data": {
                    "action": 'logs_datatables', // Action to be handled by the server-side script
                    "logs_datatable_nonce": $('#logs_datatable_nonce').val()
                }
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

})( jQuery );