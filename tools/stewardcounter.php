<?php

/*   ---------------------------------------------

Author : Quentinv57

Licence : GNU General Public License v3
			(see //www.gnu.org/licenses/)
			
Date of creation : 2012-01-20
Last modified : 2012-02-22

Diplays a detailed counter about steward operations
			
---------------------------------------------   */

#ob_gzhandler();

// Functions
include ('../include/functions.fct.php');

// just formats the number
function nformat ($nb) { if (!empty($nb)) return $nb; else return 0; }

function empty_array ($arr) { 
	foreach ($arr as $v)
		if (!empty($v)) return FALSE;
		
	return TRUE;
}

function frights ($rightsfoo) {
// on attend dans cette fonction qqch du type "bureaucrat, sysop oversight"
// (pour un changement de droits de bureaucrate et admin à oversighter, ex)
	$rights = explode ("\n",$rightsfoo);
	
	$rights_removed = explode(', ',$rights[0]);
	$rights_added = explode(', ',$rights[1]);
	
	// si on ajoute un droit qu'on retire, on ne l'a pas modifié...
	foreach ($rights_added as $k => $val)
		if (in_array($val,$rights_removed)) {
			unset ($rights_added[$k]);
			unset ($rights_removed[array_search($val,$rights_removed)]);
		}
		
	$ret = array('added'=>array(),'removed'=>array());
	
	if (!empty_array($rights_added) && $rights_added[0]!='(none)')
		$ret['added'] = $rights_added;
	if (!empty_array($rights_removed) && $rights_removed[0]!='(none)')
		$ret['removed'] = $rights_removed;
	
	
	return $ret;
}

function get_set ($setsfoo) {
	$sets = explode ("\n",$setsfoo);
	return $sets[0];
}


function is_self ($user, $userfoo) {
	return (substr($userfoo,0,strlen($user))==$user) ? TRUE : FALSE;
}

function is_meta ($userfoo) {
	return (!preg_match('#@#',$userfoo)) ? TRUE : FALSE;
}



?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "//www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="//www.w3.org/1999/xhtml" lang="en-US" xml:lang="en-US">
<head><title>Quentinv57's Tools! - Steward Actions Counter</title><style type="text/css">
<!--/* <![CDATA[ */
@import "//tools.wmflabs.org/quentinv57-tools/main.css"; 
@import "//fr.wikipedia.org/w/index.php?title=MediaWiki:Monobook.css&usemsgcache=yes&action=raw&ctype=text/css&smaxage=2678400";
@import "//fr.wikipedia.org/w/index.php?title=MediaWiki:Common.css&usemsgcache=yes&action=raw&ctype=text/css&smaxage=2678400";
@import "//fr.wikipedia.org/w/index.php?title=-&action=raw&gen=css&maxage=2678400&smaxage=0&ts=20061201185052"; 
@import "//bits.wikimedia.org/skins/common/shared.css";
/* ]]> */-->
td { vertical-align: top; }
.align { text-align:center; }
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
<h1>Steward Actions Counter</h1>
<br />
<?php

// firstly : displays the form  -----------------------------------------------------------------------------------------
if (empty($_GET['username']))
{
?><fieldset><legend>Steward Actions Counter</legend>
<form method="get" action="stewardcounter.php" id="mw-globalsysoplog-form1">
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
	$mysql_ts = new MySQLi ('metawiki.labsdb',$toolserver_mycnf['user'], $toolserver_mycnf['password'], 'metawiki_p');
	unset($toolserver_mycnf);
	
	// taking GET vars
	$username = str_replace('_',' ',utf8_ucfirst($mysql_ts->real_escape_string($_GET['username'])));
	
	$couter = array (	'rights-self' => array (),
						'rights-other' => array (),
						'globalrights' => array(),
						'setchange' => array(),
						'globalblock' => array(),
						'centralauth' => array()
						);
						
	$total = array (	'rights-self' => 0,
						'rights-other' => 0,
						'globalrights' => 0,
						'setchange' => 0,
						'globalblock' => 0,
						'centralauth' => 0
						);
						
	$totall = 0;
	
	// Rights (self and others)
	$res = $mysql_ts->query ("SELECT `log_title`, `log_params` FROM `logging_userindex` WHERE `log_type`='rights' AND `log_user_text`='".$username."'");
	$arr_databases = array();
	while ($line = $res->fetch_assoc()) {
		if (!empty($line['log_params']) && !is_meta($line['log_title'])) {
			$frights = frights($line['log_params']);
			$self = (is_self($username, $line['log_title'])) ? 'self' : 'other';
			
			foreach ($frights['added'] as $v) {
				$counter['rights-'.$self]['added'][$v]++;
				$counter['rights-'.$self]['total'][$v]++;
			}
			foreach ($frights['removed'] as $v) {
				$counter['rights-'.$self]['removed'][$v]++;
				$counter['rights-'.$self]['total'][$v]++;
			}
			
			// Update totals
			$total['rights-'.$self]++;
			$totall++;
		}
	}
	$res->free();
	
	// Global rights
	$res = $mysql_ts->query ("SELECT `log_action`,`log_params` FROM `logging_userindex` WHERE `log_type`='gblrights' AND `log_user_text`='".$username."'");
	$arr_databases = array();
	while ($line = $res->fetch_assoc()) {
		switch ($line['log_action']) {
			case 'usergroups':
				if (!empty($line['log_params'])) {
					$frights = frights($line['log_params']);
					
					foreach ($frights['added'] as $v) {
						$counter['globalrights']['added'][$v]++;
						$counter['globalrights']['total'][$v]++;
					}
					foreach ($frights['removed'] as $v) {
						$counter['globalrights']['removed'][$v]++;
						$counter['globalrights']['total'][$v]++;
					}
					
					// Update totals
					$total['globalrights']++;
					$totall++;
				}
			break;
			
			case 'setchange':
				if (!empty($line['log_params'])) {
					$counter['setchange'][get_set($line['log_params'])]++;
					
					// Update totals
					$total['setchange']++;
					$totall++;
				}
			break;
		}
	}
	$res->free();

	// Global blocks
	$res = $mysql_ts->query ("SELECT `log_action` FROM `logging_userindex` WHERE `log_type`='gblblock' AND `log_user_text`='".$username."'");
	$arr_databases = array();
	while ($line = $res->fetch_assoc()) {
		switch ($line['log_action']) {
			case 'gblock2':
			$counter['globalblock']['block']++;
			break;
			
			case 'gunblock':
			$counter['globalblock']['unblock']++;
			break;
			
			case 'modify':
			$counter['globalblock']['block change']++;
			break;
		}
		
		// Update totals
		$total['globalblock']++;
		$totall++;
	}
	$res->free();

	// Global locks & hide (CentralAuth)
	$res = $mysql_ts->query ("SELECT `log_action`,`log_params` FROM `logging_userindex` WHERE `log_type`='globalauth' AND `log_user_text`='".$username."'");
	$arr_databases = array();
	while ($line = $res->fetch_assoc()) {
		switch ($line['log_action']) {
			case 'setstatus':
			$frights = frights($line['log_params']);
			
			foreach ($frights['added'] as $v)
				$counter['centralauth']['un'.$v]++;
			foreach ($frights['removed'] as $v)
				$counter['centralauth'][$v]++;
			break;
		}
		
		// Update totals
		$total['centralauth']++;
		$totall++;
	}
	$res->free();

	
	
	$mysql_ts->close();
	
	// now that SQL connection is closed, we can stripslashes as it fixes a display issue with appostrophies (requested by Cantons-de-l'Est)
	$username = htmlspecialchars(stripslashes($username));
	
// now, displaying the log
?>

<!-- Google Chart Script -->
    <script type="text/javascript" src="//www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Type');
        data.addColumn('number', 'Actions');
        data.addRows([
          ['User right changes',    <?php echo intval($total['rights-other']); ?>],
          ['Self temporary permissions',      <?php echo intval($total['rights-self']); ?>],
          ['Global right changes',  <?php echo intval($total['globalrights']); ?>],
          ['Wikiset changes', <?php echo intval($total['setchange']); ?>],
          ['Global IP blocks',    <?php echo intval($total['globalblock']); ?>],
		  ['CentralAuth actions',    <?php echo intval($total['centralauth']); ?>]
        ]);

        var options = {
          width: 700, height: 400,
          title: 'Graph of steward actions by <?php echo $username; ?>'
        };

        var chart = new google.visualization.PieChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
    </script>
<!-- End of Script -->

<p>Here is a detailed view of stewards actions for the user <strong><?php echo $username ; ?></strong>.</p>
<p>Please remember this is just an indicative view to see on which areas the steward works, but as any other counter the quantity does not really mean something.</p>
<p>It contains the following sections :
<ul>
<li>Rights changes<ul>
<li><a href="#urc">User rights changes</a> (<?php echo intval($total['rights-other']); ?>)</li> 
<li><a href="#stp">Self temporary permissions</a> (<?php echo intval($total['rights-self']); ?>)</li> 
<li><a href="#grc">Global rights changes</a> (<?php echo intval($total['globalrights']); ?>)</li> 
<li><a href="#wc">Wikiset changes</a> (<?php echo intval($total['setchange']); ?>)</li> 
</ul></li>
<li>Global blocks / locks<ul>
<li><a href="#gbl">Global IP blocks</a> (<?php echo intval($total['globalblock']); ?>)</li> 
<li><a href="#ca">CentralAuth actions</a> (<?php echo intval($total['centralauth']); ?>)</li> 
</ul></li>
</ul></p>

<div id="chart_div" align="center"></div>

<br /><br />
<h2>Rights changes</h2>
<h3 id="urc">User rights changes</h3>
<?php
if (!empty_array($counter['rights-other']['total'])) {
?>
<table class="wikitable">
  <tr><th>Type</th><th>Added</th><th>Removed</th><th>Total</th></tr>
<?php
	arsort($counter['rights-other']['total']);
	
	// Displays the content of the table
	foreach ($counter['rights-other']['total'] as $bit => $val)
	{
		echo '  <tr><td>' .$bit. '</td><td class="align">' .intval($counter['rights-other']['added'][$bit]). '</td><td class="align">' .intval($counter['rights-other']['removed'][$bit]). '</td><td class="align">' .intval($counter['rights-other']['total'][$bit]). '</td></tr>';
	}
?>
</table>
<?php
} else echo "<p>This user did not make user right changes on other users.</p>";
?>

<h3 id="stp">Self temporary permissions</h3>
<?php
if (!empty_array($counter['rights-self']['total'])) {
?>
<table class="wikitable">
  <tr><th>Type</th><th>Added</th><th>Removed</th></tr>
<?php
	arsort($counter['rights-self']['total']); // we should put the total instead of this
	
	// Displays the content of the table
	foreach ($counter['rights-self']['total'] as $bit => $val)
	{
		echo '  <tr><td>' .$bit. '</td><td class="align">' .intval($counter['rights-self']['added'][$bit]). '</td><td class="align">' .intval($counter['rights-self']['removed'][$bit]). '</td></tr>';
	}
?>
</table>
<?php
} else echo "<p>This user did not make assign temporary permissions to himself.</p>";
?>

<h3 id="grc">Global rights changes</h3>
<?php
if (!empty_array($counter['globalrights'])) {
?>
<table class="wikitable">
  <tr><th>Type</th><th>Added</th><th>Removed</th><th>Total</th></tr>
<?php
	arsort($counter['globalrights']);

	// Displays the content of the table
	foreach ($counter['globalrights']['total'] as $bit => $val)
	{
		echo '  <tr><td>' .$bit. '</td><td class="align">' .intval($counter['globalrights']['added'][$bit]). '</td><td class="align">' .intval($counter['globalrights']['removed'][$bit]). '</td><td class="align">' .intval($counter['globalrights']['total'][$bit]). '</td></tr>';
	}
?>
</table>
<?php
} else echo "<p>This user did not change user global rights.</p>";
?>

<h3 id="wc">Wikiset changes</h3>
<?php
if (!empty_array($counter['setchange'])) {
?>
<table class="wikitable">
  <tr><th>Set</th><th>Changes</th></tr>
<?php
	arsort($counter['setchange']);
	
	// Displays the content of the table
	foreach ($counter['setchange'] as $bit => $val)
	{
		echo '  <tr><td>' .$bit. '</td><td class="align">' .intval($val). '</td></tr>';
	}
?>
</table>
<?php
} else echo "<p>This user did not change Wikisets.</p>";
?>

<br />
<h2>Global blocks / locks</h2>
<h3 id="gbl">Global IP blocks</h3>
<?php
if (!empty_array($counter['globalblock'])) {
?>
<table class="wikitable">
  <tr><th>Action</th><th>Changes</th></tr>
<?php
	arsort($counter['globalblock']);

	// Displays the content of the table
	foreach ($counter['globalblock'] as $bit => $val)
	{
		echo '  <tr><td>' .$bit. '</td><td class="align">' .intval($val). '</td></tr>';
	}
?>
</table>
<?php
} else echo "<p>This user did not block / unblock IPs globally.</p>";
?>

<h3 id="ca">CentralAuth actions</h3>
<?php
if (!empty_array($counter['centralauth'])) {
?>
<table class="wikitable">
  <tr><th>Action</th><th>Changes</th></tr>
<?php
	arsort($counter['centralauth']);

	// Displays the content of the table
	foreach ($counter['centralauth'] as $bit => $val)
	{
		echo '  <tr><td>' .$bit. '</td><td class="align">' .intval($val). '</td></tr>';
	}
?>
</table>
<?php
} else echo "<p>This user did not change status on global accounts.</p>";
?>
<?php

// stats
logging();
}

?></div></div>
<?php include ('../include/tools_menu.html'); ?>
</body></html>

