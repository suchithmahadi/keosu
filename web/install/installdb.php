<?php
	$dbhost=test_input($_POST['dbhost']);
	$dbport=test_input($_POST['dbport']);
	$dbname=test_input($_POST['dbname']);
	$dbusername=test_input($_POST['dbusername']);
	$dbpassword=test_input($_POST['dbpassword']);
	$adminpassword=test_input($_POST['adminpassword']);
	if($dbport==null){
		$dbport="3306";
	}

	if($dbhost==null || $dbname==null || $dbusername ==null || $adminpassword==null){
		header("Location: configure.php?error=param");
		exit;
	}

	if (file_exists(__DIR__ . '/../../app/config/parameters.yml')) {
		header("Location: configure.php?error=already");
		exit;
	}
	echo($dbhost.":".$dbport);
	//Testing mysql connection
	$link = mysql_connect($dbhost.":".$dbport, $dbusername, $dbpassword);
	if (!$link) {
		header("Location: configure.php?error=bdd");
		exit;
	}
	//Creating database
	$sql = 'CREATE DATABASE '.$dbname;
	if (!mysql_query($sql, $link)) {
		header("Location: configure.php?error=bdd");
		exit;
	}
	//Import mysql dump file
	$templine = '';
	mysql_select_db($dbname);
	$lines = file(__DIR__ . '/sql/dump.sql');
	foreach ($lines as $line){
		// Skip it if it's a comment
		if (substr($line, 0, 2) == '--' || $line == '')
			continue;
		// Add this line to the current segment
		$templine .= $line;
		// If it has a semicolon at the end, it's the end of the query
		if (substr(trim($line), -1, 1) == ';'){
			// Perform the query
			mysql_query($templine);
			// Reset temp variable to empty
			$templine = '';
		}
	}


	mysql_close($link);

	//Generating parameter.yml file
	generateParameters($dbhost, $dbport,$dbname,$dbusername,$dbpassword,$adminpassword);

	function generateParameters($dbhost, $dbport,$dbname,$dbusername,$dbpassword,$adminpassword){
		$currentUrl = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$lastSlach = strrpos($currentUrl, "/");
		$currentUrl = substr($currentUrl,0,$lastSlach );
		$lastSlach = strrpos($currentUrl, "/");
		$currentUrl = substr($currentUrl,0,$lastSlach );
		$url_param='/app_dev.php/';

		$content =
			'parameters:' . PHP_EOL .
			'    database_driver: pdo_mysql' . PHP_EOL .
			'    database_host: ' . $dbhost . PHP_EOL .
			'    database_port: ' . $dbport . PHP_EOL .
			'    database_name: ' . $dbname . PHP_EOL .
			'    database_user: ' . $dbusername . PHP_EOL .
			'    database_password: ' . $dbpassword . PHP_EOL .
			'    database_path: ~' . PHP_EOL.
			'    url_base: ' . $currentUrl . PHP_EOL .
			'    url_param: ' . $url_param . PHP_EOL.
			'    mailer_transport: smtp' . PHP_EOL.
			'    mailer_host: localhost' . PHP_EOL.
			'    mailer_user: ~'  . PHP_EOL.
			'    mailer_password: ~' . PHP_EOL.
			'    admin_password: ' .$adminpassword. PHP_EOL.
			'    locale: en' .  PHP_EOL.
			'    secret: '.generateRandomString() .  PHP_EOL.
			'    installer: false' .  PHP_EOL;

		writeFile($content, 'parameters.yml');
		header("Location: ".$currentUrl.$url_param);
		exit;
	}
	function writeFile($content, $fileName){
		$fileName = __DIR__ . '/../../app/config/' . $fileName;
		echo($fileName);
		if (file_exists($fileName)) {
			unlink($fileName);
		}
		$file = fopen($fileName, "x+");
		fwrite($file, $content);
		fclose($file);
	}
	function generateRandomString($length = 20) {
		return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
	}
	function test_input($data) {
		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data);
		return $data;
	}

?>