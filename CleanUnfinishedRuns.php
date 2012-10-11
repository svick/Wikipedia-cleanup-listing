<?php
//Smallman12q
//Svick
//Version .1
//For Cleanup Listing

require_once 'pub/Settings.php';

$ts_pw = posix_getpwuid(posix_getuid());
$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/.my.cnf");
$con = mysql_connect('enwiki-p.userdb.toolserver.org', $ts_mycnf['user'], $ts_mycnf['password'])
        or die('Could not connect: ' . mysql_error()); 
$user_name = $ts_mycnf['user'];
unset($ts_mycnf, $ts_pw);

$user_db = "u_${user_name}_cleanup";

mysql_select_db($user_db, $con)
        or die('Could not select db: ' . mysql_error());

$sql = "
  select id
  from runs
  where finished = 0
  and datediff(now(), time) > 7";
$runs = mysql_query($sql,$con)
        or die('Could not select runs: '. mysql_error());

while ($run = mysql_fetch_assoc($runs))
{
  $run_id = $run['id'];
  echo "Removing unfinished run $run_id.\n";
  $sql = "
    delete categories
    from categories
    join articles on articles.id = article_id
    where run_id = $run_id";

  mysql_query($sql, $con)
    or die('Could not delete categories: ' . mysql_error());

  $sql = "
    delete from articles
    where run_id = $run_id";

  mysql_query($sql, $con)
    or die('Could not delete articles: ' . mysql_error());

  $sql = "
    delete from runs
    where id = $run_id";

  mysql_query($sql, $con)
    or die('Could not delete run: ' . mysql_error());
}

//close connection
mysql_close($con)
        or die('Could not close connection to db: ' . mysql_error());
?>
