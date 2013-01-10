<?php

/**
 * Plugin Name: Multisite Site Category
 * Plugin URI: https://github.com/brasofilo/multisite-site-category
 * Description: Add a custom meta option when registering new sites in WordPress Multisite.
 * Network: true
 * Author: Rodolfo Buaiz
 * Author URI: http://rodbuaiz.com/
 * Version: 1.1
 * Stable Tag: 1.1
 * License: GPLv2 or later
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU 
 * General Public License version 2, as published by the Free Software Foundation.  You may NOT assume 
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */


// BUSTED!
!defined( 'ABSPATH' ) AND exit(
                "<pre>Hi there! I'm just part of a plugin, <h1>&iquest;what exactly are you looking for?"
);

// PREPARE
if( !class_exists( 'BL_Multisite_Categories' ) ):

    class BL_Multisite_Categories
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
                wp_die( 'Cannot install in Single Site. Multisite only!' );

            // Get the Sites
            global $wpdb;
            $blogs = $wpdb->get_results(
                    "SELECT blog_id
                    FROM {$wpdb->blogs}
                    WHERE site_id = '{$wpdb->siteid}'"
            );

            // Create the Category 
            foreach( $blogs as $blog )
            {
                switch_to_blog( $blog->blog_id );
                $have_category = get_blog_option( $blog->blog_id, 'site_category' );
                if( !$have_category )
                    update_blog_option( $blog->blog_id, 'site_category', 'Uncategorized' );
            }
            restore_current_blog();
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
            if( !is_admin() )
                add_action(
                        'signup_blogform', array( $this, 'signup_blogform_extra_field' )
                );

            if( !is_network_admin() )
                return;

            $this->plugin_url  = plugins_url( '/', __FILE__ );
            $this->plugin_path = plugin_dir_path( __FILE__ );

            add_action(
                    'admin_print_scripts-site-new.php', array( $this, 'admin_scripts' )
            );
            add_action(
                    'admin_footer-sites.php', array( $this, 'add_style' )
            );
            add_action(
                    'manage_blogs_custom_column', array( $this, 'add_columns' ), 10, 2
            );
            add_action(
                    'manage_sites_custom_column', array( $this, 'add_columns' ), 10, 2
            );
            add_action(
                    'wpmu_new_blog', array( $this, 'add_new_blog_field' )
            );
            add_filter(
                    'add_signup_meta', array( $this, 'append_extra_field_as_meta' )
            );
            add_filter(
                    'wpmu_blogs_columns', array( $this, 'print_columns' )
            );
        }


        /**
         * Constructor. Intentionally left empty and public.
         *
         * @see plugin_setup()
         * @since 2012.09.12
         */
        public function __construct()
        {
            
        }


        /**
         * Add new option when registering a site (back and front end)
         *
         * URI: http://stackoverflow.com/a/10372861/1287812
         */
        public function add_new_blog_field( $blog_id, $user_id, $domain, $path, $site_id, $meta )
        {
            $new_field_value = 'default';

            // Site added in the back end
            if( !empty( $_POST['blog']['site_category'] ) )
            {
                switch_to_blog( $blog_id );
                $new_field_value = $_POST['blog']['site_category'];
                update_option( 'site_category', $new_field_value );

                restore_current_blog();
            }
            // Site added in the front end
            elseif( !empty( $meta['site_category'] ) )
            {
                $new_field_value = $meta['site_category'];
                update_option( 'site_category', $new_field_value );
            }
        }


        /**
         * Add new field in /wp-admin/network/site-new.php
         * has to be done with jQuery
         *
         * URI: http://stackoverflow.com/a/10372861/1287812
         */
        public function admin_scripts()
        {
            wp_register_script( 'b_msc', $this->plugin_url . 'js/script.js' );
            wp_enqueue_script( 'b_msc' );
        }


        /**
         * Add new field in site signup form /wp-signup.php
         *
         * URI: http://wordpress.stackexchange.com/a/50550/12615
         */
        public function signup_blogform_extra_field()
        {
            $txt = __( 'Category' );
            echo "
			<label>{$txt}</label>
			<input type='text' name='site_category' value='' />
		";
        }


        /**
         * Append the submitted value of our custom input 
         * into the meta array that is stored while the user doesn't activate
         *
         * URI: http://wordpress.stackexchange.com/a/50550/12615
         */
        public function append_extra_field_as_meta( $meta )
        {
            if( isset( $_REQUEST['site_category'] ) )
                $meta['site_category'] = $_REQUEST['site_category'];

            return $meta;
        }


        /**
         * Add custom columns (ID and Site Category) in Sites listing
         *
         */
        public function add_columns( $column_name, $blog_id )
        {
            if( 'blog_id' === $column_name )
                echo $blog_id;

            elseif( 'site_category' === $column_name )
            {
                $sitecat = get_blog_option( $blog_id, 'site_category' );
                echo $sitecat;
            }

            return $column_name;
        }


        /**
         * Add Columns
         *
         */
        public function print_columns( $cols )
        {
            $in = array( "blog_id"              => "ID" );
            $cols                  = $this->arrayPushAfter( $cols, $in, 0 );
            $cols['site_category'] = __( 'Category' );
            return $cols;
        }


        /**
         * Add column widths
         *
         */
        public function add_style()
        {
            echo '<style>#blog_id { width:7%; } #site_category { width:20%; }</style>';
        }


        /**
         * Insert $in item in position $pos inside the $src array
         *
         */
        private function arrayPushAfter( $src, $in, $pos )
        {
            if( is_int( $pos ) )
                $R = array_merge( array_slice( $src, 0, $pos + 1 ), $in, array_slice( $src, $pos + 1 ) );
            else
            {
                foreach( $src as $k => $v )
                {
                    $R[$k] = $v;
                    if( $k == $pos )
                        $R     = array_merge( $R, $in );
                }
            }
            return $R;
        }


    }

endif;


// ACTION!
if( function_exists( 'add_action' ) )
{
    // Initial plugin hooks
    register_activation_hook(
            __FILE__, array( 'BL_Multisite_Categories', 'on_activation' )
    );


    add_action(
            'plugins_loaded', array( BL_Multisite_Categories::get_instance(), 'plugin_setup' )
    );
}
