<?php
/*
Plugin Name: Convert NextGEN Galleries to WordPress
Plugin URI: 
Description: Converts NextGEN galleries to WordPress default galleries.
Version: 1.0
Author: Stefan Senk
Author URI: http://www.senktec.com
License: GPL2


Copyright 2014  Stefan Senk  (email : info@senktec.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function cng_admin_url() {
	return admin_url('options-general.php?page=convert-nextgen-galleries.php');
}

function cng_get_posts_to_convert_query($shortcode = 'nggallery', $post_id = null, $max_number_of_posts = -1) {
	$args = array(
		's'           => '[' . $shortcode,
		'post_type'   => 'any',
		'post_status' => 'any',
		'p' => $post_id,
		'posts_per_page' => $max_number_of_posts
	);
	return new WP_Query( $args );
}

function cng_get_galleries_to_convert($gallery_id = null, $max_number_of_galleries = -1) {
	global $wpdb;
  if ($gallery_id)
  	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ngg_gallery WHERE gid = %d", $gallery_id ) );
  else
  	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ngg_gallery" ) );
}

function cng_find_shortcodes($post, $shortcode='nggallery') {
	$matches = null;
	preg_match_all( '/\[' . $shortcode . '.*?\]/si', $post->post_content, $matches );
	return $matches[0];
}

function cng_get_gallery_id_from_shortcode($shortcode) {
	$atts = shortcode_parse_atts($shortcode);
	return intval( $atts['id'] );
}

function cng_get_singlepic_atts_from_shortcode($shortcode) {
	return shortcode_parse_atts($shortcode);
}

function cng_list_galleries($galleries) {
	echo '<h3>Listing ' . count($galleries) . ' galleries to convert:</h3>';

	echo '<table>';
	echo '<tr>';
	echo '<th>Gallery ID</th>';
	echo '<th>Gallery Title</th>';
	echo '<th colspan="2">Actions</th>';
	echo '<tr>';
	foreach ( $galleries as $gallery ) {
		echo '<tr>';
		echo '<td>' . $gallery->gid . '</td>';
		echo '<td>' . $gallery->title . '</td>';
		echo '<td><a href="' . admin_url('admin.php?page=nggallery-manage-gallery&mode=edit&gid=' . $gallery->gid) . '">Edit gallery</a></td>';
		echo '<td><a href="' . cng_admin_url() . '&amp;action=convert&gallery=' . $gallery->gid . '" class="button">Convert</a></td>';
		echo '<tr>';
	}
	echo '</table>';
}

function cng_post_header($post) {
  $show_post_link = '<a href="' . get_permalink( $post->ID ) . '">Show</a>';
  $edit_post_link = '<a href="' . admin_url('post.php?action=edit&amp;post=' . $post->ID) . '">Edit</a>';
  return '<h4>In ' . $post->post_type . ' ' . $post->post_title . ' ' . $show_post_link . ' ' .  $edit_post_link . ':</h4>';
}

function cng_convert_galleries($galleries) {
	global $wpdb;

	echo '<h3>Converting ' . count($galleries) . ' galleries:</h3>';

  $nggallery_posts = cng_get_posts_to_convert_query('nggallery')->posts;
  $singlepic_posts = cng_get_posts_to_convert_query('singlepic')->posts;

	foreach ( $galleries as $gallery ) {

	  $images = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ngg_pictures WHERE galleryid = %d ORDER BY sortorder, pid ASC", $gallery->gid ) );

	  $attachment_ids = array();

	  foreach ( $images as $image ) {
		  $existing_image_path =  ABSPATH . trailingslashit( $gallery->path ) . $image->filename;
		  if ( ! file_exists ($existing_image_path) ) {
			  echo "ERROR: File '$existing_image_path' not found.<br>";
			  continue;
		  }

		  $tmp_image_path = get_temp_dir() . $image->filename;
		  copy($existing_image_path, $tmp_image_path);

		  $file_array['name'] = $image->filename;
		  $file_array['tmp_name'] = $tmp_image_path;

		  if ( ! trim( $image->alttext ) )
			  $image->alttext = $image->filename;

		  $post_data = array(
			  'post_title' => $image->alttext,
			  /*'post_content' => $image->description,*/
			  'post_excerpt' => $image->description,
			  'menu_order' => $image->sortorder
		  );
		  $id = media_handle_sideload( $file_array, null, $image->description, $post_data );
		  if ( is_wp_error($id) ) {
			  echo "ERROR: media_handle_sideload() filed for '$existing_image_path'.<br>";
			  continue;
		  }

		  array_push( $attachment_ids, $id );
		  $attachment = get_post( $id );
		  update_post_meta( $attachment->ID, '_wp_attachment_image_alt', $image->alttext );

      foreach ( $singlepic_posts as $post ) {
        $header_already_shown = false;
	      foreach ( cng_find_shortcodes($post, 'singlepic') as $shortcode ) {

		      $atts = cng_get_singlepic_atts_from_shortcode($shortcode);
          $pid = intval( $atts['id'] );

          if ($pid == $image->pid) {

            if (strpos($atts['float'],'left') !== false)
              $align = 'alignleft ';
            elseif (strpos($atts['float'],'right') !== false)
              $align = 'alignright ';
            else
              $align = 'alignnone ';

            $new_code = wp_get_attachment_link($attachment->ID);
            $new_code = str_replace('class="', 'title="' . $image->description . '"class="' . $align , $new_code);
            $new_code = str_replace('<a ', '<a class="singlepic ' . $align . '" ' , $new_code);

    				$post->post_content = str_replace( $shortcode, $new_code, $post->post_content );
    				wp_update_post( $post );

            if (!$header_already_shown) {
              wp_update_post( array(
                      'ID' => $attachment->ID,
                      'post_parent' => $post->ID
              ) );
              echo cng_post_header($post);
              $header_already_shown = true;
            }
    				echo "Replaced <code>$shortcode</code> with <code>" . esc_html($new_code) . "</code>.<br>";
          }
        }
      }

      /* If the "exclude" property is set, we're not supposed to display the image as part of the gallery. */
      if ($image->exclude != 0)
        array_pop( $attachment_ids );
	  }

    foreach ( $nggallery_posts as $post ) {
      $header_already_shown = false;
      foreach ( cng_find_shortcodes($post, 'nggallery') as $shortcode ) {
	      $gid = cng_get_gallery_id_from_shortcode($shortcode);
        if ($gid == $gallery->gid) {
					$new_shortcode = '[gallery columns="3" link="file" ids="'. implode( ',', $attachment_ids ) . '"]';
					$post->post_content = str_replace( $shortcode, $new_shortcode, $post->post_content );
					wp_update_post( $post );
          if (!$header_already_shown) {
            echo cng_post_header($post);
            $header_already_shown = true;
          }
					echo "Replaced <code>$shortcode</code> with <code>$new_shortcode</code>.<br>";
        }
      }
    }
  }
}

add_filter( 'plugin_action_links', function($links, $file) {
	if ( $file == 'convert-nextgen-galleries/convert-nextgen-galleries.php' ) {
		array_unshift( $links, '<a href="' . cng_admin_url() . '">' . __( 'Settings', 'convert-nextgen-galleries' ) . '</a>' );
	}
	return $links;
}, 10, 2 );

add_action('admin_menu', function() {
	add_options_page( 
		__( 'Convert NextGEN Galleries', 'convert-nextgen-galleries' ),
		__( 'Convert NextGEN Galleries', 'convert-nextgen-galleries' ),
		'manage_options', 'convert-nextgen-galleries.php', function() {

		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have permission to access this page.', 'convert-nextgen-galleries' ) );
		}
?>
		<div class="wrap">
			<h2><?php _e( 'Convert NextGEN Galleries to WordPress', 'convert-nextgen-galleries' ); ?></h2>
			<?php 
				$gallery_id = isset($_GET['gallery']) ? $_GET['gallery'] : null;
				$max_num_to_convert = isset($_GET['max_num']) ? $_GET['max_num'] : -1;

				$galleries_to_convert = cng_get_galleries_to_convert( $gallery_id, $max_num_to_convert );

				if ( isset( $_GET['action'] ) ) {
					if ( $_GET['action'] == 'list' ) {
						cng_list_galleries($galleries_to_convert);
					} elseif ( $_GET['action'] == 'convert' ) {
						cng_convert_galleries($galleries_to_convert);
					}
				} else {
					echo '<h3>' . count($galleries_to_convert) . ' galleries to convert</h3>';
				}
			?>
			<p><a class="" href="<?php echo cng_admin_url() . '&amp;action=list' ?>">List galleries to convert</a></p>
			<p><a class="button" href="<?php echo cng_admin_url() . '&amp;action=convert' ?>">Convert all galleries</a></p>
		</div>  
<?php
	});
});
