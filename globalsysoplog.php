<?php

/*   ---------------------------------------------

Author : Quentinv57

Licence : GNU General Public License v3
			(see //www.gnu.org/licenses/)
			
Date of creation : 2011-08-27
Last modified : 2011-08-28

Global Sysop Log TS tool
			
---------------------------------------------   */

#ob_gzhandler();

// Functions
include ('../include/functions.fct.php');

// just formats the number
function nformat ($nb) { if (!empty($nb)) return $nb; else return 0; }



// this array contains what will be displayed (work by Mr. Tanvir :D)
$displayed_actions = array (	'delete' => "deleted",
								'restore' => "restored",
								'block' => "blocked",
								'unblock' => "unblocked",
								'reblock' => "changed blocked settings of",
								'protect' => "protected",
								'unprotect' => "unprotected",
								'modify' => "changed protection settings of",
								'rights' => "changed user rights of",
								'renameuser' => "renamed");


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "//www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="//www.w3.org/1999/xhtml" lang="en-US" xml:lang="en-US">
<head><title>Quentinv57's Tools! - Global Sysop Log</title><style type="text/css">
<!--/* <![CDATA[ */
@import "//tools.wmflabs.org/quentinv57-tools/main.css"; 
@import "//fr.wikipedia.org/w/index.php?title=MediaWiki:Monobook.css&usemsgcache=yes&action=raw&ctype=text/css&smaxage=2678400";
@import "//fr.wikipedia.org/w/index.php?title=MediaWiki:Common.css&usemsgcache=yes&action=raw&ctype=text/css&smaxage=2678400";
@import "//fr.wikipedia.org/w/index.php?title=-&action=raw&gen=css&maxage=2678400&smaxage=0&ts=20061201185052"; 
/* ]]> */-->
td { vertical-align: top; }
.external, .external:visited { color: #222222; }
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
<h1>Global Sysop Log</h1>
<br />
<?php

// firstly : displays the form  -----------------------------------------------------------------------------------------
if (empty($_GET['username']))
{
?><fieldset><legend>Global Sysop Log</legend>
<form method="get" action="globalsysoplog.php" id="mw-globalsysoplog-form1">
<table border="0" id="mw-movepage-table"> 
<tr><td class='mw-label'><label for="username">Username :</label></td><td class='mw-input'><input id="username" name="username" type="text" /></td></tr>
<tr><td>&#160;</td><td class="mw-input"><input id="showonlyGS" name="showonlyGS" type="checkbox" value="1" />&#160;<label for="showonlyGS">Show only GS wikis</label></td></tr>
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
	
	// taking GET vars
	$username = $mysql_ts->real_escape_string($_GET['username']);
	$showonlyGS = ($_GET['showonlyGS']==1)? TRUE : FALSE;
	$limit = (!empty($_GET['limit'])) ? intval($_GET['limit']) : '50'; // 50 is the limit by default
	if ($limit>10000) $limit = 10000; // limit can't be over 10000
	$offset = (!empty($_GET['offset'])) ? intval($_GET['offset']) : 0;
	
	// showonly-GS option :
	if ($showonlyGS) {
		$mysql_ts->close();
		$toolserver_mycnf = parse_ini_file("/data/project/quentinv57-tools/replica.my.cnf");
		$mysql_ts = new MySQLi ('centralauth.labsdb',$toolserver_mycnf['user'], $toolserver_mycnf['password'], 'centralauth_p'); // hopes centralauth_p will stay on s7
		
		$res = $mysql_ts->query("SELECT `ws_wikis` FROM `wikiset` WHERE `ws_name`='Opted-out of global sysop wikis'");
		$req = $res->fetch_assoc();
		$res->free();
		
		$gsoutarray = explode(',',$req['ws_wikis']);
		$mysql_ts->close();
		$mysql_ts = new MySQLi ('enwiki.labsdb',$toolserver_mycnf['user'], $toolserver_mycnf['password'], 'meta_p'); // don't forget to switch on "toolserver" database when finished
		unset( $toolserver_mycnf );
	}
	
	// getting a lits of wikis from the database (only non-locked)
	$res = $mysql_ts->query ("SELECT `dbname`,`slice`,`url` FROM `wiki` WHERE `is_closed`=0 ORDER BY `slice`"); 
	$arr_databases = array();
	while ($line = $res->fetch_assoc()) { $arr_databases[$line['slice']][$line['dbname']] = $line['url']; }
	$res->free();
	
	
	$log = array(); // here will be stored all logging
	$log_time = array(); // to sort by timestamp
	$namespaces = array(); // we're forced to store namespaces...
	
	// first loop, for each SQL server
	foreach ($arr_databases as $sql_server => $content)
	{
		// re-opening database connection everytime host is changing
		$toolserver_mycnf = parse_ini_file("/data/project/quentinv57-tools/replica.my.cnf");
		$mysql = new MySQLi ($sql_server, $toolserver_mycnf['user'], $toolserver_mycnf['password']); // or echo "<p>SQL error : Can't connect to $sql_server</p>";
		unset($toolserver_mycnf);
	
		// second loop, for each database (= each wiki)
		foreach ($content as $db => $domain)
		{
			$project = substr($db,0,-2);
			
			// now the showonly-GS option : the wiki logs will be only displayed if the option is not enabled, or if the wiki has not opted out
			if (!$showonlyGS || !in_array($project,$gsoutarray))
			{
				$mysql->select_db($db);
				$res = $mysql->query ("SELECT `log_action`,`log_timestamp`,`log_title`,`log_namespace`,`log_comment`,`log_params` FROM `logging_userindex` LEFT JOIN `user` ON `user`.`user_id`=`logging_userindex`.`log_user` WHERE `user_name`='" .$username. "'");
				
				if (empty($namespaces[$project])) {
					$res2 = $mysql_ts->query ("SELECT `ns_id`,`ns_name` FROM `namespacename` WHERE `dbname`='" .$db. "'");
					
					while ($line = $res2->fetch_assoc())
					{
						if ($line['ns_id']>0) 	$namespaces[$project][$line['ns_id']] = $line['ns_name'];
					}
					$res2->free();
				}
				
				// then a while loop on the MySQL result
				while ($line = $res->fetch_assoc())
				{
					// if this action should be displayed
					if (array_key_exists($line['log_action'], $displayed_actions))
					{
						$log[] = array (	'project' => $project,
											'url' => '//'.$domain,
											'action' => $line['log_action'],
											'title' => $line['log_title'],
											'ns' => $line['log_namespace'],
											'comment' => $line['log_comment'],
											'params' => $line['log_params']);
																				
						$log_time[] = wfTimestamp($line['log_timestamp']);
					}
				}
				
				$res->free();
			}
		}
		$mysql->close();
	}
	
	$mysql_ts->close();
	
	// now that SQL connection is closed, we can stripslashes as it fixes a display issue with appostrophies (requested by Cantons-de-l'Est)
	$username = htmlspecialchars(stripslashes($username));
	
// now, displaying the log
?>

<fieldset><legend>Global Sysop Log</legend>
(<?php echo (empty($offset)) ? 'Latest' : '<a href="globalsysoplog.php?username='.$username.'&limit='.$limit.'&offset=&showonlyGS='.$showonlyGS.'">Latest</a>'; ?> | <?php echo (($offset+$limit)>=count($log)) ? 'Earliest' : '<a href="globalsysoplog.php?username='.$username.'&limit='.$limit.'&offset='.intval(count($log)-$limit).'&showonlyGS='.$showonlyGS.'">Earliest</a>'; ?>) View (<?php echo (empty($offset)) ? 'newer '.$limit : '<a href="globalsysoplog.php?username='.$username.'&limit='.$limit.'&offset='.intval($offset-$limit).'&showonlyGS='.$showonlyGS.'">newer '.$limit.'</a>'; ?> | <?php echo (($offset+$limit)>=count($log)) ? 'older '.$limit : '<a href="globalsysoplog.php?username='.$username.'&limit='.$limit.'&offset='.intval($offset+$limit).'&showonlyGS='.$showonlyGS.'">older '.$limit.'</a>'; ?>) (<a href="globalsysoplog.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=20&showonlyGS=<?php echo $showonlyGS; ?>">20</a> | <a href="globalsysoplog.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=50&showonlyGS=<?php echo $showonlyGS; ?>">50</a> | <a href="globalsysoplog.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=100&showonlyGS=<?php echo $showonlyGS; ?>">100</a> | <a href="globalsysoplog.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=250&showonlyGS=<?php echo $showonlyGS; ?>">250</a> | <a href="globalsysoplog.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=500&showonlyGS=<?php echo $showonlyGS; ?>">500</a>)
<ul>
<?php
	array_multisort($log_time,SORT_NUMERIC,$log); // sorted by timestamp
	$log = array_reverse($log,TRUE);
	
	$i=0;
	foreach ($log as $key => $item)
	{
		$i++; // here is the counter
		if ($i>($offset+$limit)) {
			break;
		}
		
		// only began the display at the offset
		if ($i>=$offset) {
			// these vars are just here to make the display more easy
			$usernamelink = '<a href="' .$item['url']. '/wiki/User:' .$username. '">' .$username.'@'.$item['project'].'</a> (<a href="' .$item['url']. '/wiki/User talk:' .$username. '">talk</a> | <a href="' .$item['url']. '/wiki/Special:Contributions/' .$username. '">contribs</a> | <a href="' .$item['url']. '/wiki/Special:Log/' .$username. '">logs</a>)';
			$page = ($item['ns']>0) ? $namespaces[$item['project']][$item['ns']].':'.$item['title'] : $item['title'];
			$pagelink = '<a href="' .$item['url']. '/wiki/' .htmlspecialchars($page). '">' .str_replace('_',' ',htmlspecialchars($page)).'</a>';
			$params = (!empty($item['params'])) ? htmlspecialchars($item['params']) : '';
			if ($item['action']=='renameuser') 	$params = 'to <a href="' .$item['url']. '/wiki/User:' .htmlspecialchars($params). '">'.htmlspecialchars($params).'</a>'; 
			if ($item['action']=='rights') 	$params = 'to <em>'.htmlspecialchars($params).'</em>';
			$params .= (!empty($item['params'])) ? ' ' : '';		
			
?><li><?php echo display_time($log_time[$key]); ?> <?php echo $usernamelink; ?> <?php echo $displayed_actions[$item['action']]; ?> <?php echo $pagelink; ?> <?php echo $params; ?><?php echo comment_linkify($item['comment']); ?></li>
<?php
		}
	}
?></ul>
(<?php echo (empty($offset)) ? 'Latest' : '<a href="globalsysoplog.php?username='.$username.'&limit='.$limit.'&offset=&showonlyGS='.$showonlyGS.'">Latest</a>'; ?> | <?php echo (($offset+$limit)>=count($log)) ? 'Earliest' : '<a href="globalsysoplog.php?username='.$username.'&limit='.$limit.'&offset='.intval(count($log)-$limit).'&showonlyGS='.$showonlyGS.'">Earliest</a>'; ?>) View (<?php echo (empty($offset)) ? 'newer '.$limit : '<a href="globalsysoplog.php?username='.$username.'&limit='.$limit.'&offset='.intval($offset-$limit).'&showonlyGS='.$showonlyGS.'">newer '.$limit.'</a>'; ?> | <?php echo (($offset+$limit)>=count($log)) ? 'older '.$limit : '<a href="globalsysoplog.php?username='.$username.'&limit='.$limit.'&offset='.intval($offset+$limit).'&showonlyGS='.$showonlyGS.'">older '.$limit.'</a>'; ?>) (<a href="globalsysoplog.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=20&showonlyGS=<?php echo $showonlyGS; ?>">20</a> | <a href="globalsysoplog.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=50&showonlyGS=<?php echo $showonlyGS; ?>">50</a> | <a href="globalsysoplog.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=100&showonlyGS=<?php echo $showonlyGS; ?>">100</a> | <a href="globalsysoplog.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=250&showonlyGS=<?php echo $showonlyGS; ?>">250</a> | <a href="globalsysoplog.php?username=<?php echo $username; ?>&offset=<?php echo $offset; ?>&limit=500&showonlyGS=<?php echo $showonlyGS; ?>">500</a>)
<?php

// stats
logging();
}

?></div></div>
<?php include ('../include/tools_menu.html'); ?>
</body></html>

