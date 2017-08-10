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
			'inactive-plugin-name'    => __( 'Plugin Name', 'plugin-activation-status' ), 
			'inactive-raw'            => __( 'Raw Plugin Data', 'plugin-activation-status' ), 
		) );
	}
	
	function get_hidden_columns() {
		return apply_filters( 'plugin-activation-status-list-table-inactive-hidden', array(
			'inactive-raw', 
		) );
	}
	
	function get_sortable_columns() {
		return apply_filters( 'plugin-activation-status-list-table-inactive-sortable', array(
			'inactive-plugin-name' => array( 'inactive-plugin-name', true )
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
				'inactive-plugin-slug'    => $v,
				'inactive-plugin-name'    => $this->get_plugin_name( $v ), 
			);
		}
		if ( ( isset( $_GET['orderby'] ) && 'inactive-plugin-name' == $_GET['orderby'] ) || ! isset( $_GET['orderby'] ) ) {
			usort( $this->items, array( &$this, 'usort_reorder' ) );
		}
		
		$this->process_bulk_action();
	}
	
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="pas_plugin_bulk_actions_inactive[]" value="%s"/>', esc_attr( $item['inactive-plugin-slug'] ) );
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

	/**
	 * Perform the appropriate bulk action
	 * @TODO Figure out how to reload the table data after plugins are deleted
	 *
	 * @return void
	 */
	function process_bulk_action() {
		if ( 'delete' == $this->current_action() ) {
			delete_plugins( $_POST['pas_plugin_bulk_actions_inactive'] );
			$url = network_admin_url( 'plugins.php' );
			$url = add_query_arg( 'page', 'all_active_plugins', $url );
			$url = add_query_arg( 'list_active_plugins', '1', $url );
			$url = wp_nonce_url( 'active_plugins', '_active_plugins_nonce', $url );
			wp_safe_redirect( $url );
		}
	}
	
	function usort_reorder( $a, $b ) {
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'inactive-plugin-name';
		$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
		
		if ( 'plugin-name' == $orderby ) {
			$order = 'asc';
			$orderby = 'inactive-plugin-name';
		}
		
		if ( 'inactive-plugin-name' == $orderby ) {
			$result = strcasecmp( $this->get_plugin_name( $a[$orderby] ), $this->get_plugin_name( $b[$orderby] ) );
		}
		
		return 'asc' == $order ? $result : ( $result * -1 );
	}
}