<?php
/*
Plugin Name: Custom Post Type Editor
Plugin URI: https://om4.io/plugins/custom-post-type-editor/
Description: Customize the text labels, menu names or description for any registered custom post type using a simple Dashboard user interface.
Version: 1.6.3
Author: OM4 Software
Author URI: https://om4.io/
Text Domain: cpt-editor
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/*
	Copyright 2012-2023 OM4 (email: plugins@om4.io    web: https://om4.io/)

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


/**
 * Main plugin class.
 */
class OM4_CPT_Editor {

	/**
	 * The database version number for this plugin version.
	 *
	 * @var int
	 */
	protected $db_version = 1;

	/**
	 * The currently installed db_version.
	 *
	 * @var int
	 */
	protected $installed_version;

	/**
	 * The name of the directory that this plugin is installed in.
	 *
	 * @var string
	 */
	protected $dirname;

	/**
	 * The URL to this plugin's folder.
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * The name of the WordPress option that stores the plugin settings.
	 *
	 * @var string
	 */
	protected $option_name = 'om4_cpt_editor';

	/**
	 * The OM4_CPT_List_Table instance.
	 *
	 * @var OM4_CPT_List_Table
	 */
	protected $list;

	/**
	 * The URL to the plugin's settings page.
	 *
	 * @var string
	 */
	protected $base_url;

	/**
	 * The (backup) original copy of each custom post type before it is overridden.
	 *
	 * @var array<string, WP_Post_Type>
	 */
	protected $cpt_originals;

	/**
	 * Default settings.
	 *
	 * @var array{'types':array<string,array<string,string>>}
	 */
	protected $settings = array(
		'types' => array(),
	);

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Store the name of the directory that this plugin is installed in.
		$this->dirname = str_replace( '/cpt-editor.php', '', plugin_basename( __FILE__ ) );

		$this->url = trailingslashit( plugins_url( '', __FILE__ ) );

		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		add_action( 'plugins_loaded', array( $this, 'load_domain' ) );

		add_action( 'init', array( $this, 'check_version' ) );

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		$this->installed_version = absint( get_option( $this->option_name ) );

		$data = get_option( $this->option_name );
		if ( is_array( $data ) ) {
			$this->installed_version = absint( $data['version'] );
			$this->settings          = $data['settings'];
		}

		add_action( 'registered_post_type', array( $this, 'post_type_registered' ), 10 );
	}

	/**
	 * Initialise I18n/Localisation.
	 *
	 * @return void
	 */
	public function load_domain() {
		load_plugin_textdomain( 'cpt-editor' );
	}

	/**
	 * Plugin Activation Tasks.
	 *
	 * @return void
	 */
	public function activate() {
		// There aren't really any installation tasks (for now).
		if ( ! $this->installed_version ) {
			$this->installed_version = $this->db_version;
			$this->save_settings();
		}
	}

	/**
	 * Performs any database upgrade tasks if required.
	 *
	 * @return void
	 */
	public function check_version() {
		if ( $this->installed_version !== $this->db_version ) {
			// Upgrade tasks.
			if ( 0 === $this->installed_version ) {
				++$this->installed_version;
			}
			$this->save_settings();
		}
	}

	/**
	 * Executed whenever a Post Type is registered (by core, a plugin or a theme).
	 *
	 * Override any labels that have been customized, and if we're in the backend save a backup of the
	 * original CPT so that we can detect that its been modified.
	 *
	 * @param string $post_type Post type.
	 * @return void
	 */
	public function post_type_registered( $post_type ) {
		global $wp_post_types;

		if ( $this->need_to_backup_custom_post_types() && ! isset( $this->cpt_originals[ $post_type ] ) ) {
			// Save a copy of the original (unmodified) version of this post type.
			$this->cpt_originals[ $post_type ] = $wp_post_types[ $post_type ];
		}

		if ( isset( $this->settings['types'][ $post_type ]['labels'] ) && is_array( $this->settings['types'][ $post_type ]['labels'] ) ) {

			foreach ( $this->settings['types'][ $post_type ]['labels'] as $label_name => $label_text ) {

				if ( $label_text !== $wp_post_types[ $post_type ]->labels->$label_name ) {
					// This label text is customized, so override the default.
					$wp_post_types[ $post_type ]->labels->{$label_name} = $label_text;
				}
			}
			// Set the CPT's label in case it was changed. See register_post_type() (where $args->label = $args->labels->name).
			$wp_post_types[ $post_type ]->label = $wp_post_types[ $post_type ]->labels->name;
		}
		if ( isset( $this->settings['types'][ $post_type ]['description'] ) ) {
			if ( $this->settings['types'][ $post_type ]['description'] !== $wp_post_types[ $post_type ]->description ) {
				// The CPT description is customized, so override the default.
				$wp_post_types[ $post_type ]->description = $this->settings['types'][ $post_type ]['description'];
			}
		}
	}

	/**
	 * Whether we're on the Dashboard, Settings, Custom Post Types screen.
	 *
	 * @return bool
	 */
	private function is_settings_page() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return is_admin() && isset( $_GET['page'] ) && 'cpt_editor' === $_GET['page'];
	}

	/**
	 * Whether or not we should save a backup of the original CPT definition before we override it.
	 * We don't want to do this on every page load
	 *
	 * @return bool
	 */
	private function need_to_backup_custom_post_types() {
		return $this->is_settings_page();
	}

	/**
	 * Set up the Admin Settings menu
	 *
	 * @return void
	 */
	public function admin_menu() {
		add_options_page(
			__( 'Custom Post Types', 'cpt-editor' ),
			__( 'Custom Post Types', 'cpt-editor' ),
			'manage_options',
			'cpt_editor',
			array(
				$this,
				'admin_page',
			)
		);

		$this->override_built_in_custom_post_type_menu_labels();
	}

	/**
	 * Unfortunately WordPress' built-in Custom Post Types (post, page, attachment) don't automatically use their defined labels in the Dashboard menu.
	 * Instead, they are hard-coded in wp-admin/menu.php.
	 *
	 * This function checks to see if the user has modified the labels for any of these built-in custom post types,
	 * and if so it manually overrides the dashboard menu so that it uses these defined labels.
	 *
	 * @return void
	 */
	private function override_built_in_custom_post_type_menu_labels() {
		global $menu, $submenu;

		$builtins_that_need_overrides = array(
			'post',
			'page',
			'attachment',
		);

		if ( is_array( $this->settings['types'] ) ) {
			foreach ( $builtins_that_need_overrides as $post_type ) {

				if ( ! isset( $this->settings['types'][ $post_type ]['labels'] ) || ! is_array( $this->settings['types'][ $post_type ]['labels'] ) ) {
					// The user hasn't customized the labels for this built-in CPT.
					continue;
				}

				// Override built-in CPT labels.
				$admin_labels_that_need_overrides = array(
					'menu_name',
					'all_items',
					'add_new',
				);
				foreach ( $admin_labels_that_need_overrides as $label_name_to_override ) {

					if ( isset( $this->settings['types'][ $post_type ]['labels'][ $label_name_to_override ] ) ) {
						// The user has customized this label.

						$id   = null;
						$file = null;
						// These $id and $file values are taken from wp-admin/menu.php (where they are hard-coded).
						switch ( $post_type ) {
							case 'post':
								// Posts.
								$id   = 5;
								$file = 'edit.php';
								break;
							case 'attachment':
								// Media.
								$id   = 10;
								$file = 'upload.php';
								break;
							case 'page':
								// Pages.
								$id   = 20;
								$file = 'edit.php?post_type=page';
								break;
						}

						switch ( $label_name_to_override ) {
							case 'menu_name':
								// Top level menu item label.
								if ( isset( $menu[ $id ][0] ) ) {
									// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
									$menu[ $id ][0] = $this->settings['types'][ $post_type ]['labels'][ $label_name_to_override ];
								}
								break;
							case 'all_items':
								// 'All Items' sub menu label
								if ( isset( $submenu[ $file ][5][0] ) ) {
									// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
									$submenu[ $file ][5][0] = $this->settings['types'][ $post_type ]['labels'][ $label_name_to_override ];
								}
								break;
							case 'add_new':
								// 'Add New' sub menu label
								if ( isset( $submenu[ $file ][10][0] ) ) {
                                    // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
									$submenu[ $file ][10][0] = $this->settings['types'][ $post_type ]['labels'][ $label_name_to_override ];
								}
								break;
						}
					}
				}
			}
		}
	}

	/**
	 * Admin Page Controller/Handler.
	 *
	 * @return void
	 */
	public function admin_page() {

		$this->base_url = admin_url( 'options-general.php?page=cpt_editor' );

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['action'] ) ) {
			$this->admin_page_list();
			return;
		}

		$this->admin_page_edit();
	}


	/**
	 * The Dashboard screen that lists all registered Custom Post Types.
	 *
	 * @return void
	 */
	private function admin_page_list() {
		$this->admin_page_header();
		?>
		<h2><?php esc_html_e( 'Registered Custom Post Types', 'cpt-editor' ); ?></h2>
		<p><?php esc_html_e( 'Below is a list of registered custom post types. These post types are typically registered by WordPress core, WordPress themes or WordPress plugins.', 'cpt-editor' ); ?></p>
		<p><?php esc_html_e( 'Click on a post type to view its details.', 'cpt-editor' ); ?></p>
		<?php

		require 'inc/OM4_CPT_List_Table.php';

		$this->list = new OM4_CPT_List_Table( $this );

		$this->list->display();

		$this->admin_page_footer();
	}

	/**
	 * The Dashboard screen that lets the user edit/modify a Custom Post Type.
	 *
	 * @return void
	 */
	protected function admin_page_edit() {
		if ( ! isset( $_GET['name'] ) ) {
			return;
		}

		$this->admin_page_header();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$custom_post_type      = get_post_type_object( sanitize_key( $_GET['name'] ) );
		$custom_post_type_name = isset( $custom_post_type->name ) ? $custom_post_type->name : null;

		if ( is_null( $custom_post_type ) ) {
			echo '<p>' . esc_html__( 'Invalid Custom Post Type', 'cpt-editor' ) . '</p>';
			$this->back_link();
			$this->admin_page_footer();
			return;
		}

		$nonce = "{$this->option_name}_edit_custom_post_type_{$custom_post_type_name}";

		?>
		<h2>
		<?php
			// Translators: %s The name of the custom post type.
			echo esc_html( sprintf( __( "Edit '%s' Custom Post Type", 'cpt-editor' ), $custom_post_type_name ) );
		?>
			</h2>
		<?php

		/**
		 * Label Definitions (used when displaying the Edit form)
		 *
		 * @see get_post_type_labels() for a full list of supported labels.
		 */
		$labels = array();

		// Description isn't really a label, but it's easier this way.
		$labels['description']['name']        = __( 'Description:', 'cpt-editor' );
		$labels['description']['description'] = __( 'A short descriptive summary of what the post type is.', 'cpt-editor' );

		$labels['name']['name']        = __( 'Name:', 'cpt-editor' );
		$labels['name']['description'] = __( 'General name for the post type, usually plural.', 'cpt-editor' );

		$labels['singular_name']['name']        = __( 'Singular Name:', 'cpt-editor' );
		$labels['singular_name']['description'] = __( 'Name for one object of this post type.', 'cpt-editor' );

		$labels['add_new_item']['name']        = __( 'Add New Item:', 'cpt-editor' );
		$labels['add_new_item']['description'] = __( 'The add new item text.', 'cpt-editor' );

		$labels['edit_item']['name']        = __( 'Edit Item:', 'cpt-editor' );
		$labels['edit_item']['description'] = __( 'The edit item text.', 'cpt-editor' );

		$labels['new_item']['name']        = __( 'New Item:', 'cpt-editor' );
		$labels['new_item']['description'] = __( 'The new item text.', 'cpt-editor' );

		$labels['view_item']['name']        = __( 'View Item:', 'cpt-editor' );
		$labels['view_item']['description'] = __( 'The view item text.', 'cpt-editor' );

		$labels['view_items']['name']        = __( 'View Items:', 'cpt-editor' );
		$labels['view_items']['description'] = __( 'The label used in the toolbar on the post listing screen (if this post type supports archives).', 'cpt-editor' );

		$labels['attributes']['name']        = __( 'Attributes:', 'cpt-editor' );
		$labels['attributes']['description'] = __( 'The label used for the title of the post attributes meta box (used to select post type templates).', 'cpt-editor' );

		$labels['search_items']['name']        = __( 'Search Items:', 'cpt-editor' );
		$labels['search_items']['description'] = __( 'The search items text.', 'cpt-editor' );

		$labels['not_found']['name']        = __( 'Not Found:', 'cpt-editor' );
		$labels['not_found']['description'] = __( 'The not found text.', 'cpt-editor' );

		$labels['not_found_in_trash']['name']        = __( 'Not Found in Trash:', 'cpt-editor' );
		$labels['not_found_in_trash']['description'] = __( 'The not found in trash text.', 'cpt-editor' );

		$labels['parent_item_colon']['name']        = __( 'Parent Item Colon:', 'cpt-editor' );
		$labels['parent_item_colon']['description'] = __( 'The parent item text. Only used for hierarchical post types.', 'cpt-editor' );
		$labels['parent_item_colon']['condition']   = 'hierarchical';
		// Only display this label for hierarchical custom post types.

		$labels['menu_name']['name']        = __( 'Menu Name:', 'cpt-editor' );
		$labels['menu_name']['description'] = __( 'The text used in the Dashboard\'s top level menu.', 'cpt-editor' );

		$labels['all_items']['name']        = __( 'All Items:', 'cpt-editor' );
		$labels['all_items']['description'] = __( 'The text used in the Dashboard menu\'s \'all items\' submenu item.', 'cpt-editor' );

		$labels['add_new']['name']        = __( 'Add New:', 'cpt-editor' );
		$labels['add_new']['description'] = __( 'The text used in the Dashboard menu\'s \'add new\' submenu item.', 'cpt-editor' );

		$labels['name_admin_bar']['name']        = __( 'Admin Bar Name:', 'cpt-editor' );
		$labels['name_admin_bar']['description'] = __( 'The text used in the Admin Bar\'s \'New\' menu.', 'cpt-editor' );

		/**
		 * New labels added in WordPress 4.3.
		 *
		 * @link https://make.wordpress.org/core/2015/12/11/additional-labels-for-custom-post-types-and-custom-taxonomies/
		 */
		$labels['featured_image']['name']        = __( 'Featured Image:', 'cpt-editor' );
		$labels['featured_image']['description'] = __( 'Overrides the \'Featured Image\' phrase for this post type.', 'cpt-editor' );

		$labels['set_featured_image']['name']        = __( 'Set featured Image:', 'cpt-editor' );
		$labels['set_featured_image']['description'] = __( 'Overrides the \'Set featured image\' phrase for this post type.', 'cpt-editor' );

		$labels['remove_featured_image']['name']        = __( 'Remove featured Image:', 'cpt-editor' );
		$labels['remove_featured_image']['description'] = __( 'Overrides the \'Remove featured image\' phrase for this post type.', 'cpt-editor' );

		$labels['use_featured_image']['name']        = __( 'Use as featured Image:', 'cpt-editor' );
		$labels['use_featured_image']['description'] = __( 'Overrides the \'Use as featured image\' phrase for this post type.', 'cpt-editor' );

		/**
		 * New labels added in WordPress 4.4.
		 *
		 * @link https://make.wordpress.org/core/2015/12/11/additional-labels-for-custom-post-types-and-custom-taxonomies/
		 */
		$labels['archives']['name']        = __( 'Archives:', 'cpt-editor' );
		$labels['archives']['description'] = __( 'The post type archive label used in nav menus.', 'cpt-editor' );

		$labels['insert_into_item']['name']        = __( 'Insert into post:', 'cpt-editor' );
		$labels['insert_into_item']['description'] = __( 'Overrides the \'Insert into post\'/\'Insert into page\' phrase (used when inserting media into a post).', 'cpt-editor' );

		$labels['uploaded_to_this_item']['name']        = __( 'Uploaded to this post:', 'cpt-editor' );
		$labels['uploaded_to_this_item']['description'] = __( 'Overrides the \'Uploaded to this post\'/\'Uploaded to this page\' phrase (used when viewing media attached to a post).', 'cpt-editor' );

		$labels['filter_items_list']['name']        = __( 'Filter posts list:', 'cpt-editor' );
		$labels['filter_items_list']['description'] = __( 'Screen reader text for the filter links heading on the post type listing screen.', 'cpt-editor' );

		$labels['items_list_navigation']['name']        = __( 'Posts list navigation:', 'cpt-editor' );
		$labels['items_list_navigation']['description'] = __( 'Screen reader text for the pagination heading on the post type listing screen.', 'cpt-editor' );

		$labels['items_list']['name']        = __( 'Posts list:', 'cpt-editor' );
		$labels['items_list']['description'] = __( 'Screen reader text for the items list heading on the post type listing screen.', 'cpt-editor' );

		/**
		 * New labels added in WordPress 4.7.
		 */
		$labels['view_items']['name']        = __( 'View Items:', 'cpt-editor' );
		$labels['view_items']['description'] = __( 'Label for viewing post type archives.', 'cpt-editor' );

		$labels['attributes']['name']        = __( 'Attributes:', 'cpt-editor' );
		$labels['attributes']['description'] = __( 'Label for the attributes meta box.', 'cpt-editor' );

		/**
		 * New labels added in WordPress 5.0.
		 */
		$labels['item_published']['name']        = __( 'Item Published:', 'cpt-editor' );
		$labels['item_published']['description'] = __( 'Label used when an item is published.', 'cpt-editor' );

		$labels['item_published_privately']['name']        = __( 'Item Published Privately:', 'cpt-editor' );
		$labels['item_published_privately']['description'] = __( 'Label used when an item is published with private visibility.', 'cpt-editor' );

		$labels['item_reverted_to_draft']['name']        = __( 'Item Reverted to Draft:', 'cpt-editor' );
		$labels['item_reverted_to_draft']['description'] = __( 'Label used when an item is switched to a draft.', 'cpt-editor' );

		$labels['item_scheduled']['name']        = __( 'Item Scheduled:', 'cpt-editor' );
		$labels['item_scheduled']['description'] = __( 'Label used when an item is scheduled for publishing.', 'cpt-editor' );

		$labels['item_updated']['name']        = __( 'Item Updated:', 'cpt-editor' );
		$labels['item_updated']['description'] = __( 'Label used when an item is updated.', 'cpt-editor' );

		/**
		 * New labels added in WordPress 5.7.
		 */
		$labels['filter_by_date']['name']        = __( 'Filter by Date:', 'cpt-editor' );
		$labels['filter_by_date']['description'] = __( 'Label for the date filter in list tables.', 'cpt-editor' );

		/**
		 * New labels added in WordPress 5.8.
		 */
		$labels['item_link']['name']        = __( 'Item Link:', 'cpt-editor' );
		$labels['item_link']['description'] = __( 'Title for a navigation link block variation.', 'cpt-editor' );

		$labels['item_link_description']['name']        = __( 'Item Link Description:', 'cpt-editor' );
		$labels['item_link_description']['description'] = __( 'Description for a navigation link block variation.', 'cpt-editor' );

		if ( isset( $_POST['action'] ) && 'edit_custom_post_type' === $_POST['action'] ) {

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Insufficient privileges!', 'cpt-editor' ) );
			}
			check_admin_referer( $nonce );

			$needs_save = false;

			if ( isset( $_POST['reset_to_defaults'] ) && '1' === $_POST['reset_to_defaults'] ) {
				// Reset all labels to their default values.
				if ( isset( $this->settings['types'][ $custom_post_type_name ]['labels'] ) ) {
					unset( $this->settings['types'][ $custom_post_type_name ]['labels'] );
				}
				// Reset description to its default value.
				if ( isset( $this->settings['types'][ $custom_post_type_name ]['description'] ) ) {
					unset( $this->settings['types'][ $custom_post_type_name ]['description'] );
				}
				$needs_save = true;
			} else {
				// Process the labels.

				foreach ( (array) $custom_post_type->labels as $label_name => $label_text ) {

					if ( isset( $_POST[ $label_name ] ) ) {

						$label_name_input = sanitize_text_field( wp_unslash( $_POST[ $label_name ] ) );

						if ( strlen( $label_name_input ) > 0 ) {
							// Some label text has been entered in the form.

							if ( $label_name_input !== $this->cpt_originals[ $custom_post_type_name ]->labels->{$label_name} ) {
								// Label text is customized from the default.
								$this->settings['types'][ $custom_post_type_name ]['labels'][ $label_name ] = $label_name_input;
								$needs_save = true;
							} else {
								// Label text is the same as the default.
								unset( $this->settings['types'][ $custom_post_type_name ]['labels'][ $label_name ] );
								$needs_save = true;
							}
						} else {
							// No label text specified -> reset to default.
							unset( $this->settings['types'][ $custom_post_type_name ]['labels'][ $label_name ] );
							$needs_save = true;
						}
					}
				}

				// Process the description.

				if ( isset( $_POST['description'] ) ) {

					$description = sanitize_text_field( wp_unslash( $_POST['description'] ) );

					if ( strlen( $description ) > 0 ) {
						// Some description has been entered in the form.

						if ( $description !== $this->cpt_originals[ $custom_post_type_name ]->description ) {
							// Description is customized from the default.
							$this->settings['types'][ $custom_post_type_name ]['description'] = $description;
							$needs_save = true;
						} else {
							// Description is the same as the default.
							unset( $this->settings['types'][ $custom_post_type_name ]['description'] );
							$needs_save = true;
						}
					} else {
						// No description text specified -> reset to default.
						unset( $this->settings['types'][ $custom_post_type_name ]['description'] );
						$needs_save = true;
					}
				}
			}

			if ( $needs_save ) {
				$this->save_settings();
				echo '<div class="updated"><p>' . wp_kses( __( 'Custom Post Type updated. Your changes will be visible on your next page load. <a href="">Reload page</a>', 'cpt-editor' ), array( 'a' => array( 'href' => true ) ) ) . '</p></div>';
				$this->back_link();
				$this->admin_page_footer();
				return;
			}
		}

		?>
		<form action="" method="post" id="edit_custom_post_type">
			<p><?php esc_html_e( 'This screen lets you customize the description and/or labels for this Custom Post Type.', 'cpt-editor' ); ?></p>
			<p><?php esc_html_e( 'Customized fields are shown in blue.', 'cpt-editor' ); ?></p>
			<p><?php esc_html_e( 'To reset a field to its default, empty its text field. To reset all to their defaults, use the checkbox below:', 'cpt-editor' ); ?></p>
			<table class="form-table">
				<tr class="form-field">
					<th scope="row">&nbsp;</th>
					<td><label for="reset_to_defaults"><input type="checkbox" name="reset_to_defaults" id="reset_to_defaults" value="1" ?><?php esc_html_e( 'Reset all to their defaults', 'cpt-editor' ); ?></label></td>
				<?php
				foreach ( $labels as $label_name => $label_info ) {
					if ( isset( $label_info['condition'] ) ) {
						// This label needs to satisfy a condition before it is displayed.
						if ( ! $custom_post_type->{$label_info['condition']} ) {
							// Don't display this label.
							continue;
						}
					}
					?>
					<tr class="form-field">
						<th scope="row">
							<label for="<?php echo esc_attr( $label_name ); ?>">
								<?php
								if ( 'description' === $label_name ) {
									echo esc_html( $label_info['name'] );
								} else {
									// Translators: 1: Label Name. 2: Custom Post Type Name.
									echo wp_kses( sprintf( __( '%1$1s<br />(%2$2s)', 'cpt-editor' ), $label_info['name'], $label_name ), array( 'br' => array() ) );
								}
								?>
							</label></th>
						<td>
							<?php
							$class = '';
							if ( 'description' === $label_name ) {
								$class = esc_attr( isset( $this->settings['types'][ $custom_post_type_name ]['description'] ) ? 'customized' : 'default' );
							} else {
								$class = esc_attr( isset( $this->settings['types'][ $custom_post_type_name ]['labels'][ $label_name ] ) ? 'customized' : 'default' );
							}

							$value = '';
							if ( 'description' === $label_name ) {
								$value = $custom_post_type->description;
							} else {
								$value = $custom_post_type->labels->$label_name;
							}
							?>
							<input name="<?php echo esc_attr( $label_name ); ?>" type="text" id="<?php echo esc_attr( $label_name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="<?php echo sanitize_html_class( $class ); ?>" />
							<?php
							$default = '';
							if ( 'description' === $label_name ) {
								$default = ( $this->cpt_originals[ $custom_post_type_name ]->description ) ? '<code>' . esc_html( $this->cpt_originals[ $custom_post_type_name ]->description ) . '</code>' : esc_html__( '[Empty]', 'cpt-editor' );
							} else {
								$default = ( $this->cpt_originals[ $custom_post_type_name ]->labels->$label_name ) ? '<code>' . esc_html( $this->cpt_originals[ $custom_post_type_name ]->labels->$label_name ) . '</code>' : esc_html__( '[Empty]', 'cpt-editor' );
							}
							?>
							<span class="description">
							<?php
								// Translators: 1: Label Description. 2: Label Default.
								echo wp_kses( sprintf( __( '%1$1s Default: %2$2s', 'cpt-editor' ), $label_info['description'], $default ), array( 'code' => array() ) );
							?>
								</span>
						</td>
					</tr>
					<?php
				}
				?>
			</table>
			<?php wp_nonce_field( $nonce ); ?>
			<input type="hidden" name="action" value="edit_custom_post_type" />
			<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'cpt-editor' ); ?>"></p>
		</form>
		<?php
		$this->back_link();
		$this->admin_page_footer();
	}

	/**
	 * The header for the Dashboard screens.
	 *
	 * @return void
	 */
	private function admin_page_header() {
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
	 * Link back.
	 *
	 * @return void
	 */
	private function back_link() {
		?>
		<p><a href="<?php echo esc_attr( $this->base_url ); ?>"><?php esc_html_e( '&lArr; Back', 'cpt-editor' ); ?></a></p>
		<?php
	}

	/**
	 * The footer for the Dashboard screens.
	 *
	 * @return void
	 */
	private function admin_page_footer() {
		?>
		</div>
		<?php
	}

	/**
	 * Whether or not the specified Custom Post Type has been customized using this plugin.
	 *
	 * @param string $post_type The Custom Post Type name/identifier.
	 * @return bool
	 */
	public function is_customized( $post_type ) {
		return ( isset( $this->settings['types'][ $post_type ]['labels'] ) && is_array( $this->settings['types'][ $post_type ]['labels'] ) && count( $this->settings['types'][ $post_type ]['labels'] ) );
	}

	/**
	 * The number of customizations for the specified Custom Post Type
	 *
	 * @param string $post_type The Custom Post Type name/identifier.
	 * @return int
	 */
	public function number_of_customizations( $post_type ) {
		$num = ( isset( $this->settings['types'][ $post_type ]['labels'] ) && is_array( $this->settings['types'][ $post_type ]['labels'] ) ) ? count( $this->settings['types'][ $post_type ]['labels'] ) : 0;
		if ( isset( $this->settings['types'][ $post_type ]['description'] ) ) {
			++$num;
		}
		return $num;
	}

	/**
	 * Saves the plugin's settings to the database
	 *
	 * @return void
	 */
	protected function save_settings() {
		$data = array_merge( array( 'version' => $this->installed_version ), array( 'settings' => $this->settings ) );
		update_option( $this->option_name, $data );
	}
}

if ( defined( 'ABSPATH' ) && defined( 'WPINC' ) ) {
	if ( ! isset( $GLOBALS['om4_CPT_Editor'] ) ) {
		$GLOBALS['om4_CPT_Editor'] = new OM4_CPT_Editor();
	}
}
