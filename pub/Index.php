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

        $listing = mysql_real_escape_string($_GET['listing']);
        $user_db = "u_${user_name}_cleanup";
        if ($listing)
          $user_db = "u_${user_name}_${listing}_cleanup";

        mysql_select_db($user_db, $con)
                or die('Could not select db: ' . mysql_error());

        $table_writer = TableWriterFactory::Create('html');
        $table_writer->WriteHeader("Cleanup listings");
        $table_writer->WriteText("This is a list of cleanup listings for ???.");
?>
    <ul>
<?
        $sql = "SELECT name
                FROM groups
                WHERE active = 1
                AND id IN (
                  SELECT group_id
                  FROM runs
                  WHERE finished = 1)
                ORDER BY name";
        $groups = mysql_query($sql,$con);
        while ($group = mysql_fetch_assoc($groups))
        {
          $encoded_name = urlencode($group['name']);
?>
      <li>
        <?= $table_writer->FormatLink("CleanupListing.php?group=$encoded_name", $group['name']) ?>
        (<?= $table_writer->FormatLink("CleanupListing.php?group=$encoded_name&format=csv", 'CSV') ?>,
        <?= $table_writer->FormatLink("CleanupListingByCat.php?group=$encoded_name", 'by cat') ?>)
      </li>
<?
        }
?>
    </ul>
<?
        $table_writer->WriteFooter();
?>
