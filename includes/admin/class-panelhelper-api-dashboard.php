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
 * Settings class.
 */
class panelhelper_api_dashboard{
    public function save_meta_boxes() {
	}

    public function __construct() {
        
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 1 );

	}


    function dash_comp(){
        $html = '';
        $html .= '<div id="parentboxph">';
        $html .= $this->panelhelper_api_sales();
        $html .= $this->panelhelper_api_spend();
        $html .= '</div>';
        $html .= $this->panelhelper_api_failed_orders();
        $html .= $this->panelhelper_api_server_balance_field();
        return $html;
    }

    function panelhelper_api_failed_orders() {

    
            echo '<table class="panelhelper-table-dashboard">';
            echo '<tr><th>WC Order Number</th><th>Message</th><th>Time</th></tr>';
        
           
            
                // No failed orders, display message
            echo '<tr><td colspan="3">Please upgrade to premium in "Settings" to view this info.</td></tr>';
            
            
            
        
            // Display link to view more orders
            echo '<phtitle2 style="font-size: 20px; margin-right: 10px;">Recently Failed Orders</phtitle2>';
            $tab_link = add_query_arg('tab', 'Orders');
            echo '<a href="' . esc_url($tab_link) . '" class="button-link-ph" style="display: block; margin-top: 10px;">View More</a>';
            echo '</table>';
        }
    
    

    function panelhelper_api_server_balance_field() {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}panelhelper_api_servers");
        ?>
    
        <phtitle2 style="font-size: 20px; margin-right: 10px;">Server Balance</phtitle2>
    
        <table class="panelhelper-table-dashboard">
            <tr>
                <th>API Server</th>
                <th>Balance</th>
            </tr>
            <?php
            // Create an array to store servers with balances
            $servers_with_balances = array();
    
            foreach ($results as $row) {
                $api_key = $row->API_KEY;
                $api_url = $row->API_URL;
    
                // Fetch status using API
                if (strpos($api_url, 'http') === 0) {
                $api = new panelhelper_api_adder(); // Replace YourAPI with the actual class name or API client
                $balance = $api->api_balance($api_key, $api_url); // Assuming order_id exists
    
                // Check if balance is not empty
                if (!empty($balance->balance)) {
                    // Store server details along with balance
                    $servers_with_balances[] = array(
                        'url' => $api_url,
                        'balance' => $balance->balance,
                        'currency' => $balance->currency
                    );
                }
                }
        }
            // If no servers have balances, display message in a row
            if (empty($servers_with_balances)) {
                echo '<tr><td colspan="2">No active servers</td></tr>';
            } else {
                // Sort the servers based on balance (descending order)
                usort($servers_with_balances, function ($a, $b) {
                    return $b['balance'] <=> $a['balance'];
                });
    
                // Display only the top 3 servers with balances
                $count = 0;
                foreach ($servers_with_balances as $server) {
                    if ($count >= 3) {
                        break; // Exit loop if 3 rows displayed
                    }
                
                    // Display row
                    echo '<tr>';
                    $url = $server['url'];
    
                    // Check if the URL starts with "http"
                    if (strpos($url, 'http') === 0) {
                        // Remove everything until "//"
                        $url = substr($url, strpos($url, '//') + 2);
    
                        // Remove everything after the last "."
                        $url = substr($url, 0, strrpos($url, '.'));
    
                        // Display the modified URL
                        echo '<td>' . esc_html($url) . '</td>';
                    } else {
                        // If URL doesn't start with "http", display it as is
                        echo '<td>' . esc_html($server['url']) . '</td>';
                    }
                    echo '<td>' . esc_html($server['balance']) . esc_html($server['currency']) .'</td>';
                    echo '</tr>';
                    $count++; // Increment count
                }
            }

        $tab_link = add_query_arg('tab', 'Servers');
        echo '<a href="' . esc_url($tab_link) . '" class="button-link-ph" style="display: block; margin-top: 10px;">View More</a>';
        echo '</table>';
    }
    







    function panelhelper_api_sales(){
        $seven_days_ago = date('Y-m-d', strtotime('-7 days'));
        $args = array(
            'meta_query' => array(
                array(
                    'key' => 'panelhelper',
                    'value' => true,
                    'compare' => '=',
                ),
            ),
                'date_query' => array(
                    array(
                        'after' => $seven_days_ago,
                        'inclusive' => true,
                    ),
            ),
            'post_type' => 'shop_order',
            'post_status' => 'any', // Include orders with any status
            'posts_per_page' => -1, // Get all orders
        );
        
        $orders_query = new WP_Query($args);
        
        // Count the orders
        $orders_count = $orders_query->found_posts;
            ob_start();
            ?>
            
            <title>White Box</title>
            <style>
        .ph-db-box {
            width: 280px;
            height: 75px;
            background-color: white;
            border-radius: 12px;
            padding: 20px;
            margin-right:10px;
            font-family: Arial, sans-serif;
            display: flex;
        }
    
        .ph-db-content {
            display: flex;
            flex-direction: column;
            justify-content: center; /* Apply justify-content to the content container */
            flex-grow: 1; /* Allow the content container to grow to fill available space */
        }
    
        .ph-db-medium-text {
            margin-top: 5px;
            font-size: 20px;
            font-weight: 400;
            text-align: left;
            z-index: 1; /* Ensure the text appears over the image */
            white-space: nowrap; /* Prevent text from wrapping */
        }
    
        .ph-db-large-text {
            font-size: 40px;
            font-weight: 800;
            text-align: left;
            margin-top:20px;
        }
    
        .ph-chart-image-container {
            display: flex; /* Create a flex container for the image */
            justify-content: center; /* Apply justify-content to the image container */
        }
    
        .ph-chart-image {
            width: 150px;
            margin: auto;
            display: block;
        }
    </style>
    
    <div class="ph-db-box">
        <div class="ph-db-content">
        <div class="ph-db-medium-text">Sales last 7D</div>
        <div class="ph-db-large-text"><?php echo esc_attr($orders_count); ?></div>
        </div>
        <div class="ph-chart-image-container"> <!-- Container specifically for the image -->
            <img class="ph-chart-image" src="../wp-content/plugins/panelhelper/assets/images/chart-mv-blue.png" alt="Image">
        </div>
    </div>
    
    
    
        <?php
        $html = ob_get_clean(); // Get the buffered output and clean the buffer
        return $html;
    
        }
    
    
    
    function panelhelper_api_spend(){
        global $wpdb;
    
        // Prepare SQL query to sum up API prices within the last 7 days
        $query = "
        SELECT ROUND(SUM(api_price), 2) AS total_api_price
            FROM {$wpdb->prefix}panelhelper_api_orders
            WHERE order_datetime >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ";
        
        // Execute the query
        $total_api_price = $wpdb->get_var($query);
                ob_start();
                ?>
      <title>White Box</title>
    
    
    <div class="ph-db-box">
        <div class="ph-db-content">
            <div class="ph-db-medium-text">API spend last 7D</div>
            <div class="ph-db-large-text">$<?php echo esc_attr($total_api_price); ?></div>
        </div>
        <div class="ph-chart-image-container"> <!-- Container specifically for the image -->
            <img class="ph-chart-image" src="../wp-content/plugins/panelhelper/assets/images/chart-mv-red.png" alt="Image">
        </div>
    </div>
    
        
        
        
        <?php
            $html = ob_get_clean(); // Get the buffered output and clean the buffer
            return $html;
        
            }
    
    








}