<?php
/**
   * Snippet: BOGO Free Item Quota
   *
   * Limits the total number of free items an Advanced Coupons BOGO promotion
   * can give across all orders. Set the limit on the coupon edit screen.
   * The running total is stored in coupon meta and restored on full
   * cancellation or refund (partial refunds are ignored).
   *
   * Works at the BOGO deal-cycle level: each recursive cycle is either
   * allowed or blocked, so granularity equals the deal quantity per cycle
   * (1 item for a standard buy-1-get-1).
   */

  // -------------------------------------------------------------------------
  // Admin: add Max Free Items fields to the coupon edit screen
  // -------------------------------------------------------------------------

  add_action( 'woocommerce_coupon_options', function ( $coupon_id, $coupon ) {
      echo '<div class="options_group">';

      woocommerce_wp_text_input( array(
          'id'                => '_acfw_bogo_max_free_items',
          'label'             => __( 'BOGO max free items', 'woocommerce' ),
          'desc_tip'          => true,
          'description'       => __( 'Total free items this BOGO coupon may give across all orders. Leave blank for unlimited.', 'woocommerce' ),
          'type'              => 'number',
          'custom_attributes' => array( 'min' => 0, 'step' => 1 ),
          'value'             => get_post_meta( $coupon_id, '_acfw_bogo_max_free_items', true ),
      ) );

      woocommerce_wp_text_input( array(
          'id'                => '_acfw_bogo_free_items_used',
          'label'             => __( 'BOGO free items used', 'woocommerce' ),
          'desc_tip'          => true,
          'description'       => __( 'Running total of free items given. Adjust manually if needed.', 'woocommerce' ),
          'type'              => 'number',
          'custom_attributes' => array( 'min' => 0, 'step' => 1 ),
          'value'             => (int) get_post_meta( $coupon_id, '_acfw_bogo_free_items_used', true ),
      ) );

      echo '</div>';
  }, 10, 2 );

  add_action( 'woocommerce_coupon_options_save', function ( $coupon_id ) {
      if ( isset( $_POST['_acfw_bogo_max_free_items'] ) ) {
          $max = absint( $_POST['_acfw_bogo_max_free_items'] );
          if ( $max > 0 ) {
              update_post_meta( $coupon_id, '_acfw_bogo_max_free_items', $max );
          } else {
              delete_post_meta( $coupon_id, '_acfw_bogo_max_free_items' );
          }
      }
      if ( isset( $_POST['_acfw_bogo_free_items_used'] ) ) {
          update_post_meta( $coupon_id, '_acfw_bogo_free_items_used', absint( $_POST['_acfw_bogo_free_items_used'] ) );
      }
  } );

  // -------------------------------------------------------------------------
  // Reset the in-request quota tracker at the start of each calculate_totals()
  // -------------------------------------------------------------------------

  add_action( 'woocommerce_before_calculate_totals', function () {
      global $acfw_bogo_free_items_quota;
      $acfw_bogo_free_items_quota = null;
  }, 1 );

  // -------------------------------------------------------------------------
  // Enforce quota on each BOGO deal cycle
  // -------------------------------------------------------------------------

  add_action( 'acfw_bogo_after_verify_trigger_deals', function ( $bogo_deal ) {
      global $acfw_bogo_free_items_quota;

      $coupon_id = $bogo_deal->get_coupon()->get_id();
      $max       = (int) get_post_meta( $coupon_id, '_acfw_bogo_max_free_items', true );

      if ( ! $max ) {
          return;
      }

      // Initialise quota once per calculate_totals() pass.
      if ( null === $acfw_bogo_free_items_quota ) {
          $used                       = (int) get_post_meta( $coupon_id, '_acfw_bogo_free_items_used', true );
          $acfw_bogo_free_items_quota = max( 0, $max - $used );
      }

      // How many items did verify_deals() allocate this cycle?
      $fresh_allowed = array_column( $bogo_deal->deals, 'quantity', 'entry_id' );
      $cycle_qty     = array_sum( $fresh_allowed ) - $bogo_deal->get_allowed_deal_quantity();

      if ( $cycle_qty <= 0 ) {
          return; // Nothing was allocated — nothing to limit.
      }

      if ( $acfw_bogo_free_items_quota >= $cycle_qty ) {
          // Quota covers this cycle — allow it.
          $acfw_bogo_free_items_quota -= $cycle_qty;
      } else {
          // Quota exhausted — block this cycle.
          // Resetting allowed_deals to base values makes has_deal_fulfilled() return
          // false, so confirm_matched_triggers() is skipped, temp entries are cleared,
          // and _set_matching_cart_item_deals_prices() never sees this cycle's deals.
          foreach ( $fresh_allowed as $entry_id => $qty ) {
              $bogo_deal->set_allowed_deal_quantity( $entry_id, $qty );
          }
      }
  } );

  // -------------------------------------------------------------------------
  // Helper: count total discounted item quantity from BOGO order meta
  // -------------------------------------------------------------------------

  function acfw_bogo_count_free_items( array $bogo_discounts ): int {
      $count = 0;
      foreach ( $bogo_discounts as $discount ) {
          foreach ( ( $discount['discounted_prices'] ?? array() ) as $dp ) {
              $count += (int) $dp['quantity'];
          }
      }
      return $count;
  }

  // -------------------------------------------------------------------------
  // Increment used count when an order is placed (priority 20, after BOGO at 10)
  // -------------------------------------------------------------------------

  add_action( 'woocommerce_checkout_order_processed', function ( $order_id, $posted_data, $order ) {
      $bogo_discounts = $order->get_meta( 'acfw_order_bogo_discounts' );
      if ( empty( $bogo_discounts ) ) {
          return;
      }

      $free_count = acfw_bogo_count_free_items( (array) $bogo_discounts );
      if ( ! $free_count ) {
          return;
      }

      foreach ( $order->get_items( 'coupon' ) as $coupon_item ) {
          $coupon    = new WC_Coupon( $coupon_item->get_code() );
          $coupon_id = $coupon->get_id();

          if ( ! get_post_meta( $coupon_id, '_acfw_bogo_max_free_items', true ) ) {
              continue;
          }

          $current = (int) get_post_meta( $coupon_id, '_acfw_bogo_free_items_used', true );
          update_post_meta( $coupon_id, '_acfw_bogo_free_items_used', $current + $free_count );
      }
  }, 20, 3 );

  // -------------------------------------------------------------------------
  // Restore quota on full cancellation, failure, or refund
  // -------------------------------------------------------------------------

  function acfw_bogo_restore_quota( int $order_id ): void {
      $order = wc_get_order( $order_id );
      if ( ! $order || $order->get_meta( '_acfw_bogo_quota_restored' ) ) {
          return;
      }

      $bogo_discounts = $order->get_meta( 'acfw_order_bogo_discounts' );
      if ( empty( $bogo_discounts ) ) {
          return;
      }

      $free_count = acfw_bogo_count_free_items( (array) $bogo_discounts );
      if ( ! $free_count ) {
          return;
      }

      foreach ( $order->get_items( 'coupon' ) as $coupon_item ) {
          $coupon    = new WC_Coupon( $coupon_item->get_code() );
          $coupon_id = $coupon->get_id();

          if ( ! get_post_meta( $coupon_id, '_acfw_bogo_max_free_items', true ) ) {
              continue;
          }

          $current = (int) get_post_meta( $coupon_id, '_acfw_bogo_free_items_used', true );
          update_post_meta( $coupon_id, '_acfw_bogo_free_items_used', max( 0, $current - $free_count ) );
      }

      $order->update_meta_data( '_acfw_bogo_quota_restored', '1' );
      $order->save();
  }

  add_action( 'woocommerce_order_status_cancelled', 'acfw_bogo_restore_quota' );
  add_action( 'woocommerce_order_status_failed',    'acfw_bogo_restore_quota' );
  add_action( 'woocommerce_order_status_refunded',  'acfw_bogo_restore_quota' );

//   A few things to know before deploying:
//
//   - The "BOGO free items used" field on the coupon edit screen is editable, so you can reset or adjust the count manually at any time
//   - For your buy-X-get-Y-free setup, each recursive cycle gives 1 free item, so the quota enforces in single-unit increments — exactly right
//   - The _acfw_bogo_quota_restored flag prevents double-restoration if, say, an order transitions through multiple terminal statuses
