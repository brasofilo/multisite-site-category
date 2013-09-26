/**
 * Handles the repeatable fields
 * 
 * Sorting is disabled here and in the HTML block in the submenu script
 * 
 * @package    Multisite Site Category
 * @author     Rodolfo Buaiz
 * @since      2013.09.26
 */

jQuery(document).ready(function($) {
     $('#add-row').on('click', function() {
         var row = $('.empty-row.screen-reader-text').clone(true);
         row.removeClass('empty-row screen-reader-text');
         row.insertBefore('#repeatable-fieldset-one tbody>tr:last');
         return false;
     });
     $('.remove-row').on('click', function() {
         $(this).parents('tr').remove();
         return false;
     });
     /* SORTING DISABLED
     $('#repeatable-fieldset-one tbody').sortable({
         opacity: 0.6,
         revert: true,
         cursor: 'move',
         handle: '.sort'
     });
     */
 });
