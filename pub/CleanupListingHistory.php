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

if ($project_name == null)
        die('Project was not set.');

$project_name = str_replace(' ', '_', $project_name);

$project_name_sql = mysql_real_escape_string($project_name);

$table_writer = TableWriterFactory::Create($_GET['format']);
$table_writer->WriteHeader("Cleanup history for $project_name");

$table_writer->WriteTableHeader(array(
        new Column('Timestamp'),
        new Column('Total articles'),
        new Column('Cleanup articles'),
        new Column('Cleanup issues')));

$sql = "SELECT
            runs.time AS time,
            runs.total_articles AS total_articles,
            (SELECT COUNT(*)
             FROM articles
             WHERE run_id = runs.id) AS cleanup_articles,
            (SELECT COUNT(*)
             FROM articles
             JOIN categories on articles.id = categories.article_id
             WHERE run_id = runs.id) AS issues
        FROM projects
        JOIN runs ON projects.id = runs.project_id
        WHERE name = '$project_name_sql'
        AND runs.finished = 1
        ORDER BY runs.time";

$runs = mysql_query($sql, $con)
  or die('Could not load runs: ' . mysql_error());

while ($run = mysql_fetch_assoc($runs))
{
    $table_writer->WriteRow(array(
      $run['time'],
      $run['total_articles'],
      $run['cleanup_articles'],
      $run['issues']
    ));
}

$table_writer->WriteTableFooter();

$table_writer->WriteFooter();
?>
