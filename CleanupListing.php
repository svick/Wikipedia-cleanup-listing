<?php
//Smallman12q
//PD October 2010
//Version .1
//For Cleanup Listing
 
        $cleanupcountercats = array('Articles with invalid date parameter in template', 'Accuracy disputes', 'Articles lacking page references', 'BLP articles lacking sources', 'Unreferenced BLPs', 'Use British English', 'Category needed', 'Articles needing chemical formulas', 'Articles with broken or outdated citations', 'Wikipedia articles needing clarification', 'Wikipedia templates needing cleanup', 'Wikipedia pages needing cleanup', 'Wikipedia categories needing cleanup', 'Articles needing cleanup', 'Statements with common sense issues', 'Wikipedia articles with possible conflicts of interest', 'Wikipedia articles needing context', 'Wikipedia articles needing copy edit', 'Copied and pasted articles and sections', 'Articles containing potentially dated statements', 'Use dmy dates', 'Use mdy dates', 'Use ymd dates', 'Articles with dead external links', 'Dead-end pages', 'Deprecated templates', 'Articles with disputed statements', 'Use British (Oxford) English', 'Articles to be expanded', 'Articles to be expanded by month', 'Articles needing expert attention by month', 'Wikipedia external links cleanup', 'Articles that need to differentiate between fact and fiction', 'Articles sourced only by IMDB', 'Articles sourced by IMDB', 'Articles lacking in-text citations', 'Wikipedia introduction cleanup', 'Articles needing link rot cleanup', 'Articles to be merged', 'Articles with topics of unclear notability', 'NPOV disputes', 'Articles that may contain original research', 'Orphaned articles', 'Articles with minor POV problems', 'Wikipedia articles needing page number citations', 'Articles with peacock terms', 'Wikipedia articles with plot summary needing attention', 'Articles with a promotional tone', 'Articles with sections that need to be turned into prose', 'Articles to be pruned by month', 'Articles pruned by month', 'Articles slanted towards recent events', 'Recently revised', 'Articles needing additional references', 'Articles lacking reliable references', 'Wikipedia articles needing rewrite', 'Articles needing sections', 'Articles with excessive "see also" sections', 'Self-contradictory articles', 'Articles lacking sources', 'Wikipedia spam cleanup', 'Articles to be split', 'Article sections to be split', 'Articles that may be too long', 'Wikipedia articles needing style editing', 'Wikipedia articles that are too technical', 'Articles with trivia sections', 'Uncategorized stubs', 'Unreviewed new articles', 'Unreviewed new articles created via the Article Wizard', 'Articles with unsourced statements', 'Wikipedia articles in need of updating', 'Userspace drafts', 'Userspace drafts created via the Article Wizard', 'Wikipedia articles needing factual verification', 'Articles with weasel words', 'Articles with specifically marked weasel-worded phrases', 'Articles that need to be wikified', 'Wikipedia articles needing SmackBot clean up', 'Wikipedia articles needing cleanup', 'Articles needing the year an event occurred');
        $months = array("January", "February", "March", "April", "June", "July", "August", "September", "October", "November", "December");
        $years = range(2004, 2010);
        $classes = array("FA-Class", "A-Class",    "GA-Class", "B-Class", "C-Class", "Start-Class", "Stub-Class", "FL-Class", "List-Class","Unassessed");
        $importances = array("Top", "High", "Mid", "Low", "Unknown");
 
 
        $wikiprojects = array("Equine");//, "Films", "Food and drink");
 
 
        $ts_pw = posix_getpwuid(posix_getuid());
        $ts_mycnf = parse_ini_file($ts_pw['dir'] . "/.my.cnf");
        $con = mysql_connect('enwiki-p.userdb.toolserver.org', $ts_mycnf['user'], $ts_mycnf['password'])
                or die('Could not connect: ' . mysql_error()); 
        $user_table = "u_${ts_mycnf['user']}.articles";
        unset($ts_mycnf, $ts_pw);
 
        mysql_select_db('enwiki_p', $con)
                or die('Could not select db: ' . mysql_error());
 
        //foreach as opposed to while for legibility
        foreach($wikiprojects as $wikiproject)
        {
	    $sql = "DROP TABLE IF EXISTS $user_table";
	    mysql_query($sql,$con)
                    or die('Could not drop table: ' . mysql_error());
 
            //Create temporary table
            $sql = "CREATE TABLE $user_table(
                        articleid INT(8) UNSIGNED,
                        talkid INT(8) UNSIGNED,
                        article VARCHAR(255),
                        importance VARCHAR(7),
                        quality VARCHAR(5),
                        catnumber TINYINT UNSIGNED NOT NULL DEFAULT 0,
                        taskforce VARCHAR(255),
                        categories TEXT NOT NULL
                    )";
            mysql_query($sql,$con)
                    or die('Could not create table: ' . mysql_error());
 
            //Load articles and pageid from WikiProject
            $categoryarticles = "'WikiProject_".$wikiproject."_articles'";
            $sql = "
                INSERT INTO $user_table
                (
                    articleid,
		    talkid,
                    article
                )
                SELECT article.page_id as articleid, talk.page_id as talkid, article.page_title as article FROM page AS article
		        JOIN page AS talk ON article.page_title = talk.page_title
                        JOIN categorylinks AS cl1 ON talk.page_id = cl1.cl_from
                                WHERE cl1.cl_to = ".$categoryarticles."
				AND article.page_namespace = 0
				AND talk.page_namespace = 1";
            mysql_query($sql,$con)
                    or die('Could not load WikiProject '.$wikiproject." articles: ". mysql_error());
 
            echo "Processing importances\n";
 
            //Set importance
            foreach($importances as $importance)
            {
                //try both upper and lower
                $theimportance = $importance."-importance_".$wikiproject."_articles";
                //http://www.electrictoolbox.com/article/mysql/cross-table-update/
                $sql = "UPDATE $user_table a
                        SET a.importance = '".$importance."'"."
			WHERE a.talkid IN
			  (SELECT cl.cl_from
			   FROM categorylinks cl
                           WHERE cl.cl_to = '".$theimportance."')";
                mysql_query($sql,$con)
                        or die('Could not load WikiProject '.$wikiproject." importance: ". mysql_error());
 
                $theimportance = $importance."-importance_".strtolower($wikiproject)."_articles";
                    //lowercase
                $sql = "UPDATE $user_table a
                        SET a.importance = '".$importance."'"."
			WHERE a.talkid IN
			  (SELECT cl.cl_from
			   FROM categorylinks cl
                           WHERE cl.cl_to = '".$theimportance."')";
                mysql_query($sql,$con)
                        or die('Could not load WikiProject '.$wikiproject." importance: ". mysql_error());
            }
 
            echo "Processing classes\n";
 
            //Set Class
            foreach($classes as $class)
            {
                                //try both upper and lower - to do
                $theclass = $class."_".$wikiproject."_articles";
                $sql = "UPDATE $user_table a
                        SET a.quality = '".str_replace("-Class","",$class)."'"."
			WHERE a.talkid IN
			  (SELECT cl.cl_from
			   FROM categorylinks cl
                           WHERE cl.cl_to = '".$theclass."')";
            mysql_query($sql,$con)
                    or die('Could not load WikiProject '.$wikiproject." quality class: ". mysql_error());
 
            //lowercase
                $theclass = $class."_".strtolower($wikiproject)."_articles";
                $sql = "UPDATE $user_table a
                        SET a.quality = '".str_replace("-Class","",$class)."'"."
			WHERE a.talkid IN
			  (SELECT cl.cl_from
			   FROM categorylinks cl
                           WHERE cl.cl_to = '".$theclass."')";
            mysql_query($sql,$con)
                    or die('Could not load WikiProject '.$wikiproject." quality class: ". mysql_error());
 
            }
 
            foreach($cleanupcountercats as $countercat)
            {
 
            echo "Processing $countercat\n";
 
                //to do create table for each cat
 
                foreach($years as $year)
                {
                    foreach($months as $month)
                    {
                        $thecountercat = str_replace(' ', '_', $countercat." from ".$month." ".$year);
 
                        //update main table
                        $sql = "UPDATE $user_table a
                                SET a.categories = concat(categories, '".$countercat." (".$month." ".$year.")'),
                                    a.catnumber = a.catnumber + 1
			        WHERE a.articleid IN
			          (SELECT cl.cl_from
			           FROM categorylinks cl
                                   WHERE cl.cl_to = '".$thecountercat."')";
                        mysql_query($sql,$con)
                                or die('Could not load category '.$thecountercat.'for WikiProject '.$wikiproject.": ". mysql_error());
 
                    }//month
                }//year
            }//countercat
        }//wikiproject
 
        //close connection
            mysql_close($con)
                    or die('Could not close connection to db: ' . mysql_error());
 
 //print out to Wikipedia
 
        ?>
