<?php

require "vodka.class.php"; // main class
require "config.php"; // this is where all our settings are

$vodka = new vodka($params); // init

$vodka->loadTemplate("demo"); // loading template

$vodka->loadCurrentPage(); // this will load the current page, you can also use loadPage($page) instead
$vodka->buildCurrentMenu(); // this will build the menu for the current page, once again, you can use buildMenu($page) instead

// let's insert some lorem ipsum
$lorem = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";
$vodka->replaceVar("{LOREM}",$lorem);

// let's insert some more info to the head section
$vodka->appendHead('<meta name="GENERATOR" content="Vodka">');

// let's insert something based on the current page
// you can also do that by defining custom page values, check config.php for details
$page = $vodka->getCurrentPage();
switch ($page["name"]) {
	case "second":
		$vodka->replaceVar("{CLASS}","second");
		break;
	case "third":
		$vodka->replaceVar("{CLASS}","third");
		break;
	case "404":
		$vodka->replaceVar("{WARN}","You shouldn't be there.");
		break;
	case "usage":
		$vodka->appendHead('<script src="https://cdn.rawgit.com/google/code-prettify/master/loader/run_prettify.js"></script>');
}

// let's insert generation info
$generation_time = round(microtime(true) - $vodka->getStartTime(),4);
$vodka->replaceVar("{FOOTER_INFO}",'generated with <a href="https://github.com/deseven/vodka">Vodka</a> rev.'.$vodka::rev." in $generation_time sec");

$vodka->buildCurrentPage(); // finally, we are ready to output our page

?>