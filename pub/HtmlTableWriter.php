<?php

include_once 'ITableWriter.php';

class HtmlTableWriter
{
  public function WriteHeader($title)
  {
?>
<html>
  <head>
    <title><?= $title ?></title>
  </head>
  <style>
    td, th { border: 1px solid black }
    table { border-collapse: collapse }
  </style>
  <body>
<?php
  }

  public function WriteSection($name)
  {
  }

  public function WriteTableHeader($columns)
  {
  }

  public function WriteRow($cells)
  {
  }

  public function WriteTableFooter()
  {
  }

  public function WriteFooter()
  {
  }
}

?>
