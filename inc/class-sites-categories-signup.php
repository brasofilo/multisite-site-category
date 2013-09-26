<?php
/**
 * Handles signup process
 * 
 * UNUSED AND UNTESTED with the new dropdown/mature features
 * 
 * @package    Multisite Site Category
 * @author     Rodolfo Buaiz
 * @since      2013.09.26
 */

class B5F_Sites_Categories_Signup
{
    public function __construct()
    {
         # FRONTEND SIGNUP
        if( !is_admin() )
            add_action(
                    'signup_blogform', array( $this, 'signup_blogform_extra_field' )
            );
        # BAIL OUT
        if( !is_network_admin() )
            return;
        
        # BACKEND SIGN-UP
        add_action(
                'wpmu_new_blog', array( $this, 'add_new_blog_field' )
        );
        add_filter(
                'add_signup_meta', array( $this, 'append_extra_field_as_meta' )
        );
      
         # INJECT FIELD IN SITE-NEW
        add_action(
                'admin_print_scripts-site-new.php', array( $this, 'new_site_input_field_scripts' )
        );
   }
    
 
    /**
     * Add new field in site signup form /wp-signup.php
     *
     * URI: http://wordpress.stackexchange.com/a/50550/12615
     */
    public function signup_blogform_extra_field()
    {
        echo B5F_Multisite_Categories::get_instance()->get_dropdown( '' );
//      printf('<label>%s</label><input type="text" name="site_category" value="" />',__( 'Category'));
    }

    /**
     * Add new option when registering a site (back and front end)
     *
     * URI: http://stackoverflow.com/a/10372861/1287812
     */
    public function add_new_blog_field( $blog_id, $user_id, $domain, $path, $site_id, $meta )
    {
        $new_field_value = '';

        # Site added in the back end
        if( !empty( $_POST['blog']['input_site_cat'] ) )
        {
            switch_to_blog( $blog_id );
            $cat_id = $_POST['blog']['input_site_cat'];
            # TODO: if Sign-up is to be enabled, change this to a method
            $val = B5F_Multisite_Categories::get_instance()->do_mature_to_name( $cat_id );
            update_blog_option( $blog_id, 'site_category', $val );
            update_blog_status( $blog_id, 'mature', $cat_id );
            get_blog_status( $blog_id, 'mature');
            restore_current_blog();
        }
        # Site added in the front end
        elseif( !empty( $meta['input_site_cat'] ) )
        {
            $new_field_value = $meta['input_site_cat'];
            update_option( 'site_category', $new_field_value );
        }
    }


    /**
     * Append the submitted value of our custom input 
     * into the meta array that is stored while the user doesn't activate
     *
     * URI: http://wordpress.stackexchange.com/a/50550/12615
     */
    public function append_extra_field_as_meta( $meta )
    {
        if( isset( $_REQUEST['input_site_cat'] ) )
            $meta['site_category'] = $_REQUEST['input_site_cat'];

        return $meta;
    }


   /**
     * Add new field in /wp-admin/network/site-new.php
     * has to be done with jQuery
     *
     * URI: http://stackoverflow.com/a/10372861/1287812
     */
    public function new_site_input_field_scripts()
    {
        $url = B5F_Multisite_Categories::get_instance()->plugin_url . 'js/msc-dropdown.js';
        wp_register_script( 'b5f_msc', $url );
        wp_enqueue_script( 'b5f_msc' );
        wp_localize_script( 'b5f_msc', 'b5f', array(
            'dropdown' => B5F_Multisite_Categories::get_instance()->get_dropdown( '' )
        ) );
    }
    
}