<?php

require_once('ListingAddUsers.php');

//login into mysql server
$ts_pw = posix_getpwuid(posix_getuid());
$home_dir = $ts_pw['dir'];
$ts_mycnf = parse_ini_file($home_dir . "/.my.cnf");
$con = mysql_connect('enwiki-p.userdb.toolserver.org', $ts_mycnf['user'], $ts_mycnf['password'])
        or die('Could not connect: ' . mysql_error());
$user_name = $ts_mycnf['user'];
unset($ts_mycnf, $ts_pw);

$user = $_POST['user'];
$pass = $_POST['pass'];

if(empty($user) || empty($pass))
    die("Missing user or password!");

if($users[$user] != hash('sha256', $pass))
    die("Incorrect user or password!");

if(!empty($_POST["name"]))
    $name = mysql_real_escape_string($_POST["name"]);
else
    die("No project name given!");

$is_wikiproject = isset($_POST["is_wikiproject"]) ? 1 : 0;

$cat_name = NULL;
if(!empty($_POST["cat_name"]))
    $cat_name = mysql_real_escape_string($_POST["cat_name"]);

mysql_select_db('enwiki_p', $con)
        or die('Could not select db: ' . mysql_error());

echo "Creating a listing for ${name}...<br>";
flush();

$log_file = fopen($home_dir . '/CleanupListingAdd.log', 'a');
$date = date('Y-m-d H:i:s');
fwrite($log_file, "$date User $user is creating a listing for $name.\n");
fclose($log_file);

$user_db = "u_${user_name}_cleanup";

//check if name already exists...didn't use unique
$sql = "SELECT COUNT(name)
        FROM $user_db.projects
        WHERE name = '${name}'";
$result = mysql_query($sql,$con)
            or die("Could not check if ${name} exists.");
$count = mysql_result(mysql_query($sql,$con), 0);

if($count != 0)
    die("${name} already exists.");

//add the listing

$sql = "INSERT INTO $user_db.projects(name, cat_name, is_wikiproject)
            VALUES ('${name}','${cat_name}',${is_wikiproject})";

mysql_query($sql,$con)
        or die('Could not add a listing for ${name}: ' . mysql_error());

echo "Created a listing for ${name}.";

mysql_close($con)
        or die('Could not close connection to db: ' . mysql_error());
?>
