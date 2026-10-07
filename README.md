# Users Bulk Delete With Preview

<p align="center">
   <img src="https://github.com/user-attachments/assets/9e194d8f-5c49-4105-92ad-56c17d713a13" height="300" alt="Users Bulk Delete With Preview" />
</p>

**Contributors**: maksymvasylchuk  
**Tags**: bulk delete, users delete with preview, users bulk delete with preview, users bulk clean with preview  
**Requires at least**: 6.2  
**Tested up to**: 7.1  
**Stable tag**: 2.4.1  
**Requires PHP**: 8.0  
**License**: GPLv2 or later  
**License URI**: [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)
**Donate link**: https://www.paypal.com/donate/?hosted_button_id=NXKRKRDFLKBFG

Easily delete multiple WordPress users with the Users Bulk Delete With Preview plugin. Preview details before removal for accuracy and better control.

## Description

Introducing the **Users Bulk Delete With Preview** plugin – the ultimate solution for managing large numbers of WordPress users with precision and ease. Whether you’re handling a growing membership site, an extensive e-commerce platform, a WordPress multisite network, or a vibrant community, this plugin simplifies the process of user deletion and site-level user removal while helping you avoid accidental data loss.

### Features

1. **Bulk Deletion Capabilities**  
   Effortlessly remove multiple users at once, saving you time and reducing the hassle of deleting users one by one. Perfect for cleaning up inactive accounts, managing user roles, or streamlining your database.

2. **Preview Before Deletion**  
   Our plugin includes a crucial preview feature that allows you to review user details before finalizing the deletion process. This step is essential for verifying that you are deleting the correct users, thereby minimizing the risk of accidental removal.

3. **User Filtering Options**  
   Easily filter users based on various criteria such as user role, registration date, or email. This powerful filtering system ensures that you can target specific groups of users for deletion, making your management tasks more precise and effective, including WooCommerce orders.

4. **Safe and Secure**  
   The Users Bulk Delete With Preview plugin prioritizes your data’s security. It requires confirmation before executing any deletions, ensuring that no user data is lost inadvertently. Additionally, it provides a safeguard by allowing you to export user data before proceeding with bulk operations.

5. **User-Friendly Interface**  
   Designed with simplicity in mind, the plugin features an intuitive interface that makes it easy for users of all technical levels to navigate and operate. The clear layout and straightforward options ensure a smooth experience throughout the user management process.

6. **Single Site and Multisite Support**  
   On single-site installations, selected users are deleted from the site. On multisite installations, selected users are safely removed from the current site without deleting their network account.

7. **Export and Audit Tools**  
   Export selected users to CSV before taking action and keep a log of deletion operations. Exports are generated on demand and downloaded directly, so user data is never left in public upload folders.

8. **Large Sites, Background Deletion and WP-CLI**  
   The preview is paged on the server, so it works with tens of thousands of users. Deletions run in batches, in the browser tab or in the background, and can be followed and cancelled on the Deletion Jobs page. The same filters are available on the command line with `wp ubdwp find` and `wp ubdwp delete`.

## Minimum Requirements

* PHP 8.0 or greater is required.

## Installation

### Automatic Installation

Automatic installation is the easiest option; WordPress will handle the file transfer. Log in to your WordPress dashboard, navigate to the **Plugins** menu, and click **Add New**. In the search field, type “Users Bulk Delete With Preview” and click **Search Plugins**. Once you find the plugin, click **Install Now**, and WordPress will take it from there.

### Manual Installation

The manual installation method requires downloading the Users Bulk Delete With Preview plugin and uploading it to your web server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](https://wordpress.org/support/article/managing-plugins/#manual-plugin-installation).

## Usage

1. Once the plugin is activated, navigate to **Bulk Users Delete**.
2. Use the available filters to search for users by role, meta-data, or other criteria.
3. Preview the selected users to verify details.
4. Select the users you want to delete.
5. Confirm the deletion, and the users will be removed from your site.

## Screenshots

**1. Initial Step with Existing Users Filter**

<p align="center">
   <img src="https://ps.w.org/users-bulk-delete-with-preview/assets/screenshot-1.png" alt="Initial Step with Existing Users Filter" width="800" />
</p>

**2. Initial Step with Different Filters**

<p align="center">
   <img src="https://ps.w.org/users-bulk-delete-with-preview/assets/screenshot-2.png" alt="Initial Step with Different Filters" width="800" />
</p>

**3. Initial Step with WooCommerce Filter**

<p align="center">
   <img src="https://ps.w.org/users-bulk-delete-with-preview/assets/screenshot-3.png" alt="Initial Step with WooCommerce Filter" width="800" />
</p>

**4. Second Step: Users Preview**

<p align="center">
   <img src="https://ps.w.org/users-bulk-delete-with-preview/assets/screenshot-4.png" alt="Second Step: Users Preview" width="800" />
</p>

**5. Delete Confirmation**

<p align="center">
   <img src="https://ps.w.org/users-bulk-delete-with-preview/assets/screenshot-5.png" alt="Delete Confirmation" width="800" />
</p>

**6. Deletion Process**

<p align="center">
   <img src="https://ps.w.org/users-bulk-delete-with-preview/assets/screenshot-6.png" alt="Deletion Process" width="800" />
</p>

**7. Deleted Users Review**

<p align="center">
   <img src="https://ps.w.org/users-bulk-delete-with-preview/assets/screenshot-7.png" alt="Deleted Users Review" width="800" />
</p>

**8. Logs**

<p align="center">
   <img src="https://ps.w.org/users-bulk-delete-with-preview/assets/screenshot-8.png" alt="Logs" width="800" />
</p>

**9. Deletion Jobs**

<p align="center">
   <img src="https://ps.w.org/users-bulk-delete-with-preview/assets/screenshot-9.png" alt="Deletion Jobs" width="800" />
</p>

**10. Settings**

<p align="center">
   <img src="https://ps.w.org/users-bulk-delete-with-preview/assets/screenshot-10.png" alt="Settings" width="800" />
</p>

## Frequently Asked Questions

### Does this plugin permanently delete users?
On single-site installations, yes: once the deletion is confirmed, the selected users are permanently deleted. On multisite, the selected users are only removed from the current site; their network account and their access to other sites are kept.

### Can I restore deleted users?
No, once users are deleted, they cannot be restored. Please make sure to verify the list during the preview step.

### Is this plugin compatible with WooCommerce?
Yes, the plugin is compatible with WooCommerce and allows filtering users who have placed orders.

### How does the plugin work on WordPress multisite?
On multisite, the plugin works in the current site context. It removes selected users from the current site instead of deleting their network account, helping network administrators avoid removing users from other sites by mistake.

### How do I delete spam users who have no first name?
Choose "Find users according to certain criteria", set User Role to Subscriber, pick the first_name field under User Meta and choose "Meta is empty or missing". WordPress stores an empty first_name for every user who did not fill it in, so "Meta does not exist" will not find them. You can also tick "Only users without posts or comments on this site" to target accounts that never contributed anything.

### Can I delete administrators with this plugin?
No. Administrators, users who can manage other users and super admins are protected: they are marked in the preview and skipped during deletion. Use the standard WordPress Users screen for them. Developers can change which users are protected with the ubdwp_is_protected_user filter.

### What happens to the content of deleted users?
For each user you can reassign their posts to another user, permanently remove all their related content (posts in any status and comments), or leave the default. On single-site installations the default follows WordPress core: the user's posts and pages are moved to the trash. On multisite, the user is only removed from the current site and their content stays in place unless you choose to reassign or remove it.

### Does the plugin support network activation?
Yes. When network activated, the plugin creates its log table for each site and initializes the table automatically for newly created sites.

### How do I delete tens of thousands of users?
Narrow the users down with the filters, open the preview and use "Select all users of the preview". In the confirmation, choose "In the background": the deletion runs in batches and continues after you close the page; follow it on the Deletion Jobs page. Back up your database first. On the command line, the same can be done with `wp ubdwp delete`.

### Is there a WP-CLI command?
Yes. Run the commands as an administrator with --user, for example: wp ubdwp find --role=subscriber --meta-key=first_name --meta-compare=empty --format=count --user=admin, then wp ubdwp delete with the same filters and --dry-run to see the summary, or --yes to delete. Use wp ubdwp jobs to list, run or cancel deletion jobs, and wp help ubdwp for all options.

### What personal data does the plugin store (GDPR)?
The deletion log stores the ID of each deleted account, the date and the administrator who deleted it. By default, emails and display names in new entries are masked (j***@example.com); on the Settings page you can store them in full or not at all, apply the setting to existing entries, set a retention period and delete old entries. The log is included in WordPress Tools > Export Personal Data and Erase Personal Data, and the plugin adds suggested text to the privacy policy guide. CSV exports are downloaded directly and never stored on the server.

## Upgrade Notice

### 2.4.1
Fixes the deletion progress bar, which was not visible in 2.4.0, and adds pagination to the Deletion Jobs page. No manual upgrade steps are required.

### 2.4.0
Big update for large sites: server-side preview paging, background deletion jobs, WP-CLI commands and privacy tools for the log (masked emails by default, retention, personal data export and erasure). Jobs and settings have their own pages. No manual upgrade steps are required.

### 2.3.0
Safer bulk deletion: administrators are protected, the confirmation shows exactly what will be deleted, large deletions need typed confirmation, and the registration date filter supports before, on and between. No manual upgrade steps are required.

### 2.2.2
Recommended security and bug fix release. Rejects invalid date and number filters instead of ignoring them, logs real user data, tightens permissions, fixes preview pagination and logs ordering, and adds Ukrainian JS translations. No manual upgrade steps are required.

### 2.2.1
Important bug fix and security release, tested with WordPress 7.1.2 and PHP 8.4. Fixes deletion of selected users across table pages and user meta filters that could match the wrong users. CSV exports are no longer stored on the server. No manual upgrade steps are required.

### 2.2.0
Adds single-site and multisite-aware user management, network activation support, safer current-site user removal, and hardened CSV export handling. No manual upgrade steps are required.

### 2.1.1
Security and compatibility maintenance release for WordPress 7.0. Includes safer CSV export handling and cleanup. No manual upgrade steps are required.

### 2.1.0
Added option to search for non-existing or empty user meta. Updated translations. No upgrade steps are required for this version.
### 2.0.0
Updated structure, uk translation, bug fixes and code improvements. No upgrade steps required for this version.
### 1.1.1
Updated links, tags and uk translation. No upgrade steps required for this version.
### 1.1.0
Updated Libraries, Bug Fixes, Code Improvements. No upgrade steps required for this version.
### 1.0.0
Initial release of the Users Bulk Delete With Preview plugin. No upgrade steps required for this version.

## Credits

This plugin uses the following third-party libraries:

- [jQuery](https://jquery.com/) – Licensed under MIT License.
- [DataTables](https://datatables.net/) – Licensed under MIT License.
- [Select2](https://select2.org/) – Licensed under MIT License.

## Development

The admin scripts and styles are written in `assets/src` and built into `assets/build` with [@wordpress/scripts](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/).

```bash
npm install
npm run build      # production build
npm run start      # rebuild on changes
npm run i18n:pot   # update languages/users-bulk-delete-with-preview.pot
npm run i18n:update # update the .po files from the .pot
npm run i18n:mo    # compile the .mo files
npm run i18n:json  # JavaScript translations from the .po files
```

Commit the contents of `assets/build` together with the sources: the plugin loads only the built files.

### Hooks

| Hook | Type | Use |
|---|---|---|
| `ubdwp_filter_form_fields` | action | Print extra filter rows (`<tr>`) in the search form; they are sent with the preview request |
| `ubdwp_found_user_ids` | filter | `( $user_ids, $type, $request )` – narrow the users found by the filters |
| `ubdwp_before_delete_user` | action | `( $user_id, $reassign, $user )` – before a user is deleted, while the user and content exist |
| `ubdwp_user_deleted` | action | `( $user_id, $entry )` – after a user was deleted or removed from the site |
| `ubdwp_job_created` | action | `( $job )` – a deletion job was created |
| `ubdwp_job_finished` | action | `( $status, $job )` – a job was completed, cancelled or failed |
| `ubdwp_settings_page` | action | Add sections to the Settings page |
| `ubdwp_is_protected_user` | filter | Protect users from deletion |
| `ubdwp_preview_limit`, `ubdwp_delete_batch_size`, `ubdwp_confirmation_threshold`, `ubdwp_background_time_budget` | filter | Limits of the preview, batches and confirmation |

## Changelog
### 2.4.1
*Release Date - TBD*
* Fixed the deletion progress bar not being visible since 2.4.0
* The Deletion Jobs page is paged (20 jobs per page) and shows the number of jobs
* The Deletion Jobs page refreshes only while a job is running, not while it waits for confirmation
* WP-CLI: user data printed by the commands can no longer contain terminal control characters, and --format=csv escapes spreadsheet formulas like the CSV export
* WP-CLI: permissions are checked before anything else is done
* New screenshots of the Deletion Jobs and Settings pages
* The Logs page is titled "Logs", like its menu item; the Ukrainian translation now also covers the plugin description
* Fewer conflicts with other plugins: all element IDs and CSS classes of the plugin pages are now prefixed (ubdwp), so markup or styles of other plugins cannot break the confirmation dialog, the progress bar or the loader
* The Bulk Users Delete page no longer sends a needless job status request when it is opened

### 2.4.0
*Release Date - 5 October 2026*
* The preview now loads users page by page from the server, so it is no longer limited to 10,000 users and stays fast with tens of thousands of users
* "Select all users of the preview" selects every matching user on all pages; single users can still be unselected
* Deletions run as jobs in batches of 50 users (filterable with ubdwp_delete_batch_size), and the confirmation lets you run them in this browser tab or in the background
* Background deletions use Action Scheduler when it is available (for example with WooCommerce) or WP-Cron, run as the user who started them and continue after the page is closed
* New Deletion Jobs page with progress and a Cancel button; a running deletion can also be stopped from the progress bar
* New WP-CLI commands: wp ubdwp find, wp ubdwp delete (with --dry-run, --reassign, --batch-size and --background) and wp ubdwp jobs
* New email filter "Ends with", for example a domain such as @example.com
* New filter: only users without WooCommerce orders
* New "All users of this site" option for the existing users filter, instead of loading every user into the list
* The typed confirmation for large deletions is now also checked by the server
* Fixed the confirmation dialog and CSV export counting fewer users than selected for large selections (for example 1500 instead of 2230): servers with a low max_input_vars dropped part of the list. Deletion itself was not affected
* Previews, summaries, exports and deletions now use the WordPress admin memory limit, so large previews no longer run out of memory on hosts with a low default memory limit
* Large CSV exports are generated in chunks to keep memory use low
* Content can no longer be reassigned to a user who is deleted later in the same deletion
* Filter values with apostrophes (for example O'Brien) are no longer changed by WordPress slashes
* The results step lists up to 1,000 deleted users; all of them are listed on the Logs page
* The ubdwp_preview_limit filter now caps the number of users in a preview (no limit by default)
* Restored the Ukrainian translation of the plugin name and translated all new texts into Ukrainian
* Privacy: emails and names of deleted users are now stored masked in new log entries by default (j***@example.com); you can store them in full or not at all on the Settings page
* Privacy: optional retention period for the log; older entries and finished deletion jobs are deleted automatically once a day
* Privacy: "Apply to existing entries", "Delete entries older than" and "Delete the whole log" actions on the Settings page
* Privacy: the log is included in Tools > Export Personal Data and Erase Personal Data (deleted users and the administrators who deleted them)
* Privacy: suggested text for the privacy policy page
* New WP-CLI command wp ubdwp logs purge|anonymize
* Uninstalling removes the privacy settings and scheduled tasks
* New hooks for extensions: ubdwp_filter_form_fields and ubdwp_found_user_ids (extra filters), ubdwp_before_delete_user and ubdwp_user_deleted, ubdwp_job_created and ubdwp_job_finished, ubdwp_settings_page
* Deletion jobs and the plugin settings now have their own pages: Bulk Users Delete > Deletion Jobs and Bulk Users Delete > Settings; the Logs page shows only the log
* The confirmation is now a native dialog and the registration date fields use the browser date picker; Bootstrap and jQuery UI are no longer loaded, which makes the plugin smaller and avoids style conflicts with other plugins
* Wide preview tables scroll inside the table instead of the whole admin page
* Scripts and styles are now built with @wordpress/scripts and include right-to-left styles; the readable sources are in assets/src

### 2.3.0
*Release Date - 28 September 2026*
* Administrators, users who can manage other users and super admins are now protected and cannot be deleted with the plugin (developers can change this with the ubdwp_is_protected_user filter)
* Protected users are marked in the preview table and cannot be selected
* The confirmation dialog now shows how many users will be deleted or skipped and what happens to their posts and comments
* Deleting 20 or more users requires typing the number of users to confirm (filterable with ubdwp_confirmation_threshold)
* The results step lists users that were not deleted, with the reason
* Registration date filter now supports on or after, on or before, on a specific day and between two dates
* Registration date filter now uses the site's timezone, so day boundaries match what administrators see
* The Registered column in the preview is shown in the site's timezone
* New Posts column in the preview shows how many posts each user has
* New filter: only users without posts or comments on the site
* The results step shows what happened to each user's content instead of a user ID
* The confirmation field gets focus automatically and Enter confirms once the number matches
* "Meta is empty or missing" now also matches users who do not have the meta key at all
* Added a hint explaining how to find users with blank profile fields such as first_name
* Very large previews are loaded in parts of 10,000 users (filterable with ubdwp_preview_limit) with a notice, so the preview no longer fails on sites with tens of thousands of users
* WooCommerce product filter now searches products as you type instead of loading every product when the page opens, so the page stays fast in large stores
* WooCommerce "Select All" now matches customers who bought any product
* WooCommerce product filter uses a single faster query and only counts paid orders in both HPOS and legacy order storage
* Refreshed admin UI: all fields, Select2 dropdowns, tables, pagination, steps and the confirmation dialog now share one size, border, corner radius and the admin color scheme
* Fixed the "Select All" label on the existing users filter so clicking the text toggles the checkbox
* Removed hidden email and display name fields from the preview form
* Prefixed nonce actions

### 2.2.2
*Release Date - 27 September 2026*
* Fixed preview table pagination showing only one page on first load
* Fixed logs table ordering so entries created in the same second are not repeated or skipped between pages
* Added missing Ukrainian JavaScript translation files
* Reject invalid registration dates and invalid number/date user meta values instead of silently ignoring the filter
* Prefixed AJAX actions and the localized JavaScript object to avoid conflicts with other plugins
* Always return a JSON error response for unexpected server errors
* Escaped plugin action links
* Limited the logs page size to 100 entries per request
* Deletion logs now record user data from the database instead of values sent by the browser
* Duplicate user IDs in one request are processed only once
* Plugin pages and logs now also require the list_users capability

### 2.2.1
*Release Date - 26 September 2026*
* Tested compatibility with WordPress 7.1.2 and PHP 8.4
* Fixed bulk deletion processing unchecked rows and stopping with an error
* Fixed selected users on other table pages being ignored by deletion and export
* Fixed user meta filter stripping characters from meta keys, which could match the wrong users
* Fixed number and date user meta comparisons being compared as strings
* Prevented reassigning content to users that are being deleted, and skipped users with an invalid reassign target instead of deleting their content
* Added per-user delete/remove capability checks
* CSV export is now generated in memory and downloaded directly, without storing files in uploads
* Removed leftover CSV export files from previous versions
* Improved CSV formula injection protection
* Escaped user data rendered in admin tables
* Fixed WooCommerce product filter for stores without HPOS (legacy order storage)
* Fixed log tables of deleted multisite sites being left in the database
* Limited user meta key search to current-site users on multisite
* Remove all related content now also deletes trashed posts, auto-drafts and comments in any status
* Replaced the per-row reassign user list with an AJAX user search for large sites
* Limited existing users autocomplete results on large sites
* Fixed success message and progress bar counts after deletion
* Kept deletion logs visible when the acting administrator is deleted
* Skipped empty deletion log entries
* Fixed PHP 8.4 fputcsv() deprecation notice
* Fixed wpdb::prepare() _doing_it_wrong notice in the logs table that could break its AJAX response with WP_DEBUG_DISPLAY enabled
* Hardened request handling against malformed input and improved AJAX error messages
* Fixed plugin header requirements parsing
* Fixed DataTables translations not being applied
* Translated step 3 table headers and deletion result messages
* Added JavaScript translations loading and updated the translation template and Ukrainian translation

### 2.2.0
*Release Date - 05 July 2026*
* Added multisite-aware activation for network-wide installs
* Added automatic log table setup for newly created multisite sites
* Added automatic per-site database setup after plugin updates
* Added multisite-aware uninstall cleanup across all sites
* Limited user searches and filters to the current site context
* Fixed email comparison filters returning a broad user list when no users matched
* Fixed empty email fields affecting other user filters
* Limited email comparison SQL results to current-site users on multisite
* Removed users from the current site on multisite instead of deleting network users
* Kept single-site behavior as full user deletion
* Added deletion log action context for deleted vs removed users
* Added randomized CSV export file names
* Added automatic cleanup for old CSV export files
* Ensured WordPress user deletion helpers are loaded before single-site deletes

### 2.1.1
*Release Date - 14 June 2026*
* Tested compatibility with WordPress 7.0
* Fixed export file cleanup nonce handling
* Hardened CSV export file deletion path validation
* Added CSV formula injection protection
* Prevented deletion of the current administrator via crafted requests
* Fixed plugin text domain loading path
* Fixed plugin database version option
* Improved escaping in admin templates

### 2.1.0
*Release Date - 06 April 2025*
* Added option to search for non-existing or empty user meta 
* Updated translations 
* Bug fixes and code improvements

### 2.0.0
*Release Date - 03 January 2025*
* Updated plugin structure for better organization and maintainability
* Updated uk translation
* Improved code logic and performance optimizations
* Added additional validation checks
* Bug fixes and code improvements

### 1.1.1
*Release Date - 02 November 2024*
* Updated links
* Updated tags
* Updated uk translation

### 1.1.0
*Release Date - 14 October 2024*
* Updated Libraries: All core libraries have been updated to their latest versions for enhanced performance, security, and compatibility.
* Bug Fixes
* Code Improvements

### 1.0.0
* The first version of the plugin.

## License
This plugin is licensed under the GPLv2 or later license. See the [LICENSE](LICENSE) file for details.
