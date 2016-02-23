<?php

if(!$_COOKIE['user']) exit;

$file_sur = "survey.txt";

if($_GET['act'] == "stat" && $_GET['action'] == 0)
{
	setcookie('secondtime', 1, time()+60*60*24*365);
}

if($_GET['act'] == "survey" && $_GET['action'] !== false)
{
	$fp = fopen($file_sur, "a");
	/*
	Actions:
	0 - pressed View button
	10 - feature 1 dislike
	11 - feature 1 like
	20 - feature 2 dislike
	21 - feature 2 like
	...
	*/
	fwrite($fp, $_COOKIE['user']."\t".$_GET['action']."\t".date("H:i:s, d/m")."\n");
	fclose($fp);

	if(strlen($_GET['action']) == 2)
	{
		setcookie('option'.substr($_GET['action'], 0, 1), substr($_GET['action'], 1, 1), time()+60*60*24*365);
	}
}

?>