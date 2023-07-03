<?php

/**
 * SiteXML parser
 *
 * v 2.0
 *
 * (c) 2015 Michael Zelensky
 *
 * About SiteXML technology: www.sitexml.info
 *
 */

DEFINE('DEBUG', true);
DEFINE('siteXML', '.site.xml');
DEFINE('USERS_FILE', '../.users');
DEFINE('CONTENT_DIR', '.content/');
DEFINE('THEMES_DIR', '.themes/');
DEFINE('MODULES_DIR', '.modules/');
DEFINE('AJAX_BROWSING_SCRIPT', '<script src="/js/siteXML.ajaxBrowsing.js"></script>');
DEFINE('CONTENT_EDIT_SCRIPT', '
    <link rel="stylesheet" href="http://yui.yahooapis.com/3.18.1/build/cssreset-context/cssreset-context-min.css" type="text/css" />
    <link rel="stylesheet" href="/css/siteXML.editContent.css" type="text/css" />
    <link rel="stylesheet" href="/css/siteXML.editXML.css" type="text/css" />
    <script src="/js/siteXML.editContent.js"></script>
    <script src="/js/siteXML.editXML.js"></script>');
DEFINE('DEFAULT_THEME_HTML', '<!DOCTYPE html><html>
    <head><meta http-equiv="Content-Type" content="text/html; charset=utf8">
    <%META%>
    <title><%TITLE%></title>
    </head><body>
    <div id="header" style="font-size: 3em"><%SITENAME%></div><div id="navi" style="float:left; width:180px"><%NAVI%></div>
    <div id="main" style="padding:0 10px 20px 200px"><%CONTENT(main)%></div>
    <div id="footer">This is <a href="http://www.sitexml.info">SiteXML</a> default theme<br/>SiteXML:PHP v1.0
    <a href="/.site.xml">.site.xml</a></div></body></html>');

session_start();

$siteXML = new siteXML();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {

    case 'POST':
        if (isset($_POST['sitexml'])) {
            if (!isset($_SESSION['username'])) {
                header('HTTP/1.1 401 Access denied');
                die;
            }
            if ($siteXML->saveXML($_POST['sitexml'])) {
                echo 'siteXML saved';
            } else {
                $siteXML->error('siteXML was not saved');
            }
        } elseif (isset($_POST['cid']) && isset($_POST['content'])) {
            if (!isset($_SESSION['username'])) {
                header('HTTP/1.1 401 Access denied');
                die;
            }
            $siteXML->saveContent($_POST['cid'], $_POST['content']);
        } elseif (isset($_POST['username']) && isset($_POST['password'])) {
            $siteXML->login();
        }
        break;

    case 'GET':
        if (isset($_GET['logout'])) {
            $siteXML->logout();
        }

        if (isset($_GET['edit'])) {
            if (!empty($_SESSION['username'])) {
                $_SESSION['edit'] = true;
                echo $siteXML->page();
            } else {
                echo $siteXML->loginScreen('edit');
            }
        } elseif (isset($_GET['sitexml'])) {
            header("Content-type: text/xml; charset=utf-8");
            echo $siteXML->getXML();
        } elseif (isset($_GET['login'])) {
            echo $siteXML->loginScreen();
        } elseif (!empty($_GET['cid'])) {
            echo $siteXML->getContent($_GET['cid']);
        } elseif (!empty($_GET['id']) && !empty($_GET['name'])) {
            echo $siteXML->getContentByIdAndName($_GET['id'], $_GET['name']);
        } else {
            echo $siteXML->page();
        }

        break;

    default:
        header('HTTP/1.1 405 Method Not Allowed');
        header('Allow: GET, POST');
        break;
}


/* Class */
class SiteXML {

    var $pid;
    var $obj;
    var $pageObj;
    var $editMode = false;
    var $basePath;

    //
    function siteXML() {
        $this->setEditMode();
        $this->obj = $this->getObj();
        $this->pid = $this->getPid();
        $this->pageObj = $this->getPageObj($this->pid);
        $this->themeObj = $this->getTheme();
        $this->basePath = $this->getSiteBasePath();
    }

    //
    function setEditMode() {
        if (!empty($_SESSION['edit']) && !empty($_SESSION['username'])) {
            header("Cache-Control: no-cache, must-revalidate");
            $this->editMode = true;
        }
    }

    //
    function loginScreen($edit = '') {
        return '<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<style>
    .siteXML-logindiv {
        width: 250px;
        margin: auto;
    }
    .siteXML-logindiv div {
        padding: 5px 0;
    }
</style>
<body>
<div class="siteXML-logindiv">
    <form action="/" method="post">
        <div>
            <input placeholder="Username" name="username" type="text" autofocus="true">
        </div>
        <div>
            <input placeholder="Password" name="password" type="password">
        </div>
        <div>'
        . ( $edit == 'edit' ? '<input type="hidden" name="edit" value="true">' : '')
        . '<input type="submit">
        </div>
    </form>
</div>
</body>
</html>';
    }

    //
    function login() {
        if (!empty($_POST['username']) && isset($_POST['password'])) {
            $username = $_POST['username'];
            $password = md5($_POST['password']);
            $user = $this->getUser($username);
            if ($user) {
                if ($user[2] === $password) {
                    $_SESSION['username'] = $username;
                    if (!empty($_POST['edit'])) {
                        $_SESSION['edit'] = true;
                    }
                    header('location: /');
                }
            }
        }
    }

    //
    function getUser($username) {
        if (file_exists(USERS_FILE)) {
            $users = file_get_contents(USERS_FILE);
            $users = explode(PHP_EOL, $users);
            foreach ($users as $user) {
                $user = explode(':', $user);
                if ($user[1] === $username) {
                    return $user;
                }
            }
        }
    }

    //
    function logout() {
        if (isset($_GET['logout'])) {
            session_destroy();
        };
    }

    //
    function getObj () {
        if (!file_exists(siteXML)) die ('Fatal error: .site.xml does not exist');
        $obj = simplexml_load_file(siteXML, 'SimpleXMLElement');
        if (!$obj) {
            die ('Fatal error: .site.xml is not a well formed XML');
        } else {
            return $obj;
        }
    }

    //
    function getPid () {
        $pid = false;
        if (isset($_GET['id'])) {
            $pid = $_GET['id'];
        } else if ($_SERVER['REQUEST_URI'] !== '/') {
            if ($_SERVER['REQUEST_URI'][0] === '/') {
                $alias = substr($_SERVER['REQUEST_URI'], 1);
            } else {
                $alias = $_SERVER['REQUEST_URI'];
            }
            $alias = urldecode($alias);
            $aliasNoEndingSlash = rtrim($alias, "/");
            $pid = $this->getPageIdByAlias($aliasNoEndingSlash);
        }
        if (!$pid) {
            $defaultPid = $this->getDefaultPid();
            if (!$defaultPid) {
                $defaultPid = $this->getFirstPagePid();
            }
            $pid = $defaultPid;
        }
        if (!$pid) {
            die('Fatal error: no pages in this site');
        } else {
            return $pid;
        }
    }

    //recursive
    function getDefaultPid ($pageObj = false) {
        if (!$pageObj) $pageObj = $this->obj;
        $defaultPid = false;
        foreach ($pageObj as $k => $v) {
            if (strtolower($k) == 'page') {
                $attr = $this->attributes($v);
                if (@strtolower($attr['startpage']) == 'yes') {
                    $defaultPid = $attr['id'];
                    break;
                } else {
                    $defaultPid = $this->getDefaultPid($v);
                    if ($defaultPid) break;
                }
            }
        }
        return $defaultPid;
    }

    /*
     * @param {String} $alias - make sure that it doesn't end with slash - '/'
     * */
    function getPageIdByAlias($alias, $parent = false) {
        $pid = false;
        if (!$parent) $parent = $this->obj;
        //can't use xpath because of slashes in alias
        foreach ($parent as $k => $v) {
            if (strtolower($k) === 'page') {
                $attr = $this->attributes($v);
                if (!empty($attr['alias']) && rtrim($attr['alias']) == $alias) {
                    $pid = $attr['id'];
                } else {
                    $pid = $this->getPageIdByAlias($alias, $v);
                }
                if ($pid) break;
            }
        }
        return $pid;
    }

    //
    function getFirstPagePid () {
        $pid = false;
        foreach ($this->obj as $k=>$v) {
            if (strtolower($k) == 'page') {
                $attr = $this->attributes($v);
                $pid = $attr['id'];
                break;
            }
        }
        return $pid;
    }

    //
    function getPageObj ($pid) {
        if ($pid) {
            $pageObj = $this->obj->xpath("//page[@id='$pid']");
        } else {
            $pageObj = $this->obj->xpath("//page");
        }
        if (isset($pageObj[0])) {
            return $pageObj[0];
        } else {
            return false;
        }
    }

    /*
     * @param {Object} page object. If not given, $this->pageObj will be used
     * @returns {Object} theme by page object
     */
    function getTheme($pageObj = false) {
        if (!$pageObj) $pageObj = $this->pageObj;
        $attr = $this->attributes($pageObj);
        if (!empty($attr['theme'])) {
            $themeId = $attr['theme'];
        } else {
            $themeId = false;
        }
        if ($themeId) {
            $themeObj = $this->obj->xpath("//theme[@id='$themeId']");
            if (count($themeObj) <=0 ) {
                $this->error("Error: theme with id $themeId does not exist");
            }
        } else {
            $themeObj = $this->obj->xpath("//theme[contains(translate(@default, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'yes')]");
            if (count($themeObj) <=0 ) {
                $themeObj = $this->obj->xpath("//theme");
            }
        }
        if (isset($themeObj[0])) {
            return $themeObj[0];
        } else {
            return false;
        }
    }

    /*
     * @param {Object} theme || If not given, DEFAULT_THEME_HTML will be returned
     * @returns {String} theme html
     */
    function getThemeHTML($themeObj = false) {
        if (!$themeObj) {
            $this->error('SiteXML error: template does not exist, default template HTML will be used');
            $themeHTML = DEFAULT_THEME_HTML;
        } else {
            $attr = $this->attributes($themeObj);
            $dir = (empty($attr['dir'])) ? '' : $attr['dir'];
            $path = THEMES_DIR;
            if (substr($path, -1) != '/') $path .= '/';
            if (!empty($dir)) $path .= $dir;
            if (substr($path, -1) != '/') $path .= '/';
            if (!empty($attr['file'])) {
                $path .= $attr['file'];
                if (file_exists($path)) {
                    $themeHTML = file_get_contents($path);
                } else {
                    $this->error('SiteXML error: template file does not exist, default template HTML will be used');
                    $themeHTML = DEFAULT_THEME_HTML;
                }
            } else {
                $this->error('SiteXML error: template file missing, default template HTML will be used');
                $themeHTML = DEFAULT_THEME_HTML;
            }
        }
        return $themeHTML;
    }

    //
    function getTitle() {
        $pageObj = $this->pageObj;
        $attr = $this->attributes($pageObj);
        return (isset($attr['title'])) ? $attr['title'] : '';
    }

    //
    function getSiteName () {
        $attr = $this->attributes($this->obj);
        if (isset($attr['name'])) {
            $siteName = $attr['name'];
        } else {
            $siteName = $_SERVER['HTTP_HOST'];
        }
        return $siteName;
    }

    //
    function getSiteBasePath () {
        $attr = $this->attributes($this->obj);
        if (isset($attr['base_path'])) {
            $basePath = $attr['base_path'];
        } else {
            $basePath = null;
        }
        return $basePath;
    }

    //
    function getThemePath ($themeObj = false) {
        if (!$themeObj) $themeObj = $this->themeObj;
        $attr = $this->attributes($themeObj);
        if (isset($attr['dir'])) {
            $dir = $attr['dir'];
            if (substr($dir, -1) != '/') $dir .= '/';
        } else {
            $dir = '';
        }
        if ($this->basePath) {
            $fullPath = '/'. $this->basePath .'/'. THEMES_DIR . $dir;
        } else {
            $fullPath = '/'. THEMES_DIR . $dir;
        }
        return $fullPath;
    }

    //
    function replaceMacroCommands ($HTML) {
        $macroCommands = array(
            '<%THEME_PATH%>',
            '<%SITENAME%>',
            '<%TITLE%>',
            '<%META%>',
            '<%NAVI%>'
        );
        $replacement = array(
            $this->getThemePath(),
            $this->getSiteName(),
            $this->getTitle(),
            $this->getMetaHTML(),
            $this->getNavi()
        );
        $HTML = str_replace($macroCommands, $replacement, $HTML);
        return $HTML;
    }

    //
    function getMetaHTML ($pageObj = false) {
        if (!$pageObj) $pageObj = $this->pageObj;
        $metaHTML = '';
        foreach ($this->obj as $k => $v) {
            if (strtolower($k) == 'meta') {
                $metaHTML .= $this->singleMetaHTML($v);
            }
        }
        foreach ($pageObj as $k => $v) {
            if (strtolower($k) == 'meta') {
                $metaHTML .= $this->singleMetaHTML($v);
            }
        }
        return $metaHTML;
    }

    //
    function singleMetaHTML ($metaObj) {
        $attr = $this->attributes($metaObj);
        $metaHTML = '<meta';
        if ($attr) {
            foreach ($attr as $k => $v) {
                $metaHTML .= " $k=\"$v\"";
            }
        }
        $metaHTML .= ">";
        return $metaHTML;
    }

    //
    function replaceThemeContent ($HTML) {
        return $this->replaceContent($HTML, 'theme');
    }

    //
    function replacePageContent ($HTML) {
        return $this->replaceContent($HTML, 'page');
    }

    //
    function replaceContent($HTML, $where) {
        if ($where == 'page') {
            $obj = $this->pageObj;
        } elseif ($where == 'theme') {
            $obj = $this->themeObj;
        } else {
            return false;
        }

        if ($obj) foreach ($obj as $k => $v) {
            if (strtolower($k) == 'content') {
                $attr = $this->attributes($v);
                $name = @$attr['name'];
                $search = "<%CONTENT($name)%>";
                if (strpos($HTML, $search) !== false) {
                    if (isset($attr['type']) && $attr['type'] == 'module') {
                        $file = MODULES_DIR . $v;
                        if (file_exists($file)) {
                            ob_start();
                            include_once($file);
                            $contents = ob_get_clean();
                        } else {
                            $this->error("Error: module file " . $attr['file'] . " does not exist");
                        }

                    } else {
                        $file = CONTENT_DIR . $v;
                        if (file_exists($file)) {
                            $contents = file_get_contents($file);
                        } else {
                            $contents = $this->error("Error: content file " . $v . " does not exist", true);
                        }
                        $contents = '<div class="siteXML-content" cid="' . $attr['id'] . '" cname="' . $name . '">' . $contents . '</div>';
                    }
                    $HTML = str_replace($search, $contents, $HTML);
                }
            }
        }
        return $HTML;
    }

    //
    function getNavi($obj = false, $maxlevel = 0, $level = 0) {
        $level ++;
        if (!$obj) $obj = $this->obj;
        $HTML = '';
        if ($maxlevel === 0 || $maxlevel >= $level) {
            foreach($obj as $k => $v) {
                if (strtolower($k) == 'page') {
                    $attr = $this->attributes($v);
                    if (isset($attr['nonavi']) && strtolower($attr['nonavi']) === 'yes') {
                        continue;
                    }
                    $liClass = ($attr['id'] == $this->pid) ? ' class="siteXML-current"' : '';
                    $href = (isset($attr['alias'])) ? '/' . $attr['alias'] : '/?id=' . $attr['id'];
                    if ($this->basePath) {
                        $href = '/' . $this->basePath . $href;
                    }

                    $hasContent = false;
                    foreach($v as $i => $tmp) {
                        if (strtolower($i) == 'content') {
                            $hasContent = true;
                            break;
                        }
                    }

                    if ($hasContent) {
                        $HTML .= '<li' . $liClass . ' pid="' . $attr['id'] . '"><a href="' . $href . '" pid="' . $attr['id'] . '">' . $attr['name'] . '</a>';
                    } else {
                        $HTML .= '<li' . $liClass . 'pid="' . $attr['id'] . '">' . $attr['name'] . '';
                    }
                    $HTML .= $this->getNavi($v, $maxlevel, $level);
                    $HTML .= '</li>';
                }
            }
            if ($HTML <> '') $HTML = "<ul class=\"siteXML-navi level-$level\">$HTML</ul>";
        }
        return $HTML;
    }

    //
    function replaceNavi($HTML) {
        $HTML = str_replace('<%NAVI%>', $this->getNavi(), $HTML);
        $pos = strpos($HTML, '<%NAVI', 0);
        while ($pos) {
            $pos1 = strpos($HTML, '(', $pos + 1);
            $pos2 = strpos($HTML, ')', $pos + 1);
            if ($pos1 && $pos2) {
                $arg = substr($HTML, $pos1 + 1 , $pos2 - $pos1 - 1);
                $arg = explode(',', $arg);
            } else {
                $arg = false;
            }
            if ($arg) {
                $needle = "<%NAVI(" . $arg[0] . "," . $arg[1] . ")%>";
                $pageObj = $this->getPageObj($arg[0]);
                $replace = $this->getNavi($pageObj, $arg[1]);
                $HTML = str_replace($needle, $replace, $HTML);
            }
            $pos = strpos($HTML, '<%NAVI', $pos + 1);
        }
        return $HTML;
    }

    //
    function replacePlink($HTML) {
        $pos = strpos($HTML, '<%PLINK');
        while ($pos) {
            $pos1 = strpos($HTML, '(', $pos + 1);
            $pos2 = strpos($HTML, ')', $pos + 1);
            if ($pos1 && $pos2) {
                $arg = substr($HTML, $pos1 + 1 , $pos2 - $pos1 - 1);
            } else {
                $arg = false;
            }
            if ($arg) {
                $needle = "<%PLINK(" . $arg . ")%>";
                $replace = $this->getPlink($arg);
                $HTML = str_replace($needle, $replace, $HTML);
            }
            $pos = strpos($HTML, '<%PLINK', $pos + 1);
        }
        return $HTML;
    }

    //
    function getPlink($id) {
        $pageObj = $this->getPageObj($id);
        $attr = $this->attributes($pageObj);
        if (!empty($attr['alias'])) {
            $href = '/' . $attr['alias'];
        } else {
            $href = '/?id=' . $id;
        }
        $pname = $attr['name'];
        $html = '<a href="'. $href .'" plink="'. $id .'" pid="'. $id .'">'. $pname .'</a>';
        return $html;
    }

    //
    function appendScripts($HTML) {
        $pos = stripos($HTML, "</body>");
        $scripts = '<!--<script src="'. ($this->basePath ? $this->basePath . '/' : '') .'/js/jquery-2.1.3.min.js"></script>-->' .
            '<script src="'. ($this->basePath ? $this->basePath . '/' : '') .'/js/sitexml.js"></script>' .
            AJAX_BROWSING_SCRIPT .
            ($this->editMode ? CONTENT_EDIT_SCRIPT : '');
        if ($pos >= 0) {
            $HTML = substr($HTML, 0, $pos) .
                $scripts .
                substr($HTML, $pos);
        } else {
            $HTML .= $scripts;
        }
        return $HTML;
    }

    //
    function page () {
        $pageHTML = $this->getThemeHTML($this->themeObj);
        $pageHTML = $this->replaceNavi($pageHTML);
        $pageHTML = $this->replacePageContent($pageHTML);
        $pageHTML = $this->replaceThemeContent($pageHTML);
        $pageHTML = $this->replaceMacroCommands($pageHTML);
        $pageHTML = $this->replacePlink($pageHTML);
        $pageHTML = $this->appendScripts($pageHTML);
        return $pageHTML;
    }

    /*
     * Echoes or returns error message
     * @param {String} $error
     * @param {Boolean} $return - return error message instead of just echoing it
     * */
    function error ($error, $return = false) {
        if (DEBUG) {
            if ($return) {
                return "$error\n";
            } else {
                echo "$error\n";
            }
        }
    }

    /*
     * @param {SimpleXML Object} $obj
     * */
    function attributes ($obj) {
        if (!$obj) return false;
        $attr = $obj->attributes();
        $newattr = array();
        foreach ($attr as $k => $v) {
            $newattr[strtolower($k)] = $v;
        }
        return $newattr;
    }

    //
    function getXML () {
        return file_get_contents(siteXML);
    }

    //
    function saveXML ($xmlstr) {
        return file_put_contents(siteXML, $xmlstr);
    }

    //
    function saveContent ($cid, $content) {
        $file = $this->obj->xpath("//content[@id='$cid']");
        $file = CONTENT_DIR . $file[0];
        if (file_exists($file)) {
            if (file_put_contents($file, $content)) {
                echo 'Content saved';
            } else {
                header("HTTP/1.0 500 Server Error");
                $this->error('Error: Content not saved: ' . $file);
            };
        } else {
            header("HTTP/1.0 404 Not Found");
            $this->error('Error: Content file ' . $file . ' does not exist');
        }
    }

    //
    function getContentByIdAndName ($id, $name) {
        $c = $this->obj->xpath("//page[@id='$id']/content[@name='$name']");
        $attr = $this->attributes($c[0]);
        $cid = $attr['id'];
        return $this->getContent($cid, $c);
    }

    /*
     * @param {Integer | String} $cid - content id
     * @param {XML Object} $cobj - not required; content node object
     * */
    function getContent ($cid, $cobj = false) {
        if (!$cobj) {
            $file = $this->obj->xpath("//content[@id='$cid']");
        } else {
            $file = $cobj;
        }

        $file = CONTENT_DIR . $file[0];
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $content = $this->replacePLINK($content);
        } else {
            $content = false;
            http_response_code(404);
        }
        return $content;
    }
}