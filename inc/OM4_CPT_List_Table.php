<?php
/*
	Copyright 2012-2023 OM4 (email : plugins@om4.com.au)

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

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * The list table for the Custom Post Types listing screen
 */
class OM4_CPT_List_Table extends WP_List_Table {

	/**
	 * The OM4 CPT Editor plugin instance.
	 *
	 * @var OM4_CPT_Editor
	 */
	private $instance;

	/**
	 * Constructor.
	 *
	 * @param OM4_CPT_Editor $instance The OM4 CPT Editor plugin instance.
	 */
	public function __construct( OM4_CPT_Editor $instance ) {
		$this->instance = $instance;

		parent::__construct(
			array(
				// Singular name of the listed records.
				'singular' => 'cpt',
				// Plural name of the listed records.
				'plural'   => 'cpts',
				// Does this table support ajax?
				'ajax'     => false,
			)
		);

		$this->prepare_items();
	}


	/**
	 * Retrieve the list of custom post types
	 *
	 * @return void
	 */
	public function prepare_items() {
		$post_types = get_post_types( array(), 'objects' );
		foreach ( $post_types as $post_type => $post_type_object ) {
			$this->items[] = array(
				'title'  => $post_type_object->label,
				'name'   => $post_type,
				'status' => $this->instance->number_of_customizations( (string) $post_type ),
			);
		}

		$columns = $this->get_columns();

		$this->_column_headers = array( $columns, array(), array() );
	}

	/**
	 * Gets a list of columns.
	 *
	 * @inheritdoc
	 * @return array<string,string>
	 */
	public function get_columns() {
		$columns = array(
			'name'   => __( 'Custom Post Type', 'cpt-editor' ),
			'status' => __( 'Status', 'cpt-editor' ),
		);

		return $columns;
	}

	/**
	 * Generates content for a single row's name column.
	 *
	 * @param array<string,string> $item The current item.
	 * @return string
	 */
	public function column_name( $item ) {
		// URL to the edit screen.
		$edit_url = add_query_arg(
			array(
				'action' => 'edit',
				'name'   => $item['name'],
			)
		);

		// Build row actions.
		$actions = array(
			// Translators: %s the URL of the edit page.
			'edit' => sprintf( __( '<a href="%s">Edit</a>', 'cpt-editor' ), esc_url( $edit_url ) ),
		);

		// Return the title contents.
		return sprintf(
			'<a href="%1$s">%2$s</a> <span style="color:silver">(%3$s)</span>%4$s',
			/*$1%s*/
			$edit_url,
			/*$2%s*/
			esc_html( $item['title'] ),
			/*$3%s*/
			esc_html( $item['name'] ),
			/*$4%s*/
			$this->row_actions( $actions )
		);
	}

	/**
	 * Generates content for a single row's status column.
	 *
	 * @param array<string,string> $item The current item.
	 *
	 * @return string|null
	 */
	public function column_status( $item ) {
		if ( $item['status'] > 0 ) {
			return __( 'Customized', 'cpt-editor' );
		} else {
			return __( 'Default', 'cpt-editor' );
		}
	}
}
