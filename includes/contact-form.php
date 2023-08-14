<?php

add_shortcode('contact','show_contact_form'); // Creates the shortCode

add_action( 'rest_api_init', 'create_rest_endpoint'); //Creates a endpoint for the form to send the data the user inputs

add_action( 'init', 'create_submissions_page'); //Creates the submissions page

add_action( 'add_meta_boxes', 'create_meta_box'); // Creates the custom columns for the submissions page in respect to the data grab from the from

add_filter( 'manage_submission_posts_columns', 'custom_submission_columns'); // Defines the columns used for our custom post type

add_action('manage_submission_posts_custom_column', 'fill_submission_columns', 10, 2); // Injects the data into the custom post types to be displayed in the submissions page

function fill_submission_columns($column,$post_id){
    switch ($column) {
        case 'name':
              echo esc_html(get_post_meta($post_id, 'name', true));
              break;

        case 'email':
              echo esc_html(get_post_meta($post_id, 'email', true));
              break;

        case 'phone':
              echo esc_html(get_post_meta($post_id, 'phone', true));
              break;

        case 'message':
              echo esc_html(get_post_meta($post_id, 'message', true));
              break;
  }
}

function custom_submission_columns($columns){
    $columns = array(
        'cb' => $columns['cb'],
        'name' => __('Name','contact-plugin'),
        'email' => __('Email','contact-plugin'),
        'phone' => __('Phone','contact-plugin'),
        'message' => __('Message','contact-plugin'),
    );
    return $columns;
}

function create_meta_box() {
    add_meta_box('custom_contact_form','Submission','display_submission', 'submission');
}

function display_submission() {
    $postmetas = get_post_meta(get_the_ID());
    unset($postmetas['_edit_lock']);
    echo '<ul>';
    foreach($postmetas as $key => $value)
    {
        echo '<li><strong>' . ucfirst($key) . " :</strong><br /> " . $value[0] . '</li>';
    }
    echo '</ul>';

    echo '<strong>Fixed Method Name : </strong><br />' . get_post_meta(get_the_ID(),'name',true);
}

function create_submissions_page(){

    $args = [

        'public' => true,
        'has_archive' => true,
        'menu_position' => 30,
        'publicly_queryable' => false,
        'labels' => [

              'name' => 'Submissions',
              'singular_name' => 'Submission',
              'edit_item' => 'View Submission'

        ],
        'supports' => false,
        'capability_type' => 'post',
        'capabilities' => array(
              'create_posts' => false,
        ),
        'map_meta_cap' => true
  ];

    register_post_type('submission' , $args);
}

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


    
    $postarr = [
        'post_title' => $params['name'],
        'post_type' =>  'submission',
        'post_status' => 'publish'
    ];

    $post_id = wp_insert_post($postarr);

    wp_mail( $admin_email, $subject, $message, $headers);



    foreach ($params as $label => $value){
        $message .=  ucfirst($label) . ':' . $value;
        add_post_meta( $post_id, $label, $value);
    }



    return new WP_REST_Response('The Message was sent',200);
}