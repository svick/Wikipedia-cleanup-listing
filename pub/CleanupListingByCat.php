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

        $project_name_sql = mysql_real_escape_string($project_name);
        $project_name_human = str_replace('_', ' ', $project_name);

        $sql = "SELECT projects.id AS id, projects.is_wikiproject AS is_wikiproject, runs.id AS run_id, runs.time AS time
                FROM projects
                JOIN runs ON projects.last_run_id = runs.id
                WHERE name = '$project_name_sql'";
        $project = mysql_fetch_assoc(mysql_query($sql,$con))
                or die('Could not select project: ' . mysql_error());
        $project_id = $project['id'];
        $run_id = $project['run_id'];
        $run_time = $project['time'];
        $is_wikiproject = $project['is_wikiproject'];
        if ($is_wikiproject)
        {
            $project_name = "WikiProject_$project_name";
            $project_name_human = "WikiProject $project_name_human";
        }

        $table_writer = TableWriterFactory::Create($_GET['format']);
        $table_writer->WriteHeader("Cleanup listing for $project_name_human");
        $link = $table_writer->FormatWikiLink("Wikipedia:$project_name", "$project_name_human");
        $table_writer->WriteText("This is a cleanup listing for $link generated on " . date('j F Y, G:i:s e', strtotime($run_time)) . ".");

        $sql = "SELECT categories.name AS name, COUNT(*) AS count
                FROM categories
                JOIN articles on categories.article_id = articles.id
                WHERE articles.run_id = $run_id
                AND articles.project_id = $project_id
                GROUP BY categories.name";
        $sections_query = mysql_query($sql,$con)
                or die('Could not select sections: ' . mysql_error());

        while($section = mysql_fetch_assoc($sections_query))
            $sections[] = $section;

        $table_writer->WriteTocHeader();
        foreach ($sections as $section)
            $table_writer->WriteTocEntry($section['name'], "({$section['count']})");
        $table_writer->WriteTocFooter();

        foreach ($sections as $section)
        {
            $table_writer->WriteSection($section['name']);
            $table_writer->WriteTableHeader(array(
                    new Column('Article'),
                    new Column('Importance'),
                    new Column('Class'),
                    new Column('Month'),
                    new Column('Categories')));

            $sql = "SELECT DISTINCT id, article, importance, class, (
                        SELECT COALESCE(CONCAT(MONTHNAME(CONCAT(year, '-', month, '-01')), ' ', year), year)
                        FROM categories AS c
                        WHERE c.article_id = articles.id
                        AND c.name = '{$section['name']}'
                        ORDER BY year, month
                        LIMIT 1
                    ) AS month
                    FROM articles
                    JOIN categories ON articles.id = categories.article_id
                    WHERE run_id = $run_id
                    AND project_id = $project_id
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
                  $month_name = $category['month'] == 0 ? '' : date('F', mktime(0, 0, 0, $category['month'], 1)) . ' ';
                  $categories[] = "{$category['name']} ($month_name{$category['year']})";
                }

                $table_writer->WriteRow(array(
                  $table_writer->FormatLink("http://en.wikipedia.org/wiki/{$article['article']}", str_replace('_', ' ', $article['article'])),
                  $article['importance'],
                  $article['class'],
                  $article['month'],
                  implode(', ', $categories)
                ));
        
            }

            $table_writer->WriteTableFooter();
        }

    $table_writer->WriteFooter();
?>
