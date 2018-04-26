<?php
/**
 * Implements the table of status indicators
 */
class Plugin_Activation_Status_List_Table extends WP_List_Table {
	public $all_plugins = array();
	public $active_plugins = array();
	public $active_on = array();
	
	function set_all_plugins( $plugins=array() ) {
		$this->all_plugins = $plugins;
	}
	
	function set_active_plugins( $plugins=array() ) {
		$this->active_plugins = $plugins;
	}
	
	function get_plugin( $key ) {
		if ( array_key_exists( $key, $this->active_plugins ) )
			return $this->active_plugins[$key];
		
		return false;
	}
	
	function set_active_on( $plugins=array() ) {
		$this->active_on = $plugins;
	}
	
	function get_blog_active_on( $key ) {
		if ( array_key_exists( $key, $this->active_on ) && array_key_exists( 'site', $this->active_on[$key] ) )
			return $this->active_on[$key]['site'];
		
		return array();
	}
	
	function get_network_active_on( $key ) {
		if ( array_key_exists( $key, $this->active_on ) && array_key_exists( 'network', $this->active_on[$key] ) )
			return $this->active_on[$key]['network'];
		
		return array();
	}
	
	function get_plugin_name( $key ) {
		if ( array_key_exists( $key, $this->all_plugins ) )
			return $this->all_plugins[$key]['Name'];
		
		return $key;
	}
	
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
			'plugin-name' => array( 'plugin-name', true ),
			'network-active' => array( 'network-active', false ),
			'blog-active' => array( 'blog-active', false ),
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
		) );
	}
	
	function prepare_items( $plugins=array() ) {
		$this->_column_headers = $this->get_column_info();
		$this->items = array();
		foreach ( $this->active_on as $k=>$v ) {
			$this->items[] = array(
				'plugin-path'    => $k,
				'plugin-name'    => $this->get_plugin_name( $k ), 
				'network-active' => $this->get_network_active_on( $k ), 
				'blog-active'    => $this->get_blog_active_on( $k ), 
			);
		}
		if ( ( isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], array( 'plugin-name', 'network-active', 'blog-active' ) ) ) || ! isset( $_GET['orderby'] ) ) {
			usort( $this->items, array( &$this, 'usort_reorder' ) );
		}
		
		$this->process_bulk_action();
	}
	
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="pas_plugin_bulk_actions[]" value="%s"/>', esc_attr( $item['plugin-path'] ) );
	}
	
	function column_default( $item, $column_name=null ) {
		switch ( $column_name ) {
			case 'raw' : 
				return sprintf( '<pre><code>%s</code></pre>', print_r( $item, true ) );
				break;
			case 'network-active' : 
			case 'blog-active' : 
				$rt = '<ul class="pas-site-list" style="list-style: none;">';
				foreach ( $item[$column_name] as $site_id=>$site_name ) {
					$rt .= sprintf( '<li>%d. %s</li>', $site_id, $site_name );
				}
				$rt .= '</ul>';
				return $rt;
				break;
			default : 
				return esc_attr( $item[$column_name] );
				break;
		}
	}
	
	function process_bulk_action() {
		if ( ! isset( $_POST['pas_plugin_bulk_actions'] ) ) {
			return;
		}

		if ( 'blog-deactivate' == $this->current_action() ) {
			foreach ( $_POST['pas_plugin_bulk_actions'] as $plugin ) {
				foreach ( $this->get_blog_active_on( $plugin ) as $blog ) {
					$this->deactivate_plugin( $plugin, $blog );
				}
			}
		} else if ( 'network-deactivate' == $this->current_action() ) {
			foreach ( $_POST['pas_plugin_bulk_actions'] as $plugin ) {
				foreach ( $this->get_network_active_on( $plugin ) as $network ) {
					$this->network_deactivate_plugin( $plugin, $network );
				}
			}
		}

		$url = network_admin_url( 'plugins.php' );
		$url = add_query_arg( 'page', 'all_active_plugins', $url );
		$url = add_query_arg( 'list_active_plugins', '1', $url );
		$url = wp_nonce_url( 'active_plugins', '_active_plugins_nonce', $url );
		print( '<pre><code>' );
		var_dump( $url );
		print( '</code></pre>' );
		return;
		wp_safe_redirect( $url );
	}

	function deactivate_plugin( $plugin, $blog ) {
		$b = $blog;
		global $wpdb;

		$wpdb->set_blog_id( $b );
		$active_plugins = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name=%s", 'active_plugins' ) );
		if ( is_wp_error( $active_plugins ) ) {
			return;
		}
		if ( ! is_array( $active_plugins ) )
			$active_plugins = maybe_unserialize( $active_plugins );
		if ( ! is_array( $active_plugins ) )
			return;

		if ( in_array( $_POST['plugin'], $active_plugins ) ) {
			$index = array_search( $_POST['plugin'], $active_plugins );
			unset( $active_plugins[$index] );
		} elseif ( array_key_exists( $_POST['plugin'], $active_plugins ) ) {
			unset( $active_plugins[$_POST['plugin']] );
		}
		$done = $wpdb->update( $wpdb->options, array( 'option_value' => maybe_serialize( $active_plugins ) ), array( 'option_name' => 'active_plugins' ), array( '%s' ), array( '%s' ) );

		return;
	}

	function network_deactivate_plugin( $plugin, $network ) {
		$n = $network;
		global $wpdb;

		$active_plugins = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->sitemeta} WHERE meta_key=%s AND site_id=%d", 'active_sitewide_plugins', $n ) );
		if ( is_wp_error( $active_plugins ) ) {
			return;
		}
		if ( ! is_array( $active_plugins ) )
			$active_plugins = maybe_unserialize( $active_plugins );
		if ( ! is_array( $active_plugins ) )
			return;

		if ( in_array( $plugin, $active_plugins ) ) {
			$index = array_search( $plugin, $active_plugins );
			unset( $active_plugins[$index] );
		} elseif ( array_key_exists( $plugin, $active_plugins ) ) {
			unset( $active_plugins[$plugin] );
		}
		$done = $wpdb->update( $wpdb->sitemeta, array( 'meta_value' => maybe_serialize( $active_plugins ) ), array( 'meta_key' => 'active_sitewide_plugins', 'site_id' => $n ), array( '%s' ), array( '%s', '%d' ) );

		return;
	}
	
	function usort_reorder( $a, $b ) {
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'plugin-name';
		$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
		
		if ( 'inactive-plugin-name' == $orderby ) {
			$order = 'asc';
			$orderby = 'plugin-name';
		}
		
		if ( 'plugin-name' == $orderby ) {
			$result = strcasecmp( $this->get_plugin_name( $a['plugin-path'] ), $this->get_plugin_name( $b['plugin-path'] ) );
		} else if ( 'network-active' == $orderby || 'blog-active' == $orderby ) {
			$result = strcasecmp( count( $a[$orderby] ), count( $b[$orderby] ) );
		}
		
		return 'asc' == $order ? $result : ( $result * -1 );
	}
}