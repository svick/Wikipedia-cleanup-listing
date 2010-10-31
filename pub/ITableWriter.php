<?php

interface ITableWriter
{
  public function WriteHeader($title);
  public function WriteSection($name);
  public function WriteTableHeader($columns);
  public function WriteRow($cells);
  public function WriteTableFooter();
  public function WriteFooter();
}

?>
