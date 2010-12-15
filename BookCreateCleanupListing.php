<?php
//Svick
//Smallman12q
//PD December 2010
//Untested version
//Book Cleanup Listing
//Based on CreateCleanupListing.php

require_once 'pub/Settings.php';

//Login
$ts_pw = posix_getpwuid(posix_getuid());
$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/.my.cnf");
$con = mysql_connect('enwiki-p.userdb.toolserver.org', $ts_mycnf['user'], $ts_mycnf['password'])
        or die('Could not connect: ' . mysql_error());
$user_name = $ts_mycnf['user'];
unset($ts_mycnf, $ts_pw);

//Select english wikidb
mysql_select_db('enwiki_p', $con)
        or die('Could not select db: ' . mysql_error());

//userdb
$user_db = "u_${user_name}_bookcleanup";//book cleanup
$sql = "CREATE DATABASE IF NOT EXISTS $user_db";
mysql_query($sql,$con)
        or die('Could not create database: ' . mysql_error());

//books-  book name & id
$sql = "CREATE TABLE IF NOT EXISTS $user_db.books(
                    id INT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL
                    pageid INT(8) UNSIGNED NULL
                )";
mysql_query($sql,$con)
        or die('Could not create books table: ' . mysql_error());

//add books which aren't in db
$sql = "INSERT INTO $user_db.books (name, pageid)
        SELECT page_title, page_id
                WHERE page_title NOT IN
                    (SELECT name FROM $user_db.books)
                      AND page_namespace = 108
        FROM page";
mysql_query($sql,$con)
        or die('Could not add books: ' . mysql_error());

//runs table
$sql = "CREATE TABLE IF NOT EXISTS $user_db.bookruns(
                    id INT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    book_id INT(8) UNSIGNED NOT NULL,
                    total_articles INT(8) UNSIGNED NULL,
                    FOREIGN KEY (book_id) REFERENCES books(id)
                )";
mysql_query($sql,$con)
        or die('Could not create runs table: ' . mysql_error());

//articles table
$sql = "CREATE TABLE IF NOT EXISTS $user_db.bookarticles(
                    id INT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    articleid INT(8) UNSIGNED,
                    talkid INT(8) UNSIGNED,
                    article VARCHAR(255),
                    run_id INT(8) UNSIGNED,
                    FOREIGN KEY (run_id) REFERENCES bruns(id)
                )";
mysql_query($sql,$con)
        or die('Could not create articles table: ' . mysql_error());


//categories table
$sql = "CREATE TABLE IF NOT EXISTS $user_db.bookcategories(
                    name VARCHAR(255) NOT NULL,
                    month TINYINT(2) UNSIGNED NULL,
                    year YEAR NULL,
                    article_id INT(8) UNSIGNED NOT NULL,
                    FOREIGN KEY (article_id) REFERENCES bookarticles(id)
                )";
mysql_query($sql,$con)
        or die('Could not create categories table: ' . mysql_error());


//Select only those which havne't run in a week
$sql = "SELECT DISTINCT id, pageid, name
                FROM $user_db.books
                LEFT JOIN $user_db.bookruns
                    ON books.id = bookruns.id
                    AND DATEDIFF(NOW(), time) < 7
                ";
$books = mysql_query($sql,$con)
        or die('Could not select books: '. mysql_error());

//Actual comparison
while ($book = mysql_fetch_assoc($books)) {
    $book_pageid = $book['pageid'];
    $book_name = $book['name'];
    $book_id = $book['id'];

    echo "Processing Book $book_name.\n";

    $sql = "INSERT INTO $user_db.bookruns (book_id) VALUE ($book_id)";
    mysql_query($sql,$con)
            or die('Could not insert new run: ' . mysql_error());

    $run_id = mysql_insert_id()
        or die('Could not get run id: ' . mysql_error());

    //Load article names and ids from book
    $sql = "INSERT INTO $user_db.bookarticles (articleid, article, run_id)
            SELECT DISTINCT page.page_id, page.page_title, $run_id
            FROM pagelinks INNER JOIN page
                ON page.page_title = pagelinks.pl_title
            WHERE pagelinks.pl_from = $book_id
                AND pagelinks.pl_namespace =0";
    mysql_query($sql,$con)
            or die('Could not load book '.$book_name." articles: ". mysql_error());

    $sql = "SELECT COUNT(*)
                    FROM $user_db.barticles
                    WHERE run_id = $run_id";
    $count = mysql_result(mysql_query($sql,$con), 0)
            or die('Could not load book '.$book_name." count: ". mysql_error());

    /*
    if ($count == 0) {
        $categoryarticles = mysql_real_escape_string("WikiProject_${cat_name}_articles");
        $sql = "
                  INSERT INTO $user_db.articles
                  (
                      articleid,
                      article,
                      run_id
                  )
                  SELECT article.page_id, talk.page_id, article.page_title, $run_id
                  FROM page AS article
                  JOIN page AS talk ON article.page_title = talk.page_title
                  JOIN categorylinks AS cl ON talk.page_id = cl.cl_from
                  WHERE cl.cl_to = '$categoryarticles'
                  AND article.page_namespace = 0
                  AND talk.page_namespace = 1";
        mysql_query($sql,$con)
                or die('Could not load WikiProject '.$project_name." articles: ". mysql_error());

        $sql = "SELECT COUNT(*)
                      FROM $user_db.articles
                      WHERE run_id = $run_id";
        $count = mysql_result(mysql_query($sql,$con), 0);

        if ($count == 0) {
            $categoryarticles = mysql_real_escape_string($cat_name);
            $sql = "
                    INSERT INTO $user_db.articles
                    (
                        articleid,
                        talkid,
                        article,
                        run_id
                    )
                    SELECT article.page_id, talk.page_id, article.page_title, $run_id
                    FROM page AS article
                    JOIN page AS talk ON article.page_title = talk.page_title
                    JOIN categorylinks AS cl ON talk.page_id = cl.cl_from
                    WHERE cl.cl_to = '$categoryarticles'
                    AND article.page_namespace = 0
                    AND talk.page_namespace = 1";
            mysql_query($sql,$con)
                    or die('Could not load WikiProject '.$project_name." articles: ". mysql_error());

            $sql = "SELECT COUNT(*)
                        FROM $user_db.articles
                        WHERE run_id = $run_id";
            $count = mysql_result(mysql_query($sql,$con), 0);

            if ($count == 0) {
                echo "Could not get articles for WikiProject $project_name.\n";
                continue;
            }
        }
    }
    */

    //up to here

    $sql = "UPDATE $user_db.runs
                    SET total_articles = $count
                    WHERE id = $run_id";
    mysql_query($sql, $con)
        or die("Could not update runs :". mysql_error());

    //Compare each monthly countercat
    foreach($monthlycleanupcountercats as $countercat) {
        $thecountercat = str_replace(' ', '_', "$countercat from %");

        //insert into categories table
        $sql = "INSERT INTO $user_db.bookcategories (name, month, year, article_id)
                        SELECT
                          '$countercat',
                          MONTH(STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(cl_to, '_', -2), '_', 1), '%M')),
                          SUBSTRING_INDEX(cl_to, '_', -1),
                          $user_db.bookarticles.id
                        FROM $user_db.bookarticles INNER JOIN categorylinks
                            ON categorylinks.cl_from = $user_db.bookarticles.articleid
                        WHERE $user_db.bookarticles.run_id = $run_id
                            AND categorylinks.cl_to LIKE '$thecountercat'";
        mysql_query($sql,$con)
                or die("Could not load monthly category $countercat for book $book_name: ". mysql_error());
    }//countercat

    //Compare each NONmonthly countercat
    foreach($cleanupcountercats as $countercat) {
        $thecountercat = str_replace(' ', '_', $countercat);

        //insert into categories table
        $sql = "INSERT INTO $user_db.bcategories (name, month, year, article_id)
                        SELECT
                          '$countercat',
                          NULL,
                          NULL,
                          a.id
                        FROM $user_db.bookarticles a
                        JOIN categorylinks cl ON cl.cl_from = a.articleid
                        WHERE a.run_id = $run_id
                        AND cl.cl_to LIKE '$thecountercat'";
        mysql_query($sql,$con)
                or die("Could not load non-monthly category $countercat for $book_name: ". mysql_error());
    }//countercat

    //delete "clean" articles
    $sql = "DELETE FROM $user_db.articles
                    WHERE run_id = $run_id
                    AND id NOT IN (
                        SELECT article_id
                        FROM $user_db.bookcategories)";
    mysql_query($sql,$con)
            or die ('Could not delete "clean" articles: '. mysql_error());

}//wikiproject

//close connection
mysql_close($con)
        or die('Could not close connection to db: ' . mysql_error());
?>
