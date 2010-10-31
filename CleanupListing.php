<?php
//Svick
//PD October 2010
//Version .1
//For Cleanup Listing

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

        $sql = "SELECT id, time FROM runs ORDER BY id DESC LIMIT 1";
        $run = mysql_fetch_assoc(mysql_query($sql,$con))
                or die('Could not select last run: ' . mysql_error());
        $run_id = $run['id'];
        $run_time = $run['time'];

        $sql = "SELECT id FROM projects WHERE name = $project";
        $project = mysql_fetch_assoc(mysql_query($sql,$con));
        $project_id = $project['id'];
?>
<html>
  <head>
    <title>Cleanup listing for WikiProject <?= $project_name ?></title>
  </head>
  <style>
    td, th { border: 1px solid black }
    table { border-collapse: collapse }
  </style>
  <body>
    <p>This is a cleanup listing for <a href="http://en.wikipedia.org/wiki/Wikipedia:WikiProject_<?= $project_name ?>">WikiProject <?= $project_name ?></a> generated on <?= date('j F Y, G:i:s e', strtotime($run_time)) ?>.</p>
    <table>
      <tr>
        <th>Article</th>
        <th>Importance</th>
        <th>Class</th>
        <th>Categories</th>
      </tr>
<?
        $sql = "SELECT id, article, importance, quality
                FROM articles
                WHERE run_id = $run_id
                ORDER BY article";
        $articles = mysql_query($sql,$con)
          or die('Could not load articles: ' . mysql_error());

        while ($article = mysql_fetch_assoc($articles))
        {
?>
      <tr>
        <td><a href='http://en.wikipedia.org/wiki/<?= $article['article'] ?>'><?= str_replace('_', ' ', $article['article']) ?></a></td>
        <td><?= $article['importance'] ?></td>
        <td><?= $article['quality'] ?></td>
        <td>
<?
            $sql = "SELECT name, month, year
                    FROM categories
                    WHERE article_id = {$article['id']}";
            $categories = mysql_query($sql,$con)
              or die('Could not load categories: ' . mysql_error());
	    $first = true;
	    while ($category = mysql_fetch_assoc($categories))
	    {
	      $month_name = date('F', mktime(0, 0, 0, $category['month'], 1));
	      if (!$first)
		echo ', ';
              echo "{$category['name']} ($month_name {$category['year']})";
	      $first = false;
	    }
?>
        </td>
      </tr>
<?
        }
?>
    </table>
  </body>
</html>
