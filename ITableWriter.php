<?php

interface ITableWriter
{
  public void WriteHeader($title);
  public void WriteSection($name);
  public void WriteTableHeader($columns);
  public void WriteRow($cells);
  public void WriteTableFooter();
  public void WriteFooter();
}

?>
