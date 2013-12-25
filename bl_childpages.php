<?php
/*
Plugin Name: Bryans Childpage Thumbnail Generator
Plugin URI: http://www.bryanleister.com/projects/childpages-plugin/
Description: A plug-in that creates child page thumbnails with or without titles. Shortcode is [gallery_childpages]. Place that on a parent page and all the children will be listed. Options include styling and the ability to place on pages that are not the parent by using the id of a parent page.  All options: [gallery_childpages order="ASC" orderby="title" id="1290" number="-1" height="100" width="200" size="thumbnail" margin="10px 0 0 20px" style="my_style" include="" pagetitle="1" selector="H2" selector_height="30px" selector_width"100%" selector_padding="10px 0 0 20px" showimages="0" showimages="true" exclude="1,33,22"] .  Options follow wordpress conventions, however I am use 0 for 'false' and 1 for 'true' in showimages and pagetitle.
Version: 1.2
Author: Bryan Leister
Author URI: http://bryanleister.com
License: GPL2
*/

add_shortcode('gallery_childpages', 'gallery_childpages_shortcode');

/* Child Pages code
Based on the Gallery code we are finding all of the Children of a Page and then displaying
them as either a thumbnail or as an image thumbnail.
 */
function gallery_childpages_shortcode($attr) 
{
	
	global $post, $wp_locale;

	static $instance = 0;
	$instance++;
	$childpages = array();
	
	// Allow plugins/themes to override the default gallery template.
	$output = apply_filters('post_gallery', '', $attr);
	if ( $output != '' )
	{
		return $output;
	}
	
	// We're trusting author input, so let's at least make sure it looks like a valid orderby statement
	if ( isset( $attr['orderby'] ) ) 
	{
		$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
		if ( !$attr['orderby'] )
		{
			unset( $attr['orderby'] );
		}
	}

	extract(shortcode_atts(array(
		'order'      => 'ASC',
		'orderby'    => 'title',
		'id'         => '',
		'number'     => -1,
		'height'    => '100',
		'width'	    => '',
		'size'       => 'thumbnail',
		'style'		=> '',
		'include'    => '',
		'pagetitle'  => false,
		'selector'   => 'h3',
		'selector_height' => '30px',
		'selector_width' => '100%',
		'selector_padding' => '',
		'showimages' => true,
		'text_align' => 'left',
		'text_margin' => '',
		'exclude'    => ''
	), $attr));

	if( $id == '')
	{
		$id = $post->ID;
	}
	
	$id = intval($id);
	
	if($width != null)
	{
		$width='width:' . $width . 'px';
	}
	// $width="width:140px";
	
	if ($order == 'RAND')
	{
		$orderby = 'none';
	}
	
	if($text_margin == '')
	{
		$textmargin = ($height/2)-10;
		$textmargin .= "px 0 0 0";
		
	} else 
	{
		$textmargin = $text_margin;
	}
	// $textmargin = ($height)-30;
	$offsettext = ($height*3);

	$childpages = & get_posts('post_parent=' . $id . '&post_type=page&post_status=publish&numberposts=' . $number . '&exclude=' . $exclude . '&orderby=' . $orderby . '&order=' . $order);
	// 
	
	//Check if we have anything to output
	if ( empty($childpages)) 
	{
		$output = "<p>There are no child pages for post id # " . $id . "</p>";
		
	} else //We do have childpages, let's loop through them
	{
		//Nest the gallery inside a DIV to prevent Wordpress from adding a <p> to the post and screwing it up...
		$output = "<div class='row'>"; 

		foreach ($childpages as $page_id => $page) 
		{
			//Clear the vars in case we have multiple shortcodes on a single post
			$feat_image = NULL;
			// $attachmenturl = NULL;
			// $the_title_html = NULL;

			$thispage = $page->ID; //Store the child page ID 
			
			//Start building the link to the childpage
			$the_pagelink = get_permalink($thispage);
			$output .= "<div class='col-lg-3'>";
			$output .= "<div class='thumbnail'>";
			//$output .= "<a href='" . $the_pagelink . "' class='gallery_children" . $id . ">";

			//Store the page title formatted in HTML in case we need it
			$the_page_title = get_the_title($thispage);
			$the_title_html = "<" . $selector . " class='bl text-center'>" . $the_page_title . "</" . $selector . ">";

			if($showimages)
			{
				$feat_image = wp_get_attachment_image_src( get_post_thumbnail_id($thispage), $size );
			
				// Use the Featured for a background image if we have one specified
				if($feat_image)
				{
					$feat_url = $feat_image[0];
					//$output .= ";background-image: url(\"" . $feat_url . "\");background-position:center;' alt='{$the_page_title}' title='{$the_page_title}'>";
					$output .= "<img src='" . $feat_url . "' class='img-responsive'; alt='{$the_page_title}' />";
					
					if($pagetitle)
					{
						//$output .= "</a>";
						$output .= "<a href='" . $the_pagelink . "' class='gallery_children" . $id . ">";
						$output .= "<div class='caption'>";
						$output .= $the_title_html;
						$output .= "</div><!--class=caption-->";
						$output .= "</a>";

				 	} 

				} else //Look for attachments to the page and use one of those
				{
					$childattachments = & get_children(array('post_parent' => $thispage, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order', 'order' => 'DESC'));
		
					if (!empty($childattachments)) 
					{
						$first_attachment = array_shift($childattachments);
						$attachmenturl = wp_get_attachment_image_src($first_attachment->ID, $size);
						
						if(!empty($attachmenturl[0]))
						{ 
							$output .= ";background-image: url(\"" . $attachmenturl[0] . "\");background-position:center;' >";
					
							//Display a page title if they set the option to true, i.e $pagetitle="1"
							if($pagetitle)
							{
								$output .= $the_title_html;
						 	} 
						
						} else //If the attachments are not images, we will just use the title
						{
							$output .= "'>" . $the_title_html;
						}
					} else // Must be image attachments, use the title instead
					{
						$output .= "'>" . $the_title_html;
					} //End check for images attached to the post
				
				} // End if checking for Featured or other images
			
			} else //Not showing images at all, we'll use the title
			{
				$output .= "'>" .$the_title_html;
			}//End if Show Images
			$output .= "</div><!--class=thumbnail-->";//thumbnail
			$output .= "</div><!--thumbnail-->";//col-lg-3
		} //End For Each
		$output .= "</div>";//row
		
	} //End check for output
	return $output;
} //End function

?>
