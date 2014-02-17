<?php

/*   ---------------------------------------------

Author : Quentinv57

Licence : GNU General Public License v3
			(see //www.gnu.org/licenses/)
			
Date of creation : 2011-08
Last modified : 2011-08-27

BotHelper TS tool
			
---------------------------------------------   */

#ob_gzhandler();

include ('../include/functions.fct.php');

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "//www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="//www.w3.org/1999/xhtml" lang="en-US" xml:lang="en-US">
<head><title>Quentinv57's Tools! - Bot Helper</title><style type="text/css">
<!--/* <![CDATA[ */
@import "//tools.wmflabs.org/quentinv57-tools/main.css"; 
@import "//fr.wikipedia.org/w/index.php?title=MediaWiki:Monobook.css&usemsgcache=yes&action=raw&ctype=text/css&smaxage=2678400";
@import "//fr.wikipedia.org/w/index.php?title=MediaWiki:Common.css&usemsgcache=yes&action=raw&ctype=text/css&smaxage=2678400";
@import "//fr.wikipedia.org/w/index.php?title=-&action=raw&gen=css&maxage=2678400&smaxage=0&ts=20061201185052"; 
/* ]]> */-->
td { vertical-align: top; }
.external, .external:visited { color: black; }

.lbl1 {
display:block;
width:140px;
float:left;
}
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
<h1>Bot Helper</h1>
<br />
<?php

// Time limit to prevent very long regexes
set_time_limit(60);

// Open connexion to database
$toolserver_mycnf = parse_ini_file("/data/project/quentinv57-tools/replica.my.cnf");
$mysql = new MySQLi ('enwiki.labsdb',$toolserver_mycnf['user'], $toolserver_mycnf['password'], 'meta_p');
unset($toolserver_mycnf);

// List of non-closed wikis
$res = $mysql->query ("SELECT `dbname`,`url` FROM `wiki` WHERE `is_closed`=0");
while ($line = $res->fetch_assoc()) $arr_databases[substr($line['dbname'],0,-2)] = $line['url'];
$res->free();
$mysql->close();

// First part : displays the form  -----------------------------------------------------------------------------
if (empty($_GET['wiki']) || !array_key_exists($_GET['wiki'], $arr_databases) || empty($_GET['botname'])|| empty($_GET['match']))
{
?><p>This tool should help bot owners to get a list of every contributions of their bot matching a regex.</p>
<form method="get" action="bothelper.php" id="mw-bothelper-form1">
<fieldset><legend>Bot Helper</legend>
<p><label class="lbl1" for="wiki">Please select your wiki :</label>&nbsp;<select id="wiki" name="wiki"><?php
	
	foreach ($arr_databases as $db => $domain)
	{
		echo '<option value="'.$db.'">'.$db.'</option>';
	}
	
?></select></p>
<p><label class="lbl1" for="botname">Name of your bot :</label>&nbsp;<input type="text" id="botname" name="botname" /></p>
<p><label for="match">Regex that the summary should match (without delimiter) :</label>&nbsp;<input type="text" id="match" name="match" /></p>
<!-- case insentive ? -->
<input type="submit" value="Go !" /></fieldset>
</form>
<?php
}

// Second part : display the results  -----------------------------------------------------------------------------
elseif (array_key_exists($_GET['wiki'], $arr_databases))
{
	// $_GET data (part 1)
	$wiki = $_GET['wiki']; // does not need to be escaped as it is part of $arr_databases array
	$wiki_url = '//'.$arr_databases[$wiki];
	
	// Connexion to database
	$toolserver_mycnf = parse_ini_file("/data/project/quentinv57-tools/replica.my.cnf");
	$mysql = new MySQLi ($wiki.'.labsdb',$toolserver_mycnf['user'], $toolserver_mycnf['password'], $wiki.'_p');
	unset($toolserver_mycnf);
	
	// $_GET data (part 2)
	$botname = $mysql->real_escape_string($_GET['botname']);
	$match = $_GET['match'];
	
	// get revisions from database on the specified wiki by the specified user/bot
	$res = $mysql->query ("SELECT `rev_id`,`rev_comment`,`rev_timestamp`,`page_title`,`page_latest` FROM `revision_userindex` LEFT JOIN `page` ON `page`.`page_id`=`revision`.`rev_page` WHERE `rev_user_text`='$botname' AND `rev_deleted`=0 ORDER BY `rev_id` DESC");
	
	// enter the while (on database results) :
		// $count is the number of matching items
		// $text is the finally displayed text
	$count=0; $text="<ul>\n";
	while ($reb = $res->fetch_assoc()) {
		if (preg_match("#".str_replace('#','\#',$match)."#",$reb['rev_comment'])) {
			$count++;
			$mktime=$reb['rev_timestamp'];
			$mktime = mktime(substr($mktime,8,2), substr($mktime,10,2), substr($mktime,12,2), substr($mktime,4,2), substr($mktime,6,2), substr($mktime,0,4));
			$last = ($reb['rev_id']==$reb['page_latest']) ? "<strong>(top)</strong>" : "";
			
			$text .= "<li>".date('Y-m-d H:i:s',$mktime)." : <a href=\"$wiki_url/w/index.php?oldid=".$reb['rev_id']."&diff=prev\">".$reb['page_title']."</a> $last <em>(".$reb['rev_comment'].")</em></li>\n";
		}
	}
	$text.="</ul>";
	
	// display the final text
	echo $count . " revisions matched :\n" . $text;
	
	logging();
}

?></div></div>
<?php include ('../include/tools_menu.html'); ?>
</body></html>

