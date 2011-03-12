<?php

require_once 'ITableWriter.php';

class WikicodeTableWriter implements ITableWriter
{
  protected $current_section;
  protected $columns;
  protected $rows;

  public $Text = '';

  public function WriteHeader($title)
  {
    header('Content-Type: text/plain; charset=UTF-8');
  }

  public function WriteText($text)
  {
    $this->Text .= "\n$text\n";
  }

  public function WriteSection($name, $level = 1)
  {
    $level = $level + 1;
    $markup = str_repeat('=', $level);

    $this->current_section = $name;

    $this->Text .= "\n$markup $name $markup\n";
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
    $this->Text .= "\n{| class=\"wikitable sortable\"\n|-\n";

    $first = true;

    foreach ($columns as $column)
    {
      if ($column->Empty)
        continue;

      if ($first)
      {
        $this->Text .= '!';
        $first = false;
      }
      else
        $this->Text .= '!!';

      if (!$column->Sortable)
        $this->Text .= 'class="unsortable"|';

      $this->Text .= $column->Name;
    }

    $this->Text .= "\n";
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
    $this->Text .= "|-\n";

    $first = false;

    foreach($this->columns as $key => $column)
    {
      if ($column->Empty)
        continue;

      if ($first)
      {
        $this->Text .= '|';
        $first = false;
      }
      else
        $this->Text .= '||';

      $this->Text .= $cells[$key];
    }

    $this->Text .= "\n";
  }

  public function WriteTableFooter()
  {
    $this->WriteTableHeaderInternal($this->columns);
    foreach ($this->rows as $row)
      $this->WriteRowInternal($row);
    $this->Text .= "|}\n";
  }

  public function WriteFooter()
  {
    echo $this->Text;
  }

  public function WriteTocHeader()
  { }

  public function WriteTocEntry($name, $text)
  { }

  public function WriteTocFooter()
  { }

  public function FormatLink($url, $text)
  {
    return '[' . $url . ' ' . $text . ']';
  }

  public function FormatWikiLink($page, $text = null)
  {
    if ($text == null)
      return "[[$page]]";
    else
      return "[[$page|$text]]";
  }
}

?>
