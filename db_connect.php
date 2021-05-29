<?php
	$dbhost = 'dbhost';
	$dbuser = 'dbuser';
	$dbpass = 'dvpass';
	$link = mysqli_connect($dbhost, $dbuser, $dbpass) or die ('cannot connect to db');
	$dbname = 'dbname';
	mysqli_select_db($link, $dbname);
?>