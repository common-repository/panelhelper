<?php
/**
 * Settings class file.
 *
 * @package WordPress Plugin Template/Settings
 */


 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//include 'admin/class-panelhelper-api-servers.php';
//include 'admin/class-panelhelper-api-services.php';
//include 'admin/class-panelhelper-api-functions.php';
//include 'admin/class-panelhelper-api-orders.php';

function panelhelper_include_files_woo() {
	
	// Get legend dir path.
	$plugin_dir = plugin_dir_path(__FILE__);
	$wc_file_to_include = $plugin_dir . 'integration/class-functions-hooks-integration-woocommerce.php';
	if (file_exists($wc_file_to_include)) {
		include_once $wc_file_to_include;
	}
	
	
}
add_action( 'panelhelper_include_files_late', 'panelhelper_include_files_woo' );


function panelhelper_include_other_functions(){
	$plugin_dir = plugin_dir_path(__FILE__);



	$servers_dir_ph = $plugin_dir . 'admin/class-panelhelper-api-servers.php';
	$services_dir_ph = $plugin_dir . 'admin/class-panelhelper-api-services.php';
	$functions_dir_ph = $plugin_dir . 'admin/class-panelhelper-api-functions.php';
	$orders_dir_ph = $plugin_dir . 'admin/class-panelhelper-api-orders.php';
	$dashboard_dir_ph = $plugin_dir . 'admin/class-panelhelper-api-dashboard.php';
	$supportfunc_dir_ph = $plugin_dir . 'admin/class-panelhelper-support-functions.php';
	$emails_ph = $plugin_dir . 'admin/class-panelhelper-api-emails.php';

	$premium_dash_ph = $plugin_dir . 'admin/premium/class-panelhelper-premium-dashboard.php';
	$premium_check_ph = $plugin_dir . 'admin/premium/class-panelhelper-premium-check.php';
	$premium_api_orders_ph = $plugin_dir . 'admin/premium/class-panelhelper-premium-api-orders.php';
	$premium_emails_ph = $plugin_dir . 'admin/premium/class-panelhelper-premium-email.php';
	$premium_servers_ph = $plugin_dir . 'admin/premium/class-panelhelper-premium-servers.php';
	$premium_wc_ph = $plugin_dir . 'admin/premium/class-panelhelper-premium-woocommerce-integration.php';
	
	include_once $servers_dir_ph;
	include_once $services_dir_ph;
	include_once $functions_dir_ph;
	include_once $supportfunc_dir_ph;
	include_once $emails_ph;

	//check if the plugin was activated in wordpress

	if (file_exists($premium_check_ph)) {

		update_option('panelhelper_api_active', 'true');
		include_once $premium_check_ph;


		//$ph_first_check = get_option('panehelper_firstcheck');


			$api_activate = new panelhelper_api_activator();
			$api_activate->check_if_panelhelper_active();
			$response = get_option('check_if_panelhelper_active');

		//do_action( 'qm/debug', $response );
		if(trim($response) == "true"){

		include_once $premium_dash_ph;
		include_once $premium_api_orders_ph;
		include_once $premium_emails_ph;
		include_once $premium_servers_ph;
		include_once $premium_wc_ph;
		

		}else{
		

		include_once $dashboard_dir_ph;
		include_once $orders_dir_ph;
		
		

		}
	} else{

		include_once $dashboard_dir_ph;
		include_once $orders_dir_ph;
		update_option('panelhelper_api_active', 'false');
		update_option('check_if_panelhelper_active', 'false');
	}
	
	
}
add_action( 'panelhelper_include_all_files', 'panelhelper_include_other_functions' );


//this function updates the databases periodically to always get the newest order status from the server
function panelhelper_update_order_status_periodically() {
	//create order page


	$ph_supportfunc = new panelhelper_support_functions();
	$ph_supportfunc->panelhelper_orderpage_creator();
	$ph_supportfunc->panelhelper_orderpage_shortcode();


	//$panelhelper_damo->check_if_panelhelper_active();

	global $wpdb;
	$table_name_orders = $wpdb->prefix . 'panelhelper_api_orders';
	$table_name_services = $wpdb->prefix . 'panelhelper_api_services';
	$table_name_servers = $wpdb->prefix . 'panelhelper_api_servers';

	$query = "
	SELECT 
		IF(o.api_order_number = 0, 'ERROR', CONCAT(o.api_order_number, '(', s.API_URL, ')')) AS `api_number_server_name`,
		CONCAT(i.SERVICE_ID, ' - ', i.service_name) AS `product_name`,
		o.quantity,
			o.api_price,
		CAST(o.store_price AS DECIMAL) AS `store_price`,
		o.ID AS order_id,
		o.api_order_number,
		i.api_key,
		s.API_URL,
		o.woocommerce_order_number,
			o.order_datetime,
		o.remains,
			o.status,
			o.start_count
		FROM 
			$table_name_orders o
		JOIN 
			$table_name_services i ON o.services_database_id = i.ID
		JOIN 
			$table_name_servers s ON i.api_key = s.API_KEY
	ORDER BY 
		order_datetime DESC;
	";

	$orders = $wpdb->get_results($query);
    if ($orders) {
        foreach ($orders as $order) {
			$api_key = $order->api_key;
			$api_url = $order->API_URL;
			$order_id = $order->api_order_number;
			$wc_order_id = trim($order->woocommerce_order_number);
			$current_status= $order->status;
			if($current_status != "Completed" && $current_status != "Canceled" && $current_status != "Incorrect request"){
            $api = new panelhelper_api_adder(); 
            $status = $api->api_status($api_key, $api_url, $order_id);
			//do_action( 'qm/debug', $status );

            // Update status, remains, and start count if they are different from API response
            if ($status && isset($status->remains) && isset($status->status)) {
                if ($status->remains != $order->remains || $status->status != $order->status) {
                    $wpdb->update(
                        $table_name_orders,
                        array(
                            'status' => $status->status,
                            'remains' => $status->remains,
                            'start_count' => $status->start_count,
							'api_price' =>$status->charge
                        ),
                        array('ID' => $order->order_id)
                    );
				
					$api_service_enabled = get_option('panelhelper_wc_updater');
					$response = get_option('check_if_panelhelper_active');

				if($api_service_enabled = true && $status->status =='Completed' && trim($response) == "true"){
						//do_action( 'qm/debug', var_dump($wc_order_id));


							// WooCommerce is active
							// Hook into an action that runs after WooCommerce is initialized
								panelhelper_update_woocommerce_status($wc_order_id);

                    
                }
				if($api_service_enabled = true && $status->status =='Canceled' && trim($response) == "true"){
					//do_action( 'qm/debug', var_dump($wc_order_id));

					//notify email
					$email_order_errors = get_option('panelhelper_email_order_errors');
					$response = get_option('check_if_panelhelper_active');

					if($email_order_errors == true && trim($response) == "true"){
						$panelhelperemail = new panelhelper_premium_email;
						$panelhelperemail->Panelhelper_send_email_on_failed_order($wc_order_id);
					}
						

				
				}
            }
				}
            

            // Update status to error if there is an error
            if ($status && isset($status->error)) {
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->prefix}panelhelper_api_orders 
						SET status = %s
						WHERE ID = %d",
						$status->error,
						$order->order_id
					)
				);
				//order not started email
				$email_order_notstarted = get_option('panelhelper_email_order_notstarted');
				$response = get_option('check_if_panelhelper_active');

					if($email_order_notstarted == true && trim($response) == "true"){
						$panelhelperemail = new panelhelper_premium_email;
						$panelhelperemail->Panelhelper_send_email_on_order_notstarted($wc_order_id);
					}

            }
        }
    	}
}
//send balance email
$balance_warning_email_ph = get_option('panelhelper_balance_warning');
$response = get_option('check_if_panelhelper_active');

if($balance_warning_email_ph == true && trim($response) == "true"){
	$panelhelperemail = new panelhelper_premium_email;
	$panelhelperemail->Panelhelper_balance_helper();
}

}


function panelhelper_update_woocommerce_status($orderid) {

	$updatewoocommerce = new panelhelper_api_activator;
	$updatewoocommerce->wc_order_updater($orderid);
	

}



add_filter( 'cron_schedules', 'panelhelper_add_cron_interval' );
function panelhelper_add_cron_interval( $schedules ) {
    $schedules['panelhelper_everyminute'] = array(
            'interval'  => 10, // time in seconds
            'display'   => 'Every Minute'
    );
    return $schedules;
}

if (!wp_next_scheduled('update_order_status_event')) {
	wp_schedule_event( time(), 'panelhelper_everyminute', 'update_order_status_event' );
	}
	add_action('update_order_status_event', 'panelhelper_update_order_status_periodically');

/**
 * Settings class.
 */
class panelhelper_Settings {
	

	/**
	 * The single instance of panelhelper_Settings.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_instance = null; //phpcs:ignore

	/**
	 * The main plugin object.
	 *
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 *
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();
	

	/**
	 * Constructor function.
	 *
	 * @param object $parent Parent object.
	 */
	public function __construct( $parent ) {
		$this->include_files_late();
		$this->parent = $parent;
		$this->base = 'wpt_';
		$this->panelhelper_add_all_tables();
		
		
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		add_action('panelhelperheader', array($this, 'add_plugin_banner'), 10, 1 );
		
		// Register plugin settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add settings page to menu.
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );


		// Add settings link to plugins page.
		add_filter(
			'plugin_action_links_' . plugin_basename( $this->parent->file ),
			array(
				$this,
				'add_settings_link',
			)
		);

		// Configure placement of plugin settings page. See readme for implementation.
		add_filter( $this->base . 'menu_settings', array( $this, 'configure_settings' ) );

	}
	

	public function include_files_late() {

		/**
		 * Helper hook to include files late.
		 *
		 */
		do_action( 'panelhelper_include_files_late' );
		do_action('panelhelper_include_all_files');

	}


	private function panelhelper_add_all_tables() {

		global $wpdb;
		$api_servers = new panelhelper_api_servers();
		$api_services = new panelhelper_api_services();
		$api_orders = new panelhelper_api_orders();
		//the number of tables in the db
		$number_of_tables = 3;
		


		$table_exists = $wpdb->get_var($wpdb->prepare("
		SELECT COUNT(*) 
		FROM INFORMATION_SCHEMA.TABLES 
		WHERE TABLE_SCHEMA = %s 
		AND TABLE_NAME IN (%s, %s, %s)",
		$wpdb->dbname,
		"{$wpdb->prefix}panelhelper_api_servers",
		"{$wpdb->prefix}panelhelper_api_services",
		"{$wpdb->prefix}panelhelper_api_orders"
	));
	


		if ($table_exists != $number_of_tables) {
			//do_action( 'qm/debug', 'create more tables' );
			
			$api_servers->panelhelper_create_custom_table();
			$api_services->panelhelper_create_api_services_table();
			$api_orders->panelhelper_create_api_orders_table();

		}
		

	}








	/**
	 * Initialise settings
	 *
	 * @return void
	 */
	public function init_settings() {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to admin menu
	 *
	 * @return void
	 */
	public function add_menu_item() {

		$args = $this->menu_settings();

		// Do nothing if wrong location key is set.
		if ( is_array( $args ) && isset( $args['location'] ) && function_exists( 'add_' . $args['location'] . '_page' ) ) {
			switch ( $args['location'] ) {
				case 'options':
				case 'submenu':
					$page = add_submenu_page( $args['parent_slug'], $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'] );
					break;
				case 'menu':
					$page = add_menu_page( $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'], $args['icon_url'], $args['position'] );
					break;
				default:
					return;
			}
			add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );
		}
	}



	/**
	 * Prepare default settings page arguments
	 *
	 * @return mixed|void
	 */
	private function menu_settings() {
		return apply_filters(
			$this->base . 'menu_settings',
			array(
				'location'    => 'menu', // Possible settings: options, menu, submenu.
				'parent_slug' => 'options-general.php',
				'page_title'  => __( 'Panelhelper', 'panelhelper' ),
				'menu_title'  => __( 'Panelhelper', 'panelhelper' ),
				'capability'  => 'manage_options',
				'menu_slug'   => $this->parent->_token . '_settings',
				'function'    => array( $this, 'settings_page' ),
				'icon_url'    => 'data:image/svg+xml;base64,' . base64_encode('<svg width="20" height="20" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path fill="black" d="M128 1408h1024v-128h-1024v128zm0-512h1024v-128h-1024v128zm1568 448q0-40-28-68t-68-28-68 28-28 68 28 68 68 28 68-28 28-68zm-1568-960h1024v-128h-1024v128zm1568 448q0-40-28-68t-68-28-68 28-28 68 28 68 68 28 68-28 28-68zm0-512q0-40-28-68t-68-28-68 28-28 68 28 68 68 28 68-28 28-68zm96 832v384h-1792v-384h1792zm0-512v384h-1792v-384h1792zm0-512v384h-1792v-384h1792z"/></svg>'),
                'position'    => '55',
			)
		);
	}

	/**
	 * Container for settings page arguments
	 *
	 * @param array $settings Settings array.
	 *
	 * @return array
	 */
	public function configure_settings( $settings = array() ) {
		return $settings;
	}

	/**
	 * Load settings JS & CSS
	 *
	 * @return void
	 */
	public function settings_assets() {

		// We're including the farbtastic script & styles here because they're needed for the colour picker
		// If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the wpt-admin-js script below.
		wp_enqueue_style( 'farbtastic' );
		wp_enqueue_script( 'farbtastic' );

		// We're including the WP media scripts here because they're needed for the image upload field.
		// If you're not including an image upload then you can leave this function call out.
		wp_enqueue_media();

		wp_register_script( $this->parent->_token . '-settings-js', $this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js', array( 'farbtastic', 'jquery' ), '1.0.0', true );
		wp_enqueue_script( $this->parent->_token . '-settings-js' );
	}

	/**
	 * Add settings link to plugin list table
	 *
	 * @param  array $links Existing links.
	 * @return array        Modified links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __( 'Settings', 'panelhelper' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	/**
	 * Build settings fields
	 *
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {
		

		$settings['Dashboard'] = array(
			'title'       => __( 'Dashboard', 'panelhelper' ),
		);

		$settings['Services'] = array(
			'title'       => __( 'Services', 'panelhelper' ),
		);

		$settings['addservice'] = array(                                                                    
			'title'       => __( 'Add Service', 'panelhelper' ),																																													  
		);
		$settings['editservice'] = array(                                                                                                                                                                         
                        'title'       => __( 'Edit Service', 'panelhelper' ),                                                                                                                                                                            
        );

		$settings['importservices'] = array(                                                                                                                                                                         
					'title'       => __( 'Import Services', 'panelhelper' ),                                                                                                                                                                            
		);


		$settings['Servers'] = array(
			'title'       => __( 'Servers', 'panelhelper' ),
		);
		$settings['addserver'] = array(                                                                                                                                                                                              
			'title'       => __( 'Add Server', 'panelhelper' ),																																												  
	);


		$settings['Orders'] = array(
			'title'       => __( 'Orders', 'panelhelper' ),
		);



		$settings['Customer Pages'] = array(                                                                                                                                                                         
			'title'       => __( 'Customer Pages', 'panelhelper' ),                                                                                                                                                                            
);

$settings['Email Notifications'] = array(                                                                                                                                                                         
	'title'       => __( 'Email Notifications', 'panelhelper' ),                                                                                                                                                                            
);
$settings['Settings'] = array(
	'title'       => __( 'Settings', 'panelhelper' ),
);




		

		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register plugin settings
	 *
	 * @return void
	 */
	public function register_settings() {
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab.
			//phpcs:disable
			$current_section = '';
			if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
				$current_section = sanitize_text_field($_POST['tab']);
			} else {
				if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
					$current_section = sanitize_text_field($_GET['tab']);
				}
			}
			//phpcs:enable

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section !== $section ) {
					continue;
				}

				// Add section to page.
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), $this->parent->_token . '_settings' );
				if (isset($data['fields']) && is_array($data['fields'])) {
				foreach ( $data['fields'] as $field ) {

					// Validation callback for field.
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field.
					$option_name = $this->base . $field['id'];
					register_setting( $this->parent->_token . '_settings', $option_name, $validation );

					// Add field to page.
					add_settings_field(
						$field['id'],
						$field['label'],
						array( $this->parent->admin, 'display_field' ),
						$this->parent->_token . '_settings',
						$section,
						array(
							'field'  => $field,
							'prefix' => $this->base,
						)
					);
				}}

				if ( ! $current_section ) {
					break;
				}
			}
		}
	}

	/**
	 * Settings section.
	 *
	 * @param array $section Array of section ids.
	 * @return void
	 */
	public function settings_section( $section ) {
		$html = '<p> ' . esc_html($this->settings[ $section['id'] ]['description']) . '</p>' . "\n";
		echo wp_kses_post($html);

	}

	/**
	 * Load settings page content.
	 *
	 * @return void
	 */
	public function settings_page() {
		// Instantiate the class containing the different pages
		$response = get_option('check_if_panelhelper_active');
		$api_active = get_option('panelhelper_api_active');

		$api_servers = new panelhelper_api_servers();
		$api_services = new panelhelper_api_services();
		$api_orders = new panelhelper_api_orders();
		$api_dash = new panelhelper_api_dashboard();
		$api_email = new panelhelper_email();

		if(trim($api_active) == "true"){
			$api_activate = new panelhelper_api_activator();
		}
		
		// Build page HTML.
		$html      = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
			//$html .= '<h2>' . __( 'Panelhelper', 'panelhelper' ) . '</h2>' . "\n";
			$tab = '';

  //          if (isset($_GET['tab']) && $_GET['tab'] === 'Servers') {
   //          do_action('panelhelperheader', $this->settings[ 'page' ] );
	//		}
    

	
	
	?>
                                                                                                                                                                                                              
                         <h1 class="notice-container"></h1>
                                                                                                                                                                                                              
                         <div class="panelhelper-plugin-fw-banner">
                                                                                                                                                                                                              
                         </div>
                                                                                                                                                                                                              
                                                                                                                                                                                                              
                                                                                                                                                                                                              
                                                                                                                                                                                                              
                                                                                                                                                                                                              
                                                                                                                                                                                                              
                                                                                                                                                                                                              
                 <?php
		//phpcs:disable
		if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
			$tab .= sanitize_text_field( $_GET['tab'] );
		}
		//phpcs:enable

		// Show page tabs.
		if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

			$html .= '<h2 class="nav-tab-wrapper">' . "\n";

			$c = 0;
			foreach ( $this->settings as $section => $data ) {

				// Set tab class.
				$class = 'nav-tab';
				if ( ! isset( $_GET['tab'] ) ) { //phpcs:ignore
					if ( 0 === $c ) {
						$class .= ' nav-tab-active';
					}
				} else {
					if ( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) { //phpcs:ignore
						$class .= ' nav-tab-active';
					}
				}

				// Set tab link.
				$tab_link = add_query_arg( array( 'tab' => $section ) );
				if ( isset( $_GET['settings-updated'] ) ) { //phpcs:ignore
					$tab_link = remove_query_arg( 'settings-updated', $tab_link );
				}
				if($section != 'addservice' && $section != 'editservice' && $section != 'addserver' && $section != 'importservices') {
				// Output tab.
				$html .= '<a href="' . esc_url($tab_link) . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";
				}
				++$c;


			}



			//returns the tab field for active and non active panelhelper
			if(trim($response) == "true" && $api_active == 'true'){
				$html .= '<div class="status-tab">Premium</div>' . "\n";
			} else {
				$html .= '<div class="status-tab">Standard</div>' . "\n";
			}
			$html .= '</h2>' . "\n";

		}

			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				// Get settings fields.
				ob_start();
				settings_fields( $this->parent->_token . '_settings' );
				//do_settings_sections( $this->parent->_token . '_settings' ); Main info from settings_section
				if (empty($tab)) {
					$tab = 'Dashboard';
				}
				
				//different input for different tabs, all html is stored here
				$html .= $this->handle_tab_specific_dash($tab, $api_dash);
				$html .= $this->handle_tab_specific_servers($tab, $api_servers);
				$html .= $this->handle_tab_specific_services($tab, $api_services);
				$html .= $this->handle_tab_specific_orders($tab, $api_orders);
				$html .= $this->handle_tab_specific_emails($tab, $api_email);
				switch ($tab){
					case 'Settings':
						if($api_active == 'true'){
						$html .= $api_activate->settingspage();
						} else{
						?>
							<form class="" action="" method="post" autocomplete="off">
							</form>
							<a href="https://appalify.com/panelhelper/" style="text-decoration: none;">
 								<button style="background-color: #50C878; color: white; font-size: 16px; padding: 10px 20px; border-radius: 7px; border: none; cursor: pointer !important; margin-bottom: 50px; margin-right: 15px;">
    								Get Premium
  								</button>
							</a> 
							<a href="https://appalify.com/plugin-support/" style="text-decoration: none;">
 								<button class="button-link-ph">
    								Request new feature or report a bug
  								</button>
							</a> 


							<br><titletagph class="panelhelper-title">Premium Settings</titletagph><br>



						<br><br>
						<form class="" action="" method="post" autocomplete="off">
						</form>
						<form id="myForm" method="post">
		
                		<strong style="font-size: 16px; margin-right: 5px;">WC update order status (PREMIUM)</strong>
						<input type="checkbox" name="panelhelper_wc_updater" id="panelhelper_wc_updater"> activate
  
    					<?php wp_nonce_field('panelhelper_wc_updater_nonce', 'panelhelper_wc_updater'); ?>
						</form>

						<script>
    						document.getElementById('panelhelper_wc_updater').addEventListener('change', function(event) {
        					event.preventDefault(); // Prevent the form from submitting immediately
        					if (confirm("You need premium for this feature. Would you like to upgrade to premium?")) {
           						 window.location.href = 'https://appalify.com/panelhelper/'; // Redirect to the premium page
        					} else {
            				// If the user cancels, uncheck the checkbox
            				this.checked = false;
        					}
    						});
						</script>

						<br>This will set your Woocommerce orders to completed, once your API order is marked as completed.

						<?php
						}
						break;
						case 'Customer Pages':
							?>
							<br><titletagph class="panelhelper-title">Order Checker for Customer</titletagph><br><br>
							<br><b style="font-size: 16px; margin-right: 5px;"> The order checker was added to your pages. Please check your wordpress "pages" tab for "Order Checker" to find the link.
							<br><br> You can change the title, but don't change the code or an error might occur. If you made a mistake, you can delete the page to the reload the page data.
							<br><br><br> If you would like to display the exact remains of a customer, please upgrade to the premium version, if you haven't already. </b>
							<?php	
						break;
					}

				$html .= ob_get_clean();

				



				

				$html     .= '<p class="submit">' . "\n";
					$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
				//	$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings', 'panelhelper' ) ) . '" />' . "\n";
				$html     .= '</p>' . "\n";
			$html         .= '</form>' . "\n";
			$html             .= '</div>' . "\n";
                                                                                                                                                                                                              
                                                                                                                                                                                                              
        $default_allowed_html = wp_kses_allowed_html('post');
                                                                                                                                                                                                              
		$custom_allowed_html = array(
			'br' => array(),
			'titletagph' => array(
				'class' => array(),
			),
			'a' => array(
				'href' => array(),
				'class' => array(),
				'style' => array(),
			),
			'select' => array(
				'id' => array(),
				'name' => array(),
				'required' => array(),
			),
			'option' => array(
				'value' => array(),
				'selected' => array(),
				'disabled' => array(),
			),
			'script' => array(), // Scripts are risky; be sure this is necessary and secure.
			'form' => array(
				'class' => array(),
				'action' => array(),
				'method' => array(),
				'autocomplete' => array(),
				'id' => array(), // Added for the form with id="myForm"
			),
			'input' => array(
				'type' => array(),
				'id' => array(),
				'name' => array(),
				'value' => array(),
				'required' => array(),
				'placeholder' => array(),
				'checked' => array(), // Added for checkbox input
			),
			'label' => array(
				'for' => array(),
			),
			'button' => array(
				'type' => array(),
				'class' => array(),
				'name' => array(),
			),
			'text' => array(),
			'h1' => array(),
			'h2' => array( // Added for the h2 tag
				'style' => array(),
			),
			'div' => array(
				'class' => array(),
				'style' => array(), // Added for inline styles
			),
			'phtitle2' => array(
				'style' => array(),
			),
			'title' => array(),
			'style' => array(),
		);
		
		
																																																				   
                                                                                                                                                                                                              
$allowed_html = array_merge_recursive($default_allowed_html, $custom_allowed_html);
echo wp_kses($html, $allowed_html); //phpcs:ignore

                                                                                                                                                                                                              
                                                                                                                                                                                                              
        }

	public function add_plugin_banner( $page ) {

		//do_action( 'qm/debug', 'plugin banner activated' );

		

			 ?>
			 <h1 class="notice-container"></h1>
			 <div class="panelhelper-plugin-fw-banner">
			 </div>


		
		 <?php
	 }


	 private function handle_tab_specific_servers($tab, $api_servers) {
		$html = '';

		//check for prem
		$response = get_option('check_if_panelhelper_active');
		$api_active = get_option('panelhelper_api_active');
		//prem pages
		if(trim($response) == "true" && $api_active == 'true'){
		$premium_server = new panelhelper_api_servers_premium;
		//switchcase to see which tab is active and opening the method in the tab as html
			switch ($tab){
				case 'Servers':
					$html .= $premium_server->panelhelper_api_server_table_page();
					break;
				// Add more cases for other tabs if needed
				case 'addserver':
					$html .= $premium_server->panelhelper_api_server_add();
					break;

			}
		} else{
			//switchcase to see which tab is active and opening the method in the tab as html
			switch ($tab){
				case 'Servers':
					$html .= $api_servers->panelhelper_api_server_add();
					break;
			}
		}
		return $html;
	}

	private function handle_tab_specific_services($tab, $api_services) {
		$html = '';
	
		//switchcase to see which tab is active and opening the method in the tab as html
		switch ($tab){
			
			case 'Services':
				$html .= $api_services->panelhelper_api_services_table_page();
				break;
			case 'addservice':
					$html .= $api_services->panelhelper_api_service_add();
					break;
			case 'editservice':
						$html .= $api_services->panelhelper_api_service_edit();
						break;
			case 'importservices':
						$html .= $api_services->panelhelper_api_service_import();
						break;
		}
	
		return $html;
	}

	private function handle_tab_specific_orders($tab, $api_orders) {
		$html = '';
	
		//switchcase to see which tab is active and opening the method in the tab as html
		switch ($tab){
			case 'Orders':
				$html .= $api_orders->panelhelper_api_orders_table_page();
				break;

		}
	
		return $html;
	}

	private function handle_tab_specific_dash($tab, $api_dash) {
		$html = '';
	
		//switchcase to see which tab is active and opening the method in the tab as html
		switch ($tab){
			case 'Dashboard':
				$html .= $api_dash->dash_comp();
				break;

		}
	
		return $html;
	}

	private function handle_tab_specific_emails($tab, $api_email) {
		$html = '';

		//switchcase to see which tab is active and opening the method in the tab as html
		switch ($tab){
			case 'Email Notifications':
				$html .= $api_email->panelhelper_email_settings();
				break;

		}
	
		return $html;
	}




	/**
	 * Main panelhelper_Settings Instance
	 *
	 * Ensures only one instance of panelhelper_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see panelhelper()
	 * @param object $parent Object instance.
	 * @return object panelhelper_Settings instance
	 */
	public static function instance( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	} // End instance()








}
