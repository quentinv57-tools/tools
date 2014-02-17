<?php

/*   ---------------------------------------------

Author : Quentinv57

Licence : GNU General Public License v3
			(see //www.gnu.org/licenses/)
			
Date of creation : 2011-08-27
Last modified : 2012-01-21

Global Sysop Stats TS tool
			
---------------------------------------------   */

#ob_gzhandler();

// Functions
include ('../include/functions.fct.php');

// just formats the number
function nformat ($nb) { if (!empty($nb)) return $nb; else return 0; }

// this array contains what will be displayed (work by Mr. Tanvir :D)
$displayed_actions = array (	'delete' => "Deletions",
								'restore' => "Restored pages",
								'block' => "Blocks",
								'unblock' => "Unblocks",
								'reblock' => "Block changes",
								'protect' => "Protections",
								'unprotect' => "Unprotections",
								'modify' => "Protection changes",
								'rights' => "Rights changes",
								'renameuser' => "Renamings");


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "//www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="//www.w3.org/1999/xhtml" lang="en-US" xml:lang="en-US">
<head><title>Quentinv57's Tools! - Global Sysop Statistics</title><style type="text/css">
<!--/* <![CDATA[ */
@import "//tools.wmflabs.org/quentinv57-tools/main.css"; 
@import "//fr.wikipedia.org/w/index.php?title=MediaWiki:Monobook.css&usemsgcache=yes&action=raw&ctype=text/css&smaxage=2678400";
@import "//fr.wikipedia.org/w/index.php?title=MediaWiki:Common.css&usemsgcache=yes&action=raw&ctype=text/css&smaxage=2678400";
@import "//fr.wikipedia.org/w/index.php?title=-&action=raw&gen=css&maxage=2678400&smaxage=0&ts=20061201185052"; 
@import "//bits.wikimedia.org/skins/common/shared.css";
/* ]]> */-->
td { vertical-align: top; }
.external, .external:visited { color: black; }

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
<h1>Global Sysop Statistics</h1>
<br />
<?php

// firstly : displays the form  -----------------------------------------------------------------------------------------
if (empty($_GET['username']))
{
?><fieldset><legend>Global Sysop Statistics</legend>
<form method="get" action="globalsysopstats.php" id="mw-globalsysopstats-form1">
<table border="0" id="mw-movepage-table"> 
<tr><td class='mw-label'><label for="username">Username :</label></td><td class='mw-input'><input id="username" name="username" type="text" /></td></tr>
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
	$mysql = new MySQLi ('enwiki.labsdb',$toolserver_mycnf['user'], $toolserver_mycnf['password'], 'meta_p');
	unset($toolserver_mycnf);
	
	// taking GET vars
	$username = $mysql->real_escape_string($_GET['username']);
	
	// getting a lits of wikis from the database (only non-locked)
	$res = $mysql->query ("SELECT `dbname`,`slice`,`url` FROM `wiki` WHERE `is_closed`=0 ORDER BY `slice`"); 
	$arr_databases = array();
	while ($line = $res->fetch_assoc()) { $arr_databases[$line['slice']][$line['dbname']] = $line['url']; }
	$res->free();
	
	
	$actions = array(); // here will be stored all actions
	$total = array(); // and here will be the total
	$nbwikistouched = 0; // and the number of wikis touched
	
	// first loop, for each SQL server
	foreach ($arr_databases as $sql_server => $content)
	{
		// re-opening database connection everytime host is changing
		$mysql->close();
		$toolserver_mycnf = parse_ini_file("/data/project/quentinv57-tools/replica.my.cnf");
		$mysql = new MySQLi ($sql_server, $toolserver_mycnf['user'], $toolserver_mycnf['password']); // or echo "<p>SQL error : Can't connect to $sql_server</p>";
		$mysql->set_charset("utf8"); // sometimes better
		unset($toolserver_mycnf);
	
		// second loop, for each database (= each wiki)
		foreach ($content as $db => $domain)
		{ 
			$project = substr($db,0,-2);
			$project_url = '//' .$domain; // much better :)
			
			$mysql->select_db($db);
			$res = $mysql->query ("SELECT `log_action`,`log_timestamp`,`log_title`,`log_comment`,`log_params` FROM `logging_userindex` LEFT JOIN `user` ON `user`.`user_id`=`logging_userindex`.`log_user` WHERE `user_name`='" .$username. "'");
			
			// then a while loop on the MySQL result
			while ($line = $res->fetch_assoc())
			{
				// if this action should be displayed
				if (array_key_exists($line['log_action'], $displayed_actions))
				{
					$actions[$project][$line['log_action']] ++;
					$actions[$project]['total'] ++;
					$total[$line['log_action']] ++;
					$total['total'] ++;
					
					if ($actions[$project]['total']==1) $nbwikistouched++;
				}
			}
			
			$res->free();
		}
	}
	
	$mysql->close();
	
	// now that SQL connection is closed, we can stripslashes as it fixes a display issue with appostrophies (requested by Cantons-de-l'Est)
	$username = htmlspecialchars(stripslashes($username));
	
// now, displaying the results
?>

<fieldset><legend>Global Sysop Statistics</legend>
<p><strong>Name :</strong> <?php echo $username; ?></p>
<p><strong>Total actions :</strong> <?php echo number_format($total['total'],0,'.',' '); ?></p>
<p><strong>Number of wikis :</strong> <?php echo nformat($nbwikistouched); ?></p>
<?php
	foreach ($displayed_actions as $key => $title)
			echo '<p>Total ' .strtolower($title). ' : ' .number_format($total[$key],0,'.',' '). '</p>'."\n";
?></fieldset>


<table class="wikitable sortable">
  <tr><th>Wiki</th><th>Total actions</th>
<?php
	// Displays the heading of the stats table
	foreach ($displayed_actions as $key => $title)
		echo '<th>' .$title. '</th>';
	
	echo "</tr>\n";

	ksort($actions); // sorting per project alpha

	// Displays the content of the table
	foreach ($actions as $project => $content)
	{
		echo '  <tr><td>' .$project. '</td><td>' .nformat($content['total']). '</td>';
		
		foreach ($displayed_actions as $key => $title)
			echo '<td>' .nformat($content[$key]). '</td>';
			
		echo '</tr>'."\n";
	}
?>
</table>
<script src="//tools.wmflabs.org/quentinv57-tools/tools/sortable.js" type="text/javascript"></script>
<?php
// stats
logging();
}

?></div></div>
<?php include ('../include/tools_menu.html'); ?>
</body></html>

