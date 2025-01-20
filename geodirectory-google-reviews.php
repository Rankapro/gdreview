
<?php
/*
Plugin Name: GeoDirectory Google Reviews Integration
Plugin URI: https://example.com
Description: A plugin to pull Google Reviews into GeoDirectory business listings.
Version: 1.0.0
Author: Your Name
Author URI: https://example.com
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Add Google Place ID field to GeoDirectory Listings
function gd_google_reviews_add_place_id_field( $post_types ) {
    if ( isset( $post_types['gd_place'] ) ) {
        $post_types['gd_place']['fields'][] = array(
            'id'    => 'google_place_id',
            'label' => 'Google Place ID',
            'desc'  => 'Enter the Google Place ID for this business to display reviews.',
            'type'  => 'text',
        );
    }
    return $post_types;
}
add_filter( 'geodir_post_type_fields', 'gd_google_reviews_add_place_id_field' );

// Fetch Google Reviews from the API
function gd_google_reviews_fetch_reviews( $place_id ) {
    $api_key = 'YOUR_GOOGLE_API_KEY'; // Replace with your Google API Key
    $endpoint = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$place_id}&fields=reviews&key={$api_key}";
    
    $response = wp_remote_get( $endpoint );
    if ( is_wp_error( $response ) ) {
        return [];
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    return isset( $data['result']['reviews'] ) ? $data['result']['reviews'] : [];
}

// Display Reviews on the Listing Page
function gd_google_reviews_display( $content ) {
    if ( is_singular( 'gd_place' ) ) {
        $place_id = get_post_meta( get_the_ID(), 'google_place_id', true );
        if ( ! empty( $place_id ) ) {
            $reviews = gd_google_reviews_fetch_reviews( $place_id );

            if ( ! empty( $reviews ) ) {
                $content .= '<h3>Google Reviews</h3><ul class="google-reviews">';
                foreach ( $reviews as $review ) {
                    $content .= '<li>';
                    $content .= '<strong>' . esc_html( $review['author_name'] ) . '</strong>: ';
                    $content .= '<em>' . esc_html( $review['text'] ) . '</em> ';
                    $content .= 'Rating: ' . intval( $review['rating'] ) . '/5';
                    $content .= '</li>';
                }
                $content .= '</ul>';
            } else {
                $content .= '<p>No reviews found for this business.</p>';
            }
        }
    }

    return $content;
}
add_filter( 'the_content', 'gd_google_reviews_display' );

// Auto-Update Functionality
function gd_google_reviews_auto_update( $transient ) {
    if ( empty( $transient->checked ) ) {
        return $transient;
    }

    $remote_version = '1.0.1'; // Replace with the current version on your server
    $plugin_slug = plugin_basename( __FILE__ );
    $plugin_data = get_plugin_data( __FILE__ );

    if ( version_compare( $plugin_data['Version'], $remote_version, '<' ) ) {
        $transient->response[ $plugin_slug ] = (object) array(
            'slug'        => $plugin_slug,
            'new_version' => $remote_version,
            'url'         => 'https://your-server.com/plugin-info',
            'package'     => 'https://your-server.com/download/plugin.zip',
        );
    }

    return $transient;
}
add_filter( 'site_transient_update_plugins', 'gd_google_reviews_auto_update' );
?>
