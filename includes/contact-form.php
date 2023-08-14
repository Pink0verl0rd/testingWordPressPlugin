<?php

if( !defined('ABSPATH') ){

    die('You cannot be here');

}

add_shortcode('contact','show_contact_form'); // Creates the shortCode

add_action( 'rest_api_init', 'create_rest_endpoint'); //Creates a endpoint for the form to send the data the user inputs

add_action( 'init', 'create_submissions_page'); //Creates the submissions page

add_action( 'add_meta_boxes', 'create_meta_box'); // Creates the custom columns for the submissions page in respect to the data grab from the from

add_filter( 'manage_submission_posts_columns', 'custom_submission_columns'); // Defines the columns used for our custom post type

add_action('manage_submission_posts_custom_column', 'fill_submission_columns', 10, 2); // Injects the data into the custom post types to be displayed in the submissions page

add_action( 'admin_init', 'setup_search');

add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');

function enqueue_custom_scripts()
{

      // Enqueue custom css for plugin

      wp_enqueue_style('contact-form-plugin', MY_PLUGIN_URL . 'assets/css/contact-plugin.css');
}


function setup_search(){
          // Only apply filter to submissions page

          global $typenow;

          if ($typenow === 'submission') {
    
                add_filter('posts_search', 'submission_search_override', 10, 2);
          }

}

function submission_search_override($search, $query){
    // Override the submissions page search to include custom meta data

    global $wpdb;

    if ($query->is_main_query() && !empty($query->query['s'])) {
        $sql    = "
            or exists (
                select * from {$wpdb->postmeta} where post_id={$wpdb->posts}.ID
                and meta_key in ('name','email','phone')
                and meta_value like %s
            )
        ";
        $like   = '%' . $wpdb->esc_like($query->query['s']) . '%';
        $search = preg_replace(
                "#\({$wpdb->posts}.post_title LIKE [^)]+\)\K#",
                $wpdb->prepare($sql, $like),
                $search
        );
    }

    return $search;
}
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
    /*unset($postmetas['_edit_lock']);
    echo '<ul>';
    foreach($postmetas as $key => $value)
    {
        echo '<li><strong>' . ucfirst($key) . " :</strong><br /> " . $value[0] . '</li>';
    }
    echo '</ul>';

    echo '<strong>Fixed Method Name : </strong><br />' . get_post_meta(get_the_ID(),'name',true);*/

    echo '<ul>';

    echo '<li><strong>Name:</strong><br /> ' . esc_html(get_post_meta(get_the_ID(), 'name', true)) . '</li>';
    echo '<li><strong>Email:</strong><br /> ' . esc_html(get_post_meta(get_the_ID(), 'email', true)) . '</li>';
    echo '<li><strong>Phone:</strong><br /> ' . esc_html(get_post_meta(get_the_ID(), 'phone', true)) . '</li>';
    echo '<li><strong>Message:</strong><br /> ' . esc_html(get_post_meta(get_the_ID(), 'message', true)) . '</li>';

    echo '</ul>';
}

function create_submissions_page(){

    $args = [

        'public' => true,
        'has_archive' => true,
        'menu_position' => 79,
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
    // Get all paramas
    $params = $data->get_params();

    // Set field names
    $field_name = sanitize_text_field( $params['name'] );
    $field_email = sanitize_email( $params['email'] );
    $field_phone = sanitize_text_field( $params['phone'] );
    $field_message = sanitize_textarea_field( $params['message'] );


    if( !wp_verify_nonce( $params['_wpnonce'], 'wp_rest' )){
        return new WP_Rest_Response('Message not sent',422);
    }
    unset($params['_wpnonce']);
    unset($params['_wp_http_referer']);

    // send email after this lol
    $headers = [];
    $recipient_email = get_plugin_options('contact_plugin_recipients');
    $admin_email = $recipient_email ? $recipient_email : get_bloginfo('admin_email');
    $admin_name = get_bloginfo('name');
    
    $headers[] = "From: {$admin_name} <{$admin_email}>";    
    $headers[] = "Reply-to: {$field_name} <{$field_email}>";
    $headers[] = "Content-type : html";

    $subject = "New enquiry from {$field_name}";

    $message = '';
    $message .= "Message has been sent from {$field_name} <br /> <br />";


    
    $postarr = [
        'post_title' => $field_name,
        'post_type' =>  'submission',
        'post_status' => 'publish'
    ];

    $post_id = wp_insert_post($postarr);

    foreach ($params as $label => $value){

        switch($label)
        {
            case 'message':
                $value = sanitize_textarea_field($value);
            break;
            case'email':
                $value = sanitize_email($value );
            break;
            default:
                $value = sanitize_text_field($value);
        }
        add_post_meta( $post_id, sanitize_text_field($label), sanitize_text_field($value));

        $message .=  sanitize_text_field(ucfirst($label)) . ':' . $value;
    }

    wp_mail( $admin_email, $subject, $message, $headers);

    // Set confirmation message
    $confirmation_message = str_replace('{name}',$field_name, get_plugin_options('contact_plugin_message') ) ? get_plugin_options('contact_plugin_message') : "The message was sent successfully";

    return new WP_REST_Response($confirmation_message,200);
}