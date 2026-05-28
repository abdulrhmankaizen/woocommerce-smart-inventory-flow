<?php<?php 
/**
 * Smart Inventory Sync for WooCommerce
 * Piece + Carton Shared Stock Logic
 */
add_action('woocommerce_reduce_order_stock', 'kaizen_sync_inventory');
function kaizen_sync_inventory($order_id)
{
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $quantity   = $item->get_quantity();
        // Product type from custom field
        $type = get_post_meta($product_id, '_product_unit_type', true);
        // Main shared inventory product ID
        $shared_stock_id = get_post_meta($product_id, '_shared_stock_product', true);
        if (!$shared_stock_id) {
            continue;
        }
        $shared_product = wc_get_product($shared_stock_id);
        if (!$shared_product) {
            continue;
        }
        // Current stock
        $current_stock = $shared_product->get_stock_quantity();
        /**
         * Inventory Logic
         * 1 Carton = 24 Pieces
         */
        if ($type === 'carton') {
            // Convert cartons to pieces
            $deduct_quantity = $quantity * 24;
        } else {
            // Piece product
            $deduct_quantity = $quantity;
        }
        // New stock calculation
        $new_stock = $current_stock - $deduct_quantity;
        // Prevent negative stock
        if ($new_stock < 0) {
            wc_add_notice(
                __('Not enough stock available.', 'kaizen'),
                'error'
            );
            return;
        }
        // Update stock
        $shared_product->set_stock_quantity($new_stock);
        // Save changes
        $shared_product->save();
    }
}
