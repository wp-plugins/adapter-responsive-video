<?php

/*
Plugin Name: Adapter Responsive Video
Plugin URI: www.ryankienstra.com/responsive-video
Description: A video widget that fits any screen size. Also makes all videos in posts resize to the screen. To get started, go to "Appearance" > "Widgets" and create an "Adapter Video" widget.
Version: 1.0.0
Author: Ryan Kienstra
Author URI: www.ryankienstra.com
License: GPLv2
*/

add_action( 'init' , 'arv_localization_callback' ) ;
function arv_localization_callback() {
  load_plugin_textdomain( 'adapter-responsive-video' , false , basename( dirname( __FILE__ ) ) . '/languages' ) ; 
}

add_action( 'widgets_init' , 'arv_register_widget' ) ;
function arv_register_widget() {
  register_widget( 'Adapter_Responsive_Video' ) ; 
}

class Adapter_Responsive_Video extends WP_Widget {

  function __construct() {
    $options =  array( 'classname' => 'adapter-responsive-video' ,
    	               'description' => __( 'Video from YouTube, Vimeo, and more.' , 'adapter-responsive-video' ) ,
		     ) ;
    $this->WP_Widget( 'adapter_responsive_video' , __( 'Adapter Video' , 'adapter-responsive-video' ) , $options ) ;
  }

  function form( $instance )  {
  
    $video_url = isset( $instance[ 'video_url' ] ) ? $instance[ 'video_url' ] : "" ;
    ?>
      <p>
        <label for="<?php echo $this->get_field_id( 'video_url' ) ; ?>">
	  <?php _e( 'Video url' , 'adapter-responsive-video' ) ; ?>
	</label>
	<input type="text" id="<?php echo $this->get_field_id( 'video_url' ) ; ?>" class="widefat" name="<?php echo $this->get_field_name( 'video_url' ) ; ?>" value="<?php echo $video_url ; ?>" placeholder="e.g. www.youtube.com/watch?v=mOXRZ0eYSA0" \>
      </p>
    <?php
  }

  function update( $new_instance , $previous_instance ) {
    $instance = $previous_instance ;
    $video_url = isset( $new_instance[ 'video_url' ] ) ? $new_instance[ 'video_url' ] : "" ;
    if ( $video_url ) {
      $raw_iframe_code = arv_get_raw_iframe_code( $video_url ) ;
      $instance[ 'video_source' ] = arv_get_iframe_attribute( $raw_iframe_code , 'src' ) ;
      $instance[ 'aspect_ratio_class' ] = arv_get_class_for_aspect_ratio( $raw_iframe_code ) ;
      $instance[ 'video_url' ] = strip_tags( $video_url ) ;
    }
    return $instance ;
  }

  function widget( $args , $instance ) { 
    extract( $args ) ;
    $video_source = isset( $instance[ 'video_source' ] ) ? $instance[ 'video_source' ] : "" ;    
    $aspect_ratio_class = isset( $instance[ 'aspect_ratio_class' ] ) ? $instance[ 'aspect_ratio_class' ] : "" ; 
    if ( $video_source ) {
      $bootstrap_responsive_video = get_bootstrap_responsive_video( $video_source , $aspect_ratio_class ) ;
      echo $before_widget . $bootstrap_responsive_video . $after_widget ;
    }
  }

}

function arv_get_raw_iframe_code( $url ) {
  $url_no_tags = strip_tags( $url ) ; 
  $raw_code = wp_oembed_get( $url_no_tags ) ;
  return $raw_code ;
}
  
function get_bootstrap_responsive_video( $src , $class ) {
  $max_width = apply_filters( 'arv_video_max_width' , '580' ) ;
  return 
    "<div class='responsive-video-container' style='max-width:{$max_width}px'>
      <div class='embed-responsive {$class}'>
	 <iframe class='embed-responsive-item' src='{$src}'>
	 </iframe>
       </div>
     </div>\n" ; 
}

function arv_get_iframe_attribute( $iframe , $attribute ) {
  $pattern  = '/<iframe.*?' . $attribute . '=\"([^\"]+?)\"/' ;
  preg_match( $pattern , $iframe , $matches ) ;
  if ( isset( $matches[ 1 ] ) ) {
    return $matches[ 1 ] ;
  }
}

function arv_get_class_for_aspect_ratio( $embed_code ) {    
  $bootstrap_apect_ratio = get_bootstrap_aspect_ratio( $embed_code ) ;
  return  'embed-responsive-' . $bootstrap_apect_ratio ;
}

function get_bootstrap_aspect_ratio( $embed_code ) {
  $aspect_ratio = arv_get_raw_aspect_ratio( $embed_code ) ;
  if ( is_ratio_closer_to_four_by_three( $aspect_ratio ) ) {
    return '4by3' ;
  }
  return '16by9' ;
}

function arv_get_raw_aspect_ratio( $embed_code ) {
  $embed_width = arv_get_iframe_attribute( $embed_code , 'width' ) ;
  $embed_height =  arv_get_iframe_attribute( $embed_code , 'height' ) ;
  if ( $embed_width && $embed_height ) { 
    $aspect_ratio = floatval( $embed_width ) / floatval( $embed_height ) ;
    return $aspect_ratio ;
  }
}

function is_ratio_closer_to_four_by_three( $ratio ) {
  $difference_from_four_by_three = get_difference_from_four_by_three( $ratio ) ;
  $difference_from_sixteen_by_nine = get_difference_from_sixteen_by_nine( $ratio ) ;
  return ( $difference_from_four_by_three < $difference_from_sixteen_by_nine ) ; 
}

function get_difference_from_four_by_three( $value ) {
  $four_by_three = 1.3333 ;
  $difference_from_four_by_three = abs( $value - $four_by_three ) ;
  return $difference_from_four_by_three ; 
}

function get_difference_from_sixteen_by_nine( $value ) {
  $sixteen_by_nine = 1.777 ;
  $difference_from_sixteen_by_nine = abs( $value - $sixteen_by_nine ) ;
  return $difference_from_sixteen_by_nine ;  
}

add_filter( 'embed_oembed_html' , 'arv_result_filter' ) ; 
function arv_result_filter( $raw_embed_code ) {
  $src = arv_get_iframe_attribute( $raw_embed_code , 'src' ) ;
  $class = arv_get_class_for_aspect_ratio( $raw_embed_code ) ;
  $bootstrap_markup = get_bootstrap_responsive_video( $src , $class ) ;
  return "<div class='post-responsive-video'>" . $bootstrap_markup . "</div>" ; 
}