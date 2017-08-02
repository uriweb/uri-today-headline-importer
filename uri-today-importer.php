<?php
/*
Plugin Name: URI Today Importer
Plugin URI: http://www.uri.edu
Description: Create news posts from a URI Today feed.
Version: 1.0
Author: John Pennypacker
Author URI: 
*/

// Block direct requests
if ( !defined('ABSPATH') )
	die('-1');

// set up the admin settings screen
include_once( 'inc/uri-today-settings.php' );


/**
 * Retrieves posts from URI Today, then processes them to be inserted into the database.
 */
function uri_today_fetch_posts() {
	// 2 is news
	// 231 'nursing' or 420 'college of nursing'
	// 981 commencement
	
	$domain = get_option('uri_today_domain', 'https://today.uri.edu');
	$tags = get_option( 'uri_today_remote_tags' );
	$after = get_option( 'uri_today_oldest_date', FALSE );
	
	if(empty($tags)) { // do nothing unless the admin configured the plugin
		return FALSE;
	}
	
	$url = $domain . '/wp-json/wp/v2/posts?per_page=10&tags=' . $tags . '&categories=2';
	if($after !== FALSE) {
		$url .= '&after=' . $after . 'T00:00:00';
	}
	$data = file_get_contents($url);
	$data = json_decode($data);
	
	uri_today_process_posts($data);	
	
}
// uri_today_headlines_hook is a cron job created by this plugin
add_action( 'uri_today_headlines_hook', 'uri_today_fetch_posts' );
// init runs on each load, useful for debugging, less useful for performance.
//add_action('init', 'uri_today_fetch_posts');



/**
 * Adds a post to the WP database
 * @param arr $post
 */
function uri_today_importer_add_post($post) {

	$category_id = get_option( 'uri_today_local_category' );
	$post_status = get_option( 'uri_today_post_status', 'draft' );

	//echo '<pre>cat: ', print_r($category_id, TRUE), '</pre>';

	// prime the new post array
	$new_post = array(
			'post_title'    => $post['post_title'],
			'post_content'  => $post['post_excerpt'],
			'post_excerpt'  => $post['post_excerpt'],
			'post_status'   => $post_status,
			'post_date'     => $post['post_date'],
			//'post_author'   => get_current_user_id(),
			'post_type'     => 'post',
			'post_category' => array($category_id),
			'meta_input'   => array(
				'_links_to' => $post['links_to'],
				'_uri_today_post_id' => $post['post_id']
			)
	);

	// check to see if we've already got this postname
	// @todo: what if the post has been imported multiple times?
	$post_id = 0;
	$args = array(
		'posts_per_page'   => 1,
		'orderby'          => 'post_date',
		'order'            => 'DESC',
		'post_status'      => 'any',
		'meta_query' => array(
			array(
				'key'     => '_uri_today_post_id',
				'value'   => $post['post_id'],
				'compare' => '=',
			),
		),
	);
	$existing_post = get_posts( $args );
	$post_id = $existing_post[0]->ID;


	
	if( $post_id ) {
		// update post
		wp_update_post( $new_post );
	} else {
		// insert post
		$post_id = wp_insert_post( $new_post );
	}

	$post['ID'] = $post_id;

	// add featured media (if it exists)
	if( isset( $post['image_square'] ) && ! has_post_thumbnail( $post_id ) ) {
// 		echo '<pre>would upload</pre>';
// 		echo '<pre>', print_r($post, TRUE), '</pre>';
		uri_today_add_media($post);
	}
	
}




/**
 * Adds a media file to a post if there is one
 * @param arr $post
 */
function uri_today_add_media($post) {
	$wp_upload_dir = wp_upload_dir();
	//echo '<pre>', print_r($wp_upload_dir, TRUE), '</pre>';
	
	if(isset($post['image_square'])) {


		// $filename should be the path to a file in the upload directory.
		$filename = $wp_upload_dir['path'] . '/' . $post['image_square']->file;
		
		// apparently, we have to write the file data ourselves... seems odd.
		file_put_contents($filename, file_get_contents($post['image_square']->source_url));

		// Prepare an array of post data for the attachment.
		$attachment = array(
			'guid'           => $wp_upload_dir['url'] . '/' . $post['image_square']->file, 
			'post_mime_type' => $post['image_square']->mime_type,
			'post_title'     => $post['image_square']->title,
			'post_content'   => '',
			'post_status'    => 'inherit'
		);


		// Insert the attachment.
		$attach_id = wp_insert_attachment( $attachment, $filename, $post['ID'] );

		// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// Generate the metadata for the attachment, and update the database record.
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		
		// add the alt attribute
		update_post_meta( $attach_id, '_wp_attachment_image_alt', $post['image_square']->alt_text );

		set_post_thumbnail( $post['ID'], $attach_id );
	}


}




/**
 * Process a JSON post object and convert it to an array
 * @param arr $data
 */
function uri_today_process_posts($data) {
	
	if(!is_array($data)) {
		return FALSE;
	}
	
	foreach($data as $post) {
	
		$staged_post = array();
		
		$staged_post['post_title'] = $post->title->rendered;
		$staged_post['links_to'] = $post->link;
		$staged_post['post_date'] = $post->date;
		$staged_post['post_id'] = $post->id;
		// use the lead field first, fallback to the excerpt 
		// excerpt is typically auto generated from body and includes the date line.  boo.
		$staged_post['post_excerpt'] = trim($post->post_meta_fields->lead[0]);
		if(empty($staged_post['post_excerpt'])) {
			$staged_post['post_excerpt'] = $post->excerpt->rendered;
		}

		$image_square_id = $post->post_meta_fields->image_square[0];	
		if(isset($image_square_id)) {
			$url = 'https://today.uri.edu/wp-json/wp/v2/media/' . $image_square_id;
			$image_data = file_get_contents($url);
			$image_data = json_decode($image_data);
			$staged_post['image_square'] = $image_data->media_details->sizes->thumbnail;
			$staged_post['image_square']->title = $image_data->title->rendered;
			$staged_post['image_square']->alt_text = $image_data->alt_text;
			$staged_post['image_square']->caption = $image_data->caption->rendered;
		}

		uri_today_importer_add_post($staged_post);
	
	}
}




/**
 * Activates this cron job
 */
function uri_today_schedule() {
	if( !wp_next_scheduled( 'uri_today_import_hook' ) ) {
		wp_schedule_event( time(), 'twicedaily', 'uri_today_import_hook' );
	}
} // end uri_today_schedule()
register_activation_hook( __FILE__, 'uri_today_schedule' );

/**
 * Deactivates future instances of this cron job
 */
function uri_today_deactivate() {
	$timestamp = wp_next_scheduled( 'uri_today_import_hook' );
	wp_unschedule_event($timestamp, 'uri_today_import_hook' );
}
register_deactivation_hook( __FILE__, 'uri_today_deactivate' );


