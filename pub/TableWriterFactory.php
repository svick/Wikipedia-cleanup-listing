<?php

require_once 'HtmlTableWriter.php';
require_once 'CsvTableWriter.php';

class TableWriterFactory
{
  public static function Create($format)
  {
    switch($format)
    {
    case "csv":
      return new CsvTableWriter();
    case "html":
    default:
      return new HtmlTableWriter();
    }
  }
}

?>
