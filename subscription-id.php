<?php

error_log('My Pronamic child theme update is working!');

function get_id() {
    $subscription_id = add_query_arg(
        [
            'subscription' => $this->get_id(),

        ],
    );

    return $subscription_id;
}


function generate_cancel_url($subscription_id, $subscription_key) {
    if (empty($subscription_id) || empty($subscription_key)) {
      error_log('Cancel URL Error - Missing subscription ID or key');
      return '#'; // Return a placeholder URL if parameters are missing
    }
  
    if (strpos($subscription_key, 'mp-sub-') !== false) {
      $subscription_key = str_replace('mp-sub-', '', $subscription_key);
    }
    
    if (strpos($subscription_key, 'subscr_') === false) {
      $subscription_key = 'subscr_' . $subscription_key; // Prefix with 'subscr_' if missing
    }
    // Generate the cancel URL
    $cancel_url = add_query_arg(
      [
        'subscription' => $subscription_id,
        'key'          => $subscription_key,
        'action'       => 'cancel',
      ],
      home_url('/') // Base URL
    );
  
    // Log the generated URL for debugging
    error_log('Generated Cancel URL: ' . $cancel_url);
  
    return $cancel_url;
  }


?>