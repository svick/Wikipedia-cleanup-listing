<?php
//Smallman12q
//Svick
//PD October 2010
//Version .1
//For Cleanup Listing

        $cleanupcountercats = array('Accuracy disputes', 'Articles lacking page references', 'BLP articles lacking sources', 'Unreferenced BLPs', 'Use British English', 'Category needed', 'Articles needing chemical formulas', 'Articles with broken or outdated citations', 'Wikipedia articles needing clarification', 'Wikipedia templates needing cleanup', 'Wikipedia pages needing cleanup', 'Wikipedia categories needing cleanup', 'Articles needing cleanup', 'Statements with common sense issues', 'Wikipedia articles with possible conflicts of interest', 'Wikipedia articles needing context', 'Wikipedia articles needing copy edit', 'Copied and pasted articles and sections', 'Articles containing potentially dated statements', 'Use dmy dates', 'Use mdy dates', 'Use ymd dates', 'Articles with dead external links', 'Dead-end pages', 'Deprecated templates', 'Articles with disputed statements', 'Use British (Oxford) English', 'Articles to be expanded', 'Articles to be expanded by month', 'Articles needing expert attention by month', 'Wikipedia external links cleanup', 'Articles that need to differentiate between fact and fiction', 'Articles sourced only by IMDB', 'Articles sourced by IMDB', 'Articles lacking in-text citations', 'Wikipedia introduction cleanup', 'Articles needing link rot cleanup', 'Articles to be merged', 'Articles with topics of unclear notability', 'NPOV disputes', 'Articles that may contain original research', 'Orphaned articles', 'Articles with minor POV problems', 'Wikipedia articles needing page number citations', 'Articles with peacock terms', 'Wikipedia articles with plot summary needing attention', 'Articles with a promotional tone', 'Articles with sections that need to be turned into prose', 'Articles to be pruned by month', 'Articles pruned by month', 'Articles slanted towards recent events', 'Recently revised', 'Articles needing additional references', 'Articles lacking reliable references', 'Wikipedia articles needing rewrite', 'Articles needing sections', 'Articles with excessive "see also" sections', 'Self-contradictory articles', 'Articles lacking sources', 'Wikipedia spam cleanup', 'Articles to be split', 'Article sections to be split', 'Articles that may be too long', 'Wikipedia articles needing style editing', 'Wikipedia articles that are too technical', 'Articles with trivia sections', 'Uncategorized stubs', 'Unreviewed new articles', 'Unreviewed new articles created via the Article Wizard', 'Articles with unsourced statements', 'Wikipedia articles in need of updating', 'Userspace drafts', 'Userspace drafts created via the Article Wizard', 'Wikipedia articles needing factual verification', 'Articles with weasel words', 'Articles with specifically marked weasel-worded phrases', 'Articles that need to be wikified', 'Wikipedia articles needing SmackBot clean up', 'Wikipedia articles needing cleanup', 'Articles needing the year an event occurred');
        $months = array("January", "February", "March", "April", "June", "July", "August", "September", "October", "November", "December");
        $years = range(2004, 2010);
        $classes = array("FA-Class", "A-Class",    "GA-Class", "B-Class", "C-Class", "Start-Class", "Stub-Class", "FL-Class", "List-Class","Unassessed");
        $importances = array("Top", "High", "Mid", "Low", "Unknown");

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

        $sql = "CREATE TABLE IF NOT EXISTS $user_db.articles(
                    id INT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    articleid INT(8) UNSIGNED,
                    talkid INT(8) UNSIGNED,
                    article VARCHAR(255),
                    importance VARCHAR(7),
                    quality VARCHAR(5),
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
                    month VARCHAR(10) NOT NULL,
                    year YEAR NOT NULL,
                    article_id INT(8) UNSIGNED NOT NULL,
                    FOREIGN KEY (article_id) REFERENCES articles(id)
                )";
        mysql_query($sql,$con)
                or die('Counld not create categories table: ' . mysql_error());

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

            //Load articles and pageid from WikiProject
            $categoryarticles = "'WikiProject_${project_name}_articles'";
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
                JOIN categorylinks AS cl ON talk.page_id = cl.cl_from
                WHERE cl.cl_to = $categoryarticles
                AND article.page_namespace = 0
                AND talk.page_namespace = 1";
            mysql_query($sql,$con)
                    or die('Could not load WikiProject '.$wikiproject." articles: ". mysql_error());

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
                $theclass = "${class}_${project_part}_articles";
                $sql = "UPDATE $user_db.articles a
                        SET a.quality = '".str_replace("-Class","",$class)."'"."
                        WHERE a.project_id = $project_id
                        AND a.run_id = $run_id
                        AND a.talkid IN
                          (SELECT cl.cl_from
                           FROM categorylinks cl
                           WHERE cl.cl_to = '".$theclass."')";
            mysql_query($sql,$con)
                    or die('Could not load WikiProject '.$wikiproject." quality class: ". mysql_error());
            }

            foreach($cleanupcountercats as $countercat)
            {

                echo "Processing $countercat\n";

                foreach($years as $year)
                {
                    foreach($months as $month)
                    {
                        $thecountercat = str_replace(' ', '_', "$countercat from $month $year");

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
        }//wikiproject

        //close connection
            mysql_close($con)
                    or die('Could not close connection to db: ' . mysql_error());

 //print out to Wikipedia

        ?>
