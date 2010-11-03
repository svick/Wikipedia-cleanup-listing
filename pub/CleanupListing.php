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

        $project_name = mysql_real_escape_string($_GET['project']);

        if ($project_name == null)
                die('Project was not set.');

        $sql = "SELECT id, time FROM runs ORDER BY id DESC LIMIT 1";
        $run = mysql_fetch_assoc(mysql_query($sql,$con))
                or die('Could not select last run: ' . mysql_error());
        $run_id = $run['id'];
        $run_time = $run['time'];

        $sql = "SELECT id FROM projects WHERE name = '$project_name'";
        $project = mysql_fetch_assoc(mysql_query($sql,$con))
                or die('Could not select project: ' . mysql_error());
        $project_id = $project['id'];

        $table_writer = TableWriterFactory::Create($_GET['format']);
        $table_writer->WriteHeader("Cleanup listing for WikiProject $project_name");
        $table_writer->WriteText("This is a cleanup listing for <a href=\"http://en.wikipedia.org/wiki/Wikipedia:WikiProject_$project_name\">WikiProject $project_name</a> generated on " . date('j F Y, G:i:s e', strtotime($run_time)) . ".");
        $table_writer->WriteTableHeader(array(
                new Column('Article', true),
                new Column('Importance', true),
                new Column('Class', true),
                new Column('Count', true),
                new Column('Categories')));

        $sort = mysql_real_escape_string($_GET['sort']);
        if ($sort)
                $sort = strtolower($sort);
        else
                $sort = 'article';

        if ($sort == 'count')
                $sort = "$sort DESC";

        $sql = "SELECT id, article, importance, class, (SELECT COUNT(*) FROM categories WHERE articles.id = categories.article_id) AS count
                FROM articles
                WHERE run_id = $run_id
                AND project_id = $project_id
                ORDER BY $sort";
        $articles = mysql_query($sql,$con)
          or die('Could not load articles: ' . mysql_error());

        while ($article = mysql_fetch_assoc($articles))
        {
            $sql = "SELECT name, month, year
                    FROM categories
                    WHERE article_id = {$article['id']}";
            $category_rows = mysql_query($sql,$con)
              or die('Could not load categories: ' . mysql_error());
            $categories = array();
            while ($category = mysql_fetch_assoc($category_rows))
            {
              $month_name = date('F', mktime(0, 0, 0, $category['month'], 1));
              $categories[] = "{$category['name']} ($month_name {$category['year']})";
            }

            $table_writer->WriteRow(array(
              $table_writer->FormatLink("http://en.wikipedia.org/wiki/{$article['article']}", str_replace('_', ' ', $article['article'])),
              $article['importance'],
              $article['class'],
              $article['count'],
              implode(', ', $categories)
            ));
        }

        $table_writer->WriteTableFooter();
        $table_writer->WriteFooter();
?>
