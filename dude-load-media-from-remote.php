<?php
/**
 * Try to load images from remote if not present in local env.
 * Environment variable REMOTE_MEDIA_URL must be defined for this to work
 *
 * Plugin Name:       Load images from remote
 * Plugin URI:        https://github.com/digitoimistodude/dude-load-media-from-remote
 * Description:       Try to load images from remote if not present in local env
 * Version:           0.1.0
 * Author:            Digitoimisto Dude Oy
 * Author URI:        https://dude.fi
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Dude_Load_Media_From_Remote;

// Run the plugin only in staging or development environments when the REMOTE_MEDIA_URL is defined
if ( getenv( 'WP_ENV' ) && ( 'staging' === getenv( 'WP_ENV' ) || 'development' === getenv( 'WP_ENV' ) ) && getenv( 'REMOTE_MEDIA_URL' ) ) {
  add_filter( 'wp_get_attachment_image_src', __NAMESPACE__ . '\maybe_load_media_from_remote', 999, 3 );
  add_filter( 'wp_prepare_attachment_for_js', __NAMESPACE__ . '\maybe_load_remote_media_file_for_js', 999, 2 );
}

/**
 * Load remote file for JS response
 *
 * @param   array  $response   Response data
 * @param   object $attachment Attachment post object
 * @return  array              Possibly modified response data
 */
function maybe_load_remote_media_file_for_js( $response, $attachment ) {
  if ( attached_file_exists( $attachment->ID ) ) {
    return $response;
  }

  if ( isset( $response['url'] ) ) {
    $response['url'] = try_to_load_image_from_remote( $response['url'] );
  }

  if ( isset( $response['sizes'] ) && is_array( $response['sizes'] ) && count( $response['sizes'] ) ) {
    foreach ( $response['sizes'] as &$size ) {
      $size['url'] = try_to_load_image_from_remote( $size['url'] );
    }
  }

  return $response;
} // end maybe_load_remote_media_file_for_js

/**
 * Check if media file exists, if not, then try to load it from remote
 *
 * @param array $image          Image data
 * @param int   $attachment_id  Attachment ID
 */
function maybe_load_media_from_remote( $image, $attachment_id ) {
  if ( attached_file_exists( $attachment_id ) ) {
    return $image;
  }

  if ( isset( $image[0] ) && ! empty( $image[0] ) ) {
    $image[0] = try_to_load_image_from_remote( $image[0] );
  }

  return $image;
} // end maybe_load_media_from_remote

/**
 * Try to load image from remote
 *
 * @param  string $local_media_url  Url to media file in local env
 * @return string                   Either the remote media url if it exists or local url as fallback
 */
function try_to_load_image_from_remote( $local_media_url ) {
  $remote_media_url = str_replace( getenv( 'WP_HOME' ), getenv( 'REMOTE_MEDIA_URL' ), $local_media_url );
  if ( url_exists( $remote_media_url ) ) {
    return $remote_media_url;
  }

  return $local_media_url;
} // end try_to_load_image_from_remote

/**
 * Test if attached file exists
 *
 * @param  int $id  Attachment id
 * @return bool     Test result
 */
function attached_file_exists( $id ) {
  $file_path = get_attached_file( $id );
  if ( $file_path && file_exists( $file_path ) ) {
    return true;
  }

  return false;
} // end attached_file_exists

/**
 * Check if url exists from it's headers
 * @param  string $url URL to check
 * @return boolean     Does the url exists
 */
function url_exists( $url ) {
  $headers = @get_headers( $url );
  return is_array( $headers ) ? preg_match( '/^HTTP\\/\\d+\\.\\d+\\s+2\\d\\d\\s+.*$/', $headers[0] ) : false;
} // end url_exists
