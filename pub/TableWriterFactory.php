<?php

require_once 'HtmlTableWriter.php';
require_once 'CsvTableWriter.php';
require_once 'WikicodeTableWriter.php';

class TableWriterFactory
{
  public static function Create($format)
  {
    switch($format)
    {
    case 'csv':
      return new CsvTableWriter();
    case 'wikicode':
      return new WikicodeTableWriter();
    case 'html':
    default:
      return new HtmlTableWriter();
    }
  }
}

?>
