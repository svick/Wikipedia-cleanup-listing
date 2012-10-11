<?php
//Smallman12q
//Svick
//Version .1
//For Cleanup Listing

function unique_name($folder, $name, $extension)
{
  $result = "$folder/$name.$extension";

  $i = 2;
  while (file_exists($result))
  {
    $result = "$folder/$name-$i.$extension";
    $i++;
  }

  return $result;
}

$folder = '/mnt/user-store/svick/listings_archive';

function dump($year, $month, $table, $condition)
{
  global $folder;
  $dump = unique_name($folder, "$year-$month-$table", "sql.gz");
  $ignored = null;
  $success = 1;
  exec("/home/svick/CleanupListing/ArchiveDump.sh $table '$condition' '$dump'", &$ignored, &$success);
  return $success === 0;
}

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
  select distinct month(time) as month, year(time) as year
  from runs
  where datediff(now(), time) > 60
  and archived = 0
  order by year, month";
$intervals = mysql_query($sql,$con)
        or die('Could not select intervals: '. mysql_error());

while ($interval = mysql_fetch_assoc($intervals))
{
  $month = $interval['month'];
  $full_month = sprintf('%02d', $month);
  $year = $interval['year'];
  echo "Archiving $year-$full_month.\n";

  $sql = "
    select min(id) as min, max(id) as max
    from runs
    where month(time) = $month
    and year(time) = $year";
  $result = mysql_query($sql, $con)
    or die('Could not select run id: ' . mysql_error());
  $bounds = mysql_fetch_assoc($result);
  $min_run_id = $bounds['min'];
  $max_run_id = $bounds['max'];

  $sql = "
    select max(id)
    from articles
    where run_id = $max_run_id";
  $result = mysql_query($sql, $con)
    or die('Could not select article id: ' . mysql_error());
  $max_article_id = mysql_result($result, 0);

  dump($year, $full_month, 'runs', "id <= $max_run_id AND id >= $min_run_id")
    or die('Could not dump runs.');
  dump($year, $full_month, 'articles', "id <= $max_article_id")
    or die('Could not dump articles.');
  dump($year, $full_month, 'categories', "article_id <= $max_article_id")
    or die('Could not dump categories.');

  $sql = "
    delete from categories
    where article_id <= $max_article_id";
  mysql_query($sql, $con)
    or die('Could not delete categories: ' . mysql_error());

  $sql = "
    delete from articles
    where id <= $max_article_id";
  mysql_query($sql, $con)
    or die('Could not delete articles: ' . mysql_error());

  $sql = "
    update runs
    set archived = 1
    where id <= $max_run_id";
  mysql_query($sql, $con)
    or die('Could not mark as archived: ' . mysql_error());
}

//close connection
mysql_close($con)
        or die('Could not close connection to db: ' . mysql_error());
?>
