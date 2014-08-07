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
    <?php if( !empty( $event_tax ) ) : ?>
      <em>Leave blank to revert to global tax setting.</em>
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


