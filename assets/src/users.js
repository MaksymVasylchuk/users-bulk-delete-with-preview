/**
 * Users Bulk Delete With Preview: users page script.
 *
 * Built with @wordpress/scripts into assets/build/users.js (styles into assets/build/users.css).
 */
import $ from 'jquery';
import { __, sprintf } from '@wordpress/i18n';
import './users.css';

// Variables
const form         = '#search_users_form'; // The ID of the user search form
const translations = ubdwpData.translations || {}; // Server-side translated strings
let currentStep    = 1; // Tracks the current step in a multi-step process

// Preview stored on the server: the table only loads the current page
const preview = {token: '', total: 0};

// Selected users: "all users of the preview except some" or "these users", plus content reassignment
const selection = {
    all: false,
    ids: new Set(),
    except: new Set(),
    reassign: {},
    defaultReassign: '',
    labels: {}
};

// Deletion job of this page
const job = {id: 0, mode: 'browser', listed: 0, stopping: false, started: false, preparing: false};

/**
 * Initialize the script when the document is ready
 */
$( document ).ready(
    function () {
        initializeUserSearch();               // Initialize user search with Select2
        initializeDropdownsAndDatePicker();   // Initialize dropdowns and date picker
        initializeEventListeners();           // Set up event listeners
        handleSelectAllUsers();               // Handle "All users of this site"
        handleSelectAllProducts();            // Handle "Select All Products" functionality

        if (ubdwpData.runningJobId) {
            watchRunningBackgroundJob( ubdwpData.runningJobId );
        }
    }
);

/**
 * Initialize Select2 for user search
 */
function initializeUserSearch() {
    $( '#user_search' ).select2(
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

    initializeSelect2( '#user_role', __( 'Select user roles', 'users-bulk-delete-with-preview' ) );
    initializeSelect2(
        '#products',
        __( 'Select products that bought user', 'users-bulk-delete-with-preview' ),
        {
            ajax: {
                url: ubdwpData.ajaxurl,
                type: 'POST',
                dataType: 'json',
                delay: 250,
                data: params => ({
                    action: 'ubdwp_search_products',
                    q: params.term || '',
                    nonce: $( '#search_products_nonce' ).val()
                }),
                processResults: data => ({results: data.success ? data.data.results : []})
            }
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

    const select     = document.getElementById( 'user_meta_equal' );
    const valueInput = document.getElementById( 'user_meta_value' );

    function toggleInput() {
        if (select.value === 'meta_not_exists' || select.value === 'meta_is_empty') {
            valueInput.style.display = 'none';
        } else {
            valueInput.style.display = 'inline-block';
        }
    }

    select.addEventListener( 'change', toggleInput );
    toggleInput(); // Initial check
}

/**
 * Initialize Select2 with the plugin defaults
 *
 * @param {string} selector - The jQuery selector for the element
 * @param {string} placeholder - Placeholder for the element
 * @param {object} options - Additional Select2 options
 */
function initializeSelect2(selector, placeholder, options = {}) {
    $( selector ).select2(
        Object.assign(
            {
                width: '400px',
                placeholder: placeholder
            },
            options
        )
    );
}

/**
 * Handle "All users of this site": the server finds them, so the user list is not needed
 */
function handleSelectAllUsers() {
    $( '#selectAllUsers' ).on(
        'change',
        function () {
            $( '#user_search' ).val( null ).prop( 'disabled', $( this ).is( ':checked' ) ).trigger( 'change' );
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
            // "Select All" matches customers who bought any product, so the product list is not needed
            const allProducts = $( this ).is( ':checked' );

            $( '#products' ).val( null ).prop( 'disabled', allProducts ).trigger( 'change' );
        }
    );
}

/**
 * Send a POST request to admin-ajax.php
 *
 * @param {object} data - Request data
 *
 * @return {Promise<object>} Response data, rejected with a message on failure
 */
function request(data) {
    return new Promise(
        (resolve, reject) => {
            $.ajax(
                {
                    url: ubdwpData.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: data
                }
            ).done(
                response => {
                    if (response && response.success) {
                        resolve( response.data );
                    } else {
                        reject( (response && response.data && response.data.message) || __( 'An unexpected error occurred.', 'users-bulk-delete-with-preview' ) );
                    }
                }
            ).fail(
                () => reject( __( 'An unexpected error occurred.', 'users-bulk-delete-with-preview' ) )
            );
        }
    );
}

/**
 * Data sent with every job request
 *
 * @param {object} data - Request data
 *
 * @return {object} Request data with the nonce
 */
function jobRequest(data) {
    return request( Object.assign( {delete_users_nonce: $( '#delete_users_nonce' ).val()}, data ) );
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

    // Find the users and store them as a preview on the server
    $( document ).on(
        'click',
        '.preview_before_remove',
        function (e) {
            e.preventDefault();
            clearWordpressError();
            showLoader();

            request( $( form ).serialize() ).then(
                data => {
                    hideLoader();
                    preview.token = data.token;
                    preview.total = parseInt( data.total, 10 ) || 0;
                    resetSelection();

                    // Show step 2 first: DataTables measures the container to size paging and columns.
                    currentStep = 2;
                    showStep( currentStep );
                    setupUserTable();
                }
            ).catch( showError );
        }
    );

    // Selection banner actions
    $( document ).on(
        'click',
        '.ubdwp-select-all-matching',
        () => {
            selectAllMatching( true );
        }
    );

    $( document ).on(
        'click',
        '.ubdwp-clear-selection',
        () => {
            selectAllMatching( false );
        }
    );

    // Show the summary before deleting
    $( document ).on( 'click', '.deleteButton', prepareDeletion );

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

    // Close the dialog with its close buttons, or with a click outside of it (Esc closes it natively)
    $( document ).on( 'click', '[data-ubdwp-close]', closeConfirmDialog );

    $( '#confirmModal' ).on(
        'click',
        function (e) {
            if (e.target === this) {
                closeConfirmDialog();
            }
        }
    );

    // Start the deletion
    $( document ).on( 'click', '#confirmDelete', startDeletion );

    // A summary that was not confirmed is not kept as a job
    $( '#confirmModal' ).on(
        'close',
        () => {
            if (job.id && ! job.started) {
                jobRequest( {action: 'ubdwp_job_discard', job_id: job.id} ).catch( () => {} );
                job.id = 0;
            }
        }
    );

    // Stop a running deletion
    $( document ).on( 'click', '#ubdwp_cancel_job', stopDeletion );

    // Handle export button click
    $( document ).on(
        'click',
        '.export-users-button',
        function (e) {
            e.preventDefault();
            clearWordpressError();

            if ( ! getSelectedCount()) {
                createWordpressError( translations.selectAnyUser );
                return;
            }

            showLoader();
            request(
                {
                    action: 'ubdwp_export_users',
                    export_users_nonce: $( '#export_users_nonce' ).val(),
                    token: preview.token,
                    selection_json: getSelectionJson()
                }
            ).then(
                data => {
                    hideLoader();
                    downloadFile( data.content, data.file_name, 'text/csv;charset=utf-8' );
                }
            ).catch( showError );
        }
    );
}

/**
 * Open the confirmation dialog and focus the confirmation field, or the safe Cancel button
 */
function openConfirmDialog() {
    const dialog = document.getElementById( 'confirmModal' );

    if ( ! dialog.open) {
        dialog.showModal();
    }

    $( $( '#ubdwp_confirm_typing' ).is( ':visible' ) ? '#ubdwp_confirm_input' : '#ubdwp_cancel_delete' ).trigger( 'focus' );
}

/**
 * Close the confirmation dialog
 */
function closeConfirmDialog() {
    const dialog = document.getElementById( 'confirmModal' );

    if (dialog.open) {
        dialog.close();
    }
}

/**
 * Create a job for the selected users and show its summary
 */
function prepareDeletion() {
    // A second click while the summary is prepared would create a second job
    if (job.preparing) {
        return;
    }

    clearWordpressError();

    if ( ! getSelectedCount()) {
        createWordpressError( translations.selectAnyUser );
        return;
    }

    job.preparing = true;
    showLoader( __( 'Preparing the summary…', 'users-bulk-delete-with-preview' ) );

    jobRequest(
        {
            action: 'ubdwp_create_job',
            token: preview.token,
            selection_json: getSelectionJson()
        }
    ).then( summarizeJob ).then(
        status => {
            hideLoader();
            job.id      = status.job_id;
            job.started = false;
            showDeleteSummary( status );
            openConfirmDialog();
        }
    ).catch( showError ).finally( () => {
        job.preparing = false;
    } );
}

/**
 * Summarize a job chunk by chunk until it is ready
 *
 * @param {object} status - Job status
 *
 * @return {Promise<object>} Status of the summarized job
 */
function summarizeJob(status) {
    if ('summarizing' !== status.status) {
        return Promise.resolve( status );
    }

    showLoader( sprintf( __( 'Preparing the summary… %d%%', 'users-bulk-delete-with-preview' ), status.percent ) );

    return jobRequest( {action: 'ubdwp_job_summary', job_id: status.job_id} ).then( summarizeJob );
}

/**
 * Fill the confirmation dialog with what the deletion will do
 *
 * @param {object} status - Job status with its summary
 */
function showDeleteSummary(status) {
    const summary = status.summary;
    const $list   = $( '#ubdwp_delete_summary' ).empty();
    const add     = text => $list.append( $( '<li>' ).text( text ) );

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

    if (summary.skipped_count > 0) {
        add( sprintf( translations.summarySkipped, summary.skipped_count ) );
    }

    $( '#ubdwp_delete_skipped' ).toggle( summary.skipped_count > 0 ).find( 'tbody' ).html( status.skipped_template );

    // Large deletions are better run in the background, small ones finish quickly in the tab
    $( '#ubdwp_delete_mode' ).toggle( summary.deletable > 0 );
    $( 'input[name="ubdwp_delete_mode"][value="' + (summary.deletable > 2000 ? 'background' : 'browser') + '"]' ).prop( 'checked', true );

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

/**
 * Start the confirmed job in the browser or in the background
 */
function startDeletion() {
    if ($( '#confirmDelete' ).prop( 'disabled' ) || ! job.id) {
        return;
    }

    job.started = true;
    closeConfirmDialog();

    job.mode     = $( 'input[name="ubdwp_delete_mode"]:checked' ).val() || 'browser';
    job.listed   = 0;
    job.stopping = false;

    $( '#user_delete_success_list, #user_delete_failed_list' ).empty();
    $( '#user_delete_failed, #ubdwp_results_note' ).hide();
    disableButtonsOnTheSecondStep();
    showProgressBar();

    jobRequest(
        {
            action: 'ubdwp_job_start',
            job_id: job.id,
            mode: job.mode,
            confirm: $( '#ubdwp_confirm_input' ).val().trim()
        }
    ).then(
        status => {
            updateProgress( status );

            if ('background' === job.mode) {
                $( '#ubdwp_background_note' ).empty().append(
                    document.createTextNode( __( 'The deletion runs in the background. You can leave this page and follow it on the Deletion Jobs page.', 'users-bulk-delete-with-preview' ) + ' ' ),
                    $( '<a>', {href: ubdwpData.jobsUrl} ).text( __( 'Open the Deletion Jobs page', 'users-bulk-delete-with-preview' ) )
                ).show();
                pollJob( status.job_id );
            } else {
                processNextBatch( status.job_id );
            }
        }
    ).catch(
        message => {
            activateButtonsOnTheSecondStep();
            hideProgressBar();
            showError( message );
        }
    );
}

/**
 * Delete the next batch of a job run in the browser
 *
 * @param {number} jobId - Job ID
 */
function processNextBatch(jobId) {
    jobRequest( {action: 'ubdwp_job_process', job_id: jobId} ).then(
        status => {
            appendResults( status.template );
            updateProgress( status );

            if (status.finished) {
                finishDeletion( status );
            } else {
                processNextBatch( jobId );
            }
        }
    ).catch(
        message => {
            // Users from earlier batches are already gone, so show the result of the job.
            jobRequest( {action: 'ubdwp_job_status', job_id: jobId} ).then(
                status => {
                    if (status.deleted > 0 || status.finished) {
                        finishDeletion( status );
                    } else {
                        activateButtonsOnTheSecondStep();
                        hideProgressBar();
                    }

                    createWordpressError( message );
                }
            ).catch(
                () => {
                    activateButtonsOnTheSecondStep();
                    hideProgressBar();
                    createWordpressError( message );
                }
            );
        }
    );
}

/**
 * Follow a background job until it is finished
 *
 * @param {number} jobId - Job ID
 * @param {function} onUpdate - Called with each status
 *
 * @return {Promise<object>} Status of the finished job
 */
function pollJob(jobId, onUpdate = updateProgress) {
    return new Promise(
        (resolve, reject) => {
            const check = () => {
                jobRequest( {action: 'ubdwp_job_status', job_id: jobId} ).then(
                    status => {
                        onUpdate( status );

                        if (status.finished) {
                            if (onUpdate === updateProgress) {
                                finishDeletion( status );
                            }

                            resolve( status );
                        } else {
                            setTimeout( check, 3000 );
                        }
                    }
                ).catch( reject );
            };

            check();
        }
    );
}

/**
 * Show the progress of a background job started earlier, on top of the page
 *
 * @param {number} jobId - Job ID
 */
function watchRunningBackgroundJob(jobId) {
    const $notice = $( '<div>', {class: 'notice notice-info ubdwp-job-notice'} ).append( $( '<p>' ) );
    const $text   = $notice.find( 'p' );

    $( '#notices' ).append( $notice );

    pollJob(
        jobId,
        status => {
            $text.text( sprintf( __( 'A background deletion is running: %1$d of %2$d users processed (%3$d%%).', 'users-bulk-delete-with-preview' ), status.position, status.total, status.percent ) );
        }
    ).then(
        status => {
            $notice.removeClass( 'notice-info' ).addClass( 'notice-success' );
            $text.text( sprintf( __( 'The background deletion is finished. Deleted users: %1$d. Not deleted: %2$d.', 'users-bulk-delete-with-preview' ), status.deleted, status.failed ) );
        }
    ).catch( () => $notice.remove() );
}

/**
 * Stop the running job: users already deleted stay deleted
 */
function stopDeletion() {
    if ( ! job.id || job.stopping) {
        return;
    }

    job.stopping = true;
    $( '#ubdwp_cancel_job' ).prop( 'disabled', true );

    jobRequest( {action: 'ubdwp_job_cancel', job_id: job.id} ).catch( showError );
}

/**
 * Add deleted users to the results table, up to the display limit
 *
 * @param {string} template - Table rows
 */
function appendResults(template) {
    if ( ! template || ! template.trim()) {
        return;
    }

    const $rows = $( $.parseHTML( template.trim() ) ).filter( 'tr' );
    const space = ubdwpData.resultsLimit - job.listed;

    if (space > 0) {
        $( '#user_delete_success_list' ).append( $rows.slice( 0, space ) );
    }

    job.listed += $rows.length;
}

/**
 * Update the progress bar
 *
 * @param {object} status - Job status
 */
function updateProgress(status) {
    const position = Math.min( status.position, status.total );

    $( '#progressBarInner' ).css( 'width', status.percent + '%' ).attr( 'aria-valuenow', status.percent );
    $( '#deletedCount' ).text( `${position} / ${status.total} (${status.percent}%)` );
}

/**
 * Show the result of a finished job
 *
 * @param {object} status - Job status
 */
function finishDeletion(status) {
    activateButtonsOnTheSecondStep();
    hideProgressBar();

    let message = sprintf( translations.deleteSuccess, status.deleted );

    if (status.failed > 0) {
        message += ' ' + sprintf( translations.deleteFailed, status.failed );
    }

    if ('cancelled' === status.status) {
        message += ' ' + __( 'The deletion was stopped.', 'users-bulk-delete-with-preview' );
    }

    if (status.failed_users_template && status.failed_users_template.trim()) {
        $( '#user_delete_failed_list' ).html( status.failed_users_template );
        $( '#user_delete_failed' ).show();
    }

    // Background jobs and very large deletions are listed on the Logs page
    if ('background' === job.mode || job.listed > ubdwpData.resultsLimit) {
        const note = 'background' === job.mode
            ? __( 'The deleted users are listed on the Logs page.', 'users-bulk-delete-with-preview' )
            : sprintf( __( 'Only the first %d deleted users are listed here. All of them are listed on the Logs page.', 'users-bulk-delete-with-preview' ), ubdwpData.resultsLimit );

        $( '#ubdwp_results_note' ).empty().append(
            document.createTextNode( note + ' ' ),
            $( '<a>', {href: ubdwpData.logsUrl} ).text( __( 'Open the Logs page', 'users-bulk-delete-with-preview' ) )
        ).show();
    }

    $( '#user_delete_success_table' ).toggle( job.listed > 0 );
    $( '#user_delete_success_heading' ).text( message );
    job.id      = 0;
    currentStep = 3;
    showStep( currentStep );
}

/**
 * Number of selected users (protected users are counted, they are skipped later)
 *
 * @return {number} Number of users
 */
function getSelectedCount() {
    return selection.all ? Math.max( 0, preview.total - selection.except.size ) : selection.ids.size;
}

/**
 * Selection sent to the server as one JSON field, so PHP's max_input_vars cannot cut it short
 *
 * @return {string} JSON
 */
function getSelectionJson() {
    return JSON.stringify(
        {
            all: selection.all,
            ids: Array.from( selection.ids ),
            except: Array.from( selection.except ),
            default: selection.defaultReassign,
            reassign: selection.reassign
        }
    );
}

/**
 * Forget the selection of the previous preview
 */
function resetSelection() {
    selection.all             = false;
    selection.defaultReassign = '';
    selection.reassign        = {};
    selection.ids.clear();
    selection.except.clear();
    updateSelectionInfo();
}

/**
 * Select or unselect all users of the preview, on all pages
 *
 * @param {boolean} all - Select all
 */
function selectAllMatching(all) {
    selection.all = all;
    selection.ids.clear();
    selection.except.clear();
    applySelectionToRows();
    updateSelectionInfo();
}

/**
 * Check if a user is selected
 *
 * @param {string} id - User ID
 *
 * @return {boolean} True when selected
 */
function isSelected(id) {
    return selection.all ? ! selection.except.has( id ) : selection.ids.has( id );
}

/**
 * Show the checkboxes and reassign values of the current page from the selection
 */
function applySelectionToRows() {
    const $checkboxes = $( '#userTable tbody input.user-checkbox' );

    $checkboxes.each(
        function () {
            if ( ! this.disabled) {
                this.checked = isSelected( String( this.value ) );
            }
        }
    );

    $( '#userTable tbody select.user-select' ).each(
        function () {
            const id    = String( $( this ).closest( 'tr' ).find( 'input.user-checkbox' ).val() );
            const value = Object.prototype.hasOwnProperty.call( selection.reassign, id ) ? selection.reassign[ id ] : selection.defaultReassign;

            setReassignSelectValue( $( this ), value, selection.labels[ value ] || '' );
        }
    );

    const $enabled = $checkboxes.filter( ':not(:disabled)' );
    const checked  = $enabled.filter( ':checked' ).length;
    const header   = $( '#select-all' ).get( 0 );

    if (header) {
        header.checked       = $enabled.length > 0 && checked === $enabled.length;
        header.indeterminate = checked > 0 && checked < $enabled.length;
    }
}

/**
 * Show how many users are selected, and offer to select all users of the preview
 */
function updateSelectionInfo() {
    const count = getSelectedCount();
    const $info = $( '#ubdwp_selection_info' );

    if ( ! count) {
        $info.hide();
        return;
    }

    $info.find( '.ubdwp-selection-text' ).text(
        selection.all
            ? sprintf( __( 'All %d users of the preview are selected. Protected users will be skipped.', 'users-bulk-delete-with-preview' ), count )
            : sprintf( __( 'Selected users: %d.', 'users-bulk-delete-with-preview' ), count )
    );
    $info.find( '.ubdwp-select-all-matching' )
        .text( sprintf( __( 'Select all %d users of the preview', 'users-bulk-delete-with-preview' ), preview.total ) )
        .toggle( ! selection.all && preview.total > count );
    $info.show();
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
 * Show the specified step in the form
 *
 * @param {number} step - The step number to show
 */
function showStep(step) {
    clearWordpressError();
    $( '.form-step' ).hide();
    $( '#step-' + step ).show();
    $( '.step_icon' ).removeClass( 'is-active' ).removeAttr( 'aria-current' );
    $( '#step_icon_' + step ).addClass( 'is-active' ).attr( 'aria-current', 'step' );
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
 * Hide the loader and show an error message
 *
 * @param {string} message - Error message
 */
function showError(message) {
    hideLoader();
    clearErrors( form );
    createWordpressError( 'string' === typeof message ? message : __( 'An unexpected error occurred.', 'users-bulk-delete-with-preview' ) );
}

/**
 * Create a WordPress styled error message
 *
 * @param {string} message - The error message
 * @param {string} type - "error" or "warning"
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
    $( '#notices' ).find( '.notice:not(.ubdwp-job-notice)' ).remove();
    $( '#notices' ).prepend( errorDiv );
}

/**
 * Show the loading spinner
 *
 * @param {string} text - Optional progress text
 */
function showLoader(text = '') {
    $( '#ubdwp_loader_text' ).text( text );
    $( '#page_loader' ).show();
}

/**
 * Show the progress bar
 */
function showProgressBar() {
    $( '#deleteProgressBar' ).show();
    $( '#progressBarInner' ).css( 'width', '0%' );
    $( '#deletedCount' ).text( '' );
    $( '#ubdwp_background_note' ).hide();
    $( '#ubdwp_cancel_job' ).prop( 'disabled', false );
}

/**
 * Hide the progress bar
 */
function hideProgressBar() {
    $( '#deleteProgressBar' ).hide();
}

/**
 * Disable buttons
 */
function disableButtonsOnTheSecondStep() {
    $( '.previous_step, .export-users-button, .deleteButton' ).prop( 'disabled', true );
}

/**
 * Activate buttons
 */
function activateButtonsOnTheSecondStep() {
    $( '.previous_step, .export-users-button, .deleteButton' ).prop( 'disabled', false );
}

/**
 * Hide the loading spinner
 */
function hideLoader() {
    $( '#page_loader' ).hide();
    $( '#ubdwp_loader_text' ).text( '' );
}

function clearWordpressError() {
    $( '#notices' ).find( '.notice:not(.ubdwp-job-notice)' ).remove();
}

/**
 * Set up the preview table: rows are loaded page by page from the server
 */
function setupUserTable() {
    if ($.fn.DataTable.isDataTable( '#userTable' )) {
        $( '#userTable' ).DataTable().clear().destroy();
    }

    const escapeText = $.fn.dataTable.render.text();

    const usersTable = $( '#userTable' ).DataTable(
        {
            serverSide: true,
            processing: true,
            responsive: true,
            ajax: (data, callback) => {
                const order = data.order && data.order.length ? data.order[0] : {column: 1, dir: 'asc'};

                request(
                    {
                        action: 'ubdwp_preview_page',
                        nonce: ubdwpData.previewNonce,
                        token: preview.token,
                        draw: data.draw,
                        start: data.start,
                        length: data.length,
                        search: data.search ? data.search.value : '',
                        order_column: order.column,
                        order_dir: order.dir
                    }
                ).then( callback ).catch(
                    message => {
                        createWordpressError( message );
                        callback( {draw: data.draw, recordsTotal: 0, recordsFiltered: 0, data: []} );
                    }
                );
            },
            columns: [
                {
                    title: '<input type="checkbox" id="select-all" aria-label="' + escapeAttribute( __( 'Select all users on this page', 'users-bulk-delete-with-preview' ) ) + '">',
                    data: 'checkbox',
                    orderable: false,
                    searchable: false
                },
                {title: translations.id, data: 'ID', render: escapeText},
                {title: translations.username, data: 'user_login', render: escapeText},
                {title: translations.email, data: 'user_email', render: escapeText},
                {title: translations.registered, data: 'user_registered', render: escapeText},
                {title: translations.role, data: 'user_role', render: escapeText, orderable: false},
                {title: translations.posts, data: 'post_count', render: escapeText, orderable: false},
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
            searchDelay: 400,
            lengthMenu: [
                [10, 25, 50, 100, 250, 500],
                [10, 25, 50, 100, 250, 500]
            ]
        }
    );

    initializeGeneralSelectOptions();

    // Each page is drawn from the server: show its selection and initialize its reassign selects
    usersTable.on(
        'draw',
        () => {
            initializeVisibleReassignSelects();
            applySelectionToRows();
        }
    );

    // Trigger resize event
    $( window ).trigger( 'resize' );
}

/**
 * Escape text for an HTML attribute
 *
 * @param {string} text - Text
 *
 * @return {string} Escaped text
 */
function escapeAttribute(text) {
    return $( '<div>' ).text( text ).html().replace( /"/g, '&quot;' );
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
                token: preview.token
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
        $select.append( new Option( text || value, value, false, false ) );
    }

    $select.val( value ).trigger( 'change.select2' );
}

/**
 * Initialize general select options and selection handlers for the user table
 */
function initializeGeneralSelectOptions() {
    const $generalSelect = $( '#generalSelect' );

    if ($generalSelect.data( 'select2' )) {
        $generalSelect.select2( 'destroy' );
    }

    selection.labels = {
        '': translations.selectUser,
        remove_all_related_content: translations.removeContent
    };

    $generalSelect
        .off( '.ubdwp' )
        .empty()
        .append( new Option( translations.selectUser, '', true, true ) )
        .append( new Option( translations.removeContent, 'remove_all_related_content', false, false ) )
        .select2( getReassignSelect2Options( '400px' ) );

    // Header checkbox: select or unselect the users of the current page
    $( '#userTable' ).off( '.ubdwp' ).on(
        'click.ubdwp',
        '#select-all',
        function () {
            const checked = this.checked;

            $( '#userTable tbody input.user-checkbox:not(:disabled)' ).each(
                function () {
                    toggleUser( String( this.value ), checked );
                }
            );

            applySelectionToRows();
            updateSelectionInfo();
        }
    );

    // Individual user checkbox
    $( '#userTable tbody' ).off( '.ubdwp' ).on(
        'change.ubdwp',
        'input.user-checkbox',
        function () {
            toggleUser( String( this.value ), this.checked );
            applySelectionToRows();
            updateSelectionInfo();
        }
    ).on(
        'change.ubdwp',
        'select.user-select',
        function () {
            const id    = String( $( this ).closest( 'tr' ).find( 'input.user-checkbox' ).val() );
            const value = String( $( this ).val() || '' );

            selection.reassign[ id ]  = value;
            selection.labels[ value ] = $( this ).find( 'option:selected' ).text();
        }
    );

    // The general value applies to all users; per-user choices are reset
    $generalSelect.on(
        'change.ubdwp',
        function () {
            const value = String( $( this ).val() || '' );

            selection.defaultReassign = value;
            selection.reassign        = {};
            selection.labels[ value ] = $( this ).find( 'option:selected' ).text();
            applySelectionToRows();
        }
    );
}

/**
 * Select or unselect one user
 *
 * @param {string} id - User ID
 * @param {boolean} checked - Selected
 */
function toggleUser(id, checked) {
    if (selection.all) {
        if (checked) {
            selection.except.delete( id );
        } else {
            selection.except.add( id );
        }
    } else if (checked) {
        selection.ids.add( id );
    } else {
        selection.ids.delete( id );
    }
}
