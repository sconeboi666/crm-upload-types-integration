=== allow-cad-uploads ===
Contributors: batmanbroom
Tags: uploads, mime types, file types, crm, jetpack-crm
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://paypal.me/batmanbroom

Manage allowed upload file types (extensions and MIME types) and optionally extend Jetpack CRM attachment support without editing core plugin files.

== Description ==
CRM Upload Types Integration adds a central admin interface to manage allowed upload file extensions and MIME types in WordPress.
It can also integrate with Jetpack CRM by extending its accepted upload types at runtime without modifying Jetpack CRM plugin files. It provides visibility into which file types are allowed and which are excluded based on WordPress's known MIME types.

== Installation ==
1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin via the Plugins menu
3. Go to **Settings→Upload Types**
4. Add or edit allowed extensions using the provided format

== Frequently Asked Questions ==

= Does this modify Jetpack CRM files? =
No. The integration extends Jetpack CRM upload rules at runtime using WordPress hooks. No files are modified.

= Will updates to Jetpack CRM break this? =

No. The integration is update-safe and works alongside Jetpack CRM.

= Does this affect all uploads? =

Yes. Upload rules apply site-wide, including the Media Library and CRM attachments.

= Can I revert changes easily? =

Yes. Deactivate or remove the plugin to restore default behavior.

== Screenshots ==

1. Upload Types settings page showing allowed and excluded lists

== Changelog ==

= 1.2.0 =

* Added settings link on Plugins page
* Added global excluded file types list

= 1.1.0 =

* Added allowed/excluded visibility
* Improved settings UI

= 1.0.0 =

* Initial release
== Upgrade Notice ==

= 1.2.0 =

Adds settings link and global file visibility.

