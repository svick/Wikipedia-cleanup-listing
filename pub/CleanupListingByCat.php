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

        $table_writer = TableWriterFactory::Create($_GET['format']);
        $table_writer->WriteHeader("Cleanup listing for WikiProject $project_name");
        $table_writer->WriteText("This is a cleanup listing for <a href=\"http://en.wikipedia.org/wiki/Wikipedia:WikiProject_$project_name\">WikiProject $project_name</a> generated on " . date('j F Y, G:i:s e', strtotime($run_time)) . ".");

        $sql = "SELECT DISTINCT categories.name AS name
                FROM categories
                JOIN articles on categories.article_id = articles.id
                WHERE articles.run_id = $run_id";
        $sections = mysql_query($sql,$con)
                or die('Could not select sections: ' . mysql_error());

        while($section = mysql_fetch_assoc($sections))
        {
            $table_writer->WriteSection($section['name']);
            $table_writer->WriteTableHeader(array(
                    new Column('Article'),
                    new Column('Importance'),
                    new Column('Class'),
                    new Column('Categories')));

            $sql = "SELECT DISTINCT id, article, importance, class
                    FROM articles
                    JOIN categories ON articles.id = categories.article_id
                    WHERE run_id = $run_id
                    AND categories.name = '{$section['name']}'
                    ORDER BY article";
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
                  implode(', ', $categories)
                ));
        
            }

            $table_writer->WriteTableFooter();
        }

    $table_writer->WriteFooter();
?>
