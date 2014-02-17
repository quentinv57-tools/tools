<?php
ini_set("display_errors", 1);
ini_set("memory_limit", '512M');
// An IPv4 address is made of 4 bytes from x00 to xFF which is d0 to d255
define( 'RE_IP_BYTE', '(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])' );
define( 'RE_IP_ADD', RE_IP_BYTE . '\.' . RE_IP_BYTE . '\.' . RE_IP_BYTE . '\.' . RE_IP_BYTE );
// An IPv4 block is an IP address and a prefix (d1 to d32)
define( 'RE_IP_PREFIX', '(3[0-2]|[12]?\d)' );
define( 'RE_IP_BLOCK', RE_IP_ADD . '\/' . RE_IP_PREFIX );
 
// An IPv6 address is made up of 8 words (each x0000 to xFFFF).
// However, the "::" abbreviation can be used on consecutive x0000 words.
define( 'RE_IPV6_WORD', '([0-9A-Fa-f]{1,4})' );
define( 'RE_IPV6_PREFIX', '(12[0-8]|1[01][0-9]|[1-9]?\d)' );
define( 'RE_IPV6_ADD',
   '(?:' . // starts with "::" (including "::")
       ':(?::|(?::' . RE_IPV6_WORD . '){1,7})' .
   '|' . // ends with "::" (except "::")
       RE_IPV6_WORD . '(?::' . RE_IPV6_WORD . '){0,6}::' .
   '|' . // contains one "::" in the middle (the ^ makes the test fail if none found)
       RE_IPV6_WORD . '(?::((?(-1)|:))?' . RE_IPV6_WORD . '){1,6}(?(-2)|^)' .
   '|' . // contains no "::"
       RE_IPV6_WORD . '(?::' . RE_IPV6_WORD . '){7}' .
   ')'
);
// An IPv6 block is an IP address and a prefix (d1 to d128)
define( 'RE_IPV6_BLOCK', RE_IPV6_ADD . '\/' . RE_IPV6_PREFIX );
// For IPv6 canonicalization (NOT for strict validation; these are quite lax!)
define( 'RE_IPV6_GAP', ':(?:0+:)*(?::(?:0+:)*)?' );
define( 'RE_IPV6_V4_PREFIX', '0*' . RE_IPV6_GAP . '(?:ffff:)?' );
 
// This might be useful for regexps used elsewhere, matches any IPv6 or IPv6 address or network
define( 'IP_ADDRESS_STRING',
   '(?:' .
       RE_IP_ADD . '(?:\/' . RE_IP_PREFIX . ')?' . // IPv4
   '|' .
       RE_IPV6_ADD . '(?:\/' . RE_IPV6_PREFIX . ')?' . // IPv6
   ')'
);
/*   ---------------------------------------------

Author : Quentinv57

Licence : GNU General Public License v3
			(see //www.gnu.org/licenses/)
			
Date of creation : 2011-08-28
Last modified : 2012-02-13

Global Contributions TS tool

---------------------------------------------   */

#ob_gzhandler();
$time = microtime( 1 );
// Functions
include ('../include/functions.fct.php');

// just formats the number
function nformat ($nb) { if (!empty($nb)) return $nb; else return 0; }

/**
* Convert an IP into a verbose, uppercase, normalized form.
* IPv6 addresses in octet notation are expanded to 8 words.
* IPv4 addresses are just trimmed.
*
* @param string $ip IP address in quad or octet form (CIDR or not).
* @return String
*/
function sanitizeIP( $ip ) {
   $ip = trim( $ip );
   if ( $ip === '' ) {
	   return null;
   }
   if ( isIPv4( $ip ) || !isIPv6( $ip ) ) {
	   return $ip; // nothing else to do for IPv4 addresses or invalid ones
   }
   // Remove any whitespaces, convert to upper case
   $ip = strtoupper( $ip );
   // Expand zero abbreviations
   $abbrevPos = strpos( $ip, '::' );
   if ( $abbrevPos !== false ) {
	   // We know this is valid IPv6. Find the last index of the
	   // address before any CIDR number (e.g. "a:b:c::/24").
	   $CIDRStart = strpos( $ip, "/" );
	   $addressEnd = ( $CIDRStart !== false )
		   ? $CIDRStart - 1
		   : strlen( $ip ) - 1;
	   // If the '::' is at the beginning...
	   if ( $abbrevPos == 0 ) {
		   $repeat = '0:';
		   $extra = ( $ip == '::' ) ? '0' : ''; // for the address '::'
		   $pad = 9; // 7+2 (due to '::')
	   // If the '::' is at the end...
	   } elseif ( $abbrevPos == ( $addressEnd - 1 ) ) {
		   $repeat = ':0';
		   $extra = '';
		   $pad = 9; // 7+2 (due to '::')
	   // If the '::' is in the middle...
	   } else {
		   $repeat = ':0';
		   $extra = ':';
		   $pad = 8; // 6+2 (due to '::')
	   }
	   $ip = str_replace( '::',
		   str_repeat( $repeat, $pad - substr_count( $ip, ':' ) ) . $extra,
		   $ip
	   );
   }
   // Remove leading zeros from each bloc as needed
   $ip = preg_replace( '/(^|:)0+(' . RE_IPV6_WORD . ')/', '$1$2', $ip );

   return $ip;
}
function isIPv4( $ip ) {
   return (bool)preg_match( '/^' . RE_IP_ADD . '(?:\/' . RE_IP_PREFIX . ')?$/', $ip );
}
function isIPv6( $ip ) {
   return (bool)preg_match( '/^' . RE_IPV6_ADD . '(?:\/' . RE_IPV6_PREFIX . ')?$/', $ip );
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "//www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="//www.w3.org/1999/xhtml" lang="en-US" xml:lang="en-US">
<head><title>Quentinv57's Tools! - Global Contributions Watcher</title><style type="text/css">
<!--/* <![CDATA[ */
@import "//tools.wmflabs.org/quentinv57-tools/main.css"; 
@import "//fr.wikipedia.org/w/index.php?title=MediaWiki:Monobook.css&usemsgcache=yes&action=raw&ctype=text/css&smaxage=2678400";
@import "//fr.wikipedia.org/w/index.php?title=MediaWiki:Common.css&usemsgcache=yes&action=raw&ctype=text/css&smaxage=2678400";
@import "//fr.wikipedia.org/w/index.php?title=-&action=raw&gen=css&maxage=2678400&smaxage=0&ts=20061201185052"; 
/* ]]> */-->
td { vertical-align: top; }
.external, .external:hover, .external:visited { color: black; text-decoration: none; border-bottom-width: 1px; border-bottom-style: dotted; }
.external:hover { border-bottom-width: 0px; }
.autocomment{color:gray}

</style>
<!--[if lt IE 5.5000]><style type="text/css">@import "/skins-1.5/monobook/IE50Fixes.css?82";</style><![endif]-->
<!--[if IE 5.5000]><style type="text/css">@import "/skins-1.5/monobook/IE55Fixes.css?82";</style><![endif]-->
<!--[if IE 6]><style type="text/css">@import "/skins-1.5/monobook/IE60Fixes.css?82";</style><![endif]-->
<!--[if IE 7]><style type="text/css">@import "/skins-1.5/monobook/IE70Fixes.css?82";</style><![endif]-->
<!--[if lt IE 7]><script type="text/javascript" src="/skins-1.5/common/IEFixes.js?82"></script>
<meta http-equiv="imagetoolbar" content="no" /><![endif]-->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body class="mediawiki"><div id="globalWrapper"><div id="column-content"><div id="content">
<h1>Global Contributions Watcher</h1>
<br />
<?php

// firstly : displays the form  -----------------------------------------------------------------------------------------
if (empty($_GET['username']))
{
?><fieldset><legend>Global Contributions Watcher</legend>
<form method="get" action="globalcontribs.php" id="mw-globalsysoplog-form1">
<table border="0" id="mw-movepage-table"> 
<tr><td class='mw-label'><label for="username">Username :</label></td><td class='mw-input'><input id="username" name="username" type="text" /></td></tr>
<tr><td>&#160;</td><td class="mw-input"><input id="onlylastdays" name="onlylastdays" type="checkbox" value="1" />&#160;Only display the last <input onclick="document.getElementById('onlylastdays').checked='checked';" type="text" size="2" id="nbdays" name="nbdays" value="7" /> days</tr></td>
<tr><td>&#160;</td><td class='mw-submit'><input type="submit" value="Go !" /></td></tr>
</table>
</form>
<?php
}

// secondly : displays the results  -----------------------------------------------------------------------------------------
else
{
	// open connection from database (on s7 are both toolserver and centralauth_p databases)
	$toolserver_mycnf = parse_ini_file("/data/project/quentinv57-tools/replica.my.cnf");
	$mysql_ts = new MySQLi ('enwiki.labsdb',$toolserver_mycnf['user'], $toolserver_mycnf['password'], 'meta_p');
	unset($toolserver_mycnf);
	$querystring1 = "SELECT `wiki`.`dbname`, `url`, `script_path` from `wiki` LEFT JOIN `legacy` ON `legacy`.`dbname`=`wiki`.`dbname`";
	
	// taking GET vars
	$username = $mysql_ts->real_escape_string(sanitizeIP($_GET['username']));
	$limit = (!empty($_GET['limit'])) ? intval($_GET['limit']) : '50'; // 50 is the limit by default
	if ($limit>10000) $limit = 10000; // limit can't be over 10000
	$offset = (!empty($_GET['offset'])) ? intval($_GET['offset']) : 0;
	$disponly_nbdays = (isset($_GET['onlylastdays']) && $_GET['onlylastdays'] && !empty($_GET['nbdays'])) ? intval($_GET['nbdays']) : 0 ;
	$disponly_qry = ($disponly_nbdays>0) ? " AND `rev_timestamp`>".date('YmdHis', (time()-$disponly_nbdays*86400)) : '';
	
	// getting a lits of wikis from the database (only non-locked)
	$res = $mysql_ts->query ("SELECT `dbname`,`slice`,`url` FROM `wiki` WHERE `is_closed`=0 ORDER BY `slice`"); 
	$arr_databases = array();
	$api_queries = array();
	
	$contribs = array(); // here will be stored all logging
	$contribs_time = array(); // to sort by timestamp
	$namespaces = array(); // we're forced to store namespaces...
	
	while ($line = $res->fetch_assoc()) { $arr_databases[$line['slice']][$line['dbname']] = $line['url']; }
	$res->free();
	if( $res = $mysql_ts->query( $querystring1 ) ) {
		while( $line = $res->fetch_assoc() ) {
			$api_link = $line['url'].$line['script_path']."api.php";
			$query = "?action=query&meta=siteinfo&format=php&siprop=namespaces";
			$api_queries[$line['dbname']] = $api_link.$query;
		}
	} else {
		die( $mysql_ts->error );
	}
	$res->free();
	
	// first loop, for each SQL server
	foreach ($arr_databases as $sql_server => $content)
	{
		// re-opening database connection everytime host is changing
		$toolserver_mycnf = parse_ini_file("/data/project/quentinv57-tools/replica.my.cnf");
		$mysql = new MySQLi ($sql_server, $toolserver_mycnf['user'], $toolserver_mycnf['password']); // or echo "<p>SQL error : Can't connect to $sql_server</p>";
		unset($toolserver_mycnf);
		$querystring2 = "";
		
		//Let's use the new union method to cut the execution time by a factor of 100x and fallback to the old setup in the event of a failure.
		$i = 0;
		foreach ($content as $db => $domain)
		{
			$project = $db;
			if( $project == "centralauth" ) {
				$i++;
				continue;
			}
			$querystring2 .= "(SELECT `dbname`, `rev_comment`,`rev_timestamp`,`rev_minor_edit`,`page_title`,`page_namespace`,`page_is_new`,(`rev_id`=`page_latest`) as page_is_last FROM `$db"."_p`.`revision_userindex` LEFT JOIN `$db"."_p`.`page` ON `$db"."_p`.`page`.`page_id`=`$db"."_p`.`revision_userindex`.`rev_page` LEFT JOIN `meta_p`.`wiki` ON `meta_p`.`wiki`.`dbname`='$db' WHERE `rev_user_text`='" .$username. "'" .$disponly_qry. ")";
			$i++;
			if( $i != count( $content ) ) $querystring2 .= " UNION ";
		}

		if( $res = $mysql->query ($querystring2) ) { //Try the UNION approach and fallback onto individual on failure.
			while ($line = $res->fetch_assoc())
			{
				$domain = $content[$line['dbname']];
				$contribs[] = array (	'project' => $line['dbname'],
										'url' => $domain,
										'title' => $line['page_title'],
										'ns' => $line['page_namespace'],
										'comment' => $line['rev_comment'],
										'isminor' => $line['rev_minor_edit'],
										'isnew' => $line['page_is_new'],
										'islast' => $line['page_is_last']);
																		
				$contribs_time[] = wfTimestamp($line['rev_timestamp']);
			}
			
			$res->free();
		} else {	// second loop, for each database (= each wiki)
			foreach ($content as $db => $domain)
			{
				$project = $db;
				if( $project == "centralauth" ) continue;
				
				$mysql->select_db($db."_p");
				$res = $mysql->query ("SELECT `rev_comment`,`rev_timestamp`,`rev_minor_edit`,`page_title`,`page_namespace`,`page_is_new`,(`rev_id`=`page_latest`) as page_is_last FROM `revision_userindex` LEFT JOIN `page` ON `page`.`page_id`=`revision_userindex`.`rev_page`  WHERE `rev_user_text`='" .$username. "'" .$disponly_qry. ""); // $disponly_qry adds a condition on the timestamp
				
				if (empty($namespaces[$project])) {
					
					//This method is not usable on labs.  So let's use the API instead.
					/*$res2 = $mysql_ts->query ("SELECT `ns_id`,`ns_name` FROM `namespacename` WHERE `dbname`='" .$db. "' AND `ns_is_favorite`=1");
					
					while ($line = $res2->fetch_assoc())
					{
						if ($line['ns_id']>0) 	$namespaces[$project][$line['ns_id']] = $line['ns_name'];
					}
					$res2->free();*/
					
					//In order to do the API, we need to construct a link to the api.
					//Let's use the legacy view on meta_p to do that.
					if( $res2 = $mysql_ts->query( "SELECT `url`, `script_path` from `wiki` LEFT JOIN `legacy` ON `legacy`.`dbname`='$project' WHERE `wiki`.`dbname`='$project'" ) ) {
						$line = $res2->fetch_assoc();
						$api_link = $line['url'].$line['script_path']."api.php";
						$query = "?action=query&meta=siteinfo&format=php&siprop=namespaces";
						$results = unserialize( file_get_contents( $api_link.$query ) );
						foreach( $results['query']['namespaces'] as $id=>$namespace ) {
							if( $id > 0 ) $namespaces[$project][$id] = $namespace['*'];
						}
					} else {
						die( $mysql_ts->error );
					}
					$res2->free();
				}
				
				// then a while loop on the MySQL result
				while ($line = $res->fetch_assoc())
				{
					$contribs[] = array (	'project' => $project,
											'url' => '//'.$domain,
											'title' => $line['page_title'],
											'ns' => $line['page_namespace'],
											'comment' => $line['rev_comment'],
											'isminor' => $line['rev_minor_edit'],
											'isnew' => $line['page_is_new'],
											'islast' => $line['page_is_last']);
																			
					$contribs_time[] = wfTimestamp($line['rev_timestamp']);
				}
				
				$res->free();
			}
		}
		$mysql->close();
	}

	$mysql_ts->close();
	
	// now that SQL connection is closed, we can stripslashes as it fixes a display issue with apostrophes (requested by Cantons-de-l'Est)
	$username = htmlspecialchars(stripslashes($username));
	
// now, displaying the log
?>

<fieldset><legend>Global Contributions Watcher</legend>
<?php if ($disponly_nbdays>0) echo "<p>Only the last <strong>".$disponly_nbdays."</strong> days are displayed.</p>"; 
/* under this comment are navig-links : if you make changes to this, don't forget to update the copy which is under the log display */ ?>
(<?php echo (empty($offset)) ? 'Latest' : '<a href="globalcontribs.php?username='.$username.'&limit='.$limit.'&offset=&onlylastdays='.(( isset($_GET['onlylastactive']) ) ? (( isset($_GET['onlylastactive']) ) ? htmlspecialchars($_GET['onlylastdays']) : "") : "").'&nbdays='.$disponly_nbdays.'">Latest</a>'; ?> | 
<?php echo (($offset+$limit)>=count($contribs)) ? 'Earliest' : '<a href="globalcontribs.php?username='.$username.'&limit='.$limit.'&offset='.intval(count($contribs)-$limit).'&onlylastdays='.(( isset($_GET['onlylastactive']) ) ? (( isset($_GET['onlylastactive']) ) ? htmlspecialchars($_GET['onlylastdays']) : "") : "").'&nbdays='.$disponly_nbdays.'">Earliest</a>'; ?>) 
View (<?php echo (empty($offset)) ? 'newer '.$limit : '<a href="globalcontribs.php?username='.$username.'&limit='.$limit.'&offset='.intval($offset-$limit).'&onlylastdays='.(( isset($_GET['onlylastactive']) ) ? (( isset($_GET['onlylastactive']) ) ? htmlspecialchars($_GET['onlylastdays']) : "") : "").'&nbdays='.$disponly_nbdays.'">newer '.$limit.'</a>'; ?> | 
<?php echo (($offset+$limit)>=count($contribs)) ? 'older '.$limit : '<a href="globalcontribs.php?username='.$username.'&limit='.$limit.'&offset='.intval($offset+$limit).'&onlylastdays='.(( isset($_GET['onlylastactive']) ) ? (( isset($_GET['onlylastactive']) ) ? htmlspecialchars($_GET['onlylastdays']) : "") : "").'&nbdays='.$disponly_nbdays.'">older '.$limit.'</a>'; ?>) 
(<a href="globalcontribs.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=20&onlylastdays=<?php echo (( isset($_GET['onlylastactive']) ) ? htmlspecialchars($_GET['onlylastdays']) : ""); ?>&nbdays=<?php echo $disponly_nbdays; ?>">20</a> | 
<a href="globalcontribs.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=50&onlylastdays=<?php echo (( isset($_GET['onlylastactive']) ) ? htmlspecialchars($_GET['onlylastdays']) : ""); ?>&nbdays=<?php echo $disponly_nbdays; ?>">50</a> | 
<a href="globalcontribs.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=100&onlylastdays=<?php echo (( isset($_GET['onlylastactive']) ) ? htmlspecialchars($_GET['onlylastdays']) : ""); ?>&nbdays=<?php echo $disponly_nbdays; ?>">100</a> | 
<a href="globalcontribs.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=250&onlylastdays=<?php echo (( isset($_GET['onlylastactive']) ) ? htmlspecialchars($_GET['onlylastdays']) : ""); ?>&nbdays=<?php echo $disponly_nbdays; ?>">250</a> | 
<a href="globalcontribs.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=500&onlylastdays=<?php echo (( isset($_GET['onlylastactive']) ) ? htmlspecialchars($_GET['onlylastdays']) : ""); ?>&nbdays=<?php echo $disponly_nbdays; ?>">500</a>)
<ul>
<?php
	array_multisort($contribs_time,SORT_NUMERIC,$contribs); // sorted by timestamp
	$contribs = array_reverse($contribs,TRUE);
	
	$i=0;
	foreach ($contribs as $key => $item)
	{
		$i++; // here is the counter
		if ($i>($offset+$limit)) {
			break;
		}
		
		// only began the display at the offset
		if ($i>=$offset) {
			// these vars are just here to make the display more easy
			$usernamelink = '<a href="' .$item['url']. '/wiki/User:' .$username. '">' .$username.'@'.$item['project'].'</a> (<a href="' .$item['url']. '/wiki/User talk:' .$username. '">talk</a> | <a href="' .$item['url']. '/wiki/Special:Contributions/' .$username. '">contribs</a> | <a href="' .$item['url']. '/wiki/Special:Log/' .$username. '">logs</a>)';
			if( empty( $namespaces[$item['project']] ) ) {
				$results = unserialize( file_get_contents( $api_queries[$item['project']] ) );
				foreach( $results['query']['namespaces'] as $id=>$namespace ) {
					if( $id > 0 ) $namespaces[$item['project']][$id] = $namespace['*'];
				}
			}
			$page = ($item['ns']>0) ? $namespaces[$item['project']][$item['ns']].':'.$item['title'] : $item['title'];
			$pagelink = '<a href="' .$item['url']. '/wiki/' .htmlspecialchars($page). '">' .str_replace('_',' ',htmlspecialchars($page)).'</a>';
			$flags = ($item['isnew']) ? 'N':'';
			$flags .= ($item['isminor']) ? 'm':'';
			$flags = (!empty($flags)) ? '<strong>'.$flags.'</strong> ' : '';
			$top = ($item['islast']) ? ' <strong>(top)</strong>' : '';
			
?><li><?php echo display_time($contribs_time[$key]); ?> <?php echo $usernamelink; ?> edited <?php echo $flags . $pagelink . $top; ?> <?php echo comment_linkify($item['comment']); ?></li>
<?php
		}
	}
?></ul>
<?php /* this is a copy of the navig links, which are just before the log display */ ?>
(<?php echo (empty($offset)) ? 'Latest' : '<a href="globalcontribs.php?username='.$username.'&limit='.$limit.'&offset=&onlylastdays='.(( isset($_GET['onlylastactive']) ) ? htmlspecialchars($_GET['onlylastdays']) : "").'&nbdays='.$disponly_nbdays.'">Latest</a>'; ?> | 
<?php echo (($offset+$limit)>=count($contribs)) ? 'Earliest' : '<a href="globalcontribs.php?username='.$username.'&limit='.$limit.'&offset='.intval(count($contribs)-$limit).'&onlylastdays='.(( isset($_GET['onlylastactive']) ) ? htmlspecialchars($_GET['onlylastdays']) : "").'&nbdays='.$disponly_nbdays.'">Earliest</a>'; ?>) 
View (<?php echo (empty($offset)) ? 'newer '.$limit : '<a href="globalcontribs.php?username='.$username.'&limit='.$limit.'&offset='.intval($offset-$limit).'&onlylastdays='.(( isset($_GET['onlylastactive']) ) ? htmlspecialchars($_GET['onlylastdays']) : "").'&nbdays='.$disponly_nbdays.'">newer '.$limit.'</a>'; ?> | 
<?php echo (($offset+$limit)>=count($contribs)) ? 'older '.$limit : '<a href="globalcontribs.php?username='.$username.'&limit='.$limit.'&offset='.intval($offset+$limit).'&onlylastdays='.(( isset($_GET['onlylastactive']) ) ? htmlspecialchars($_GET['onlylastdays']) : "").'&nbdays='.$disponly_nbdays.'">older '.$limit.'</a>'; ?>) 
(<a href="globalcontribs.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=20&onlylastdays=<?php echo (( isset($_GET['onlylastactive']) ) ? htmlspecialchars($_GET['onlylastdays']) : ""); ?>&nbdays=<?php echo $disponly_nbdays; ?>">20</a> | 
<a href="globalcontribs.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=50&onlylastdays=<?php echo (( isset($_GET['onlylastactive']) ) ? htmlspecialchars($_GET['onlylastdays']) : ""); ?>&nbdays=<?php echo $disponly_nbdays; ?>">50</a> | 
<a href="globalcontribs.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=100&onlylastdays=<?php echo (( isset($_GET['onlylastactive']) ) ? htmlspecialchars($_GET['onlylastdays']) : ""); ?>&nbdays=<?php echo $disponly_nbdays; ?>">100</a> | 
<a href="globalcontribs.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=250&onlylastdays=<?php echo (( isset($_GET['onlylastactive']) ) ? htmlspecialchars($_GET['onlylastdays']) : ""); ?>&nbdays=<?php echo $disponly_nbdays; ?>">250</a> | 
<a href="globalcontribs.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=500&onlylastdays=<?php echo (( isset($_GET['onlylastactive']) ) ? htmlspecialchars($_GET['onlylastdays']) : ""); ?>&nbdays=<?php echo $disponly_nbdays; ?>">500</a>)
<?php

// stats
logging();
}

?></div></div>
<?php include ('../include/tools_menu.html'); ?>
Executed in <?php echo round( microtime( 1 )-$time, 2 );?> seconds.
</body></html>

