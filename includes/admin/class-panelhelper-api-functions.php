<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * These functions are used to connect to the api servers and do certain tasks.
 */
class panelhelper_api_adder {

 /** Add order */
 public function order($data)
 {
     $post = array_merge(['key' => $this->api_key, 'action' => 'add'], $data);
     return json_decode($this->connect($post));
 }

 /** Get order status  */
 public function status($order_id)
 {
     return json_decode(
         $this->connect([
             'key' => $this->api_key,
             'action' => 'status',
             'order' => $order_id
         ])
     );
 }

 /** Get orders status */
 public function multiStatus($order_ids)
 {
     return json_decode(
         $this->connect([
             'key' => $this->api_key,
             'action' => 'status',
             'orders' => implode(",", (array)$order_ids)
         ])
     );
 }

 /** Get services */
 public function services()
 {
     return json_decode(
         $this->connect([
             'key' => $this->api_key,
             'action' => 'services',
         ])
     );
 }

 /** Refill order */
 public function refill(int $orderId)
 {
     return json_decode(
         $this->connect([
             'key' => $this->api_key,
             'action' => 'refill',
             'order' => $orderId,
         ])
     );
 }

 /** Refill orders */
 public function multiRefill(array $orderIds)
 {
     return json_decode(
         $this->connect([
             'key' => $this->api_key,
             'action' => 'refill',
             'orders' => implode(',', $orderIds),
         ]),
         true,
     );
 }

 /** Get refill status */
 public function refillStatus(int $refillId)
 {
      return json_decode(
         $this->connect([
             'key' => $this->api_key,
             'action' => 'refill_status',
             'refill' => $refillId,
         ])
     );
 }

 /** Get refill statuses */
 public function multiRefillStatus(array $refillIds)
 {
      return json_decode(
         $this->connect([
             'key' => $this->api_key,
             'action' => 'refill_status',
             'refills' => implode(',', $refillIds),
         ]),
         true,
     );
 }

 /** Cancel orders */
 public function cancel(array $orderIds)
 {
     return json_decode(
         $this->connect([
             'key' => $this->api_key,
             'action' => 'cancel',
             'orders' => implode(',', $orderIds),
         ]),
         true,
     );
 }

 /** Get balance */
 public function balance()
 {
     return json_decode(
         $this->connect([
             'key' => $this->api_key,
             'action' => 'balance',
         ])
     );
 }

 private function connect($post)
 {
     $_post = [];
     if (is_array($post)) {
         foreach ($post as $name => $value) {
             $_post[] = $name . '=' . urlencode($value);
         }
     }
 
     $args = array(
         'body'        => is_array($post) ? join('&', $_post) : '',
         'sslverify'   => false,
         'user-agent'  => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)'
     );
 
     $response = wp_remote_post($this->api_url, $args);
 
     if (is_wp_error($response)) {
         return false;
     }
 
     $result = wp_remote_retrieve_body($response);
     return $result;
 }

 public function api_order($service, $link, $quantity, $api_key, $api_url)
 {
     $data = [
         'service' => $service,
         'link' => $link,
         'quantity' => $quantity,
     ];

     // Merge additional parameters if needed

     // Set API key and URL
     $this->api_key = $api_key;
     $this->api_url = $api_url;

     // Perform order
     return $this->order($data);
 }

 public function api_order_comment($service, $link, $quantity, $api_key, $api_url, $comments)
 {   
     $data = [
         'service' => $service,
         'link' => $link,
         'comments' => $comments
     ];

     // Merge additional parameters if needed

     // Set API key and URL
     $this->api_key = $api_key;
     $this->api_url = $api_url;

     // Perform order
     return $this->order($data);
 }

 public function api_status($api_key, $api_url, $order_id)
 {
     // Set API key and URL
     $this->api_key = $api_key;
     $this->api_url = $api_url;
 
     // Perform status check
     return $this->status($order_id);
 }

 public function callMultiStatus(array $api_keys, array $api_urls, $order_ids)
 {
     $results = [];
     
     // Loop through each API key and API URL pair
     foreach (array_combine($api_keys, $api_urls) as $api_key => $api_url) {
         // Call multiStatus function for each pair
         $this->api_key = $api_key;
         $this->api_url = $api_url;
         $results[$api_key] = $this->multiStatus($order_ids);
     }
     
     return $results;
 }

 
 public function api_balance($api_key, $api_url)
 {
     // Set API key and URL
     $this->api_key = $api_key;
     $this->api_url = $api_url;
 
     // Perform status check
     return $this->balance();
 }

 public function api_services($api_key, $api_url)
 {  
    
    
    $this->api_key = $api_key;
    $this->api_url = $api_url;
    

    return $this->services();
 }






 public function multiStatusForMultipleAPIs($apiKeys, $apiUrls, $orderIds) {
    $results = [];

    foreach ($apiKeys as $apiKey) {
        foreach ($apiUrls as $apiUrl) {
            $tempResults = [];
            foreach ($orderIds as $orderId) {
                $result = $this->multiStatusForSingleAPI($apiKey, $apiUrl, $orderId);
                $tempResults[$orderId] = $result;
            }
            $results[] = ['api_key' => $apiKey, 'api_url' => $apiUrl, 'results' => $tempResults];
        }
    }

    return $results;
}

public function multiStatusForSingleAPI($apiKey, $apiUrl, $orderIds) {
    return json_decode(
        $this->connect([
            'key' => $apiKey,
            'action' => 'status',
            'orders' => implode(",", (array)$orderIds)
        ], $apiUrl)
    );
}


}

