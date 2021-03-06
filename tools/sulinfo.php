<?php
ini_set("display_errors", 1);

/*   ---------------------------------------------

Author : Quentinv57

Licence : GNU General Public License v3
			(see //www.gnu.org/licenses/)
			
Date of creation : 2011-08
Last modified : 2016-11-20 (by Alex Monk as tools admin, previous: 2012-01-26)

SULinfo TS tool
			
---------------------------------------------   */

#ob_gzhandler();

// Functions
$time = microtime( 1 );
include ('../include/functions.fct.php');

define ('LOCKEDWIKI_TEXT', '<small style="color:#555555">(closed wiki)</style>');



// First of all, if preferences have been changed, we edit them (Cookies) - requested by xeno (en)
if (!empty($_POST['pref-showinactivity']) AND !empty($_POST['pref-showblocks']) AND !empty($_POST['pref-showlocked']))
{
	// setting / updating cookies
	setcookie('sulinfo-pref-showinactivity', $_POST['pref-showinactivity'], time() + 365*24*3600, '/~quentinv57/');
	setcookie('sulinfo-pref-showblocks', $_POST['pref-showblocks'], time() + 365*24*3600, '/~quentinv57/');
	setcookie('sulinfo-pref-showlocked', $_POST['pref-showlocked'], time() + 365*24*3600, '/~quentinv57/');
	
	// setting COOKIE vars, so the current page will display the current COOKIES settings (that have just been defined)
	$_COOKIE['sulinfo-pref-showinactivity'] = $_POST['pref-showinactivity'];
	$_COOKIE['sulinfo-pref-showblocks'] = $_POST['pref-showblocks'];
	$_COOKIE['sulinfo-pref-showlocked'] = $_POST['pref-showlocked'];
	
	logging(); // storing POST data for debug purposes
}


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "//www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="//www.w3.org/1999/xhtml" lang="en-US" xml:lang="en-US">
<head><title>Quentinv57's Tools! - SUL Info</title><style type="text/css">
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
<h1>SUL Info</h1>
<br />
<?php

// First part : displays the form --------------------------------------------------------------------------------------------------------------------------------
if (empty($_GET['username']))
{
?><p>This script gives a list of every account using a specified name on WMF wikis, and displays SUL data if accounts have been merged.</p>
<p>If the tool doesn't work, you can use <a href="//meta.wikimedia.org/wiki/Special:CentralAuth">CentralAuth</a> <small><a href="//meta.wikimedia.org/wiki/Special:CentralAuth">(secure)</a></small> (works for unified accounts only).</p>
<p>Thanks to <a href="//meta.wikimedia.org/wiki/User:VasilievVV">VVV</a> for his help, and to any other bug reporter.</p><br /><br />
<fieldset><legend>SUL Info</legend>
<form method="get" action="//tools.wmflabs.org/quentinv57-tools/tools/sulinfo.php" id="mw-sulinfo-form1">
<table border="0" id="mw-movepage-table"> 
<tr><td class='mw-label'><label for="username">Username :</label></td><td class='mw-input'><input id="username" name="username" type="text" /></td></tr><?php
	if (empty($_COOKIE['sulinfo-pref-showinactivity']) || $_COOKIE['sulinfo-pref-showinactivity']=='ask') echo '<tr><td>&#160;</td><td class="mw-input"><input id="showinactivity" name="showinactivity" type="checkbox" value="1" />&#160;<label for="showinactivity">Display inactivity</label></tr></td>'."\n";
	if (empty($_COOKIE['sulinfo-pref-showblocks']) || $_COOKIE['sulinfo-pref-showblocks']=='ask') echo '<tr><td>&#160;</td><td class="mw-input"><input id="showblocks" name="showblocks" type="checkbox" value="1" />&#160;<label for="showblocks">Display blocks</label></td></tr>'."\n";
	if (empty($_COOKIE['sulinfo-pref-showlocked']) || $_COOKIE['sulinfo-pref-showlocked']=='ask') echo '<tr><td>&#160;</td><td class="mw-input"><input id="showlocked" name="showlocked" type="checkbox" value="1" />&#160;<label for="showlocked">Show locked/closed wikis</label></td></tr>'."\n";
?>
<tr><td>&#160;</td><td class='mw-submit'><input type="submit" value="Go !" /></td></tr>
</table>
</form></fieldset>

<fieldset><legend>Preferences</legend>
<form method="post" action="//tools.wmflabs.org/quentinv57-tools/tools/sulinfo.php" id="mw-sulinfo-form2">
<p>To set your preferences, you must have cookies enabled.</p>
<table border="0" id="mw-movepage-table">
<tr><td class='mw-label'><label>Display inactivity :</label></td>
	<td class='mw-input'><input type="radio" name="pref-showinactivity" value="ask" <?php if (empty($_COOKIE['sulinfo-pref-showinactivity']) || $_COOKIE['sulinfo-pref-showinactivity']=='ask') echo 'checked="checked"'; ?>/>Still ask&#160;&#160;
						 <input type="radio" name="pref-showinactivity" value="always" <?php if (isset($_COOKIE['sulinfo-pref-showinactivity']) && $_COOKIE['sulinfo-pref-showinactivity']=='always') echo 'checked="checked"'; ?>/>Always&#160;&#160;
						 <input type="radio" name="pref-showinactivity" value="never" <?php if (isset($_COOKIE['sulinfo-pref-showinactivity']) && $_COOKIE['sulinfo-pref-showinactivity']=='never') echo 'checked="checked"'; ?>/>Never</td></tr>
<tr><td class='mw-label'><label>Display blocks :</label></td>
	<td class='mw-input'><input type="radio" name="pref-showblocks" value="ask" <?php if (empty($_COOKIE['sulinfo-pref-showblocks']) || $_COOKIE['sulinfo-pref-showblocks']=='ask') echo 'checked="checked"'; ?>/>Still ask&#160;&#160;
						 <input type="radio" name="pref-showblocks" value="always" <?php if (isset($_COOKIE['sulinfo-pref-showblocks']) && $_COOKIE['sulinfo-pref-showblocks']=='always') echo 'checked="checked"'; ?>/>Always&#160;&#160;
						 <input type="radio" name="pref-showblocks" value="never" <?php if (isset($_COOKIE['sulinfo-pref-showblocks']) && $_COOKIE['sulinfo-pref-showblocks']=='never') echo 'checked="checked"'; ?>/>Never</td></tr>
<tr><td class='mw-label'><label>Show locked/closed wikis :</label></td>
	<td class='mw-input'><input type="radio" name="pref-showlocked" value="ask" <?php if (empty($_COOKIE['sulinfo-pref-showlocked']) || $_COOKIE['sulinfo-pref-showlocked']=='ask') echo 'checked="checked"'; ?>/>Still ask&#160;&#160;
						 <input type="radio" name="pref-showlocked" value="always" <?php if (isset($_COOKIE['sulinfo-pref-showlocked']) && $_COOKIE['sulinfo-pref-showlocked']=='always') echo 'checked="checked"'; ?>/>Always&#160;&#160;
						 <input type="radio" name="pref-showlocked" value="never" <?php if (isset($_COOKIE['sulinfo-pref-showlocked']) && $_COOKIE['sulinfo-pref-showlocked']=='never') echo 'checked="checked"'; ?>/>Never</td></tr>
<tr><td>&#160;</td><td class='mw-submit'><input type="submit" value="Change preferences" /></td></tr>
</table>
</form></fieldset>
<?php
}

// Second part : displays the results --------------------------------------------------------------------------------------------------------------------------------
else
{
	// init the $sulinfo array (will contain data for the global account)
	$sulinfo = array ( 	'id' => 0,
						'locked' => 0,
						'hidden' => 0,
						'status' => NULL,
						'totaleditcount' => 0,
						'registered' => NULL,
						'lastactive' => NULL );
	
	// connection do the database
	$toolserver_mycnf = parse_ini_file("/data/project/quentinv57-tools/replica.my.cnf");
	$mysql = new MySQLi ('enwiki.labsdb',$toolserver_mycnf['user'], $toolserver_mycnf['password'], 'meta_p');
	unset($toolserver_mycnf);
	
	# PART 2.1 -
	# Taking the list of wikis from the database
	# puis editcount, activité et statuts sur chaque wiki
	
	// GET params
	$username = str_replace('_',' ',utf8_ucfirst($mysql->real_escape_string($_GET['username'])));
	// using str_replace because the chars '_' and ' ' are the same to MediaWiki (requested by Ash_Crow)
	// using ucfirst() because the first letter is always a capital (requested by pmartin)
	// previous command replaced by utf8_ucfirst(), to fix compatibility issue with CJK characters (requested by Kanjy)
	
	// setting the $showinactivity parameter, taking cookies and GET params into account
	if (empty($_COOKIE['sulinfo-pref-showinactivity']) || $_COOKIE['sulinfo-pref-showinactivity']=="ask")
		$showinactivity = (!empty($_GET['showinactivity'])) ? TRUE : FALSE;
	elseif ($_COOKIE['sulinfo-pref-showinactivity']=="never")
		$showinactivity = 0;
	elseif ($_COOKIE['sulinfo-pref-showinactivity']=="always")
		$showinactivity = 1;
	else exit('Error with the preference feature. Please report this bug and the URL on [[meta:User talk:Quentinv57]]. Thanks !'); // assert
	
	// same with $showblocks
	if (empty($_COOKIE['sulinfo-pref-showblocks']) || $_COOKIE['sulinfo-pref-showblocks']=="ask")
		$showblocks = (!empty($_GET['showblocks'])) ? TRUE : FALSE;
	elseif ($_COOKIE['sulinfo-pref-showblocks']=="never")
		$showblocks = 0;
	elseif ($_COOKIE['sulinfo-pref-showblocks']=="always")
		$showblocks = 1;
	else exit('Error with the preference feature. Please report this bug and the URL on [[meta:User talk:Quentinv57]]. Thanks !'); // assert
	
	// same with $hidelocked
	if (empty($_COOKIE['sulinfo-pref-showlocked']) || $_COOKIE['sulinfo-pref-showlocked']=="ask")
		$hidelocked = (!empty($_GET['showlocked'])) ? FALSE : TRUE;
	elseif ($_COOKIE['sulinfo-pref-showlocked']=="never")
		$hidelocked = 1;
	elseif ($_COOKIE['sulinfo-pref-showlocked']=="always")
		$hidelocked = 0;
	else exit('Error with the preference feature. Please report this bug and the URL on [[meta:User talk:Quentinv57]]. Thanks !'); // assert
	
	// query the database that returns the list of the wikis (nonlocked only or everything, depending of $hidelocked var)
	$query = "SELECT `dbname`,`slice`,`url` FROM `wiki` ORDER BY `slice`";
	if ($hidelocked) $query = "SELECT `dbname`,`slice`,`url` FROM `wiki` WHERE `is_closed`=0 ORDER BY `slice`";
	$res = $mysql->query ($query); 
	
	// while loop on database result to get $arr_databases array
	$arr_databases = array();
	while ($line = $res->fetch_assoc()) {
		$arr_databases[$line['slice']][$line['dbname']] = $line['url'];
	}
	$res->free();
	
	// $xwiki will contain user data for each project
	$xwiki = array();
	$unattached_accounts = array();
	
	// first loop, for each SQL server
	foreach ($arr_databases as $sql_server => $content)
	{
		// re-opening database connection everytime host is changing
		$mysql->close();
		$toolserver_mycnf = parse_ini_file("/data/project/quentinv57-tools/replica.my.cnf");
		$mysql = new MySQLi ($sql_server, $toolserver_mycnf['user'], $toolserver_mycnf['password']); // or echo "<p>SQL error : Can't connect to $sql_server</p>";
		unset($toolserver_mycnf);
		
		//The new more efficient query method.  Let's ping all the databases in a cluster at once.
		//We'll need to generate a query string first.
		$i = 0;
		foreach ($content as $db => $domain)
		{
			if( $i == 0 ) $querystring1 = "";
			if( $i == 0 ) $querystring2 = "";
			if( $i == 0 ) $querystring3 = "";
			if( $db != "centralauth" ) $querystring1 .= "(SELECT `user_id`, `user_registration`,`user_editcount`, `ug_group`, `dbname` FROM `$db"."_p`.`user` LEFT JOIN `$db"."_p`.`user_groups` ON `$db"."_p`.`user_groups`.`ug_user`=`$db"."_p`.`user`.`user_id` LEFT JOIN `meta_p`.`wiki` ON `meta_p`.`wiki`.`dbname`='$db' WHERE `user_name`='" .$username. "' GROUP BY `ug_group`)";
			else {$i++; continue;}
			if( $db != "centralauth" ) $querystring2 .= "(SELECT `ipb_reason`,`ipb_expiry`,`ipb_by`, `dbname` FROM `$db"."_p`.`ipblocks_ipindex` LEFT JOIN `meta_p`.`wiki` ON `meta_p`.`wiki`.`dbname`='$db' WHERE `ipb_address`='" .$username. "')";
			$i++;
			if( $i != count( $content ) ) $querystring1 .= " UNION ";
			if( $i != count( $content ) ) $querystring2 .= " UNION ";
		}
		
		//Make the query, if it fails, fallback to the old design
		if( $result1 = $mysql->query( $querystring1 ) )
		{
			$groupresults = array();
			$blockresults = array();
			$contribsresults = array();
			while( $req = $result1->fetch_assoc() ) {
				$groupresults[$req['dbname']][] = $req;
			}
			$result1->free();
			if( $showblocks && $result1 = $mysql->query( $querystring2 ) ) {
				while( $req = $result1->fetch_assoc() ) {
					$blockresults[$req['dbname']] = $req;
				}
			}

			foreach( $groupresults as $project=>$req ) {
				
				$domain = $content[$project];
				//$project.="test";
				$xwiki[$project] = array(	'id' => $req[0]['user_id'],
											'registered' => $req[0]['user_registration'],
											'editcount' => $req[0]['user_editcount'],
											'url' => $domain );
				
				
				// Upgrading global vars stats (total editcount and first registration) :
				$sulinfo['totaleditcount'] += $req[0]['user_editcount'];
				// 20120221 - registration is re-updated below to fix some bug
				if (empty($req['user_registration']))
					$sulinfo['registered'] = -1; // on met cette variable à -1 si la date d'enregistrement est trop vieille pour apparaître dans la base de données
				elseif ((empty($sulinfo['registered']) || wfTimestamp($req['user_registration'])<$sulinfo['registered']) && $sulinfo['registered']!=-1)
					$sulinfo['registered'] = wfTimestamp($req['user_registration']);
				
				// Adding other status
				foreach( $req as $req2 ) {
					$xwiki[$project]['status'][] = $req2['ug_group'];
				}
				$xwiki[$project]['status'] = implode( ", ", $xwiki[$project]['status'] );

				// 2.1.2 - getting blocks
				if ($showblocks)
				{
					$xwiki[$project]['blocked'] = (isset( $blockresults[$project])) ? 1 : 0;
					// if we want a day to specify the block expiry, reason, and sysop it will be here
					// now we display a link to the log, which is to my mind necessary, and does not break the table
					
				}
				
				// 2.1.3 - getting last activity
				if ($showinactivity)
				{
					// depending on the fact that user ID is stored or not, the request can be simplified
					if (!empty($xwiki[$project]['id'])) {
						if( $querystring3 != "" ) $querystring3 .= " UNION ";
						$querystring3 .= "(SELECT max(rev_timestamp) as last_contrib, `dbname` FROM `$project"."_p`.`revision_userindex` LEFT JOIN `meta_p`.`wiki` ON `meta_p`.`wiki`.`dbname`='$project' WHERE `rev_user`='" .$xwiki[$project]['id']. "')";
						unset ($xwiki[$project]['id']); // local ID is no longer needed, we can unset() it to free memory
					}
					else {
						if( $querystring3 != "" ) $querystring3 .= " UNION ";
						$querystring3 .= "(SELECT max(rev_timestamp) as last_contrib, `dbname` FROM `$project"."_p`.`user` LEFT JOIN `$project"."_p`.`revision_userindex` ON `$project"."_p`.`user`.`user_id`=`$project"."_p`.`revision_userindex`.`rev_user` LEFT JOIN `meta_p`.`wiki` ON `meta_p`.`wiki`.`dbname`='$project' WHERE `user_name`='" .$username. "')";
					}
				}
			}
			if ($showinactivity && $querystring3 != "" && $res = $mysql->query ($querystring3))
			{
				
				while($req = $res->fetch_assoc()) {
					if( is_null( $req['dbname'] ) ) continue;
					$xwiki[$req['dbname']]['lastactive'] = $req['last_contrib'];
					
					// Upgrading global vars (last activity) :
					if (empty($sulinfo['lastactive']) || wfTimestamp($req['last_contrib'])>$sulinfo['lastactive'])
							$sulinfo['lastactive'] = wfTimestamp($req['last_contrib']);
				}
			}
		}
		else
		{
			// Fallback to this method, should the UNION query fail.
			// second loop, for each database (= each wiki)
			foreach ($content as $db => $domain)
			{
				$project = $db; // getting the project name from the database name
				
				// only do the following instructions if the database can be selected
				// (else the SQL server is not working, so the loop should borke to switch on the next server)
				if (!$mysql->select_db($db."_p"))
				{
					$_ISSUE['sql_servers'][] = $sql_server;
					break 2; // reporting issue to display a warning message
				}
				else
				{
					// Firstly I was using the following request to do everything :
					// "SELECT `user_registration`,`user_editcount`,max(day) as last_contrib,ug_group,ipb_address=user_name as blocked FROM `user` LEFT JOIN `user_daily_contribs` ON `user`.`user_id`=`user_daily_contribs`.`user_id` LEFT JOIN `user_groups` ON `user_groups`.`ug_user`=`user`.`user_id` LEFT JOIN `ipblocks_ipindex` ON `ipblocks_ipindex`.`ipb_address`=`user`.`user_name` WHERE `user_name`='" .$username. "' GROUP BY `ug_group`"
					// but it's really less time-consuming to use three separate requests, and even to propose to the user to display only what he wants to see
					
					// 2.1.1 - getting registration, editcount and groups from database
					if ($res2 = $mysql->query ("SELECT `user_id`, `user_registration`,`user_editcount`,ug_group FROM `user` LEFT JOIN `user_groups` ON `user_groups`.`ug_user`=`user`.`user_id` WHERE `user_name`='" .$username. "' GROUP BY `ug_group`"))
					{
						// skipping the project if the account does not exist
						if ($res2->num_rows != 0)
						{
							$req = $res2->fetch_assoc();
							
							// if the account is existing, it is added to the $xwiki array
							$xwiki[$project] = array(	'id' => $req['user_id'],
														'registered' => $req['user_registration'],
														'editcount' => $req['user_editcount'],
														'status' => $req['ug_group'],
														'url' => $domain );
														
							// Upgrading global vars stats (total editcount and first registration) :
							$sulinfo['totaleditcount'] += $req['user_editcount'];
							// 20120221 - registration is re-updated below to fix some bug
							if (empty($req['user_registration']))
								$sulinfo['registered'] = -1; // on met cette variable à -1 si la date d'enregistrement est trop vieille pour apparaître dans la base de données
							elseif ((empty($sulinfo['registered']) || wfTimestamp($req['user_registration'])<$sulinfo['registered']) && $sulinfo['registered']!=-1)
								$sulinfo['registered'] = wfTimestamp($req['user_registration']);
							
							// Adding other status
							while ($req = $res2->fetch_assoc()) {
								$xwiki[$project]['status'] .= ', ' . $req['ug_group'];
							}

							// 2.1.2 - getting blocks
							if ($showblocks && $res = $mysql->query ("SELECT `ipb_reason`,`ipb_expiry`,`ipb_by` FROM `ipblocks_ipindex` WHERE `ipb_address`='" .$username. "'"))
							{
								$xwiki[$project]['blocked'] = ($res->num_rows > 0) ? 1 : 0;
								// if we want a day to specify the block expiry, reason, and sysop it will be here
								// now we display a link to the log, which is to my mind necessary, and does not break the table
								
								$res->free();
							}
							
							// 2.1.3 - getting last activity
							if ($showinactivity)
							{
								// depending on the fact that user ID is stored or not, the request can be simplified
								if (!empty($xwiki[$project]['id'])) {
									$query = "SELECT max(day) as last_contrib FROM `user_daily_contribs` WHERE `user_id`='" .$xwiki[$project]['id']. "'";
									unset ($xwiki[$project]['id']); // local ID is no longer needed, we can unset() it to free memory
								}
								else
									$query = "SELECT max(day) as last_contrib FROM `user` LEFT JOIN `user_daily_contribs` ON `user`.`user_id`=`user_daily_contribs`.`user_id` WHERE `user_name`='" .$username. "'";
								
								if ($res = $mysql->query ($query))
								{
									$req = $res->fetch_assoc();
									
									$xwiki[$project]['lastactive'] = $req['last_contrib'];
									
									// Upgrading global vars (last activity) :
									if (empty($sulinfo['lastactive']) || wfTimestamp($req['last_contrib'])>$sulinfo['lastactive'])
											$sulinfo['lastactive'] = wfTimestamp($req['last_contrib']);
								}
							}
						}
						$res2->free();
					}
					// Si on n'a pas réussi à récupérer les données (bug signalé le 2011-11-14) :
					else {
						if( $project != "centralauth" ) $_ISSUE['sql_dbases'][] = $project;
					}
				}
			}
		}
	}
	
	// DAB. said "it will be there as long as the wmf keeps it there"
	// so it is useless to switch to s7 database, it will be done only if an issue occurs
	if (!$mysql->select_db('centralauth_p'))
	{ // however, if an error occurs, an error is displayed instead of the SUL data
		$_ISSUE['sql_centralauth'] = TRUE;
	}
	else
	{
		# PART 2.2 -
		# getting from the database attached accounts with the
		# specified username
		
		$res = $mysql->query("SELECT `lu_wiki` FROM `localuser` WHERE `lu_name`='" .$username. "'");
		
		$attached_accounts = array();
		
		while ($line = $res->fetch_assoc()) {
			$attached_accounts[] = $line['lu_wiki'];
		}
		
		
		# PART 2.3 -
		# getting SUL data
		# plus stats on local accounts
		
		$res = $mysql->query("SELECT `gu_id`,`gu_locked`,`gu_hidden`,`gug_group` FROM `globaluser` LEFT JOIN `global_user_groups` ON `globaluser`.`gu_id`=`global_user_groups`.`gug_user` WHERE `gu_name`='" .$username. "'");
		$req = $res->fetch_assoc();
		
		$sulinfo['id'] = $req['gu_id'];
		$sulinfo['locked'] = $req['gu_locked'];
		$sulinfo['hidden'] = $req['gu_hidden'];
		$sulinfo['status'] = (!empty($req['gug_group'])) ? str_replace('_',' ',$req['gug_group']) : 'none';
							
		while ($req = $res->fetch_assoc()) { // for other groups
			$sulinfo['status'] .= ', ' . str_replace('_',' ',$req['gug_group']);
		}
		
		
		// the script ends : the database can be closed so
		$mysql->close();
		
		
		// 20120221 - registration is re-updated here to fix some bug
		// (already set above but could be wrong if one unattached account is older)
		$sulinfo['registered'] = 0;
		foreach ($xwiki as $proj => $arr) {
		if (in_array($proj, $attached_accounts)) {
			if (empty($arr['registered']))
				$sulinfo['registered'] = -1; // on met cette variable à -1 si la date d'enregistrement est trop vieille pour apparaître dans la base de données
			elseif ((empty($sulinfo['registered']) || wfTimestamp($arr['registered'])<$sulinfo['registered']) && $sulinfo['registered']!=-1)
				$sulinfo['registered'] = wfTimestamp($arr['registered']);
		}}
	}
	
	// now that SQL connection is closed, we can stripslashes as it fixes a display issue with appostrophies (requested by Cantons-de-l'Est)
	$username = htmlspecialchars(stripslashes($username));
	
	
	
	# ------------------------------------------------------------------------------------------------
	#    PART 3 : Displays the results
	# ------------------------------------------------------------------------------------------------
	
	// displaying errors (if some occured)
	if (!empty($_ISSUE['sql_servers']) || !empty($_ISSUE['sql_dbases']))
	{
?><fieldset><legend>SQL Errors</legend>
<?php
		foreach ($_ISSUE['sql_servers'] as $sqlserver)
		{
			$message = '<p><img src="//upload.wikimedia.org/wikipedia/commons/2/22/Exclam_icon.svg" alt="warning" width="20" /> <strong>Warning :</strong> The SQL server s'.$sqlserver.' is down or having issues. Consequently, the following wikis won\'t be displayed : ';
			
			$i=0; // affichage des wikis qui ne seront donc pas affichés. Le $i n'est là que pour la présentation.
			foreach ($arr_databases[$sqlserver] as $db => $dom)
			{ $i++;
				if ($i==count($arr_databases[$sqlserver]) && count($arr_databases[$sqlserver])!=1) $message .= ' and ';
				$message .= substr($db,0,-2);
				if ($i<(count($arr_databases[$sqlserver])-1)) $message .= ', ';
			}
			
			$message .= '.</p>'."\n";
			
			echo $message;
		}
		
		if (!empty($_ISSUE['sql_dbases']))
		{
			$message = '<p><img src="//upload.wikimedia.org/wikipedia/commons/2/22/Exclam_icon.svg" alt="warning" width="20" /> <strong>Warning :</strong> The server is having some SQL issues. Consequently, the following wikis won\'t be displayed : ';
			
			$i=0; // affichage des wikis qui ne seront donc pas affichés. Le $i n'est là que pour la présentation.
			foreach ($_ISSUE['sql_dbases'] as $project)
			{ $i++;
				if ($i==count($_ISSUE['sql_dbases']) && count($_ISSUE['sql_dbases'])!=1) $message .= ' and ';
				$message .= $project;
				if ($i<(count($_ISSUE['sql_dbases'])-1)) $message .= ', ';
			}
			
			$message .= '.</p>'."\n";
			
			echo $message;
		}
?></fieldset>
<?php
	}
	
	// displaying global account (SUL) data
?><fieldset><legend>SUL info</legend>
<?php
	if (!empty($sulinfo['id']) && ( ( isset( $_ISSUE ) && !$_ISSUE['sql_centralauth']) || ( !isset( $_ISSUE ) ) ) ) {
?>
<p><strong>Name : </strong> <?php echo $username; ?></p>
<p><strong>User ID : </strong> <?php echo $sulinfo['id']; ?></p>
<p><strong>Registered : </strong> <?php if ($sulinfo['registered']==-1) echo "< 2006";
										else echo display_date($sulinfo['registered']); ?></p><?php if ($showinactivity) { ?>
<p><strong>Last activity : </strong> <?php echo display_date($sulinfo['lastactive']); ?></p><?php } ?>
<p><strong>Total editcount : </strong> <?php echo number_format($sulinfo['totaleditcount']); ?></p>
<p><strong>Global groups : </strong> <?php echo $sulinfo['status']; ?></p>
<p><strong>Hidden / Locked : </strong> <?php
	if (!$sulinfo['locked'] && !$sulinfo['hidden'])
		echo 'No';
	elseif ($sulinfo['locked'] && !$sulinfo['hidden'])
		echo 'Locked';
	elseif (!$sulinfo['locked'] && $sulinfo['hidden'])
		echo 'Hidden';
	else
		echo 'Locked and hidden';
		
// the following links can be modified if some other tool can help
?></p>
<p><strong>Useful links : </strong><a href="//meta.wikimedia.org/wiki/Special:CentralAuth/<?php echo $username; ?>">CentralAuth</a> <small><a href="//meta.wikimedia.org/wiki/Special:CentralAuth/<?php echo $username; ?>">(secure)</a></small> - 
<a href="//toolserver.org/~pathoschild/crossactivity/?user=<?php echo $username; ?>">Cross-wiki activities</a> - 
<a href="//tools.wmflabs.org/guc/index.php?user=<?php echo $username; ?>">Cross-wiki contribs</a> - 
<a href="//toolserver.org/~erwin85/xcontribs.php?user=<?php echo $username; ?>">Cross-wikiness</a>

<?php
if (empty($_COOKIE['sulinfo-pref-showinactivity']) || empty($_COOKIE['sulinfo-pref-showblocks']) || empty($_COOKIE['sulinfo-pref-showlocked']) || $_COOKIE['sulinfo-pref-showinactivity']=='ask' || $_COOKIE['sulinfo-pref-showblocks']=='ask' || $_COOKIE['sulinfo-pref-showlocked']=='ask') {
?><form method="get" action="//tools.wmflabs.org/quentinv57-tools/tools/sulinfo.php" id="mw-localstewardlog-form1">
<input type="hidden" name="username" value="<?php echo $username; ?>" />
<p><strong>Change advanced options : </strong><?php
	if (empty($_COOKIE['sulinfo-pref-showinactivity']) || $_COOKIE['sulinfo-pref-showinactivity']=='ask') { ?> &#160;<input id="showinactivity" name="showinactivity" type="checkbox" value="1" <?php if ($showinactivity) echo 'checked="checked"'; ?>/>&#160;<label for="showinactivity">Display inactivity</label><?php echo "\n"; }
	if (empty($_COOKIE['sulinfo-pref-showblocks']) || $_COOKIE['sulinfo-pref-showblocks']=='ask') { ?> &#160;<input id="showblocks" name="showblocks" type="checkbox" value="1" <?php if ($showblocks) echo 'checked="checked"'; ?>/>&#160;<label for="showblocks">Display blocks</label><?php echo "\n"; }
	if (empty($_COOKIE['sulinfo-pref-showlocked']) || $_COOKIE['sulinfo-pref-showlocked']=='ask') { ?> &#160;<input id="showlocked" name="showlocked" type="checkbox" value="1" <?php if (!$hidelocked) echo 'checked="checked"'; ?>/>&#160;<label for="showlocked">Show locked/closed wikis</label><?php echo "\n"; }
?>
&#160;&#160;<input type="submit" value="Go !" /></p>
</form>
<?php
}
	}
	elseif (isset( $_ISSUE ) && $_ISSUE['sql_centralauth']) { // displaying an error if s7 has an issue and global data cannot been queried
?><p><img src="//upload.wikimedia.org/wikipedia/commons/2/22/Exclam_icon.svg" alt="warning" width="20" /> Due to an issue on Tool Labs' s7 databases server, these informations cannot been displayed. Please wait that the problem is fixed or request help at <a href="irc://irc.freenode.net/#wikimedia-labs">#wikimedia-labs</a>.</p>
<?php
	}
	else { // if the user has not merged his accounts
?><p>There is no SUL account matching the name "<?php echo $username; ?>".</p>
<?php
// quick links to change settings (inactivity, blocks, ...)
if (empty($_COOKIE['sulinfo-pref-showinactivity']) || empty($_COOKIE['sulinfo-pref-showblocks']) || empty($_COOKIE['sulinfo-pref-showlocked']) || $_COOKIE['sulinfo-pref-showinactivity']=='ask' || $_COOKIE['sulinfo-pref-showblocks']=='ask' || $_COOKIE['sulinfo-pref-showlocked']=='ask') {
?><form method="get" action="//tools.wmflabs.org/quentinv57-tools/tools/sulinfo.php" id="mw-localstewardlog-form1">
<input type="hidden" name="username" value="<?php echo $username; ?>" />
<p><strong>Change advanced options : </strong><?php
	if (empty($_COOKIE['sulinfo-pref-showinactivity']) || $_COOKIE['sulinfo-pref-showinactivity']=='ask') { ?> &#160;<input id="showinactivity" name="showinactivity" type="checkbox" value="1" <?php if ($showinactivity) echo 'checked="checked"'; ?>/>&#160;<label for="showinactivity">Display inactivity</label><?php echo "\n"; }
	if (empty($_COOKIE['sulinfo-pref-showblocks']) || $_COOKIE['sulinfo-pref-showblocks']=='ask') { ?> &#160;<input id="showblocks" name="showblocks" type="checkbox" value="1" <?php if ($showblocks) echo 'checked="checked"'; ?>/>&#160;<label for="showblocks">Display blocks</label><?php echo "\n"; }
	if (empty($_COOKIE['sulinfo-pref-showlocked']) || $_COOKIE['sulinfo-pref-showlocked']=='ask') { ?> &#160;<input id="showlocked" name="showlocked" type="checkbox" value="1" <?php if (!$hidelocked) echo 'checked="checked"'; ?>/>&#160;<label for="showlocked">Show locked/closed wikis</label><?php echo "\n"; }
?>
&#160;&#160;<input type="submit" value="Go !" /></p>
</form>
<?php // this is just a copy of the previous form
}
	}
?>

</fieldset>

<?php
// now, displays a list of local attached accounts (if there is a SUL account matching this name)
	if (!empty($sulinfo['id'])) {
?>
<fieldset><legend>List of local accounts</legend>

<table class="wikitable sortable">
	<tr>
		<th>Local wiki</th>
		<th>Links</th>
		<th>Registered</th>
		<th>Edit count</th>
		<th>Local groups</th><?php if ($showinactivity) { ?>
		<th>Last activity</th><?php } if ($showblocks) { ?>
		<th>Blocked</th><?php } ?>
	</tr>

<?php

	// the array $unattached_accounts will contain data about unattached accounts
	
	
	ksort($xwiki,SORT_STRING); // alphabetic sort on project names (requested by Edhral)
	
	// loop for every project where the username exists (attached and unattached)
	foreach ($xwiki as $project => $content)
	{
		// if the account is attached to SUL : display
		if (in_array($project,$attached_accounts)) {
			if ($content['url'] != NULL) $project_url = str_replace( 'https:', '', str_replace( 'http:', '', $content['url'] ) ); // far better to use the domain in the database
			else { /* // if it is not (it happens), this method can work but does not accept a lot of exceptions
				preg_match('#^([a-z0-9]+)(wik[a-z0-9]*)$#',$project,$ma);
				if ($ma[2]=="wiki") $ma[2].="pedia";
				$project_url = '//' .$ma[1]. '.' .$ma[2]. '.org'; */
				$project_url = 'LOCKED';
			}
?>	<tr>
		<td><?php echo $project; ?></td>
		<td><?php if ($project_url=='LOCKED') echo LOCKEDWIKI_TEXT; else { ?><a href="<?php echo $project_url.'/wiki/User:'.$username; ?>">user</a>, <a href="<?php echo $project_url.'/wiki/User talk:'.$username; ?>">talk</a>, <a href="<?php echo $project_url.'/wiki/Special:Contributions/'.$username; ?>">edits</a><?php } ?></td>
		<td><span style='display: none'><?php echo wfTimestamp($content['registered']); ?></span><?php echo display_date(wfTimestamp($content['registered'])); ?></td>
		<td style="text-align:right"><?php echo $content['editcount']; ?></td>
		<td><?php echo $content['status']; ?></td><?php if ($showinactivity) { ?>
		<td><span style='display: none'><?php if( isset( $content['lastactive'] ) ) echo wfTimestamp($content['lastactive']); ?></span><?php if( isset( $content['lastactive'] ) ) echo display_date(wfTimestamp($content['lastactive'])); ?></td><?php } if ($showblocks) { ?>
		<td><?php echo ($content['blocked']) ? '<a href="'.$project_url.'/w/index.php?title=Special:Log&type=block&page=User%3A'.$username.'">yes</a>' : 'no'; ?></td><?php } ?>
	</tr>

<?php
		}
		
		// if the account is unattached to SUL
		else	$unattached_accounts[] = $project;
	}
?>

</table>

</fieldset>
<?php
	}
	else
	{ // if none of the accounts are attached
		foreach ($xwiki as $project => $content)
			$unattached_accounts[] = $project;
	}
	
	
// now, display a list of unattached accounts
?>

<fieldset><legend>List of unattached accounts</legend>

<?php
	if ( count($unattached_accounts)>0)
	{
?>
<table class="wikitable sortable">
	<tr>
		<th>Local wiki</th>
		<th>Links</th>
		<th>Registered</th>
		<th>Edit count</th>
		<th>Local groups</th><?php if ($showinactivity) { ?>
		<th>Last activity</th><?php } if ($showblocks) { ?>
		<th>Blocked</th><?php } ?>
	</tr>
<?php
		
		foreach ($unattached_accounts as $project)
		{
			$content = $xwiki[$project];
			
			if ($content['url'] != NULL) $project_url = str_replace( 'https:', '', str_replace( 'http:', '', $content['url'] ) ); // duplicated source (to fix)
			else { /* // if it is not (it happens), this method can work but does not accept a lot of exceptions
				preg_match('#^([a-z0-9]+)(wik[a-z0-9]*)$#',$project,$ma);
				if ($ma[2]=="wiki") $ma[2].="pedia";
				$project_url = '//' .$ma[1]. '.' .$ma[2]. '.org'; */
				$project_url = 'LOCKED';
			}
?>	<tr>
		<td><?php echo $project; ?></td>
		<td><?php if ($project_url=='LOCKED') echo LOCKEDWIKI_TEXT; else { ?><a href="<?php echo $project_url.'/wiki/User:'.$username; ?>">user</a>, <a href="<?php echo $project_url.'/wiki/User talk:'.$username; ?>">talk</a>, <a href="<?php echo $project_url.'/wiki/Special:Contributions/'.$username; ?>">edits</a><?php } ?></td>
		<td><span style='display: none'><?php echo wfTimestamp($content['registered']); ?></span><?php echo display_date(wfTimestamp($content['registered'])); ?></td>
		<td><?php echo $content['editcount']; ?></td>
		<td><?php echo $content['status']; ?></td><?php if ($showinactivity) { ?>
		<td><span style='display: none'><?php if( isset( $content['lastactive'] ) ) echo wfTimestamp($content['lastactive']); ?></span><?php if( isset( $content['lastactive'] ) ) echo display_date(wfTimestamp($content['lastactive'])); ?></td><?php } if ($showblocks) { ?>
		<td><?php echo ($content['blocked']) ? '<a href="'.$project_url.'/w/index.php?title=Special:Log&type=block&page=User%3A'.$username.'">yes</a>' : 'no'; ?></td><?php } ?>
	</tr>

<?php
		}
?>
</table>
<?php
	}
	
	else echo '<p>There are no unattached account with this username.</p>';
?>

</fieldset>
<script src="//tools.wmflabs.org/quentinv57-tools/tools/sortable.js" type="text/javascript"></script>

<?php

logging();

}

?></div></div>
<?php include ('../include/tools_menu.html');  ?>
Executed in <?php echo round( microtime( 1 )-$time, 2 );?> seconds.
</body></html>

