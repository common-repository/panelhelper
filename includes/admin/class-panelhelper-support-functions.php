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

 
class panelhelper_support_functions{  
    
    
function panelhelper_orderpage_shortcode(){
    add_shortcode('order_status', array($this,'display_order_status_box')); 
}

function panelhelper_orderpage_creator(){
    $ph_page_title = "Order Checker";
    $ph_post_content = $this->panelhelper_orderpage_html();


    $args = array(
        'post_type' => 'page',
        'title' => $ph_page_title,
        'post_status' => 'publish',
        'posts_per_page' => 1
    );
    $query = new WP_Query($args);

    if(!$query->have_posts()){
        $ph_orders = array(
            'post_title'    => wp_strip_all_tags( $ph_page_title ),
            'post_content'  =>  '[order_status]', 
            'post_status'   => 'publish',
            'post_type'     => 'page'
            );
            wp_insert_post($ph_orders);
    }
    



}

function panelhelper_orderpage_html(){
    ob_start();
    ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
      .panelhelper-order-status-box {
            border: 1px solid #ccc;
            border-radius: 15px;
            padding: 20px;
            width: 300px;
            margin: 20px auto;
            text-align: center;
        }
        .panelhelper-order-status-box input[type="text"] {
            width: calc(100% - 22px);
            padding: 10px;
            border-radius: 10px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
        }
        .panelhelper-order-status-box button {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            background-color: #1B1B1B;
            color: white;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }
        .panelhelper-order-status-box button:hover {
            background-color: #343434;
        }
        .panelhelper-order-status-result {
            border: 1px solid #ccc;
            border-radius: 15px;
            padding: 20px;
            width: 300px;
            margin: 20px auto;
            text-align: center;
        }
       






        .card {
    z-index: 0;
    
    padding-bottom: 20px;
    margin-top: 90px;
    margin-bottom: 90px;
    border-radius: 10px;
}

.top {
    padding-top: 40px;
    padding-left: 13% !important;
    padding-right: 13% !important;
}

/*Icon progressbar*/
#progressbar {
    margin-bottom: 30px;
    overflow: hidden;
    color: #455A64;
    padding-left: 0px;
    margin-top: 30px;
} 

#progressbar li {
    list-style-type: none;
    font-size: 13px;
    width: 25%;
    float: left;
    position: relative;
    font-weight: 400;
}

#progressbar .step0:before {
    font-family: FontAwesome;
    content: "\f10c";
    color: #fff;
}

#progressbar li:before {
    width: 40px;
    height: 40px;
    line-height: 45px;
    display: block;
    font-size: 20px;
    background: #C5CAE9;
    border-radius: 50%;
    margin: auto;
    padding: 0px;
}

/*ProgressBar connectors*/
#progressbar li:after {
    content: '';
    width: 100%;
    height: 12px;
    background: #C5CAE9;
    position: absolute;
    left: 0;
    top: 16px;
    z-index: -1;
}

#progressbar li:last-child:after {
    border-top-right-radius: 10px;
    border-bottom-right-radius: 10px;
    position: absolute;
    left: -50%;
}

#progressbar li:nth-child(2):after, #progressbar li:nth-child(3):after {
    left: -50%;
}

#progressbar li:first-child:after {
    border-top-left-radius: 10px;
    border-bottom-left-radius: 10px;
    position: absolute;
    left: 50%;
}

#progressbar li:last-child:after {
    border-top-right-radius: 10px;
    border-bottom-right-radius: 10px;
}

#progressbar li:first-child:after {
    border-top-left-radius: 10px;
    border-bottom-left-radius: 10px;
}

/*Color number of the step and the connector before it*/
#progressbar li.active:before, #progressbar li.active:after {
    background: #1B1B1B;
}

#progressbar li.active:before {
    font-family: FontAwesome;
    content: "\f00c";
}

.icon {
    width: 60px;
    height: 60px;
    margin-right: 15px;
}

.icon-content { 
    padding-bottom: 20px;
}

@media screen and (max-width: 992px) {
    .icon-content {
        width: 50%;
    }
}




    </style>

    

    <div class="panelhelper-order-status-box">
        <form method="post" action="">
            <input type="text" name="order_number" placeholder="Please Enter Order Number" required>
            <button type="submit">Check Status</button>
        </form>
    </div>
    



</div>

    
    <?php
    return ob_get_clean();
}

// Shortcode function to display the order status box
function display_order_status_box() {
    global $wpdb;

    $output = $this->panelhelper_orderpage_html();

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['order_number'])) {
        $order_status ='';
        $order_number = sanitize_text_field($_POST['order_number']);
        $query = $wpdb->prepare("SELECT status, start_count, remains FROM {$wpdb->prefix}panelhelper_api_orders WHERE woocommerce_order_number = %s", $order_number);
        $order_data = $wpdb->get_row($query, ARRAY_A);
        if($order_data!= NULL){
        $order_status = $order_data['status'];
        } 
        if ($order_status == '') {
            $output .= '<div class="panelhelper-order-status-result">No order was found under that number.</div>';
        } else{
            
            $response = get_option('check_if_panelhelper_active');
            if(trim($response) == "true"){
                $output .= $this->panelhelper_order_statusbar_plus($order_number, $order_data);
            } else{
                $output .= $this->panelhelper_order_statusbar($order_number, $order_status);
            }
        }
    }

    return $output;
}

function panelhelper_order_statusbar($order_number, $order_status){
    ob_start();
    
    ?>
    <div class="container px-1 px-md-4 py-5 mx-auto">
    <div class="card">
        <div class="row d-flex justify-content-between px-3 top">
            <div class="d-flex">
                <h5>ORDER <span class="text-primary font-weight-bold">#<?php echo esc_attr($order_number)?></span></h5>
            </div>
            <div class="d-flex flex-column text-sm-right" style="font-size: 20px; font-weight: 600;"><?php echo esc_attr($order_status)?></div>
            <div class="d-flex flex-column text-sm-right">
                <p class="mb-0">Expected Arrival <span><?php echo date('F j, Y');?></span></p>
          
            </div>
        </div>

        <?php if ($order_status == "Processing"){?>
        <div class="row d-flex justify-content-center">
            <div class="col-12">
            <ul id="progressbar" class="text-center">
                <li class="active step0"></li> 
                <li class="active step0"></li> 
                <li class="step0"></li> 
                <li class="step0"></li> 
            </ul>
            </div> 
        </div>

        <?php }else if ($order_status == "Completed"){?>
            <div class="row d-flex justify-content-center">
            <div class="col-12">
            <ul id="progressbar" class="text-center">
                <li class="active step0"></li>
                <li class="active step0"></li>
                <li class="active step0"></li>
                <li class="active step0"></li>
            </ul>
            </div> 
        </div> 

        <?php }if ($order_status == "Pending"){?>
        <div class="row d-flex justify-content-center">
            <div class="col-12">
            <ul id="progressbar" class="text-center">
                <li class="active step0"></li> 
                <li class="step0"></li> 
                <li class="step0"></li> 
                <li class="step0"></li> 
            </ul>
            </div> 
        </div>

        <?php }else if ($order_status == "Canceled"){?>
            <div class="row d-flex justify-content-center">
            <div class="col-12">
            <ul id="progressbar" class="text-center">
                <li class="step0"></li> 
                <li class="step0"></li> 
                <li class="step0"></li> 
                <li class="step0"></li> 
            </ul>
            </div> 
        </div>

        <?php }?>
            </div> 
        </div> 
    </div>
</div>
<?php
return ob_get_clean();
}

function panelhelper_order_statusbar_plus($order_number, $order_data){
    ob_start();
    $order_status = $order_data['status'];
    $start_count = $order_data['start_count'];
    $remains = $order_data['remains'];
    ?>
    <div class="container px-1 px-md-4 py-5 mx-auto">
    <div class="card">
        <div class="row d-flex justify-content-between px-3 top">
            <div class="d-flex">
                <h5>ORDER <span class="text-primary font-weight-bold">#<?php echo esc_attr($order_number)?></span></h5>
            </div>
            <div class="d-flex flex-column text-sm-right" style="font-size: 20px; font-weight: 600;"><?php echo esc_attr($order_status)?></div>
            <div class="d-flex flex-column text-sm-right">
                <p class="mb-0">Expected Arrival <span><?php echo date('F j, Y');?></span></p>
          
            </div>
        </div>

        <?php if ($order_status == "Processing"){?>
        <div class="row d-flex justify-content-center">
            <div class="col-12">
            <ul id="progressbar" class="text-center">
                <li class="active step0"></li> 
                <li class="active step0"></li> 
                <li class="step0"></li> 
                <li class="step0"></li> 
            </ul>
            </div> There are currently <?php echo esc_attr($remains)?> remaining.
        </div>

        <?php }else if ($order_status == "Completed"){?>
            <div class="row d-flex justify-content-center">
            <div class="col-12">
            <ul id="progressbar" class="text-center">
                <li class="active step0"></li>
                <li class="active step0"></li>
                <li class="active step0"></li>
                <li class="active step0"></li>
            </ul>
            </div> 
        </div> 

        <?php }if ($order_status == "Pending"){?>
        <div class="row d-flex justify-content-center">
            <div class="col-12">
            <ul id="progressbar" class="text-center">
                <li class="active step0"></li> 
                <li class="step0"></li> 
                <li class="step0"></li> 
                <li class="step0"></li> 
            </ul>
            </div> Thank your for your order. Your start count is <?php echo esc_attr($start_count)?>.
        </div>

        <?php }else if ($order_status == "Canceled"){?>
            <div class="row d-flex justify-content-center">
            <div class="col-12">
            <ul id="progressbar" class="text-center">
                <li class="step0"></li> 
                <li class="step0"></li> 
                <li class="step0"></li> 
                <li class="step0"></li> 
            </ul>
            </div> Your order was canceled please contact support.
        </div>

        <?php }?>
            </div> 
        </div> 
    </div>
</div>
<?php
return ob_get_clean();
}


}