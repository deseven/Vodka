vodka
=====

Simple and tiny PHP flat-file site engine.

## dependencies
PHP 5.2 or higher (PHP7 recommended)  
basic php knowledge  

## demo webserver configuration
### apache
Use `.htaccess` files from the demo.  

### lighttpd
Use `url.rewrite-if-not-file = ( "^(.*)$" => "index.php?$1" )` for clean urls.  
Use `$HTTP["url"] =~ "^/pages/" { url.access-deny = ( "" ) }` for pages directory protection, etc.  

### nginx
Use `location / { try_files $uri $uri/ /index.php?$request_uri; }` for clean urls.  
Use `location ^~ /pages/ { return 403; }` for pages directory protection, etc.  

## FAQ
**Q:** What is it for?  
**A:** For small promo-sites, homepages, etc...

**Q:** There are tons of php-engines, why are you making another one?  
**A:** I like to do it my way.

**Q:** What can it do?  
**A:** Not much, check the [demo](http://vodka.deseven.info) or the [dev version](http://dev.vodka.deseven.info/) with various tests. I'll add more functionality in case I (or someone else) will need it.

**Q:** How to use it?  
**A:** Check the [basic usage page](http://vodka.deseven.info/usage). Then check the demo source files. Everything is pretty self-explanatory.

**Q:** What name is that?  
**A:** Just imagine how cool it is to have a website which is based on vodka!

**Q:** I have a suggestion or a bug report.  
**A:** Feel free to create issues on the github or to mail me directly.

Hope you'll have fun with vodka :)
