=== Users Bulk Delete With Preview ===
Contributors: maksymvasylchuk
Tags: bulk delete, user management, delete users, preview delete, bulk clean
Requires at least: 6.2
Tested up to: 7.1.2
Stable tag: 2.3.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.paypal.com/donate/?hosted_button_id=NXKRKRDFLKBFG

Easily delete multiple WordPress users with the Users Bulk Delete With Preview plugin. Preview details before removal for accuracy and better control.

== Description ==

Introducing the **Users Bulk Delete With Preview** plugin – the ultimate solution for managing large numbers of WordPress users with precision and ease. Whether you’re handling a growing membership site, an extensive e-commerce platform, a WordPress multisite network, or a vibrant community, this plugin simplifies the process of user deletion and site-level user removal while helping you avoid accidental data loss.

### Features

1. **Bulk Deletion Capabilities**:
   Effortlessly remove multiple users at once, saving you time and reducing the hassle of deleting users one by one. Perfect for cleaning up inactive accounts, managing user roles, or streamlining your database.

2. **Preview Before Deletion**:
   Our plugin includes a crucial preview feature that allows you to review user details before finalizing the deletion process. This step is essential for verifying that you are deleting the correct users, thereby minimizing the risk of accidental removal.

3. **User Filtering Options**:
   Easily filter users based on various criteria such as user role, registration date, or email. This powerful filtering system ensures that you can target specific groups of users for deletion, making your management tasks more precise and effective, including WooCommerce orders.

4. **Safe and Secure**:
   The Users Bulk Delete With Preview plugin prioritizes your data’s security. It requires confirmation before executing any deletions, ensuring that no user data is lost inadvertently. Additionally, it provides a safeguard by allowing you to export user data before proceeding with bulk operations.

5. **User-Friendly Interface**:
   Designed with simplicity in mind, the plugin features an intuitive interface that makes it easy for users of all technical levels to navigate and operate. The clear layout and straightforward options ensure a smooth experience throughout the user management process.

6. **Single Site and Multisite Support**:
   On single-site installations, selected users are deleted from the site. On multisite installations, selected users are safely removed from the current site without deleting their network account.

7. **Export and Audit Tools**:
   Export selected users to CSV before taking action and keep a log of deletion operations. Exports are generated on demand and downloaded directly, so user data is never left in public upload folders.

== Minimum Requirements ==

* PHP 8.0 or greater is required.

== Installation ==

### Automatic Installation

Automatic installation is the easiest option; WordPress will handle the file transfer. Log in to your WordPress dashboard, navigate to the **Plugins** menu, and click **Add New**. In the search field, type “Users Bulk Delete With Preview” and click **Search Plugins**. Once you find the plugin, click **Install Now**, and WordPress will take it from there.

### Manual Installation

The manual installation method requires downloading the Users Bulk Delete With Preview plugin and uploading it to your web server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](https://wordpress.org/support/article/managing-plugins/#manual-plugin-installation).

== Usage ==

1. Once the plugin is activated, navigate to **Bulk Users Delete**.
2. Use the available filters to search for users by role, meta-data, or other criteria.
3. Preview the selected users to verify details.
4. Select the users you want to delete.
5. Confirm the deletion, and the users will be removed from your site.

== Screenshots ==

1. Initial Step with Existing Users Filter
2. Initial Step with Different Filters
3. Initial Step with WooCommerce Filter
4. Second Step: Users Preview
5. Delete Confirmation
6. Deletion Process
7. Deleted Users Review
8. Logs

== Frequently Asked Questions ==

= Does this plugin permanently delete users? =

On single-site installations, yes: once the deletion is confirmed, the selected users are permanently deleted. On multisite, the selected users are only removed from the current site; their network account and their access to other sites are kept.

= Can I restore deleted users? =

No, once users are deleted, they cannot be restored. Please make sure to verify the list during the preview step.

= Is this plugin compatible with WooCommerce? =

Yes, the plugin is compatible with WooCommerce and allows filtering users who have placed orders.

= How does the plugin work on WordPress multisite? =

On multisite, the plugin works in the current site context. It removes selected users from the current site instead of deleting their network account, helping network administrators avoid removing users from other sites by mistake.

= How do I delete spam users who have no first name? =

Choose "Find users according to certain criteria", set User Role to Subscriber, pick the first_name field under User Meta and choose "Meta is empty or missing". WordPress stores an empty first_name for every user who did not fill it in, so "Meta does not exist" will not find them. You can also tick "Only users without posts or comments on this site" to target accounts that never contributed anything.

= Can I delete administrators with this plugin? =

No. Administrators, users who can manage other users and super admins are protected: they are marked in the preview and skipped during deletion. Use the standard WordPress Users screen for them. Developers can change which users are protected with the ubdwp_is_protected_user filter.

= What happens to the content of deleted users? =

For each user you can reassign their posts to another user, permanently remove all their related content (posts in any status and comments), or leave the default. On single-site installations the default follows WordPress core: the user's posts and pages are moved to the trash. On multisite, the user is only removed from the current site and their content stays in place unless you choose to reassign or remove it.

= Does the plugin support network activation? =

Yes. When network activated, the plugin creates its log table for each site and initializes the table automatically for newly created sites.

== Upgrade Notice ==

= 2.3.0 =
Safer bulk deletion: administrators are protected, the confirmation shows exactly what will be deleted, large deletions need typed confirmation, and the registration date filter supports before, on and between. No manual upgrade steps are required.

= 2.2.2 =
Recommended security and bug fix release. Rejects invalid date and number filters instead of ignoring them, logs real user data, tightens permissions, fixes preview pagination and logs ordering, and adds Ukrainian JS translations. No manual upgrade steps are required.

= 2.2.1 =
Important bug fix and security release, tested with WordPress 7.1.2 and PHP 8.4. Fixes deletion of selected users across table pages and user meta filters that could match the wrong users. CSV exports are no longer stored on the server. No manual upgrade steps are required.

= 2.2.0 =
Adds single-site and multisite-aware user management, network activation support, safer current-site user removal, and hardened CSV export handling. No manual upgrade steps are required.

= 2.1.1 =
Security and compatibility maintenance release for WordPress 7.0. Includes safer CSV export handling and cleanup. No manual upgrade steps are required.

= 2.1.0 =
Added option to search for non-existing or empty user meta. Updated translations. No upgrade steps are required for this version.

= 2.0.0 =
Updated structure, uk translation, bug fixes and code improvements. No upgrade steps required for this version.

= 1.1.1 =
Updated links, tags and uk translation. No upgrade steps required for this version.

= 1.1.0 =
Updated Libraries, Bug Fixes, Code Improvements. No upgrade steps required for this version.

= 1.0.0 =
Initial release of the Users Bulk Delete With Preview plugin. No upgrade steps required for this version.


== Credits ==

This plugin uses the following third-party libraries:

– [Bootstrap](https://getbootstrap.com/) – Licensed under MIT License.
– [jQuery](https://jquery.com/) – Licensed under MIT License.
– [jQuery UI](https://jqueryui.com/) – Licensed under MIT License.
– [jQuery UI Datepicker](https://jqueryui.com/datepicker/) – Licensed under MIT License.
– [DataTables](https://datatables.net/) – Licensed under MIT License.
– [Select2](https://select2.org/) – Licensed under MIT License.

== Changelog ==
= 2.3.0 =
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

= 2.2.2 =
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

= 2.2.1 =
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

= 2.2.0 =
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

= 2.1.1 =
*Release Date - 14 June 2026*

* Tested compatibility with WordPress 7.0
* Fixed export file cleanup nonce handling
* Hardened CSV export file deletion path validation
* Added CSV formula injection protection
* Prevented deletion of the current administrator via crafted requests
* Fixed plugin text domain loading path
* Fixed plugin database version option
* Improved escaping in admin templates

= 2.1.0 =
*Release Date - 06 April 2025*

* Added option to search for non-existing or empty user meta
* Updated translations
* Bug fixes and code improvements

= 2.0.0 =
*Release Date - 03 January 2025*

* Updated plugin structure for better organization and maintainability.
* Updated uk translation
* Improved code logic and performance optimizations.
* Added additional validation checks.
* Bug fixes and code improvements.

= 1.1.1 =
*Release Date - 02 November 2024*

* Updated links
* Updated tags
* Updated uk translation

= 1.1.0 =
*Release Date - 14 October 2024*

* Updated Libraries: All core libraries have been updated to their latest versions for enhanced performance, security, and compatibility.
* Bug Fixes
* Code Improvements

= 1.0.0 =
* The first version of the plugin.
