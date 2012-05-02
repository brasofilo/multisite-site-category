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
 * Usage: enable the action, load any page, disable it
 * URI: http://premium.wpmudev.org/forums/topic/update_blog_option-and-caching-problem
 */
if(!function_exists('resetSitesOptions')) {
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
}


/*
 * Credits:
 * The plugin template was created with WordPress Plugin Template Creator
 * URI: http://soderlind.no/wordpress-plugin-template-creator/
 */

if (!class_exists('b_multisite_site_category')) {
	class b_multisite_site_category {
		/**
		 * @var string $localizationDomain Domain used for localization
		 */
		var $localizationDomain = "b_msc";

		/**
		 * @var string $url The url to this plugin
		 */
		var $url = '';
		/**
		 * @var string $urlpath The path to this plugin
		 */
		var $urlpath = '';

		//Class Functions
		/**
		 * PHP 4 Compatible Constructor
		 */
		function b_multisite_site_category(){$this->__construct();}

		/**
		 * PHP 5 Constructor
		 */
		function __construct(){
			//Language Setup
			//$locale = get_locale();
			//$mo = plugins_url("/languages/" . $this->localizationDomain . "-".$locale.".mo", __FILE__);
			//load_textdomain($this->localizationDomain, $mo);

			//"Constants" setup
			$this->url = plugins_url(basename(__FILE__), __FILE__);
			$this->urlpath = plugins_url('', __FILE__);

			//Actions
			add_action("init", array(&$this,"b_multisite_site_category_init"));

			add_action('wpmu_new_blog', array(&$this,'add_new_blog_field'));
			add_action("admin_print_scripts-site-new.php", array(&$this,'my_admin_scripts'));
			add_action('signup_blogform', array(&$this,'add_extra_field_on_blog_signup'));
			add_filter('add_signup_meta', array(&$this,'append_extra_field_as_meta'));
			add_filter('wpmu_blogs_columns', array(&$this,'get_id'));
			add_action('manage_sites_custom_column', array(&$this,'add_columns'), 10, 2);
			add_action('manage_blogs_custom_column', array(&$this,'add_columns'), 10, 2);
			add_action('admin_footer-sites.php', array(&$this,'add_style'));
		}

		function b_multisite_site_category_init() {

		}

		/*
		 * Add new option when registering a site (back and front end)
		 * URI: http://stackoverflow.com/a/10372861/1287812
		*/
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
		function my_admin_scripts() {
			$locale = get_locale();
			if('pt_BR' === $locale) {
				wp_register_script('b_msc', plugins_url('js/script-pt.js', __FILE__));
			} elseif('es_ES' === $locale) {
				wp_register_script('b_msc', plugins_url('js/script-es.js', __FILE__));
			} else {
				wp_register_script('b_msc', plugins_url('js/script.js', __FILE__));
			}
			wp_enqueue_script('b_msc');
		}


		/*
		 * Add new field in site signup form /wp-signup.php
		 * URI: http://wordpress.stackexchange.com/a/50550/12615
		*/
		function add_extra_field_on_blog_signup() {
			$txt = __('Category');
			echo <<<HTML
		<label>$txt</label>
		<input type="text" name="site_category" value=""/>
HTML;
		}

		/*
		 * Append the submitted value of our custom input into the meta array that is stored while the user doesn't activate
		 * URI: http://wordpress.stackexchange.com/a/50550/12615
		*/
		function append_extra_field_as_meta($meta) {
			if (isset($_REQUEST['site_category'])) {
				$meta['site_category'] = $_REQUEST['site_category'];
			}
			return $meta;
		}


		/*
		 * Insert $in item in position $pos inside the $src array
		 */
		function arrayPushAfter($src, $in, $pos) {
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
			$cols                  = $this->arrayPushAfter($cols, $in, 0);
			$cols['site_category'] = __('Category');
			return $cols;
		}

		function add_style() {
			echo '<style>#blog_id { width:7%; } #site_category { width:20%; }</style>';
		}
	} //End Class
} //End if class exists statement



if (class_exists('b_multisite_site_category')) {
	$b_multisite_site_category_var = new b_multisite_site_category();
}


