<?php

require_once 'ITableWriter.php';

class CsvTableWriter implements ITableWriter
{
  public function WriteHeader($title)
  {
    header('Content-Type: text/plain; charset=UTF-8');
  }

  public function WriteText($text)
  { }

  public function WriteSection($name, $level = 1)
  {
    $underline_char = null;
    switch ($level)
    {
    case 1:
      $underline_char = '=';
      break;
    case 2:
      $underline_char = '-';
      break;
    }

    echo "$name\n";
    if ($underline_char)
      echo str_repeat($underline_char, strlen($name));
    echo "\n";
  }

  public function WriteTableHeader($columns)
  {
    $this->WriteRow($columns);
  }

  public function WriteRow($cells)
  {
    echo '"' . implode('","', $cells) . "\"\n";
  }

  public function WriteTableFooter()
  {
    echo "\n";
  }

  public function WriteFooter()
  { }

  public function WriteTocHeader()
  { }

  public function WriteTocEntry($name, $text)
  { }

  public function WriteTocFooter()
  { }

  public function FormatLink($url, $text)
  {
    return $text;
  }

  public function FormatWikiLink($page, $text = null)
  {
    if ($text == null)
      $text = $page;
    return $text;
  }
}

?>
