=== Custom Post Type Editor ===
Contributors: jamescollins, om4csaba, om4
Tags: custom post type, cpt, post type, label, description, editor
Requires at least: 6.0
Tested up to: 6.6
Stable tag: 1.6.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Customize the text labels, menu names or description for any registered custom post type using a simple Dashboard user interface.

== Description ==

Customize the text labels, menu names or description for any registered custom post type using a simple Dashboard user interface. No PHP file editing is necessary!

* Want to rename `Posts` to `News`?
* Want to rename `Media` to `Files`?
* Want to rename a `Portfolio` post type to `Projects`?

You can do all of this (and more) using this plugin.

For example, you could customize the following Custom Post Types:

* The `Posts` Custom Post Type (created by WordPress Core)
* The `Pages` Custom Post Type (created by WordPress Core)
* The `Media` Custom Post Type (created by WordPress Core)
* Any Custom Post Type that is created by a WordPress plugin
* Any Custom Post Type that is created by a WordPress theme

This means that you no longer have to modify PHP files in order to rename a Custom Post Type!

See the [screenshots](https://wordpress.org/plugins/cpt-editor/screenshots/) and [Custom Post Type Editor Plugin home page](https://om4.io/plugins/custom-post-type-editor/) for further information.

== Installation ==

Installation of this plugin is simple:

1. Download the plugin files and copy to your Plugins directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Navigate to Dashboard, Settings, Custom Post Types.

== Frequently Asked Questions ==

= What does this plugin do? =

It provides an easy way for you to modify/customise the *labels* and/or *description* of any registered Custom Post Type. This includes WordPress' built-in post types, as well as any Custom Post Types created by a plugin or theme.

= What doesn't this plugin do? =

It doesn't allow you do other things such as changing a Custom Post Type's rewrite slug or body class. Changing those can cause styling or display issues with themes.

If you look at [this WordPress Codex page](http://codex.wordpress.org/Function_Reference/register_post_type#Arguments), the `labels` values can be changed by this plugin. The `description` can also be changed by this plugin. All other parameters (such as `public`, `exclude_from_search`, etc.) ***cannot*** be customised using this plugin.

= Does this plugin modify any core WordPress, plugin or theme files? =

No. It uses WordPress' hook/filter to override Custom Post Type definitions on-the-fly. No files are modified by this plugin.

= Does this plugin permanently change anything? =

No. If you deactivate this plugin, your Custom Post Type definitions will revert to their defaults.

= Does this plugin work with WordPress Multisite? =

Yes - this plugin works with WordPress Multisite.

= I found a bug. How can I contribute a patch or bug fix? =

We'd love you to fork our [Github Repository](https://github.com/OM4/cpt-editor) and send us a pull request.

Alternatively, you can report a bug on our [Issue Tracker](https://github.com/OM4/cpt-editor/issues).

== Screenshots ==
1. The list of registered Custom Post Types
1. The interface for editing a Custom Post Type

== Changelog ==

= 1.6.3 =
* Fix version number.

= 1.6.2 =
* Mark compatibility with WordPress version 6.6.

= 1.6.1 =
* Mark compatibility with WordPress version 6.5.

= 1.6.0 =
* Minimum required WordPress version updated to 6.0.
* Mark compatibility with WordPress version 6.4.

= 1.5.0 =
* Add compatibility with latest WordPress versions (including 6.1).
* Add support for new labels introduced in WordPress version 5.8 (`item_link` and `item_link_description`).
* Add support for new labels introduced in WordPress version 5.7 (`filter_by_date`).
* Add support for new labels introduced in WordPress version 5.0 (`item_published`, `item_published_privately`, `item_reverted_to_draft`, `item_scheduled`, and `item_updated`).
* Add support for new labels introduced in WordPress version 4.7 (`view_items` and `attributes`).
* PHP 8 compatibility.
* Modernize code.
* Security improvements for settings screens.

= 1.4.2 =
* Mark WordPress 5.5 compatible.

= 1.4.1 =
* Mark WordPress 5.2 compatible.

= 1.4 =
* Add support for the 2 new post type labels that were added in WordPress 4.7 (view_items and attributes).

= 1.3 =
* Add support for customizing a Custom Post Type's description. Thanks to LeodanEvolution for the suggestion.
* Add support for the 4 new post type labels that were added in WordPress 4.3.
* Add support for the 6 new post type labels that were added in WordPress 4.4.

= 1.2.6 =
* WordPress 4.5 compatibility.
* PHP 7 compatibility (a PHP notice no longer occurs).

= 1.2.5 =
* WordPress 4.3 compatibility.
* Change plugin's textdomain to match the plugin's folder name in preparation for translate.wordpress.org translations.
* Readme updates.

= 1.2.4 =
* More secure edit links.

= 1.2.3 =
* WordPress 4.1 compatibility.

= 1.2.2 =
* WordPress 4.0 compatibility.
* Screenshot updates.

= 1.2.1 =
* PHP notice fixes. Props klihelp.

= 1.2 =
* WordPress 3.8 compatibility

= 1.1 =
* WordPress 3.5 compatibility
* Documentation/FAQ updates

= 1.0.2 =
* Documentation updates
* US spelling
* Screenshot updates

= 1.0.1 =
* Add support for customising WordPress' built-in Posts, Pages and Media dashboard menu labels. Thanks to Aaron Rutley for testing this.

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.5 =
* Adds support for new custom post type labels added in recent versions of WordPress.

= 1.3 =
* Adds support for customizing a Custom Post Type's description, and adds support for 10 new custom post type labels

= 1.2 =
* WordPress 3.8 compatibility

= 1.1 =
* WordPress 3.5 compatibility, documentation/FAQ updates

= 1.0.2 =
* Documentation, spelling and screenshot updates.

= 1.0.1 =
* Adds support for customising WordPress' built-in Posts, Pages and Media dashboard menu labels.
