<?php

require_once 'ITableWriter.php';

class HtmlTableWriter implements ITableWriter
{
  public function WriteHeader($title)
  {
    header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
    <title><?= $title ?></title>
    <style type="text/css">
      td, th { border: 1px solid black }
      table { border-collapse: collapse }
      #footer { text-align: center }
    </style>
  </head>
  <body>
<?
  }

  public function WriteText($text)
  {
?>
    <p><?= $text ?></p>
<?
  }

  public function WriteSection($name)
  {
?>
    <h2><?= $name ?></h2>
<?
  }

  public function WriteTableHeader($columns)
  {
?>
    <table>
      <tr>
<?
    foreach ($columns as $column)
    {
      if ($column->Sortable)
      {
        $params = $_GET;
        $params['sort'] = $column->Name;
        $params2 = array();
        foreach($params as $key => $value)
          $params2[] = "$key=$value";
        $url = $_SERVER['PHP_SELF'] . '?' . implode('&', $params2);
        $column_string = $this->FormatLink($url, $column->Name);
      }
      else
        $column_string = $column->Name;
?>
        <th><?= $column_string ?></th>
<?
    }
?>
      </tr>
<?
  }

  public function WriteRow($cells)
  {
?>
      <tr>
<?
    foreach($cells as $cell)
    {
?>
        <td><?= $cell ?></td>
<?
    }
?>
      </tr>
<?
  }

  public function WriteTableFooter()
  {
?>
    </table>
<?
  }

  public function WriteFooter()
  {
?>
    <div id="footer">
      Authors:
      <?= $this->FormatWikiLink('User:Svick', 'Svick') ?>,
      <?= $this->FormatWikiLink('User:Smallman12q', 'Smallman12q') ?>
    </div>
  </body>
</html>
<?
  }

  public function FormatLink($url, $text)
  {
    return '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($text) . '</a>';
  }

  public function FormatWikiLink($page, $text = null)
  {
    if ($text == null)
      $text = $page;
    return $this->FormatLink("http://en.wikipedia.org/wiki/$page", $text);
  }
}

?>
