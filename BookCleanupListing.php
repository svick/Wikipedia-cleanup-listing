<?php
//Smallman12q
//Svick
//Licensed under Simplified BSD license
//Version .1

require_once 'CleanupListingBase.php';

class BookCleanupListing extends CleanupListingBase
{
  protected $database_name = 'book_cleanup';
  protected $groups_additional_columns = ',
    pageid INT(8) UNSIGNED NOT NULL';

  protected $current_pageid;

  protected function LoadNewGroups()
  {
    $sql = "INSERT IGNORE INTO $this->user_db.groups (name)
            SELECT page_title
            FROM page
            WHERE page_namespace = 108
              AND page_is_redirect = 0";
    mysql_query($sql, $this->con)
      or die('Could not add books: ' . mysql_error());
  }

  protected abstract function GetGroupsToUpdate()
  {
    $sql = "SELECT DISTINCT groups.id AS id, name, pageid
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

  protected function ReadAdditionalGroupColumns($group)
  {
    $this->current_pageid = $group['pageid'];
  }

  protected function LoadArticles()
  {
    $sql = "INSERT INTO $this->user_db.articles (articleid, article, run_id)
            SELECT DISTINCT page_id, page_title, $this->current_run_id
            FROM pagelinks
            JOIN page
              ON page_title = pl_title
              AND page_namespace = pl.namespace
            WHERE pl_from = $this->current_pageid
              AND pl_namespace = 0";
    mysql_query($sql, $this->con)
            or die('Could not load book '.$this->current_group_name." articles: ". mysql_error());
  }
}
