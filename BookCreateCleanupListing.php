<?php
//Svick
//Smallman12q
//Licensed under Simplified BSD license

require_once 'pub/Settings.php';

$settings = new Settings();

$listing = new BookCleanupListing($settings);
$listing->Update();
?>
