<?php

/*   ---------------------------------------------

Author : Quentinv57

Licence : GNU General Public License v3
			(see http://www.gnu.org/licenses/)
			
Date of creation : 2011-08-27
Last modified : 2011-10-11

functions that are used frequently in my TS tools
			
---------------------------------------------   */


function wfTimestamp($ts=0)
{ // returns a timestamp from a WP-formated date
	if (preg_match('#^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$#', $ts, $da))
	{
		$uts = gmmktime((int)$da[4],(int)$da[5],(int)$da[6],(int)$da[2],(int)$da[3],(int)$da[1]);
	}
	else $uts=NULL;
 
	return $uts;
}

function sqlTimestamp($ts=0)
{ // returns a timestamp from a SQL-formated date
	if (preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $ts, $da))
	{
		$uts = gmmktime((int)$da[4],(int)$da[5],(int)$da[6],(int)$da[2],(int)$da[3],(int)$da[1]);
	}
	else $uts=NULL;
 
	return $uts;
}

function display_date ($uts)
{ // $uts should be a timestamp
	if (!empty($uts))
		return gmdate('j F Y', $uts);
		
	else return NULL;
}

function display_time ($uts)
{ // $uts should be a timestamp
	if (!empty($uts))
		return gmdate('H:i, j F Y', $uts);
		
	else return NULL;
}

function is_inactive ( $timestr, $tm_inactivity )
{
	if ($timestr==NULL)
		return TRUE;
	// inacitivité tellement ancienne que ce n'est plus dans la base de données (ordre de 2009) 
	
	$year = substr ($timestr,0,4);
	$month = substr ($timestr,5,2);
	$day = substr ($timestr,8,2);
	
	$diffm = (date('Y') - $year)*12 + (date('m') - $month);
	$diffd = date('d') - $day;
	
	if ($diffm > $tm_inactivity)
		return TRUE;
	
	elseif ($diffm == $tm_inactivity) {
		if ($diffd > 0)
			return TRUE;
		else
			return FALSE;
	}
	
	else
		return FALSE;
}

function logging ()
{ /* stats are no more collected - remove the comment to start again
	global $_GET;
	global $_POST;
	$get = NULL;
	$post = NULL;
	
	foreach ($_GET as $key => $value)
	{
		$get .= $key.'='.$value.'; ';
	}
	foreach ($_POST as $key => $value)
	{
		$post .= $key.'='.$value.'; ';
	}
	
	$time = date ('Y-m-d H:i:s');
	
	$log = '['.$time.'] ' .$_SERVER['SCRIPT_NAME'];
	if (!empty($get)) $log .= ' - GET '.$get;
	if (!empty($post)) $log .= ' - POST '.$post;
	
	// log dans le fichier stats.txt
	$statsfile = fopen('stats.txt','a');
	fputs ($statsfile, $log."\n");
	fclose ($statsfile);
	*/
	return 1;
}

function utf8_ucfirst($str) {
	$fc = mb_strtoupper(mb_substr($str, 0, 1), 'utf-8');
	return $fc.mb_substr($str, 1);
}

function comment_linkify ($cmt)
{ // linkifies a comment string
	if (!empty($cmt)) {
		$cmt = htmlentities($cmt, ENT_QUOTES, "UTF-8");
		$cmt = preg_replace('#(https?://[a-z]+\.(wikimedia|wikipedia|wikibooks|wikinews|wikisource|wikiquote|wikiversity|wiktionary)\.org/[^ ]*)#U', '<a href="$1" class="external">$1</a>', $cmt);
		$cmt = preg_replace('#\[\[([^\]]+)\|([^\]]+)\]\]#U', '<a href="//meta.wikimedia.org/wiki/$1" class="external">$2</a>', $cmt);
		$cmt = preg_replace('#\[\[([^\]]+)\]\]#U', '<a href="//meta.wikimedia.org/wiki/$1" class="external">$1</a>', $cmt);
		$cmt = preg_replace('#/\*(.*)\*/#U', '<span class="autocomment">$1</span> ', $cmt, 1);
		$cmt = '<i>(' .$cmt. ')</i>';
		// don't forget to escape HTML special chars - replaced on 2011-10-11 by htmlentities()
		
		return $cmt;
	}
	else return '';
}



?>