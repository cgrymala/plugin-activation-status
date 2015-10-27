<?php
/**
 * Implements the table of status indicators
 */
class Plugin_Activation_Status_List_Table extends WP_List_Table {
	function get_columns() {
		return apply_filters( 'plugin-activation-status-list-table-columns', array(
			'cb'             => '<input type="checkbox"/>', 
			'plugin-name'    => __( 'Plugin Name', 'plugin-activation-status' ), 
			'network-active' => __( 'Network Activated On', 'plugin-activation-status' ), 
			'blog-active'    => __( 'Blog Activated On', 'plugin-activation-status' ), 
			'raw'            => __( 'Raw Plugin Data', 'plugin-activation-status' ), 
		) );
	}
	
	function get_hidden_columns() {
		return apply_filters( 'plugin-activation-status-list-table-hidden', array(
			'raw', 
		) );
	}
	
	function get_sortable_columns() {
		return apply_filters( 'plugin-activation-status-list-table-sortable', array(
			'name' => array( 'name', false )
		) );
	}
	
	function get_column_info() {
		return array(
			$this->get_columns(), 
			$this->get_hidden_columns(), 
			$this->get_sortable_columns(),
		);
	}
	
	function get_bulk_actions() {
		return apply_filters( 'plugin-activation-status-list-table-bulk-actions', array(
			'network-deactivate' => __( 'Deactivate on All Networks', 'plugin-activation-status' ), 
			'blog-deactivate'    => __( 'Deactivate on All Sites', 'plugin-activation-status' ), 
			'delete'             => __( 'Delete Plugin Files', 'plugin-activation-status' )
		) );
	}
	
	function prepare_items( $plugins=array() ) {
		$this->_column_headers = $this->get_column_info();
		usort( $plugins, array( &$this, 'usort_reorder' ) );
		$this->items = $plugins;
	}
	
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="pas_plugin_bulk_actions[]" value="%s"/>', esc_attr( $item['plugin_name'] ) );
	}
	
	function column_default( $item, $column_name=null ) {
		switch ( $column_name ) {
			case 'raw' : 
				return sprintf( '<pre><code>%s</code></pre>', print_r( $item, true ) );
				break;
			case 'plugin-name' : 
				return $item[$column_name];
				break;
			case 'network-active' : 
			case 'blog-active' : 
				$rt = '<ol class="pas-site-list">';
				foreach ( $item[$column_name] as $site_id=>$site_name ) {
					$rt .= sprintf( '<li>%d %s</li>', $site_id, $site_name );
				}
				$rt .= '</ol>';
				break;
			default : 
				return esc_attr( $item[$column_name] );
				break;
		}
	}
	
	function usort_reorder( $a, $b ) {
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'name';
		$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
		
		if ( is_numeric( $a[$orderby] ) && is_numeric( $b[$orderby] ) ) {
			$result = intval( $a[$orderby] ) < intval( $b[$orderby] ) ? -1 : ( intval( $a[$orderby] ) > intval( $b[$orderby] ) ? 1 : 0 );
		} else {
			$result = strcmp( $a[$orderby], $b[$orderby] );
		}
		
		return 'asc' == $order ? $result : ( $result * -1 );
	}
}