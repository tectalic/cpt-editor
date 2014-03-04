<?php
/*
Plugin Name: Custom Post Type Editor
Plugin URI: http://om4.com.au/wordpress-plugins/custom-post-type-editor/
Description: Customize the text labels or menu names for any registered custom post type using a simple Dashboard user interface.
Version: 1.2.1
Author: OM4
Author URI: http://om4.com.au/
Text Domain: om4-cpt-editor
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/*  Copyright 2012-2014 OM4 (email : info@om4.com.au)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


class OM4_CPT_Editor {

	var $db_version = 1;

	var $installed_version;

	var $dirname;

	var $url;

	var $option_name = 'om4_cpt_editor';

	var $list;

	var $form;

	var $base_url;

	var $cpt_originals;

	/*
	 * Default settings
	 */
	var $settings = array(
		'types'=> array()
	);

	/**
	 * Constructor
	 *
	 */
	function __construct() {

		// Store the name of the directory that this plugin is installed in
		$this->dirname = str_replace( '/cpt-editor.php', '', plugin_basename( __FILE__ ) );

		$this->url = trailingslashit( plugins_url( '', __FILE__ ) );

		register_activation_hook( __FILE__, array( $this, 'Activate' ) );

		add_action( 'plugins_loaded', array( $this, 'LoadDomain' ) );

		add_action( 'init', array( $this, 'CheckVersion' ) );

		add_action( 'admin_menu', array( $this, 'AdminMenu') );

		$this->installed_version = intval( get_option( $this->option_name ) );

		$data = get_option($this->option_name);
		if (is_array($data)) {
			$this->installed_version = intval($data['version']);
			$this->settings = $data['settings'];
		}

		add_action( 'registered_post_type', array( $this, 'PostTypeRegistered' ), 10, 2 );

	}

	/**
	 * Intialise I18n/Localisation
	 */
	function LoadDomain() {
		load_plugin_textdomain( 'om4-cpt-editor', false, "{$this->dirname}/languages" );
	}

	/**
	 * Plugin Activation Tasks
	 */
	function Activate() {
		// There aren't really any installation tasks (for now)
		if ( ! $this->installed_version ) {
			$this->installed_version = $this->db_version;
			$this->SaveSettings();
		}
	}

	/**
	 * Performs any database upgrade tasks if required
	 */
	function CheckVersion() {
		if ( $this->installed_version != $this->db_version ) {
			// Upgrade tasks
			if ( $this->installed_version == 0 ) {
				$this->installed_version ++;
			}
			$this->SaveSettings();
		}
	}

	/**
	 * Executed whenever a Post Type is registered (by core, a plugin or a theme).
	 *
	 * Override any labels that have been customized, and if we're in the backend save a backup of the original CPT so that we can detect that its been modified.
	 *
	 * @param string $post_type
	 * @param array $args
	 */
	function PostTypeRegistered( $post_type, $args ) {
		global $wp_post_types;

		if ( $this->NeedToBackupCustomPostTypes() && !isset($this->cpt_originals[$post_type]) ) {
			// Save a copy of the original (unmodified) version of this post type
			$this->cpt_originals[$post_type] = unserialize(serialize( $wp_post_types[$post_type] ));
		}

		if ( isset($this->settings['types'][$post_type]['labels']) && is_array($this->settings['types'][$post_type]['labels']) ) {

			foreach ( $this->settings['types'][$post_type]['labels'] as $label_name => $label_text ) {

				if ( $label_text != $wp_post_types[$post_type]->labels->$label_name ) {
					// This label text is customized, so override the default
					$wp_post_types[$post_type]->labels->$label_name = $label_text;
				}
			}
			// Set the CPT's label in case it was changed. See register_post_type() (where $args->label = $args->labels->name)
			$wp_post_types[$post_type]->label = $wp_post_types[$post_type]->labels->name;
		}
	}

	/**
	 * Whether we're on the Dashboard, Settings, Custom Post Types screen.
	 * @return bool
	 */
	private function IsSettingsPage() {
		return is_admin() && isset($_GET['page']) && $_GET['page'] == basename(__FILE__);
	}

	/**
	 * Whether or not we should save a backup of the original CPT definition before we override it.
	 * We don't want to do this on every page load
	 * @return bool
	 */
	private function NeedToBackupCustomPostTypes() {
		return $this->IsSettingsPage();
	}

	/**
	 * Set up the Admin Settings menu
	 */
	public function AdminMenu() {
		add_options_page( __( 'Custom Post Types', 'om4-cpt-editor' ), __( 'Custom Post Types', 'om4-cpt-editor' ), 'manage_options', basename( __FILE__ ), array( $this, 'AdminPage' ) );

		$this->OverrideBuiltInCustomPostTypeMenuLabels();
	}

	/**
	 * Unfortunately WordPress' built-in Custom Post Types (post, page, attachment) don't automatically use their defined labels in the Dashboard menu.
	 * Instead, they are hard-coded in wp-admin/menu.php.
	 *
	 * This function checks to see if the user has modified the labels for any of these built-in custom post types,
	 * and if so it manually overrides the dashboard menu so that it uses these defined labels.
	 */
	private function OverrideBuiltInCustomPostTypeMenuLabels() {
		global $menu, $submenu;

		$builtins_that_need_overrides = array(
			'post'
			,'page'
			,'attachment'
		);

		if ( is_array( $this->settings['types'] ) ) {
			foreach ( $builtins_that_need_overrides as $post_type ) {

				if ( !isset($this->settings['types'][$post_type]['labels']) || !is_array($this->settings['types'][$post_type]['labels']) ) {
					// The user hasn't customized the labels for this built-in CPT
					continue;
				}

				// Override built-in CPT labels
				$admin_labels_that_need_overrides = array(
					'menu_name'
					, 'all_items'
					,'add_new'
				);
				foreach ( $admin_labels_that_need_overrides as $label_name_to_override ) {

					if ( isset($this->settings['types'][$post_type]['labels'][$label_name_to_override]) ) {
						// The user has customized this label

						$id = null;
						$file = null;
						// These $id and $file values are taken from wp-admin/menu.php (where they are hard-coded)
						switch ( $post_type ) {
							case 'post': // Posts
								$id = 5;
								$file = 'edit.php';
								break;
							case 'attachment': // Media
								$id = 10;
								$file = 'upload.php';
								break;
							case 'page'; // Pages
								$id = 20;
								$file = 'edit.php?post_type=page';
								break;
						}

						if ( !is_null($id) ) {
							switch ( $label_name_to_override ) {
								case 'menu_name': // Top level menu item label
									if ( isset($menu[$id][0]) )
										$menu[$id][0] = $this->settings['types'][$post_type]['labels'][$label_name_to_override];
									break;
								case 'all_items': // 'All Items' sub menu label
									if ( isset($submenu[$file][5][0]) )
										$submenu[$file][5][0] = $this->settings['types'][$post_type]['labels'][$label_name_to_override];
									break;
								case 'add_new': // 'Add New' sub menu label
									if ( isset($submenu[$file][10][0]) )
										$submenu[$file][10][0] = $this->settings['types'][$post_type]['labels'][$label_name_to_override];
									break;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Admin Page Controller/Handler
	 */
	public function AdminPage() {

		$this->base_url = admin_url( 'options-general.php?page=' . basename(__FILE__) );

		if ( !isset($_GET['action']) )
			$_GET['action'] = '';

		switch ( $_GET['action'] ) {
				case 'edit':
					$this->AdminPageEdit();
					break;
			default:
				$this->AdminPageList();
				break;
		}
	}


	/**
	 * The Dashboard screen that lists all registered Custom Post Types
	 */
	private function AdminPageList() {
		$this->AdminPageHeader();
		?>
		<h2><?php _e( 'Registered Custom Post Types', 'om4-cpt-editor' ); ?></h2>
		<p><?php _e( 'Below is a list of registered custom post types. These post types are typically registered by WordPress core, WordPress themes or WordPress plugins.', 'om4-cpt-editor' ); ?></p>
		<p><?php _e( 'Click on a post type to view its details.', 'om4-cpt-editor' ); ?></p>
		<?php

		require('inc/OM4_CPT_List_Table.php');

		$this->list = new OM4_CPT_List_Table($this);

		$this->list->display();

		$this->AdminPageFooter();
	}

	/**
	 * The Dashboard screen that lets the user edit/modify a Custom Post Type
	 */
	function AdminPageEdit() {

		$this->AdminPageHeader();

		$custom_post_type = get_post_type_object( sanitize_key($_GET['name'] ) );
		$custom_post_type_name = isset($custom_post_type->name) ? $custom_post_type->name : null;

		if ( is_null($custom_post_type) ) {
			echo '<p>' . __( 'Invalid Custom Post Type', 'om4-cpt-editor' ) . '</p>';
			$this->BackLink();
			$this->AdminPageFooter();
			return;
		}

		$nonce = "{$this->option_name}_edit_custom_post_type_{$custom_post_type_name}";

		?>
		<h2><?php printf( esc_html__("Edit '%s' Custom Post Type", 'om4-cpt-editor'), $custom_post_type_name); ?></h2>
		<?php

		/****** Label Definitions (used when displaying the Edit form) ******/
		$labels = array();
		$labels['name']['name'] = __( 'Name:', 'om4-cpt-editor' );
		$labels['name']['description'] = __('General name for the post type, usually plural.', 'om4-cpt-editor' );

		$labels['singular_name']['name'] = __( 'Singular Name:', 'om4-cpt-editor' );
		$labels['singular_name']['description'] = __('Name for one object of this post type.', 'om4-cpt-editor' );

		$labels['add_new_item']['name'] = __( 'Add New Item:', 'om4-cpt-editor' );
		$labels['add_new_item']['description'] = __('The add new item text.', 'om4-cpt-editor' );

		$labels['edit_item']['name'] = __( 'Edit Item:', 'om4-cpt-editor' );
		$labels['edit_item']['description'] = __('The edit item text.', 'om4-cpt-editor' );

		$labels['new_item']['name'] = __( 'New Item:', 'om4-cpt-editor' );
		$labels['new_item']['description'] = __('The new item text.', 'om4-cpt-editor' );

		$labels['view_item']['name'] = __( 'View Item:', 'om4-cpt-editor' );
		$labels['view_item']['description'] = __('The view item text.', 'om4-cpt-editor' );

		$labels['search_items']['name'] = __( 'Search Items:', 'om4-cpt-editor' );
		$labels['search_items']['description'] = __('The search items text.', 'om4-cpt-editor' );

		$labels['not_found']['name'] = __( 'Not Found:', 'om4-cpt-editor' );
		$labels['not_found']['description'] = __('The not found text.', 'om4-cpt-editor' );

		$labels['not_found_in_trash']['name'] = __( 'Not Found in Trash:', 'om4-cpt-editor' );
		$labels['not_found_in_trash']['description'] = __('The not found in trash text.', 'om4-cpt-editor' );

		$labels['parent_item_colon']['name'] = __( 'Parent Item Colon:', 'om4-cpt-editor' );
		$labels['parent_item_colon']['description'] = __('The parent item text. Only used for hierarchical post types.', 'om4-cpt-editor' );
		$labels['parent_item_colon']['condition'] = 'hierarchical'; // Only display this label for hierarchical custom post types

		$labels['menu_name']['name'] = __( 'Menu Name:', 'om4-cpt-editor' );
		$labels['menu_name']['description'] = __('The text used in the Dashboard\'s top level menu.', 'om4-cpt-editor' );

		$labels['all_items']['name'] = __( 'All Items:', 'om4-cpt-editor' );
		$labels['all_items']['description'] = __('The text used in the Dashboard menu\'s \'all items\' submenu item.', 'om4-cpt-editor' );

		$labels['add_new']['name'] = __( 'Add New:', 'om4-cpt-editor' );
		$labels['add_new']['description'] =  __('The text used in the Dashboard menu\'s \'add new\' submenu item.', 'om4-cpt-editor' );

		$labels['name_admin_bar']['name'] = __( 'Admin Bar Name:', 'om4-cpt-editor' );
		$labels['name_admin_bar']['description'] = __('The text used in the Admin Bar\'s \'New\' menu.', 'om4-cpt-editor' );


		if ( isset($_POST['action']) && 'edit_custom_post_type' == $_POST['action'] ) {

			if ( !current_user_can('manage_options') ) {
				wp_die( __('Insufficient privileges!', 'om4-cpt-editor') );
			}
			check_admin_referer($nonce);

			$needs_save = false;

			if ( isset($_POST['reset_to_defaults']) && $_POST['reset_to_defaults'] == '1' ) {
				// Reset all labels to their default values
				if (isset( $this->settings['types'][$custom_post_type_name]['labels'] ) ) {
					unset($this->settings['types'][$custom_post_type_name]['labels']);
				}
				$needs_save = true;
			} else {
				// Process the labels

				foreach ( (array) $custom_post_type->labels as $label_name => $label_text ) {

					if ( isset($_POST[$label_name] ) ) {

						$_POST[$label_name] = wp_strip_all_tags( stripslashes($_POST[$label_name]) );

						if ( strlen($_POST[$label_name]) > 0 ) {
							// Some label text has been entered in the form

							if ( $_POST[$label_name] != $this->cpt_originals[$custom_post_type_name]->labels->{$label_name} ) {
								// Label text is customized from the default
								$this->settings['types'][$custom_post_type_name]['labels'][$label_name] =  $_POST[$label_name];
								$needs_save = true;
							} else {
								// Label text is the same as the default
								unset($this->settings['types'][$custom_post_type_name]['labels'][$label_name]);
								$needs_save = true;
							}

						} else {
							// No label text specified -> reset to default
							unset($this->settings['types'][$custom_post_type_name]['labels'][$label_name]);
							$needs_save = true;
						}

					}
				}
			}

			if ( $needs_save ) {
				$this->SaveSettings();
				echo '<div class="updated"><p>' . __('Custom Post Type updated. Your changes will be visible on your next page load. <a href="">Reload page</a>', 'om4-cpt-editor') . '</p></div>';
				$this->BackLink();
				$this->AdminPageFooter();
				return;
			}
		}

		?>
		<form action="" method="post" id="edit_custom_post_type">
			<h3><?php esc_html_e( 'Labels', 'om4-cpt-editor' );?></h3>
			<p><?php esc_html_e( 'This screen lets you customize one or more text or menu labels for this Custom Post Type.', 'om4-cpt-editor' ); ?></p>
			<p><?php esc_html_e( 'Customized Labels are shown in blue.', 'om4-cpt-editor' ); ?></p>
			<p><?php esc_html_e( 'To reset a label to its default, empty its text field. To reset all labels to their defaults, use the checkbox below:', 'om4-cpt-editor' ); ?></p>
			<table class="form-table">
				<tr class="form-field">
					<th scope="row">&nbsp;</th>
					<td><label for="reset_to_defaults"><input type="checkbox" name="reset_to_defaults" id="reset_to_defaults" value="1" ?><?php echo __( 'Reset all labels to their defaults', 'om4-cpt-editor' ); ?></label></td>
				<?php
				foreach ($labels as $label_name => $label_info ) {
					if ( isset($label_info['condition']) ) {
						// This label needs to satisfy a condition before it is displayed
						if ( !$custom_post_type->$label_info['condition'] ) {
							// Don't display this label
							continue;
						}
					}
					?>
					<tr class="form-field">
						<th scope="row"><label for="<?php echo ($label_name); ?>"><?php printf(__('%1s<br />(%2s)', 'om4-cpt-editor'), esc_html($label_info['name']), esc_html($label_name) ); ?></label></th>
						<td>
							<?php
							$class = esc_attr( isset($this->settings['types'][$custom_post_type_name]['labels'][$label_name]) ? 'customized' : 'default' );
							?>
							<input name="<?php echo esc_attr($label_name); ?>" type="text" id="<?php esc_attr_e($label_name); ?>" value="<?php esc_attr_e($custom_post_type->labels->$label_name); ?>" class="<?php echo $class; ?>" />
							<?php
							$default = ($this->cpt_originals[$custom_post_type_name]->labels->$label_name) ? '<code>' . esc_html($this->cpt_originals[$custom_post_type_name]->labels->$label_name) . '</code>' : esc_html__('[Empty]', 'om4-cpt-editor');
							?>
							<span class="description"><?php printf(__("%1s Default: %2s", 'om4-cpt-editor' ), esc_html($label_info['description']), $default); ?></span>
						</td>
					</tr>
					<?php
				}
				?>
			</table>
			<?php wp_nonce_field($nonce); ?>
			<input type="hidden" name="action" value="edit_custom_post_type" />
			<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'om4-cpt-editor'); ?>"></p>
		</form>
		<?php
		$this->BackLink();
		$this->AdminPageFooter();
	}

	/**
	 * The header for the Dashboard screens
	 */
	private function AdminPageHeader() {
		?>
		<div class="wrap cpt-editor">
			<style type="text/css">
				#edit_custom_post_type .form-field input[type="text"] {
					width: 20em;
				}
				#edit_custom_post_type .form-field input[type="checkbox"] {
					width: auto;
				}
				form#edit_custom_post_type .customized {
					color: blue;
				}
			</style>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					// Show/hide labels when the reset all labels checkbox is clicked
					$('form#edit_custom_post_type input[name=reset_to_defaults]').change( function() {
								$(this).closest('form').find('input[type=text]').closest('tr').toggle();
							}
					);

					// When a label text is modified, display it a customized
					$('form#edit_custom_post_type input[type=text]').keyup( function() {
								$(this).removeClass('default').addClass('customized');
							}
					);
				});
			</script>
		<?php
	}

	/**
	 * Link back
	 */
	private function BackLink() {
		?>
		<p><a href="<?php echo esc_attr($this->base_url); ?>"><?php _e( '&lArr; Back', 'om4-cpt-editor' ); ?></a></p>
		<?php
	}

	/**
	 * The footer for the Dashboard screens
	 */
	private function AdminPageFooter() {
		?>
		</div>
		<?php
	}

	/**
	 * Whether or not the specified Custom Post Type has been customized using this plugin.
	 *
	 * @param string $post_type The Custom Post Type name/identifier
	 * @return bool
	 */
	public function IsCustomized( $post_type ) {
		return ( isset($this->settings['types'][$post_type]['labels']) && is_array($this->settings['types'][$post_type]['labels']) && count($this->settings['types'][$post_type]['labels']) );
	}

	/**
	 * The number of customized labels for the specified Custom Post Type
	 * @param string $post_type The Custom Post Type name/identifier
	 * @return int
	 */
	public function NumberOfCustomizedLabels( $post_type ) {
		return ( isset($this->settings['types'][$post_type]['labels']) && is_array($this->settings['types'][$post_type]['labels']) ) ? count($this->settings['types'][$post_type]['labels']) : 0;
	}

	/**
	 * Saves the plugin's settings to the database
	 */
	function SaveSettings() {
		$data = array_merge( array( 'version' => $this->installed_version ), array( 'settings' => $this->settings ) );
		update_option( $this->option_name, $data );
	}
}

if ( defined( 'ABSPATH' ) && defined( 'WPINC' ) ) {
	if ( ! isset($GLOBALS["om4_CPT_Editor"]) ) {
		$GLOBALS["om4_CPT_Editor"] = new OM4_CPT_Editor();
	}
}
