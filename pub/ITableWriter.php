<?php

interface ITableWriter
{
  public function WriteHeader($title);
  public function WriteText($text);
  public function WriteSection($name);
  public function WriteTableHeader($columns);
  public function WriteRow($cells);
  public function WriteTableFooter();
  public function WriteFooter();

  public function FormatLink($url, $text);
  public function FormatWikiLink($page, $text);
}

class Column
{
  public $Name;
  public $Sortable;

  function __construct($name, $sortable = false)
  {
    $this->Name = $name;
    $this->Sortable = $sortable;
  }

  function __tostring()
  {
    return $this->Name;
  }
}

?>
