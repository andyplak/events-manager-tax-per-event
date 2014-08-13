<?php
/**
 * Plugin Name: Events Manager - Tax Per Event
 * Plugin URI: https://github.com/andyplak/events-manager-tax-per-event
 * Description: Plugin for Events Manager that allows the booking tax settings to be overridden per event.
 * Version: 1.0
 * Author: Andy Place
 * Author URI: http://www.andyplace.co.uk
 * License: GPL2
 */

/**
 * Add metabox to events editor that allows us to configure the tax
 */
function em_tax_adding_custom_meta_boxes( $post ) {

  add_meta_box(
    'em-event-tax',
    __( 'Tax Rules' ),
    'render_tax_meta_box',
    'event',
    'side',
    'default'
  );
}
add_action( 'add_meta_boxes_event', 'em_tax_adding_custom_meta_boxes', 10, 2 );


/**
 * Render metabox with tax options.
 * Note, this option is disabled when in Multiple Bookings mode
 */
function render_tax_meta_box() {
  global $post;

  if( get_option('dbem_multiple_bookings', 0) ) {
    _e('Tax cannot be set per event when multiple bookings mode is enabled.');
    return;
  }

  $event_tax = get_post_meta( $post->ID, '_event_tax_rate', true );

  ?>
  <p>To override the global tax settings for this event, adjust the settings below.</p>
  <strong>Global Settings:</strong><br />
  <?php if( get_option('dbem_bookings_tax_auto_add') ) : ?>
    Add tax to ticket price<br />
  <?php else: ?>
    Ticket price is inclusive of tax rate.
  <?php endif; ?>
  <br />
  Tax rate: <?php echo get_option('dbem_bookings_tax') ?>%<br />

  <p>
    <label for="event_tax_rate"><strong>Event Tax Rate</strong></label><br />
    <input type="number" name="event_tax_rate" min="0" max="100"
      value="<?php echo $event_tax ?>">%<br />
    <?php if( !empty( $event_tax ) || is_numeric( $event_tax ) ) : ?>
      <em>Leave blank to revert to global tax setting.</em>
    <?php else: ?>
      <em>Enter 0 for no tax.</em>
    <?php endif; ?>
  </p>
  <?php
}


/**
 * Save currency option setting
 */
function em_tax_save_post($post_id, $post) {

  // verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times
  if ( !wp_verify_nonce( $_POST['_emnonce'], 'edit_event' ) )
    return $post->ID;

  // Is the user allowed to edit the post or page?
  if ( !current_user_can( 'edit_post', $post->ID ))
    return $post->ID;

  if( $post->post_type == 'revision' )
    return $post->ID; // Don't store custom data twice

  if( isset( $_POST['event_tax_rate'] ) ) {
    update_post_meta( $post->ID, '_event_tax_rate', $_POST['event_tax_rate'] );
  }else{
    delete_post_meta( $post->ID, '_event_tax_rate' );
  }

}
add_action('save_post', 'em_tax_save_post', 1, 2);


/**
 * Hook in and modify tax rate for booking if set per event
 */
function em_tax_event_get_tax_rate( $tax_rate, $EM_Event ) {
  $event_tax = get_post_meta( $EM_Event->post_id, '_event_tax_rate', true );
  if( !empty( $event_tax ) || is_numeric( $event_tax ) ) {
    return $event_tax;
  }
  return $tax_rate;
}
add_filter('em_event_get_tax_rate', 'em_tax_event_get_tax_rate', 10, 2);


/****************** Ticket Price display mods ********************/


/**
 * Hook into em-tickets where ticket columns are defined.
 * Remove price and add net + gross
 */
function em_tax_booking_form_tickets_cols($columns, $EM_Event) {

  if( get_option('dbem_bookings_tax_auto_add') ) {
    $columns = array(
      'type' => 'Ticket Type',
      'net' => 'Net',
      'tax' => 'Tax',
      'price' => 'Price',
      'spaces' => 'Spaces',
    );
  }else{
    $columns = array(
      'type' => 'Ticket Type',
      'price' => 'Price',
      'tax' => 'Tax',
      'spaces' => 'Spaces',
    );
  }
  return $columns;
}
// Hook in early as we're generating array from scratch
add_filter('em_booking_form_tickets_cols', 'em_tax_booking_form_tickets_cols', 1, 2);


/**
 * Display single ticket net price
 */
function em_tax_booking_form_ticket_field_net( $EM_Ticket, $EM_Event ) {
  ?>
  <p class="ticket-net">
    <label>Net</label>
    <strong><?php echo $EM_Ticket->get_price_without_tax(true); ?></strong>
  </p>
  <?php
}
add_action('em_booking_form_ticket_field_net', 'em_tax_booking_form_ticket_field_net', 10, 2);


/**
 * Display single ticket tax
 */
function em_tax_booking_form_ticket_field_tax( $EM_Ticket, $EM_Event ) {

  $tax = $EM_Ticket->get_price_with_tax() - $EM_Ticket->get_price_without_tax();
  ?>
  <p class="ticket-tax">
    <label>Tax</label>
    <strong><?php echo $EM_Ticket->format_price( $tax ); ?></strong>
  </p>
  <?php
}
add_action('em_booking_form_ticket_field_tax', 'em_tax_booking_form_ticket_field_tax', 10, 2);


/**
 * Display ticket table net price
 */
function em_tax_booking_form_tickets_col_net( $EM_Ticket, $EM_Event ) {
  ?>
  <td class="em-bookings-ticket-table-net"><?php echo $EM_Ticket->get_price_without_tax(true); ?></td>
  <?php
}
add_action('em_booking_form_tickets_col_net', 'em_tax_booking_form_tickets_col_net', 10, 2);


/**
 * Display ticket table tax
 */
function em_tax_booking_form_tickets_col_tax( $EM_Ticket, $EM_Event ) {

  $tax = $EM_Ticket->get_price_with_tax() - $EM_Ticket->get_price_without_tax();
  ?>
  <td class="em-bookings-ticket-table-tax"><?php echo $EM_Ticket->format_price( $tax ); ?></td>
  <?php
}
add_action('em_booking_form_tickets_col_tax', 'em_tax_booking_form_tickets_col_tax', 10, 2);