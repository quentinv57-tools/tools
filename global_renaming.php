<?php

/*   ---------------------------------------------

Author : Quentinv57

Licence : GNU General Public License v3
			(see //www.gnu.org/licenses/)
			
Date of creation : 2011-08
Last modified : 2012-02-13

Global Renaming TS tool
			
---------------------------------------------   */

#ob_gzhandler();

// Functions
include ('../include/functions.fct.php');


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "//www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="//www.w3.org/1999/xhtml" lang="en-US" xml:lang="en-US">
<head><title>Quentinv57's Tools! - Global Renaming Tool</title><style type="text/css">
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
width:230px;
float:left;
}

.lbl2 {
display:block;
width:400px;
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
<h1>Global Renaming Tool</h1>
<br />
<?php

// firstly : displays the form  -----------------------------------------------------------------------------------------
if (empty($_GET['username']) || !isset($_GET['timetobeinactive']) || !isset($_GET['seuilcontribs']) || empty($_GET['newusername']))
{
?><form method="get" action="global_renaming.php" id="mw-localstewardlog-form1">
<fieldset><legend>Global Renaming Tool</legend>
<p><label class="lbl1" for="username">Username of the account to rename :</label>&nbsp;<input id="username" name="username" type="text" /></p>
<p><label class="lbl1" for="newusername">New username :</label>&nbsp;<input id="newusername" name="newusername" type="text" /></p>
<p><label class="lbl1" for="reason">Reason of renaming :</label>&nbsp;<input id="reason" name="reason" type="text" /></p><br />
<p><label class="lbl2" for="timetobeinactive">The time since a local 'crat is considered inactive (in months) :</label>&nbsp;<input id="timetobeinactive" name="timetobeinactive" type="text" /></p>
<p><label class="lbl2" for="seuilcontribs">The minimum of contributions the accounts must have to be renamed :</label>&nbsp;<input id="seuilcontribs" name="seuilcontribs" type="text" /></p>
<input type="submit" value="Go !" /></fieldset>
</form>
<?php
}

// secondly : displays the results  -----------------------------------------------------------------------------------------
else
{
	// getting a lits of wikis from the database (only non-locked)
	$toolserver_mycnf = parse_ini_file("/data/project/quentinv57-tools/replica.my.cnf");
	$mysql = new MySQLi ('enwiki.labsdb',$toolserver_mycnf['user'], $toolserver_mycnf['password'], 'meta_p');
	unset($toolserver_mycnf);
	
	
	$res = $mysql->query ("SELECT `dbname`,`slice`,`url` FROM `wiki` WHERE `is_closed`=0 ORDER BY `slice`"); 
	$arr_databases = array();
	while ($line = $res->fetch_assoc()) { $arr_databases[$line['slice']][$line['dbname']] = $line['url']; }
	$res->free();
	
	// taking GET vars
	$username = $mysql->real_escape_string($_GET['username']);
	$timetobeinactive = intval($_GET['timetobeinactive']);
	$seuilcontribs = intval($_GET['seuilcontribs']);
	$newusername = $mysql->real_escape_string($_GET['newusername']);
	$reason = $mysql->real_escape_string($_GET['reason']);
	
	// projects with active, inactive and no crats will be saved resp. on the $activecrats array, $inactivecrats str and $nocrats array
	$activecrats = array();
	$inactivecrats = "";
	$nocrats = array();
	
	
	// first loop, for each SQL server
	foreach ($arr_databases as $sql_server => $content)
	{
		// re-opening database connection everytime host is changing
		$mysql->close();
		$toolserver_mycnf = parse_ini_file("/data/project/quentinv57-tools/replica.my.cnf");
		$mysql = new MySQLi ($sql_server, $toolserver_mycnf['user'], $toolserver_mycnf['password']); // or echo "<p>SQL error : Can't connect to $sql_server</p>";
		unset($toolserver_mycnf);
	
		// second loop, for each database (= each wiki)
		foreach ($content as $db => $domain)
		{ 
			$project = substr($db,0,-2);
			$project_url = '//' .$domain; // much better :)
			
			$mysql->select_db($db);
			$res = $mysql->query ("SELECT `user_editcount` FROM `user` WHERE `user_name`='" .$username. "'");
			$req = $res->fetch_assoc();
			$res->free();
			
			// if the accounts exists locally (and the editcount overrates the limit imposed), we will look if there are some active crats
			// adding the non-empty (!=NULL) not to display wikis where the account does not exist (requested by Fr33kman)
			if (($req['user_editcount'] != NULL) AND ($req['user_editcount'] >= $seuilcontribs))
			{
				if ($res = $mysql->query ("SELECT `user_name`, max(day) as last_contrib FROM `user` LEFT JOIN `user_groups` ON `user_groups`.`ug_user`=`user`.`user_id` LEFT JOIN `user_daily_contribs` ON `user_daily_contribs`.`user_id`=`user`.`user_id` WHERE `ug_group`='bureaucrat' GROUP BY `user_name`"))
				{ // adding the (if) statement to be sure that the request was working well
					if ($res->num_rows==0) // if no rows, the project has no bureaucrats
						$nocrats[$project] = $domain;
						
					else {
						$inactive = TRUE;
						$inactiveusers = array();
						// else, doing a loop on crats to search if they are or not active ON THIS PROJECT (depending on the inactivity settings given)
						while ($inactive && $line = $res->fetch_assoc()) {
							if (!is_inactive($line['last_contrib'],$timetobeinactive))
								$inactive = FALSE;
								
							else $inactiveusers[] = $line['user_name'];
						}
						
						if ($inactive)
						{
							// contrary to other vars, it is more easy there to store the message directly than to store into an array
							$inactivecrats .= "<li>" .$project. " (<a href=\"" .$project_url. "/w/index.php?title=Special:RenameUser&oldusername=" .urlencode($username). "&newusername=" .urlencode($newusername). "&reason=" .urlencode($reason). "\">rename user</a> ; <a href=\"//toolserver.org/~pathoschild/stewardry/?wiki=" .$project. "\">stewardry</a> ; crossactivity of";
							
							$i=0;
							foreach ($inactiveusers as $inu) { $i++;
								if ($i!=1) {
									if ($i!=count($inactiveusers)) $inactivecrats .= ",";
									else $inactivecrats .= " and";
								}
								$inactivecrats .= " <a href=\"//toolserver.org/~pathoschild/crossactivity/?user=" .urlencode($inu). "\">" .$inu. "</a>";
							}
							
							$inactivecrats .= ")</li>\n";
						}
							
						/*else
							$activecrats[$project] = $domain; */ // only if we want to display projects with active 'crats
					}
					
					$res->free();
				}
			}
		}
	}
	
	$mysql->close();
	
// now, displaying the results
?><h4>Projects with no bureaucrats</h4>
<ul>
<?php
	
	foreach ($nocrats as $project => $domain) {
		$project_url = '//' .$domain; // much better :)
		
		echo "<li>" .$project. " (<a href=\"" .$project_url. "/w/index.php?title=Special:RenameUser&oldusername=" .urlencode($username). "&newusername=" .urlencode($newusername). "&reason=" .urlencode($reason). "\">rename user</a> ; <a href=\"//toolserver.org/~pathoschild/stewardry/?wiki=" .$project. "\">stewardry</a>)</li>\n";
	}
?></ul>

<h4>Projects where bureaucrats are inactive</h4>
<ul>
<?php echo $inactivecrats; // not really the more beautiful way, but easiser and probably faster
?></ul>

<?php
// just displays a short message for active crats projets (as the tool has been written for a steward purpose)
// but if necessary, projects with active bureaucrats can be taken on the $activecrats array
?><h4>Projects where at least one bureaucrat is active</h4>
<p>If you want to request the renaming localy, the meta page <a href="//meta.wikimedia.org/wiki/Index_of_pages_where_renaming_can_be_requested">Index of pages where renaming can be requested</a> will help
you. If there is no link to the project on this page, you should try to contact the community <a href="//meta.wikimedia.org/wiki/International_names_for_Village_Pump">on their village pump</a>.
<?php

// stats
logging();
}

?></div></div>
<?php include ('../include/tools_menu.html'); ?>
</body></html>

