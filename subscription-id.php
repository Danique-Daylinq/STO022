<?php

error_log('generate_cancel_url() called with: ID=' . print_r($subscription_id, true) . ', KEY=' . print_r($subscription_key, true));

// Get the Pronamic Subscription post by the meta key
$pronamic_subscription = get_posts([
  'post_type'  => 'pronamic_pay_subscription',
  'meta_key'   => '_pronamic_subscription_key',
  'meta_value' => $subscription_key,
  'numberposts' => 1,
]);

if (!empty($pronamic_subscription)) {
  $subscription_id = $pronamic_subscription[0]->ID;

  // Now use the correct function with proper ID + key
  $cancel_url = generate_cancel_url($subscription_id, $subscription_key);
} else {
  $cancel_url = '#'; // fallback
  error_log("Pronamic subscription not found for key: " . $subscription_key);
}



function generate_cancel_url($subscription_id, $subscription_key) {
  error_log('generate_cancel_url() called with: ID=' . print_r($subscription_id, true) . ', KEY=' . print_r($subscription_key, true));

  if (empty($subscription_id) || empty($subscription_key)) {
      error_log('Cancel URL Error - Missing subscription ID or key');
      return '#';
  }

  if (strpos($subscription_key, 'mp-sub-') !== false) {
      $subscription_key = str_replace('mp-sub-', '', $subscription_key);
  }

  if (strpos($subscription_key, 'subscr_') === false) {
      $subscription_key = 'subscr_' . $subscription_key;
  }

  $cancel_url = add_query_arg(
      [
          'subscription' => $subscription_id,
          'key'          => $subscription_key,
          'action'       => 'cancel',
      ],
      home_url('/')
  );

  error_log('Generated Cancel URL: ' . $cancel_url);
  return $cancel_url;
}



?>