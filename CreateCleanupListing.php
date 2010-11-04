<?php
//Smallman12q
//Svick
//PD October 2010
//Version .1
//For Cleanup Listing

        require_once 'pub/Settings.php';

        $ts_pw = posix_getpwuid(posix_getuid());
        $ts_mycnf = parse_ini_file($ts_pw['dir'] . "/.my.cnf");
        $con = mysql_connect('enwiki-p.userdb.toolserver.org', $ts_mycnf['user'], $ts_mycnf['password'])
                or die('Could not connect: ' . mysql_error()); 
        $user_name = $ts_mycnf['user'];
        unset($ts_mycnf, $ts_pw);

        mysql_select_db('enwiki_p', $con)
                or die('Could not select db: ' . mysql_error());

        $user_db = "u_${user_name}_cleanup";

        $sql = "CREATE DATABASE IF NOT EXISTS $user_db";
        mysql_query($sql,$con)
                or die('Could not create database: ' . mysql_error());

        $sql = "CREATE TABLE IF NOT EXISTS $user_db.projects(
                    id INT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    active BOOL DEFAULT 1 NOT NULL,
                    lowercase_cats BOOL NOT NULL
                )";
        mysql_query($sql,$con)
                or die('Could not create projects table: ' . mysql_error());

        $sql = "CREATE TABLE IF NOT EXISTS $user_db.runs(
                    id INT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                )";
        mysql_query($sql,$con)
                or die('Could not create runs table: ' . mysql_error());

        $classes_string = "'" . implode("', '", $classes) . "'";
        $importances_string = "'" . implode("', '", $importances) . "'";

        $sql = "CREATE TABLE IF NOT EXISTS $user_db.articles(
                    id INT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    articleid INT(8) UNSIGNED,
                    talkid INT(8) UNSIGNED,
                    article VARCHAR(255),
                    importance ENUM($importances_string),
                    class ENUM($classes_string),
                    taskforce VARCHAR(255),
                    project_id INT(8) UNSIGNED,
                    run_id INT(8) UNSIGNED,
                    FOREIGN KEY (project_id) REFERENCES projects(id),
                    FOREIGN KEY (run_id) REFERENCES runs(id)
                )";
        mysql_query($sql,$con)
                or die('Could not create articles table: ' . mysql_error());

        $sql = "CREATE TABLE IF NOT EXISTS $user_db.categories(
                    name VARCHAR(255) NOT NULL,
                    month TINYINT(2) UNSIGNED NOT NULL,
                    year YEAR NOT NULL,
                    article_id INT(8) UNSIGNED NOT NULL,
                    FOREIGN KEY (article_id) REFERENCES articles(id)
                )";
        mysql_query($sql,$con)
                or die('Could not create categories table: ' . mysql_error());

        $sql = "INSERT INTO $user_db.runs () VALUE ()";
        mysql_query($sql,$con)
                or die('Could not insert new run: ' . mysql_error());

        $run_id = mysql_insert_id();

        $sql = "SELECT id, name, lowercase_cats
                FROM $user_db.projects
                WHERE active = 1";
        $projects = mysql_query($sql,$con)
                or die('Could not select projects: '. mysql_error());

        while ($project = mysql_fetch_assoc($projects))
        {
            $project_id = $project['id'];
            $project_name = $project['name'];
            $lowercase_cats = $project['lowercase_cats'];

            echo "Processing WikiProject $project_name.\n";

            //Load articles and pageid from WikiProject
            $categoryarticles = mysql_real_escape_string("${project_name}_articles_by_quality");
            $sql = "
                INSERT INTO $user_db.articles
                (
                    articleid,
                    talkid,
                    article,
                    project_id,
                    run_id
                )
                SELECT article.page_id, talk.page_id, article.page_title, $project_id, $run_id
                FROM page AS article
                JOIN page AS talk ON article.page_title = talk.page_title
                JOIN categorylinks AS cl1 ON talk.page_id = cl1.cl_from
                JOIN page AS cat ON cl1.cl_to = cat.page_title
                JOIN categorylinks AS cl2 ON cat.page_id = cl2.cl_from
                WHERE cl2.cl_to = '$categoryarticles'
                AND article.page_namespace = 0
                AND talk.page_namespace = 1
                AND cat.page_namespace = 14";
            mysql_query($sql,$con)
                    or die('Could not load WikiProject '.$wikiproject." articles: ". mysql_error());

            foreach($cleanupcountercats as $countercat)
            {
                echo "Processing $countercat\n";

                for($year = 2004; $year <= $finalyear; $year +=1)
                {
                    for($month = 1; $month <= 12; $month +=1)
                    {
                        //after final month break
                        if(year == $finalyear)
                        {
                            if($month > $finalmonth)
                                break 1;
                        }

                        $month_name = date('F', mktime(0, 0, 0, $month, 1));
                        $thecountercat = str_replace(' ', '_', "$countercat from $month_name $year");

                        //insert into categories table
                        $sql = "INSERT INTO $user_db.categories (name, month, year, article_id)
                                SELECT '$countercat', '$month', $year, a.id
                                FROM $user_db.articles a
                                JOIN categorylinks cl ON cl.cl_from = a.articleid
                                WHERE a.project_id = $project_id
                                AND a.run_id = $run_id
                                AND cl.cl_to = '$thecountercat'";
                        mysql_query($sql,$con)
                                or die("Could not load category $thecountercat for WikiProject $project_name: ". mysql_error());
                    }//month
                }//year
            }//countercat

            echo "Deleting \"clean\" articles.\n";

            //delete "clean" articles
            $sql = "DELETE FROM $user_db.articles
                    WHERE run_id = $run_id
                    AND project_id = $project_id
                    AND id NOT IN (
                        SELECT article_id
                        FROM $user_db.categories)";
            mysql_query($sql,$con)
                    or die ('Could not delete "clean" articles: '. mysql_error());

            $project_part = $lowercase_cats ? strtolower($project_name) : $project_name;

            echo "Processing importances\n";

            //Set importance
            foreach($importances as $importance)
            {

                $theimportance = "${importance}-importance_${project_part}_articles";
                $sql = "UPDATE $user_db.articles a
                        SET a.importance = '$importance'
                        WHERE a.project_id = $project_id
                        AND a.run_id = $run_id
                        AND a.talkid IN
                          (SELECT cl.cl_from
                           FROM categorylinks cl
                           WHERE cl.cl_to = '$theimportance')";
                mysql_query($sql,$con)
                        or die('Could not load WikiProject '.$wikiproject." importance: ". mysql_error());
            }

            echo "Processing classes\n";

            //Set Class
            foreach($classes as $class)
            {
                if ($class == 'Unassessed')
                  $theclass = "${class}_${project_part}_articles";
                else
                  $theclass = "${class}-Class_${project_part}_articles";

                $sql = "UPDATE $user_db.articles a
                        SET a.class = '$class'
                        WHERE a.project_id = $project_id
                        AND a.run_id = $run_id
                        AND a.talkid IN
                          (SELECT cl.cl_from
                           FROM categorylinks cl
                           WHERE cl.cl_to = '$theclass')";
            mysql_query($sql,$con)
                    or die('Could not load WikiProject '.$wikiproject." quality class: ". mysql_error());
            }
        }//wikiproject

        //close connection
        mysql_close($con)
                or die('Could not close connection to db: ' . mysql_error());
?>
