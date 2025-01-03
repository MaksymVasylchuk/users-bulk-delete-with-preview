(function ($) {
    'use strict';

    const {__, _x, _n, _nx} = wp.i18n;

    // Variables
    const form      = '#search_users_form'; // The ID of the user search form
    let currentStep = 1; // Tracks the current step in a multi-step process

    /**
     * Initialize the script when the document is ready
     */
    $( document ).ready(
        function () {
            initializeUserSearch();               // Initialize user search with Select2
            initializeDropdownsAndDatePicker();   // Initialize dropdowns and date picker
            initializeEventListeners();           // Set up event listeners
            handleSelectAllUsers();               // Handle "Select All Users" functionality
            handleSelectAllProducts();            // Handle "Select All Products" functionality
            handleSelectAllSubscriptions();       // Handle "Select All Subscriptions" functionality
        }
    );

    /**
     * Initialize Select2 for user search
     */
    function initializeUserSearch() {
        var $user_search_select = $( '#user_search' ).select2(
            {
                placeholder: __( 'Search for users', 'users-bulk-delete-with-preview' ),
                width: '400px',
                ajax: {
                    url: localizedData.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        action: 'search_users',
                        q: params.term,
                        nonce: $( '#search_user_existing_nonce' ).val()
                    }),
                    processResults: data => {
                        clearErrors( form ); // Clear any existing errors
                        return {results: data.data.results};
                    }
                }
            }
        );

        // Adjust the height based on the selection
        $user_search_select.on(
            'select2:select select2:unselect',
            function () {
                adjustSelect2Height( $( this ) );
            }
        );

        // Initial setting for an empty Select2
        $( '.select2-selection--multiple' ).css( 'height', '35px' );
    }

    /**
     * Initialize other dropdowns and date picker
     */
    function initializeDropdownsAndDatePicker() {
        $( '#user_meta' ).select2(
            {
                width: '400px',
                ajax: {
                    url: localizedData.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        action: 'search_usermeta',
                        q: params.term,
                        nonce: $( '#search_user_meta_nonce' ).val()
                    }),
                    processResults: data => ({results: data.data})
                },
                placeholder: __( 'Select meta field', 'users-bulk-delete-with-preview' ),
                minimumInputLength: 1
            }
        );

        initializeSelect2WithHeightAdjustment( '#user_role', __( 'Select user roles', 'users-bulk-delete-with-preview' ) );
        initializeSelect2WithHeightAdjustment( '#products', __( 'Select products that bought user', 'users-bulk-delete-with-preview' ) );

        // Initial setting for an empty Select2
        $( '.select2-selection--multiple' ).css( 'height', '35px' );

        // Initialize the date picker
        $( '#registration_date' ).datepicker(
            {
                changeMonth: true,
                changeYear: true,
                dateFormat: 'yy-mm-dd',
                placeholder: __( 'Select registration date', 'users-bulk-delete-with-preview' )
            }
        );
    }

    /**
     * Initialize Select2 with height adjustment on select/unselect
     *
     * @param {string} selector - The jQuery selector for the element
     * @param {string} placeholder - Placeholder for the element
     */
    function initializeSelect2WithHeightAdjustment(selector, placeholder) {
        var $select = $( selector ).select2(
            {
                width: '400px',
                placeholder: placeholder
            }
        );

        // Adjust the height based on the selection
        $select.on(
            'select2:select select2:unselect',
            function () {
                adjustSelect2Height( $( this ) );
            }
        );
    }

    /**
     * Adjust the height of Select2 based on the selection
     *
     * @param {object} $element - The jQuery element of Select2
     */
    function adjustSelect2Height($element) {
        var $selection = $element.next( '.select2-container' ).find( '.select2-selection--multiple .select2-selection__rendered' );

        if ($selection.children( '.select2-selection__choice' ).length === 0) {
            $( '.select2-selection--multiple' ).css( 'height', '35px' );
        } else {
            $( '.select2-selection--multiple' ).css( 'height', 'auto' );
        }
    }

    /**
     * Handle "Select All Users" checkbox functionality
     */
    function handleSelectAllUsers() {
        $( '#selectAllUsers' ).on(
            'change',
            function () {
                if ($( this ).is( ':checked' )) {
                    showLoader();
                    $.ajax(
                        {
                            url: localizedData.ajaxurl,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'search_users',
                                q: '',
                                nonce: $( '#search_user_existing_nonce' ).val(),
                                select_all: true
                            },
                            success: function (data) {
                                hideLoader();
                                const allIds     = data.data.results.map(
                                    item => {
                                        const option = new Option( item.text, item.id, true, true );
                                        $( '#user_search' ).append( option ).trigger( 'change' );
                                        return item.id;
                                    }
                                );

                                $( '#user_search' ).val( allIds ).trigger( 'change' );
                                $( '#user_search' ).trigger( 'select2:select' );
                            },
                            error: function(data) {
                                console.log( 'Error fetching users' );
                                hideLoader();
                            }
                        }
                    );
                } else {
                    $( '#user_search' ).empty().trigger( 'change' ).val( null ).trigger( 'change' );
                    $( '#user_search' ).trigger( 'select2:unselect' );
                }
            }
        );
    }

    /**
     * Handle "Select All Products" checkbox functionality
     */
    function handleSelectAllProducts() {
        $( '#selectAllProducts' ).on(
            'change',
            function () {
                if ($( this ).is( ':checked' )) {
                    $( "#products > option" ).prop( "selected", "selected" );
                    $( "#products" ).trigger( "change" );
                } else {
                    $( "#products > option" ).removeAttr( "selected" );
                    $( "#products" ).trigger( "change" );
                }
            }
        );
    }

    /**
     * Handle "Select All Subscriptions" checkbox functionality
     */
    function handleSelectAllSubscriptions() {
        $( '#selectAllSubscriptions' ).on(
            'change',
            function () {
                if ($( this ).is( ':checked' )) {
                    $( "#subscriptions > option" ).prop( "selected", "selected" );
                    $( "#subscriptions" ).trigger( "change" );
                } else {
                    $( "#subscriptions > option" ).removeAttr( "selected" );
                    $( "#subscriptions" ).trigger( "change" );
                }
            }
        );
    }

    /**
     * Initialize event listeners
     */
    function initializeEventListeners() {
        // Handle navigation between steps
        $( '.previous_step' ).click(
            () => {
                currentStep--;
                showStep( currentStep );
            }
        );

        // Handle filter type changes
        $( '#filter_type' ).change(
            function () {
                const selectedType = $( this ).val();

                $( '.select_existing_form, .find_users_form, .woocommerce_filters_form' ).hide();

                switch (selectedType) {
                    case 'select_existing':
                        $( '.select_existing_form' ).show();
                        break;
                    case 'find_users':
                        $( '.find_users_form' ).show();
                        break;
                    case 'find_users_by_woocommerce_filters':
                        $( '.woocommerce_filters_form' ).show();
                        break;
                }
            }
        ).trigger( 'change' );

        // Handle preview before remove action
        $( document ).on(
            'click',
            '.preview_before_remove',
            function (e) {
                e.preventDefault();
                showLoader();

                $.ajax(
                    {
                        url: localizedData.ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: $( form ).serialize(),
                        success: function (response) {
                            hideLoader();
                            if (response.success) {
                                setupUserTable( response.data );
                                currentStep = 2;
                                showStep( currentStep );
                            } else {
                                handleErrorResponse( response );
                            }
                        },
                        error: hideLoader
                    }
                );
            }
        );

        let deletedCount = 0; // Declare deletedCount in the outer scope
        let batchSize = 10; // Number of users processed per batch

        // Show confirmation modal before delete
        $( document ).on(
            'click',
            '.deleteButton',
            () => {
                $( '#confirmModal' ).modal( 'show' );
            }
        );

        // Handle delete confirmation
        $( document ).on(
            'click',
            '#confirmDelete',
            () => {
                $( '#confirmModal' ).modal( 'hide' );
                showProgressBar();
                disableButtonsOnTheSecondStep();

                let users = extractUsersFromForm();
                let totalUsers = users.length;

                batchSize = totalUsers > 10 ? 10 : (totalUsers < 5 ? 1 : 5);

                // Reset deletedCount before starting
                deletedCount = 0;

                // Start processing the first batch
                let firstBatch = users.slice(0, batchSize);
                processUsersDeletionBatch(firstBatch, users, batchSize, totalUsers);
            }
        );

        // Extract serialized user data from the form
        function extractUsersFromForm() {
            const formData = $('#select_users_for_delete').serializeArray();
            let users = {};

            formData.forEach(field => {
                const match = field.name.match(/users\[(\d+)\]\[(\w+)\]/);
                if (match) {
                    const [_, userId, fieldName] = match;
                    users[userId] = users[userId] || {}; // Initialize if doesn't exist
                    users[userId][fieldName] = field.value; // Assign value
                }
            });

            return Object.values(users); // Return users as an array
        }

        // Function to process each batch of users
        function processUsersDeletionBatch(batch, usersArray, batchSize, totalUsers) {
            $.ajax({
                url: localizedData.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: $('#delete_users_action').val(),
                    delete_users_nonce: $('#delete_users_nonce').val(),
                    users: batch
                },
                success: function(response) {
                    if (response.success) {
                        deletedCount += batch.length; // Increment deletedCount
                        updateProgressOfUserDeletion(deletedCount, totalUsers); // Update progress bar and count

                        if (deletedCount < totalUsers) {
                            let nextBatch = usersArray.slice(deletedCount, deletedCount + batchSize);
                            $('#user_delete_success_list').append(response.data.template);
                            processUsersDeletionBatch(nextBatch, usersArray, batchSize, totalUsers); // Run next batch
                        } else {
                            finishBatchProcess(response, usersArray); // Finish processing
                        }
                    } else {
                        handleUsersDeletionFailure(response);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
                    handleUsersDeletionFailure();
                }
            });
        }

        function updateProgressOfUserDeletion(deletedCount, totalUsers) {
            const percentComplete = (deletedCount / totalUsers) * 100;
            $('#progressBarInner').css('width', percentComplete + '%');
            $('#deletedCount').text(`${deletedCount} / ${totalUsers} (${Math.round(percentComplete)}%)`);
        }

        function finishBatchProcess(response, usersArray) {
            activateButtonsOnTheSecondStep();
            hideProgressBar();
            $('#user_delete_success_list').append(response.data.template);
            let message = `Success! All selected users (${usersArray.length}) were removed!`;
            $('#user_delete_success_heading').html(message);
            currentStep = 3;
            showStep(currentStep);
        }

        function handleUsersDeletionFailure(response = null) {
            activateButtonsOnTheSecondStep();
            hideProgressBar();
            if (response) handleErrorResponse(response);
        }


        // Handle export button click
        $( document ).on(
            'click',
            '.export-users-button',
            function (e) {
                e.preventDefault();
                showLoader();
                $.ajax(
                    {
                        url: localizedData.ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'custom_export_users',
                            export_users_nonce: $( '#export_users_nonce' ).val(),
                            users: $( '.user-checkbox:checked' ).serializeArray()
                        },
                        success: function (response) {
                            if (response.success) {
                                var fileUrl = response.data.file_url;

                                // Trigger download
                                window.location.href = fileUrl;

                                // Delete the file after download
                                setTimeout(
                                    function () {
                                        $.ajax(
                                            {
                                                url: ajaxurl,
                                                type: 'POST',
                                                data: {
                                                    action: 'delete_exported_file',
                                                    nonce: localizedData.ajaxurl,
                                                    file_path: response.data.file_path
                                                },
                                                success: function (response) {
                                                    hideLoader();
                                                },
                                                error: hideLoader
                                            }
                                        );
                                    },
                                    1000
                                ); // Wait for download to complete
                            } else {
                                handleErrorResponse( response );
                            }

                        },
                        error: function (data) {
                            hideLoader();
                        }
                    }
                );
            }
        );
    }

    /**
     * Show the specified step in the form
     *
     * @param {number} step - The step number to show
     */
    function showStep(step) {
        clearWordpressError();
        $( '.form-step' ).hide();
        $( '#step-' + step ).show();
        $( '.step_icon' ).removeAttr( 'disabled' ).removeClass( 'btn-primary' ).addClass( 'btn-default' );
        $( '#step_icon_' + step ).removeAttr( 'disabled' ).removeClass( 'btn-default' ).addClass( 'btn-primary' );
    }

    /**
     * Clear errors for the given form
     *
     * @param {string} form_id - The ID of the form to clear errors from
     */
    function clearErrors(form_id) {
        $( form_id + ' :input' ).each(
            function () {
                const $input = $( this );
                const id     = $input.attr( 'id' );

                $input.removeClass( 'is-invalid is-valid' );
                $( form_id + ' #' + id + '+.invalid-feedback' ).html( "" );
            }
        );
    }

    /**
     * Show errors under input fields
     *
     * @param {string} form_id - The ID of the form
     * @param {object} errors - The errors object
     */
    function showErrorsUnderInputs(form_id, errors) {
        clearErrors( form_id );

        if (errors) {
            $.each(
                errors,
                function (index, item) {
                    index        = index.replace( /\./g, '_' );
                    const $input = $( form_id + ' #' + index );

                    $input.removeClass( 'is-valid' ).addClass( 'is-invalid' );
                    $input.parent().find( '.invalid-feedback' ).html( item );
                }
            );
        }
    }

    /**
     * Handle error response from AJAX requests
     *
     * @param {object} response - The AJAX response object
     */
    function handleErrorResponse(response) {
        hideLoader();
        clearErrors( form );

        if (response.data && response.data.errors) {
            showErrorsUnderInputs( form, response.data.errors );
        } else {
            createWordpressError( response.data.message || __( 'An unexpected error occurred.', 'users-bulk-delete-with-preview' ) );
        }
    }

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

    /**
     * Show the loading spinner
     */
    function showLoader() {
        $( '#page_loader' ).show();
    }

    /**
     * Show the progress bar
     */
    function showProgressBar() {
        // Show progress bar
        $('#deleteProgressBar').show();
        $('#progressBarInner').css('width', '0%');
    }

    /**
     * Hide the progress bar
     */
    function hideProgressBar() {
        $('#deleteProgressBar').hide();
    }

    /**
     * Disable buttons
     */
    function disableButtonsOnTheSecondStep() {
        $('.previous_step').prop('disabled', true);
        $('.export-users-button').prop('disabled', true);
        $('.deleteButton').prop('disabled', true);
    }

    /**
     * Activate buttons
     */
    function activateButtonsOnTheSecondStep() {
        $('.previous_step').prop('disabled', false);
        $('.export-users-button').prop('disabled', false);
        $('.deleteButton').prop('disabled', false);
    }

    /**
     * Hide the loading spinner
     */
    function hideLoader() {
        $( '#page_loader' ).hide();
    }

    function clearWordpressError() {
        $( '#notices' ).html( "" );
    }

    /**
     * Setup the user table with data
     *
     * @param {object} data - The data for the table
     */
    function setupUserTable(data) {
        if ($.fn.DataTable.isDataTable( '#userTable' )) {
            $( '#userTable' ).DataTable().clear().destroy();
        }

        let usersTable = $( '#userTable' ).DataTable(
            {
                data: data,
                responsive: true,
                columns: [
                    {
                        title: '<input type="checkbox" id="select-all">',
                        data: 'checkbox',
                        orderable: false,
                        searchable: false
                    },
                    {title: localizedData.id, data: 'ID'},
                    {title: localizedData.username, data: 'user_login'},
                    {title: localizedData.email, data: 'user_email'},
                    {title: localizedData.registered, data: 'user_registered'},
                    {title: localizedData.role, data: 'user_role'},
                    {
                        title: localizedData.assignContent,
                        data: 'select',
                        orderable: false,
                        searchable: false
                    }
                ],
                language: {
                    emptyTable: localizedData.emptyTable,
                    info: localizedData.info,
                    infoEmpty: localizedData.infoEmpty,
                    infoFiltered: localizedData.infoFiltered,
                    lengthMenu: localizedData.lengthMenu,
                    loadingRecords: localizedData.loadingRecords,
                    processing: localizedData.processing,
                    search: localizedData.search,
                    zeroRecords: localizedData.zeroRecords
                },
                order: [[1, 'asc']],
                lengthMenu: [
                    [10, 25, 50, 75, 100, 250, 500, -1],
                    [10, 25, 50, 75, 100, 250, 500, 'All']
                ],
                initComplete: function () {
                    $( 'input[type="search"]' ).addClass( 'custom-search-class' );
                    $( 'select[name="userTable_length"]' ).addClass( 'custom-select-class' );
                },
                createdRow: function (row) {
                    $( row ).find( 'select.user-select' ).addClass( 'custom-select-class' );
                }
            }
        );

        initializeGeneralSelectOptions( data );

        // Listen for DataTable redraw event
        usersTable.on('draw', function() {
            // Check if Select2 is initialized on this element
            $('.user-select').each(function() {
                // Check if Select2 is initialized on this element
                if ($(this).data('select2')) {
                    $(this).select2('destroy'); // Destroy only if Select2 is initialized
                }
            });

            $('.user-select').select2( {width: '200px'} );
        });

        // Trigger resize event
        $(window).trigger('resize');
    }

    /**
     * Initialize general select options for the user table
     *
     * @param {array} data - The data for the table
     */
    function initializeGeneralSelectOptions(data) {
        const firstRowSelectOptions = $( data[0].select ).html();
        $( '#generalSelect' ).html( firstRowSelectOptions ).select2();
        $( '.user-select' ).select2( {width: '200px'} );

        // Handle the "Select All" checkbox
        $( '#select-all' ).on(
            'click',
            function () {
                const rows = $( '#userTable' ).DataTable().rows( {'search': 'applied'} ).nodes();
                $( 'input[type="checkbox"]', rows ).prop( 'checked', this.checked );
            }
        );

        // Handle individual user checkbox click
        $( '#userTable tbody' ).on(
            'click',
            'input.user-checkbox',
            function () {
                if ( ! this.checked) {
                    const selectAll = $( '#select-all' ).get( 0 );
                    if (selectAll && selectAll.checked && 'indeterminate' in selectAll) {
                        selectAll.indeterminate = true;
                    }
                }
            }
        );

        // Handle general select change event
        $( '#generalSelect' ).on(
            'change',
            function () {
                const selectedValue = $( this ).val();
                // Apply the selected value to all user selects in the table
                $( '.user-select' ).val( selectedValue ).trigger( 'change' );
            }
        );
    }
})( jQuery );