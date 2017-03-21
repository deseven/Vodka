<?php

/**
* @version rev.13
* @author deseven
* @link https://github.com/deseven/vodka
*/
class vodka {

    const rev = 13;

    const head = '{VODKA:HEAD}';
    const canonical = '{VODKA:CANONICAL}';
    const description = '{VODKA:DESCRIPTION}';
    const keywords = '{VODKA:KEYWORDS}';
    const menu = '{VODKA:MENU}';
    const content = '{VODKA:CONTENT}';
    const title = '{VODKA:TITLE}';
    const template = '{VODKA:TEMPLATE}';
    const url = '{VODKA:URL}';
    const name = '{VODKA:NAME}';
    const cclass = '{VODKA:CLASS}';

    protected $base_url;
    protected $root;
    protected $show_errors = true;
    protected $forbid_scriptname = true;
    protected $clean_unused_vars;
    protected $auto_pages;
    protected $main_page;
    protected $notfound_page;
    protected $pages;
    protected $menu;
    protected $templates;
    protected $aliases;

    protected $template;
    protected $content;

    protected $current_page;
    protected $current_template;

    protected $page_built;
    protected $output;

    protected $built_head;
    protected $built_menu;

    protected $uri;

    protected $replace = array();
    protected $subject = array();

    protected $start_time;

    private function printError($error,$die = true) {
        if ($this->show_errors) {
            echo '<div style="color:white;border:1px solid black;background-color:#990000">[vodka rev.'.$this::rev.'] '.$error.'</div>';
        }
        if ($die) {
            header($_SERVER['SERVER_PROTOCOL']." 418 I'm a teapot",true,418);
            exit;
        }
    }

    private function _pathinfo($path,$opt = '') {
        $separator = ' qq ';
        $path = preg_replace('/[^ ]/u',$separator."\$0".$separator,$path);
        if ($opt == '') $pathinfo = pathinfo($path);
        else $pathinfo = pathinfo($path,$opt);
        if (is_array($pathinfo)) {
            $pathinfo2 = $pathinfo;
            foreach($pathinfo2 as $key => $val) {
                $pathinfo[$key] = str_replace($separator,'',$val);
            }
        }
        else if (is_string($pathinfo)) $pathinfo = str_replace($separator,'',$pathinfo);
        return $pathinfo;
    }

    /**
    * Engine initialization.
    *
    * *Keep in mind that all internal redirects are handled here.*
    *
    * @param array $params Array with engine parameters. Check `config.php` from demo sources or github wiki to get full list of parameters.
    * @return boolean `true` if everything is ok, `false` otherwise.
    */
    function __construct($params) {
        $this->start_time = microtime(true);

        if (!isset($params)) {
            $this->printError('no params defined.');
            return false;
        }
        if (!isset($params['system'])) {
            $this->printError('no system params defined.');
            return false;
        }
        if (!isset($params['system']['base_url'])) {
            $this->printError('base_url is required.');
            return false;
        } else {
            $this->base_url = $params['system']['base_url'];
        }
        
        if (isset($params['system']['show_errors'])) {
            $this->show_errors = $params['system']['show_errors'];
        }
        if (isset($params['system']['clean_unused_vars'])) {
            $this->clean_unused_vars = $params['system']['clean_unused_vars'];
        }
        if ($this->show_errors == true) {
            error_reporting(E_ERROR|E_WARNING|E_PARSE); 
        } else {
            error_reporting(0);
        }

        if (isset($params['system']['root'])) {
            $this->root = $params['system']['root'];
        } else {
            $this->root = realpath(dirname(__FILE__));
        }
        if (isset($params['system']['forbid_scriptname'])) {
            $this->forbid_scriptname = $params['system']['forbid_scriptname'];
        }
        if (isset($params['system']['main_page'])) {
            $this->main_page = $params['system']['main_page'];
        }
        if (isset($params['system']['404_page'])) {
            $this->notfound_page = $params['system']['404_page'];
        }
        if (isset($params['system']['auto_pages'])) {
            $this->auto_pages = $params['system']['auto_pages'];
        }

        if (isset($params['menu'])) {
            $this->menu = $params['menu'];
        } elseif (isset($params['menus'])) { // compatibility with an old format
            $this->menu = $params['menus'];
        }

        if (!isset($params['templates'])) {
            $this->printError('no templates defined.');
            return false;
        }
        if ((!isset($params['pages'])) && (!$this->auto_pages)) {
            $this->printError('no pages defined.');
            return false;
        }

        if (isset($params['aliases'])) {
            $this->aliases = $params['aliases'];
        }

        $this->pages = $params['pages'];
        foreach ($this->pages as $key => &$page) {
            if (!isset($page['path'])) {
                $this->printError('no path defined for page #'.($key+1).'.');
                return false;
            }
            if (!isset($page['name'])) {
                $page['name'] = $this->_pathinfo($page['path'],PATHINFO_FILENAME);
            }
            if (!isset($page['title'])) {
                $page['title'] = $page['name'];
            }
            if (!isset($page['description'])) {
                $page['description'] = $page['title'];
            }
            if (!isset($page['keywords'])) {
                $page['keywords'] = "";
            }
        }

        if ($this->auto_pages) {
            foreach (glob($this->auto_pages."/*.{html,htm,txt}",GLOB_BRACE) as $file) {
                $add = true;
                foreach ($this->pages as $addedpage) {
                    if ($addedpage['path'] == $file) {
                        $add = false;
                        break;
                    }
                }
                if ($add) {
                    $this->pages[] = array(
                        'path' => $file,
                        'title' => $this->_pathinfo($file,PATHINFO_FILENAME),
                        'name' => $this->_pathinfo($file,PATHINFO_FILENAME)
                    );
                }
            }
        }

        $this->templates = $params['templates'];

        $this->uri = urldecode($_SERVER['REQUEST_URI']);
        $this->uri = str_replace(basename($_SERVER['PHP_SELF']),'',$this->uri);
        $this->uri = ltrim($this->uri,'/');
        $this->uri = explode('?',$this->uri);
        $this->uri = $this->uri[0];

        if ($this->forbid_scriptname) {
            if (strpos($_SERVER['REQUEST_URI'],$_SERVER['PHP_SELF']) === 0) {
                $_SERVER['REQUEST_URI'] = str_replace($_SERVER['PHP_SELF'],dirname($_SERVER['PHP_SELF']),$_SERVER['REQUEST_URI']);
                $_SERVER['REQUEST_URI'] = preg_replace('~/{2,}~','/',$_SERVER['REQUEST_URI']);
                //echo "will redirect to ".rtrim($this->base_url,'/').$_SERVER['REQUEST_URI'];
                header('Location: '.rtrim($this->base_url,'/').$_SERVER['REQUEST_URI'],true,301);
                exit;
            }
        }

        if ($this->main_page) {
            if ($this->uri == $this->main_page) {
                $_SERVER['REQUEST_URI'] = str_replace($_SERVER['PHP_SELF'],dirname($_SERVER['PHP_SELF']),$_SERVER['REQUEST_URI']);
                $_SERVER['REQUEST_URI'] = preg_replace('~/{2,}~','/',$_SERVER['REQUEST_URI']);
                $_SERVER['REQUEST_URI'] = str_replace($this->main_page,'',$_SERVER['REQUEST_URI']);
                //echo "will redirect to ".rtrim($this->base_url,'/').$_SERVER['REQUEST_URI'];
                header('Location: '.rtrim($this->base_url,'/').$_SERVER['REQUEST_URI'],true,301);
                exit;
            }
        }

        foreach ($this->pages as &$page) {
            if (($page['name'].'/' == $this->uri) || ($page['name'] == $this->uri.'/')) {
                $_SERVER['REQUEST_URI'] = str_replace($this->uri,$page['name'],urldecode($_SERVER['REQUEST_URI']));
                //echo "will redirect to ".$_SERVER['REQUEST_URI'];
                header('Location: '.$_SERVER['REQUEST_URI'],true,301);
                exit;
            }
        }

        if (isset($_GET)) { // fixing GET
            foreach ($_GET as $key => $value) {
                unset($_GET[$key]);
                $key = explode('?',$key);
                if (isset($key[1])) {
                    $_GET = array($key[1] => $value) + $_GET;
                }
                break;
            }
        }
    }

    /**
    * Return vodka start time.
    *
    * @return int Number of microseconds.
    */
    public function getStartTime() {
        return $this->start_time;
    }

    /**
    * Loads template by its name.
    *
    * @param string $name Name of desired template.
    * @return boolean `true` if everything is ok, `false` otherwise.
    */
    public function loadTemplate($name) {
        $this->page_built = false;
        if (isset($this->templates[$name])) {
            if (file_exists($this->root.'/'.$this->templates[$name].'/template.html')) {
                $this->template = file_get_contents($this->root."/".$this->templates[$name].'/template.html');
            } else {
                $this->printError('template `$name` not found.');
                return false;
            }
        } else {
            $this->printError('no such template defined.');
            return false;
        }
        if (dirname($_SERVER['PHP_SELF']) == '/') {
            $this->current_template = '/'.$this->templates[$name];
        }
        else {
            $this->current_template = htmlspecialchars(dirname($_SERVER['PHP_SELF'])).'/'.$this->templates[$name];
        }
        return true;
    }

    /**
    * Returns page by its name or alias.
    *
    * @param string $name Name of desired page.
    * @return mixed `array` with a page or `false`.
    */
    public function getPageByName($name) {
        if (isset($this->aliases[$name])) {
            foreach ($this->pages as $page) {
                if ($page['name'] == $this->aliases[$name]) {
                    return $page;
                }
            }
        }
        foreach ($this->pages as $page) {
            if ($page['name'] == $name) {
                return $page;
            }
        }
        return false;
    }

    /**
    * Returns current page.
    *
    * @return mixed `array` with current page or `false`.
    */
    public function getCurrentPage() {
        if (is_array($this->current_page)) {
            return $this->current_page;
        }
        if (isset($this->aliases[$this->uri])) {
            foreach ($this->pages as $page) {
                if ($page['name'] == $this->aliases[$this->uri]) {
                    $this->current_page = $page;
                    return $page;
                }
            }
        }
        foreach ($this->pages as $page) {
            if ($page['name'] == $this->uri) {
                $this->current_page = $page;
                return $page;
            }
        }
        if (!$this->uri) {
            if ($this->main_page) {
                foreach ($this->pages as $page) {
                    if ($page['name'] == $this->main_page) {
                        $this->current_page = $page;
                        return $page;
                    }
                }
            } else {
                $random_page = rand(0,count($this->pages)-1);
                $this->current_page = $this->pages[$random_page];
                return $this->pages[$random_page];
            }
        }
        if ($this->notfound_page) {
            foreach ($this->pages as $page) {
                if ($page['name'] == $this->notfound_page) {
                    $this->current_page = $page;
                    return $page;
                }
            }
        }
        return false;
    }

    /**
    * Loads current page.
    *
    * @return boolean `true` if everything is ok, `false` otherwise.
    */
    public function loadCurrentPage() {
        return $this->loadPage($this->getCurrentPage());
    }

    /**
    * Loads specified page.
    *
    * @param mixed $page Array with page or page name.
    * @return boolean `true` if everything is ok, `false` otherwise.
    */
    public function loadPage($page) {
        $this->content = '';
        $this->page_built = false;
        if (!is_array($page)) {
            $page = $this->getPageByName($page);
            if (!is_array($page)) {
                $this->printError('page not defined.');
                return false;
            }
        }
        if (isset($page['path'])) {
            if (file_exists($this->root.'/'.$page['path'])) {
                $this->content = file_get_contents($this->root.'/'.$page['path']);
                return true;
            } else {
                $this->printError('page not found, check your config.',false);
                return false;
            }
        } else {
            $this->printError('page not defined.');
            return false;
        }
    }

    /**
    * Builds current menu.
    *
    * @return boolean `true` if everything is ok, `false` otherwise.
    */
    public function buildCurrentMenu() {
        return $this->buildMenu($this->getCurrentPage());
    }

    /**
    * Builds menu for specified page.
    *
    * @param mixed $page Array with page or page name.
    * @return boolean `true` if everything is ok, `false` otherwise.
    */
    public function buildMenu($page) {
        if (!is_array($this->menu)) {
            return false;
        }
        if (!is_array($page)) {
            $page = $this->getPageByName($page);
            if (!is_array($page)) {
                $this->printError('page not defined.');
                return false;
            }
        }
        for ($i = 0;$i < count($this->pages);$i++) {
            $cur_item = $this->menu['html'];
            $visible = true;
            if (isset($this->pages[$i]['visible'])) {
                $visible = $this->pages[$i]['visible'];
            }
            if ($visible) {
                if (($this->pages[$i]['name'] == $page['name']) && isset($this->menu['selected_class'])) {
                    $replace = $this->menu['selected_class'];
                    if (($i == 0) && (isset($this->menu['selected_class_first']))) {
                        $replace .= ' '.$this->menu['selected_class_first'];
                    }
                    if (($i == count($this->pages) - 1) && (isset($this->menu['selected_class_last']))) {
                        $replace .= ' '.$this->menu['selected_class_last'];    
                    }
                    $cur_item = str_replace($this::cclass,$replace,$cur_item);
                } else {
                    $cur_item = str_replace($this::cclass,'',$cur_item);
                }
                $cur_item = str_replace($this::name,$this->pages[$i]['name'],$cur_item);

                if (dirname($_SERVER['PHP_SELF']) != '/') {
                    $cur_item = str_replace($this::url,htmlspecialchars(dirname($_SERVER['PHP_SELF'])).'/'.($this->pages[$i]['name'] == $this->main_page ? '' : $this->pages[$i]['name']),$cur_item);
                } else {
                    $cur_item = str_replace($this::url,'/'.($this->pages[$i]['name'] == $this->main_page ? '' : $this->pages[$i]['name']),$cur_item);
                }
                $cur_item = str_replace($this::title,$this->pages[$i]['title'],$cur_item);
                $this->built_menu .= $cur_item;
            }
        }
        return true;
    }

    /**
    * Builds current page.
    *
    * @return boolean `true` if everything is ok, `false` otherwise.
    */
    public function buildCurrentPage() {
        return $this->buildPage($this->getCurrentPage());
    }

    /**
    * Builds specified page.
    *
    * @param mixed $page Array with page or page name.
    * @return boolean `true` if everything is ok, `false` otherwise.
    */
    public function buildPage($page) {
        if (!is_array($page)) {
            $page = $this->getPageByName($page);
            if (!is_array($page)) {
                $this->printError('page not defined.');
                return false;
            }
        }
        $this->output = $this->template;
        if (isset($page['custom'])) {
            $page['custom'] = array_reverse($page['custom']);
            while ($custom = current($page['custom'])) {
                $var = key($page['custom']);
                if (substr($var,0,1) != '{') {
                    $var = '{'.$var;
                }
                if (substr($var,-1) != '}') {
                    $var .= "}";
                }
                array_unshift($this->replace,$var);
                array_unshift($this->subject,$page['custom'][key($page['custom'])]);
                next($page['custom']);
            }
        }
        array_unshift($this->replace,$this::content,$this::head,$this::canonical,$this::description,$this::keywords,$this::template,$this::title,$this::menu);
        array_unshift($this->subject,$this->content,$this->built_head,$this->base_url.($page['name'] == $this->main_page ? '' : $page['name']),$page['description'],$page['keywords'],$this->current_template,$page['title'],$this->built_menu);
        $this->output = str_replace($this->replace,$this->subject,$this->output);
        if ($this->clean_unused_vars) {
            $this->output = preg_replace('/{[A-Z0-9:]+}/','',$this->output);
        }
        if ($page['name'] == $this->notfound_page) {
            header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found',true,404);
        }
        $this->page_built = true;
        echo $this->output;
        return true;
    }

    /**
    * Replaces variable (string encapsulated in curly braces) in page.
    * 
    * *Please note that this function doesn't modify the output directly, call `buildPage()` to apply your actions.*
    *
    * @param string $var
    * @param string $string
    * @return boolean Always `true`.
    * @example `$vodka->replaceVar('VAR','value');`
    */
    public function replaceVar($var,$string = null) {
        if (substr($var,0,1) != '{') {
            $var = '{'.$var;
        }
        if (substr($var,-1) != '}') {
            $var .= "}";
        }
        $this->replace[] = $var;
        $this->subject[] = $string;
        return true;
    }

    /**
    * Appends something to the `{VODKA:HEAD}` part of the template.
    * 
    * *Please note that this function doesn't modify the output directly, call `buildPage()` to apply your actions.*
    *
    * @param string $string
    * @return boolean Always `true`.
    * @example `$vodka->appendHead('<meta name="robots" content="all">');`
    */
    public function appendHead($string) {
        $this->built_head .= $string;
        return true;
    }

}

?>