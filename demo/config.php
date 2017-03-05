<?php

$params["system"] = array(
	"base_url"          => "http://vodka.deseven.info/", // base url of your site with trailing slash at the end, the only required parameter in system section
	"root"              => realpath(dirname(__FILE__)), // you can change that to absolute or relative path if you store your site files elsewhere
	"404_page"          => "404", // a page which we will use when the page is not found
	"main_page"         => "main", // default page, you can remove it to pick random page every time
	"auto_pages"        => "pages", // try to populate pages automatically from this directory
	"forbid_scriptname" => true, // this will redirect user to root if script name has been found in url
	"clean_unused_vars" => true, // try to clean unused vars on page output, be careful with that as it replaces everything by pattern {[A-Z0-9:]+}
	"show_errors"       => true // this enables error output, keep in mind that setting it to false will also supress php errors and warnings
);

$params["menus"] = array(
	"html"                 => '<li><a href="{VODKA:URL}" id="{VODKA:NAME}" class="{VODKA:CLASS}">{VODKA:TITLE}</a></li>', // html of the menu element
	"selected_class"       => "selected",
	"selected_class_first" => "selected_top",  // here we can define that we need to add another class for the first
	"selected_class_last"  => "selected_bottom" // and the last selected element
);

$params["aliases"] = array(
	"other_page"                   => "third", // there we can define various aliases, in this case, url with other_page will lead us to the page called "third"
	"i=like&to=use&some=variables" => "second"
);

$params["pages"] = array(
	array(
		"path"        => "pages/main.html", // location (relative to the root dir)
		"title"       => "main page",      // title for the menu entry
		"description" => "main page of this site", // meta description
		"keywords"    => "vodka, php, site engine, lightweight, flat-file", // meta keywords
		"custom"      => array( // custom values which will be inserted to the page, you can also do it manually with replaceVar() function, check index.php for more info
			"{GREETING}"  => 'Hello, this is a demo site of Vodka, simple and tiny flat-file engine, written in PHP. You can <a href="{URL}">grab the latest version on github</a>.',
			"{URL}"       => "https://github.com/deseven/vodka/"
		)
	),
	array(
		"path"        => "pages/sec.html",
		"name"        => "second", // if we want to define name explicitly
		"title"       => "second page"
	),
	array(
		"path"        => "pages/third.html",
		"title"       => "third page"
	),
	array(
		"path"        => "pages/usage.html",
		"title"       => "basic usage"
	),
	array(
		"path"        => "pages/404.html",
		"title"       => "404",
		"visible"     => false // menu visibility flag
	),
	array(
		"path"        => "pages/sec.html",
		"title"       => "long path test",
		"name"        => "long/path/test/", // you can also use long paths if you want
		"visible"     => false
	)
);

$params["templates"] = array(
	"demo" => "tpl/demo" // name and path to the template dir, relative to the site root
);

?>