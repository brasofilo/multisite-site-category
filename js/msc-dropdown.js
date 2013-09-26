/**
 * Add new field in /wp-admin/network/site-new.php
 * 
 * URI: http://stackoverflow.com/a/10372861/1287812
 * 
 * @package    Multisite Site Category
 * @author     Rodolfo Buaiz
 * @since      2013.09.26
 */

// UNUSED TEXT FIELD
// var b5f_add_field = '<input class="regular-text" type="text" title="Site Category" name="blog[site_category]">';

// Common opening for new fields
var b5f_open_tr = '<tr class="form-field form-required"></tr>';

jQuery(document).ready(function($) 
{
    /* UNUSED TEXT FIELD
    $( b5f_open_tr )
        .append( $('<th scope="row">Site category</th>') )
        .append( 
            $('<td></td>')
            .append( $(b5f_add_field) )
            .append( $('<p style="clear:both"></p>') )
        ).insertAfter('#wpbody-content table tr:eq(2)');
     */       
    $( b5f_open_tr )
        .append( $('<th scope="row">Site category</th>') )
        .append( 
            $('<td></td>')
            .append( b5f.dropdown )
            .append( $('<p style="clear:both"></p>') )
        ).insertAfter('#wpbody-content table tr:eq(2)');
});
            