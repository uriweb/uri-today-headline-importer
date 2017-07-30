<?php
/**
 * custom option and settings
 * @see https://developer.wordpress.org/plugins/settings/using-settings-api/
 * @author John Pennypacker <jpennypacker@uri.edu>
 */

/**
 * Register settings
 */
function uri_today_settings_init() {
	// register a new setting for "uri_today" page
	register_setting(
		'uri_today',
		'uri_today_domain',
		'uri_today_sanitize_domain'
	);

	register_setting(
		'uri_today',
		'uri_today_remote_tags',
		'uri_today_sanitize_ints'
	);

	register_setting(
		'uri_today',
		'uri_today_local_category',
		'uri_today_sanitize_int'
	);

	register_setting(
		'uri_today',
		'uri_today_post_status',
		'uri_today_sanitize_post_status'
	);

	register_setting(
		'uri_today',
		'uri_today_oldest_date',
		'uri_today_sanitize_date'
	);

	// register a new section in the "uri_today" page
	add_settings_section(
		'uri_today_settings',
		__( 'URI Today Importer Settings', 'uri_today' ),
		'uri_today_settings_section',
		'uri_today'
	);

	// register field
	add_settings_field(
		'uri_today_domain', // id: as of WP 4.6 this value is used only internally
		__( 'Domain', 'uri_today' ), // title
		'uri_today_domain_field', // callback
		'uri_today', // page
		'uri_today_settings', //section
		array( //args
			'label_for' => 'uri-today-field-domain',
			'class' => 'uri_today_row',
		)
	);
	add_settings_field(
		'uri_today_remote_tags', // id: as of WP 4.6 this value is used only internally
		__( 'Tags', 'uri_today' ), // title
		'uri_today_remote_tags_field', // callback
		'uri_today', // page
		'uri_today_settings', //section
		array( //args
			'label_for' => 'uri-today-field-remote-tags',
			'class' => 'uri_today_row',
		)
	);
	add_settings_field(
		'uri_today_oldest_date', // id: as of WP 4.6 this value is used only internally
		__( 'Oldest Post', 'uri_today' ), // title
		'uri_today_oldest_date_field', // callback
		'uri_today', // page
		'uri_today_settings', //section
		array( //args
			'label_for' => 'uri-today-field-oldest-date',
			'class' => 'uri_today_row',
		)
	);
	add_settings_field(
		'uri_today_local_category', // id: as of WP 4.6 this value is used only internally
		__( 'New Post Category', 'uri_today' ), // title
		'uri_today_local_category_field', // callback
		'uri_today', // page
		'uri_today_settings', //section
		array( //args
			'label_for' => 'uri-today-field-local-category',
			'class' => 'uri_today_row',
		)
	);
	add_settings_field(
		'uri_today_post_status', // id: as of WP 4.6 this value is used only internally
		__( 'New Post Status', 'uri_today' ), // title
		'uri_today_post_status_field', // callback
		'uri_today', // page
		'uri_today_settings', //section
		array( //args
			'label_for' => 'uri-today-field-post-status',
			'class' => 'uri_today_row',
		)
	);

}
 
/**
 * register our uri_today_settings_init to the admin_init action hook
 */
add_action( 'admin_init', 'uri_today_settings_init' );
 

/**
 * Callback for a settings section
 * @param arr $args has the following keys defined: title, id, callback.
 *  title is the display title of the section, 
 *  id is a string like 'uri_today_section_endpoint'
 *  callback is a function name (this particular function as it turns out)
 * @see add_settings_section()
 */
function uri_today_settings_section( $args ) {
	$intro = 'URI Today importer automatically retrieves news artices from URI Today and saves them as posts in this website.';
	echo '<p id="' . esc_attr( $args['id'] ) . '">' . esc_html_e( $intro, 'uri_today' ) . '</p>';
}
 

/**
 * Field callback
 * outputs the field
 * @see add_settings_field()
 * @param $args
 *  wordpress has magic interaction with the following keys: label_for, class.
 *  the "label_for" key value is used for the "for" attribute of the <label>.
 *  the "class" key value is used for the "class" attribute of the <tr> containing the field.
 */
function uri_today_domain_field( $args ) {
	// get the value of the setting we've registered with register_setting()
	$setting = get_option( 'uri_today_domain' );
	// output the field
	?>
		<input type="text" class="regular-text" aria-describedby="url-description" name="uri_today_domain" id="uri-today-field-domain" value="<?php print ($setting!==FALSE) ? esc_attr($setting) : ''; ?>">
		<p class="url-description">
			<?php
				esc_html_e( 'Enter the domain of where the posts are coming from, omit trailing slash.', 'uri_today' );
				echo '<br />';
				esc_html_e( 'e.g. https://today.uri.edu', 'uri_today' );
			?>
		</p>
	<?php
}

/**
 * Field callback
 * outputs the field
 * @see add_settings_field()
 * @see uri_today_field_domain_callback()
 */
function uri_today_remote_tags_field( $args ) {
	// get the value of the setting we've registered with register_setting()
	$setting = get_option( 'uri_today_remote_tags' );
	// output the field
	?>
		<input type="text" class="regular-text" aria-describedby="url-description" name="uri_today_remote_tags" id="uri-today-field-remote-tags" value="<?php print ($setting!==FALSE) ? esc_attr($setting) : ''; ?>">
		<p class="url-description">
			<?php
				esc_html_e( 'Enter the IDs of the tags you want to import. Separate multiple IDs with commas.', 'uri_today' );
				echo '<br />';
				esc_html_e( 'Ask Web Communications if you don\'t know what Tag IDs to use.', 'uri_today' );
				echo '<br />';
				esc_html_e( 'e.g. 231,420', 'uri_today' );
			?>
		</p>
	<?php
}

/**
 * Field callback
 * outputs the field
 * @see add_settings_field()
 * @see uri_today_field_domain_callback()
 */
function uri_today_oldest_date_field( $args ) {
	// get the value of the setting we've registered with register_setting()
	$setting = get_option( 'uri_today_oldest_date' );
	// output the field
	?>
		<input type="text" class="regular-text" aria-describedby="oldest-date-description" name="uri_today_oldest_date" id="uri-today-field-oldest-date" value="<?php print ($setting!==FALSE) ? esc_attr($setting) : ''; ?>">
		<p class="oldest-date-description">
			<?php
				esc_html_e( 'Only import posts newer than the date specified here.', 'uri_today' );
				echo '<br />';
				esc_html_e( 'e.g. 2017-05-25', 'uri_today' );
			?>
		</p>
	<?php
}


/**
 * Field callback
 * outputs the field
 * @see add_settings_field()
 * @see uri_today_field_domain_callback()
 */
function uri_today_local_category_field( $args ) {
	// get the value of the setting we've registered with register_setting()
	$setting = get_option( 'uri_today_local_category' );
// 	value="<?php print ($setting!==FALSE) ? esc_attr($setting) : ''; 
	$categories = get_categories();
	//echo '<pre>cats:', print_r($categories, TRUE), '</pre>';
	
	// output the field
	?>
		<select aria-describedby="url-description" name="uri_today_local_category" id="uri-today-field-local-category">
			<option>Choose One</option>
			<?php
				foreach($categories as $c) {
					$selected = ($setting !== FALSE && $setting == $c->term_id) ? ' selected' : '';
					echo '<option value="' . $c->term_id . '"' . $selected . '>' . $c->name . '</option>';
				}
			?>
		</select>
		<p class="category-description">
			<?php
				esc_html_e( 'New posts will be saved in this website with the selected category.', 'uri_today' );
			?>
		</p>
	<?php
}


/**
 * Field callback
 * outputs the field
 * @see add_settings_field()
 * @see uri_today_field_domain_callback()
 */
function uri_today_post_status_field( $args ) {
	// get the value of the setting we've registered with register_setting()
	$setting = get_option( 'uri_today_post_status' );

	// output the field
	?>
		<select aria-describedby="post-status-description" name="uri_today_post_status" id="uri-today-field-post-status">
			<option value="draft">Draft</option>
			<option value="published">Published</option>
		</select>
		<p class="post-status-description">
			<?php
				esc_html_e( 'New posts will be saved with the selected status.', 'uri_today' );
			?>
		</p>
	<?php
}




/**
 * Sanitize the input of an int
 */
function uri_today_sanitize_int( $value ) {
	return intval( $value );
}

/**
 * Sanitize the input of ints separated by commas
 */
function uri_today_sanitize_ints( $value ) {
	$output = array();

	if(strpos($value, ',') !== FALSE) {
		$a = explode(',', $value);
		foreach($a as $i) {
			$v = intval($i);
			if($v > 0) {
				$output[] = $v;
			}
		}
	} else {
		$v = intval($value);
		if($v > 0) {
			$output[] = $v;
		}
	}

	return implode( ',', $output );
}

/**
 * Sanitize the post state (either published or draft)
 */
function uri_today_sanitize_post_status( $value ) {
	if( strtolower($value) == 'draft') {
		return 'draft';
	}	
	if(strtolower($value) == 'published') {
		return 'published';
	}
}

/**
 * Sanitize the date
 */
function uri_today_sanitize_date( $value ) {
	return date('Y-m-d', strtotime($value));
}


/**
 * Sanitize the input of the URL field
 * @todo: when input fails validation, the data is sanitized 
 *   but the update message displays twice.  It should display just once.
 */
function uri_today_sanitize_domain( $value ) {
	$safe = sanitize_text_field( $value );

	if($safe !== $value) {
		$message = 'The domain failed validation, it was sanitized before it was saved. Doublecheck it.';
	} else {
		if ( isset( $_GET['settings-updated'] ) ) {
			$message = 'Settings Saved.';
		}
	}
	
	if( isset ( $message ) ) {
		add_settings_error(
			'uri_today_messages',
			'uri_today_message',
			__( $message, 'uri_today' ),
			'updated'
		);
	}

	return $safe;
}

/**
 * Add the settings page to the settings menu
 * @see https://developer.wordpress.org/reference/functions/add_options_page/
 */
function uri_today_settings_page() {
	add_options_page(
		__( 'URI Today Importer Settings', 'uri_today' ),
		__( 'URI Today Importer', 'uri_today' ),
		'manage_options',
		'uri-today-settings',
		'uri_today_settings_page_html'
	);
}

/**
 * register our uri_today_settings_page to the admin_menu action hook
 */
add_action( 'admin_menu', 'uri_today_settings_page' );




/**
 * menu callback.
 * renders the HTML on the settings page
 */
function uri_today_settings_page_html() {
	// check user capabilities
	// on web.uri, we have to leave this pretty loose
	// because web com doesn't have admin privileges.
	if ( ! current_user_can( 'manage_options' ) ) {
		echo '<div id="setting-message-denied" class="updated settings-error notice is-dismissible"> 
<p><strong>You do not have permission to save this form.</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
		return;
	}



	if ( isset( $_GET['settings-updated'] ) ) {
// 		clear the cache when the form is saved.
// 		uri_today_fetch();
// 		add_settings_error(
// 			'uri_today_messages',
// 			'uri_today_cache_message',
// 			__( 'The local cache of headlines was emptied and refreshed.', 'uri_today' ),
// 			'updated' );
		// display messages
		settings_errors( 'uri_today_messages' );
		uri_today_fetch_posts();
	}
	?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
					// output security fields for the registered setting "uri_today"
					settings_fields( 'uri_today' );
					// output setting sections and their fields
					// (sections are registered for "uri_today", each field is registered to a specific section)
					do_settings_sections( 'uri_today' );
					// output save settings button
					submit_button( 'Save Settings' );
				?>
			</form>
		</div>
	<?php
}