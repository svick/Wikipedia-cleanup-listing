<?php

require_once 'ITableWriter.php';

class HtmlTableWriter implements ITableWriter
{
  public function WriteHeader($title)
  {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title><?= $title ?></title>
    <style type="text/css">
      td, th { border: 1px solid black }
      table { border-collapse: collapse }
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
?>
        <th><?= $column ?></th>
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
  </body>
</html>
<?
  }

  public function FormatLink($url, $text)
  {
    return '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($text) . '</a>';
  }
}

?>
