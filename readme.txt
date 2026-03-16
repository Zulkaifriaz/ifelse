=== IfElse Pages – Coming Soon and Maintenance Mode ===
Contributors:      zulkaifriaz, ishahamabbas
Author: Zulkaif Riaz
Tags:              coming soon, maintenance mode, landing page, under construction
Requires at least: 6.2
Tested up to:      6.9
Stable tag:        1.0.0
Requires PHP:      7.4
License:           GPLv3
License URI:       https://www.gnu.org/licenses/gpl-3.0.html

A lightweight plugin to display Coming Soon, Maintenance, or Landing Page screens to visitors while you work on your site.

== Description ==

IfElse Pages lets you activate a Coming Soon, Maintenance, or Landing Page screen for your visitors while you build or update your website. Administrators and other permitted roles bypass the screen automatically — so you can always log in and see the real site.

**Features**

* One-click enable/disable toggle
* Three page modes: Coming Soon, Maintenance (503), Landing Page
* Three clean templates: Centered Minimal, Split Screen, Dark Mode
* Logo upload via WordPress media library
* Background colour picker and background image upload
* Optional countdown timer with auto-hide when the date expires
* SEO meta title and description fields
* Configurable role bypass — choose which roles see the real site
* Translation ready
* Zero external API calls, no tracking, no ads

== Installation ==

1. Upload the `ifelse-pages-coming-soon-and-maintenance-mode` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Settings → IfElse Pages** to configure.

== Frequently Asked Questions ==

= Will administrators always see the real site? =
Yes. Administrators bypass the screen by default. You can adjust which roles bypass it in the Settings tab.

= Does this affect the REST API or wp-admin? =
No. The intercept only runs on standard front-end page loads.

= What HTTP status code does Maintenance Mode send? =
Maintenance Mode sends a `503 Service Unavailable` header. Coming Soon and Landing Page send `200 OK`.

= Is the plugin translation ready? =
Yes. All strings use the `ifelse-pages-coming-soon-and-maintenance-mode` text domain and a `.pot` file is included in `/languages/`.

== Screenshots ==

1. Settings page for choosing a template.
2. Content tab – title, description, logo upload.
3. Design tab – template picker and background options.



= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade steps required.

== Arbitrary Sections ==
**Support**
For support, contact:
* Email: mail@zulkaif.com
* Website: https://zulkaif.com
* Plugin URI: https://zulkaif.com/ifelse.html

**Author**
Zulkaif Riaz - https://zulkaif.com
Shaham Abbas - https://shahamabbas.online
