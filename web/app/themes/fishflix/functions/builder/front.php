<?php
/**
 * Custom meta fields | Frontend
 *
 * @package Betheme
 * @author Muffin group
 * @link http://muffingroup.com
 */


/*
 * Muffin Builder
 * 
 * Main frontent builder functions
 */

if( ! function_exists( 'mfn_builder_print_content' ) )
{
	/**
	 * PRINT WordPress Editor Content
	 * 
	 * @param int $post_id
	 * @param bool $content_field
	 */
	function mfn_builder_print_content( $post_id, $content_field = false ){

		$class = get_post_field( 'post_content', $post_id ) ? 'has_content' : 'no_content' ;

		echo '<div class="section the_content '. $class .'">';
		if( ! get_post_meta( $post_id, 'mfn-post-hide-content', true )){
			echo '<div class="section_wrapper">';
			echo '<div class="the_content_wrapper">';
			if( $content_field ){
				echo apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) );
			} else {
				the_content();
			}
			echo '</div>';
			echo '</div>';
		}
		echo '</div>';
	}
}


if( ! function_exists( 'mfn_builder_print' ) )
{
	/**
	 * PRINT Muffin Builder
	 * 
	 * @param int $post_id
	 * @param bool $content_field
	 */
	function mfn_builder_print( $post_id, $content_field = false ){

		// Sizes for Items
		$classes = array(
			'divider' 	=> 'divider',
			'1/6' 		=> 'one-sixth',
			'1/5' 		=> 'one-fifth',
			'1/4' 		=> 'one-fourth',
			'1/3' 		=> 'one-third',
			'1/2' 		=> 'one-second',
			'2/3' 		=> 'two-third',
			'3/4' 		=> 'three-fourth',
			'1/1' 		=> 'one'
		);

		// Sidebars list
		$sidebars = mfn_opts_get( 'sidebars' );

		
		// $mfn_items | Wraps with Items => Sections ------------------------------------

		$mfn_items = get_post_meta( $post_id, 'mfn-page-items', true );
		

		// FIX | Muffin Builder 2.0 Compatibility
		
		if( $mfn_items && ! is_array( $mfn_items ) ){
			$mfn_items = unserialize( base64_decode( $mfn_items ) );
		}
		

		// WordPress Editor Content ---------------------------------	
		if( mfn_opts_get('display-order') == 1 ) mfn_builder_print_content( $post_id, $content_field );

		
		// Content Builder -------------------------------------
		
		if( post_password_required( ) ){
				
			// prevents duplication of the password form
			if( get_post_meta( $post_id, 'mfn-post-hide-content', true ) ){
				echo '<div class="section the_content">';
					echo '<div class="section_wrapper">';
						echo '<div class="the_content_wrapper">';
							echo get_the_password_form( );
						echo '</div>';
					echo '</div>';
				echo '</div>';
			}

		} elseif( is_array( $mfn_items ) ){

			// Sections
			foreach( $mfn_items as $section ){
					
// 				print_r($section['attr']);

				// section attributes -----------------------------------

				// classes ------------------------
				$section_class 		= array();

				$section_class[]	= $section['attr']['style'];
				$section_class[]	= $section['attr']['class'];

				if( key_exists( 'visibility', $section['attr']) ){
					$section_class[] = $section['attr']['visibility'];
				}
				if( key_exists( 'bg_video_mp4', $section['attr'] ) && $section['attr']['bg_video_mp4'] ){
					$section_class[] = 'has-video';
				}
				if( key_exists( 'navigation', $section['attr'] ) && $section['attr']['navigation'] ){
					$section_class[] = 'has-navi';
				}
				if( key_exists( 'column_margin', $section['attr'] ) && $section['attr']['column_margin'] ){
					$section_class[] = 'column-margin-'. $section['attr']['column_margin'];
				}

				$section_class		= implode(' ', $section_class);
					
				// styles -------------------------
				$section_style 		= '';

				$section_style[] 	= 'padding-top:'. intval( $section['attr']['padding_top'] ) .'px';
				$section_style[] 	= 'padding-bottom:'. intval( $section['attr']['padding_bottom'] ) .'px';
				$section_style[] 	= 'background-color:'. $section['attr']['bg_color'];

				// background image attributes
				if( $section['attr']['bg_image'] ){
					$section_style[] 	= 'background-image:url('. $section['attr']['bg_image'] .')';
					$section_bg_attr 	= explode(';', $section['attr']['bg_position']);
					$section_style[] 	= 'background-repeat:'. $section_bg_attr[0];
					$section_style[] 	= 'background-position:'. $section_bg_attr[1];
					$section_style[] 	= 'background-attachment:'. $section_bg_attr[2];
					$section_style[] 	= 'background-size:'. $section_bg_attr[3];
					$section_style[] 	= '-webkit-background-size:'. $section_bg_attr[3];
				}

				$section_style 		= implode('; ', $section_style );

				// parallax -------------------------
				$parallax = false;
				if( $section['attr']['bg_image'] && ( $section_bg_attr[2] == 'fixed' ) ){
					if( ! key_exists(4, $section_bg_attr) || $section_bg_attr[4] != 'still' ){
						$parallax = 'data-stellar-background-ratio="0.5"';
					}
				}

				// IDs ------------------------------
				if( key_exists('section_id', $section['attr']) && $section['attr']['section_id'] ){
					$section_id = 'id="'. $section['attr']['section_id'] .'"';
				} else {
					$section_id = false;
				}

				// print ------------------------------------------------

				echo '<div class="section mcb-section '. $section_class .'" '. $section_id .' style="'. $section_style .'" '. $parallax .'>'; // 100%

					// Video ------------------------
					if( key_exists( 'bg_video_mp4', $section['attr'] ) && $mp4 = $section['attr']['bg_video_mp4'] ){
						echo '<div class="section_video">';
	
							echo '<div class="mask"></div>';
								
							$poster = $section['attr']['bg_image'];
								
							echo '<video poster="'. $poster .'" controls="controls" muted="muted" preload="auto" loop="true" autoplay="true">';
		
								echo '<source type="video/mp4" src="'. $mp4 .'" />';
								if( key_exists( 'bg_video_ogv', $section['attr'] ) && $ogv = $section['attr']['bg_video_ogv'] ){
									echo '<source type="video/ogg" src="'. $ogv .'" />';
								}
			
								echo '<object width="1900" height="1060" type="application/x-shockwave-flash" data="'. THEME_URI .'/js/flashmediaelement.swf">';
								echo '<param name="movie" value="'. THEME_URI .'/js/flashmediaelement.swf" />';
								echo '<param name="flashvars" value="controls=true&file='. $mp4 .'" />';
								echo '<img src="'. $poster .'" title="No video playback capabilities" />';
								echo '</object>';
		
							echo '</video>';
							
						echo '</div>';
					}
						
					// Separator ------------------------
					if( key_exists( 'divider', $section['attr'] ) && $divider = $section['attr']['divider'] ){
						echo '<div class="section-divider '. $divider .'"></div>';
					}
						
					// Navigation ------------------------
					if( key_exists( 'navigation', $section['attr'] ) && $divider = $section['attr']['navigation'] ){
						echo '<div class="section-nav prev"><i class="icon-up-open"></i></div>';
						echo '<div class="section-nav next"><i class="icon-down-open"></i></div>';
					}
	
					echo '<div class="section_wrapper mcb-section-inner">'; // WIDTH + margin: 0 auto
					
					
						// Wraps --------------------------------------------------------
						
					
						// FIX | Muffin Builder 2.0 Compatibility
						if( ! key_exists( 'wraps', $section ) && is_array( $section['items'] ) ){
								
							$fix_wrap = array(
								'size'	=> '1/1',
								'items'	=> $section['items'],
							);
								
							$section['wraps'] = array( $fix_wrap );
								
						}
						

						if( key_exists( 'wraps', $section ) && is_array( $section['wraps'] ) ){
							foreach( $section['wraps'] as $wrap ){
								
								
								// Wrap | Classes & Styles ----------
								
								$wrap_class  = $classes[ $wrap['size'] ];				// size of item
							
								
								// Wrap | Print ---------------------
								
								echo '<div class="wrap mcb-wrap '. $wrap_class .' clearfix">';
								
										
									// Items --------------------------------------------
									
									if( is_array( $wrap['items'] ) ){
										foreach( $wrap['items'] as $item ){
					
											if( function_exists( 'mfn_print_'. $item['type'] ) ){
					
												// Item | Size
												$class  = $classes[$item['size']];
												
												// Item | Type
												$class .= ' column_'. $item['type'];
												
												// Item | Custom Classes
												if( isset( $item['fields']['classes'] ) ){
													$class .= ' '. $item['fields']['classes'];
												}
										
												// Column | Margin Bottom
												if( $item['type'] == 'column' && isset( $item['fields']['margin_bottom'] ) ){
													$class .= ' column-margin-'. $item['fields']['margin_bottom'];
												}
												
												
												// Print	
												echo '<div class="column mcb-column '. $class .'">';
													call_user_func( 'mfn_print_'. $item['type'], $item );
												echo '</div>';
												
											}
												
										}
									}
									
									
								echo '</div>';
									
							}
						}

	
					echo '</div>';
					
				echo '</div>';
			}
		}

		
		// WordPress Editor Content -------------------------------------
		if( mfn_opts_get('display-order') == 0 ) mfn_builder_print_content( $post_id, $content_field );
		
	}
}


/*
 * Print Builder Items
 * 
 * Do shortcodes connected with items
 */

if( ! function_exists( 'mfn_print_accordion' ) )
{
	/**
	 * [accordion]
	 */
	function mfn_print_accordion( $item ) {
		echo sc_accordion( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_article_box' ) )
{
	/**
	 * [article_box]
	 */
	function mfn_print_article_box( $item ) {
		echo sc_article_box( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_blockquote' ) )
{
	/**
	 * [blockquote]
	 */
	function mfn_print_blockquote( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_blockquote( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_blog' ) )
{
	/**
	 * [blog]
	 */
	function mfn_print_blog( $item ) {
		echo sc_blog( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_blog_news' ) )
{
	/**
	 * [blog_news]
	 */
	function mfn_print_blog_news( $item ) {
		echo sc_blog_news( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_blog_slider' ) )
{
	/**
	 * [blog_slider]
	 */
	function mfn_print_blog_slider( $item ) {
		echo sc_blog_slider( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_call_to_action' ) )
{
	/**
	 * [call_to_action]
	 */
	function mfn_print_call_to_action( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_call_to_action( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_chart' ) )
{
	/**
	 * [chart]
	 */
	function mfn_print_chart( $item ) {
		echo sc_chart( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_clients' ) )
{
	/**
	 * [clients]
	 */
	function mfn_print_clients( $item ) {
		echo sc_clients( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_clients_slider' ) )
{
	/**
	 * [clients_slider]
	 */
	function mfn_print_clients_slider( $item ) {
		echo sc_clients_slider( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_code' ) )
{
	/**
	 * [code]
	 */
	function mfn_print_code( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_code( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_column' ) )
{
	/**
	 * [column]
	 */
	function mfn_print_column( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		
		$column_class 	= '';
		$column_attr 	= '';
		$style 			= '';
		
		// align
		if( key_exists('align', $item['fields']) && $item['fields']['align'] ){
			$column_class	.= ' align_'. $item['fields']['align'];
		}	
		
		// animate
		if( key_exists('animate', $item['fields']) && $item['fields']['animate'] ){
			$column_class	.= ' animate';
			$column_attr	.= ' data-anim-type="'. $item['fields']['animate'] .'"'; 
		}

		// background
		if( key_exists('column_bg', $item['fields']) && $item['fields']['column_bg'] ){
			$style .= ' background-color:'. $item['fields']['column_bg'] .';';
		}
		
		// padding
		if( key_exists('padding', $item['fields']) && $item['fields']['padding'] ){
			$style .= ' padding:'. $item['fields']['padding'] .';';
		}
		
		echo '<div class="column_attr'. $column_class .'" '. $column_attr .' style="'. $style .'">';
			echo do_shortcode( $item['fields']['content'] );
		echo '</div>';
	}
}

if( ! function_exists( 'mfn_print_contact_box' ) )
{
	/**
	 * [contact_box]
	 */
	function mfn_print_contact_box( $item ) {
		echo sc_contact_box( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_content' ) )
{
	/**
	 * [content]
	 */
	function mfn_print_content( $item ) {
		echo '<div class="the_content">';
			echo '<div class="the_content_wrapper">';
				the_content();
			echo '</div>';
		echo '</div>';
	}
}

if( ! function_exists( 'mfn_print_countdown' ) )
{
	/**
	 * [countdown]
	 */
	function mfn_print_countdown( $item ) {
		echo sc_countdown( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_counter' ) )
{
	/**
	 * [counter]
	 */
	function mfn_print_counter( $item ) {
		echo sc_counter( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_divider' ) )
{
	/**
	 * [divider]
	 */
	function mfn_print_divider( $item ) {
		echo sc_divider( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_fancy_divider' ) )
{
	/**
	 * [fancy_divider]
	 */
	function mfn_print_fancy_divider( $item ) {
		echo sc_fancy_divider( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_fancy_heading' ) )
{
	/**
	 * [fancy_heading]
	 */
	function mfn_print_fancy_heading( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_fancy_heading( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_faq' ) )
{
	/**
	 * [faq]
	 */
	function mfn_print_faq( $item ) {
		echo sc_faq( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_feature_list' ) )
{
	/**
	 * [feature_list]
	 */
	function mfn_print_feature_list( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_feature_list( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_flat_box' ) )
{
	/**
	 * [flat_box]
	 */
	function mfn_print_flat_box( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_flat_box( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_hover_box' ) )
{
	/**
	 * [hover_box]
	 */
	function mfn_print_hover_box( $item ) {
		echo sc_hover_box( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_hover_color' ) )
{
	/**
	 * [hover_color]
	 */
	function mfn_print_hover_color( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_hover_color( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_how_it_works' ) )
{
	/**
	 * [how_it_works]
	 */
	function mfn_print_how_it_works( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_how_it_works( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_icon_box' ) )
{
	/**
	 * [icon_box]
	 */
	function mfn_print_icon_box( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_icon_box( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_image' ) )
{
	/**
	 * [image]
	 */
	function mfn_print_image( $item ) {
		echo sc_image( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_info_box' ) )
{
	/**
	 * [info_box]
	 */
	function mfn_print_info_box( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_info_box( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_list' ) )
{
	/**
	 * [list]
	 */
	function mfn_print_list( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_list( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_map' ) )
{
	/**
	 * [map]
	 */
	function mfn_print_map( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_map( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_offer' ) )
{
	/**
	 * [offer]
	 */
	function mfn_print_offer( $item ) {
		echo sc_offer( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_offer_thumb' ) )
{
	/**
	 * [offer_thumb]
	 */
	function mfn_print_offer_thumb( $item ) {
		echo sc_offer_thumb( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_opening_hours' ) )
{
	/**
	 * [opening_hours]
	 */
	function mfn_print_opening_hours( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_opening_hours( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_our_team' ) )
{
	/**
	 * [our_team]
	 */
	function mfn_print_our_team( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_our_team( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_our_team_list' ) )
{
	/**
	 * [our_team_list]
	 */
	function mfn_print_our_team_list( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_our_team_list( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_photo_box' ) )
{
	/**
	 * [photo_box]
	 */
	function mfn_print_photo_box( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_photo_box( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_placeholder' ) )
{
	/**
	 * [placeholder]
	 */
	function mfn_print_placeholder( $item ) {
		echo '<div class="placeholder">&nbsp;</div>';
	}
}

if( ! function_exists( 'mfn_print_portfolio' ) )
{
	/**
	 * [portfolio]
	 */
	function mfn_print_portfolio( $item ) {
		echo sc_portfolio( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_portfolio_grid' ) )
{
	/**
	 * [portfolio_grid]
	 */
	function mfn_print_portfolio_grid( $item ) {
		echo sc_portfolio_grid( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_portfolio_photo' ) )
{
	/**
	 * [portfolio_photo]
	 */
	function mfn_print_portfolio_photo( $item ) {
		echo sc_portfolio_photo( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_portfolio_slider' ) )
{
	/**
	 * [portfolio_slider]
	 */
	function mfn_print_portfolio_slider( $item ) {
		echo sc_portfolio_slider( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_pricing_item' ) )
{
	/**
	 * [pricing_item]
	 */
	function mfn_print_pricing_item( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_pricing_item( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_progress_bars' ) )
{
	/**
	 * [progress_bars]
	 */
	function mfn_print_progress_bars( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_progress_bars( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_promo_box' ) )
{
	/**
	 * [promo_box]
	 */
	function mfn_print_promo_box( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_promo_box( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_quick_fact' ) )
{
	/**
	 * [quick_fact]
	 */
	function mfn_print_quick_fact( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_quick_fact( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_shop_slider' ) )
{
	/**
	 * [shop_slider]
	 */
	function mfn_print_shop_slider( $item ) {
		echo sc_shop_slider( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_sidebar_widget' ) )
{
	/**
	 * [sidebar_widget]
	 */
	function mfn_print_sidebar_widget( $item ) {
		echo sc_sidebar_widget( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_slider' ) )
{
	/**
	 * [slider]
	 */
	function mfn_print_slider( $item ) {
		echo sc_slider( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_slider_plugin' ) )
{
	/**
	 * [slider_plugin]
	 */
	function mfn_print_slider_plugin( $item ) {
		echo sc_slider_plugin( $item['fields'] );
	}
}

if( ! function_exists( 'sc_sliding_box' ) )
{
	/**
	 * [sliding_box]
	 */
	function mfn_print_sliding_box( $item ) {
		echo sc_sliding_box( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_story_box' ) )
{
	/**
	 * [story_box]
	 */
	function mfn_print_story_box( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_story_box( $item['fields'], $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_tabs' ) )
{
	/**
	 * [tabs]
	 */
	function mfn_print_tabs( $item ) {
		echo sc_tabs( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_testimonials' ) )
{
	/**
	 * [testimonials]
	 */
	function mfn_print_testimonials( $item ) {
		echo sc_testimonials( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_testimonials_list' ) )
{
	/**
	 * [testimonials_list]
	 */
	function mfn_print_testimonials_list( $item ) {
		echo sc_testimonials_list( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_timeline' ) )
{
	/**
	 * [timeline]
	 */
	function mfn_print_timeline( $item ) {
		echo sc_timeline( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_trailer_box' ) )
{
	/**
	 * [trailer_box]
	 */
	function mfn_print_trailer_box( $item ) {
		echo sc_trailer_box( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_video' ) )
{
	/**
	 * [video]
	 */
	function mfn_print_video( $item ) {
		echo sc_video( $item['fields'] );
	}
}

if( ! function_exists( 'mfn_print_visual' ) )
{
	/**
	 * [visual]
	 */
	function mfn_print_visual( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo do_shortcode( $item['fields']['content'] );
	}
}

if( ! function_exists( 'mfn_print_zoom_box' ) )
{
	/**
	 * [zoom_box]
	 */
	function mfn_print_zoom_box( $item ) {
		if( ! key_exists('content', $item['fields']) ) $item['fields']['content'] = '';
		echo sc_zoom_box( $item['fields'], $item['fields']['content'] );
	}
}

?>