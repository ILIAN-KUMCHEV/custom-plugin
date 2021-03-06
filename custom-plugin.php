<?php

/**
 * Plugin Name:       Custom-plugin
 * Plugin URI:        
 * Description:       
 * Version:           1.0
 * Author:            Ilian
 * Author URI:        https://www.upwork.com/freelancers/~0173657a6c53708b14?viewMode=1
 */

// plugin activation hook
register_activation_hook(__FILE__, 'mytable_activation');

// callback function to create table
function mytable_activation()
{
    global $wpdb;

    if ($wpdb->get_var("show tables like '" . create_my_table() . "'") != create_my_table()) {

        $mytable = 'CREATE TABLE `' . create_my_table() . '` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `field name` varchar(100) NOT NULL,
                            `value` varchar(100) NOT NULL,
                            `status` int(11) NOT NULL DEFAULT "1",
                            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`)
                          ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;';

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($mytable);
    }
}

// returns table name
function create_my_table()
{
    global $wpdb;
    return $wpdb->prefix . "mytable";
}

add_action( 'loop_start', 'before_single_post_content' );
function before_single_post_content() {
if ( is_singular( 'post') ) {
$cf = get_post_meta( get_the_ID(), 'NEWTEXT', true );
if( ! empty( $cf ) ) {
echo '<div class="before-content">'. $cf .'</div>';
    }
  }
}


// =============Add Custom Meta Box========

function add_my_custom_text() {
   add_meta_box('my_custom_text_id', 'Custom Text', 'my_custom_text_field', 'post', 'normal', 'high');
}

function my_custom_text_field(){
   global $post;
   $get_all_meta_values = get_post_custom($post->ID);
   $custom_text=$get_all_meta_values["custom_text"][0];
   echo '
   <input type="text" name="custom_text" size="100" value="'.$custom_text.'" />';
}

add_action('admin_init', 'add_my_custom_text' );

function save_link(){
   global $post;
   update_post_meta($post->ID, "custom_text", $_POST["custom_text"]);
}


// =============Save metabox info at the new created table========

function save_custom_fields() {
    $ids=get_the_id();
    $text=$_POST['custom_text'];
    $fieldname='Custom Text';
    
    global $wpdb;
    $wpdb -> insert(
       $wpdb->prefix . "mytable",
    [
        'id'=>$ids,
        'field name'=>$fieldname,
        'value'=>$text
        ]
        );

}
add_action( 'save_post', 'save_custom_fields' );


// =============Update metabox value to the new created table when its changed via wp backend of the post========
function update_custom_fields() {
    $ids=get_the_id();
    $text=$_POST['custom_text'];
    $fieldname='Custom Text';
    
    global $wpdb;
    $wpdb -> update(
       $wpdb->prefix . "mytable",
    [ 
        'value'=>$text
        ],
     [
        'id'=>$ids
    
        ]   
        );
}

add_action( 'save_post', 'update_custom_fields' );

// =============Create custom json endpoint ========
 
function register_api_endpoints() {
  register_rest_route('custom', '/endpoint', array(
    'methods' => 'GET',
    'callback' => 'get_post_items',
  ) );
}
 
add_action( 'rest_api_init', 'register_api_endpoints' );


function get_post_items() {
  global $wpdb;
  $db=$wpdb->prefix . "mytable";
  $newtext=$wpdb -> get_var("SELECT `value` FROM $db");   
    
  $args = array (
    'post_status' => 'publish'
  );
 
  $items = array();
  if ( $posts = get_posts( $args ) ) {
    foreach ( $posts as $post ) {
         
      $items[] = array(
        'id' => $post->ID,
        'title' => $post->post_title,
        'myfield' =>$newtext
      );
    }
  }
  return $items;
}