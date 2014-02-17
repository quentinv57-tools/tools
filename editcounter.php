<?php

/*   ---------------------------------------------

Author : Quentinv57

Licence : GNU General Public License v3
			(see //www.gnu.org/licenses/)
			
Date of creation : 2012-02-12
Last modified : 2012-02-19

Editcounter to replace X!'s that expired
			
---------------------------------------------   */

#ob_gzhandler();

// Functions
include ('../include/functions.fct.php');

function zeroifnull ($int) {
	return (empty($int)) ? 0 : $int;
}



$displayed_actions = array (	'delete' => "Deletions",
								'restore' => "Undeletions",
								'block' => "Blocks",
								'unblock' => "Unblocks",
								'reblock' => "Block changes",
								'protect' => "Protections",
								'unprotect' => "Unprotections",
								'modify' => "Protection changes",
								'rights' => "User rights changes",
								'renameuser' => "User rename");


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "//www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="//www.w3.org/1999/xhtml" lang="en-US" xml:lang="en-US">
<head><title>Quentinv57's Tools! - Editcounter</title><style type="text/css">
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
<script type="text/javascript" src="//www.google.com/jsapi"></script>
<script type="text/javascript">
	function hideObject(obj) {
		document.getElementById(obj).style.display = 'none';
		document.getElementById('show-'+obj).style.display = 'inline';
		document.getElementById('hide-'+obj).style.display = 'none';
	}
	
	function displayObject(obj) {
		document.getElementById(obj).style.display = 'block';
		document.getElementById('show-'+obj).style.display = 'none';
		document.getElementById('hide-'+obj).style.display = 'inline';
	}
	
	function initObject(obj) {
		document.getElementById(obj).style.display = 'none';
		document.getElementById('show-'+obj).style.display = 'inline';
		document.getElementById('hide-'+obj).style.display = 'none';
	}
</script>
</head>
<body class="mediawiki"><div id="globalWrapper"><div id="column-content"><div id="content">
<h1>Q's Editcounter</h1>
<br />
<?php

// Open connexion to SQL database
$toolserver_mycnf = parse_ini_file("/data/project/quentinv57-tools/replica.my.cnf");
$mysql = new MySQLi ('enwiki.labsdb',$toolserver_mycnf['user'], $toolserver_mycnf['password'], 'meta_p');
unset($toolserver_mycnf);

// getting a list of wikis (non-locked only) from the database
$res = $mysql->query ("SELECT `dbname`,`url`,`slice` FROM `wiki` WHERE `is_closed`=0");
while ($line = $res->fetch_assoc()) { $arr_databases[substr($line['dbname'],0,-2)] = $line['url']; }
$res->free();

	

// First part : displays the form --------------------------------------------------------------------------------------------------------------------------------
if (empty($_GET['username']) OR empty($_GET['project']))
{
	$mysql->close();
	
?><fieldset><legend>Editcounter</legend>
<form method="get" action="//tools.wmflabs.org/quentinv57-tools/tools/editcounter.php" id="mw-sulinfo-form1">
<table border="0" id="mw-movepage-table"> 
<tr><td class='mw-label'><label for="username">Username :</label></td><td class='mw-input'><input id="username" name="username" type="text" /></td>
<tr><td class='mw-label'><label for="project">Project :</label></td><td class='mw-input'><select id="project" name="project"><?php
	
	foreach ($arr_databases as $db => $domain)
	{
		echo '<option value="'.$db.'">'.$db.'</option>';
	}
	
?></select></td></tr>
<tr><td>&#160;</td><td class='mw-submit'><input type="submit" value="Go !" /></td></tr>
</table>
</form></fieldset>
<?php
}

// Second part : displays the results --------------------------------------------------------------------------------------------------------------------------------
elseif (array_key_exists($_GET['project'], $arr_databases))
{
	// Getting namespaces
	$namespaces = array();
	$namespaces[0] = 'main';
	
	$res2 = $mysql->query ("SELECT `ns_id`,`ns_name` FROM `namespacename` WHERE `dbname`='" .$_GET['project']. "_p' AND `ns_is_favorite`=1 ORDER BY `ns_id`");
		
	while ($line = $res2->fetch_assoc())
	{
		if ($line['ns_id']>0) 	$namespaces[$line['ns_id']] = $line['ns_name'];
	}
	$res2->free();
	
	$mysql->close();
	
	
	$project = $_GET['project']; // no security issue, as the var is in $arr_databases
	$project_url = '//' .$arr_databases[$project]; // much better - protocol relative :)
	
	// Open connexion to SQL database
	$toolserver_mycnf = parse_ini_file("/data/project/quentinv57-tools/replica.my.cnf");
	$mysql = new MySQLi ($project.'.labsdb',$toolserver_mycnf['user'], $toolserver_mycnf['password'], $project.'_p');
	unset($toolserver_mycnf);
	
	$username = str_replace('_',' ',utf8_ucfirst($mysql->real_escape_string($_GET['username'])));
	
	// init the $sulinfo array (will contain data for the global account)
	$editcount = array ( 	'id' => 0,
							'editcount' => 0,
							'registered' => 0,
							'status' => array(),
							'lastactive' => NULL
							);
	
	$created_pages = array();
	$created_redirects = array();
	$most_touched_pages = array();
	$graph_contribs = array();
	$graph_contribs_acc = array();
	$graph_actions = array();
	$graph_namespaces = array();
	$graph_namespaces_monthly = array();
	$graph_sysopactions = array();
	
	// pour avoir le nombre d'édits moyen par page
	$editbypage_nbpages = 0;
	$editbypage_nbedits = 0;
	
	
	// 2.1.1 - getting registration, editcount and groups from database
	if ($res2 = $mysql->query ("SELECT `user_id`, `user_registration`,`user_editcount`,ug_group FROM `user` LEFT JOIN `user_groups` ON `user_groups`.`ug_user`=`user`.`user_id` WHERE `user_name`='" .$username. "' GROUP BY `ug_group`"))
	{
		// skipping the project if the account does not exist
		if ($res2->num_rows != 0)
		{
			$req = $res2->fetch_assoc();
			
			// if the account is existing, it is added to the $xwiki array
			$editcount['id'] = $req['user_id'];
			$editcount['registered'] = wfTimestamp($req['user_registration']);
			if (empty($req['user_registration']))
				$editcount['registered'] = -1; // on met cette variable à -1 si la date d'enregistrement est trop vieille pour apparaître dans la base de données
			$editcount['editcount'] = $req['user_editcount'];
			$editcount['status'][] = $req['ug_group'];
										
			// Adding other status
			while ($req = $res2->fetch_assoc()) {
				$editcount['status'][] = $req['ug_group'];
			}
			
			// to prevent crazy people to crash the database
			if ($editcount['editcount'] > 500000)
				exit('Sorry, but this editcount was not made to give statistics about bots. Thanks for your comprehension.');
				
			// 2.1.3 - the rest
			$query = "SELECT `rev_timestamp`,`page_namespace`,`rev_parent_id`,`page_title`,`page_is_redirect` FROM `revision_userindex` LEFT JOIN `page` ON `revision_userindex`.`rev_page`=`page`.`page_id` WHERE `rev_user`=" .$editcount['id'];
			
			if ($res = $mysql->query ($query))
			{
				$curd = 0;
				while ($req = $res->fetch_assoc())
				{
					// Pages créées
					if ($req['rev_parent_id']==0) {
						if ($req['page_is_redirect'])
							$created_redirects[$req['page_namespace']][] = $req['page_title'];
						else
							$created_pages[$req['page_namespace']][] = $req['page_title'];
					}
					
					// Pages les plus touchées
					if (empty($most_touched_pages[$req['page_namespace']][$req['page_title']])) {
						$most_touched_pages[$req['page_namespace']][$req['page_title']] = 1;
						$editbypage_nbpages++;
					}
					else
						$most_touched_pages[$req['page_namespace']][$req['page_title']] ++;
						
					// nombre d'édits moyen par page
					$editbypage_nbedits++;
					
					// Gestion des graphes
					$t = wfTimestamp($req['rev_timestamp']);
					$date = date('Y-m', $t);
					$namespace = $req['page_namespace'];
					
					// namespace graph (pie chart)
					$graph_namespaces[$namespace] = (empty($graph_namespaces[$namespace])) ? 1 : $graph_namespaces[$namespace] + 1 ;
					
					// namespace graph (by date)
					$graph_namespaces_monthly[$date][$namespace] = (empty($graph_namespaces_monthly[$date][$namespace])) ? 1 : $graph_namespaces_monthly[$date][$namespace] + 1 ;
					
					// contribs graph
					$graph_contribs[$date] = (empty($graph_contribs[$date])) ? 1 : $graph_contribs[$date] + 1 ;
					
					// n'affiche que à partir du premier pourcent
					$curd++;
					if ($curd < 0.01*$editcount['editcount'])
						unset ($graph_contribs[$date]);
				}
				
				$begincontribs = key($graph_contribs);
				end($graph_contribs);
				$lastcontribs = key($graph_contribs);
				
				
				// Dernière activité
				$editcount['lastactive'] = $t;
				
				
				// logs now
				$res = $mysql->query ("SELECT `log_action`,`log_timestamp` FROM `logging_userindex` LEFT JOIN `user` ON `user`.`user_id`=`logging_userindex`.`log_user` WHERE `user_id`='" .$editcount['id']. "'");
				while ($req = $res->fetch_assoc())
				{
					$action = $req['log_action'];
					
					$t = wfTimestamp($req['log_timestamp']);
					$date = date('Y-m', $t);
					
					if (array_key_exists($action,$displayed_actions)) {
						$graph_sysopactions[$action] = (empty($graph_sysopactions[$action])) ? 1 : $graph_sysopactions[$action] + 1;
						$graph_actions[$date] = (empty($graph_actions[$date])) ? 1 : $graph_actions[$date] + 1;
					}
				}
				
				$beginactions = key($graph_actions);
				end($graph_actions);
				$lastactions = key($graph_actions);
				
				
				// On remplit les trous pour éviter d'avoir un graphe dégueulasse
				$date = (empty($beginactions)) ? $begincontribs : min($begincontribs,$beginactions) ;
				$graph_contribs_acc[$date] = 0;
				$dm=$date;
				
				while ($date <= max($lastcontribs,$lastactions))
				{
					if (empty($graph_contribs[$date]))
						$graph_contribs[$date] = 0;
					$graph_contribs_acc[$date] = $graph_contribs_acc[$dm] + $graph_contribs[$date];
					
					if (empty($graph_actions[$date]))
						$graph_actions[$date] = 0;
					
					$dm=$date;
					$date = date('Y-m', (sqlTimestamp($date.'-01')+32*24*3600)); // mois prochain
				}
				
				// most touched pages
				foreach ($namespaces as $ns => $nsname) {
				if (!empty($most_touched_pages[$ns])) {
					arsort($most_touched_pages[$ns]);
					array_splice($most_touched_pages[$ns], 20);
				}}
				
				// order graphs
				ksort($graph_contribs);
				ksort($graph_contribs_acc);
				ksort($graph_actions);
				arsort($graph_namespaces);
				arsort($graph_sysopactions);
				
				// on a de plus besoin pour le graphe des édits par namespace par mois d'un array supplémentaire, qui
				// ne contient que les 8 namespaces les plus utisés, et en ordre inverse
				$most_used_namespaces = $namespaces;
				$i=0;
				foreach ($graph_namespaces as $k => $v) {
					if ($i < 8)
						$i++;
					else
						unset ($most_used_namespaces[$k]);
				}
				
				// fin du IF général
			}
		}
		$res2->free();
	}
	// Si on n'a pas réussi à récupérer les données (bug signalé le 2011-11-14) :
	else {
		$_ISSUE['sql_dbases'][] = $project;
	}
	
	
	// now that SQL connection is closed, we can stripslashes as it fixes a display issue with appostrophies (requested by Cantons-de-l'Est)
	$username = htmlspecialchars(stripslashes($username));
	
	/*
	foreach ($namespaces as $k => $v)
		if (empty($graph_namespaces[$k]) || $graph_namespaces[$k] < (0.02*$editcount['editcount'])*)
			unset ($namespaces[$k]); // On n'affiche pas un namespace qui représente moins de 5% du total
	*/
	
	
	# ------------------------------------------------------------------------------------------------
	#    PART 3 : Displays the results
	# ------------------------------------------------------------------------------------------------
	
	// displaying errors (if some occured)
	if (!empty($_ISSUE['sql_servers']) || !empty($_ISSUE['sql_dbases']))
	{
?><fieldset><legend>SQL Errors</legend>
<?php
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
?><fieldset><legend>General informations</legend>
<?php
	if (!empty($editcount['id'])) {
?> 
<p><strong>Name : </strong> <?php echo $username; ?></p>
<p><strong>User ID : </strong> <?php echo $editcount['id']; ?></p>
<p><strong>Registered : </strong> <?php if ($editcount['registered']==-1) echo "< 2006";
										else echo display_date($editcount['registered']); ?></p>
<p><strong>Last activity : </strong> <?php echo display_date($editcount['lastactive']); ?></p>
<p><strong>Total editcount : </strong> <?php echo number_format($editcount['editcount']); ?></p><?php if (!empty($editcount['status'][0])) { ?>
<p><strong>User groups : </strong> <?php foreach ($editcount['status'] as $k=>$v) { if ($k>0) echo ', '; echo $v; } ?></p><?php } ?>
<p><strong>Useful links : </strong><a href="//tools.wmflabs.org/quentinv57-tools/sulinfo/<?php echo urlencode($username); ?>">SULinfo</a>
</fieldset>

<fieldset><legend>Activity</legend>

    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Date');
		data.addColumn('number', 'Accumulated edits');
		data.addColumn('number', 'Monthly edits');
        data.addRows([
		<?php $i=0;
		foreach ($graph_contribs_acc as $d => $v)
		{ $i++;
			echo "['$d', ".$graph_contribs_acc[$d].", ".$graph_contribs[$d];
			echo ($i!=count($graph_contribs_acc)) ? "],\n" : "]\n";
		}
		?>
        ]);

        var options = {
          width: 1300, height: 350,
          title: 'Accumulated edits by month for <?php echo $username; ?>',
		  series: [{textStyle:{color: 'blue'},targetAxisIndex:0}, {textStyle:{color: 'red'},targetAxisIndex:1}],
		  vAxes:[{textStyle:{color: 'blue'}}, {textStyle:{color: 'red'}}]
        };

        var chart = new google.visualization.LineChart(document.getElementById('chart_div_a1'));
        chart.draw(data, options);
      }
    </script>
	<div id="chart_div_a1"></div>
	
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Month');
        <?php
		foreach ($most_used_namespaces as $k => $v) {
		if (!empty($graph_namespaces[$k])) {
			echo "data.addColumn('number', '$v');\n";
		}}
		?>
		data.addColumn('number', 'Other');
        data.addRows([
        <?php $i=0;
		foreach ($graph_namespaces_monthly as $d => $arr)
		{ $i++;
			echo "['$d'";
			
			foreach ($most_used_namespaces as $k => $v) {
			if (!empty($graph_namespaces[$k])) {
				echo ", " . zeroifnull($arr[$k]);
			}}
			
			// get the edits in the other namespaces
			$nb = 0;
			foreach ($arr as $k => $v) {
				if (!array_key_exists($k, $most_used_namespaces))
					$nb += $v;
			}
			echo ", $nb";
			
			echo ($i!=count($graph_namespaces_monthly)) ? "],\n" : "]\n";
		}
		?>
        ]);

        var options = {
          width: 1300, height: 350,
          title: 'Monthly edits ordered by namespace for <?php echo $username; ?>',
          isStacked: true,
		  reverseCategories: false
        };

        var chart = new google.visualization.ColumnChart(document.getElementById('chart_div_a2'));
        chart.draw(data, options);
      }
    </script>
	<div id="chart_div_a2"></div>
	
	
</fieldset>

<fieldset><legend>Contributions</legend>
	
	<script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Namespace');
        data.addColumn('number', 'Contributions');
        data.addRows([
          <?php $i=0;
		  foreach ($graph_namespaces as $k => $v) { $i++;
			echo "['".$namespaces[$k]."', ".$v;
			echo ($i!=count($graph_namespaces)) ? "],\n" : "]\n";
		} ?>
        ]);

        var options = {
          width: 600, height: 500,
          title: 'Namespace distribution of edits done by <?php echo $username; ?>'
        };

        var chart = new google.visualization.PieChart(document.getElementById('chart_div_b'));
        chart.draw(data, options);
      }
    </script>

<div id="chart_div_b" style="float:right"></div>

<p><strong>Edits by page :</strong> ~ <?php echo number_format($editbypage_nbedits / $editbypage_nbpages, 2); ?> edit / page</p>

<p><strong>Created pages :</strong> <small><a id="show-div-createdpages" onclick="displayObject('div-createdpages');" style="display:none">(display the content)</a><a id="hide-div-createdpages" onclick="hideObject('div-createdpages');" style="display:none">(hide the content)</a></small></p>
<div id="div-createdpages">
<ul>
<?php
		foreach ($namespaces as $ns => $nsname) {
		if (!empty($created_pages[$ns])) {
			echo '<li><strong>'.$nsname.'</strong> : ';
			$i=0;
			foreach ($created_pages[$ns] as $page) { $i++;
				$p = ($ns==0) ? $page : $nsname.':'.$page;
				if ($i!=1) echo ' - ';
				echo '<a href="'.$project_url.'/wiki/'.urlencode($p).'">'.$p.'</a>';
			}
			echo "</li>\n";
		}}
?>
</ul></div>

<div id="chart_div_b" style="float:right"></div>
<p><strong>Created redirects :</strong> <small><a id="show-div-createdredirects" onclick="displayObject('div-createdredirects');" style="display:none">(display the content)</a><a id="hide-div-createdredirects" onclick="hideObject('div-createdredirects');" style="display:none">(hide the content)</a></small></p>
<div id="div-createdredirects">
<ul>
<?php
		foreach ($namespaces as $ns => $nsname) {
		if (!empty($created_redirects[$ns])) {
			echo '<li><strong>'.$nsname.'</strong> : ';
			$i=0;
			foreach ($created_redirects[$ns] as $page) { $i++;
				$p = ($ns==0) ? $page : $nsname.':'.$page;
				if ($i!=1) echo ' - ';
				echo '<a href="'.$project_url.'/w/index.php?title='.urlencode($p).'&redirect=no">'.$p.'</a>';
			}
			echo "</li>\n";
		}}
?>
</ul></div>

<div id="chart_div_b" style="float:right"></div>
<p><strong>Most edited pages :</strong> <small><a id="show-div-mosteditedpages" onclick="displayObject('div-mosteditedpages');" style="display:none">(display the content)</a><a id="hide-div-mosteditedpages" onclick="hideObject('div-mosteditedpages');" style="display:none">(hide the content)</a></small></p>
<div id="div-mosteditedpages">
<ul>
<?php
		foreach ($namespaces as $ns => $nsname) {
		if (!empty($most_touched_pages[$ns])) {
			echo '<li><strong>'.$nsname.'</strong> : ';
			$i=0;
			foreach ($most_touched_pages[$ns] as $page => $nbedited) { $i++;
				$p = ($ns==0) ? $page : $nsname.':'.$page;
				if ($i!=1) echo ' - ';
				echo '<a href="'.$project_url.'/w/index.php?title='.urlencode($p).'&redirect=no">'.$p.'</a>' . " ($nbedited)";
			}
			echo "</li>\n";
		}}
?>
</ul></div>

</fieldset>

<?php if (in_array('sysop',$editcount['status']) || in_array('bureaucrat',$editcount['status'])) { ?>
<fieldset><legend>Operations</legend>

	<script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Action type');
        data.addColumn('number', 'Count');
        data.addRows([
          <?php $i=0;
		  foreach ($graph_sysopactions as $k => $v) { $i++;
			echo "['".$displayed_actions[$k]."', ".$v;
			echo ($i!=count($graph_sysopactions)) ? "],\n" : "]\n";
		} ?>
        ]);

        var options = {
          width: 600, height: 500,
          title: 'Statistics on logged actions done by <?php echo $username; ?>'
        };

        var chart = new google.visualization.PieChart(document.getElementById('chart_div_c'));
        chart.draw(data, options);
      }
    </script>

<div id="chart_div_c" style="float:right"></div>

</fieldset>
<?php
}
	}

	
?>


<?php

}

logging();
$mysql->close();

?></div></div>

<script type="text/javascript">
	initObject('div-createdpages');
	initObject('div-createdredirects');
	initObject('div-mosteditedpages');
</script>


<?php include ('../include/tools_menu.html');  ?>
</body></html>

