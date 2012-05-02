<?php
/*
Plugin Name: Multisite Site Category
Plugin URI: https://github.com/brasofilo/multisite-site-category
Description: Add a custom meta option when registering new sites in WordPress Multisite.
Author: Rodolfo Buaiz
Author URI: http://rodbuaiz.com/
Version: 1.0
Stable Tag: 1.0
License: GPL
*/


/*
 * Workaround for adding the "site_category" option in previous sites.
 * URI: http://premium.wpmudev.org/forums/topic/update_blog_option-and-caching-problem
 */
//add_action('init','resetSitesOptions');
function resetSitesOptions() {
	global $wpdb, $site_id, $firephp;

	$query = "SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = $site_id";

	$blog_list = $wpdb->get_results($query, ARRAY_A);
	foreach ($blog_list as $blog) {
		update_blog_option($blog['blog_id'], 'site_category', '');
		/* or */
		// delete_blog_option( $blog['blog_id'], 'site_category');
	}
}


/*
 * Add new option when registering a site (back and front end)
 * URI: http://stackoverflow.com/a/10372861/1287812
*/
add_action('wpmu_new_blog', 'add_new_blog_field');

function add_new_blog_field($blog_id, $user_id, $domain, $path, $site_id, $meta) {
	$new_field_value = 'default';

	// Site added in the back end
	if (!empty($_POST['blog']['site_category'])) {
		switch_to_blog($blog_id);
		$new_field_value = $_POST['blog']['site_category'];
		update_option('site_category', $new_field_value);

		restore_current_blog();
	}
	// Site added in the front end
	elseif (!empty($meta['site_category'])) {
		$new_field_value = $meta['site_category'];
		update_option('site_category', $new_field_value);
	}
}


/*
 * Add new field in /wp-admin/network/site-new.php
 * has to be done with jQuery
 * URI: http://stackoverflow.com/a/10372861/1287812
*/
add_action("admin_print_scripts-site-new.php", 'my_admin_scripts');

function my_admin_scripts() {
	wp_register_script('yourScript', plugins_url('script.js', __FILE__));
	wp_enqueue_script('yourScript');
}


/*
 * Add new field in site signup form /wp-signup.php
 * URI: http://wordpress.stackexchange.com/a/50550/12615
*/
add_action('signup_blogform', 'add_extra_field_on_blog_signup');

function add_extra_field_on_blog_signup() {
	echo '
		<label>Site Category</label>
		<input type="text" name="site_category" value=""/>
	';
}

/*
 * Append the submitted value of our custom input into the meta array that is stored while the user doesn't activate
 * URI: http://wordpress.stackexchange.com/a/50550/12615
*/
add_filter('add_signup_meta', 'append_extra_field_as_meta');

function append_extra_field_as_meta($meta) {
	if (isset($_REQUEST['site_category'])) {
		$meta['site_category'] = $_REQUEST['site_category'];
	}
	return $meta;
}


/*
 * Insert item as first item in array
 */
function arrayPushAfterMS($src, $in, $pos) {
	if (is_int($pos)) $R = array_merge(array_slice($src, 0, $pos + 1), $in, array_slice($src, $pos + 1));
	else {
		foreach ($src as $k => $v) {
			$R[$k] = $v;
			if ($k == $pos) $R = array_merge($R, $in);
		}
	}
	return $R;
}

/*
 * Add custom columns (ID and Site Category) in Sites listing
 */

add_filter('wpmu_blogs_columns', 'get_id');
add_action('manage_sites_custom_column', 'add_columns', 10, 2);
add_action('manage_blogs_custom_column', 'add_columns', 10, 2);
add_action('admin_footer', 'add_style');


function add_columns($column_name, $blog_id) {
	if ('blog_id' === $column_name) {
		echo $blog_id;
	}
	if ('site_category' === $column_name) {
		$sitecat = get_blog_option($blog_id, 'site_category');
		echo $sitecat;
	}
	return $column_name;
}

function get_id($cols) {
	$in                    = array("blog_id" => "ID");
	$cols                  = arrayPushAfterMS($cols, $in, 0);
	$cols['site_category'] = __('Site Category');
	return $cols;
}

function add_style() {
	echo '<style>#blog_id { width:7%; } #site_category { width:20%; }</style>';
}

