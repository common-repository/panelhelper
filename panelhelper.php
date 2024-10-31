<?php
/**
 * Plugin Name: Panelhelper - SMM Panel API tool
 * Version: 2.1.0
 * Description: Integrate your SMM panel API to your wordpress store.
 * Author: Appalify
 * Author URI: https://appalify.com/panelhelper/
 * Requires at least: 4.0
 * Tested up to: 6.6.2
 *
 * License URI:  https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * License:      GPL v2 or later
 * Text Domain: panelhelper
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Appalify
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load plugin class files.
require_once 'includes/class-panelhelper.php';
require_once 'includes/class-panelhelper-settings.php';

// Load plugin libraries.
require_once 'includes/lib/class-panelhelper-admin-api.php';
require_once 'includes/lib/class-panelhelper-post-type.php';
require_once 'includes/lib/class-panelhelper-taxonomy.php';

function panelhelper_update_database() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'panelhelper_api_servers';

    // Check if the 'BALANCE' column exists
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'BALANCE'");
    
    if (empty($column_exists)) {
        // The 'BALANCE' column does not exist, so we need to add it
        $sql = "ALTER TABLE $table_name ADD BALANCE DOUBLE";
        $wpdb->query($sql);
    }
}
function panelhelper_plugin_update() {
    $pnh_db_update = get_option('ph_db_update_check4');
    
    if ($pnh_db_update != 'done'){
    panelhelper_update_database();
    panelhelper_update_database_mainserver();
    update_option('ph_db_update_check4', 'done');
    
    }
}


function panelhelper_update_database_mainserver() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'panelhelper_api_servers';

    // Check if the 'BALANCE' column exists
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'MAIN'");
    
    if (empty($column_exists)) {
        // The 'BALANCE' column does not exist, so we need to add it
        $sql = "ALTER TABLE $table_name ADD MAIN INT(1) DEFAULT 0";
        $wpdb->query($sql);
    }
}

add_action( 'plugins_loaded', 'panelhelper_plugin_update' );

/**
 * Returns the main instance of panelhelper to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object panelhelper
 */
function panelhelper() {
	$instance = panelhelper::instance( __FILE__, '2.1.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = panelhelper_Settings::instance( $instance );
	}

	return $instance;
}

panelhelper();
