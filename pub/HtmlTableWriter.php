<?php

require_once 'ITableWriter.php';

class HtmlTableWriter implements ITableWriter
{
  protected $current_section;
  protected $columns;
  protected $rows;

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
    $this->current_section = $name;
?>
    <h2 id="<?= $name ?>"><?= $name ?></h2>
<?
  }

  public function WriteTableHeader($columns)
  {
    $this->columns = $columns;
    foreach($columns as $column)
      $column->Empty = true;
    $this->rows = array();
  }

  function WriteTableHeaderInternal($columns)
  {
?>
    <table>
      <tr>
<?
    foreach ($columns as $column)
    {
      if ($column->Empty)
        continue;
      if ($column->Sortable)
      {
        $params = $_GET;
        $params['sort'] = $column->Name;
        $params2 = array();
        foreach($params as $key => $value)
          $params2[] = "$key=$value";
        $url = $_SERVER['PHP_SELF'] . '?' . implode('&', $params2);
        if ($this->current_section)
          $url = $url . '#' . $this->current_section;
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
    $this->rows[] = $cells;
    foreach($this->columns as $key => $column)
    {
      if ($cells[$key] !== null && $cells[$key] !== '')
        $column->Empty = false;
    }
  }

  function WriteRowInternal($cells)
  {
?>
      <tr>
<?
    foreach($this->columns as $key => $column)
    {
      if ($column->Empty)
        continue;
?>
        <td><?= $cells[$key] ?></td>
<?
    }
?>
      </tr>
<?
  }

  public function WriteTableFooter()
  {
    $this->WriteTableHeaderInternal($this->columns);
    foreach ($this->rows as $row)
      $this->WriteRowInternal($row);
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

  public function WriteTocHeader()
  {
?>
    <ul>
<?
  }

  public function WriteTocEntry($name, $text)
  {
?>
      <li><a href="#<?= $name ?>"><?= $name ?></a> <?= $text ?></li>
<?
  }

  public function WriteTocFooter()
  {
?>
    </ul>
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
