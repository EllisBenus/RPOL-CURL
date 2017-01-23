<?php
/*
 * Script by Ellis Benus (byzantinex on RPOL.net)
 * ellisbenus@gmail.com
 * http://EllisBenus.com/
 *
 */ 

/*
 * Get the page referenced in the link
 *
 * If you load the script directly, with no refernces, then it loads the RPOL homepage with you logged in.
 *
 * Example Link URL: http://domain.com/curl.php?page=gameinfo.php&action=viewmap&gi=65457&gamemap=0&date=1482712464
 * Example Link URL: http://domain.com/curl.php?page=display.cgi&gi=65457&ti=3&date=1484469654#bottom
 *
 */
$rpol_curl = array();
foreach ( $_GET as $key=>$element ) {
	$rpol_curl[$key] = $element;
}

/* 
 * CURL REQUEST
 *
 * You can run CURL and load the site, or use code below to make a static file and reference that during development. 
 *
*/
$run_curl = TRUE;
//$run_curl = FALSE; // UNCOMMENT to not run CURL request

if ( $run_curl ) {
	// init the resource
	$ch = curl_init();

	/* 
	 * Change USERNAME and PASSWORD to your RPOL.net credentials
	 */
	$postData = array(
		"username" => "USERNAME",
		"password" => "PASSWORD",
		'redirect_to' => 'http://rpol.net',
		'testcookie' => '1'
	);

	$postData = "username=USERNAME&password=PASSWORD&specialaction=Login&perm=1&redir=1";

	// ... or an array of options
	curl_setopt_array($ch, array(
		CURLOPT_URL => 'http://rpol.net/login.cgi',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $postData,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_COOKIESESSION => true,
		CURLOPT_COOKIEJAR => 'cookie.txt',
		CURLOPT_COOKIEFILE => 'cookie.txt'
	));

	$output = curl_exec($ch);

	curl_setopt($ch, CURLOPT_URL, 'http://rpol.net/');
	$content = curl_exec($ch);

	$postData = "gi=" . $rpol_curl['gi'];
	$postData .= ( $rpol_curl['ti'] ) ? "&ti=" . $rpol_curl['ti'] : "";
	$postData .= "&date=" . $rpol_curl['date'];
	$postData .= ( $rpol_curl['msgpage'] ) ? "&msgpage=" . $rpol_curl['msgpage'] : "";
	$postData .= ( $rpol_curl['show'] ) ? "&show=" . $rpol_curl['show'] : "";
	$postData .= ( $rpol_curl['subject'] ) ? "&subject=" . $rpol_curl['subject'] : "";


	curl_setopt($ch, CURLOPT_URL, 'http://www.rpol.net/' . $rpol_curl['page']);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	$content = curl_exec($ch);
} // end if ( $run_curl )

/*
 * Open saved HTML file for use while testing to avoid being blocked from rpol.net for repeated requests
 */
if ( $run_curl == FALSE ) {
	$file = '../rpol.txt';
	$content = file_get_contents($file);
}
 
/* 
 * Do ALL the reformatting work to make RPOL.net Mobile Friendly
 */

// Load PHP Simple HTML DOM
include_once('../simple_html_dom.php');

// Make the page into a Simple DOM element
$html = str_get_html($content);

// Add CSS styling
$inject  = '<style type="text/css">
body { font-size: 100%; font-size: 27px !important; }
div#wrapper { padding-top: 0px !important; }
#header, 
.header,
#footer { height: 100% !important; font-size: 100%; font-size: 40px !important; position: static !important; }
#header .left,
.header .left,
#footer .left, 
#header .right,
.header .right,
#footer .right,
.left, 
.right,
.center { clear: both; text-align: left !important; }
#header .border a img { width: 100%; height: auto; } /* make the header image full width */
#contents .center img { width: 40px; height: auto; } /* make the notification images bigger */
#footer { height: 100%; text-align: left; }
#footer div { width: 100% !important; }
div[nowrap="nowrap"] { margin: 20px 0px; }
</style>';
$html->find('head', 0)->innertext = $inject.$html->find('head', 0)->innertext;
 
// Add jQuery script
$inject  = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>';
$html->find('head', 0)->innertext = $inject.$html->find('head', 0)->innertext;

/* Set the Base URL so stylesheets, images, etc... work/show correctly.
 * This needs to be changed to the domain where this curl script is hosted when the above development is done.
 *
 * Setting the base to the sub directory /rpol seems to be ignored and it's only doing the domain.
 * This means I cannot add the $_GET paramaters I need to capture those links and have them render using this script.
 * I'm going to try to capture all the links and add the URL to the front the way I want but I'm worried about this approach.
 */
//$inject  = '<base href="http://www.rpol.net/" />';
//$inject  = '<base href="http://ellisbenus.com/rpol" />';
//$html->find('head', 0)->innertext = $inject.$html->find('head', 0)->innertext;

/*
 * Simple way to replace all table elements with div's.
 * I was making this WAY too complicated.
 * Simply find every instance of the following and make them div's...
 */
$html = str_replace("<table", "<div", $html);
$html = str_replace("</table", "</div", $html);
$html = str_replace("<thead", "<div", $html);
$html = str_replace("</thead", "</div", $html);
$html = str_replace("<tbody", "<div", $html);
$html = str_replace("</tbody", "</div", $html);
$html = str_replace("<tr", "<div", $html);
$html = str_replace("</tr", "</div", $html);
$html = str_replace("<th", "<div", $html);
$html = str_replace("</th", "</div", $html);
$html = str_replace("<td", "<div", $html);
$html = str_replace("</td", "</div", $html);
$html = str_get_html($html);

// set all the widths to 100% to override the old table layout widths
foreach ( $html->find('div[width]') as $div_with_width ) {
	$div_with_width->setAttribute('width', '100%');
	//$div_with_width->removeAttribute('width');
}

// Make the images reference rpol.net instead of the base URL above
foreach ( $html->find('img') as $rpol_image ) {
	$rpol_image_source = "http://www.rpol.net" . $rpol_image->src;
	
	// Skip the rpolportraits images
	if ( !strpos( $rpol_image->src, "rpolportraits" ) ) {
		$rpol_image->setAttribute('src', $rpol_image_source);	
	} 
}
// Make the CSS Stylesheets reference rpol.net instead of the base URL above
foreach ( $html->find('link') as $rpol_style ) {
	$rpol_style_source = "http://www.rpol.net" . $rpol_style->href;
	$rpol_style->setAttribute('href', $rpol_style_source);	
}
// Make the JavaScript files reference rpol.net instead of the base URL above
foreach ( $html->find('script') as $rpol_script ) {
	$rpol_script_source = "http://www.rpol.net" . $rpol_script->src;
	
	// Skip the google loaded items
	if ( !strpos( $rpol_script->src, "googleapis" ) ) {
		$rpol_script->setAttribute('src', $rpol_script_source);	
	}
}

// Modify links to make the CURL script render all pages insted of going back to RPOL.net
foreach ( $html->find('a') as $rpol_link ) {
	// Example Links on RPOL
	// /gameinfo.php?gi=65457&date=1482712464 
	// /display.cgi?gi=65457&ti=3&date=1482542067&msgpage=3 
	// /game.cgi?gi=68592&date=1484512291

	if ( $rpol_link->href == "/" ) {
		$rpol_link->setAttribute('href', "http://ellisbenus.com/rpol/curl.php");
	}
	
	// Match and Modify only Certain Links
	if ( strpos( $rpol_link->href, "gameinfo.php" ) ||
		strpos( $rpol_link->href, "display.cgi" ) ||
		strpos( $rpol_link->href, "message.cgi" ) ||
		strpos( $rpol_link->href, "game.cgi" ) ) {

		// Remove the slash before the page so it's a proper $_GET string
		$rpol_link_replaced = str_replace( "/", "", $rpol_link->href );
		// Replace the ? with an & so it separates into two $_GET items
		$rpol_link_replaced = str_replace( "?", "&", $rpol_link_replaced );
		// Make the link reference this script
		$rpol_link_source = "http://ellisbenus.com/rpol/curl.php?page=" . $rpol_link_replaced;
		// Save the changed Link
		$rpol_link->setAttribute('href', $rpol_link_source);
	}
}


echo $html;

// Close the CURL connection
// curl_close($ch);


// --------------------------------------------------------------------------------------------------------------------
// --- Everything after this is junk code no longer needed but kept b/c I have no version control on this file --------
// --------------------------------------------------------------------------------------------------------------------

/*
 * Save HTML to a file to reference and avoid the 504 gateway error_get_last
 *
$file = 'rpol.txt';
// Open the file to get existing content
$current = file_get_contents($file);
// Append a new person to the file
$current = $content;
// Write the contents back to the file
file_put_contents($file, $current);
 */
 
 
/*
 * Getting the contents of a game thread
 * 
 * example url: http://www.rpol.net/display.cgi?gi=65457&ti=3&date=1482542067
 *
 
 *
 * Get game's main page
 *
 
$postData = "gi=65457&date=1482542067"; 
curl_setopt($ch, CURLOPT_URL, 'http://www.rpol.net/game.cgi');

 *
 * Get a thread in a game
 *

$postData = "gi=65457&ti=3&date=1484469654";
curl_setopt($ch, CURLOPT_URL, 'http://www.rpol.net/display.cgi');

curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

$content = curl_exec($ch);
 */

 
/*
 * Getting all the Game Links on the homepage after logging in
 *
 
$html = str_get_html($content);

foreach($html->find('tr.highlight') as $table) {
	
	foreach($table->find('a', 0) as $link) {
        if ( $link->innertext ) {
			echo $link->innertext . "<br>\n";
		}
	}		
}
*/

// Add jQuery to the header
/*
$inject  = '<script type="text/javascript">
$( document ).ready(function() {
	// Add ID to Table with Thread contents to make selections easier
    $(".message_alt:first").parent().parent().parent().attr("id", "rpoltable");
	
	// Replace all table tags with divs and paragraphs
	$(\'#rpoltable\').each(function (){
		$(this).replaceWith( $(this).html()
			.replace(/<tbody/gi, "<div class=\'table\'")
			.replace(/<tr/gi, "<div class=\'ccbnOutline\'")
			.replace(/<\/tr>/gi, "</div>")
			.replace(/<td/gi, "<div class=\'td\'")
			.replace(/<\/td>/gi, "</div>")
			.replace(/<\/tbody/gi, "<\/div")
		);
	});	
	
	// Replace all table tags with divs and paragraphs
	// This is the section with the pagination controls
	$(\'#contents center\').each(function (){
		$(this).replaceWith( $(this).html()
			.replace(/<tbody/gi, "<div class=\'table\'")
			.replace(/<tr/gi, "<div class=\'ccbnOutline\'")
			.replace(/<\/tr>/gi, "</div>")
			.replace(/<td/gi, "<div class=\'td\'")
			.replace(/<\/td>/gi, "</div>")
			.replace(/<\/tbody/gi, "<\/div")
		);
	});	
	
	// Replace all table tags in the header with divs and paragraphs
   $(\'#header\').each(function (){
		$(this).replaceWith( $(this).html()
			.replace(/<tbody/gi, "<div")
			.replace(/<tr/gi, "<div")
			.replace(/<\/tr>/gi, "</div>")
			.replace(/<td/gi, "<p")
			.replace(/<\/td>/gi, "</p>")
			.replace(/<\/tbody/gi, "<\/div")
		);
	});
	
	// Replace all table tags in the footer with divs and paragraphs
	// For some reason when you do this, everythign on the page disappears and only shows the line "Generated in 0.152 seconds."
   $(\'#header\').each(function (){
		$(this).replaceWith( $(this).html()
			.replace(/<tbody/gi, "<div")
			.replace(/<tr/gi, "<div")
			.replace(/<\/tr>/gi, "</div>")
			.replace(/<td/gi, "<p")
			.replace(/<\/td>/gi, "</p>")
			.replace(/<\/tbody/gi, "<\/div")
		);
	});
	
	
	// Manually adding the .header class to each div that does not already have an ID assigned so the CSS works.
	$("#wrapper > div").each( function( index ) {

		if ( !$(this).attr("id") ) {
			$(this).attr("class", "header");
		}
	});
	
	
	
	
});
</script>';
*/
// $html->find('head', 0)->innertext = $inject.$html->find('head', 0)->innertext;

/*
 * Remove Table Elements with jQuery
 * http://stackoverflow.com/questions/10141856/how-can-i-replace-multiple-tables-into-divs
 * 
 * Should/Could this be done with the Simple PHP DOM instead of jQuery?
 * 
$('#rpoltable').each(function (){
	$(this).replaceWith( $(this).html()
		.replace(/<tbody/gi, "<div class='table'")
		.replace(/<tr/gi, "<div class='ccbnOutline'")
		.replace(/<\/tr>/gi, "</div>")
		.replace(/<td/gi, "<div class='td'")
		.replace(/<\/td>/gi, "</div>")
		.replace(/<\/tbody/gi, "<\/div")
	);
});
 
 */

// Just some dev testing to better understand the php simple dom
//$html->find('div#contents', 0)->innertext;
//$html->find("div#contents", 0)->find($replace);
//$es = $html->find('div div div'); 
//$e = $html->find('ul', 0)->find('li', 0);

/*
 * Replace all instances of table elements with div's
 *
 * NOTE: This currently didn't work as well as the jQuery above. 
 * 			I'm probably doing something wrong.
 *
$replace="table,tr,td,th";  
foreach ( $html->find( 'div#contents', 0)->children(1)->find($replace) as $key=>$element ) {
	$html->find($replace,$key)->outertext="<div>".$element->innertext."</div>";
}
 
$replace="table,tr,td,th";  
foreach($html->find($replace) as $key=>$element){
	//printf("key: %s and element: %s<br>\n", $key, $element);
	//$html->find($replace,$key)->outertext="<div>".$element->innertext."</div>";
}
*/
/*
foreach ( $html->find('table') as $key=>$element ) {
	//$div->outertext = $div->innertext;
	
	printf('<p style="margin-bottom: 10px; border-bottom: 1px solid red;">Key: %s and Element %s</p>', $key, $element);
	echo "\n\n\n";
	
	$element = str_replace("table", "div", $element);
	$element = str_replace("tbody", "div", $element);
	$element = str_replace("tr", "div", $element);
	$element = str_replace("td", "div", $element);
	
	printf('<p style="margin-bottom: 10px; border-bottom: 1px solid red;">Key: %s and Element %s</p>', $key, $element);
	echo "\n\n\n";
}
*/
/*
 * I was trying to pull all the header links through the PHP simple dom to spit them out
 * as one list of links, instead of using jQuery do reformat them like I did above.
 *
 * This also didn't work very well... :-(
 
$header_links = $html->find('#header a');

$header_links_text = "";
foreach($header_links as $link) {	
	$header_links_text .= $link . " ";
}
$html->find('#header', 0)->innertext = $header_links_text.$html->find('#header', 0)->innertext;
*/
?>