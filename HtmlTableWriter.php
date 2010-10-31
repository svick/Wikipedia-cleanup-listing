<?php

include_once 'ITableWriter.php';

class HtmlTableWriter
{
  public void WriteHeader($title)
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

  public void WriteSection($name)
  {
  }

  public void WriteTableHeader($columns)
  {
  }

  public void WriteRow($cells)
  {
  }

  public void WriteTableFooter()
  {
  }

  public void WriteFooter()
  {
  }
}

?>
