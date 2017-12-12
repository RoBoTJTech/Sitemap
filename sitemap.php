<?PHP
/* FILENAME: sitemap.php
 *
 * Copying and distribution of this file, with or without modification,
 * are permitted in any medium without royalty provided the copyright
 * notice and this notice are preserved including information about me
 * and my site http://www.thejohnnyoshow.com/coding-corner.html :-)
 * This file is offered as-is, without any warranty.
 *
 * This software is free to use and alter as you need, however please don't
 * sell it, and please if possible direct others to my site if they want a
 * copy (http://www.thejohnnyoshow.com) Please like and share my videos :-)
 *
 * How to use this software...
 * edit the values in the section below to match your site requirements
 *
 *
 * You can access the script by web browser and put ?show_progress=1 in the url
 * so that all the urls that are spidered are displayed in your browser like this
 * http://YOURDOMAIN/YOURPATH/sitemap.php?show_progress=1
 *
 * Or you can set you a cronjob once a week using something  
 * like * * * * 0 * wget -q http://YOURDOMAIN/YOURPATH/sitemap.php
 */

// ------------ Configure below this line ----------------
$domain = "www.thejohnnyoshow.com";
$protocol = 'http';
$changefreq = "weekly"; // options are: always,hourly,daily,weekly,monthly,yearly,never
$priority = 1; // 0.0-1.0
$saveas = '../sitemap.xml';
$robots = '../robots.txt';
$sitemapURL = $protocol.'://'.$domain.'/sitemap.xml';

// ------------ End of configuration ----------------------

$robotdata= file_get_contents($robots); // reads an array of lines
if (!strpos('.'.$robotdata,"sitemap: $sitemapURL")){
	file_put_contents($robots,"$robotdata\nsitemap: $sitemapURL");
}


$url = $protocol . '://' . $domain;
$lastmod = date ( 'Y-m-d', time () );
$sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
$sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
if ($_GET ['show_progress']) {
	if (ob_get_level ())
		ob_end_clean ();
	echo "<pre>";
}
spider ( $url );
function spider($spiderurl) {
	global $domain;
	global $protocol;
	global $skip;
	global $sitemap;
	global $priority;
	global $changefreq;
	global $lastmod;
	
	$input = @file_get_contents ( $spiderurl ) or die ( "Could not access file: $spiderurl" );
	$input = preg_replace ( '/\s+/', ' ', trim ( $input ) );
	fixAmps ( $input, 0 );
	$doc = new DOMDocument ();
	libxml_use_internal_errors ( true );
	$doc->loadHTML ( $input );
	libxml_clear_errors ();
	
	$arr = $doc->getElementsByTagName ( "a" );
	
	foreach ( $arr as $item ) {
		$href = $item->getAttribute ( "href" );
		
		$url = parse_url ( $href );
		if ((($url ['scheme'] == 'http' || $url ['scheme'] == 'https') && $url ['host'] == $domain) || $url ['scheme'] == '') {
			if (! $url ['host'])
				$href = $protocol . "://" . $domain . "/" . $href;
			
			$skiphash = 'a' . hash ( 'sha512', $url ['path'] . $url ['query'] );
			
			if (! $skip [$skiphash]) {
				$sitemap .= "<url>\n<loc>\n" . htmlentities ( $href ) . "\n</loc>\n<lastmod>\n$lastmod\n</lastmod>\n<priority>\n$priority\n</priority>\n<changefreq>\n$changefreq\n</changefreq>\n";
				$arr2 = $doc->getElementsByTagName ( "img" );
				if ($_GET ['show_progress'])
					echo "$href\n";
				foreach ( $arr2 as $item ) {
					$img = $item->getAttribute ( "src" );
					
					$alt = $item->getAttribute ( "alt" );
					$title = $item->getAttribute ( "title" );
					$url = parse_url ( $img );
					if ((($url ['scheme'] == 'http' || $url ['scheme'] == 'https') && $url ['host'] == $domain) || $url ['scheme'] == '') {
						if (! $url ['host'])
							$img = $protocol . "://" . $domain . "/" . $img;
						
						$skiphash2 = 'a' . hash ( 'sha512', $url ['path'] . $url ['query'] );
						
						if (! $skip [$skiphash2]) {
							if ($_GET ['show_progress'])
								echo "   $img\r";
							$sitemap .= "<image:image>\n<image:loc>\n" . htmlentities ( $img ) . "\n</image:loc>\n";
							if ($alt) {
								$sitemap .= "<image:caption>\n" . htmlentities ( $title.' '.$alt ) . "\n</image:caption>\n";
								$sitemap .= "<image:title>\n" . htmlentities ( $alt ) . "\r</image:title>\n";
							}
							$sitemap .= "</image:image>\n";
							$skip [$skiphash2] = 1;
						}
					}
				}
				$sitemap .= "</url>\n";
				$spider_next = 1;
			}
			
			$skip [$skiphash] = 1;
			
			if ($spider_next)
				spider ( $href );
			
			$spider_next = 0;
		}
	}
}
$sitemap .= "</urlset>";

file_put_contents ( $saveas, $sitemap );
function fixAmps(&$html, $offset) {
	$positionAmp = strpos ( $html, '&', $offset );
	$positionSemiColumn = strpos ( $html, ';', $positionAmp + 1 );
	
	$string = substr ( $html, $positionAmp, $positionSemiColumn - $positionAmp + 1 );
	
	if ($positionAmp !== false) { // If an '&' can be found.
		if ($positionSemiColumn === false) { // If no ';' can be found.
			$html = substr_replace ( $html, '&amp;', $positionAmp, 1 ); // Replace straight away.
		} else if (preg_match ( '/&(#[0-9]+|[A-Z|a-z|0-9]+);/', $string ) === 0) { // If a standard escape cannot be found.
			$html = substr_replace ( $html, '&amp;', $positionAmp, 1 ); // This mean we need to escapa the '&' sign.
			fixAmps ( $html, $positionAmp + 5 ); // Recursive call from the new position.
		} else {
			fixAmps ( $html, $positionAmp + 1 ); // Recursive call from the new position.
		}
	}
}
?>