<?php

function theme_enqueue_styles() {
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', [] );
}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles', 20 );

function avada_lang_setup() {
    $lang = get_stylesheet_directory() . '/languages';
    load_child_theme_textdomain( 'Avada', $lang );
}
add_action( 'after_setup_theme', 'avada_lang_setup' );

function addOrderListColumn($column) {    
    $newColumn=array();

    foreach($column as $index=>$value) {
        if($index==='order-number') {
            $newColumn['order-number']= __('Bestellnummer','woocommerce');
            $newColumn['invoice-date']= __('Rechnungsdatum','woocommerce');
            $newColumn['passenger-name']= __('Fahrgastname','woocommerce'); // Hinzugefügt
        } else {
            $newColumn[$index]=$value;
        }
    }

    return($newColumn);
}
add_filter('woocommerce_my_account_my_orders_columns','addOrderListColumn');

function addOrderListRow($order) {
    $orderMeta=CHBSPostMeta::getPostMeta($order->ID);
    
    if((is_array($orderMeta)) && (array_key_exists('booking_id',$orderMeta)) && ($orderMeta['booking_id']>0)) {
        $bookingMeta=CHBSPostMeta::getPostMeta($orderMeta['booking_id']);
    
        if((is_array($bookingMeta)) && (array_key_exists('form_element_field',$bookingMeta))) {
            if(is_array($bookingMeta['form_element_field'])) {
                foreach($bookingMeta['form_element_field'] as $index=>$value) {
                    if($value['label']=='Fahrgastname') {
                        echo $value['value'];
                        return;
                    }
                }
            }
        }
    }
}
add_action('woocommerce_my_account_my_orders_column_passenger-name','addOrderListRow'); // Hinzugefügt

function addInvoiceDateRow($order) {
    $orderMeta=CHBSPostMeta::getPostMeta($order->ID);
    if((is_array($orderMeta)) && (array_key_exists('booking_id',$orderMeta)) && ($orderMeta['booking_id']>0)) {
        $bookingMeta=CHBSPostMeta::getPostMeta($orderMeta['booking_id']);
        if((is_array($bookingMeta)) && (array_key_exists('pickup_date',$bookingMeta))) {
            echo esc_html($bookingMeta['pickup_date']);
        }
    }
}
add_action('woocommerce_my_account_my_orders_column_invoice-date','addInvoiceDateRow');

// Add new column to the order table
add_filter( 'manage_edit-shop_order_columns', 'add_new_order_admin_list_column' );
function add_new_order_admin_list_column( $columns ) {
    $columns['order_passenger_name'] = __( 'Fahrgastname', 'your_theme_domain' );
    $columns['order_invoice_date'] = __( 'Rechnungsdatum', 'your_theme_domain' );
    return $columns;
}

// Add the data for the new column
add_action( 'manage_shop_order_posts_custom_column', 'add_new_order_admin_list_column_content', 10, 2 );
function add_new_order_admin_list_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'order_passenger_name' :
            $orderMeta=CHBSPostMeta::getPostMeta($post_id);
            if((is_array($orderMeta)) && (array_key_exists('booking_id',$orderMeta)) && ($orderMeta['booking_id']>0)) {
                $bookingMeta=CHBSPostMeta::getPostMeta($orderMeta['booking_id']);
                if((is_array($bookingMeta)) && (array_key_exists('form_element_field',$bookingMeta))) {
                    if(is_array($bookingMeta['form_element_field'])) {
                        foreach($bookingMeta['form_element_field'] as $index=>$value) {
                            if($value['label']=='Fahrgastname') {
                                echo $value['value'];
                                return;
                            }
                        }
                    }
                }
            }
            break;
        case 'order_invoice_date' :
            $orderMeta=CHBSPostMeta::getPostMeta($post_id);
            if((is_array($orderMeta)) && (array_key_exists('booking_id',$orderMeta)) && ($orderMeta['booking_id']>0)) {
                $bookingMeta=CHBSPostMeta::getPostMeta($orderMeta['booking_id']);
                if((is_array($bookingMeta)) && (array_key_exists('pickup_date',$bookingMeta))) {
                    echo esc_html($bookingMeta['pickup_date']);
                }
            }
            break;
    }
}

// Hier beginnt der Code, um die Routendetails auf der kasse/order-pay Seite anzuzeigen

function display_route_details_on_order_pay_page() {
    global $post;
    if (is_page('order-pay')) {
        $orderMeta = CHBSPostMeta::getPostMeta($post->ID);
        if (is_array($orderMeta) && array_key_exists('booking_id', $orderMeta) && $orderMeta['booking_id'] > 0) {
            $bookingMeta = CHBSPostMeta::getPostMeta($orderMeta['booking_id']);
            if (is_array($bookingMeta) && array_key_exists('coordinate', $bookingMeta)) {
                echo '<div class="route-details">';
                echo '<h3>' . __('Routendetails', 'chauffeur-booking-system') . '</h3>';
                echo '<ul>';
                foreach ($bookingMeta['coordinate'] as $coordinate) {
                    echo '<li>';
                    echo esc_html(CHBSHelper::getFormattedAddress($coordinate));
                    echo '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
        }
    }
}
add_action('woocommerce_before_checkout_form', 'display_route_details_on_order_pay_page');

add_action('woocommerce_thankyou', 'custom_redirect_after_purchase');

function custom_redirect_after_purchase($order_id) {
    $order = wc_get_order($order_id);

    // Überprüfen, ob die Bestellung erfolgreich abgeschlossen wurde
    if ($order->get_status() != 'failed') {
        // Weiterleitung zur "Mein Konto / Bestellungen"-Seite
        $redirect_url = wc_get_endpoint_url('orders', '', wc_get_page_permalink('myaccount'));
        wp_redirect($redirect_url);
        exit;
    }
}

?>
