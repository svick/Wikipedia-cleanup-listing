<?php
//Smallman12q
//Svick
//Licensed under Simplified BSD license
//Version .1

abstract class CleanupListingBase
{
  protected $settings;
  protected $con;
  protected $user_name;
  protected $user_db;
  protected $database_name;

  protected $groups_additional_columns;
  protected $article_additional_columns;

  protected $current_group_id;
  protected $current_group_name;
  protected $current_run_id;

  protected function __construct($settings)
  {
    $this->settings = $settings;

    $ts_pw = posix_getpwuid(posix_getuid());
    $ts_mycnf = parse_ini_file($ts_pw['dir'] . "/.my.cnf");
    $this->con = mysql_connect('enwiki-p.userdb.toolserver.org', $ts_mycnf['user'], $ts_mycnf['password'])
      or die('Could not connect: ' . mysql_error()); 
    $this->user_name = $ts_mycnf['user'];

    $this->user_db = "u_{$this->user_name}_{$this->database_name}";

    mysql_select_db('enwiki_p', $this->con)
      or die('Could not select db: ' . mysql_error());
  }

  function __destruct()
  {
    mysql_close($this->con)
      or die('Could not close connection to db: ' . mysql_error());
  }

  protected function CreateDatabase()
  {
    $sql = "CREATE DATABASE IF NOT EXISTS $this->user_db";
    mysql_query($sql,$this->con)
      or die('Could not create database: ' . mysql_error());

    $sql = "CREATE TABLE IF NOT EXISTS $this->user_db.groups(
              id INT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              name VARCHAR(255) NOT NULL,
              active BOOL DEFAULT 1 NOT NULL,
              force_create BOOL DEFAULT 0 NOT NULL
              $this->groups_additional_columns
            )";
    mysql_query($sql, $this->con)
      or die('Could not create groups table: ' . mysql_error());

    $sql = "CREATE TABLE IF NOT EXISTS $this->user_db.runs(
              id INT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              group_id INT(8) UNSIGNED NOT NULL,
              total_articles INT(8) UNSIGNED NULL,
              finished TINYINT(1) DEFAULT 0 NOT NULL,
              FOREIGN KEY (group_id) REFERENCES groups(id)
            )";
    mysql_query($sql, $this->con)
      or die('Could not create runs table: ' . mysql_error());

    $sql = "CREATE TABLE IF NOT EXISTS $this->user_db.articles(
              id INT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              articleid INT(8) UNSIGNED,
              talkid INT(8) UNSIGNED,
              article VARCHAR(255),
              run_id INT(8) UNSIGNED,
              FOREIGN KEY (run_id) REFERENCES runs(id)
              $this->article_additional_columns
            )";
    mysql_query($sql, $this->con)
      or die('Could not create articles table: ' . mysql_error());

    $sql = "CREATE TABLE IF NOT EXISTS $this->user_db.categories(
              name VARCHAR(255) NOT NULL,
              month TINYINT(2) UNSIGNED NULL,
              year YEAR NULL,
              article_id INT(8) UNSIGNED NOT NULL,
              FOREIGN KEY (article_id) REFERENCES articles(id)
            )";
    mysql_query($sql, $this->con)
      or die('Could not create categories table: ' . mysql_error());
  }

  protected abstract function GetGroupsToUpdate();
  protected abstract function ReadAdditionalGroupColumns($group);
  protected abstract function LoadArticles();
  protected function AdditionalGroupProcessing()
  { }

  public function Update()
  {
    $this->CreateDatabase();

    $groups = $this->GetGroupsToUpdate();
    while ($group = mysql_fetch_assoc($groups))
    {
      $this->current_group_id = $group['id'];
      $this->current_group_name = $group['name'];
      $this->ReadAdditionalGroupColumns($group);

      echo "Processing $this->current_group_name.\n";

      $sql = "INSERT INTO $user_db.runs (group_id) VALUE ($group_id)";
      mysql_query($sql,$con)
        or die('Could not insert new run: ' . mysql_error());

      $this->current_run_id = mysql_insert_id();

      if (!$this->LoadArticles())
        continue;

      foreach($settings->monthlycleanupcountercats as $countercat)
      {
        $thecountercat = str_replace(' ', '\_', "$countercat from %");

        //insert into categories table
        $sql = "INSERT INTO $this->user_db.categories (name, month, year, article_id)
                SELECT
                  '$countercat',
                  MONTH(STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(cl_to, '_', -2), '_', 1), '%M')),
                  SUBSTRING_INDEX(cl_to, '_', -1),
                  a.id
                FROM $this->user_db.articles a
                JOIN categorylinks cl ON cl.cl_from = a.articleid
                WHERE a.run_id = $this->current_run_id
                AND cl.cl_to LIKE '$thecountercat'";
        mysql_query($sql, $this->con)
          or die("Could not load category $countercat for $this->current_group_name: ". mysql_error());
      }

      foreach(array_merge($settings->cleanupcountercats, $settings->monthlycleanupcountercats) as $countercat)
      {
        $thecountercat = str_replace(' ', '\_', $countercat);

        //insert into categories table
        $sql = "INSERT INTO $this->user_db.categories (name, month, year, article_id)
                SELECT
                  '$countercat',
                  NULL,
                  NULL,
                  a.id
                FROM $this->user_db.articles a
                JOIN categorylinks cl ON cl.cl_from = a.articleid
                WHERE a.run_id = $this->current_run_id
                AND cl.cl_to LIKE '$thecountercat'";
        mysql_query($sql, $this->con)
          or die("Could not load category $countercat for $this->current_group_name: ". mysql_error());
      }

      //delete "clean" articles
      $sql = "DELETE FROM $this->user_db.articles
              WHERE run_id = $this->current_run_id
              AND id NOT IN (
                  SELECT article_id
                  FROM $this->user_db.categories)";
      mysql_query($sql, $this->con)
        or die ('Could not delete "clean" articles: '. mysql_error());

      $this->AdditionalGroupProcessing();

      $sql = "UPDATE $user_db.groups
              SET force_create = 0
              WHERE id = $this->current_group_id";
      mysql_query($sql, $this->con)
        or die("Could not reset forcing creating for $this->current_group_name: " . mysql_error());
    }
  }
}

?>
