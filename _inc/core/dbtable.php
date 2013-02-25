<?php
/**
 * DB table Component
 *
 * @package Frl_mitest
 */

/**
 * Class to handle custom DB tables creation
 *
 * forked code of scbTable class as part of scribu framework
 * http://scribu.net
 **/
class FRL_Dbtable {
	
	protected $name;
	protected $columns;
	protected $upgrade_method;

	
	function __construct( $name, $columns, $upgrade_method = 'dbDelta' ) {
		global $wpdb;

		$this->name = $wpdb->prefix . $name;
		$this->columns = $columns;
		$this->upgrade_method = $upgrade_method;
		
		//in DB
		if(!$this->check_exist())
            $this->install(); 
		
		//in WP
		$wpdb->tables[] = $name;
		$wpdb->$name = $this->name;
		
	}

	
	function install() {
		global $wpdb;

		$charset_collate = '';
		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty( $wpdb->charset ) )
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if ( ! empty( $wpdb->collate ) )
				$charset_collate .= " COLLATE $wpdb->collate";
		}

		if ( 'dbDelta' == $this->upgrade_method ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( "CREATE TABLE $this->name ( $this->columns ) $charset_collate" );		
			return;
		}

		if ( 'delete_first' == $this->upgrade_method )
			$wpdb->query( "DROP TABLE IF EXISTS $this->name;" );

		$wpdb->query( "CREATE TABLE IF NOT EXISTS $this->name ( $this->columns ) $charset_collate;" );
	}

	
	function uninstall() {
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS $this->name" );
	}
	
	
	function check_exist(){
		global $wpdb;		
			
		$tables = $wpdb->get_col('SHOW TABLES');
		
		if(in_array($this->name, $tables))	
			return true;
			
		return false;	
	}
	
} //class end


 
 
 
 
 
 
 
 
 
 
 
 
 
 ?>
