<?php
/*
Plugin Name: Recent Comments by Entry Widget
Plugin URI: http://www.vjcatkick.com/?page_id=4485
Description: Another style listing recent comments sort by each entry.
Version: 0.1.0
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
* Wed Dec 24 2008 - v0.0.1
- Initial release
* Sat Dec 27 2008 - v0.0.2
- add sort order switch
* Tue Dec 30 2008 - v0.0.3
- compatibility bug fix
* Jan 07 2009 - v0.0.4
- option panel design change
* Jan 26 2009 - v0.0.5
- fixed: trackbacks and pingbacks were in the list
* Jan 29 2009 - v0.0.6
- compatibility fix
* Jan 29 2009 - v0.0.7
- compatibility fix - error logic added if there are no data
* Jan 29 2009 - v0.0.8
- internal bug fix
* Jul 11 2009 - v0.0.9
- CRLF issue on IE 8 had been fixed
* Jan 02 2010 - v0.1.0
- wrong counting when comments and pingbacks/trackbacks combination
*/


function widget_recent_comments_by_entry_init() {
	if ( !function_exists('register_sidebar_widget') )
		return;

	function widget_recent_comments_by_entry( $args ) {
		extract($args);

		$options = get_option('widget_recent_comments_by_entry');
		$title = $options['rcbet_src_title'];
		$rcbet_dst_max_entry =  $options['rcbet_dst_max_entry'];
		$rcbet_dst_max_comments =  $options['rcbet_dst_max_comments'];
		$rcbet_dst_sort_order =  $options['rcbet_dst_sort_order'];

		$output = '<div id="widget_recent_comments_by_entry"><ul>';

		// section main logic from here 

		$isIEorNot = FALSE;
		$isSafariorNot = FALSE;
		$Agent = getenv( "HTTP_USER_AGENT" );
	   if( ereg( "MSIE", $Agent ) ) { $isIEorNot = true; }
	   elseif( ereg( "Safari", $Agent ) ) { $isSafariorNot = true; }

	$myCRLF = "<br />";
	global $wpdb;
	$myResults = $wpdb->get_results( "
		SELECT * FROM $wpdb->comments 
		WHERE $wpdb->comments.comment_approved = '1'
		ORDER BY $wpdb->comments.comment_date_gmt DESC 
		LIMIT 100", ARRAY_A );

	$commentedPostIDs = array();
	foreach( $myResults as $myResult ) {
		$isAlreadyFlag = FALSE;
		foreach( $commentedPostIDs as $cpid ) {
			if( $cpid == $myResult[ 'comment_post_ID' ] ) {
				$isAlreadyFlag = TRUE;
				break;
			} /* if */
		} /* foreach */
		if( $isAlreadyFlag == FALSE ) {
			$commentedPostIDs[] = $myResult[ 'comment_post_ID' ];
		} /* if */
	} /* foreach */

	if( count( $commentedPostIDs ) == 0 ) {
		$output  .= 'no comments';
	} else {
		$maxPosts = $rcbet_dst_max_entry;
		if( $maxPosts > 10 || $maxPosts < 0 ) { $maxPosts = 10; }


// 0.1.0 fixed
//		$i = 0;
		$i = $maxPosts;

		foreach( $commentedPostIDs as $ret ) {
			$thePost = wp_get_single_post( $ret , ARRAY_A);
// 0.0.8 moved
//			$theOutput =  '<li><a href="' . $thePost[ 'guid' ] . '">' . $thePost[ 'post_title' ] . '</a></li>';
//			if( $isIEorNot ) { $theOutput .= '<br />'; };
//			$output  .= $theOutput;

			$maxCommentsToDisplay = $rcbet_dst_max_comments;
			if( $maxCommentsToDisplay > 10 || $maxCommentsToDisplay < 0 ) { $maxCommentsToDisplay = 10; }
			// 0.0.5 fixed
			$queryStr = "
				SELECT * FROM $wpdb->comments 
				WHERE $wpdb->comments.comment_approved = '1'
				AND $wpdb->comments.comment_post_ID = $ret
				AND $wpdb->comments.comment_type != 'trackback'
				AND $wpdb->comments.comment_type != 'pingback'
				ORDER BY $wpdb->comments.comment_date_gmt DESC 
				LIMIT " . $maxCommentsToDisplay;
			$myCResults = $wpdb->get_results( $queryStr, ARRAY_A );

			if( $myCResults && is_array( $myCResults ) ) {		// 0.0.7

// 0.1.0 fixed
				$maxPosts -= 1;

// 0.0.8 moved
				$theOutput =  '<li><a href="' . $thePost[ 'guid' ] . '">' . $thePost[ 'post_title' ] . '</a></li>';
				if( $isIEorNot ) {
					if( !ereg( "MSIE 8", $Agent ) ) {
						$theOutput .= '<br />';
					} /* if */
				} /* if */	// 0.0.9 ie 8 support
				$output  .= $theOutput;

				if(	$rcbet_dst_sort_order == 1 ) {
					$myCResults = array_reverse( $myCResults );
				} /* if */

				$cResultLimit = count( $myCResults );
				$ii = 0;
				$myDelimiter = "-";
				if( $cResultLimit > 0 ) {		// 0.0.7
					foreach( $myCResults as $mcr ) {
						++$ii;
						$theOutput =  "<span style='font-size:0.9em;'>&nbsp;&nbsp;" . $myDelimiter . "&nbsp;" . $mcr[ 'comment_author' ] . "</span>";
						if( $ii < $cResultLimit ) { $theOutput .= $myCRLF; }
						$output  .= $theOutput;
					} /* foreach */

				$output .= $myCRLF;
				} /* if */
			} /* if */

// 0.1.0 fixed
//			if( ++$i >= $maxPosts ) { break; }
			if( $maxPosts <= 0 ) break;
		} /* foreach */
	} /* if else */

			$output .= '</ul></div>';

		// These lines generate the output

		echo $before_widget . $before_title . $title . $after_title;
		echo $output;
		echo $after_widget;
	} /* widget_recent_comments_by_entry() */

	function widget_recent_comments_by_entry_control() {
		$options = $newoptions = get_option('widget_recent_comments_by_entry');
		if ( $_POST["rcbet_src_submit"] ) {
			$newoptions['rcbet_src_title'] = strip_tags(stripslashes($_POST["rcbet_src_title"]));
			$newoptions['rcbet_dst_max_entry'] = (int) $_POST["rcbet_dst_max_entry"];
			$newoptions['rcbet_dst_max_comments'] = (int) $_POST["rcbet_dst_max_comments"];
			$newoptions['rcbet_dst_sort_order'] = (int) $_POST["rcbet_dst_sort_order"];
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_recent_comments_by_entry', $options);
		}

		// those are default value
		if ( !$options['rcbet_dst_max_entry'] ) $options['rcbet_dst_max_entry'] = 7;
		if ( !$options['rcbet_dst_max_comments'] ) $options['rcbet_dst_max_comments'] = 5;
		if ( !$options['rcbet_dst_sort_order'] ) $options['rcbet_dst_sort_order'] = 0;

		$rcbet_dst_max_entry = $options['rcbet_dst_max_entry'];
		$rcbet_dst_max_comments = $options['rcbet_dst_max_comments'];
		$rcbet_dst_sort_order = $options['rcbet_dst_sort_order'];

		$title = htmlspecialchars($options['rcbet_src_title'], ENT_QUOTES);
?>

	    <?php _e('Title:'); ?> <input style="width: 170px;" id="rcbet_src_title" name="rcbet_src_title" type="text" value="<?php echo $title; ?>" /><br />

        <?php _e('Max Entry:'); ?> <input style="width: 75px;" id="rcbet_dst_max_entry" name="rcbet_dst_max_entry" type="text" value="<?php echo $rcbet_dst_max_entry; ?>" /> (max 10)<br />
        <?php _e('Max Comments:'); ?> <input style="width: 75px;" id="rcbet_dst_max_comments" name="rcbet_dst_max_comments" type="text" value="<?php echo $rcbet_dst_max_comments; ?>" /> (max 10)<br />

        <?php _e('Sort:'); ?><br />
		<input id="rcbet_dst_sort_order" name="rcbet_dst_sort_order" type="radio" value="0" <?php if( $rcbet_dst_sort_order == 0) { echo "checked";}  ?>/> newest commentators on top<br />
		<input id="rcbet_dst_sort_order" name="rcbet_dst_sort_order" type="radio" value="1" <?php if( $rcbet_dst_sort_order == 1) { echo "checked";}  ?>/> newest commentators on bottom<br />
		*commentator list only.

  	    <input type="hidden" id="rcbet_src_submit" name="rcbet_src_submit" value="1" />

<?php
	} /* widget_recent_comments_by_entry_control() */

	register_sidebar_widget('Recent Comments by Entry', 'widget_recent_comments_by_entry');
	register_widget_control('Recent Comments by Entry', 'widget_recent_comments_by_entry_control' );
} /* widget_recent_comments_by_entry_init() */

add_action('plugins_loaded', 'widget_recent_comments_by_entry_init');

?>