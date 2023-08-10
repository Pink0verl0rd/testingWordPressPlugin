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

function handle_enquiry(){
    echo 'hello there';
}