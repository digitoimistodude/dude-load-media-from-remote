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
if ( getenv( 'WP_ENV' ) && ( 'staging' === getenv( 'WP_ENV') || 'development' === getenv( 'WP_ENV' ) ) && getenv( 'REMOTE_MEDIA_URL' ) ) {
  add_filter( 'wp_get_attachment_image_src', __NAMESPACE__ . '\maybe_load_media_from_remote', 999, 3 );
  add_filter( 'wp_prepare_attachment_for_js', __NAMESPACE__ . '\maybe_load_remote_media_file_for_js', 999, 2 );
  add_filter( 'wp_calculate_image_srcset', __NAMESPACE__ . '\maybe_load_media_remote_for_srcset', 999, 5 );
}

/**
 * Load remote file for JS response
 *
 * @param Array $response Response data
 * @param Object $attachment Attachment post object
 * @return Array Possibly modified response data
 */
function maybe_load_remote_media_file_for_js( $response, $attachment ) {
  if ( attached_file_exists( $attachment->ID ) ) {
    return $response;
  }

  if ( isset( $response['url'] ) ) {
    $response['url'] = try_to_load_image_from_remote($response['url']);
  }

  if ( isset( $response['sizes'] ) && is_array( $response['sizes'] ) && count( $response['sizes'] ) ) {
    foreach ( $response['sizes'] as &$size ) {
      $size['url'] = try_to_load_image_from_remote( $size['url'] );
    }
  }

  return $response;
}

/**
 * Check if media file exists, if not, then try to load it from remote
 *
 * @param Array $image Image data
 * @param Int $attachment_id Attachment ID
 */
function maybe_load_media_from_remote( $image, $attachment_id ) {
  if ( attached_file_exists( $attachment_id ) ) {
    return $image;
  }

  if ( isset( $image[0]) && ! empty( $image[0] ) ) {
    $image[0] = try_to_load_image_from_remote( $image[0] );
  }
  return $image;
}

/**
 * Load remote file for srcset attributes.
 *
 * @param array  $sources {
 *     One or more arrays of source data to include in the 'srcset'.
 *
 *     @type array $width {
 *         @type string $url        The URL of an image source.
 *         @type string $descriptor The descriptor type used in the image candidate string,
 *                                  either 'w' or 'x'.
 *         @type int    $value      The source width if paired with a 'w' descriptor, or a
 *                                  pixel density value if paired with an 'x' descriptor.
 *     }
 * }
 * @param array  $size_array     {
 *     An array of requested width and height values.
 *
 *     @type int $0 The width in pixels.
 *     @type int $1 The height in pixels.
 * }
 * @param string $image_src     The 'src' of the image.
 * @param array  $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
 * @param int    $attachment_id Image attachment ID or 0.
 * @return array The 'srcset' attribute value. False on error or when only one source exists.
 */
function maybe_load_media_remote_for_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
  // Original attachment exists, assume that size variations exists as well
  if ( attached_file_exists( $attachment_id ) ) {
    return $sources;
  }

  if ( ! is_array( $sources ) ) {
    return $sources;
  }

  foreach ( $sources as $key => $source ) {
    $sources[ $key ] = try_to_load_image_from_remote( $source['url'] );
  }

  return $sources;
} // end maybe_load_media_remote_for_srcset

/**
 * Try to load image from remote
 *
 * @param String $local_media_url Url to media file in local env
 * @return String Either the remote media url if it exists or local url as fallback
 */
function try_to_load_image_from_remote( $local_media_url ) {
  $remote_media_url = str_replace( getenv( 'WP_HOME'), getenv( 'REMOTE_MEDIA_URL' ), $local_media_url );
  if ( url_exists( $remote_media_url ) ) {
    return $remote_media_url;
  }
  return $local_media_url;
}

/**
 * Test if attached file exists
 *
 * @param Int $id Attachment id
 * @return Bool Test result
 */
function attached_file_exists( $id ) {
  $file_path = get_attached_file( $id );
  if ( $file_path && file_exists( $file_path ) ) {
    return true;
  }
  return false;
}

/**
 * Check if url exists from it's headers
 */
function url_exists($url) {
  $headers = @get_headers($url);
  return is_array($headers) ? preg_match('/^HTTP\\/\\d+\\.\\d+\\s+2\\d\\d\\s+.*$/',$headers[0]) : false;
}
