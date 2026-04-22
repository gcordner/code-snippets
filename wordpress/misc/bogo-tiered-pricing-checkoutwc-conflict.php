<?php
 /**
   * Fix: BOGO + Tiered Pricing cart totals conflict
   *
   * Advanced Coupons BOGO (v4.6.8+) stores a locked price in product meta and
   * hooks woocommerce_product_get_price at priority 100 to return it, overriding
   * any set_price() call made afterward (including tiered pricing at priority 99999).
   *
   * We sync the locked price to the tiered price only when tiered pricing is more
   * aggressive — preserving BOGO's free/discounted price on deal items.
   */
  add_action(
      'woocommerce_before_calculate_totals',
      function ( $cart ) {
          if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
              return;
          }
          foreach ( $cart->get_cart() as $cart_item ) {
              $product = $cart_item['data'];
              $locked  = $product->get_meta( '_acfw_bogo_locked_price', true );
              if ( ! is_numeric( $locked ) ) {
                  continue;
              }
              $tiered_price = $product->get_price( 'edit' );
              if ( (float) $tiered_price < (float) $locked ) {
                  $product->update_meta_data( '_acfw_bogo_locked_price', (float) $tiered_price );
              }
          }
      },
      999999
  );
