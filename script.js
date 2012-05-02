/*
 * Add new field in /wp-admin/network/site-new.php
 * URI: http://stackoverflow.com/questions/10339053/wordpress-multisite-how-to-add-custom-blog-options-to-add-new-site-form-in-ne
 */

(function($) {
    $(document).ready(function() {
        $('<tr class="form-field form-required"></tr>').append(
            $('<th scope="row">Site Category</th>')
        ).append(
            $('<td></td>').append(
                $('<input class="regular-text" type="text" title="Site Category" name="blog[site_category]">')
            ).append(
                $('<p>choose one</p>')
            )
        ).insertAfter('#wpbody-content table tr:eq(2)');

		
    });
})(jQuery);