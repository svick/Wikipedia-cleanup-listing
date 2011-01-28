<?php
//Smallman12q
//Svick
//Licensed under Simplified BSD license
//Version .1

require_once 'pub/Settings.php';

$settings = new Settings();

$listing = new WikiProjectCleanupListing($settings);
$listing->Update();
?>
