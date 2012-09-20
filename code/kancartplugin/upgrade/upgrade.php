<?php

if (!isset($_SESSION['ALLOW_UPGRADE']) && $_SESSION['ALLOW_UPGRADE'] === false) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class KCUpgradeLog {

    public static $steps = array();
    public static $result = 'fail';
    public static $logs = array();

    public static function setResult($result = false) {
        KCUpgradeLog::$result = $result ? 'success' : 'fail';
    }

    public static function setStepResult($step, $result = false) {
        KCUpgradeLog::$steps[$step] = $result;
    }

    public static function appendLog($step, $log = '') {
        if (!isset(KCUpgradeLog::$logs[$step])) {
            KCUpgradeLog::$logs[$step] = array();
        }
        KCUpgradeLog::$logs[$step][] = $log;
    }

    public static function getData($type = 'json') {
        if ($type == 'json') {
            return array('result' => KCUpgradeLog::$result,
                'steps' => KCUpgradeLog::$steps,
                'logs' => KCUpgradeLog::$logs);
        }
    }

}

require_once 'kancartplugin/upgrade/upgrade_core.php';

if (!isset($_SESSION['PLUGINDOWNLOADSESSID']) || isset($_REQUEST['PHPSESSID'])) {
    $_SESSION['PLUGINDOWNLOADSESSID'] = $_REQUEST['PHPSESSID'];
}

if (!isset($_SESSION['plugin_path']) || isset($_REQUEST['plugin_path'])) {
    $_SESSION['plugin_path'] = $_REQUEST['plugin_path'];
}

if (!isset($_SESSION['app_version']) || isset($_REQUEST['app_version'])) {
    $_SESSION['app_version'] = $_REQUEST['app_version'];
}

if (isset($_REQUEST['user_language']) || isset($_REQUEST['user_language'])) {
    $user_lang = $_REQUEST['user_language'];
}


define('PLUGINPATH', $_SERVER["DOCUMENT_ROOT"] . '/kancartplugin');


if (isset($_REQUEST['action']) && $_REQUEST['action'] == "access_check") {
    $access_check = getUnwritableFileLiest();
    if(count($access_check) == 0){
        $access_result = true;
    }else{
        $access_result = false;
    }
    $result = json_encode(array('result' => $access_result, 'info' => $access_check));
    die($result);
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == "php_mode") {
    $result = json_encode(php_mode_upgrade());
    die($result);
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == "ftp_mode") {
    $result = json_encode(ftp_mode_upgrade());
    die($result);
}

//if ($step == 'update') {
//    if (!isset($_REQUEST["useftp"]) || $_REQUEST["useftp"] != 1) {
//        php_mode_upgrade();
//    } else {
//        ftp_mode_upgrade();
//    }
//} else {
//    // ShowInfo();
//}

function php_mode_upgrade() {
    KCUpgradeLog::appendLog('Start', 'Using php functions');

    $fileSystemPluginUpgrader = new PluginFileSystemUpgrader(PLUGINPATH,
                    'http://www.kancart.com/download_plugin.php?cart=zencart&php_id=' . $_SESSION['PLUGINDOWNLOADSESSID'],
                    PLUGINPATH . '/kancartplugin' . $_SESSION['app_version'] . '.zip',
                    ZENCART_PLUGIN_VERSION
    );
    $execResult = $fileSystemPluginUpgrader->upgrade('/' . $_SESSION['plugin_path'] . '/kancartplugin');
    KCUpgradeLog::setResult($execResult);
    return (KCUpgradeLog::getData());
}

function ftp_mode_upgrade() {
    try {
        KCUpgradeLog::appendLog('Start', 'Using ftp upgrade');
        $ftpClient = new FtpClient();

        $ftpClient->connect('ftp://' . trim($_REQUEST['username']) . ':' . trim($_REQUEST['password']) . '@' . trim($_REQUEST['host']) . ':' . trim($_REQUEST['port']));
        KCUpgradeLog::appendLog('Start', 'FTP 连接成功');
        // connect sucessfully
        KCUpgradeLog::appendLog('Start', "当前工作路径:$ftpClient->pwd()");
        // current working dir:$ftpClient->pwd()

        $ftpUpgrader = new PluginFtpUpgrader($ftpClient);
        $remotePluginPath = $_REQUEST['path'] . '/kancartplugin'; // '/zencart/public_html/kancartplugin'

        KCUpgradeLog::appendLog('Start', "remote plugin path: $remotePluginPath");
        $ftpUpgrader->upgrade($remotePluginPath, 'http://www.kancart.com/download_plugin.php?cart=zencart&php_id=' . $_SESSION['PLUGINDOWNLOADSESSID'], ZENCART_PLUGIN_VERSION, $_SESSION['plugin_path'] . '/kancartplugin');
        KCUpgradeLog::setResult(true);
    } catch (Exception $e) {
        KCUpgradeLog::appendLog('Start', "Exception: $e");
        KCUpgradeLog::setResult(false);
    }
    return (KCUpgradeLog::getData());
}

function Writable() {
    if (count(getUnwritableFileLiest()) > 0) {
        return false;
    }
    return true;
}

function getUnwritableFileLiest() {
    $files = array();

    if (!is_writable(PLUGINPATH)) {
        $files[] = PLUGINPATH;
    }

    $filesystemIterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(PLUGINPATH), RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($filesystemIterator as $fullName => $splObject) {
        if ($splObject->isDir()) {
            $path = $splObject->__toString();
            $pathLen = strlen($path);
            if (substr($path, $pathLen - 1) !== '.') {
                if (!$splObject->isWritable()) {
                    if (!@chmod($path, 0777)) {
                        $files[] = $path;
                    }
                }
            }
        }
    }

    return $files;
}

function getBackupHistory () {
    $backupList = array();
    if (file_exists('kancartplugin/backup') && is_dir('kancartplugin/backup')) {
        
    }
}

include('kancartplugin/upgrade/zencart_upgrade_interface.php');
?>