<?php
//Svick
//PD 2012
//Version .1
//For Cleanup Listing

require_once 'TableWriterFactory.php';
require_once 'Functions.php';

$ts_pw = posix_getpwuid(posix_getuid());
$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/.my.cnf");
$con = mysql_connect('enwiki-p.userdb.toolserver.org', $ts_mycnf['user'], $ts_mycnf['password'])
        or die('Could not connect: ' . mysql_error()); 
$user_name = $ts_mycnf['user'];
unset($ts_mycnf, $ts_pw);

$user_db = "u_${user_name}_cleanup";

mysql_select_db($user_db, $con)
        or die('Could not select db: ' . mysql_error());

$project_name = $_GET['project'];
$month = (int)$_GET['month'];
$year = (int)$_GET['year'];

if ($year < 2010)
        die('Invalid year or year not set.');
if ($month < 1 || $month > 12)
        die('Invalid month or month not set.');

$month = sprintf('%02d', $month);

$table_writer = TableWriterFactory::Create($_GET['format']);
$table_writer->WriteHeader("Cleanup history for $year-$month");

$table_writer->WriteTableHeader(array(
        new Column('Project'),
        new Column('Timestamp'),
        new Column('Total articles'),
        new Column('Cleanup articles'),
        new Column('Cleanup issues')));

$sql = "SELECT
            projects.name AS name,
            runs.time AS time,
            runs.total_articles AS total_articles,
            runs.cleanup_articles AS cleanup_articles,
            runs.issues AS issues
        FROM projects
        JOIN runs ON projects.id = runs.project_id
        WHERE time LIKE '$year-$month-%'
        AND runs.finished = 1
        ORDER BY runs.time";

$runs = mysql_query($sql, $con)
  or die('Could not load runs: ' . mysql_error());

while ($run = mysql_fetch_assoc($runs))
{
    $table_writer->WriteRow(array(
      $run['name'],
      $run['time'],
      $run['total_articles'],
      $run['cleanup_articles'],
      $run['issues']
    ));
}

$table_writer->WriteTableFooter();

$table_writer->WriteFooter();
?>
