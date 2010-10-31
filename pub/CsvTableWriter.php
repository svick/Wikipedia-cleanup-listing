<?php

require_once 'ITableWriter.php';

class CsvTableWriter implements ITableWriter
{
  public function WriteHeader($title)
  { }

  public function WriteText($text)
  { }

  public function WriteSection($name)
  {
    echo "$name\n";
    echo str_repeat('=', strlen($name));
  }

  public function WriteTableHeader($columns)
  {
    WriteRow($columns);
  }

  public function WriteRow($cells)
  {
    echo '"' . implode('","', $cells) . '"';
  }

  public function WriteTableFooter()
  { }

  public function WriteFooter()
  { }

  public function FormatLink($url, $text)
  {
    return $text;
  }
}

?>
