<?php
/**
 * Wordpress video gallery widgets helper file.
 * @category   VidFlix
 * @package    Fliche Video Gallery
 * @version    0.9.0
 * @author     Company Juice <support@companyjuice.com>
 * @copyright  Copyright (C) 2016 Company Juice. All rights reserved.
 * @license    GNU General Public License http://www.gnu.org/copyleft/gpl.html 
 */

/**
 * This function is used to get popular / recent / featured / random videos 
 * 
 * @param unknown $type
 * @param unknown $thumImageorder
 * @param unknown $showLimit
 * @param unknown $vID
 * @param unknown $pID
 * @return object
 */
function getWidgetVideos ( $type, $thumImageorder, $showLimit, $vID, $pID) {
    global $wpdb;    
    /** Get where condition for query based on video types */
    $where    = getCase ($type);
    /** If type is related then add condition */
    if($type == 'related') {
      $where =  " AND b.playlist_id = $pID AND w.vid != $vID ";
    }    
    /** Query to get videos and return values */
    return $wpdb->get_results( "SELECT DISTINCT w.*,s.guid,b.playlist_id,p.playlist_name,p.playlist_slugname FROM " . $wpdb->prefix . "hdflvvideoshare w
                LEFT JOIN " . $wpdb->prefix . "hdflvvideoshare_med2play b ON w.vid = b.media_id
                LEFT JOIN " . $wpdb->prefix . "hdflvvideoshare_playlist p ON p.pid = b.playlist_id
                LEFT JOIN " . $wpdb->prefix . "posts s ON s.ID = w.slug
                WHERE w.publish = '1' AND p.is_publish = '1' $where GROUP BY w.vid ORDER BY $thumImageorder  LIMIT " . $showLimit) ;    
}

/**
 * Function to get where condition for popular / recent / featured / random videos 
 * 
 * @param unknown $type
 * @return string
 */
function getCase ( $type ) {
    /** Check type value */
    switch ( $type ) {
        case 'feature':
        case 'featured':
          /** Set where condition for featured videos */
          $where = " AND w.featured = '1' ";
        break;
        case 'popular':
        case 'random':
        case 'recent':
        default:
          /** Set where condition as empty for other videos */
          $where = " ";
          break;
    }
    /** Return where condition value */
    return $where;
}
/**
 * Get count of popular / recent / featured / random videos
 * 
 * @param unknown $type
 * @param unknown $pID
 * @return string
 */
function getWidgetVideosCount ($type, $pID) {
    global $wpdb;
    
    $orderby  = ' ';    
    /** Get where condition for count query based on video types */
    $where    = getCase ($type);
    /** If type is related then add condition */
    if( $type == 'related' ) {
      $where = " AND b.playlist_id= $pID ";
    }
    /** If type is recent / random / related then set order by in descending */
    if( $type == 'recent' || $type == 'random' || $type == 'related' ) {
       $orderby = ' w.vid DESC ' ;
    }
    /** Get video details */
    $moreF    = $wpdb->get_results ( "SELECT COUNT(w.vid) AS fliche FROM " . $wpdb->prefix . "hdflvvideoshare w
                     LEFT JOIN " . $wpdb->prefix . "hdflvvideoshare_med2play b ON w.vid = b.media_id
                     LEFT JOIN " . $wpdb->prefix . "hdflvvideoshare_playlist p ON p.pid = b.playlist_id
                     WHERE w.publish='1' AND p.is_publish='1' $where" );
    /** Check video detials are exist */
    if( !empty($moreF)) {
      /** If exist then return count value */
      return $moreF [0]->fliche;
    } else {
      /** Else return empty */
      return '';
    }
}

/**
 * Function to display popular / recent / featured / random widget videos
 * 
 * @param unknown $title
 * @param unknown $type
 * @param unknown $videosData
 * @param unknown $show
 * @param unknown $pID
 * @param unknown $playlist_slugname
 * @return string
 */
function displayWidgetVideos ($title, $type, $videosData, $show , $pID, $playlist_slugname ) {
      /** Get more page id , videos link for featured videos and plugin settings value */
      $moreName         = morePageID();
      $more_videos_link = get_morepage_permalink ( $moreName, $type );
      $settings_result  = getPluginSettings();
      
      /** Display widget tilte */
      $div        = '<div id="' . $type . '-videos"  class="sidebar-wrap "> <h3 class="widget-title">';      
      /** Check type is realted */
      if($type == 'related') {
          /** Set title for widgets */
          $link = $title;
          if (! empty ( $pID )) {
            /** Set link for related vidos title */
            $link = '<a href=' . home_url() . '/?page_id=' . $moreName . '&amp;playid=' . $pID . '>' . $title . '</a>';
          }
          $div      .= $link;
      } else{
        $div      .= '<a href="' . $more_videos_link . '">' . $title . '</a>';
      }       
      $div        .= ' </h3>';   
      
      /** Get count of videos */
      $videosCount = getWidgetVideosCount ($type, $pID);
      /** Display video thumbs */
      $div    .= '<ul class="ulwidget">';
      /** Check videos are exist */
      if (! empty ( $videosData )) {
        /** Looping widget video detials */
        foreach ( $videosData as $videodata ) {
          /** Get video file type */
          $file_type  = $videodata->file_type;
          /** Get video permalink */
          $guid       = get_video_permalink ( $videodata->slug );
          /** Get video name */
          $name = limitTitle ( $videodata->name );
          /** Get thumb image url based on file type from helper */
          $imageFea   = $videodata->image;          
          $imageFea   = getImagesValue ($videodata->image, $file_type, $videodata->amazon_buckets, '');
          /** Output to screen */
          $div    .= '<li class="clearfix sideThumb">';
          /** Display video thumb */
          $div    .= '<div class="imgBorder"> <a href="' . $guid . '" title="' . $videodata->name . '"> <img src="' . $imageFea . '" alt="' . $videodata->name . '" class="img" width="120" height="80" style="width: 120px; height: 80px;" /> </a>';
         /** Display video duration */
          if (!empty ($videodata->duration) && $videodata->duration != 0.00) {
            $div  .= '<span class="video_duration">' . $videodata->duration . '</span>';
          }
          $div    .= '</div>';
          /** Display video title */          
          $div    .= '<div class="side_video_info"> <a title="' . $videodata->name . '" class="videoHname" href="' . $guid . '">' . $name . '</a> <div class="clear"></div>';
          /** Check view is enabled in settings page */
          if ($settings_result->view_visible == 1) {
              /** Display views count */
              $div        .= displayViews ( $videodata->hitcount );
          } 
          /** Check rating is enabled in settings page */
          if ($settings_result->ratingscontrol == 1) {
              /** Display ratingss count */
              $div        .= getRatingValue ( $videodata->rate, $videodata->ratecount, '' );
          }
          $div    .= '<div class="clear"></div> <div class="clear"></div> </div></li>';
        }
      } else {
        /** Display no videos link */
        $div      .= "<li>" . __ ( 'No', FLICHE_VGALLERY) . ' ' . ucfirst ($type) . ' ' . __('Videos',FLICHE_VGALLERY) . "</li>";
      }      
      /**
       * Check number of videos to be shown count is less than or equal to total count
       */
      if (($show < $videosCount) || ($show == $videosCount)) {
          /** If type is realted video, then display playlist URL as a link */
          if($type == 'related') {
              $playlist_url = get_playlist_permalink ( $moreName, $pID, $playlist_slugname );
              $div    .= '<li><div class="right video-more"><a href="' . $playlist_url . '">';             
          } else { 
              /** Else display more pages URL as a link */
              $div    .= '<li><div class="video-more"><a href="' . $more_videos_link . '">';            
          }
          $div        .=  __ ( 'More&nbsp;Videos', FLICHE_VGALLERY ) . '&nbsp;&#187; </a> </div> <div class="clear"></div> </li>';
      } else {
        $div        .= '<li> <div align="right"></div> </li>';
      }      
      /** Return widget content */
      return $div . '</ul></div>';
}
?>
