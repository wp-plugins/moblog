<?php
/*
Plugin Name: Moblog
Plugin URI: http://www.vjcatkick.com/?page_id=5775
Description: Twitter based moblog on your sidebar.
Version: 0.1.2
Author: V.J.Catkick
Author URI: http://www.vjcatkick.com/
*/

/*
License: GPL
Compatibility: WordPress 2.6 with Widget-plugin.

Installation:
Place the widget_single_photo folder in your /wp-content/plugins/ directory
and activate through the administration panel, and then go to the widget panel and
drag it to where you would like to have it!
*/

/*  Copyright V.J.Catkick - http://www.vjcatkick.com/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


/* Changelog
* Jan 04 2009 - v0.0.0
- Initial release
* Jan 08 2009 - v0.1.0
- public release
* Jan 12 2009 - v0.1.1
- bug fix
* Jan 24 2009 - v0.1.2
- bug fix - positioning
*/


function widget_moblog_init() {
	if ( !function_exists('register_sidebar_widget') )
		return;

	function widget_moblog( $args ) {
		extract($args);

		$options = get_option('widget_moblog');
		$title = $options['widget_moblog_title'];
		$widget_moblog_twitter_id = $options['widget_moblog_twitter_id'];
		$widget_moblog_max_width =  $options['widget_moblog_max_width'];
		$widget_moblog_use_frame = $options['widget_moblog_use_frame'];
		$widget_moblog_enable_link = $options['widget_moblog_enable_link'];
		$widget_moblog_enable_text = $options['widget_moblog_enable_text'];
		$widget_moblog_frame = (int) $options['widget_moblog_frame'];
		$widget_moblog_frame_fname = $options['widget_moblog_frame_fname'];

		$output = '<div id="widget_moblog"><ul>';

		// section main logic from here 


		$moblog_width = $widget_moblog_max_width;

		function _twiitter_test_entry( $theEntry ) {
			$retv = false;
			$testStr = $theEntry->text;

			// case twinkle
			$spos = strpos( $testStr, 'http://snipurl.com' );
			if( $spos !== FALSE ) {
				$tmpstr = substr( $testStr, $spos );
				$filedata = @simplexml_load_file( $tmpstr );
				if( $filedata ) {
					$pgTitle = $filedata->head->title;
					if( strcmp( $pgTitle, "TwinkleShots" ) == 0 ) {
						return( $filedata->body->div[1]->img[src] );
					} /* if */
				} /* if */
			} /* if */

			// case twitterrific
			$spos = strpos( $testStr, 'http://twitpic.com' );
			if( $spos !== FALSE ) {
				$tmpstr = substr( $testStr, $spos );
				$filedata = @file_get_contents( $tmpstr );
				if( $filedata ) {
					$testresult = strpos( $filedata, '<title>TwitPic' );
					if( $testresult !== FALSE ) {
						$spos = strpos( $filedata, '<img id="pic"' );
						$filedata = substr( $filedata, $spos );
						$spos = strpos( $filedata, 'src="' )+5;
						$filedata = substr( $filedata, $spos );
						$spos = strpos( $filedata, '"' );
						$filedata = substr( $filedata, 0, $spos );

						// 0.1.1 fixed
						if( strpos( $filedata, "/" ) == 0 ) $filedata = 'http://twitpic.com' . $filedata;

						return( $filedata );
					} /* if */
				} /* if */
			} /* if */

			// case keitai-hyakkei
			$spos = strpos( $testStr, 'http://movapic.com' );
			if( $spos !== FALSE ) {
				$tmpstr = substr( $testStr, $spos );
				$filedata = @file_get_contents( $tmpstr );
				if( $filedata ) {
					$spos = strpos( $filedata, '<img class="image"' );
					$filedata = substr( $filedata, $spos );
					$spos = strpos( $filedata, 'src="' )+5;
					$filedata = substr( $filedata, $spos );
					$spos = strpos( $filedata, '"' );
					$filedata = substr( $filedata, 0, $spos );
					return( $filedata );
				} /* if */
			} /* if */

			return( $retv );
		} /* _twiitter_test_entry() */

		function _moblog_get_frame_url( $isVertical, $whichFrame, $customFrame ) {
			$baseurl = get_option('siteurl') . '/wp-content/plugins/moblog/images/';
			switch( $whichFrame ) {
				case 1:
					$baseurl .= 'classic_frame';
					break;
				case 2:
					$baseurl .= 'modern_frame';
					break;
				case 3:
					$baseurl .= 'photograph_frame';
					break;
				case 4:
					$baseurl .= 'iphone_frame';
					break;
				case 99:
					$baseurl .= $customFrame;
					break;
				default:
					$baseurl .= 'imageback';
			} /* switch */
			if( $isVertical ) {
				$baseurl .= '_v.gif';
			}else{
				$baseurl .= '_h.gif';
			} /* if else */
			return( $baseurl );
		} /* _moblog_get_frame_url() */


		$cached_time = $options['widget_moblog_cache_time'];
		$retv = false;
		$tget = false;

		if( $cached_time + 300 < time() ) {
//		if( 1 ) { // debug only
			$_xmlfilestr = 'http://twitter.com/statuses/user_timeline/' . $widget_moblog_twitter_id . '.xml?count=100';
			$twitters = @simplexml_load_file( $_xmlfilestr );

			if( $twitters ) {
				foreach( $twitters as $p ) {
					$retv = _twiitter_test_entry( $p );
					if( $retv !== false ) {
						$tget = $p;
						break;
					} /* if */
				} /* foreach */
			} /* if */
		} /* if */

		$c_retv = "";

// fixed 0.1.2
//		$c_retv .= '<div id="moblog_image_outer" style="position:relative; width:' . $moblog_width . 'px; height:' . $moblog_width . ' background-color:black;" >';
//		$c_retv .= '<img src="' . get_option('siteurl') . '/wp-content/plugins/moblog/images/imageback_transparent.gif'. '" border="0" width="' . $moblog_width . 'px" height="' . $moblog_width . 'px" />';


		$isVertical = false;
		$img_info = false;

		$img_info = getimagesize( $retv );
		if( $img_info ) {
			$img_width = $img_info[0];
			$img_height = $img_info[1];
			$isVertical = $img_height > $img_width ? true : false;
		} /* if */

		$frameTag  = false;
		$theFrameURL = "";
		if( $widget_moblog_use_frame ) {
			$theFrameURL = _moblog_get_frame_url( $isVertical, $widget_moblog_frame, $widget_moblog_frame_fname );
			$frameTag = '<img src="' . $theFrameURL . '" border="0" width="' . $moblog_width . 'px" style="position: absolute; left:0; top: 0;" />';
		} /* if */

		if( $tget !== false ) {
			$spos = strpos( $tget->text, 'http://' );
			$theText = substr( $tget->text, 0, $spos );
			$theLink = substr( $tget->text, $spos );

			if( $widget_moblog_enable_link ) {
				$c_retv .= '<a href="' . $theLink . '" target="_blank" >';
			} /* if */

			$img_display_width = $moblog_width;
			$img_display_height = $moblog_width;

			if( $widget_moblog_use_frame  && $img_info ) {
				$theOffset = 0; //15;	// fixed 0.1.2
				$offset_top = 0;
				$offset_left = 0;

				// first, fill out 'empty' area
				$fill_rect_size = $moblog_width - ($theOffset * 2);
				$fill_fname = ereg_replace( "_..gif", "_fill.gif", $theFrameURL );
				$fHandle = fopen( $fill_fname, "r" );	// try to open file - file exist check
				if( $fHandle== false ) {
					$fill_fname = get_option('siteurl') . '/wp-content/plugins/moblog/images/imageback_fill.gif';
				}else{
					fclose( $fHandle );
				} /* if else */
				$c_retv .= '<img src="' . $fill_fname . '" border="0" width="' . $fill_rect_size . 'px" height="' . $fill_rect_size . 'px" style="position:absolute; left:' . $theOffset . 'px; top:' . $theOffset . 'px;" />';

				if( $isVertical ) {
					$offset_top = $theOffset;
					$_the_height = $img_display_width - ($theOffset * 2);
					$_the_width = floor( ($img_width * $_the_height) / $img_height );
					$offset_left = floor( ($img_display_width - $_the_width) / 2 );

					$c_retv .= '<img src="' . $retv . '" width="' . $_the_width . 'px" border="0" style=" position: absolute; left:' . $offset_left . 'px; top: ' . $offset_top . 'px;" >';
				}else{
					$offset_left = $theOffset;
					$_the_width = $img_display_height - ($theOffset * 2);
					$_the_height = floor( ($img_height * $_the_width) / $img_width );
					$offset_top = floor( ($img_display_height - $_the_height) / 2 );

					$c_retv .= '<img src="' . $retv . '" height="' . $_the_height . 'px" border="0" style=" position: absolute; left:' . $offset_left . 'px; top: ' . $offset_top . 'px;" >';
				} /* if else */
				$c_retv .= $frameTag ? $frameTag : '';

				$offset_v_text = $moblog_width + 2;
			}else{	// no frame, just display image

				$c_retv .= '<img src="' . $retv . '" width="' . $moblog_width . 'px" border="0" style="margin-bottom:4px; border:1px solid #DDD;position: absolute; left:0; top: 0;" >';
				$img_info = getimagesize( $retv );
				$offset_v_text = floor( ( $moblog_width * $img_height ) / $img_width ) + 2;
			} /* if else */

			// frame overrapped for masking
			if( $widget_moblog_enable_link ) {
				$c_retv .= '</a>';
			} /* if */

		// fixed 0.1.2
		$c_retv = '<img src="' . get_option('siteurl') . '/wp-content/plugins/moblog/images/imageback_transparent.gif'. '" border="0" width="' . $moblog_width . 'px" height="' . $offset_v_text . 'px" />' . $c_retv;
		$c_retv = '<div id="moblog_image_outer" style="position:relative; width:' . $moblog_width . 'px; height:' . $offset_v_text . ' background-color:black;" >'  . $c_retv;

			// text below image
			if( $widget_moblog_enable_text ) {
				$c_retv .= '<div class="mbody" style="margin-top:4px;" width="' . $moblog_width . 'px" >' . $theText . '</div>';
			}else{
				$c_retv .= '<div class="mbody" style="margin-top:4px;" width="' . $moblog_width . 'px" >' . '' . '</div>';
			} /* if else */

			// actual output
			$output .= $c_retv;

			$options['widget_moblog_cache_time'] = time();
			$options['widget_moblog_cache'] = $c_retv;
			update_option('widget_moblog', $options);
		}else{
			$c_retv = $options['widget_moblog_cache'];
			$output .= $c_retv . '<!-- cached -->';
		} /* if else */

		$output .= '</div>';


		// These lines generate the output
		$output .= '</ul></div>';

		echo $before_widget . $before_title . $title . $after_title;
		echo $output;
		echo $after_widget;
	} /* widget_moblog() */

	function widget_moblog_control() {
		$options = $newoptions = get_option('widget_moblog');
		if ( $_POST["widget_moblog_submit"] ) {
			$newoptions['widget_moblog_title'] = strip_tags(stripslashes($_POST["widget_moblog_title"]));
			$newoptions['widget_moblog_twitter_id'] = stripslashes( $_POST["widget_moblog_twitter_id"] );
			$newoptions['widget_moblog_max_width'] = (int) $_POST["widget_moblog_max_width"];
			$newoptions['widget_moblog_use_frame'] = (boolean) $_POST["widget_moblog_use_frame"];
			$newoptions['widget_moblog_enable_link'] = (boolean) $_POST["widget_moblog_enable_link"];
			$newoptions['widget_moblog_enable_text'] = (boolean) $_POST["widget_moblog_enable_text"];
			$newoptions['widget_moblog_frame'] = (int) $_POST["widget_moblog_frame"];
			$newoptions['widget_moblog_frame_fname'] = strip_tags(stripslashes($_POST["widget_moblog_frame_fname"]));

			$newoptions['widget_moblog_cache_time'] = 0;
			$newoptions['widget_moblog_cache'] ="";
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_moblog', $options);
		}

		// those are default value
		if ( !$options['widget_moblog_max_width'] ) $options['widget_moblog_max_width'] = 165;

		$widget_moblog_twitter_id = $options['widget_moblog_twitter_id'];
		$widget_moblog_max_width = $options['widget_moblog_max_width'];
		$widget_moblog_use_frame = $options['widget_moblog_use_frame'];
		$widget_moblog_enable_link = $options['widget_moblog_enable_link'];
		$widget_moblog_enable_text = $options['widget_moblog_enable_text'];
		$widget_moblog_frame = $options['widget_moblog_frame'];
		$widget_moblog_frame_fname = $options['widget_moblog_frame_fname'];

		$title = htmlspecialchars($options['widget_moblog_title'], ENT_QUOTES);
?>

	    <?php _e('Title:'); ?> <input style="width: 170px;" id="widget_moblog_title" name="widget_moblog_title" type="text" value="<?php echo $title; ?>" /><br />
	    <?php _e('Twitter ID:'); ?> <input style="width: 170px;" id="widget_moblog_twitter_id" name="widget_moblog_twitter_id" type="text" value="<?php echo $widget_moblog_twitter_id; ?>" /><br />

        <?php _e('Width:'); ?> <input style="width: 75px;" id="widget_moblog_max_width" name="widget_moblog_max_width" type="text" value="<?php echo $widget_moblog_max_width; ?>" />px<br />

        <input id="widget_moblog_enable_link" name="widget_moblog_enable_link" type="checkbox" value="1" <?php if( $widget_moblog_enable_link ) echo 'checked';?>/> <?php _e('Enable link to original'); ?><br />
        <input id="widget_moblog_enable_text" name="widget_moblog_enable_text" type="checkbox" value="1" <?php if( $widget_moblog_enable_text ) echo 'checked';?>/> <?php _e('Enable text'); ?><br />
        <input id="widget_moblog_use_frame" name="widget_moblog_use_frame" type="checkbox" value="1" <?php if( $widget_moblog_use_frame ) echo 'checked';?>/> <?php _e('Use frame'); ?><br />

        <input id="widget_moblog_frame" name="widget_moblog_frame" type="radio" value="0" <?php if( $widget_moblog_frame == 0 ) echo 'checked';?>/> <img src="<?php echo get_option('siteurl') . '/wp-content/plugins/moblog/images/imageback_icon.jpg'; ?>" border="0" />&nbsp;&nbsp;
        <input id="widget_moblog_frame" name="widget_moblog_frame" type="radio" value="1" <?php if( $widget_moblog_frame == 1 ) echo 'checked';?>/> <img src="<?php echo get_option('siteurl') . '/wp-content/plugins/moblog/images/classic_frame_icon.jpg'; ?>" border="0" />&nbsp;&nbsp;
        <input id="widget_moblog_frame" name="widget_moblog_frame" type="radio" value="2" <?php if( $widget_moblog_frame == 2 ) echo 'checked';?>/> <img src="<?php echo get_option('siteurl') . '/wp-content/plugins/moblog/images/modern_frame_icon.jpg'; ?>" border="0" />&nbsp;&nbsp;

        <input id="widget_moblog_frame" name="widget_moblog_frame" type="radio" value="3" <?php if( $widget_moblog_frame == 3 ) echo 'checked';?>/> <img src="<?php echo get_option('siteurl') . '/wp-content/plugins/moblog/images/photograph_frame_icon.jpg'; ?>" border="0" />&nbsp;&nbsp;
        <input id="widget_moblog_frame" name="widget_moblog_frame" type="radio" value="4" <?php if( $widget_moblog_frame == 4 ) echo 'checked';?>/> <img src="<?php echo get_option('siteurl') . '/wp-content/plugins/moblog/images/iPhone_frame_icon.jpg'; ?>" border="0" />&nbsp;&nbsp;<br />


        <input id="widget_moblog_frame" name="widget_moblog_frame" type="radio" value="99" <?php if( $widget_moblog_frame == 99 ) echo 'checked';?>/> Custom<br />
	    <?php _e('File name:'); ?> <input style="width: 170px;" id="widget_moblog_frame_fname" name="widget_moblog_frame_fname" type="text" value="<?php echo $widget_moblog_frame_fname; ?>" /><br />


  	    <input type="hidden" id="template_src_submit" name="widget_moblog_submit" value="1" />

<?php
	} /* widget_moblog_control() */

	register_sidebar_widget('Moblog', 'widget_moblog');
	register_widget_control('Moblog', 'widget_moblog_control' );
} /* widget_moblog_init() */

add_action('plugins_loaded', 'widget_moblog_init');

?>