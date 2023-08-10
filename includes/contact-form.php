<?php

add_shortcode('contact','show_contact_form');
add_action( 'rest_api_init', 'create_rest_endpoint');
function show_contact_form(){
    include MY_PLUGIN_PATH . '/includes\templates\contact-form.php';
}
    
function create_rest_endpoint(){

    register_rest_route( 'v1/contact-form', 'submit', array(
        'methods' => 'POST',
        'callback' => 'handle_enquiry'
    ));
}

function handle_enquiry($data){
    $params = $data->get_params();
    if( !wp_verify_nonce( $params['_wpnonce'], 'wp_rest' )){
        return new WP_Rest_Response('Message not sent',422);
    }
    unset($params['_wpnonce']);
    unset($params['_wp_http_referer']);

    // send email after this lol
    $headers = [];
    $admin_email = get_bloginfo('admin_email');
    $admin_name = get_bloginfo('name');
    
    $headers[] = "From: {$admin_name} <{$admin_email}>";
    $headers[] = "Reply-to: {$params['name']} <{$params['email']}>";
    $headers[] = "Content-type : html";

    $subject = "New enquiry from {$params['name']}";

    $message = '';
    $message .= "Message has been sent from {$params['name']} <br /> <br />";

    foreach ($params as $label => $value){
        $message .=  ucfirst($label) . ':' . $value;
    }

    wp_mail( $admin_email, $subject, $message, $headers);
    return new WP_REST_Response('The Message was sent',200);
}