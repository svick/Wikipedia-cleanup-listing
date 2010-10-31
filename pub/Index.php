<?php
//Svick
//PD October 2010
//Version .1
//For Cleanup Listing

        require_once 'TableWriterFactory.php';

        $ts_pw = posix_getpwuid(posix_getuid());
        $ts_mycnf = parse_ini_file($ts_pw['dir'] . "/.my.cnf");
        $con = mysql_connect('enwiki-p.userdb.toolserver.org', $ts_mycnf['user'], $ts_mycnf['password'])
                or die('Could not connect: ' . mysql_error()); 
        $user_name = $ts_mycnf['user'];
        unset($ts_mycnf, $ts_pw);

        $user_db = "u_${user_name}_cleanup";

        mysql_select_db($user_db, $con)
                or die('Could not select db: ' . mysql_error());

        $sql = "SELECT id, time FROM runs ORDER BY id DESC LIMIT 1";
        $run = mysql_fetch_assoc(mysql_query($sql,$con))
                or die('Could not select last run: ' . mysql_error());
        $run_id = $run['id'];
        $run_time = $run['time'];

        $table_writer = TableWriterFactory::Create('html');
        $table_writer->WriteHeader("Cleanup listings for WikiProjects");
        $table_writer->WriteText("This is a list of cleanup listings for various WikiProjects generated on " . date('j F Y, G:i:s e', strtotime($run_time)) . ".");
?>
    <ul>
<?
        $sql = "SELECT name FROM projects WHERE active = 1";
        $projects = mysql_query($sql,$con);
        while ($project = mysql_fetch_assoc($projects))
        {
?>
      <li>
        <?= $table_writer->FormatLink("CleanupListing.php?project={$project['name']}", $project['name']) ?>
        (<?= $table_writer->FormatLink("CleanupListing.php?project={$project['name']}&format=csv", 'CSV') ?>,
        <?= $table_writer->FormatLink("CleanupListingByCat.php?project={$project['name']}", 'by cat') ?>)
      </li>
<?
        }
?>
    </ul>
<?
        $table_writer->WriteFooter();
?>
