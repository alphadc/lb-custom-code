<?php
/**
 * Plugin Name: LB Custom Code
 * Description: A plugin to handle custom codes for LB.
 * Version: 1.0.0
 * Author: Irwan - AlphaDC
 * Author URI: https://alphadc.net
 */

function lb_custom_features_enqueue_styles() {
    wp_enqueue_style( 'lb-custom-features-styles', plugins_url( 'assets/css/style.css', __FILE__ ) );
}
add_action( 'wp_enqueue_scripts', 'lb_custom_features_enqueue_styles' );

function custom_track_product_view() {
    if ( ! is_singular( 'product' ) ) {
        return;
    }
    global $post;
    if ( empty( $_COOKIE['woocommerce_recently_viewed'] ) ) {
        $viewed_products = array();
    } else {
        $viewed_products = (array) explode( '|', $_COOKIE['woocommerce_recently_viewed'] );
    }
    if ( ( $key = array_search( $post->ID, $viewed_products ) ) !== false ) {
        unset( $viewed_products[ $key ] );
    }
    $viewed_products[] = $post->ID;
    if ( sizeof( $viewed_products ) > 15 ) {
        array_shift( $viewed_products );
    }
    wc_setcookie( 'woocommerce_recently_viewed', implode( '|', $viewed_products ) );
}
add_action( 'template_redirect', 'custom_track_product_view', 20 );

function wc_recently_viewed_products_shortcode($atts) {
    $atts = shortcode_atts(array(
        'number' => 6, // Number of products to display
    ), $atts, 'recently_viewed_products');
    $viewed_products = ! empty( $_COOKIE['woocommerce_recently_viewed'] ) ? (array) explode( '|', $_COOKIE['woocommerce_recently_viewed'] ) : array();
    $viewed_products = array_reverse( array_filter( array_map( 'absint', $viewed_products ) ) );
    if ( empty( $viewed_products ) ) {
        return '&nbsp;';
    }
    $viewed_products = array_slice( $viewed_products, 0, $atts['number'] );
    ob_start();
    $args = array(
        'post_type' => 'product',
        'post__in' => $viewed_products,
        'orderby' => 'post__in',
    );

    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
        echo '<h2 class="elementor-heading-title elementor-size-default irone-recently-viewed-products-title">Previously viewed</h2><ul class="irone-recently-viewed-products">';
        while ( $query->have_posts() ) : $query->the_post();
            echo '<li>';
            if ( has_post_thumbnail() ) {
                echo '<a href="' . get_permalink() . '">';
                the_post_thumbnail( 'thumbnail' );
                echo '</a>';
            }
            echo '<h5><a href="' . get_permalink() . '">' . get_the_title() . '</a></h5>';

            echo '</li>';
        endwhile;
        echo '</ul>';
    }
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('recently_viewed_products', 'wc_recently_viewed_products_shortcode');