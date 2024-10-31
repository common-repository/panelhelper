<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


add_filter( 'woocommerce_product_data_tabs', 'panelhelper_add_product_data_tab_woo' );
add_action( 'woocommerce_product_data_panels', 'panelhelper_add_api_service' );

add_action( 'woocommerce_process_product_meta', 'panelhelper_save_product_settings' );
add_action('wp_ajax_load_services', 'panelhelper_load_services_callback');



add_filter('woocommerce_add_cart_item_data', 'panelhelper_save_custom_field_value', 10, 2);
add_filter('woocommerce_get_item_data', 'panelhelper_display_custom_field_on_cart_and_checkout', 10, 2);
add_action('woocommerce_single_product_summary', 'panelhelper_display_custom_field_on_product_page', 6);
add_action('woocommerce_add_order_item_meta', 'panelhelper_save_custom_field_to_order_meta_data', 10, 3);
add_action('woocommerce_payment_complete', 'panelhelper_call_api_on_order_completion');




//This file is used to add the user input to woocommerce and triggers the api orders automatically, when a wc order is completed.


function panelhelper_add_product_data_tab_woo( $tabs ) {

    $tabs['panelhelper'] = array(
        'label'    => __( 'Panelhelper', 'panelhelper' ),
        'target'   => 'panelhelper_product_settings',
        'class'    => array()
    );

    return $tabs;

}


function panelhelper_add_api_service() {
    global $post, $wpdb;

    // Retrieve the currently saved service
    $current_server = get_post_meta($post->ID, 'panelhelper_selected_server', true);
    $current_service = get_post_meta($post->ID, 'panelhelper_selected_service', true);
    $custom_title = get_post_meta($post->ID, 'panelhelper_custom_title', true);
    $custom_comment_title = get_post_meta($post->ID, 'panelhelper_cc_title', true);
    // Retrieve API servers
    $servers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}panelhelper_api_servers");
    $api_service_enabled = get_post_meta($post->ID, 'panelhelper_api_service_enabled', true); // New
    $api_comment_enabled = get_post_meta($post->ID, 'panelhelper_api_comment_enabled', true); // New
    ?>
    <div id="panelhelper_product_settings" class="panel woocommerce_options_panel panelhelper-options-groups-wrapper">
        <p>
            <div style="display: inline-block;">
                <h2 style="display: inline;">Enable Panelhelper</h2>
            </div>
            <input type="checkbox" name="panelhelper_api_service_enabled" id="panelhelper_api_service_enabled" <?php checked($api_service_enabled, 'yes'); ?>>
        </p>
        <h2>Select a service</h2>
        <p>
            <label for="panelhelper_selected_server">Select a server:</label>
            <select name="panelhelper_selected_server" id="panelhelper_selected_server">
                <option value="">Select server</option>
                <?php foreach ($servers as $server): ?>
                    <option value="<?php echo esc_attr($server->API_KEY); ?>" <?php selected($current_server, $server->API_KEY); ?>><?php echo esc_html($server->API_URL); ?></option>
                <?php endforeach; ?>
            </select>
            
        </p>





        <div id="panelhelper_service_dropdown">
    <?php
    // Retrieve services for the selected server
    $services = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}panelhelper_api_services WHERE API_KEY = %s LIMIT 10", $current_server));
    ?>
<p>
    <label for="panelhelper_selected_service">Select a service:</label>
    <input type="text" id="panelhelper_selected_service_input" autocomplete="off">
    <select name="panelhelper_selected_service" id="panelhelper_selected_service" style="display: none;">
        <option value="">Select service</option>
        <?php foreach ($services as $service): ?>
            <option value="<?= esc_attr($service->SERVICE_ID); ?>" data-name="<?= esc_attr($service->SERVICE_NAME); ?>">
                <?= esc_html($service->SERVICE_ID) . ' ' . esc_html($service->SERVICE_NAME); ?>
            </option>
        <?php endforeach; ?>
    </select>
</p>
        </div>
        
        <h2>Click enter to reload the results. Only the ID will be displayed, when you reload the page.</h2><br><br><br>

        <script>
jQuery(function($) {
    var services = <?= json_encode($services); ?>;
    var debounceTimeout;

    $('#panelhelper_selected_service_input').val(<?= json_encode($current_service); ?>);

    function filterServices(term) {
        var matcher = new RegExp($.ui.autocomplete.escapeRegex(term), "i");
        return services.filter(function(service) {
            return matcher.test(service.SERVICE_ID + ' ' + service.SERVICE_NAME);
        }).map(function(service) {
            return {
                label: service.SERVICE_ID + ' ' + service.SERVICE_NAME,
                value: service.SERVICE_ID + ' ' + service.SERVICE_NAME,
                id: service.SERVICE_ID
            };
        });
    }

    function initializeAutocomplete() {
        $('#panelhelper_selected_service_input').autocomplete({
            source: function(request, response) {
                response(filterServices(request.term));
            },
            select: function(event, ui) {
                $('#panelhelper_selected_service').val(ui.item.id);
            },
            minLength: 0
        }).focus(function() {
            $(this).autocomplete("search");
        });
    }

    initializeAutocomplete();

    $('#panelhelper_selected_service_input').on('keydown', function(event) {
        if (event.which === 13) {
            event.preventDefault();
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(function() {
                fetchServices($(this).val());
            }.bind(this), 300); // Debounce time of 300ms
        }
    });

    $('#panelhelper_selected_server').change(function() {
        var serverKey = $(this).val();
        if (serverKey) {
            fetchServicesByServer(serverKey);
        } else {
            services = [];
            updateHiddenSelect();
            $('#panelhelper_selected_service_input').autocomplete("option", "source", []);
        }
    });

    function fetchServices(query) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'search_services',
                query: query
            },
            success: function(data) {
                services = JSON.parse(data);
                updateHiddenSelect();
                $('#panelhelper_selected_service_input').autocomplete("option", "source", filterServices(query)).autocomplete("search", query);
            },
            error: function(error) {
                console.error("Error fetching services:", error);
            }
        });
    }

    function fetchServicesByServer(serverKey) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_services',
                server_key: serverKey
            },
            success: function(data) {
                services = JSON.parse(data);
                updateHiddenSelect();
                $('#panelhelper_selected_service_input').val('');
                $('#panelhelper_selected_service').val('');
                $('#panelhelper_selected_service_input').autocomplete("option", "source", filterServices('')).autocomplete("search", "");
            },
            error: function(error) {
                console.error("Error fetching services:", error);
            }
        });
    }

    function updateHiddenSelect() {
        var $select = $('#panelhelper_selected_service');
        $select.empty();
        $select.append('<option value="">Select service</option>');
        services.forEach(function(service) {
            $select.append('<option value="' + service.SERVICE_ID + '" data-name="' + service.SERVICE_NAME + '">' + service.SERVICE_ID + ' ' + service.SERVICE_NAME + '</option>');
        });
    }
});
</script>






<p>
            <div style="display: inline-block;">
                <h2 style="display: inline;">Comment field (Premium)</h2>
            </div>
            <input type="checkbox" name="panelhelper_api_comment_enabled" id="panelhelper_api_comment_enabled" <?php checked($api_comment_enabled, 'yes'); ?>>
            <h2>Add a field for users to enter comments or keywords.</h2>

            <h2>Enter a title for the comment field on the product page:</h2>
            <p>
            <label for="panelhelper_cc_title_<?php echo esc_attr($post->ID); ?>">Custom Input label:</label>
            <input type="text" name="panelhelper_cc_title_<?php echo esc_attr($post->ID); ?>" id="panelhelper_cc_title_<?php echo esc_attr($post->ID); ?>" placeholder="comments, keywords, ..." value="<?php echo esc_attr($custom_comment_title); ?>">
            </p>
        </p>
        

            



        <h2>Enter a title for the main input field on the product page:</h2>
        <p>
            <label for="panelhelper_custom_title_<?php echo esc_attr($post->ID); ?>">Custom Input label:</label>
            <input type="text" name="panelhelper_custom_title_<?php echo esc_attr($post->ID); ?>" id="panelhelper_custom_title_<?php echo esc_attr($post->ID); ?>" placeholder="link" value="<?php echo esc_attr($custom_title); ?>">
        </p>
        <h2>Note: Please use this plugin with variable products, so the quantity can be passed to the server. Name the attribute "Quantity"</h2><br><br><br>

        <?php wp_nonce_field('panelhelper_save_product_settings_nonce', 'panelhelper_product_settings_nonce'); ?>
    </div>
    <script>
        // JavaScript to handle the change event of the server dropdown
        document.addEventListener('DOMContentLoaded', function () {
            var serverDropdown = document.getElementById('panelhelper_selected_server');
            var serviceDropdown = document.getElementById('panelhelper_selected_service');

            serverDropdown.addEventListener('change', function () {
                if (this.value !== '') {
                    var nonce = '<?php echo esc_attr(wp_create_nonce("load_services_nonce")); ?>';
                    var data = {
    'action': 'load_services',
    'selected_server': this.value,
    'load_services_nonce': nonce // Change 'nonce' to 'load_services_nonce'
};
                    jQuery.post(ajaxurl, data, function (response) {
                        serviceDropdown.innerHTML = response;
                    });
                    document.getElementById('panelhelper_service_dropdown').style.display = 'block';
                } else {
                    serviceDropdown.innerHTML = '<option value="">Select service</option>';
                    document.getElementById('panelhelper_service_dropdown').style.display = 'none';
                }
            });
        });
    </script>
    <?php
}





function get_services() {
    global $wpdb;
    $server_key = sanitize_text_field($_POST['server_key']);
    $services = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}panelhelper_api_services WHERE API_KEY = %s LIMIT 10", $server_key));

    echo json_encode($services);
    wp_die(); // this is required to terminate immediately and return a proper response
}

add_action('wp_ajax_get_services', 'get_services');
add_action('wp_ajax_nopriv_get_services', 'get_services');

function search_services() {
    global $wpdb;
    $query = sanitize_text_field($_POST['query']);
    $like_query = '%' . $wpdb->esc_like($query) . '%';
    
    $services = $wpdb->get_results($wpdb->prepare("
        SELECT * 
        FROM {$wpdb->prefix}panelhelper_api_services 
        WHERE SERVICE_ID LIKE %s OR SERVICE_NAME LIKE %s
    LIMIT 10", $like_query, $like_query));

    echo json_encode($services);
    wp_die(); // this is required to terminate immediately and return a proper response
}

add_action('wp_ajax_search_services', 'search_services');
add_action('wp_ajax_nopriv_search_services', 'search_services');





// Gets user input for the WC selections
function panelhelper_save_product_settings($post_id) {
    if (!isset($_POST['panelhelper_product_settings_nonce']) || !wp_verify_nonce( sanitize_text_field( wp_unslash (  $_POST['panelhelper_product_settings_nonce'])), 'panelhelper_save_product_settings_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['panelhelper_selected_server'])) {
        update_post_meta($post_id, 'panelhelper_selected_server', sanitize_text_field($_POST['panelhelper_selected_server']));
    }

    if (isset($_POST['panelhelper_selected_service'])) {
        update_post_meta($post_id, 'panelhelper_selected_service', sanitize_text_field($_POST['panelhelper_selected_service']));
    }

    if (isset($_POST['panelhelper_custom_title_' . $post_id])) {
        update_post_meta($post_id, 'panelhelper_custom_title', sanitize_text_field($_POST['panelhelper_custom_title_' . $post_id]));
    }
    if (isset($_POST['panelhelper_cc_title_' . $post_id])) {
        update_post_meta($post_id, 'panelhelper_cc_title', sanitize_text_field($_POST['panelhelper_cc_title_' . $post_id]));
    }
    $api_service_enabled = isset($_POST['panelhelper_api_service_enabled']) ? 'yes' : 'no';
    update_post_meta($post_id, 'panelhelper_api_service_enabled', $api_service_enabled);

    $api_comment_enabled = isset($_POST['panelhelper_api_comment_enabled']) ? 'yes' : 'no';
    update_post_meta($post_id, 'panelhelper_api_comment_enabled', $api_comment_enabled);
}

// Loads the DB callback dynamically
function panelhelper_load_services_callback() {
    global $wpdb;

    if (!isset($_POST['load_services_nonce']) || !wp_verify_nonce( sanitize_text_field( wp_unslash (  $_POST['load_services_nonce'])), 'load_services_nonce')) {
        die('Nonce verification failed');
    }
    

    $selected_server = sanitize_text_field($_POST['selected_server']);
    $services = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}panelhelper_api_services WHERE API_KEY = %s", $selected_server));

    $options = '<option value="">Select service</option>';
    foreach ($services as $service) {
        $options .= '<option value="' . esc_attr($service->SERVICE_ID) . '">' . esc_html($service->SERVICE_ID) . ' ' . esc_html($service->SERVICE_NAME) . '</option>';
    }

    echo wp_kses($options, array(
        'option' => array(
            'value' => array(),
            'selected' => array(),
        ),
    ));
    wp_die();
}

function panelhelper_check_and_add_actions() {
    $api_service_enabled = get_post_meta(get_the_ID(), 'panelhelper_api_service_enabled', true);

    if ($api_service_enabled === 'yes') {
        
        add_action('woocommerce_before_add_to_cart_button', 'panelhelper_add_custom_input_field');
    }
}add_action('wp', 'panelhelper_check_and_add_actions');



//What to after the order is paid
function panelhelper_call_api_on_order_completion( $order_id ) {

    $order = wc_get_order( $order_id );
    $items = $order->get_items();



    global $wpdb;
    $table_name = $wpdb->prefix . 'panelhelper_api_orders';

    foreach ( $items as $item ) {

        //gets the product id
        $product_id = $item->get_product_id();
        
        // Get the variation ID (if it's a variation)
        $variation_id = $item->get_variation_id();
        

        //gets the selected quantity
        $variation_quantity = '';
        if ($variation_id) {
            // Retrieve the variation attributes
            $variation = new WC_Product_Variation( $variation_id );
            $attributes = $variation->get_variation_attributes();
            
            // If the 'quantity' attribute exists, get its value
            if (isset($attributes['attribute_quantity'])) {
                $variation_quantity = $attributes['attribute_quantity'];
            }
        }

        // Retrieve the selected server, service, and custom title for the product
        $selected_server = get_post_meta( $product_id, 'panelhelper_selected_server', true );
        $selected_service = get_post_meta( $product_id, 'panelhelper_selected_service', true );
        $custom_title = get_post_meta( $product_id, 'panelhelper_custom_title', true );

        // Retrieve API URL and API Key of the selected server
        global $wpdb;
        $server_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}panelhelper_api_servers WHERE API_KEY = %s", $selected_server ) );
        $api_url = $server_info->API_URL;
        $api_key = $server_info->API_KEY;

        // Use the selected service ID where API_KEY matches the selected API key
        $service_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}panelhelper_api_services WHERE API_KEY = %s AND SERVICE_ID = %s", $selected_server, $selected_service ) );
        $service_id = $service_info->SERVICE_ID;
        $service_db_id = $service_info->ID;


        // Call your API function with $selected_service here
        $link = wc_get_order_item_meta($item->get_id(), 'Custom Field', true);
        $comments = wc_get_order_item_meta($item->get_id(), 'Comment Field', true);
        if(!$comments){
        $panelhelper_api_adder = new panelhelper_api_adder();
        $response = $panelhelper_api_adder->api_order($service_id, $link, $variation_quantity,$api_key,$api_url);
        } else{
            $panelhelper_api_adder = new panelhelper_api_adder();
            $response = $panelhelper_api_adder->api_order_comment($service_id, $link, $variation_quantity,$api_key,$api_url, $comments);
        }

        $currency = $order->get_currency();

        if (!empty($link)) {
        $api_order_number = $response->order;
        //api_order( $selected_service );
        if(!$api_order_number){
            $api_order_number ='000'; 
        }


        $response = get_option('check_if_panelhelper_active');
        $api_active = get_option('panelhelper_api_active');

        if (trim($response) == "true" && $api_active == 'true') {

            $api_activate = new panelhelper_api_activator();
            $api_activate->securely_check_api_server($order->get_total());


        }
        
        $order->update_meta_data('panelhelper', true); // Set the metadata
        $order->save();
        

		$wpdb->insert(
            $table_name,
            array(
                'api_order_number' => $api_order_number, // Assuming this is obtained from the API response
                'quantity' => $variation_quantity,
                'api_price' => '0', // Assuming this is obtained from the API response
                'store_price' => $order->get_total(),
                'woocommerce_order_number' => $order_id,
                'services_database_id' => $service_db_id,
                'currency' => $currency
            ),
            array(
                '%d',
                '%d',
                '%f',
                '%f',
                '%d',
                '%d',
                '%s'
            )
        );


    }
}
}
















//the following values functions are only for the input field




function panelhelper_add_custom_input_field() {
    global $post;
    $custom_title = get_post_meta($post->ID, 'panelhelper_custom_title', true);
    
    // Generate nonce field
    wp_nonce_field( 'ph_custom_field_nonce', 'ph_custom_field_nonce' );

    echo '<div class="custom-input-field">';
    echo '<label for="panelhelper_userinput">' . esc_html($custom_title) . ': </label>';
    echo '<input type="text" id="ph_custom_field" name="ph_custom_field">';
    echo '</div>';
}


// Save custom field value when product is added to cart

function panelhelper_save_custom_field_value($cart_item_data, $product_id) {
    if (isset($_POST['ph_custom_field']) && wp_verify_nonce( sanitize_text_field( wp_unslash (   $_POST['ph_custom_field_nonce'])), 'ph_custom_field_nonce' ) ) {
        $cart_item_data['ph_custom_field'] = wc_clean($_POST['ph_custom_field']);
        $cart_item_data['unique_key'] = md5(microtime().wp_rand());
    }
    return $cart_item_data;
}


// Display custom field value on cart and checkout page

function panelhelper_display_custom_field_on_cart_and_checkout($cart_data, $cart_item) {
    $product_id = $cart_item['product_id']; // Assuming the product ID is available in the cart item.
    $custom_title = get_post_meta($product_id, 'panelhelper_custom_title', true);
    if ($custom_title && isset($cart_item['ph_custom_field'])) {
        $cart_data[] = array(
            'name' => esc_html($custom_title),
            'value' => $cart_item['ph_custom_field']
        );
    }
    return $cart_data;
}


// Display custom field value on single product page


function panelhelper_display_custom_field_on_product_page() {
    global $product;
    
    $ph_custom_field_value = $product->get_meta('ph_custom_field');

    if (!empty($ph_custom_field_value)) {
        echo '<p>Your Value: ' . esc_html($ph_custom_field_value) . '</p>';
    }
}

// Save custom field value to order meta data
function panelhelper_save_custom_field_to_order_meta_data($item_id, $values, $cart_item_key) {
    if (isset($values['ph_custom_field'])) {
        wc_add_order_item_meta($item_id, 'Custom Field', $values['ph_custom_field']);
    }
}








