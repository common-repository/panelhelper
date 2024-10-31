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
class panelhelper_api_services{
    public function save_meta_boxes() {
	}

    public function __construct() {
        
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 1 );

	}
    
    
   //creates a table that is related to the servers table 
    function panelhelper_create_api_services_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}panelhelper_api_services (
            ID int NOT NULL AUTO_INCREMENT,
            SERVICE_ID int NOT NULL,
            SERVICE_NAME varchar(255) NOT NULL,
            MIN_ORDER int NOT NULL,
            MAX_ORDER int NOT NULL,
            PRICE double NOT NULL,
            API_KEY varchar(255) NOT NULL,
            PRIMARY KEY (ID),
            CONSTRAINT fk_api_key FOREIGN KEY (API_KEY) REFERENCES {$wpdb->prefix}panelhelper_api_servers (API_KEY)
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

//displays all available services with the option to edit or delete them
    function panelhelper_api_services_table_page() {
        // Add nonce to the URL
        $default_num_results = 10;
        
        $num_results = isset($_GET['num_results']) && wp_verify_nonce( sanitize_text_field( wp_unslash (  $_GET['nonce'])), 'num_results_nonce' ) ? sanitize_text_field( wp_unslash( $_GET['num_results'] ) ) : '';
        $num_results = max($num_results, $default_num_results);

        $tab_link = add_query_arg( 'tab', 'addservice' );
        $import_all_services = add_query_arg( 'tab', 'importservices' );
        $tab_reload = add_query_arg( 'tab', 'Services' );
        global $wpdb;
        $table_name = $wpdb->prefix . 'panelhelper_api_services';
        $selected_server = isset($_GET['serverch']) && wp_verify_nonce( sanitize_text_field( wp_unslash (  $_GET['nonce'])), 'serverch_nonce' ) ? sanitize_text_field( wp_unslash($_GET['serverch'])) : '';


        $all_servers_selected = ($selected_server === '');
        if ($all_servers_selected) {
            // Placeholder selected, select all services
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}panelhelper_api_services LIMIT %d;",
            $num_results));
        } else {
            // Specific server selected, filter services by API key
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}panelhelper_api_services WHERE API_KEY = %s LIMIT %d;",
            $selected_server, $num_results));
        }
    
        
        ?>
        <br><titletagph class="panelhelper-title">Add Services</titletagph><br>

        <a href="<?php echo esc_url( $tab_link ); ?>" class="button-link-ph">Add Service</a>
        <a href="<?php echo esc_url( $import_all_services ); ?>" class="button-link-ph">Import All Services from Server</a>

        
        <select id="serverch" name="serverch" required>
        <option value="" <?php echo esc_attr($all_servers_selected ? 'selected' : ''); ?>>All Servers</option>
        <?php
        global $wpdb;
        $rows2 = $wpdb->get_results($wpdb->prepare("SELECT API_URL, API_KEY FROM {$wpdb->prefix}panelhelper_api_servers LIMIT %d;",
        $num_results));

        if ($rows2) {
            foreach ($rows2 as $row2) {
                $selected = ($selected_server == $row2->API_KEY) ? 'selected' : '';
                echo "<option value='" . esc_attr($row2->API_KEY) . "' " . esc_attr($selected) . ">" . esc_html($row2->API_URL) . "</option>";
            }
        }
        ?>
    </select>
    


    <script>
        document.getElementById('serverch').addEventListener('change', function() {
            var selectedServer = this.value;
    <?php $nonce = wp_create_nonce('serverch_nonce'); ?>
    var nonce = <?php echo wp_json_encode($nonce); ?>;

        var currentUrl = window.location.href;
        var url = new URL(currentUrl);
        url.searchParams.set('serverch', encodeURIComponent(selectedServer ));
        url.searchParams.set('nonce', nonce);
        window.location.href = url.toString();


        });
    </script>

    
<?php
// Assuming $rows is an array of service objects obtained from your database query

// Initialize the search term
$search_term = '';

// Handle the clear search button
if (isset($_POST['clear_search'])) {
    $search_term = '';
} else {
    // Check if a search term is set and filter the $rows array
    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';

    if ($search_term) {
        $rows = array_filter($rows, function($row) use ($search_term) {
            return stripos($row->SERVICE_NAME, $search_term) !== false || stripos($row->SERVICE_ID, $search_term) !== false;
        });
    }
}
?>
    <form class="" action="" method="post" autocomplete="off">
            </form>
    <form method="post" action="" style="margin-bottom: 20px;">
         <input type="text" name="search_term" value="<?php echo esc_attr($search_term); ?>" placeholder="Search services or ID">
         <button type="submit" class="button-link-ph">Search</button>
         <button type="submit" name="clear_search" class="button-link-ph">Clear</button>
    </form><br>


      
        
    <table class="panelhelper-table">
    <tr>
        <th>Service ID</th>
        <th>Service Name</th>
        <th>Min Order</th>
        <th>Max Order</th>
        <th>Price</th>
        <th>Actions</th>
    </tr>
        <?php foreach ($rows as $row) : ?>
            <tr>
                
            <td><?php echo esc_html($row->SERVICE_ID); ?></td>
            <td><?php echo esc_html($row->SERVICE_NAME); ?></td>        
            <td><?php echo esc_html($row->MIN_ORDER); ?></td>
            <td><?php echo esc_html($row->MAX_ORDER); ?></td>
            <td><?php echo esc_html(rtrim($row->PRICE, '0')); ?></td>
                <td>
                <form class="" action="" method="post" autocomplete="off">
            </form>
		<form method="post">
			<input type="hidden" id="id" name="id" value="<?php echo esc_html($row->ID); ?>">
			<button type="submit" name="delete_button" class="button">Delete</button>
            <?php  wp_nonce_field( 'delete_button_nonce', 'delete_button' ); ?>
		</form>
        <?php
                $edit_tab_link = add_query_arg('tab', 'editservice');
                $edit_service_link = add_query_arg('id', $row->ID, $edit_tab_link);
                ?>
                <a href="<?php echo esc_url($edit_service_link); ?>" class="button">Edit</a>
                </td>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
   
<br>
    <select id="num_results" name="num_results" required>
    <option value="10" <?php echo esc_html($num_results) == '10' ? 'selected' : ''; ?>>10</option>
    <option value="20" <?php echo esc_html($num_results) == '20' ? 'selected' : ''; ?>>20</option>
    <option value="30" <?php echo esc_html($num_results) == '30' ? 'selected' : ''; ?>>30</option>
    <option value="100" <?php echo esc_html($num_results) == '100' ? 'selected' : ''; ?>>100</option>
    <option value="1000" <?php echo esc_html($num_results) == '1000' ? 'selected' : ''; ?>>1 000</option>
    <option value="5000" <?php echo esc_html($num_results) == '5000' ? 'selected' : ''; ?>>5 000 (slow)</option>
    <option value="10000" <?php echo esc_html($num_results) == '10000' ? 'selected' : ''; ?>>10 000 (slow)</option>
</select>

<script>
    document.getElementById('num_results').addEventListener('change', function() {
        var selectedOrder = this.value;
        <?php $nonce = wp_create_nonce('num_results_nonce'); ?>
        var nonce = <?php echo wp_json_encode($nonce); ?>;
        var currentUrl = window.location.href;
        var url = new URL(currentUrl);
        url.searchParams.set('num_results', encodeURIComponent(selectedOrder));
        url.searchParams.set('nonce', nonce);
        window.location.href = url.toString();

    });

    // Set the initial value of num_results if it exists in the URL
    var urlParams = new URLSearchParams(window.location.search);
    var numResultsParam = urlParams.get('num_results');
    if (numResultsParam !== null) {
        document.getElementById('num_results').value = numResultsParam;
    } else {
        // If num_results is not found in the URL, set it to the default value (10 in this case)
        document.getElementById('num_results').value = '10';
    }
    
</script>
    
    <?php
if (isset($_POST['delete_button']) && wp_verify_nonce( sanitize_text_field( wp_unslash (  $_POST['delete_button'])), 'delete_button_nonce' ) ) { //wp_verify_nonce
    $id = sanitize_text_field($_POST['id']);
    $this->panelhelper_handle_server_deletion($id);

    // Reload the page after form submission
    exit;
} 
	}


	//deletes servers if it is triggered through the main page
    function panelhelper_handle_server_deletion($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'panelhelper_api_services';
    
        $id = sanitize_text_field($id);
    
        // Perform deletion
        $wpdb->query(
            $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}panelhelper_api_services WHERE
            (ID)
            = ( %d)",
            $id
            )
            );
    
        // You can echo a message or do something else if needed
        echo "<script>window.location.href = '" . esc_url($tab_link) . "';</script>";
    
        // Always exit to avoid further execution
        exit();
    }




    //add new servers database function
	function panelhelper_insert_values_into_custom_table($SID, $SNAME, $MAXO, $MINO, $PRICE, $SERVER) {
		global $wpdb;
		
		// Table name
		$table_name = $wpdb->prefix . 'panelhelper_api_services';
	
		// Insert data into the table
		$wpdb->insert(
			"{$wpdb->prefix}panelhelper_api_services",
			array(
				'SERVICE_ID' => $SID,
				'SERVICE_NAME' => $SNAME,
                'MIN_ORDER' => $MINO,
                'MAX_ORDER' => $MAXO,
                'PRICE' => $PRICE,
                'API_KEY' => $SERVER,
			),
			array(
				'%d', // NAME is a string
				'%s', // API_KEY is an integer
                '%d',
                '%d',
                '%f',
                '%s',
			)
		);
	}
    
    //add new services page
    function panelhelper_api_service_add() {
        $tab_link = add_query_arg( 'tab', 'Services' );
        global $wpdb;
        $row = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}panelhelper_api_services" );
        ?> 

        <a href="<?php echo esc_url( $tab_link ); ?>" class="button-link-ph">Back</a>
        
            <form class="" action="" method="post" autocomplete="off">
            </form>
		<form class="wrap-add" method="post">
        <h1>Add Service</h1><br>
            <div class="wrap-items">
			<label for="SID">Service ID</label> <br>
			<input type="text" id="SID" name="SID" required><br><br>
            <label for="SNAME">Service Name</label> <br>
			<input type="text" id="SNAME" name="SNAME" required><br><br>
            <label for="MAXO">Max Order</label> <br>
			<input type="text" id="MAXO" name="MAXO" required><br><br>
            <label for="MINO">Min Order</label> <br>
			<input type="text" id="MINO" name="MINO" required><br><br>
            <label for="PRICE">Price</label> <br>
			<input type="text" id="PRICE" name="PRICE" placeholder="Example: 0.1"required><br><br>
            <label for="server">Server</label><br>
            <select id="server" name="server" required>
                    <option value="" selected disabled>Choose a Server</option>
                    <?php
                    		$response = get_option('check_if_panelhelper_active');
                            $api_active = get_option('panelhelper_api_active');
                            //prem pages
                            if(trim($response) == "true" && $api_active == 'true'){
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'panelhelper_api_servers';
                    $servers = $wpdb->get_results("SELECT API_URL, API_KEY FROM {$wpdb->prefix}panelhelper_api_servers");
    
                    if ($servers) {
                        foreach ($servers as $server) {
                            $selected = ($server->API_KEY == $row->API_KEY) ? 'selected' : '';
                            echo "<option value='" . esc_attr($server->API_KEY) . "' " . esc_attr($selected) . ">" . esc_html($server->API_URL) . "</option>";
                        }
                    }
                } else{
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'panelhelper_api_servers';
                    $servers = $wpdb->get_results("SELECT API_URL, API_KEY FROM {$wpdb->prefix}panelhelper_api_servers WHERE main = 1");
    
                    if ($servers) {
                        foreach ($servers as $server) {
                            $selected = ($server->API_KEY == $row->API_KEY) ? 'selected' : '';
                            echo "<option value='" . esc_attr($server->API_KEY) . "' " . esc_attr($selected) . ">" . esc_html($server->API_URL) . "</option>";
                        }
                    }
                }
                    ?>
                </select>
			<button type="submit" class="button-submit">Submit</button>
            <?php  wp_nonce_field( 'button-submit_nonce', 'button-submit' ); ?>
            <text> You will be redirected to the services page, unless their is an error in your input.</text>
    </div>
		</form>


        
        
    <?php
		if (isset($_POST['SID']) && isset($_POST['SNAME']) && wp_verify_nonce( sanitize_text_field( wp_unslash (  $_POST['button-submit'])), 'button-submit_nonce' ) ) {
            //&& isset($_POST['MAXO']) && isset($_POST['MINO']) && isset($_POST['PRICE']) && isset($_POST['SERVER'])
            $SID = intval($_POST['SID']);
            $SNAME = sanitize_text_field($_POST['SNAME']);
            $MAXO = intval($_POST['MAXO']);
            $MINO = intval($_POST['MINO']);
            $PRICE = floatval($_POST['PRICE']);
            $SERVER = sanitize_text_field($_POST['server']);
			$this->panelhelper_insert_values_into_custom_table($SID, $SNAME, $MAXO, $MINO, $PRICE, $SERVER);

            // Reload the page after form submission
            $redirect_url = add_query_arg( array(
                'page' => 'panelhelper_settings',
                'tab' => 'Services'
            ), admin_url( 'admin.php' ) );
        
            wp_redirect($redirect_url);
            exit;
		}
	}



    

    //edit services page
    function panelhelper_api_service_edit() {
        $tab_link = add_query_arg('tab', 'Services');
        global $wpdb;
        $table_name = $wpdb->prefix . 'panelhelper_api_services';
        
        // Retrieve data for the specific ID passed
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}panelhelper_api_services WHERE ID = %d", $id));
    
        ?>


        <a href="<?php echo esc_url( $tab_link ); ?>" class="button-link-ph">Back</a>
        
            <form class="" action="" method="post" autocomplete="off">
            </form>
		<form class="wrap-add" method="post">
        <strong>Important:</strong> Only service ID and the server have to be valid for the service to work.
        <h1>Edit Service</h1><br>
            <div class="wrap-items">
                <label for="SID">Service ID</label> <br>
                <input type="text" id="SID" name="SID" value="<?php echo esc_attr($row->SERVICE_ID); ?>" required><br><br>
                <label for="SNAME">Service Name</label> <br>
                <input type="text" id="SNAME" name="SNAME" value="<?php echo esc_attr($row->SERVICE_NAME); ?>" required><br><br>
                <label for="MAXO">Max Order</label> <br>
                <input type="text" id="MAXO" name="MAXO" value="<?php echo esc_attr($row->MAX_ORDER); ?>" required><br><br>
                <label for="MINO">Min Order</label> <br>
                <input type="text" id="MINO" name="MINO" value="<?php echo esc_attr($row->MIN_ORDER); ?>" required><br><br>
                <label for="PRICE">Price</label> <br>
                <input type="text" id="PRICE" name="PRICE" value="<?php echo esc_attr(rtrim($row->PRICE, '0')); ?>" placeholder="Example: 0.1" required><br><br>
                <label for="server">Server</label><br>
                <select id="server" name="server" required>
                    <option value="" selected disabled>Choose a Server</option>
                    <?php
                    		$response = get_option('check_if_panelhelper_active');
                            $api_active = get_option('panelhelper_api_active');
                            //prem pages
                            if(trim($response) == "true" && $api_active == 'true'){
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'panelhelper_api_servers';
                    $servers = $wpdb->get_results("SELECT API_URL, API_KEY FROM {$wpdb->prefix}panelhelper_api_servers");
    
                    if ($servers) {
                        foreach ($servers as $server) {
                            $selected = ($server->API_KEY == $row->API_KEY) ? 'selected' : '';
                            echo "<option value='" . esc_attr($server->API_KEY) . "' " . esc_attr($selected) . ">" . esc_html($server->API_URL) . "</option>";
                        }
                    }
                } else{
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'panelhelper_api_servers';
                    $servers = $wpdb->get_results("SELECT API_URL, API_KEY FROM {$wpdb->prefix}panelhelper_api_servers WHERE main = 1");
    
                    if ($servers) {
                        foreach ($servers as $server) {
                            $selected = ($server->API_KEY == $row->API_KEY) ? 'selected' : '';
                            echo "<option value='" . esc_attr($server->API_KEY) . "' " . esc_attr($selected) . ">" . esc_html($server->API_URL) . "</option>";
                        }
                    }
                }
                    ?>
                </select>
                <form method="post">
    <button type="submit" class="button-submit" name="submit_button">Edit</button>
    <?php  wp_nonce_field( 'button-submit_nonce', 'button-submit' ); ?>
    <text> You will be redirected to the services page, unless there is an error in your input.</text>
</form>
            
    </div>
		</form>


        
        
        <?php
if (isset($_POST['submit_button']) && wp_verify_nonce( sanitize_text_field( wp_unslash (  $_POST['button-submit'])), 'button-submit_nonce' )) {
    $SID = intval($_POST['SID']);
    $SNAME = sanitize_text_field($_POST['SNAME']);
    $MAXO = intval($_POST['MAXO']);
    $MINO = intval($_POST['MINO']);
    $PRICE = floatval($_POST['PRICE']);
    $SERVER = sanitize_text_field($_POST['server']);
    $this->panelhelper_handle_service_edit($id, $SID, $SNAME, $MAXO, $MINO, $PRICE, $SERVER);
    exit;
}

        }
    
    
        //edit function for sql
        function panelhelper_handle_service_edit($id, $SID, $SNAME, $MAXO, $MINO, $PRICE, $SERVER){
            $tab_link = add_query_arg('tab', 'Services');
            global $wpdb;
            $table_name = $wpdb->prefix . 'panelhelper_api_services';
        
            // No need to reassign values
        
            // Perform update
            $wpdb->update(
                "{$wpdb->prefix}panelhelper_api_services",
                array(
                    'SERVICE_ID' => $SID,
                    'SERVICE_NAME' => $SNAME,
                    'MAX_ORDER' => $MAXO,
                    'MIN_ORDER' => $MINO,
                    'PRICE' => $PRICE,
                    'API_KEY' => $SERVER,
                ),
                array('ID' => $id),
                array('%d', '%s', '%d', '%d', '%f', '%s'), // Change '%d' to '%s' for 'API_KEY'
                array('%d')
            );
            $redirect_url = add_query_arg( array(
                'page' => 'panelhelper_settings',
                'tab' => 'Services'
            ), admin_url( 'admin.php' ) );
        
            wp_redirect($redirect_url);
            exit;
            exit();
        }


    
        function panelhelper_api_service_import() {
            $tab_link = add_query_arg( 'tab', 'Services' );
            global $wpdb;
            $rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}panelhelper_api_services" );
            ?> 
    
            <a href="<?php echo esc_url( $tab_link ); ?>" class="button-link-ph">Back</a>
            <?php
             $response = get_option('check_if_panelhelper_active');
             $api_active = get_option('panelhelper_api_active');
            if (trim($response) == "false" || $api_active == 'false') {
                echo '<div class="notice notice-warning is-dismissible"><p>There is no premium plan connected to your account. Please get premium to use this service.</p></div>';

            }
                ?> 
                <form class="" action="" method="post" autocomplete="off">
                </form>
            <form class="wrap-add" method="post">
            <h1>Import all Services</h1><br>
                <div class="wrap-items">
               
                <label for="server">Server</label><br>
    <select id="server" name="server" required>
    <option value="" selected disabled>Choose a Server</option>
        <?php
        global $wpdb;
        $rows = $wpdb->get_results("SELECT API_URL, API_KEY FROM {$wpdb->prefix}panelhelper_api_servers");
    
        if ($rows) {
            foreach ($rows as $row) {
                echo "<option value='" . esc_attr($row->API_KEY) . "'>" . esc_html($row->API_URL) . "</option>";
            }
        }
        ?>
    </select>
                <button type="submit" class="button-submit">Import All</button>
                <?php  wp_nonce_field( 'button-submit_nonce', 'button-submit' ); ?>
                <text> You will be redirected to the services page, unless their is an error in your input.</text>
        </div>
            </form>
    
    
            
            
        <?php
            if (isset($_POST['server']) && wp_verify_nonce( sanitize_text_field( wp_unslash (  $_POST['button-submit'])), 'button-submit_nonce' ) ) {

                $SERVER = sanitize_text_field($_POST['server']);
                $API_URL = $wpdb->get_var($wpdb->prepare("SELECT API_URL FROM {$wpdb->prefix}panelhelper_api_servers WHERE API_KEY = %s", $SERVER));


                $response = get_option('check_if_panelhelper_active');
                $api_active = get_option('panelhelper_api_active');

                if (trim($response) == "true" && $api_active == 'true') {
                    $api_activate = new panelhelper_api_activator();
                    $api_activate->panelhelper_import_all($SERVER, $API_URL);
                } 
                // Reload the page after form submission
                $redirect_url = add_query_arg( array(
                    'page' => 'panelhelper_settings',
                    'tab' => 'Services'
                ), admin_url( 'admin.php' ) );
            
                wp_redirect($redirect_url);
                exit;
            }
        }



   




    

}

