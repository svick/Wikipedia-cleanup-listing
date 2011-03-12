<?php
//Svick
//November 2010
//For Cleanup Listing

function CreateCategoryString($category_rows)
{
  $categories = array();
  while ($category = mysql_fetch_assoc($category_rows))
  {
    $month_name = $category['month'] ? date('F', mktime(0, 0, 0, $category['month'], 1)) . ' ' : '';
    $current_date_part = $category['year'] ? "$month_name{$category['year']}" : '';
    
    if ($category['name'] == $category_name)
    {
      if ($current_date_part)
      {
        if ($date_part)
          $date_part = "$date_part, $current_date_part";
        else
          $date_part = $current_date_part;
      }
    }
    else
    {
      if ($category_name)
        if ($date_part)
          $categories[] = "$category_name ($date_part)";
        else
          $categories[] = $category_name;

      $category_name = $category['name'];
      $date_part = $current_date_part;
    }
  }

  if ($category_name)
    if ($date_part)
      $categories[] = "$category_name ($date_part)";
    else
      $categories[] = $category_name;

  $category_name = null;
  $date_part = null;

  return $categories;
}
?>
