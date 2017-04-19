<?php
// light feed reader
define('DATA_DIR', 'data');
define('INC_DIR', 'inc');
define('CACHE_DIR', DATA_DIR.'/cache');
define('FAVICON_DIR', INC_DIR.'/favicon');

define('DATA_FILE', DATA_DIR.'/data.php');
define('CONFIG_FILE', DATA_DIR.'/config.php');
define('STYLE_FILE', 'style.css');

define('BAN_FILE', DATA_DIR.'/ipbans.php');

define('FEED_VERSION', 1.0);

define('PHPPREFIX', '<?php /* '); // Prefix to encapsulate data in php code.
define('PHPSUFFIX', ' */ ?>'); // Suffix to encapsulate data in php code.

define('MIN_TIME_UPDATE', 5); // Minimum accepted time for update

define('ERROR_NO_ERROR', 0);
define('ERROR_NO_XML', 1);
define('ERROR_ITEMS_MISSED', 2);
define('ERROR_LAST_UPDATE', 3);
define('ERROR_UNKNOWN', 4);

// fix some warning
date_default_timezone_set('Europe/London');

if (!is_dir(DATA_DIR)) {
    if (!@mkdir(DATA_DIR, 0755)) {
        echo '
<script>
 alert("Error: can not create '.DATA_DIR.' directory, check permissions");
 document.location=window.location.href;
</script>';
        exit();
    }
    @chmod(DATA_DIR, 0755);
    if (!is_file(DATA_DIR.'/.htaccess')) {
        if (!@file_put_contents(
                DATA_DIR.'/.htaccess',
                "Allow from none\nDeny from all\n"
                )) {
            echo '
<script>
 alert("Can not protect '.DATA_DIR.'");
 document.location=window.location.href;
</script>';
            exit();
        }
    }
}

/* function grabFavicon */
function grabFavicon($url, $feedHash){
    $url = 'http://getfavicon.appspot.com/'.$url.'?defaulticon=bluepng';
    $file = FAVICON_DIR.'/favicon.'.$feedHash.'.ico';

    if(!file_exists($file) && in_array('curl', get_loaded_extensions()) && Session::isLogged()){
        $ch = curl_init ($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        $raw = curl_exec($ch);
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
            $fp = fopen($file, 'x');
            fwrite($fp, $raw);
            fclose($fp);
        }
        curl_close ($ch);
    }

    if (file_exists($file)) {
        return $file;
    } else {
        return $url;
    }
}

/**
 * autoload class
 *
 * @param string $className The name of the class to load
 */
function __autoload($className)
{
    include_once 'class/'. $className . '.php';
}

// Check if php version is correct
MyTool::initPHP();
// Initialize Session
Session::init(BAN_FILE);
// XSRF protection with token
if (!empty($_POST)) {
    if (!Session::isToken($_POST['token'])) {
        die('Wrong token.');
    }
}
unset($_SESSION['tokens']);

$pb = new PageBuilder('FeedPage');
$kfp = new FeedPage(STYLE_FILE);
$lfc = new FeedConf(CONFIG_FILE, FEED_VERSION);
$lf = new Feed(DATA_FILE, CACHE_DIR, $lfc);

// List or Expanded ?
$view = $lfc->view;
// show or hide list of feeds ?
$listFeeds =  $lfc->listFeeds;
// All or Unread ?
$filter =  $lfc->filter;
// newerFirst or olderFirst
$order =  $lfc->order;
// number of item by page
$byPage = $lfc->getByPage();
// Hash : 'all', feed hash or folder hash
$currentHash = $lfc->getCurrentHash();
// Query
$query = '?';
if (!empty($currentHash) and $currentHash !== 'all') {
    $query = '?currentHash='.$currentHash.'&amp;';
}

$pb->assign('view', $view);
$pb->assign('listFeeds', $listFeeds);
$pb->assign('filter', $filter);
$pb->assign('order', $order);
$pb->assign('byPage', $byPage);
$pb->assign('currentHash', $currentHash);
$pb->assign('query', $query);
$pb->assign('redirector', $lfc->redirector);
$pb->assign('shaarli', htmlspecialchars($lfc->shaarli));
$pb->assign('autoreadItem', $lfc->autoreadItem);
$pb->assign('autoreadPage', $lfc->autoreadPage);
$pb->assign('autohide', $lfc->autohide);
$pb->assign('autofocus', $lfc->autofocus);
$pb->assign('autoupdate', $lfc->autoUpdate);
$pb->assign('addFavicon', $lfc->addFavicon);
$pb->assign('version', FEED_VERSION);
$pb->assign('kfurl', MyTool::getUrl());

if (isset($_GET['login'])) {
    // Login
    if (!empty($_POST['login'])
        && !empty($_POST['password'])
    ) {
        if (Session::login(
            $lfc->login,
            $lfc->hash,
            $_POST['login'],
            sha1($_POST['password'].$_POST['login'].$lfc->salt)
        )) {
            if (!empty($_POST['longlastingsession'])) {
                // (31536000 seconds = 1 year)
                $_SESSION['longlastingsession'] = 31536000;
                $_SESSION['expires_on'] =
                    time() + $_SESSION['longlastingsession'];
                session_set_cookie_params($_SESSION['longlastingsession']);
            } else {
                session_set_cookie_params(0); // when browser closes
            }
            session_regenerate_id(true);

            MyTool::redirect();
        }
        die("Login failed !");
    } else {
        $pb->assign('pagetitle', 'Login - '.strip_tags($lfc->title));
        $pb->renderPage('login');
    }
} elseif (isset($_GET['logout'])) {
    //Logout
    Session::logout();
    MyTool::redirect();
} elseif (isset($_GET['ajax'])) {
    $lf->loadData();
    $needSave = false;
    $result = array();
    if (isset($_GET['current'])) {
        $result['item'] = $lf->getItem($_GET['current'], false);
        $result['item']['itemHash'] = $_GET['current'];
    }
    if (isset($_GET['read'])) {
        $needSave = $lf->mark($_GET['read'], 1);
        if ($needSave) {
            $result['read'] = $_GET['read'];
        }
    }
    if (isset($_GET['unread'])) {
        $needSave = $lf->mark($_GET['unread'], 0);
        if ($needSave) {
            $result['unread'] = $_GET['unread'];
        }
    }
    if (isset($_GET['toggleFolder'])) {
        $needSave = $lf->toggleFolder($_GET['toggleFolder']);
    }
    if (isset($_GET['page'])) {
        $listItems = $lf->getItems($currentHash, $filter);
        $currentPage = $_GET['page'];
        $index = ($currentPage - 1) * $byPage;
        $results = array_slice($listItems, $index, $byPage + 1, true);
        $result['page'] = array();
        $firstIndex = -1;
        if (isset($_GET['last'])) {
            $firstIndex = array_search($_GET['last'], array_keys($results));
            if ($firstIndex === false) {
                $firstIndex = -1;
            }
        }
        $i = 0;
        foreach(array_slice($results, $firstIndex + 1, count($results) - $firstIndex - 1, true) as $itemHash => $item) {
            $result['page'][$i] = $lf->getItem($itemHash, false);
            $result['page'][$i]['read'] = $item[1];
            $i++;
        }
    }
    if (isset($_GET['update'])) {
        if (Session::isLogged()) {
            if (empty($_GET['update'])) {
                $result['update']['feeds'] = array();
                $feedsHash = $lf->orderFeedsForUpdate(array_keys($lf->getFeeds()));
                foreach ($feedsHash as $feedHash) {
                    $feed = $lf->getFeed($feedHash);
                    $result['update']['feeds'][] = array($feedHash, $feed['title'], (int) ((time() - $feed['lastUpdate']) / 60), $lf->getTimeUpdate($feed));
                }
            } else {
                $feed = $lf->getFeed($_GET['update']);
                $info = $lf->updateChannel($_GET['update']);
                if (empty($info['error'])) {
                    $info['error'] = $feed['description'];
                } else {
                    $info['error'] = $lf->getError($info['error']);
                }
                $info['newItems'] = array_keys($info['newItems']);
                $result['update'] = $info;
            }
        } else {
            $result['update'] = false;
        }
    }
    if ($needSave) {
        $lf->writeData();
    }
    MyTool::renderJson($result);
} elseif (isset($_GET['help'])) {
    $pb->assign('pagetitle', 'Help for light feed reader');
    $pb->renderPage('help');
} elseif ((isset($_GET['update'])
          && (Session::isLogged()
              || (isset($_GET['cron'])
                  && $_GET['cron'] === sha1($lfc->salt.$lfc->hash))))
          || (isset($argv)
              && count($argv) >= 3
              && $argv[1] == 'update'
              && $argv[2] == sha1($lfc->salt.$lfc->hash))) {
    // Update
    $lf->loadData();
    $forceUpdate = false;
    if (isset($_GET['force'])) {
        $forceUpdate = true;
    }
    $feedsHash = array();
    $hash = 'all';
    if (isset($_GET['update'])) {
        $hash = $_GET['update'];
    }
    // type : 'feed', 'folder', 'all', 'item'
    $type = $lf->hashType($hash);
    switch($type) {
    case 'feed':
        $feedsHash[] = $hash;
        break;
    case 'folder':
        $feedsHash = $lf->getFeedsHashFromFolderHash($hash);
        break;
    case 'all':
    case '':
        $feedsHash = array_keys($lf->getFeeds());
        break;
    case 'item':
    default:
        break;
    }
    if (isset($_GET['cron']) || isset($argv) && count($argv) >= 3) {
        $lf->updateFeedsHash($feedsHash, $forceUpdate);
    } else {
        $pb->assign('lf', $lf);
        $pb->assign('feedsHash', $feedsHash);
        $pb->assign('forceUpdate', $forceUpdate);
        $pb->assign('pagetitle', 'Update');
        $pb->renderPage('update');
    }
} elseif (isset($_GET['config']) && Session::isLogged()) {
    // Config
    if (isset($_POST['save'])) {
        if (isset($_POST['disableSessionProtection'])) {
            $_POST['disableSessionProtection'] = '1';
        } else {
            $_POST['disableSessionProtection'] = '0';
        }
        $lfc->hydrate($_POST);
        MyTool::redirect();
    } elseif (isset($_POST['cancel'])) {
        MyTool::redirect();
    } else {
        $menu = $lfc->getMenu();
        $paging = $lfc->getPaging();

        $pb->assign('page', 'config');
        $pb->assign('pagetitle', 'Config - '.strip_tags($lfc->title));
        $pb->assign('kfctitle', htmlspecialchars($lfc->title));
        $pb->assign('kfcredirector', htmlspecialchars($lfc->redirector));
        $pb->assign('kfcshaarli', htmlspecialchars($lfc->shaarli));
        $pb->assign('kfclocale', htmlspecialchars($lfc->locale));
        $pb->assign('kfcmaxitems', htmlspecialchars($lfc->maxItems));
        $pb->assign('kfcmaxupdate', htmlspecialchars($lfc->maxUpdate));
        $pb->assign('kfcpublic', (int) $lfc->public);
        $pb->assign('kfccron', sha1($lfc->salt.$lfc->hash));
        $pb->assign('kfcautoreaditem', (int) $lfc->autoreadItem);
        $pb->assign('kfcautoreadpage', (int) $lfc->autoreadPage);
        $pb->assign('kfcautoupdate', (int) $lfc->autoUpdate);
        $pb->assign('kfcautohide', (int) $lfc->autohide);
        $pb->assign('kfcautofocus', (int) $lfc->autofocus);
        $pb->assign('kfcaddfavicon', (int) $lfc->addFavicon);
        $pb->assign('kfcdisablesessionprotection', (int) $lfc->disableSessionProtection);
        $pb->assign('kfcmenu', $menu);
        $pb->assign('kfcpaging', $paging);

        $pb->renderPage('config');
    }
} elseif (isset($_GET['import']) && Session::isLogged()) {
    // Import
    if (isset($_POST['import'])) {
        // If file is too big, some form field may be missing.
        if ((!isset($_FILES))
            || (isset($_FILES['filetoupload']['size'])
            && $_FILES['filetoupload']['size']==0)
        ) {
            $rurl = empty($_SERVER['HTTP_REFERER'])
                ? '?'
                : $_SERVER['HTTP_REFERER'];
            echo '<script>alert("The file you are trying to upload'
                . ' is probably bigger than what this webserver can accept '
                . '(' . MyTool::humanBytes(MyTool::getMaxFileSize())
                . ' bytes). Please upload in smaller chunks.");'
                . 'document.location=\'' . htmlspecialchars($rurl)
                . '\';</script>';
            exit;
        }
        
        $lf->loadData();
        $lf->setData(Opml::importOpml($lf->getData()));
        $lf->sortFeeds();
        $lf->writeData();
        exit;
    } else if (isset($_POST['cancel'])) {
        MyTool::redirect();
    } else {
        $pb->assign('pagetitle', 'Import');
        $pb->renderPage('import');
    }
} elseif (isset($_GET['export']) && Session::isLogged()) {
    // Export
    $lf->loadData();
    Opml::exportOpml($lf->getFeeds(), $lf->getFolders());
} elseif (isset($_GET['add']) && Session::isLogged()) {
    // Add feed
    $lf->loadData();

    if (isset($_POST['newfeed']) && !empty($_POST['newfeed'])) {
        if ($lf->addChannel($_POST['newfeed'])) {
            // Add success
            $folders = array();
            if (!empty($_POST['folders'])) {
                foreach ($_POST['folders'] as $hashFolder) {
                    $folders[] = $hashFolder;
                }
            }
            if (!empty($_POST['newfolder'])) {
                $newFolderHash = MyTool::smallHash($_POST['newfolder']);
                $lf->addFolder($_POST['newfolder'], $newFolderHash);
                $folders[] = $newFolderHash;
            }
            $hash = MyTool::smallHash($_POST['newfeed']);
            $lf->editFeed($hash, '', '', $folders, '');
            $lf->sortFeeds();
            $lf->writeData();
            MyTool::redirect('?currentHash='.$hash);
        } else {
            // Add fail
            $returnurl = empty($_SERVER['HTTP_REFERER'])
                ? MyTool::getUrl()
                : $_SERVER['HTTP_REFERER'];
            echo '<script>alert("The feed you are trying to add already exists'
                . ' or is wrong. Check your feed or try again later.");'
                . 'document.location=\'' . htmlspecialchars($returnurl)
                . '\';</script>';
            exit;
        }
    }

    $newfeed = '';
    if (isset($_GET['newfeed'])) {
        $newfeed = htmlspecialchars($_GET['newfeed']);
    }
    $pb->assign('page', 'add');
    $pb->assign('pagetitle', 'Add a new feed');
    $pb->assign('newfeed', $newfeed);
    $pb->assign('folders', $lf->getFolders());
    
    $pb->renderPage('addFeed');
} elseif (isset($_GET['toggleFolder']) && Session::isLogged()) {
    $lf->loadData();
    if (isset($_GET['toggleFolder'])) {
        $lf->toggleFolder($_GET['toggleFolder']);
    }
    $lf->writeData();
    MyTool::redirect();
} elseif ((isset($_GET['read'])
           || isset($_GET['unread']))
          && Session::isLogged()) {
    // mark all as read : item, feed, folder, all
    $lf->loadData();

    $read = 1;
    if (isset($_GET['read'])) {
        $hash = $_GET['read'];
        $read = 1;
    } else {
        $hash = $_GET['unread'];
        $read = 0;
    }

    $needSave = $lf->mark($hash, $read);
    if ($needSave) {
        $lf->writeData();
    }

    // type : 'feed', 'folder', 'all', 'item'
    $type = $lf->hashType($hash);
    if ($type === 'item') {
        MyTool::redirect($query.'current='.$hash);
    } else {
        if ($filter === 'unread' && $read === 1) {
            MyTool::redirect('?');
        } else {
            MyTool::redirect($query);
        }
    }
} elseif (isset($_GET['edit']) && Session::isLogged()) {
    // Edit feed, folder, all
    $lf->loadData();
    $pb->assign('page', 'edit');
    $pb->assign('pagetitle', 'edit');
    
    $hash = substr(trim($_GET['edit'], '/'), 0, 6);
// type : 'feed', 'folder', 'all', 'item'
$type = $lf->hashType($currentHash);
    $type = $lf->hashType($hash);
    switch($type) {
    case 'feed':
        if (isset($_POST['save'])) {
            $title = $_POST['title'];
            $description = $_POST['description'];
            $folders = array();
            if (!empty($_POST['folders'])) {
                foreach ($_POST['folders'] as $hashFolder) {
                    $folders[] = $hashFolder;
                }
            }
            if (!empty($_POST['newfolder'])) {
                $newFolderHash = MyTool::smallHash($_POST['newfolder']);
                $lf->addFolder($_POST['newfolder'], $newFolderHash);
                $folders[] = $newFolderHash;
            }
            $timeUpdate = $_POST['timeUpdate'];

            $lf->editFeed($hash, $title, $description, $folders, $timeUpdate);
            $lf->writeData();

            MyTool::redirect();
        } elseif (isset($_POST['delete'])) {
            $lf->removeFeed($hash);
            $lf->writeData();

            MyTool::redirect('?');
        } elseif (isset($_POST['cancel'])) {
            MyTool::redirect();
        } else {
            $feed = $lf->getFeed($hash);
            if (!empty($feed)) {
                $lastUpdate = 'need update';
                if (!$lf->needUpdate($feed)) {
                    $diff = (int) (time() - $feed['lastUpdate']);
                    $lastUpdate =
                        (int) ($diff / 60) . ' m ' . (int) ($diff % 60) . ' s';
                }

                $pb->assign('feed', $feed);
                $pb->assign('folders', $lf->getFolders());
                $pb->assign('lastUpdate', $lastUpdate);
                $pb->renderPage('editFeed');
            } else {
                MyTool::redirect();
            }
        }
        break;
    case 'folder':
        if (isset($_POST['save'])) {
            $oldFolderTitle = $lf->getFolderTitle($hash);
            $newFolderTitle = $_POST['foldertitle'];
            if ($oldFolderTitle !== $newFolderTitle) {
                $lf->renameFolder($hash, $newFolderTitle);
                $lf->writeData();
            }

            if (empty($newFolderTitle)) {
                MyTool::redirect('?');
            } else {
                MyTool::redirect('?currentHash='.MyTool::smallHash($newFolderTitle));
            }
        } elseif (isset($_POST['cancel'])) {
            MyTool::redirect();
        } else {
            $folderTitle = $lf->getFolderTitle($hash);
            $pb->assign('foldertitle', htmlspecialchars($folderTitle));
            $pb->renderPage('editFolder');
        }
        break;
    case 'all':
        if (isset($_POST['save'])) {

            $feedsHash = array();
            foreach ($_POST['feeds'] as $feedHash) {
                $feedsHash[] = $feedHash;
            }

            foreach ($feedsHash as $feedHash) {
                $feed = $lf->getFeed($feedHash);
                $addFoldersHash = $feed['foldersHash'];
                if (!empty($_POST['addfolders'])) {
                    foreach ($_POST['addfolders'] as $folderHash) {
                        if (!in_array($folderHash, $addFoldersHash)) {
                            $addFoldersHash[] = $folderHash;
                        }
                    }
                }
                if (!empty($_POST['addnewfolder'])) {
                    $newFolderHash = MyTool::smallHash($_POST['addnewfolder']);
                    $lf->addFolder($_POST['addnewfolder'], $newFolderHash);
                    $addFoldersHash[] = $newFolderHash;
                }
                $removeFoldersHash = array();
                if (!empty($_POST['removefolders'])) {
                    foreach ($_POST['removefolders'] as $folderHash) {
                        $removeFoldersHash[] = $folderHash;
                    }
                }
                $addFoldersHash = array_diff($addFoldersHash, $removeFoldersHash);

                $lf->editFeed(
                    $feedHash,
                    '',
                    '',
                    $addFoldersHash,
                    ''
                );
            }
            $lf->writeData();

            MyTool::redirect();
        } elseif (isset($_POST['delete'])) {
            foreach ($_POST['feeds'] as $feedHash) {
                $lf->removeFeed($feedHash);
            }
            $lf->writeData();

            MyTool::redirect();
        } elseif (isset($_POST['cancel'])) {
            MyTool::redirect();
        } else {
            $folders = $lf->getFolders();
            $listFeeds = $lf->getFeeds();
            $pb->assign('folders', $folders);
            $pb->assign('listFeeds', $listFeeds);
            $pb->renderPage('editAll');
        }
        break;
    case 'item':
    default:
        MyTool::redirect();
        break;
    }
} elseif (isset($_GET['shaarli'])) {
    $lf->loadData();
    $item = $lf->getItem($_GET['shaarli'], false);
    $shaarli = $lfc->shaarli;
    // remove sel used with javascript
    $shaarli = str_replace('${sel}', '', $shaarli);

    $url = htmlspecialchars_decode($item['link']);
    $via = htmlspecialchars_decode($item['via']);
    $title = htmlspecialchars_decode($item['title']);

    if (parse_url($url, PHP_URL_HOST) !== parse_url($via, PHP_URL_HOST)) {
        $via = 'via '.$via;
    } else {
        $via = '';
    }

    $shaarli = str_replace('${url}', urlencode($url), $shaarli);
    $shaarli = str_replace('${title}', urlencode($title), $shaarli);
    $shaarli = str_replace('${via}', urlencode($via), $shaarli);

    header('Location: '.$shaarli);
} else {
    if (Session::isLogged() || $lfc->public) {
        $lf->loadData();
        if ($lf->updateItems()) {
            $lf->writeData();
        }

        $listItems = $lf->getItems($currentHash, $filter);
        $listHash = array_keys($listItems);

        $currentItemHash = '';
        if (isset($_GET['current']) && !empty($_GET['current'])) {
            $currentItemHash = $_GET['current'];
        }
        if (isset($_GET['next']) && !empty($_GET['next'])) {
            $currentItemHash = $_GET['next'];
            if ($lfc->autoreadItem) {
                if ($lf->mark($currentItemHash, 1)) {
                    if ($filter == 'unread') {
                        unset($listItems[$currentItemHash]);
                    }
                    $lf->writeData();
                }
            }
        }
        if (isset($_GET['previous']) && !empty($_GET['previous'])) {
            $currentItemHash = $_GET['previous'];
        }
        if (empty($currentItemHash)) {
            $currentPage = $lfc->getCurrentPage();
            $index = ($currentPage - 1) * $byPage;
        } else {
            $index = array_search($currentItemHash, $listHash);
            if (isset($_GET['next'])) {
                if ($index < count($listHash)-1) {
                    $index++;
                } 
            }

            if (isset($_GET['previous'])) {
                if ($index > 0) {
                    $index--;
                }
            }
        }
        if ($index < count($listHash)) {
            $currentItemHash = $listHash[$index];
        } else {
            $index = count($listHash) - 1;
        }

        $unread = 0;
        foreach ($listItems as $itemHash => $item) {
            if ($item[1] === 0) {
                $unread++;
            }
        }

        // pagination
        $currentPage = (int) ($index/$byPage)+1;
        if ($currentPage <= 0) {
            $currentPage = 1;
        }
        $begin = ($currentPage - 1) * $byPage;
        $maxPage = (count($listItems) <= $byPage) ? '1' : ceil(count($listItems) / $byPage);
        $nbItems = count($listItems);

        // list items
        $listItems = array_slice($listItems, $begin, $byPage, true);

        // type : 'feed', 'folder', 'all', 'item'
        $currentHashType = $lf->hashType($currentHash);
        $hashView = '';
        switch($currentHashType){
        case 'all':
            $hashView = '<span id="nb-unread">'.$unread.'</span><span class="hidden-phone"> unread items</span>';
            break;
        case 'feed':
            $hashView = 'Feed (<a href="'.$lf->getFeedHtmlUrl($currentHash).'" title="">'.$lf->getFeedTitle($currentHash).'</a>): '.'<span id="nb-unread">'.$unread.'</span><span class="hidden-phone"> unread items</span>';
            break;
        case 'folder':
            $hashView = 'Folder ('.$lf->getFolderTitle($currentHash).'): <span id="nb-unread">'.$unread.'</span><span class="hidden-phone"> unread items</span>';
            break;
        default:
            $hashView = '<span id="nb-unread">'.$unread.'</span><span class="hidden-phone"> unread items</span>';
            break;
        }

        $menu = $lfc->getMenu();
        $paging = $lfc->getPaging();
        $pb->assign('menu',  $menu);
        $pb->assign('paging',  $paging);
        $pb->assign('currentHashType', $currentHashType);
        $pb->assign('currentHashView', $hashView);
        $pb->assign('currentPage',  (int) $currentPage);
        $pb->assign('maxPage', (int) $maxPage);
        $pb->assign('currentItemHash', $currentItemHash);
        $pb->assign('nbItems', $nbItems);
        $pb->assign('items', $listItems);
        if ($listFeeds == 'show') {
            $pb->assign('feedsView', $lf->getFeedsView());
        }
        $pb->assign('lf',  $lf);
        $pb->assign('pagetitle', strip_tags($lfc->title));

        $pb->renderPage('index');
    } else {
        $pb->assign('pagetitle', 'Login - '.strip_tags($lfc->title));
        if (!empty($_SERVER['QUERY_STRING'])) {
            $pb->assign('referer', MyTool::getUrl().'?'.$_SERVER['QUERY_STRING']);
        }
        $pb->renderPage('login');
    }
}
//print(number_format(microtime(true)-START_TIME,3).' secondes');
