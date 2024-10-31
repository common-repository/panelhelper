<?php
/**
 * Settings class file.
 *
 * @package WordPress Plugin Template/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * This class is used to add, edit, and display all services.
 */
class panelhelper_api_servers{
        public function save_meta_boxes() {
        }
    
        public function __construct() {
            
            add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 1 );
    
        }
        
        
        //creates a table if it doesnt exist
        function panelhelper_create_custom_table() {
            $id =1;
            
            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}panelhelper_api_servers (
                ID int NOT NULL AUTO_INCREMENT,
                API_URL varchar(255) NOT NULL,
                API_KEY varchar(255) NOT NULL,
                BALANCE double,
                MAIN INT(1) DEFAULT 0,
                PRIMARY KEY (id),
                UNIQUE (API_KEY)
            ) $charset_collate;";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }
    
    
    

    
        function maskApiKey($apiKey) {
            if(strlen($apiKey) >= 4){
    
                // Get the first 4 characters of the API Key
                $firstFour = substr($apiKey, 0, 4);
                // Calculate the length of the remaining characters
                $remainingLength = strlen($apiKey) - 4;
                // Create a string of asterisks with the same length as the remaining characters
                $masked = str_repeat('*', $remainingLength);
                // Concatenate the first 4 characters with the masked string
                return $firstFour . $masked;
            } else {
                return $apiKey; // Return the original key if it's 5 characters or less
            }
        }
    

    
        //server add form page
        function panelhelper_api_server_add_old() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'panelhelper_api_servers';
            
            // Check if there's already a server marked as `main = 1`
            $main_server = $wpdb->get_row("SELECT * FROM {$table_name} WHERE main = 1");
        
            // Set variables to populate the form fields if a main server exists
            $url_value = '';
            $api_key_value = '';
            
            if ($main_server) {
                // If main server exists, populate form fields with existing data
                $url_value = esc_attr($main_server->url);
                $api_key_value = esc_attr($main_server->api_key);
            }
        
            // Display the form
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>Important:</strong> Free Users can only use one Server. Please update to the <strong>premium</strong> version if you would like to use more servers.
                <br>Your old services will still run on other panels, however you cannot edit them.</p>
            </div>
            <form class="" action="" method="post" autocomplete="off">
            </form>
            <form class="wrap-add" method="post">
                <h1>Main Server</h1><br>
                <div class="wrap-items">
                    <label for="url">API URL</label> <br>
                    <input type="text" id="url" name="url" value="<?php echo $url_value; ?>" required><br><br>
                    <label for="api_key">API KEY</label><br>
                    <input type="text" id="api_key" name="api_key" value="<?php echo $api_key_value; ?>" required><br><br>
                    <button type="submit" class="button-submit">Save</button>
                    <?php wp_nonce_field( 'button-submit_nonce', 'button-submit' ); ?>
                </div>
            </form>
            <?php
        
            // Check if form is submitted
            if (isset($_POST['url']) && isset($_POST['api_key']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['button-submit'])), 'button-submit_nonce')) {
                $url = sanitize_text_field($_POST['url']);
                $api_key = sanitize_text_field($_POST['api_key']);
        
                if ($main_server) {
                    // If a main server already exists, update it
                    $wpdb->update(
                        $table_name,
                        array(
                            'url' => $url,
                            'api_key' => $api_key
                        ),
                        array('main' => 1)
                    );
                } else {
                    // No main server, insert a new one with `main = 1`
                    $wpdb->insert(
                        $table_name,
                        array(
                            'url' => $url,
                            'api_key' => $api_key,
                            'main' => 1
                        )
                    );
                }
        
                // Reload the page after form submission
                $redirect_url = add_query_arg(array(
                    'page' => 'panelhelper_settings',
                    'tab' => 'Servers'
                ), admin_url('admin.php'));
                
                wp_redirect($redirect_url);
                exit;
            }
        }


        
        function panelhelper_api_server_add() {
            $tab_link = add_query_arg('tab', 'Services');
            global $wpdb;
            $table_name = $wpdb->prefix . 'panelhelper_api_servers';
            
            // Retrieve data for the specific ID passed
            $main_server = $wpdb->get_row("SELECT * FROM {$table_name} WHERE main = 1");
        
            ?>
    <div class="notice notice-error is-dismissible">
                <p><strong>Important:</strong>If you are experiencing errors, please request a new API Key or contact support. Free Users can only use one Server. Please update to the <strong>premium</strong> version if you would like to use more servers.
                <br>Your old services will still run on other panels, however you cannot edit them.</p>
            </div>
    
            
                <form class="" action="" method="post" autocomplete="off">
                </form>
            <form class="wrap-add" method="post">

            <h1>Main Server</h1><br>
<div class="wrap-items">
    <form method="post">
        <label for="APINAME">API URL</label> <br>
        <input type="text" id="APINAME" name="APINAME" value="<?php echo esc_attr($main_server->API_URL); ?>" required><br><br>

        <label for="APIKEYNAME">Key</label> <br>
        <input type="text" id="APIKEYNAME" name="APIKEYNAME" value="<?php echo esc_attr($main_server->API_KEY); ?>" required><br><br>
        
        <button type="submit" class="button-submit" name="submit_button">Save</button>
        <?php wp_nonce_field('button-submit_nonce', 'button-submit'); ?>
    </form>
</div>

<?php

if (isset($_POST['submit_button']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['button-submit'])), 'button-submit_nonce')) {
    $APIURL = sanitize_text_field($_POST['APINAME']);
    $APIKEY = sanitize_text_field($_POST['APIKEYNAME']);


    if ($main_server) {
        $this->panelhelper_handle_server_edit($APIURL, $APIKEY);
    } else {
        $this->panelhelper_handle_server_add_main($APIURL, $APIKEY);
    }
    exit;
}
    
            }
        
        
            //edit function for sql
            function panelhelper_handle_server_edit($APIURL, $APIKEY) {
                global $wpdb;
                $wpdb->query('SET FOREIGN_KEY_CHECKS=0');

                // Update the primary table (panelhelper_api_servers)
                $table_name = $wpdb->prefix . 'panelhelper_api_servers';
                
                $old_api_key = $wpdb->get_var($wpdb->prepare(
                    "SELECT API_KEY FROM $table_name WHERE MAIN = %d",
                    1
                ));

                $wpdb->update(
                    $table_name,
                    array(
                        'API_URL' => $APIURL,
                        'API_KEY' => $APIKEY,
                        'MAIN' => 1,
                    ),
                    array('MAIN' => 1),
                    array('%s', '%s', '%d'),
                    array('%d')
                );
            
                // If there are related tables that rely on API_KEY as a foreign key
                $related_table_name = $wpdb->prefix . 'panelhelper_api_services';  // Replace with the actual related table
                $wpdb->update(
                    $related_table_name,
                    array('API_KEY' => $APIKEY),  // Set new API_KEY
                    array('API_KEY' =>  $old_api_key),  // Update where old API_KEY is found
                    array('%s'),
                    array('%s')
                );
                $wpdb->query('SET FOREIGN_KEY_CHECKS=1');
                // Redirect after the update
                $redirect_url = add_query_arg(array(
                    'page' => 'panelhelper_settings',
                    'tab' => 'Servers',
                ), admin_url('admin.php'));
                
                wp_redirect($redirect_url);
                exit;
            }

    function panelhelper_handle_server_add_main($APIURL, $APIKEY){
        $tab_link = add_query_arg('tab', 'Server');
        global $wpdb;
        $table_name = $wpdb->prefix . 'panelhelper_api_servers';
    
        // No need to reassign values
    
        // Perform update
        $wpdb->insert(
            "{$wpdb->prefix}panelhelper_api_servers",
            array(
                'API_URL' => $APIURL,
                'API_KEY' => $APIKEY,
                'MAIN' => 1,
            ),
            
            array('%s', '%s', '%d')
            
        );
        $redirect_url = add_query_arg( array(
            'page' => 'panelhelper_settings',
            'tab' => 'Servers'
        ), admin_url( 'admin.php' ) );
    
        wp_redirect($redirect_url);
        exit;
        exit();

    }
       
    
    
    
    
        
    
    }