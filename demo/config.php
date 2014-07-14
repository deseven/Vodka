<?php

$params["system"] = array(
	"root" => realpath(dirname(__FILE__)),
	"clean_urls" => false, // not really working right now, don't use
	"404_page" => "404", // a page which we will use when the page is not found
	"main_page" => "main", // default page, you can remove to pick random page every time
	"show_errors" => true
);

$params["menus"] = array(
	"html" => '<li><a href="{VODKA:URL}" id="{VODKA:NAME}" class="{VODKA:CLASS}">{VODKA:TITLE}</a></li>', // html of the menu element
	"selected_class" => "selected",
	"selected_class_first" => "selected_top",  // here we can define that we need to add another class for the first
	"selected_class_last" => "selected_bottom" // and the last selected element
);

$params["aliases"] = array(
	"other_page" => "third", // there we can define various aliases, in this case, url with ?other_page will lead us to the page called "third"
	"i=like&to=use&some=variables" => "second"
);

$params["pages"] = array(
	array(
		"path" => "pages/main.html", // location (relative to the root dir)
		"name" => "main",            // internal page name
		"title" => "main page"       // title for the menu entry
	),
	array(
		"path" => "pages/second.html",
		"name" => "second",
		"title" => "second page"
	),
	array(
		"path" => "pages/third.html",
		"name" => "third",
		"title" => "third page"
	),
	array(
		"path" => "pages/404.html",
		"name" => "404",
		"title" => "404",
		"visible" => false // menu visibility flag
	)
);

$params["templates"] = array(
	"demo" => "tpl/demo" // name and path to the template dir
);

?>