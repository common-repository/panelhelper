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
 * displays all orders in the admin settings.
 */
class panelhelper_api_orders{
    public function save_meta_boxes() {
	}

    public function __construct() {
        
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 1 );

	}
    
    
    //creates table if it doesnt exist (is called by the main settings doc)
    function panelhelper_create_api_orders_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}panelhelper_api_orders (
            ID int NOT NULL AUTO_INCREMENT,
            api_order_number int NOT NULL,
            quantity int NOT NULL,
            api_price double NOT NULL,
            store_price double NOT NULL,
            order_datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            woocommerce_order_number int NOT NULL,
            status varchar(100) NOT NULL DEFAULT 'PENDING',
            remains varchar(100),
            start_count varchar(100),
            services_database_id int NOT NULL,
            currency varchar(100),
            PRIMARY KEY (ID),
            FOREIGN KEY (services_database_id) REFERENCES {$wpdb->prefix}panelhelper_api_services (ID)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    //diplays the page
    function panelhelper_api_orders_table_page() {
        global $wpdb;
        
        $status= 'done';
        $tab_reload = add_query_arg( 'tab', 'Orders' );
        // Default number of recent results

        $default_num_results = 10;
        
        $num_results = isset($_GET['num_results']) && wp_verify_nonce( sanitize_text_field( wp_unslash (  $_GET['nonce'])), 'num_results_nonce' ) ? sanitize_text_field( wp_unslash( $_GET['num_results'] ) ) : '';
        $num_results = max($num_results, $default_num_results);

        
        // Check if a different number of results is requested


        // Adjust the query to limit the number of results based on the parameter
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
            IF(o.api_order_number = 0, 'ERROR', CONCAT(o.api_order_number, '(', s.API_URL, ')')) AS `api_number_server_name`,
            CONCAT(i.SERVICE_ID, ' - ', i.service_name) AS `product_name`,
            o.quantity,
            o.api_price AS `api_price`,
            CAST(o.store_price AS DECIMAL) AS `store_price`,
            o.ID AS order_id,
            o.api_order_number,
            i.api_key,
            s.API_URL,
            o.woocommerce_order_number,
            o.status,
            o.start_count,
            o.remains
        FROM 
        {$wpdb->prefix}panelhelper_api_orders o
        JOIN 
        {$wpdb->prefix}panelhelper_api_services i ON o.services_database_id = i.ID
        JOIN 
        {$wpdb->prefix}panelhelper_api_servers s ON i.api_key = s.API_KEY
        ORDER BY 
            order_datetime DESC
        LIMIT %d;",
        $num_results
        ));
        

        
        
        if ($results) {
            echo '<br><titletagph class="panelhelper-title">Orders</titletagph><br><br>';
            echo '<table class="panelhelper-table">';
            echo '<tr><th>WC Order Number</th><th>API Order Number(Server Name)</th><th>Product</th><th>Quantity</th><th>API Price</th><th>Store Price</th><th>Profit</th><th>Status</th></tr>';
            foreach ($results as $row) {
    
                $api_key = $row->api_key;
                $api_url = $row->API_URL;
                $order_id = $row->api_order_number;
                // Fetch status using API
                $profit = $row->store_price - $row->api_price;

                $panelhelper_serverstatus = $row->api_number_server_name;

                if($order_id == 0){
                    if($row->quantity == 0){
                        $panelhelper_serverstatus = "ERROR: create an attribute named 'Quantity' in product settings";
                    } else if($row->status == "Incorrect request"){
                        $panelhelper_serverstatus = "ERROR: incorrect service or duplicate order";
                    } else{
                        $panelhelper_serverstatus = "ERROR: incorrect service or duplicate order";
                    }
                }
    
                echo '<tr>';
                $site_url = get_site_url(); // Get the base URL of the site
                $order_edit_url = $site_url . '/wp-admin/post.php?post=' . esc_html($row->woocommerce_order_number) . '&action=edit';
                echo '<td><a href="' . esc_url($order_edit_url) . '">' . esc_html($row->woocommerce_order_number) . '</a></td>';
    
                echo '<td>' . esc_html($panelhelper_serverstatus) . '</td>';
                echo '<td>' . esc_html($row->product_name) . '</td>';
                echo '<td>' . esc_html($row->quantity) . '</td>';
                echo '<td>' . esc_html(rtrim($row->api_price, '0')) . '</td>';
                echo '<td>' . esc_html($row->store_price) . '</td>';
                echo '<td>' . esc_html(rtrim($profit, '0')) . '</td>';
                echo '<td>Status: GET PREMIUM <br> Start Count: 0<br> Remains: 0</td>';
                echo '</tr>';
    
                
            }
            echo '</table>';
        } else {
            echo 'No Order yet.';
        }
        ?>
        
        <select id="num_results" name="num_results" required>
    <option value="10" <?php echo esc_html($num_results) == '10' ? 'selected' : ''; ?>>10</option>
    <option value="20" <?php echo esc_html($num_results) == '20' ? 'selected' : ''; ?>>20</option>
    <option value="30" <?php echo esc_html($num_results) == '30' ? 'selected' : ''; ?>>30</option>
    <option value="100" <?php echo esc_html($num_results) == '100' ? 'selected' : ''; ?>>100</option>
    <option value="1000" <?php echo esc_html($num_results) == '1000' ? 'selected' : ''; ?>>1000</option>
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
    }
    


    
        
	



}
