=== Custom Post Type Editor ===
Contributors: jamescollins, glenn-om4
Donate link: http://om4.com.au/wordpress-plugins/#donate
Tags: custom post type, cpt, post type, label, editor, cms, wp, multisite, wpmu
Requires at least: 3.2
Tested up to: 3.4
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Customize the text labels or menu names for any registered custom post type using a simple Dashboard user interface.

== Description ==

Customize the text labels or menu names for any registered custom post type using a simple Dashboard user interface. No PHP file editing is necessary!

For example, you could customize the following Custom Post Types:

* The `Posts` Custom Post Type (created by WordPress Core)
* The `Pages` Custom Post Type (created by WordPress Core)
* The `Media` Custom Post Type (created by WordPress Core)
* Any Custom Post Type that is created by a WordPress plugin
* Any Custom Post Type that is created by a WordPress theme

This means that you no longer have to modify PHP files in order to rename a Custom Post Type! Using this plugin, you can now make those changes using a simple interface in your WordPress dashboard. See the [screenshots](http://wordpress.org/extend/plugins/cpt-editor/screenshots/) for details.

* Want to rename `Posts` to `News`?
* Want to rename `Media` to `Files`?
* Want to rename the WooThemes’ `Features` post type to `Tours`?

You can do all of this (and more) using this plugin.

See the [Custom Post Type Editor Plugin](http://om4.com.au/wordpress-plugins/custom-post-type-editor/) home page for further information.

== Installation ==

Installation of this plugin is simple:

1. Download the plugin files and copy to your Plugins directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Navigate to Dashboard, Settings, Custom Post Types.

== Frequently Asked Questions ==

= Does this plugin modify any core WordPress, plugin or theme files? =

No. It uses WordPress' hook/filter to override Custom Post Type definitions on-the-fly. No files are modified by this plugin.

= Does this plugin permanently change anything? =

No. If you deactivate this plugin, your Custom Post Type definitions will revert to their defaults.

= I found a bug. How can I contribute a patch or bug fix? =
We'd love you to fork our [Github Repository](https://github.com/OM4/cpt-editor) and send us a pull request.

== Screenshots ==
1. The list of registered Custom Post Types
1. The interface for editing a Custom Post Type

== Changelog ==

= 1.0.1 =
* Add support for customising WordPress' built-in Posts, Pages and Media dashboard menu labels. Thanks to Aaron Rutley for testing this.

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.1 =
* Adds support for customising WordPress' built-in Posts, Pages and Media dashboard menu labels.
