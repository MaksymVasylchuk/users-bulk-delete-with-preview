(function ($) {
    'use strict';

    const {__, _x, _n, _nx, sprintf} = wp.i18n;

    // Variables
    const form         = '#search_users_form'; // The ID of the user search form
    const translations = ubdwpData.translations || {}; // Server-side translated strings
    let currentStep    = 1; // Tracks the current step in a multi-step process
    let previewUserIds = []; // IDs of all users shown in the preview table

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
                    url: ubdwpData.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        action: 'ubdwp_search_users',
                        q: params.term,
                        nonce: $( '#search_user_existing_nonce' ).val()
                    }),
                    processResults: data => {
                        clearErrors( form ); // Clear any existing errors
                        return {results: data.success ? data.data.results : []};
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
                tags: true,
                ajax: {
                    url: ubdwpData.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        action: 'ubdwp_search_usermeta',
                        q: params.term,
                        nonce: $( '#search_user_meta_nonce' ).val()
                    }),
                    processResults: data => ({results: data.success && Array.isArray( data.data ) ? data.data : []})
                },
                placeholder: __( 'Select meta field', 'users-bulk-delete-with-preview' ),
                minimumInputLength: 1
            }
        );

        initializeSelect2WithHeightAdjustment( '#user_role', __( 'Select user roles', 'users-bulk-delete-with-preview' ) );
        initializeSelect2WithHeightAdjustment( '#products', __( 'Select products that bought user', 'users-bulk-delete-with-preview' ) );

        // Initial setting for an empty Select2
        $( '.select2-selection--multiple' ).css( 'height', '35px' );

        // Initialize the date pickers
        $( '#registration_date, #registration_date_to' ).datepicker(
            {
                changeMonth: true,
                changeYear: true,
                dateFormat: 'yy-mm-dd'
            }
        );

        // The second date is only used for the "between" comparison
        $( '#registration_date_compare' ).on(
            'change',
            function () {
                const isBetween = 'between' === $( this ).val();
                $( '#registration_date_to_wrap' ).toggle( isBetween );

                if ( ! isBetween) {
                    $( '#registration_date_to' ).val( '' );
                }
            }
        ).trigger( 'change' );

        const select = document.getElementById('user_meta_equal');
        const valueInput = document.getElementById('user_meta_value');

        function toggleInput() {
            if (select.value === 'meta_not_exists' || select.value === 'meta_is_empty') {
                valueInput.style.display = 'none';
            } else {
                valueInput.style.display = 'inline-block';
            }
        }

        select.addEventListener('change', toggleInput);
        toggleInput(); // Initial check
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
                            url: ubdwpData.ajaxurl,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'ubdwp_search_users',
                                q: '',
                                nonce: $( '#search_user_existing_nonce' ).val(),
                                select_all: true
                            },
                            success: function (data) {
                                hideLoader();

                                if ( ! data.success) {
                                    handleErrorResponse( data );
                                    return;
                                }

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
                        url: ubdwpData.ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: $( form ).serialize(),
                        success: function (response) {
                            hideLoader();
                            if (response.success) {
                                // Show step 2 first: DataTables measures the container to size paging and columns.
                                const preview = Array.isArray( response.data ) ? {rows: response.data} : response.data;

                                currentStep = 2;
                                showStep( currentStep );
                                setupUserTable( preview.rows );

                                // Very large result sets are loaded in parts to keep the page responsive
                                if (preview.truncated && preview.message) {
                                    createWordpressError( preview.message, 'warning' );
                                }
                            } else {
                                handleErrorResponse( response );
                            }
                        },
                        error: function () {
                            hideLoader();
                            createWordpressError( __( 'An unexpected error occurred.', 'users-bulk-delete-with-preview' ) );
                        }
                    }
                );
            }
        );

        // Show confirmation modal before delete
        $( document ).on(
            'click',
            '.deleteButton',
            () => {
                clearWordpressError();

                const users = getCheckedUsers();

                if ( ! users.length) {
                    createWordpressError( translations.selectAnyUser );
                    return;
                }

                showLoader();
                $.ajax(
                    {
                        url: ubdwpData.ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'ubdwp_delete_summary',
                            delete_users_nonce: $( '#delete_users_nonce' ).val(),
                            users: users
                        },
                        success: function (response) {
                            hideLoader();

                            if ( ! response.success) {
                                handleErrorResponse( response );
                                return;
                            }

                            showDeleteSummary( response.data );
                            $( '#confirmModal' ).modal( 'show' );
                        },
                        error: function () {
                            hideLoader();
                            createWordpressError( __( 'An unexpected error occurred.', 'users-bulk-delete-with-preview' ) );
                        }
                    }
                );
            }
        );

        // Enable the delete button only when the typed number matches
        $( document ).on(
            'input',
            '#ubdwp_confirm_input',
            function () {
                const expected = String( $( '#confirmDelete' ).data( 'expected' ) || '' );
                $( '#confirmDelete' ).prop( 'disabled', '' !== expected && $( this ).val().trim() !== expected );
            }
        );

        // Enter in the confirmation field confirms once the number matches
        $( document ).on(
            'keydown',
            '#ubdwp_confirm_input',
            function (e) {
                if ('Enter' === e.key) {
                    e.preventDefault();

                    if ( ! $( '#confirmDelete' ).prop( 'disabled' )) {
                        $( '#confirmDelete' ).trigger( 'click' );
                    }
                }
            }
        );

        // Focus the confirmation field, or the safe Cancel button, when the dialog opens
        $( document ).on(
            'shown.bs.modal',
            '#confirmModal',
            function () {
                if ($( '#ubdwp_confirm_typing' ).is( ':visible' )) {
                    $( '#ubdwp_confirm_input' ).trigger( 'focus' );
                } else {
                    $( this ).find( '[data-bs-dismiss="modal"].btn' ).trigger( 'focus' );
                }
            }
        );

        /**
         * Fill the confirmation dialog with what the deletion will do
         *
         * @param {object} summary - Summary returned by the server
         */
        function showDeleteSummary(summary) {
            const $list = $( '#ubdwp_delete_summary' ).empty();
            const add   = text => $list.append( $( '<li>' ).text( text ) );

            add( sprintf( translations.summarySelected, summary.selected ) );
            add( sprintf( summary.multisite ? translations.summaryRemovable : translations.summaryDeletable, summary.deletable ) );

            if (summary.reassign_users > 0) {
                add( sprintf( translations.summaryReassign, summary.reassign_users, summary.reassign_posts, summary.reassign_targets.join( ', ' ) ) );
            }

            if (summary.default_users > 0) {
                add( summary.multisite ? sprintf( translations.summaryKeep, summary.default_users ) : sprintf( translations.summaryTrash, summary.default_users, summary.default_posts ) );
            }

            if (summary.remove_users > 0) {
                add( sprintf( translations.summaryRemove, summary.remove_users, summary.remove_posts, summary.remove_comments ) );
            }

            if (summary.skipped > 0) {
                add( sprintf( translations.summarySkipped, summary.skipped ) );
            }

            $( '#ubdwp_delete_skipped' ).toggle( summary.skipped > 0 ).find( 'tbody' ).html( summary.skipped_template );

            const $confirm = $( '#confirmDelete' );
            $( '#ubdwp_confirm_input' ).val( '' );

            if (summary.deletable < 1) {
                $( '#ubdwp_confirm_text' ).text( translations.nothingToDelete );
                $( '#ubdwp_confirm_typing' ).hide();
                $confirm.prop( 'disabled', true ).data( 'expected', '' );
            } else if (summary.confirm_required) {
                $( '#ubdwp_confirm_text' ).text( sprintf( translations.confirmTyping, summary.deletable ) );
                $( '#ubdwp_confirm_typing' ).show();
                $confirm.prop( 'disabled', true ).data( 'expected', summary.deletable );
            } else {
                $( '#ubdwp_confirm_text' ).text( translations.confirmIrreversible );
                $( '#ubdwp_confirm_typing' ).hide();
                $confirm.prop( 'disabled', false ).data( 'expected', '' );
            }
        }

        // Handle delete confirmation
        $( document ).on(
            'click',
            '#confirmDelete',
            () => {
                if ($( '#confirmDelete' ).prop( 'disabled' )) {
                    return;
                }

                $( '#confirmModal' ).modal( 'hide' );

                const users = getCheckedUsers();

                if ( ! users.length) {
                    createWordpressError( translations.selectAnyUser );
                    return;
                }

                showProgressBar();
                disableButtonsOnTheSecondStep();
                $( '#user_delete_success_list, #user_delete_failed_list' ).empty();
                $( '#user_delete_failed' ).hide();

                const totalUsers = users.length;
                const state      = {
                    users: users,
                    totalUsers: totalUsers,
                    batchSize: totalUsers > 10 ? 10 : (totalUsers < 5 ? 1 : 5),
                    processed: 0,
                    deleted: 0,
                    failed: 0
                };

                updateProgressOfUserDeletion( 0, totalUsers );
                processUsersDeletionBatch( state );
            }
        );

        // Process the next batch of checked users
        function processUsersDeletionBatch(state) {
            const batch = state.users.slice( state.processed, state.processed + state.batchSize );

            $.ajax(
                {
                    url: ubdwpData.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: $( '#delete_users_action' ).val(),
                        delete_users_nonce: $( '#delete_users_nonce' ).val(),
                        users: batch
                    },
                    success: function (response) {
                        if ( ! response.success) {
                            handleUsersDeletionFailure( state, response );
                            return;
                        }

                        state.processed += batch.length;
                        state.deleted   += parseInt( response.data.deleted_count, 10 ) || 0;
                        state.failed    += parseInt( response.data.failed_count, 10 ) || 0;

                        $( '#user_delete_success_list' ).append( response.data.template );

                        if (response.data.failed_template && response.data.failed_template.trim()) {
                            $( '#user_delete_failed_list' ).append( response.data.failed_template );
                            $( '#user_delete_failed' ).show();
                        }

                        updateProgressOfUserDeletion( state.processed, state.totalUsers );

                        if (state.processed < state.totalUsers) {
                            processUsersDeletionBatch( state );
                        } else {
                            finishBatchProcess( state );
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.error( 'AJAX error:', textStatus, errorThrown );
                        handleUsersDeletionFailure( state, null );
                    }
                }
            );
        }

        function updateProgressOfUserDeletion(processed, totalUsers) {
            const percentComplete = totalUsers ? (processed / totalUsers) * 100 : 0;
            $( '#progressBarInner' ).css( 'width', percentComplete + '%' );
            $( '#deletedCount' ).text( `${processed} / ${totalUsers} (${Math.round( percentComplete )}%)` );
        }

        function finishBatchProcess(state) {
            activateButtonsOnTheSecondStep();
            hideProgressBar();

            let message = sprintf( translations.deleteSuccess, state.deleted );

            if (state.failed > 0) {
                message += ' ' + sprintf( translations.deleteFailed, state.failed );
            }

            $( '#user_delete_success_heading' ).text( message );
            currentStep = 3;
            showStep( currentStep );
        }

        function handleUsersDeletionFailure(state, response) {
            // Users from earlier batches are already gone, so show them instead of hiding the result.
            if (state.deleted > 0) {
                state.failed += state.totalUsers - state.processed;
                finishBatchProcess( state );
            } else {
                activateButtonsOnTheSecondStep();
                hideProgressBar();
            }

            if (response) {
                handleErrorResponse( response );
            } else {
                createWordpressError( __( 'An unexpected error occurred.', 'users-bulk-delete-with-preview' ) );
            }
        }

        // Handle export button click
        $( document ).on(
            'click',
            '.export-users-button',
            function (e) {
                e.preventDefault();
                clearWordpressError();

                const users = getCheckedUsers();

                if ( ! users.length) {
                    createWordpressError( translations.selectAnyUser );
                    return;
                }

                showLoader();
                $.ajax(
                    {
                        url: ubdwpData.ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'ubdwp_export_users',
                            export_users_nonce: $( '#export_users_nonce' ).val(),
                            users: users.map( user => ({value: user.id}) )
                        },
                        success: function (response) {
                            hideLoader();

                            if (response.success) {
                                downloadFile( response.data.content, response.data.file_name, 'text/csv;charset=utf-8' );
                            } else {
                                handleErrorResponse( response );
                            }
                        },
                        error: function () {
                            hideLoader();
                            createWordpressError( __( 'An unexpected error occurred.', 'users-bulk-delete-with-preview' ) );
                        }
                    }
                );
            }
        );
    }

    /**
     * Download text content as a file without storing it on the server
     *
     * @param {string} content - File content
     * @param {string} fileName - File name
     * @param {string} mimeType - File MIME type
     */
    function downloadFile(content, fileName, mimeType) {
        const url  = URL.createObjectURL( new Blob( [content], {type: mimeType} ) );
        const link = document.createElement( 'a' );

        link.href     = url;
        link.download = fileName;
        document.body.appendChild( link );
        link.click();
        link.remove();

        setTimeout( () => URL.revokeObjectURL( url ), 1000 );
    }

    /**
     * Get row nodes of the preview table, including rows on other pages
     *
     * @return {jQuery} Row nodes
     */
    function getTableRowNodes() {
        if ( ! $.fn.DataTable.isDataTable( '#userTable' )) {
            return $();
        }

        return $( $( '#userTable' ).DataTable().rows().nodes() );
    }

    /**
     * Collect checked users from all pages of the preview table
     *
     * @return {Array<object>} Checked users
     */
    function getCheckedUsers() {
        const users = [];

        getTableRowNodes().find( 'input.user-checkbox:checked:not(:disabled)' ).each(
            function () {
                const $row = $( this ).closest( 'tr' );
                const id   = String( $( this ).val() );

                users.push(
                    {
                        id: id,
                        reassign: $row.find( 'select.user-select' ).val() || ''
                    }
                );
            }
        );

        return users;
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

        if (response && response.data && response.data.errors) {
            showErrorsUnderInputs( form, response.data.errors );
        } else {
            createWordpressError( (response && response.data && response.data.message) || __( 'An unexpected error occurred.', 'users-bulk-delete-with-preview' ) );
        }
    }

    /**
     * Create a WordPress styled error message
     *
     * @param {string} message - The error message
     */
    function createWordpressError(message, type = 'error') {
        const errorDiv         = $( '<div>', {class: 'notice notice-' + ('warning' === type ? 'warning' : 'error') + ' is-dismissible'} );
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

        previewUserIds = data.map( user => user.ID );

        const escapeText = $.fn.dataTable.render.text();

        let usersTable = $( '#userTable' ).DataTable(
            {
                data: data,
                // Row nodes of all pages must exist so checked users and reassign values survive paging.
                deferRender: false,
                responsive: true,
                columns: [
                    {
                        title: '<input type="checkbox" id="select-all">',
                        data: 'checkbox',
                        orderable: false,
                        searchable: false
                    },
                    {title: translations.id, data: 'ID', render: escapeText},
                    {title: translations.username, data: 'user_login', render: escapeText},
                    {title: translations.email, data: 'user_email', render: escapeText},
                    {title: translations.registered, data: 'user_registered', render: escapeText},
                    {title: translations.role, data: 'user_role', render: escapeText},
                    {title: translations.posts, data: 'post_count', render: escapeText},
                    {
                        title: translations.assignContent,
                        data: 'select',
                        orderable: false,
                        searchable: false
                    }
                ],
                language: {
                    emptyTable: translations.emptyTable,
                    info: translations.info,
                    infoEmpty: translations.infoEmpty,
                    infoFiltered: translations.infoFiltered,
                    lengthMenu: translations.lengthMenu,
                    loadingRecords: translations.loadingRecords,
                    processing: translations.processing,
                    search: translations.search,
                    zeroRecords: translations.zeroRecords
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

        initializeGeneralSelectOptions();
        initializeVisibleReassignSelects();

        // Rows on other pages are initialized when they are drawn
        usersTable.on( 'draw', initializeVisibleReassignSelects );

        // Trigger resize event
        $( window ).trigger( 'resize' );
    }

    /**
     * Select2 options for searching reassign target users via AJAX
     *
     * @param {string} width - Select width
     *
     * @return {object} Select2 options
     */
    function getReassignSelect2Options(width) {
        return {
            width: width,
            ajax: {
                url: ubdwpData.ajaxurl,
                type: 'POST',
                dataType: 'json',
                delay: 250,
                data: params => ({
                    action: 'ubdwp_search_reassign_users',
                    q: params.term || '',
                    nonce: ubdwpData.reassignUsersNonce,
                    exclude: previewUserIds
                }),
                processResults: data => ({
                    results: [
                        {id: '', text: translations.selectUser},
                        {id: 'remove_all_related_content', text: translations.removeContent}
                    ].concat( data.success ? data.data.results : [] )
                })
            }
        };
    }

    /**
     * Initialize Select2 on reassign selects of the currently drawn rows
     */
    function initializeVisibleReassignSelects() {
        $( '#userTable tbody select.user-select' ).each(
            function () {
                if ( ! $( this ).data( 'select2' )) {
                    $( this ).select2( getReassignSelect2Options( '200px' ) );
                }
            }
        );
    }

    /**
     * Set a value on a reassign select, adding the option if it was loaded via AJAX
     *
     * @param {jQuery} $select - Select element
     * @param {string} value - Option value
     * @param {string} text - Option label
     */
    function setReassignSelectValue($select, value, text) {
        if ( ! $select.find( 'option' ).filter( (index, option) => option.value === value ).length) {
            $select.append( new Option( text, value, false, false ) );
        }

        $select.val( value ).trigger( 'change' );
    }

    /**
     * Initialize general select options for the user table
     */
    function initializeGeneralSelectOptions() {
        const $generalSelect = $( '#generalSelect' );

        if ($generalSelect.data( 'select2' )) {
            $generalSelect.select2( 'destroy' );
        }

        $generalSelect
            .off( '.ubdwp' )
            .empty()
            .append( new Option( translations.selectUser, '', true, true ) )
            .append( new Option( translations.removeContent, 'remove_all_related_content', false, false ) )
            .select2( getReassignSelect2Options( '400px' ) );

        // Handle the "Select All" checkbox
        $( '#select-all' ).off( '.ubdwp' ).on(
            'click.ubdwp',
            function () {
                const rows = $( '#userTable' ).DataTable().rows( {'search': 'applied'} ).nodes();
                // Protected users have a disabled checkbox and are never selected
                $( 'input.user-checkbox:not(:disabled)', rows ).prop( 'checked', this.checked );
            }
        );

        // Handle individual user checkbox click
        $( '#userTable tbody' ).off( '.ubdwp' ).on(
            'click.ubdwp',
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

        // Apply the general value to user selects on all table pages
        $generalSelect.on(
            'change.ubdwp',
            function () {
                const value = String( $( this ).val() || '' );
                const text  = $( this ).find( 'option:selected' ).text();

                getTableRowNodes().find( 'select.user-select' ).each(
                    function () {
                        setReassignSelectValue( $( this ), value, text );
                    }
                );
            }
        );
    }
})( jQuery );
