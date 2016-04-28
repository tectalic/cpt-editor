<?php
/*  Copyright 2012-2016 OM4 (email : plugins@om4.com.au)

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

if ( !class_exists('WP_List_Table') ){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * The list table for the Custom Post Types listing screen
 */
class OM4_CPT_List_Table extends WP_List_Table {

	private $instance;

	public function __construct( $instance ) {

		$this->instance = $instance;

		parent::__construct( array(
			'singular'  => 'cpt',     //singular name of the listed records
			'plural'    => 'cpts',    //plural name of the listed records
			'ajax'      => false        //does this table support ajax?
		) );

		$this->prepare_items();

	}

	/**
	 * Retrieve the list of custom post types
	 */
	function prepare_items() {

		$post_types = get_post_types(array(), 'objects');
		foreach ( $post_types as $post_type => $post_type_object ) {
			$this->items[] = array(
				'title' => $post_type_object->label,
				'name' => $post_type,
				'status' => $this->instance->NumberOfCustomizations($post_type)
			);
		}

		$columns = $this->get_columns();

		$this->_column_headers = array($columns, array(), array());

	}

	function get_columns() {
		$columns = array(
			'name'     => __( 'Custom Post Type', 'cpt-editor' )
			,'status'     => __( 'Status', 'cpt-editor' )
		);
		return $columns;
	}

	function column_default( $item, $column_name ) {

	}

	function column_name( $item ) {

		// URL to the edit screen
		$edit_url = add_query_arg( array( 'action' => 'edit', 'name' => $item['name']) );

		//Build row actions
		$actions = array(
			'edit'      => sprintf( __('<a href="%s">Edit</a>', 'cpt-editor'), esc_url( $edit_url ) )
		);

		//Return the title contents
		return sprintf( '<a href="%1$s">%2$s</a> <span style="color:silver">(%3$s)</span>%4$s',
			/*$1%s*/
			$edit_url,
			/*$2%s*/
			esc_html($item['title']),
			/*$3%s*/
			esc_html($item['name']),
			/*$4%s*/
			$this->row_actions( $actions )
		);
	}

	function column_status( $item ) {
		if ( $item['status'] > 0 ) {
			return __( 'Customized', 'cpt-editor' );
		} else {
			return __( 'Default', 'cpt-editor' );
		}
	}

}
