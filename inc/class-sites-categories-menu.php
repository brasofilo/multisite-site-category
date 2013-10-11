<?php
/**
 * Plugin admin submenu for Sites menu
 * 
 * The repeater fields sorting is disabled in the HTML here and in the JS file
 * 
 * @package    Multisite Site Category
 * @author     Rodolfo Buaiz
 * @since      2013.09.26
 */


class B5F_Sites_Categories_Menu
{
    /**
     * Add network admin menu
     */
    public function __construct()
    {
        add_action( 'network_admin_menu', array( $this, 'cat_menu' ) );
    }
    
    /**
     * Assign menu and set scripts
     */
    public function cat_menu()
    {
        $page = add_submenu_page(
            'sites.php',
            'Site Categories', 
            'Site Categories', 
            'add_users', 
            'site-cats', 
            array( $this, 'render_menu' )
        );
        add_action( "admin_print_scripts-$page", array( $this, 'print_script' ) );
    }

    
    /**
     * Display plugin screen
     * 
     * The count starts at 2 because Mature uses 0 and 1
     * if greater than 1, 'mature' it's not added 
     * to the site name in the Sites screen
     */
    public function render_menu()
    {
        if ( 
            isset( $_POST['cat_name'] ) 
            && isset( $_POST['b5f_debug_log'] ) 
            && wp_verify_nonce( $_POST['b5f_debug_log'], plugin_basename( __FILE__ ) ) 
        )
        {
            $cats_arr = array();
            $count = 2;
            usort( $_POST['cat_name'], array( $this, 'cmp' ) );
            foreach( $_POST['cat_name'] as $key => $value )
            {
                if( !empty( $value ) )
                {
                    $cats_arr[] = array( 'mature' => $count, 'name' => $value );
                    $count++;
                }
            }
            update_option( B5F_Multisite_Categories::$option_name, $cats_arr );
            B5F_Multisite_Categories::get_instance()->update_sites( $cats_arr );
        }
        $this->echo_html( get_option( B5F_Multisite_Categories::$option_name ) );
    }

    
    /**
     * Helper function for plugin screen
     * 
     * @param string $repeatable_fields
     */
    private function echo_html( $repeatable_fields )
    {
        ?>
        <div class="wrap">
        <div id="icon-tools" class="icon32"></div> 
        <h2>Site Categories</h2>
        <div id="poststuff">

                <form action="" method="post" id="notes_form">
                <?php
                wp_nonce_field( plugin_basename( __FILE__ ), 'b5f_debug_log' );
                //echo "<textarea id='b5f_sitecats_input' name='b5f_sitecats_input' cols='70' rows='15'>$text</textarea></label>";
                ?>

            <table id="repeatable-fieldset-one" width="100%">
            <thead>
                <tr>
                    <th width="2%"></th>
                    <th width="90%"></th>
                    <!--<th width="2%"></th>-->
                </tr>
            </thead>
            <tbody>
            <?php
            $blogs = B5F_Multisite_Categories::get_blog_list();
            
            if ( $repeatable_fields ) :

                foreach ( $repeatable_fields as $field ) {
                    $num_sites = count( $this->search_ocurrences($blogs,'mature', $field['mature'] ) );
                    $num_sites = '<span style="opacity:.5">Sites: </span><b>'. $num_sites . '</b>';
                ?>
            <tr>
                <td><a class="button remove-row" href="#">-</a></td>
                <td><input type="text" class="widefat" name="cat_name[]" value="<?php if($field['name'] != '') echo esc_attr( $field['name'] ); ?>" /></td>
                <td><?php echo $num_sites; ?></td>
                <!--<td><a class="sort">|||</a></td>-->
           </tr>
                <?php
                }
            else :
                // show a blank one
            ?>
            <tr>
                <td><a class="button remove-row" href="#">-</a></td>
                <td><input type="text" class="widefat" name="cat_name[]" /></td>
                <td>&nbsp;</td>
                <!--<td><a class="sort">|||</a></td>-->

            </tr>
            <?php endif; ?>

            <!-- empty hidden one for jQuery -->
            <tr class="empty-row screen-reader-text">
                <td><a class="button remove-row" href="#">-</a></td>
                <td><input type="text" class="widefat" name="cat_name[]" /></td>
                <td>&nbsp;</td>
                <!--<td><a class="sort">|||</a></td>-->

            </tr>
            </tbody>
            </table>

            <p><a id="add-row" class="button" href="#">Add another</a>
            <!--<input type="submit" class="metabox_submit" value="Save" />-->
            </p>
        <?php submit_button(); ?>
        <hr />
        </form>
        <footer>&hearts; <a href="http://brasofilo.com">Rodolfo Buaiz</a> &middot; <a href="https://github.com/brasofilo/">Github</a></footer>
        </div>
        </div>	
        <?php
    }

    
    /**
     * Enqueue plugin script
     * 
     */
    public function print_script()
    {
        wp_enqueue_script( 
            'repeat', 
            B5F_Multisite_Categories::get_instance()->plugin_url . 'js/msc-repeatable.js', 
            array( 'jquery-ui-sortable', 'jquery-ui-core', 'jquery')
        );
    }
    
    /**
     * Sort array alphabetically
     * 
     * @param string $a
     * @param string $b
     * @return array
     */
    private function cmp($a, $b)
    {
        return strcasecmp($a, $b);
    }
    
    /**
     * Search for ocurrences of a given $key=>$value
     * 
     * Used to count the number of sites within a category
     * 
     * @param array $array
     * @param string $key
     * @param string $value
     * @return array
     */
    private function search_ocurrences($array, $key, $value)
    {
        $results = array();

        if (is_array($array))
        {
            if (isset($array[$key]) && $array[$key] == $value)
                $results[] = $array;

            foreach ($array as $subarray)
                $results = array_merge($results, $this->search_ocurrences($subarray, $key, $value));
        }

        return $results;
    }
}