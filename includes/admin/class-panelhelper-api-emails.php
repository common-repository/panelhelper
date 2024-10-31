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
class panelhelper_email{
    public function save_meta_boxes() {
	}



function panelhelper_email_settings() {
  $html = '';
  $html .= $this->panelhelper_notification_load_status();
  $html .= $this->panelhelper_set_notifications_order_errors();
  $html .= $this->panelhelper_set_notifications_order_notstarted();
  $html .= $this->panelhelper_balance_warning();
  return $html;
}

/**
 * 
 * 
 * Set a checkbox for Email notifications
 * 
 */
public function panelhelper_notification_load_status(){
  $email_order_errors = get_option('panelhelper_email_order_errors');
  $email_order_notstarted = get_option('panelhelper_email_order_notstarted');
  $balance_warning = get_option('panelhelper_balance_warning');


  ?>
  <br><titletagph class="panelhelper-title">Email Notifications (Premium)</titletagph><br>


<?php
/**
 * 
 * 
 * Save email for email noti
 * 
 */
if (isset($_POST['email_notif_submit'])) {
        // Sanitize the email input
        $email_notif = sanitize_email($_POST['ph_email_notif']);

        // Validate the email before saving
        if (is_email($email_notif)) {
            // Save the input to the WordPress option "ph_email_notif"
            update_option('ph_email_notif', $email_notif);

            // Provide feedback to the user
            echo '<div class="updated"><p>Email notification updated to: ' . esc_html($email_notif) . '</p></div>';
        } else {
            // Error message if email is invalid
            echo '<div class="error"><p>Please enter a valid email address.</p></div>';
        }
    }

    // Get the current value of the option (if exists)
    $current_email = get_option('ph_email_notif', '');

    // Display the form
    ?>  <form class="" action="" method="post" autocomplete="off">
          </form>
    <form method="POST">
        <label for="ph_email_notif">Enter Notification Email:</label>
        <input type="email" id="ph_email_notif" name="ph_email_notif" value="<?php echo esc_attr($current_email); ?>" required />
        <input type="submit" name="email_notif_submit" value="Save" />
    </form>



  <br><br>
  <form class="" action="" method="post" autocomplete="off">
          </form>
  <form id="myForm" method="post">

              <strong style="font-size: 16px; margin-right: 5px;">Order errors</strong>
          
  <input type="checkbox" name="panelhelper_email_order_errors" id="panelhelper_email_order_errors" <?php checked($email_order_errors, true); ?>>
          <?php  wp_nonce_field( 'panelhelper_email_order_errors_nonce', 'panelhelper_email_order_errors' ); ?>
  </form>
  <br>You will receive emails, if an order fails or is canceled.


  <br><br><br>
  <form class="" action="" method="post" autocomplete="off">
          </form>
  <form id="notstartedform" method="post">

              <strong style="font-size: 16px; margin-right: 5px;">Order not started</strong>
          
  <input type="checkbox" name="panelhelper_email_order_notstarted" id="panelhelper_email_order_notstarted" <?php checked($email_order_notstarted, true); ?>>
          <?php  wp_nonce_field( 'panelhelper_email_order_notstarted_nonce', 'panelhelper_email_order_notstarted' ); ?>
  </form>
  <br>You will receive emails, if an order isn't started, due to an error (No SMM API ID received).


  
  <br><br><br>
  <form class="" action="" method="post" autocomplete="off">
          </form>
  <form id="warningform" method="post">

              <strong style="font-size: 16px; margin-right: 5px;">Balance warning</strong>
          
  <input type="checkbox" name="panelhelper_balance_warning" id="panelhelper_balance_warning" <?php checked($balance_warning, true); ?>>
          <?php  wp_nonce_field( 'panelhelper_balance_warning_nonce', 'panelhelper_balance_warning' ); ?>
  </form>
  <br>You will receive emails, once your balance drops below the specified point.
  <br><br>




  <?php

if (isset($_POST['balance_warning_submit'])) {
    // Sanitize the input
    $balance_warning = intval($_POST['balance_warning']);

    // Save the input to the WordPress option "balance_warning"
    update_option('balance_warning', $balance_warning);

    // Provide feedback to the user
    echo '<div class="updated"><p>Balance warning updated to: ' . $balance_warning . '</p></div>';
}

// Get the current value of the option (if exists)
$current_value = get_option('balance_warning', '');

// Display the form
?>
<form method="POST">
    <label for="balance_warning">Set Balance Warning:</label>
    <input type="number" id="balance_warning" name="balance_warning" value="<?php echo esc_attr($current_value); ?>" required />
    <input type="submit" name="balance_warning_submit" value="Save" />
</form>
<?php
}


public function panelhelper_set_notifications_order_errors(){
  $email_order_errors = get_option('panelhelper_email_order_errors');
  $ph_response = get_option('check_if_panelhelper_active');
  ?>
  <script>
      document.getElementById('panelhelper_email_order_errors').addEventListener('change', function() {
      document.getElementById('myForm').submit();
      });
  </script>
  <?php
  if (trim($ph_response) == "true") {
  if (isset($_POST['panelhelper_email_order_errors']) && wp_verify_nonce( sanitize_text_field( wp_unslash (  $_POST['panelhelper_email_order_errors'])), 'panelhelper_email_order_errors_nonce' ) ) {
  if ($email_order_errors ==false){
    $panelhelper_email_order_errors2 = true;
    update_option('panelhelper_email_order_errors', $panelhelper_email_order_errors2);
  } else{
    $panelhelper_email_order_errors2 = false;
    update_option('panelhelper_email_order_errors', $panelhelper_email_order_errors2);

  }

  $redirect_url = add_query_arg( array(
    'page' => 'panelhelper_settings',
    'tab' => 'Email+Notifications'
  ), admin_url( 'admin.php' ) );

  wp_redirect($redirect_url);
  exit;
  }
    } else{
    ?>
    <script>
    			document.getElementById('panelhelper_email_order_errors').addEventListener('change', function(event) {
        		event.preventDefault(); // Prevent the form from submitting immediately
        			if (confirm("You need premium for this feature. Would you like to upgrade to premium?")) {
           				 window.location.href = 'https://appalify.com/panelhelper/'; // Redirect to the premium page
        			} else {
            		// If the user cancels, uncheck the checkbox
            		    this.checked = false;
        			}
    			});
	</script>

					

	<?php
    }

}

public function panelhelper_set_notifications_order_notstarted(){

  $email_order_notstarted = get_option('panelhelper_email_order_notstarted');
  $ph_response = get_option('check_if_panelhelper_active');


    ?>
    <script>
        document.getElementById('panelhelper_email_order_notstarted').addEventListener('change', function() {
        document.getElementById('notstartedform').submit();
        });
    </script>
    <?php
   
  
    if (trim($ph_response) == "true") {
  if (isset($_POST['panelhelper_email_order_notstarted']) && wp_verify_nonce( sanitize_text_field( wp_unslash (  $_POST['panelhelper_email_order_notstarted'])), 'panelhelper_email_order_notstarted_nonce' ) ) {
  if ($email_order_notstarted ==false){
    $panelhelper_email_order_notstarted2 = true;
    update_option('panelhelper_email_order_notstarted', $panelhelper_email_order_notstarted2);
  } else{
    $panelhelper_email_order_notstarted2 = false;
    update_option('panelhelper_email_order_notstarted', $panelhelper_email_order_notstarted2);

  }

  $redirect_url = add_query_arg( array(
    'page' => 'panelhelper_settings',
    'tab' => 'Email+Notifications'
  ), admin_url( 'admin.php' ) );

  wp_redirect($redirect_url);
  exit;
  }
    } else{
    ?>
    <script>
    			document.getElementById('panelhelper_email_order_notstarted').addEventListener('change', function(event) {
        		event.preventDefault(); // Prevent the form from submitting immediately
        			if (confirm("You need premium for this feature. Would you like to upgrade to premium?")) {
           				 window.location.href = 'https://appalify.com/panelhelper/'; // Redirect to the premium page
        			} else {
            		// If the user cancels, uncheck the checkbox
            		    this.checked = false;
        			}
    			});
	</script>

					

	<?php
    }

}

public function panelhelper_balance_warning(){

  $balance_warning = get_option('panelhelper_balance_warning');
  $ph_response = get_option('check_if_panelhelper_active');
  ?>


  <script>
      document.getElementById('panelhelper_balance_warning').addEventListener('change', function() {
      document.getElementById('warningform').submit();
      });
  </script>
  <?php
  if (trim($ph_response) == "true") {
  if (isset($_POST['panelhelper_balance_warning']) && wp_verify_nonce( sanitize_text_field( wp_unslash (  $_POST['panelhelper_balance_warning'])), 'panelhelper_balance_warning_nonce' ) ) {
  if ($balance_warning ==false){
    $panelhelper_balance_warning2 = true;
    update_option('panelhelper_balance_warning', $panelhelper_balance_warning2);
  } else{
    $panelhelper_balance_warning2 = false;
    update_option('panelhelper_balance_warning', $panelhelper_balance_warning2);

  }

  $redirect_url = add_query_arg( array(
    'page' => 'panelhelper_settings',
    'tab' => 'Email+Notifications'
  ), admin_url( 'admin.php' ) );

  wp_redirect($redirect_url);
  exit;
  }
    } else{
    ?>
    <script>
    			document.getElementById('panelhelper_balance_warning').addEventListener('change', function(event) {
        		event.preventDefault(); // Prevent the form from submitting immediately
        			if (confirm("You need premium for this feature. Would you like to upgrade to premium?")) {
           				 window.location.href = 'https://appalify.com/panelhelper/'; // Redirect to the premium page
        			} else {
            		// If the user cancels, uncheck the checkbox
            		    this.checked = false;
        			}
    			});
	</script>

					

	<?php
    }

}



}