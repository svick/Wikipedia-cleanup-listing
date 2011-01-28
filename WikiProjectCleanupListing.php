<?php
//Smallman12q
//Svick
//Licensed under Simplified BSD license
//Version .1

require_once 'CleanupListingBase.php';

class WikiProjectCleanupListing extends CleanupListingBase
{
  protected $database_name = 'cleanup';
  protected $groups_additional_columns = ',
    cat_name VARCHAR(255) NULL,
    is_wikiproject BOOL DEFAULT 1 NOT NULL';

  protected $current_cat_name;

  public function __construct($settings)
  {
    parent::__construct($settings);

    $classes_string = "'" . implode("', '", $this->settings->classes) . "'";
    $importances_string = "'" . implode("', '", $this->settings->importances) . "'";

    $this->article_additional_columns = ",
      talkid INT(8) UNSIGNED,
      importance ENUM($importances_string),
      class ENUM($classes_string)";
  }

  protected function GetGroupsToUpdate()
  {
    $sql = "SELECT DISTINCT groups.id AS id, name, cat_name
            FROM $this->user_db.groups
            LEFT JOIN $this->user_db.runs
              ON groups.id = runs.group_id
              AND DATEDIFF(NOW(), time) < 7
            WHERE active = 1
            AND (time IS NULL
               OR force_create = 1)";
    return mysql_query($sql, $this->con)
            or die('Could not select groups: '. mysql_error());
  }

  protected function ReadAdditionalGroupColumns($project)
  {
    $this->current_cat_name = $project['cat_name'] ? $project['cat_name'] : $project['name'];
  }

  protected function GetInsertFromCategorySql($categoryarticles)
  {
    return
      "INSERT INTO $this->user_db.articles
       (
         articleid,
         talkid,
         article,
         run_id
       )
       SELECT article.page_id, talk.page_id, article.page_title, $this->current_run_id
       FROM page AS article
       JOIN page AS talk ON article.page_title = talk.page_title
       JOIN categorylinks AS cl ON talk.page_id = cl.cl_from
       WHERE cl.cl_to = '$categoryarticles'
       AND article.page_namespace = 0
       AND talk.page_namespace = 1";
  }

  protected function LoadArticles()
  {
    $categoryarticles = mysql_real_escape_string(ucfirst("${cat_name}_articles_by_quality"));
    $sql = "
        INSERT INTO $this->user_db.articles
        (
            articleid,
            talkid,
            article,
            run_id
        )
        SELECT DISTINCT article.page_id, talk.page_id, article.page_title, $this->current_run_id
        FROM page AS article
        JOIN page AS talk ON article.page_title = talk.page_title
        JOIN categorylinks AS cl1 ON talk.page_id = cl1.cl_from
        JOIN page AS cat ON cl1.cl_to = cat.page_title
        JOIN categorylinks AS cl2 ON cat.page_id = cl2.cl_from
        WHERE cl2.cl_to = '$categoryarticles'
        AND article.page_namespace = 0
        AND talk.page_namespace = 1
        AND cat.page_namespace = 14";
    mysql_query($sql, $this->con)
      or die('Could not load WikiProject '.$this->current_group_name." articles: ". mysql_error());

    $count = $this->GetArticleCount();
    if ($count == 0)
    {
      $categoryarticles = mysql_real_escape_string("WikiProject_{$this->current_cat_name}_articles");
      $sql = $this->GetInsertFromCategorySql($categoryarticles);
      mysql_query($sql, $this->con)
        or die('Could not load WikiProject '.$this->current_group_name." articles: ". mysql_error());

      $count = $this->GetArticleCount();
      if ($count == 0)
      {
        $categoryarticles = mysql_real_escape_string($cat_name);
        $sql = $this->GetInsertFromCategorySql($categoryarticles);
        mysql_query($sql, $this->con)
          or die('Could not load WikiProject '.$this->current_group_name." articles: ". mysql_error());

        $count = $this->GetArticleCount();
        if ($count == 0)
        {
          echo "Could not get articles for WikiProject $this->current_group_name.\n";
          return false;
        }
      }
    }

    $sql = "UPDATE $this->user_db.runs
            SET total_articles = $count
            WHERE id = $this->current_run_id";
    mysql_query($sql, $this->con);

    return true;
  }

  protected function AdditionalGroupProcessing()
  {
    //Set importance
    foreach($this->settings->importances as $importance)
    {
        $theimportance = mysql_real_escape_string("${importance}-importance_{$this->current_cat_name}_articles");
        $sql = "UPDATE $this->user_db.articles a
                SET a.importance = '$importance'
                WHERE a.run_id = $this->current_run_id
                AND a.talkid IN
                  (SELECT cl.cl_from
                   FROM categorylinks cl
                   WHERE cl.cl_to = '$theimportance')";
        mysql_query($sql, $this->con)
          or die("Could not load WikiProject $this->current_group_name importance: ". mysql_error());
    }

    //Set Class
    foreach($this->settings->classes as $class)
    {
        if ($class == 'Unassessed')
          $theclass = "${class}_{$this->current_cat_name}_articles";
        else
          $theclass = "${class}-Class_{$this->current_cat_name}_articles";

        $theclass = mysql_real_escape_string($theclass);

        $sql = "UPDATE $this->user_db.articles a
                SET a.class = '$class'
                WHERE a.run_id = $this->current_run_id
                AND a.talkid IN
                  (SELECT cl.cl_from
                   FROM categorylinks cl
                   WHERE cl.cl_to = '$theclass')";
        mysql_query($sql, $this->con)
          or die("Could not load WikiProject $this->current_group_name quality class: ". mysql_error());
    }
  }
}
?>
