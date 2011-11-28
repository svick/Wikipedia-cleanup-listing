<?php

require_once('ListingAddUsers.php');

if(isset($_POST["user"]) || isset($_POST["pass"]))
    die("Missing user or password!");

if($users[$_POST["user"]] != hash('sha256', $_POST["pass"]))
    die("Incorrect user or password!");

if(isset($_POST["name"]))
    $name = mysql_real_escape_string($_POST["name"]);
else
    die("No project name given!");

$is_wikiproject = 1;
if($_POST["is_wikiproject"] != "checked")
    $is_wikiproject  = 0;

$cat_name = NULL;
if(isset($_POST["cat_name"]))
    $cat_name = mysql_real_escape_string($_POST["cat_name"]);

//login into mysql server
$ts_pw = posix_getpwuid(posix_getuid());
$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/.my.cnf");
$con = mysql_connect('enwiki-p.userdb.toolserver.org', $ts_mycnf['user'], $ts_mycnf['password'])
        or die('Could not connect: ' . mysql_error());
$user_name = $ts_mycnf['user'];
unset($ts_mycnf, $ts_pw);

mysql_select_db('enwiki_p', $con)
        or die('Could not select db: ' . mysql_error());

echo "Creating a listing for ${name}...";

$user_db = "u_${user_name}_cleanup";

//check if name already exists...didn't use unique
$sql = "SELECT COUNT(name)
        FROM $user_db.projects
        WHERE name = ${name}";
$count = mysql_result(mysql_query($sql,$con), 0)
            or die("Could not check if ${name} exists.");

if($count != 0)
    die("${name} already exists.");

//add the listing

$sql = "INSERT INTO $user_db.projects(name, cat_name, is_wikiproject)
            VALUES (${name},${cat_name},${is_wikiproject})";

mysql_query($sql,$con)
        or die('Could not add a listing for ${name}: ' . mysql_error());

echo "Created a listing for ${name}.";

mysql_close($con)
        or die('Could not close connection to db: ' . mysql_error());
?>
 
