<?php
ini_set("display_errors", 1);
/*   ---------------------------------------------

Author : Quentinv57

Licence : GNU General Public License v3
			(see //www.gnu.org/licenses/)
			
Date of creation : 2011-08
Last modified : 2016-11-20 (by Alex Monk as a tools admin for https://phabricator.wikimedia.org/T67226, previously: 2011-08-27)

Local Steward Log TS tool
			
---------------------------------------------   */

#ob_gzhandler();
$time = microtime( 1 );
include ('../include/functions.fct.php');


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "//www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="//www.w3.org/1999/xhtml" lang="en-US" xml:lang="en-US">
<head><title>Quentinv57's Tools! - Local Steward Log</title><style type="text/css">
<!--/* <![CDATA[ */
@import "//tools.wmflabs.org/quentinv57-tools/main.css"; 
@import "//fr.wikipedia.org/w/index.php?title=MediaWiki:Monobook.css&usemsgcache=yes&action=raw&ctype=text/css&smaxage=2678400";
@import "//fr.wikipedia.org/w/index.php?title=MediaWiki:Common.css&usemsgcache=yes&action=raw&ctype=text/css&smaxage=2678400";
@import "//fr.wikipedia.org/w/index.php?title=-&action=raw&gen=css&maxage=2678400&smaxage=0&ts=20061201185052"; 
/* ]]> */-->
td { vertical-align: top; }
.external, .external:visited { color: #AAAAAA; }
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
<h1>Local Steward Log</h1>
<br />
<?php

// Open connexion to SQL database
$toolserver_mycnf = parse_ini_file("/data/project/quentinv57-tools/replica.my.cnf");
$mysql = new MySQLi ('enwiki.labsdb',$toolserver_mycnf['user'], $toolserver_mycnf['password'], 'meta_p');
unset($toolserver_mycnf);

// getting a list of wikis (non-locked only) from the database
$res = $mysql->query ("SELECT `dbname`,`url` FROM `wiki` WHERE `is_closed`=0");
while ($line = $res->fetch_assoc()) { $arr_databases[$line['dbname']] = $line['url']; }
$res->free();
$mysql->close();

// firstly : displays the form  -----------------------------------------------------------------------------------------
if (empty($_GET['wiki']) || !array_key_exists($_GET['wiki'], $arr_databases))
{
?><form method="get" action="localstewardlog.php" id="mw-localstewardlog-form1">
<fieldset><legend>Local Steward Log</legend>
<p><label for="wiki">Please select your wiki :</label>&nbsp;<select id="wiki" name="wiki"><?php
	
	foreach ($arr_databases as $db => $domain)
	{
		echo '<option value="'.$db.'">'.$db.'</option>';
	}
	
?></select></p>
<input type="submit" value="Go !" /></fieldset>
</form>
<?php
}

// secondly : displays the results  -----------------------------------------------------------------------------------------
elseif (array_key_exists($_GET['wiki'], $arr_databases))
{
	$wiki = $_GET['wiki']; // no security issue, as the var is in $arr_databases
	$wiki_url = $arr_databases[$wiki];
	$limit = (!empty($_GET['limit'])) ? intval($_GET['limit']) : '50'; // 50 is the limit by default
	if ($limit>100000) $limit = 100000; // limit can't be over 100000
	$offset = (!empty($_GET['offset'])) ? intval($_GET['offset']) : 0;
	
	// re-opening connection to metawiki_p database
	$toolserver_mycnf = parse_ini_file("/data/project/quentinv57-tools/replica.my.cnf");
	$mysql = new MySQLi ('metawiki.labsdb',$toolserver_mycnf['user'], $toolserver_mycnf['password'], 'metawiki_p');
	unset($toolserver_mycnf);
	
	// getting the meta steward log from the database
	$res = $mysql->query ("SELECT `user`.`user_name`, `logging_userindex`.`log_params`, `logging_userindex`.`log_title`, `logging_userindex`.`log_timestamp`, `logging_userindex`.`log_comment` FROM `logging_userindex` LEFT JOIN `user` ON `user`.`user_id` = `logging_userindex`.`log_user` WHERE `logging_userindex`.`log_type`='rights' AND `logging_userindex`.`log_action`='rights' AND `logging_userindex`.`log_title` LIKE '%@$wiki' ORDER BY `logging_userindex`.`log_id` DESC");

?><fieldset><legend>Local Steward Log</legend>
(<?php echo (empty($offset)) ? 'Latest' : '<a href="localstewardlog.php?wiki='.$wiki.'&limit='.$limit.'&offset=">Latest</a>'; ?> | <?php echo (($offset+$limit)>=$res->num_rows) ? 'Earliest' : '<a href="localstewardlog.php?wiki='.$wiki.'&limit='.$limit.'&offset='.intval($res->num_rows-$limit).'">Earliest</a>'; ?>) View (<?php echo (empty($offset)) ? 'newer '.$limit : '<a href="localstewardlog.php?wiki='.$wiki.'&limit='.$limit.'&offset='.intval($offset-$limit).'">newer '.$limit.'</a>'; ?> | <?php echo (($offset+$limit)>=$res->num_rows) ? 'older '.$limit : '<a href="localstewardlog.php?wiki='.$wiki.'&limit='.$limit.'&offset='.intval($offset+$limit).'">older '.$limit.'</a>'; ?>) (<a href="localstewardlog.php?wiki=<?php echo $wiki; ?>&offset=<?php echo $offset; ?>&limit=20">20</a> | <a href="localstewardlog.php?wiki=<?php echo $wiki; ?>&offset=<?php echo $offset; ?>&limit=50">50</a> | <a href="localstewardlog.php?wiki=<?php echo $wiki; ?>&offset=<?php echo $offset; ?>&limit=100">100</a> | <a href="localstewardlog.php?wiki=<?php echo $wiki; ?>&offset=<?php echo $offset; ?>&limit=250">250</a> | <a href="localstewardlog.php?wiki=<?php echo $wiki; ?>&offset=<?php echo $offset; ?>&limit=500">500</a>)
<ul>
<?php
	$i=0;
	while ($line = $res->fetch_assoc())
	{
		$i++; // here is the counter
		if ($i>($offset+$limit)) {
			break;
		}
		
		// only began the display at the offset
		if ($i>=$offset) {
			// getting data
			$date_str = display_time(wfTimestamp($line['log_timestamp']));
			
			$steward = '<a href="//meta.wikimedia.org/wiki/User:'.htmlspecialchars($line['user_name']).'" class="mw-userlink">'.htmlspecialchars($line['user_name']).'</a>';
			
			$user = explode('@', $line['log_title']); $user = $user[0];
			$userstr = '<a href="'.$wiki_url.'/wiki/User:'.htmlspecialchars($user).'" class="mw-userlink">'.htmlspecialchars($user).'@'.$wiki.'</a>';
			ini_set("display_errors", 0);
			$group = unserialize( $line['log_params'] ) ;
			ini_set("display_errors", 1);
			if( $group === false ) {
				$group = explode( "\n", $line['log_params'] );
				$groupfrom = (!empty($group[0])) ? $group[0] : '(none)';
				$groupto = (!empty($group[1])) ? $group[1] : '(none)';
				$groupstr = (!empty($group[0]) || !empty($group[1])) ? "from $groupfrom to $groupto " : '';
			} else {
				$groupfrom = (!empty($group['4::oldgroups'])) ? implode( ', ', $group['4::oldgroups'] ) : '(none)';
				$groupto = (!empty($group['5::newgroups'])) ? implode( ', ', $group['5::newgroups'] ) : '(none)';
				$groupstr = (!empty($group['4::oldgroups']) || !empty($group['5::newgroups'])) ? "from $groupfrom to $groupto " : '';
			}
			$commentstr = comment_linkify($line['log_comment']);
			// don't forger to escape HTML specialchars
			
			// displaying the result
			echo "<li>$date_str $steward changed group membership for $userstr $groupstr $commentstr</li>";
		}
	}
?></ul>
(<?php echo (empty($offset)) ? 'Latest' : '<a href="localstewardlog.php?wiki='.$wiki.'&limit='.$limit.'&offset=">Latest</a>'; ?> | <?php echo (($offset+$limit)>=$res->num_rows) ? 'Earliest' : '<a href="localstewardlog.php?wiki='.$wiki.'&limit='.$limit.'&offset='.intval($res->num_rows-$limit).'">Earliest</a>'; ?>) View (<?php echo (empty($offset)) ? 'newer '.$limit : '<a href="localstewardlog.php?wiki='.$wiki.'&limit='.$limit.'&offset='.intval($offset-$limit).'">newer '.$limit.'</a>'; ?> | <?php echo (($offset+$limit)>=$res->num_rows) ? 'older '.$limit : '<a href="localstewardlog.php?wiki='.$wiki.'&limit='.$limit.'&offset='.intval($offset+$limit).'">older '.$limit.'</a>'; ?>) (<a href="localstewardlog.php?wiki=<?php echo $wiki; ?>&offset=<?php echo $offset; ?>&limit=20">20</a> | <a href="localstewardlog.php?wiki=<?php echo $wiki; ?>&offset=<?php echo $offset; ?>&limit=50">50</a> | <a href="localstewardlog.php?wiki=<?php echo $wiki; ?>&offset=<?php echo $offset; ?>&limit=100">100</a> | <a href="localstewardlog.php?wiki=<?php echo $wiki; ?>&offset=<?php echo $offset; ?>&limit=250">250</a> | <a href="localstewardlog.php?wiki=<?php echo $wiki; ?>&offset=<?php echo $offset; ?>&limit=500">500</a>)
</fieldset>
<?php
	
	$res->free();
	$mysql->close();
	
	logging();
}

?></div></div>
<?php include ('../include/tools_menu.html'); ?>
Executed in <?php echo round( microtime( 1 )-$time, 2 );?> seconds.
</body></html>

