<?php
/**
 * Implements the table of inactive plugins
 */
class Plugin_Activation_Status_List_Table_Inactive extends WP_List_Table {
	public $all_plugins = array();
	public $inactive_plugins = array();
	public $active_plugins = array();
	public $active_on = array();
	
	function set_all_plugins( $plugins=array() ) {
		$this->all_plugins = $plugins;
	}
	
	function set_active_plugins( $plugins=array() ) {
		$this->active_plugins = $plugins;
	}
	
	function set_inactive_plugins( $plugins=array() ) {
		$this->inactive_plugins = $plugins;
	}
	
	function get_plugin( $key ) {
		if ( array_key_exists( $key, $this->inactive_plugins ) )
			return $this->inactive_plugins[$key];
		
		return false;
	}
	
	function get_plugin_name( $key ) {
		if ( array_key_exists( $key, $this->all_plugins ) )
			return $this->all_plugins[$key]['Name'];
		
		return $key;
	}
	
	function get_columns() {
		return apply_filters( 'plugin-activation-status-list-table-inactive-columns', array(
			'cb'             => '<input type="checkbox"/>', 
			'plugin-name'    => __( 'Plugin Name', 'plugin-activation-status' ), 
			'raw'            => __( 'Raw Plugin Data', 'plugin-activation-status' ), 
		) );
	}
	
	function get_hidden_columns() {
		return apply_filters( 'plugin-activation-status-list-table-inactive-hidden', array(
			'raw', 
		) );
	}
	
	function get_sortable_columns() {
		return apply_filters( 'plugin-activation-status-list-table-inactive-sortable', array(
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
		return apply_filters( 'plugin-activation-status-list-table-inactive-bulk-actions', array(
			'delete'             => __( 'Delete Plugin Files', 'plugin-activation-status' )
		) );
	}
	
	function prepare_items( $plugins=array() ) {
		$this->_column_headers = $this->get_column_info();
		$this->items = array();
		foreach ( $plugins as $k=>$v ) {
			$this->items[] = array(
				'plugin-name'    => $this->get_plugin_name( $k ), 
			);
		}
		usort( $this->items, array( &$this, 'usort_reorder' ) );
		
		$this->process_bulk_action();
	}
	
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="pas_plugin_bulk_actions[]" value="%s"/>', esc_attr( $item['plugin-name'] ) );
	}
	
	function column_default( $item, $column_name=null ) {
		switch ( $column_name ) {
			case 'raw' : 
				return sprintf( '<pre><code>%s</code></pre>', print_r( $item, true ) );
				break;
			default : 
				return esc_attr( $item[$column_name] );
				break;
		}
	}
	
	function process_bulk_action() {
		if ( 'delete' == $this->current_action() ) {
			wp_die( 'This is where we would delete the selected plugins' );
		}
	}
	
	function usort_reorder( $a, $b ) {
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'plugin-name';
		$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
		
		if ( 'plugin-name' == $orderby ) {
			$result = strcmp( $this->get_plugin_name( $a[$orderby] ), $this->get_plugin_name( $b[$orderby] ) );
		} else if ( is_numeric( $a[$orderby] ) && is_numeric( $b[$orderby] ) ) {
			$result = intval( $a[$orderby] ) < intval( $b[$orderby] ) ? -1 : ( intval( $a[$orderby] ) > intval( $b[$orderby] ) ? 1 : 0 );
		} else {
			$result = strcmp( $a[$orderby], $b[$orderby] );
		}
		
		return 'asc' == $order ? $result : ( $result * -1 );
	}
}