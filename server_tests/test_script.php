<h1>Server Tests</h1>
<?php

$hostname = 'localhost';
$username = '';
$password = '';
$db_name = '';

$admin_email = '';

error_reporting(E_ALL);

// modrewrite
if ($_GET['rewrite'] == 'true') {
	echo "<p>Modrewrite and .htaccess operational</p>";
} else {
	echo "<p>Modrewrite and .htaccess no testes</p>";	
}

// test mysql
if ($username) {
	$res = mysql_connect($hostname, $username, $password) or die("Couldn't connect to database");
	echo "<p>Connected to db</p>";
}

if ($db_name) {
	mysql_select_db($db_name, $res) or die("Couldn't select database");
	echo "<p>Selected db</p>";
}

// test email
if ($admin_email) {
	mail($admin_email, 'test email', 'test email', "from: $admin_email\n") or ("Couldn't send email");
}


echo "all tests passed";

?>