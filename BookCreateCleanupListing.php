<?php
//Svick
//Smallman12q
//Licensed under Simplified BSD license

require_once 'pub/Settings.php';
require_once 'BookCleanupListing.php';

$settings = new Settings();

$listing = new BookCleanupListing($settings);
$listing->Update();
?>
