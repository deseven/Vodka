<?php

/*
* Vodka rev.7
* written by deseven
* website: http://deseven.info
*/

if (!function_exists('mb_pathinfo')) {
    function mb_pathinfo($path, $opt = '') {
        $separator = ' qq ';
        $path = preg_replace('/[^ ]/u', $separator."\$0".$separator, $path);
        if ($opt == '') $pathinfo = pathinfo($path);
        else $pathinfo = pathinfo($path, $opt);

        if (is_array($pathinfo)) {
            $pathinfo2 = $pathinfo;
            foreach($pathinfo2 as $key => $val) {
                $pathinfo[$key] = str_replace($separator, '', $val);
            }
        }
        else if (is_string($pathinfo)) $pathinfo = str_replace($separator, '', $pathinfo);
        return $pathinfo;
    }
}

class vodka {

    const rev = 7;

    const head = '{VODKA:HEAD}';
    const menu = '{VODKA:MENU}';
    const content = '{VODKA:CONTENT}';
    const title = '{VODKA:TITLE}';
    const template = '{VODKA:TEMPLATE}';
    const url = '{VODKA:URL}';
    const name = '{VODKA:NAME}';
    const cclass = '{VODKA:CLASS}';

    protected $root;
    protected $clean_urls;
    protected $show_errors;
    protected $clean_unused_vars;
    protected $auto_pages;
    protected $main_page;
    protected $notfound_page;
    protected $pages;
    protected $menus;
    protected $templates;
    protected $aliases;

    protected $template;
    protected $content;

    protected $current_page;
    protected $current_template;

    protected $page_built;
    protected $output;

    protected $head;
    protected $menu;

    protected $replace = array();
    protected $subject = array();

    protected $start_time;

    private function printError($error) {
        if ($this->show_errors) {
            echo '<div style="color:white;border:1px solid black;background-color:#990000">[vodka rev.'.$this::rev.'] '.$error.'</div>';
        }
    }

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
        }
        if (isset($params['system']['clean_urls'])) {
            $this->clean_urls = $params['system']['clean_urls'];
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

        if (isset($params['menus'])) {
            $this->menus = $params['menus'];    
        }

        if (!isset($params['templates'])) {
            $this->printError('no templates defined.');
            return false;
        }
        if ((!isset($params['pages'])) && (!strlen($this->auto_pages))) {
            $this->printError('no pages defined.');
            return false;
        }

        if (isset($params['aliases'])) {
            $this->aliases = $params['aliases'];
        }

        $this->pages = $params['pages'];
        foreach ($this->pages as &$page) {
            if (!isset($page['name'])) {
                $page['name'] = mb_pathinfo($page['path'],PATHINFO_FILENAME);
            }
        }

        if (strlen($this->auto_pages)) {
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
                        'title' => mb_pathinfo($file,PATHINFO_FILENAME),
                        'name' => mb_pathinfo($file,PATHINFO_FILENAME)
                    );
                }
            }
        }

        $this->templates = $params['templates'];
    }

    public function getStartTime() {
        return $this->start_time;
    }

    public function loadTemplate($name) {
        $this->page_built = false;
        if (isset($this->templates[$name])) {
            if (file_exists($this->root.'/'.$this->templates[$name].'/template.html')) {
                $this->template = file_get_contents($this->root.'/'.$this->templates[$name].'/template.html');
            } else {
                $this->printError('template `$name` not found.');
                return false;
            }
        } else {
            $this->printError('no such template defined.');
            return false;
        }
        $this->current_template = $this->templates[$name];
        return true;
    }

    public function getCurrentPage() {
        $_SERVER['QUERY_STRING'] = ltrim($_SERVER['QUERY_STRING'],'/');
        if (is_array($this->current_page)) {
            return $this->current_page;
        }
        if (isset($this->aliases[$_SERVER['QUERY_STRING']])) {
            foreach ($this->pages as $page) {
                if ($page['name'] == $this->aliases[$_SERVER['QUERY_STRING']]) {
                    $this->current_page = $page;
                    return $page;
                }
            }
        }
        foreach ($this->pages as $page) {
            if ($page['name'] == $_SERVER['QUERY_STRING']) {
                $this->current_page = $page;
                return $page;
            }
        }
        if (strlen($_SERVER['QUERY_STRING']) == 0) {
            if (strlen($this->main_page) > 0) {
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
        if (strlen($this->notfound_page) > 0) {
            foreach ($this->pages as $page) {
                if ($page['name'] == $this->notfound_page) {
                    $this->current_page = $page;
                    return $page;
                }
            }
        }
        return false;
    }

    public function appendHead($string) {
        $this->head .= $string;
        return true;
    }

    public function loadCurrentPage() {
        return $this->loadPage($this->getCurrentPage());
    }

    public function loadPage($page = null) {
        $this->page_built = false;
        if ($page === null) {
            $this->printError('page not defined.');
            return false;
        } elseif (isset($page['path'])) {
            if (file_exists($this->root.'/'.$page['path'])) {
                $this->content = file_get_contents($this->root.'/'.$page['path']);
                return true;
            } else {
                $this->printError('page not found.');
                return false;
            }
        } else {
            $this->printError('page not defined.');
            return false;
        }
        return false;
    }

    public function buildCurrentMenu() {
        return $this->buildMenu($this->getCurrentPage());
    }

    public function buildMenu($page = null) {
        if ($page === null) {
            $this->printError('page not defined.');
            return false;
        }
        for ($i = 0;$i < count($this->pages);$i++) {
            $cur_item = $this->menus['html'];
            $visible = true;
            if (isset($this->pages[$i]['visible'])) {
                $visible = $this->pages[$i]['visible'];
            }
            if ($visible) {
                if (($this->pages[$i]['name'] == $page['name']) && isset($this->menus['selected_class'])) {
                    $replace = $this->menus['selected_class'];
                    if (($i == 0) && (isset($this->menus['selected_class_first']))) {
                        $replace .= ' '.$this->menus['selected_class_first'];
                    }
                    if (($i == count($this->pages) - 1) && (isset($this->menus['selected_class_last']))) {
                        $replace .= ' '.$this->menus['selected_class_last'];    
                    }
                    $cur_item = str_replace($this::cclass,$replace,$cur_item);
                } else {
                    $cur_item = str_replace($this::cclass,'',$cur_item);
                }
                $cur_item = str_replace($this::name,$this->pages[$i]['name'],$cur_item);
                if ($this->clean_urls) {
                    $cur_item = str_replace($this::url,dirname($_SERVER['PHP_SELF']).$this->pages[$i]['name'],$cur_item);
                } else {
                    $cur_item = str_replace($this::url,dirname($_SERVER['PHP_SELF']).'?'.$this->pages[$i]['name'],$cur_item);
                }
                $cur_item = str_replace($this::title,$this->pages[$i]['title'],$cur_item);
                $this->menu .= $cur_item;
            }
        }
    }

    public function buildCurrentPage() {
        return $this->buildPage($this->getCurrentPage());
    }

    public function buildPage($page = null) {
        if ($page === null) {
            $this->printError('page not defined.');
            return false;
        }
        if ($_SERVER['QUERY_STRING'] == $this->main_page) {
            header('Location: '.dirname($_SERVER['PHP_SELF']),true,302);
            return true;
        }
        $this->output = $this->template;
        $this->output = str_replace($this::content,$this->content,$this->output);
        $this->output = str_replace($this::head,$this->head,$this->output);
        $this->output = str_replace($this::template,$this->current_template,$this->output);
        $this->output = str_replace($this::title,$page['title'],$this->output);
        $this->output = str_replace($this::menu,$this->menu,$this->output);
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

    public function replaceVar($var,$string = null) {
        $this->replace[] = $var;
        $this->subject[] = $string;
        return true;
    }
}

?>