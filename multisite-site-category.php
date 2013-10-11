<?php

/**
 * Plugin Name: Multisite Site Category
 * Plugin URI: https://github.com/brasofilo/multisite-site-category
 * Description: Add a custom meta option when registering new sites in WordPress Multisite.
 * Network: true
 * Author: Rodolfo Buaiz
 * Author URI: http://rodbuaiz.com/
 * Version: 2013.10.11
 * License: GPLv2 or later
 * 
 */

/*
Multisite Site Category
Copyright (C) 2013  Rodolfo Buaiz

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


# BUSTED!
!defined( 'ABSPATH' ) AND exit(
                "<pre>Hi there! I'm just part of a plugin, <h1>&iquest;what exactly are you looking for?"
);

# ACTIVATION
register_activation_hook(
        __FILE__, array( 'B5F_Multisite_Categories', 'on_activation' )
);

# INIT
add_action(
        'plugins_loaded', array( B5F_Multisite_Categories::get_instance(), 'plugin_setup' )
);

class B5F_Multisite_Categories
{
    /**
     * Plugin instance.
     *
     * @see get_instance()
     * @type object
     */
    protected static $instance = NULL;

    /**
     * URL to this plugin's directory.
     *
     * @type string
     */
    public $plugin_url = '';

    /**
     * Path to this plugin's directory.
     *
     * @type string
     */
    public $plugin_path = '';

    /**
     * Option name for the Categories List
     * 
     * @var object 
     */
    public static $option_name = 'sites_categories_list';
    
    /**
     * Holds the List of Categories and its IDs (mature)
     * 
     * @var object 
     */
    public $options;
    
    /**
     * Debug only, show the mature column
     * 
     * Use add_filter( 'msc_show_mature_column', '__return_true' );
     * 
     * @var bool 
     */
    public static $show_mature_column = false;
    
    
    /**
     * Cache list of sites with id + mature
     * 
     * add_filter( 'msc_transient_time', function(){ return 1; } );
     * 
     * @var bool 
     */
    public static $sites_transient = 3600; // 1 hour
    
    
    public static $repo_slug = 'multisite-site-category';

    /**
     * Access this plugin’s working instance
     *
     * @wp-hook plugins_loaded
     * @since   2012.09.13
     * @return  object of this class
     */
    public static function get_instance()
    {
        NULL === self::$instance and self::$instance = new self;
        return self::$instance;
    }


    /**
     * Activation hook
     * 
     * Checks if option exist, otherwise fill it up
     * 
     * @global type $wpdb
     */
    public function on_activation()
    {
        if( !is_multisite() )
            wp_die(
               'Cannot install in Single Site. Multisite only!', 
               'Error',  
               array( 
                   'response' => 500, 
                   'back_link' => true 
               )
           );

        $blogs = self::get_blog_list();
        $original_blog = get_current_blog_id();
        foreach( $blogs as $blog )
        {
            switch_to_blog( $blog['blog_id'] );
            $has_category = get_blog_option( $blog['blog_id'], 'site_category' );
            if( empty( $has_category ) )
                update_blog_option( $blog['blog_id'], 'site_category', '' );
        }
        switch_to_blog( $original_blog );
    }


    /**
     * Used for regular plugin work.
     *
     * @wp-hook plugins_loaded
     * @since   2012.09.10
     * @return  void
     */
    public function plugin_setup()
    {
        global $pagenow;
        $this->plugin_url  = plugins_url( '/', __FILE__ );
        $this->plugin_path = plugin_dir_path( __FILE__ );
        $this->options = get_option( self::$option_name );
        
        # SIGNUP FIELDS
        # needs further work, disable for now
        // require_once 'inc/class-sites-categories-signup.php';
        // new B5F_Sites_Categories_Signup();
        
        # WP, ALL MATURES ARE OK
        if( 'sites.php' != $pagenow )
            add_filter( 'blog_details', array( $this, 'hack_mature_queries' ) );	

        # BAIL OUT
        if( !is_network_admin() )
            return;
        
        # NETWORK MENU
        require_once 'inc/class-sites-categories-menu.php';
        new B5F_Sites_Categories_Menu();
        
        # COLUMNS
        require_once 'inc/class-sites-categories-columns.php';
        new B5F_Sites_Categories_Columns();
        
        # MANIPULATE FIELDS IN SITE-INFO
        add_action( 'admin_init', array( $this, 'site_info_post_data' ) );
        add_action( 'admin_footer', array( $this, 'site_info_scripts' ) );
        
        # Self hosted updates
        include_once 'inc/plugin-update-checker.php';
        $updateChecker = new PluginUpdateCheckerB(
            'https://raw.github.com/brasofilo/'.self::$repo_slug.'/master/inc/update.json', 
            __FILE__, 
            self::$repo_slug.'-master'
        );
        # Workaround to remove the suffix "-master" from the unzipped directory
        add_filter( 'upgrader_source_selection', array( $this, 'rename_github_zip' ), 1, 3 );
    }


    /**
     * Constructor. Intentionally left empty and public.
     *
     * @see plugin_setup()
     * @since 2012.09.12
     */
    public function __construct() {}


    /**
     * Tell WP all Matures are equal to 0
     * Except in the screen sites.php
     * 
     * @param object $details
     * @return object
     */
    public function hack_mature_queries( $details )
    {
        $details->mature = 0;
        return $details;
    }

    /**
     * Change site category in Site Info
     * 
     * @return void
     */
    public function site_info_post_data()
    {
        if ( 
            !isset( $_POST['nonce_b5f_msc'] ) 
            || !wp_verify_nonce( $_POST['nonce_b5f_msc'], plugin_basename( __FILE__ ) ) 
        )
            return;
        if( isset( $_POST['input_site_cat'] ) )
        {
            $val = $this->do_mature_to_name( $_POST['input_site_cat'] );
            update_blog_option( $_POST['id'], 'site_category', $val );
            update_blog_status( absint( $_POST['id'] ), 'mature', $_POST['input_site_cat'] );
            get_blog_status(absint( $_POST['id'] ),'mature');
        }
    }
    
    
    /**
     * Manipulate fields on site-info.php
     * 
     * @return string
     */
    public function site_info_scripts()
    {
        if( 'site-info-network' != get_current_screen()->id || !isset( $_GET['id'] ) )
            return;

        $nonce = wp_nonce_field( plugin_basename( __FILE__ ), 'nonce_b5f_msc', true, false );
        $dropdown = $this->get_dropdown( $_GET['id'], $nonce );
        $dropdown = '<tr><th scope="row">Category</th><td>' . $dropdown . $nonce . '</td></tr>';
echo <<<HTML
	<script type="text/javascript">
		jQuery(document).ready( function($) {
			$(".form-table").find("label:contains('Mature')").remove();
            $('$dropdown').appendTo('.form-table')
		});
	</script>
HTML;
    }

    
    /** 
     * Generate HTML for categories dropdown
     * 
     * @param type $nonce
     */
    public function get_dropdown( $site_id )
    {
        $all_cats = get_option( self::$option_name );
        $dropdown = '<select name="input_site_cat" id="input_site_cat">';
        $site_cat = !empty( $site_id ) ? get_blog_option( $site_id, 'site_category' ) : false;
        $empty_cat = '';
        if( $site_cat )
            $site_cat = $this->do_name_to_mature( $site_cat );
        else
            $empty_cat = 'selected="selected"';
        
        $dropdown .= '<option value="empty" ' . $empty_cat . '>--select--</option>';
        foreach ( $all_cats as $cat ) 
        {
            $sel = $cat['mature'] == $site_cat ? 'selected="selected"' : '';
            $dropdown .= sprintf(
                '<option value="%s" %s>%s</option>',
                $cat['mature'],
                $sel,//selected( $count, $site_cat, false ),
                $cat['name']
            );
        }	
        $dropdown .= '</select>';
        return $dropdown;
    }
    
    
    /**
     * Categories settings changed, updated sites
     * 
     * @param array $cats_arr
     */
    public function update_sites( $cats_arr )
    {
        $this->options = $cats_arr;
        $blogs = self::get_blog_list();
        foreach( $blogs as $blog )
        {
            $id = $blog['blog_id'];
            $opt = get_blog_option( $id, 'site_category' );
            $mature = $this->do_name_to_mature( $opt );
            if( !$mature )
                update_blog_option( $id, 'site_category', '' );
            update_blog_status( absint( $id ), 'mature', $mature );
            get_blog_status( absint( $id ),'mature');
        }
    }
    
    
    /**
     * Get blog list
     * 
     * @return array All blogs IDs
     */
    public static function get_blog_list() 
    {
        $blogs = get_site_transient( 'multisite_blog_list' );
        if ( FALSE === $blogs ) 
        {
            $time = apply_filters( 'msc_transient_time',  self::$sites_transient );
            global $wpdb;
            $limit = '';
            //$limit = "LIMIT $start, $num";
            $blogs = $wpdb->get_results(
                $wpdb->prepare( "
                    SELECT blog_id, mature 
                    FROM $wpdb->blogs
                    WHERE site_id = %d 
                    $limit
                ", $wpdb->siteid ), 
             ARRAY_A );
             set_site_transient( 'multisite_blog_list', $blogs, $time );
        }
        return $blogs;
    }

    
    /**
     * Gets category name base on id (mature)
     * 
     * @param int $mature
     * @return string
     */
    public function do_mature_to_name( $mature )
    {
        foreach( $this->options as $opt )
        {
            if( $mature == $opt['mature'] )
                return $opt['name'];
        }
        return '';
    }
    
    
    /**
     * Get id (mature) based on category name
     * 
     * @param string $category
     * @return int
     */
    public function do_name_to_mature( $category )
    {
        foreach( $this->options as $opt )
        {
            if( $category == $opt['name'] )
                return $opt['mature'];
        }
        return '';
    }
    
    
    /**
     * Add donate link to plugin description in /wp-admin/plugins.php
     * 
     * @param array $plugin_meta
     * @param string $plugin_file
     * @param string $plugin_data
     * @param string $status
     * @return array
     */
    public function donate_link( $plugin_meta, $plugin_file, $plugin_data, $status ) 
	{
		if( plugin_basename( __FILE__ ) == $plugin_file )
			$plugin_meta[] = sprintf(
                '&hearts; <a href="%s">%s</a>',
                'https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=JNJXKWBYM9JP6&lc=US&item_name=Rodolfo%20Buaiz&item_number=Plugin%20donation&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted',
                __( 'Buy me a beer :)' )
            );
		return $plugin_meta;
	}


    /**
	 * Removes the prefix "-master" when updating from GitHub zip files
	 * 
	 * See: https://github.com/YahnisElsts/plugin-update-checker/issues/1
	 * 
	 * @param string $source
	 * @param string $remote_source
	 * @param object $thiz
	 * @return string
	 */
	public function rename_github_zip( $source, $remote_source, $thiz )
	{
		if(  strpos( $source, self::$repo_slug ) === false )
			return $source;

		$path_parts = pathinfo($source);
		$newsource = trailingslashit($path_parts['dirname']). trailingslashit( self::$repo_slug );
		rename($source, $newsource);
		return $newsource;
	}

}