<?php

class FtpClient {

    /**
     * Connection object
     *
     * @var resource
     */
    protected $connection = false;
    protected $connected = false;

    /**
     * Check connected, throw exception if not
     *
     * @throws Exception
     * @return null
     */
    protected function checkConnectionEstablished() {
        if (!$this->connection) {
            throw new Exception(__CLASS__ . " - no connection established with ftp server.");
        }
    }

    public function raw($cmd) {
        $this->checkConnectionEstablished();
        return @ftp_raw($this->connection, $cmd);
    }

    public function dirExists($dir) {
        $this->checkConnectionEstablished();
        $path = $this->abspath($dir);
        if ($this->chdir($path)) {
            return true;
        }
        return false;
    }

    public function rename($oldName, $newName) {
        $this->checkConnectionEstablished();
        $oldName = $this->abspath($oldName);
        $newName = $this->abspath($newName);


        return @ftp_rename($this->connection, $oldName, $newName);
    }

    public function pwd() {
        $d = $this->raw("pwd");
        $data = explode(" ", $d[0], 3);
        if (empty($data[1])) {
            return false;
        }
        if (intval($data[0]) != 257) {
            return false;
        }
        $out = trim($data[1], '"');
        if ($out !== "/") {
            $out = rtrim($out, "/");
        }
        return $out;
    }

    private function getRemoteFileDir($remoteFile) {
        if (substr($remoteFile, 0, 1) != '/') {
            $currentDir = $this->pwd();
            if ($currentDir) {
                if ($currentDir == '/') {
                    $remoteFile = $currentDir . $remoteFile;
                } else {
                    $remoteFile = $currentDir . '/' . $remoteFile;
                }
            }
        }
        $lastInfexOfSlash = strrpos($remoteFile, '/');
        $sub = substr($remoteFile, 0, $lastInfexOfSlash);
        $pathArray = explode('/', $sub);
        return $pathArray;
    }

    public function deleteFile($filePath) {
        $this->checkConnectionEstablished();
        return @ftp_delete($this->connection, $filePath);
    }

    public function mkdirs($remoteFile) {
        $pathArray = $this->getRemoteFileDir($remoteFile);
        $absPath = '/';
        $this->chdir($absPath);
        foreach ($pathArray as $path) {
            if (!empty($path)) {
                if ($absPath == '/') {
                    $absPath = $absPath . $path;
                } else {
                    $absPath = $absPath . '/' . $path;
                }
                if (!$this->chdir($absPath)) {
                    KCUpgradeLog::appendLog('Backuping', "创建文件夹：$absPath");
                    if (!@ftp_mkdir($this->connection, $absPath)) {
                        // try to change the parent directory mode
                        KCUpgradeLog::appendLog('Backuping', "更改文件夹权限：$absPath to 0755");
                        if (!@ftp_mkdir($this->connection, $absPath)) {
                            if (@ftp_chmod($this->connection, 0755, dirname($absPath))) {
                                if (!@ftp_mkdir($this->connection, $absPath)) {
                                    throw new Exception('Failed to create directory(' . $absPath . '),please check permissions .');
                                }
                            } else {
                                throw new Exception('Failed to create directory(' . $absPath . '),please check permissions .');
                            }
                        }
                    } else {
                        KCUpgradeLog::appendLog('Backuping', "创建文件夹：$absPath 成功");
                    }
                }
            }
        }
        return true;
    }

    public function mkdir($dir) {
        $this->checkConnectionEstablished();
        $dir = $this->abspath($dir);
        return ftp_mkdir($this->connection, $dir);
    }

    /**
     * Make dir recursive
     *
     * @param string $path
     * @param int $mode
     */
    public function mkdirRecursive($path, $mode = 0755) {
        $this->checkConnectionEstablished();
        $dir = explode('/', $path);
        $path = "";
        $ret = true;
        for ($i = 0; $i < count($dir); $i++) {
            $path .= "/" . $dir[$i];
            if (!@ftp_chdir($this->connection, $path)) {
                @ftp_chdir($this->connection, "/");
                if (!@ftp_mkdir($this->connection, $path)) {
                    $ret = false;
                    break;
                } else {
                    @ftp_chmod($this->connection, $mode, $path);
                }
            }
        }
        return $ret;
    }

    function quit() {
        $this->checkConnectionEstablished();
        @ftp_quit($this->connection);
        $this->connection = false;
    }

    /**
     * Try to login to server
     *
     * @param string $login
     * @param string $password
     * @throws Exception on invalid login credentials
     * @return boolean
     */
    public function login($login = "anonymous", $password = "test@gmail.com") {
        $this->checkConnectionEstablished();
        $res = @ftp_login($this->connection, $login, $password);
        if (!$res) {
            throw new Exception("Invalid login credentials");
        }
        return $res;
    }

    /**
     * Validate connection string
     *
     * @param string $string
     * @throws Exception
     * @return string
     */
    public function validate($string) {
        if (empty($string)) {
            throw new Exception("Connection string is empty");
        }
        $data = @parse_url($string);
        if (false === $data) {
            throw new Exception("Connection string invalid: '{$string}'");
        }
        if ($data['scheme'] != 'ftp') {
            throw new Exception("Support for scheme '{$data['scheme']}' unsupported");
        }
        return $data;
    }

    /**
     * Connect to server using connect string
     * Connection string: ftp://user:pass@server:port/path
     * user,pass,port,path are optional parts
     *
     * @param string $string
     * @param int $timeout
     * @return null
     */
    public function connect($string, $timeout = 90) {
        $params = $this->validate($string);
        $port = isset($params['port']) ? intval($params['port']) : 21;

        $this->connection = @ftp_connect($params['host'], $port, $timeout);

        if (!$this->connection) {
            throw new Exception("Cannot connect to host: {$params['host']}");
        }
        ftp_pasv($this->connection, true);
        if (isset($params['user']) && isset($params['pass'])) {
            $this->login($params['user'], $params['pass']);
        } else {
            $this->login();
        }
        if (isset($params['path'])) {
            if (!$this->chdir($params['path'])) {
                throw new Exception("Cannot chdir after login to: {$params['path']}");
            }
        }
        $this->connected = true;
    }

    public function isConnected() {
        return $this->connected;
    }

    /**
     * ftp_fput wrapper
     *
     * @param string $remoteFile
     * @param resource $handle
     * @param int $mode  FTP_BINARY | FTP_ASCII
     * @param int $startPos
     * @return boolean
     */
    public function fput($remoteFile, $handle, $mode = FTP_BINARY, $startPos = 0) {
        $this->checkConnectionEstablished();
        $result = @ftp_fput($this->connection, $remoteFile, $handle, $mode, $startPos);
        @ftp_chmod($this->connection, 0644, $remoteFile);
        return $result;
    }

    public function getConnection() {
        return $this->connection;
    }

    /**
     * ftp_put wrapper
     *
     * @param string $remoteFile
     * @param string $localFile
     * @param int $mode FTP_BINARY | FTP_ASCII
     * @param int $startPos
     * @return boolean
     */
    public function put($remoteFile, $localFile, $mode = FTP_BINARY, $startPos = 0) {
        $this->checkConnectionEstablished();
        $result = @ftp_put($this->connection, $remoteFile, $localFile, $mode, $startPos);

        return $result;
    }

    private $remoteBaseDir;
    private $localBaseDir;

    public function uploadDir($remoteDir, $localDir) {
        $this->remoteBaseDir = $remoteDir;
        $this->localBaseDir = $localDir;
        $this->uploadDirRecursively($remoteDir, $localDir);
        return true;
    }

    private function abspath($remotePath) {
        if (!empty($remotePath)) {
            if (substr($remotePath, 0, 1) != '/') {
                $pwd = $this->pwd();
                if ($pwd) {
                    if ($pwd == '/') {
                        $remotePath = '/' . $remotePath;
                    } else {
                        $remotePath = $pwd . '/' . $remotePath;
                    }
                }
            }
        }
        return $remotePath;
    }

    public function copyDir($remoteSourceDir, $remoteDestDir) {
        $remoteSourceDir = $this->abspath($remoteSourceDir);
        $remoteDestDir = $this->abspath($remoteDestDir);
        if (!chdir($remoteSourceDir)) {
            throw new Exception('Source path does not exists .');
        }
        if (!chdir($remoteDestDir)) {
            $this->mkdirs($remoteDestDir);
        }
    }

    private function uploadDirRecursively($remoteDir, $localDir) {
        KCUpgradeLog::appendLog('Upgrading', "localDir: $localDir");
        if (!is_dir($localDir)) {
            KCUpgradeLog::appendLog('Upgrading', "$localDir is not a directory");
//            throw new Exception("$localDir is not a directory .");
        }
        $fileAndDirs = scandir($localDir);

        foreach ($fileAndDirs as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (is_dir($localDir . '/' . $file)) {
                $this->uploadDirRecursively($remoteDir, $localDir . '/' . $file);
            } else {
                $idx = strpos($localDir . '/' . $file, $this->localBaseDir);
                $this->mkdirs($this->remoteBaseDir . substr($localDir . '/' . $file, $idx + strlen($this->localBaseDir)));
                $remoteFilePath = $this->remoteBaseDir . substr($localDir . '/' . $file, $idx + strlen($this->localBaseDir));
                if (false === $this->put($remoteFilePath, $localDir . '/' . $file)) {
                    KCUpgradeLog::appendLog('Upgrading', "上传文件: $localDir/$file => $remoteFilePath 失败");
                    KCUpgradeLog::setStepResult('Upgrading', false);
                    throw new Exception('Cannot upload file ' . $localDir . '/' . $file . ',please check permissions .');
                } else {
                    KCUpgradeLog::appendLog('Upgrading', "上传文件: $localDir/$file => $remoteFilePath 成功");
                }
            }
        }
        KCUpgradeLog::setStepResult('Upgrading', "上传文件夹: $localDir 成功");
    }

    /**
     * Upload local file to remote server.
     * Can be used for relative and absoulte remote paths
     * Relative: use chdir before calling this
     *
     * @param string $remote
     * @param string $local
     * @param int $dirMode
     * @param int $fileMode
     * @return boolean
     */
    public function upload($remote, $local, $dirMode = 0777, $fileMode = 0) {
        $this->checkConnectionEstablished();

        if (!file_exists($local)) {
            throw new Exception("Local file doesn't exist: {$local}");
        }
        if (!is_readable($local)) {
            throw new Exception("Local file is not readable: {$local}");
        }
        if (is_dir($local)) {
            throw new Exception("Directory given instead of file: {$local}");
        }

        $globalPathMode = substr($remote, 0, 1) == "/";
        $dirname = dirname($remote);
        $cwd = $this->getcwd();
        if (false === $cwd) {
            throw new Exception("Server returns something awful on PWD command");
        }

        if (!$globalPathMode) {
            $dirname = $cwd . "/" . $dirname;
            $remote = $cwd . "/" . $remote;
        }
        $res = $this->mkdirRecursive($dirname, $dirMode);
        $this->chdir($cwd);

        if (!$res) {
            return false;
        }
        $res = $this->put($remote, $local);

        if (!$res) {
            return false;
        }

        if ($fileMode) {
            $res = $this->chmod($fileMode, $remote);
        }
        return (boolean) $res;
    }

    /**
     * ftp_pasv wrapper
     *
     * @param boolean $pasv
     * @return boolean
     */
    public function pasv($pasv) {
        $this->checkConnectionEstablished();
        return @ftp_pasv($this->connection, (boolean) $pasv);
    }

    /**
     * Close FTP connection
     *
     * @return null
     */
    public function close() {
        if ($this->connection) {
            @ftp_close($this->connection);
        }
    }

    /**
     * ftp_chmod wrapper
     *
     * @param $mode
     * @param $remoteFile
     * @return boolean
     */
    public function chmod($mode, $remoteFile) {
        $this->checkConnectionEstablished();
        return @ftp_chmod($this->connection, $mode, $remoteFile);
    }

    /**
     * ftp_chdir wrapper
     *
     * @param string $dir
     * @return boolean
     */
    public function chdir($dir) {
        $this->checkConnectionEstablished();
        $dir = $this->abspath($dir);
        return @ftp_chdir($this->connection, $dir);
    }

    public function rmdir($dir) {
        $this->checkConnectionEstablished();
        $dir = $this->abspath($dir);
        return @ftp_rmdir($this->connection, $dir);
    }

    /**
     * ftp_cdup wrapper
     *
     * @return boolean
     */
    public function cdup() {
        $this->checkConnectionEstablished();
        return @ftp_cdup($this->connection);
    }

    /**
     * ftp_get wrapper
     *
     * @param string $localFile
     * @param string $remoteFile
     * @param int $fileMode         FTP_BINARY | FTP_ASCII
     * @param int $resumeOffset
     * @return boolean
     */
    public function get($localFile, $remoteFile, $fileMode = FTP_BINARY, $resumeOffset = 0) {
        $remoteFile = $this->abspath($remoteFile);
        $this->checkConnectionEstablished();
        return @ftp_get($this->connection, $localFile, $remoteFile, $fileMode, $resumeOffset);
    }

    /**
     * ftp_nlist wrapper
     *
     * @param string $dir
     * @return boolean
     */
    public function nlist($dir = "/") {
        $this->checkConnectionEstablished();
        $dir = $this->abspath($dir);
        return @ftp_nlist($this->connection, $dir);
    }

    public function size($file) {
        $this->checkConnectionEstablished();
        $file = $this->abspath($file);
        return @ftp_size($this->connection, $file);
    }

}

class FileInfo {
    /**
     * Constant can be used in getInfo() function as second parameter.
     * Check whether directory and all files/sub directories are writable
     *
     * @const int
     */

    const INFO_WRITABLE = 1;

    /**
     * Constant can be used in getInfo() function as second parameter.
     * Check whether directory and all files/sub directories are readable
     *
     * @const int
     */
    const INFO_READABLE = 2;

    /**
     * Constant can be used in getInfo() function as second parameter.
     * Get directory size
     *
     * @const int
     */
    const INFO_SIZE = 4;

    /**
     * Constant can be used in getInfo() function as second parameter.
     * Combination of INFO_WRITABLE, INFO_READABLE, INFO_SIZE
     *
     * @const int
     */
    const INFO_ALL = 7;

    /**
     * Get information (readable, writable, size) about $path
     *
     * @param string $path
     * @param int $infoOptions
     * @param array $skipFiles
     */
    public static function getInfo($path, $infoOptions = self::INFO_ALL) {
        $info = array();
        if ($infoOptions & self::INFO_READABLE) {
            $info['readable'] = true;
        }

        if ($infoOptions & self::INFO_WRITABLE) {
            $info['writable'] = true;
        }

        if ($infoOptions & self::INFO_SIZE) {
            $info['size'] = 0;
        }

        $filesystemIterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($filesystemIterator as $item) {
            if (($infoOptions & self::INFO_WRITABLE) && !$item->isWritable()) {
                $info['writable'] = false;
            }

            if (($infoOptions & self::INFO_READABLE) && !$item->isReadable()) {
                $info['readable'] = false;
            }

            if ($infoOptions & self::INFO_SIZE && !$item->isDir()) {
                $info['size'] += $item->getSize();
            }
        }
        return $info;
    }

}

class PluginBackup {

    private $sourceDir;
    private $destDir;
    private $skipPaths = array();

    public function setSkipPaths($skipPaths) {
        $this->skipPaths = $skipPaths;
    }

    public function __construct($sourceDir, $destDir) {
        $this->sourceDir = $sourceDir;
        $this->destDir = $destDir;
    }

    public function backup() {
        KCUpgradeLog::appendLog('Backuping', '开始备份前检查');
        // do check before backup
        if ($this->doCheckBeforeBackup() == false) {
            KCUpgradeLog::appendLog('Backuping', "错误：备份前检查失败");
            return false;
        }
        KCUpgradeLog::appendLog('Backuping', "备份前检查通过");

        KCUpgradeLog::appendLog('Backuping', '开始备份');
        if ($this->moveDir($this->sourceDir, $this->destDir) == false) {
            return false;
        }
        return true;
    }

    private function moveDir($sourceDir, $destDir) {
        if (!is_dir($sourceDir)) {
            return false;
        }

        if (!file_exists($destDir)) {
            KCUpgradeLog::appendLog('Backuping', "警告: 目录 $destDir 不存在，尝试创建");
            if (!@mkdir($destDir, 0755, true)) {
                KCUpgradeLog::appendLog('Backuping', "错误: 目录创建失败");
                return false;
            }
            KCUpgradeLog::appendLog('Backuping', "目录创建成功");
        }

        $sourceFilesAndDirs = scandir($sourceDir);
        foreach ($sourceFilesAndDirs as $file) {
            if (!in_array($sourceDir . '/' . $file, $this->skipPaths)) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                if (is_dir($sourceDir . '/' . $file)) {
                    $result = $this->moveDir($sourceDir . '/' . $file, $destDir . '/' . $file);
                    if ($result == false) {
                        return false;
                    }
                } else {
                    if (copy($sourceDir . '/' . $file, $destDir . '/' . $file) == false) {
                        KCUpgradeLog::appendLog('Backuping', "错误: 文件复制失败 $sourceDir/$file => $destDir / $file");
                        return false;
                    } else {
                        KCUpgradeLog::appendLog('Backuping', "文件复制成功 $sourceDir/$file => $destDir / $file");
                    }
                }
            }
        }
        return true;
    }

    private function doCheckBeforeBackup() {
        if (empty($this->sourceDir)) {
            KCUpgradeLog::appendLog('Backuping', "错误: 插件路径为空");
            // Source path is not specified
            return false;
        }
        if (!file_exists($this->sourceDir)) {
            KCUpgradeLog::appendLog('Backuping', "错误: 插件路径 $this->sourceDir 不存在");
            // Source directory($this->sourceDir) does not exists
            return false;
        }
        $info = FileInfo::getInfo($this->sourceDir);
        if (!$info['readable']) {
            KCUpgradeLog::appendLog('Extracting', "错误: 插件路径 $this->sourceDir 不可读");
            // Source path is not readable: $this->sourceDir
            return false;
        }
        KCUpgradeLog::appendLog('Backuping', "插件路径 $this->sourceDir 检查通过");

        if (empty($this->destDir)) {
            KCUpgradeLog::appendLog('Backuping', "错误: 备份目标路径为空");
            // Destination path is not specified
            return false;
        }
        if (file_exists($this->destDir)) {
            if (!is_writable($this->destDir)) {
                KCUpgradeLog::appendLog('Backuping', "错误: 备份目标路径 $this->destDir 不可写");
                // Destination path is not writable
                return false;
            }
        } else {
            KCUpgradeLog::appendLog('Backuping', "警告: 备份目标路径 $this->sourceDir 不存在，尝试创建");
            if (!@mkdir($this->destDir, 0755, true)) {
                KCUpgradeLog::appendLog('Backuping', "错误: 备份目标路径创建失败");
                // Can not create backup directory
                return false;
            }
            KCUpgradeLog::appendLog('Backuping', "备份目标路径创建成功");
        }
        KCUpgradeLog::appendLog('Backuping', "备份目标路径 $this->destDir 检查通过");

        $freeSpaceSize = disk_free_space($this->destDir);
        if ($freeSpaceSize < $info[size]) {
            KCUpgradeLog::appendLog('Backuping', "错误: 磁盘空间不足");
            // Not enough space to create backup
            return false;
        }
        return true;
    }

}

/**
 * download the plugin .
 */
class PluginDownloader {

    /**
     * the plugin's url
     * @var string
     */
    private $url;
    private $downloadPath;

    public function __construct($url, $downloadPath) {
        $this->url = $url;
        $this->downloadPath = $downloadPath;
    }

    public function download() {
        $data = $this->getData($this->url);
        if ($data !== false) {
            $fp = @fopen($this->downloadPath, 'w+');
            if (false === $fp) {
                KCUpgradeLog::appendLog('Downloading', "错误: $this->downloadPath 不可写");
                // "Can not open file ($this->downloadPath) for writing ."
                return false;
            }
            $fileSize = @fwrite($fp, $data);
            @fclose($fp);
            if ($fileSize == 0) {
                KCUpgradeLog::appendLog('Downloading', "错误: 插件大小 0 byte(s)");
                // Zero bytes are downloaded.
                return false;
            }
            KCUpgradeLog::appendLog('Downloading', "插件大小: $fileSize byte(s)");
            return true;
        } else {
            KCUpgradeLog::appendLog('Downloading', "错误: 下载插件失败");
            // Fail to download the plugin.
            return false;
        }
    }

    /* gets the data from a URL */

    function getData($url) {
        $ch = curl_init();
        $timeout = 50;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

}

/**
 * Extract zip contents to the  specified directory.
 */
class ZipExtracter {

    /**
     * the source zip file path
     * @var string
     */
    private $sourceZip;

    /**
     * the path to which the source zip file will be extracted to
     * @var type
     */
    private $destDir;
    private $extractBase = '/';
    private $zipExtracterHandler = null;

    public function __construct($sourceZip, $destDir, $extractBase = '/') {
        $this->sourceZip = $sourceZip;
        $this->destDir = $destDir;
        if (is_string($extractBase) && $extractBase != '') {
            if (substr($extractBase, 0, 1) != '/') {
                $extractBase = '/' . $extractBase;
            }
            $this->extractBase = $extractBase;
        }
    }

    public function getExtractBase() {
        return $this->extractBase;
    }

    public function setZipExtracterHandler($handler) {
        $this->zipExtracterHandler = $handler;
    }

    public function getSourceZip() {
        return $this->sourceZip;
    }

    public function getDestDir() {
        return $this->destDir;
    }

    /**
     * check conditions before extracting .
     * If the check action failed,exception will be threw .
     * @throws Exception
     */
    private function doCheckBeforeExtract() {
        if (!file_exists($this->sourceZip)) {
            KCUpgradeLog::appendLog('Extracting', "错误: 插件文件 $this->sourceZip 不存在");
            // Sourc zip file does not exists .
            return false;
        }
        KCUpgradeLog::appendLog('Extracting', "插件文件 $this->sourceZip 存在");
        $pluginSize = filesize($this->sourceZip);
        KCUpgradeLog::appendLog('Extracting', "插件文件大小：$pluginSize bytes");
        if (!is_readable($this->sourceZip)) {
            KCUpgradeLog::appendLog('Extracting', "错误: 插件文件 $this->sourceZip 不可读");
            // "Source zip file ($this->sourceZip) is not readable.
            return false;
        }
        KCUpgradeLog::appendLog('Extracting', "插件文件 $this->sourceZip 可读");

        if (empty($this->destDir)) {
            KCUpgradeLog::appendLog('Extracting', "错误: 解压目标路径为空");
            // Destination path is not specified.
            return false;
        }
        if (file_exists($this->destDir)) {
            KCUpgradeLog::appendLog('Extracting', "解压目标路径 $this->destDir 存在");
            if (!is_writable($this->destDir)) {
                KCUpgradeLog::appendLog('Extracting', "错误: 解压目标路径 $this->destDir 不可写");
                // Destination path is not writable
                return false;
            }
        } else {
            KCUpgradeLog::appendLog('Extracting', "警告: 解压目标路径 $this->destDir 不存在，尝试创建该目录");
            if (!@mkdir($this->destDir, 0755, true)) {
                KCUpgradeLog::appendLog('Extracting', "错误: 解压目标路径 $this->destDir 创建失败");
                // Can not create destination directory.
                return false;
            }
        }
        KCUpgradeLog::appendLog('Extracting', "解压目标路径 $this->destDir 可写");
        return true;
    }

    public function extract() {
        KCUpgradeLog::appendLog('Extracting', '开始解压前检查');
        // do check before extracting
        if ($this->doCheckBeforeExtract() == false) {
            KCUpgradeLog::appendLog('Extracting', "错误：解压前检查失败");
            return false;
        }
        KCUpgradeLog::appendLog('Extracting', "解压前检查通过");

        KCUpgradeLog::appendLog('Extracting', '开始解压');
        if ($this->doExtract() == false) {
            return false;
        }
        return true;
    }

    /**
     * do the actual extracting action
     * @throws Exception
     */
    private function doExtract() {
        $zip = zip_open($this->sourceZip);
        if ($zip) {
            KCUpgradeLog::appendLog('Extracting', "打开 $this->sourceZip 成功，开始解压文件");
            while ($zip_entry = zip_read($zip)) {
                $entryName = zip_entry_name($zip_entry);
                $entrySize = zip_entry_filesize($zip_entry);
                KCUpgradeLog::appendLog('Extracting', "读取条目成功： $entryName ($entrySize bytes)");
                if (zip_entry_open($zip, $zip_entry, "r")) {
                    KCUpgradeLog::appendLog('Extracting', "$entryName 打开成功： $entryName ($entrySize bytes)");
                    // $entryName open successfully
                    if (!empty($entrySize)) {
                        $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                        if (!is_null($this->zipExtracterHandler)) {
                            $this->zipExtracterHandler->onExtracting($this, '/' . $entryName, $buf);
                        }
                    }
                    zip_entry_close($zip_entry);
                } else {
                    KCUpgradeLog::appendLog('Extracting', "错误：$entryName 打开失败");
                    return false;
                }
            }
            zip_close($zip);
            return true;
        } else {
            KCUpgradeLog::appendLog('Extracting', "错误：打开 $this->sourceZip 失败");
            // open plugin zip file failed
            return false;
        }
    }

}

interface ZipExtracterHandler {

    public function onExtracting($zipExtracter, $entryName, $entryData);
}

class PluginZipExtracterHandler implements ZipExtracterHandler {

    private $skipPaths = array();

    public function setSkipPaths(array $skipsPaths) {
        $this->skipPaths = $skipsPaths;
    }

    private function mkdirs($dir) {
        if (empty($dir)) {
            return false;
        }
        if (!file_exists($dir)) {
            $this->mkdirs(dirname($dir));
            @mkdir($dir);
        }
    }

    public function onExtracting($zipExtracter, $entryName, $entryData) {
        KCUpgradeLog::appendLog('Extracting', "开始保存文件: ");
        if (!in_array($entryName, $this->skipPaths)) {
            $extractBase = $zipExtracter->getExtractBase();
            if ($extractBase !== '') {
                if (substr($extractBase, strlen($extractBase) - 1) !== '/') {
                    $extractBase = $extractBase . '/';
                }
                $idx = strpos($entryName, $extractBase);
                if ($idx !== false) {
                    $entryName = substr($entryName, $idx + strlen($extractBase));
                    $fileName = $zipExtracter->getDestDir() . '/' . $entryName;
                    $dirName = dirname($fileName);

                    if (!file_exists($dirName)) {
                        KCUpgradeLog::appendLog('Extracting', "目录不存在，创建目录: $dirName");
                        $this->mkdirs($dirName);
                    }
                    $fp = @fopen($fileName, 'w+');
                    if ($fp === false) {
                        KCUpgradeLog::appendLog('Extracting', "无法保存至: $fileName");
                        // Can not extract to $fileName
                        return false;
                    }
                    @fwrite($fp, $entryData);
                    @fclose($fp);
                    KCUpgradeLog::appendLog('Extracting', "$fileName 保存成功");
                    return true;
                } else {
                    KCUpgradeLog::appendLog('Extracting', "错误：文件路径错误 entryName $entryName，extractBase $extractBase");
                    return false;
                }
            }
        } else {
            KCUpgradeLog::appendLog('Extracting', "在忽略列表中，忽略该文件");
            return true;
        }
    }

}

class PluginFileSystemUpgrader {

    private $pluginPath = '';
    private $downloadUrl = '';
    private $downloadFilePath = '';
    private $pluginVersion = '';

    public function __construct($pluginPath, $downloadUrl, $downloadFilePath, $pluginVersion) {
        $this->pluginPath = $pluginPath;
        $this->downloadUrl = $downloadUrl;
        $this->downloadFilePath = $downloadFilePath;
        $this->pluginVersion = $pluginVersion;
    }

    private function getPluginBackupDirectory() {
        return $this->pluginPath . '/backup/plugin_' . time();
    }

    private function getNewTmpPluginDirectory() {
        return $this->pluginPath . '/kancartplugin_new';
    }

    public function upgrade($zipBase = '') {
        KCUpgradeLog::appendLog('Start', 'befor upgrading');
        if (empty($this->pluginPath)) {
            KCUpgradeLog::appendLog('Start', '错误: 插件路径为空');
            // The plugin path is empty.
            return false;
        }

        if (!file_exists($this->pluginPath) || !is_dir($this->pluginPath)) {
            KCUpgradeLog::appendLog('Start', '错误: 插件路径不存在或者不是文件夹');
            // The plugin path does not exist or it is not a directory.
            return false;
        }

        // 下载插件
        KCUpgradeLog::appendLog('Downloading', '准备下载');
        KCUpgradeLog::appendLog('Downloading', '插件保存路径: ' . $this->downloadFilePath);
        KCUpgradeLog::appendLog('Downloading', '插件下载地址: ' . $this->downloadUrl);
        $pluginDownloader = new PluginDownloader($this->downloadUrl, $this->downloadFilePath);

        if ($pluginDownloader->download() == false) {
            KCUpgradeLog::appendLog('Downloading', '下载失败');
            KCUpgradeLog::setStepResult('Downloading', false);
            return false;
        }
        // download successfully
        KCUpgradeLog::appendLog('Downloading', '下载成功');
        KCUpgradeLog::setStepResult('Downloading', true);


        // 解压插件
        KCUpgradeLog::appendLog('Extracting', '准备解压');
        $newTmpPluginDirectory = $this->getNewTmpPluginDirectory();
        KCUpgradeLog::appendLog('Extracting', '插件目录: ' . $this->pluginPath);
        KCUpgradeLog::appendLog('Extracting', '新插件临时目录: ' . $newTmpPluginDirectory);
        KCUpgradeLog::appendLog('Extracting', 'extract base: ' . $zipBase);
        $extracter = new ZipExtracter($this->downloadFilePath, $newTmpPluginDirectory, $zipBase);
        $handler = new PluginZipExtracterHandler();
        $handler->setSkipPaths(array());
        $extracter->setZipExtracterHandler($handler);

        if ($extracter->extract() == false) {
            KCUpgradeLog::appendLog('Extracting', '解压失败');
            KCUpgradeLog::setStepResult('Extracting', false);
            return false;
        }
        // extract sucessfully
        KCUpgradeLog::appendLog('Extracting', '解压成功');
        KCUpgradeLog::setStepResult('Extracting', true);

        // 备份插件
        KCUpgradeLog::appendLog('Backuping', '准备备份');
        $pluginBackupDirectory = $this->getPluginBackupDirectory();
        KCUpgradeLog::appendLog('Backuping', "备份文件目录：$pluginBackupDirectory");
        if (!file_exists($pluginBackupDirectory)) {
            KCUpgradeLog::appendLog('Backuping', "警告：备份文件目录不存在，尝试创建");
            if (@mkdir($pluginBackupDirectory, 0755, true) == false) {
                KCUpgradeLog::appendLog('Backuping', "错误：备份文件目录创建失败");
                return false;
            }
            KCUpgradeLog::appendLog('Backuping', "备份文件目录创建成功");
        }
        $backup = new PluginBackup($this->pluginPath, $pluginBackupDirectory);
        $backup->setSkipPaths(array($this->downloadFilePath, $newTmpPluginDirectory, $this->pluginPath . '/backup'));
        KCUpgradeLog::appendLog('Backuping', "忽略文件或目录列表：$this->downloadFilePath, $newTmpPluginDirectory, $this->pluginPath" . '/backup');
        if ($backup->backup() == false) {
            KCUpgradeLog::appendLog('Backuping', '备份失败');
            KCUpgradeLog::setStepResult('Backuping', false);
            return false;
        }
        // backup successfully
        KCUpgradeLog::appendLog('Backuping', '备份成功');
        KCUpgradeLog::setStepResult('Backuping', true);


        // 替换旧插件文件
        KCUpgradeLog::appendLog('Upgrading', '准备升级');
        KCUpgradeLog::appendLog('Upgrading', "新插件临时目录：$newTmpPluginDirectory");
        KCUpgradeLog::appendLog('Upgrading', "插件目录：$this->pluginPath");
        $clearResult = $this->clearOldFiles($this->pluginPath, array($newTmpPluginDirectory, $this->pluginPath . '/backup'));
        if ($clearResult == false) {
            KCUpgradeLog::appendLog('Upgrading', '错误：清除插件目录失败');
            KCUpgradeLog::setStepResult('Upgrading', false);
            return false;
        }
        KCUpgradeLog::appendLog('Upgrading', '清除插件目录成功');
        $copyResult = $this->moveFileToPluginPath($newTmpPluginDirectory, $this->pluginPath);
        if ($copyResult == false) {
            KCUpgradeLog::appendLog('Upgrading', '错误：将新插件从临时目录移动至插件目录失败');
            KCUpgradeLog::setStepResult('Upgrading', false);
            return false;
        }
        KCUpgradeLog::appendLog('Upgrading', '升级成功');
        KCUpgradeLog::setStepResult('Upgrading', true);

        // 清理 临时的新插件文件夹
        KCUpgradeLog::appendLog('Cleaning', "清理临时目录：$newTmpPluginDirectory");
        $this->removeDirAndFiles($newTmpPluginDirectory);
        KCUpgradeLog::appendLog('Cleaning', '清理成功');
        // 结束
        return true;
    }

//    private function removeUnnecessaryFile($compareDir1, $compareDir2, $destDir) {
//        if (($handle = opendir("$dirName"))) {
//            while (false !== ($item = readdir($handle))) {
//                if ($item != "." && $item != "..") {
//                    if (is_dir("$dirName/$item")) {
//                        $this->removeDirAndFiles("$dirName/$item");
//                    } else {
//                        echo 'delete: ' . $dirName . '/' . $item . '<br />';
//                        echo (unlink("$dirName/$item") ? 'true': 'false') . '<br />';
//                    }
//                }
//            }
//            closedir($handle);
//            echo 'delete DIR: ' . $dirName . '<br />';
//            echo (rmdir($dirName) ? 'true': 'false') . '<br />';
//        }
//    }

    function clearOldFiles($sourceDir, $skipPaths = array()) {
        if (!is_dir($sourceDir)) {
            KCUpgradeLog::appendLog('Upgrading', "错误：目标文件夹：$sourceDir 不是目录");
            return false;
        }
        KCUpgradeLog::appendLog('Upgrading', "开始清除：$sourceDir 下的文件");
        $sourceFilesAndDirs = scandir($sourceDir);

        foreach ($sourceFilesAndDirs as $file) {
            if (!in_array($sourceDir . '/' . $file, $skipPaths)) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                if (is_dir($sourceDir . '/' . $file)) {
                    $result = $this->removeDirAndFiles($sourceDir . '/' . $file);
                    if ($result == false) {
                        return false;
                    }
                } else {
                    if (unlink($sourceDir . '/' . $file) == false) {
                        KCUpgradeLog::appendLog('Upgrading', "错误：删除文件失败 $sourceDir/$file");
                        return false;
                    } else {
                        KCUpgradeLog::appendLog('Upgrading', "删除文件成功 $sourceDir/$file");
                    }
                }
            }
        }
        return true;
    }

    function moveFileToPluginPath($sourceDir, $destDir) {
        if (!is_dir($sourceDir)) {
            KCUpgradeLog::appendLog('Upgrading', "错误：源文件夹：$sourceDir 不是目录");
            return false;
        }
        if (!file_exists($destDir)) {
            KCUpgradeLog::appendLog('Upgrading', "错误：目标文件夹：$destDir 不存在");
            return false;
        }
        $sourceFilesAndDirs = scandir($sourceDir);
        foreach ($sourceFilesAndDirs as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (is_dir($sourceDir . '/' . $file)) {
//                    $this->removeDirAndFiles($destDir . '/' . $file);
                mkdir($destDir . '/' . $file, 0755);
                $result = $this->moveFileToPluginPath($sourceDir . '/' . $file, $destDir . '/' . $file);
                if ($result == false) {
                    return false;
                }
            } else {
//                    unlink($destDir . '/' . $file);
                if (file_exists($sourceDir . '/' . $file)) {
                    copy($sourceDir . '/' . $file, $destDir . '/' . $file);
                    KCUpgradeLog::appendLog('Upgrading', "复制文件: $sourceDir/$file => $destDir/$file");
                }
            }
        }
        return true;
    }

    function removeDirAndFiles($dirName) {
        if (($handle = opendir("$dirName"))) {
            while (false !== ($item = readdir($handle))) {
                if ($item != "." && $item != "..") {
                    if (is_dir("$dirName/$item")) {
                        $this->removeDirAndFiles("$dirName/$item");
                        rmdir("$dirName/$item");
                    } else {
                        if (unlink("$dirName/$item")) {
                            KCUpgradeLog::appendLog('Upgrading', "删除文件: $dirName/$item 成功");
                        } else {
                            KCUpgradeLog::appendLog('Upgrading', "警告：删除文件: $dirName/$item 失败");
                        }
                    }
                }
            }
            closedir($handle);
            if (rmdir("$dirName/$item")) {
                KCUpgradeLog::appendLog('Upgrading', "删除目录: $dirName 成功");
            } else {
                KCUpgradeLog::appendLog('Upgrading', "警告：删除目录: $dirName 失败");
            }
        }
        return true;
    }

}

class PluginFtpUpgrader {

    /**
     *
     * @var FtpClient
     */
    private $ftpClient;

    public function __construct($ftpClient) {
        if (is_null($ftpClient)) {
            throw new Exception('The ftp client must not be null .');
        }
        $this->ftpClient = $ftpClient;
    }

    public function checkBeforeUpgrade($remotePluginPath) {
        // check write permission
    }

    private function ftpBackup($remotePath, $dest, $remoteBase) {
        $contents = $this->ftpClient->nlist($remotePath);
        if ($contents === false) {
            KCUpgradeLog::appendLog('Backuping', '错误：获取插件目录文件列表发生异常');
            return false;
        }
        foreach ($contents as $file) {
            if ($file !== $dest) {
                if ($this->ftpClient->size($file) === -1) {
                    $this->ftpBackup($file, $dest, $remoteBase);
                    if (false === $this->ftpClient->rmdir($file)) {
                        KCUpgradeLog::appendLog('Backuping', "警告：删除FTP目录：$file 失败");
                    } else {
                        KCUpgradeLog::appendLog('Backuping', "删除FTP目录：$file 成功");
                    }
                } else {
                    $idx = strpos($file, $remoteBase);
                    $fileRelativeName = substr($file, $idx + strlen($remoteBase));
                    KCUpgradeLog::appendLog('Backuping', "备份插件文件：$file => $dest$fileRelativeName");
                    $mkdirsResult = $this->ftpClient->mkdirs($dest . $fileRelativeName);
                    if ($mkdirsResult == false) {
                        KCUpgradeLog::appendLog('Backuping', 'FTP创建目录失败');
                        KCUpgradeLog::setStepResult('Backuping', false);
                        return false;
                    }
                    $tmpFile = tempnam(md5(uniqid(rand())), '');
                    if ($tmpFile === false) {
                        throw new Exception('Canot create temp file for writing .');
                    }
                    if (false === $this->ftpClient->get($tmpFile, $file)) {
                        throw new Exception('Can not download file for back up,please check your ftp permissions .');
                    }
                    if (false === $this->ftpClient->put($dest . $fileRelativeName, $tmpFile)) {
                        KCUpgradeLog::appendLog('Backuping', "上传备份文件发生错误，清理临时文件：$tmpFile");
                        @unlink($tmpFile);
                        throw new Exception('Can not upload file for back up,please check your ftp permissions .');
                    }
                    KCUpgradeLog::appendLog('Backuping', "清理临时文件：$tmpFile");
                    @unlink($tmpFile);
                    if (false === $this->ftpClient->deleteFile($file)) {
                        KCUpgradeLog::appendLog('Backuping', "失败：删除文件：$file 失败");
                        // warning : could not delete old plugin file
                        throw new Exception('Cannot delete old plugin file,please check ftp permission .');
                    } else {
                        KCUpgradeLog::appendLog('Backuping', "删除文件：$file 成功");
                    }
                }
            }
        }
        return true;
    }

    private function deleteDir($dir) {
        if (!file_exists($dir) || !is_writable($dir)) {
            return false;
        }
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (is_dir($dir . '/' . $file)) {
                $this->deleteDir($dir . '/' . $file);
                @rmdir($dir . '/' . $file);
            } else {
                @unlink($dir . '/' . $file);
            }
        }
        @rmdir($dir);
    }

    private function rollback($fromRemoteDir, $toRemoteDir, $fromRemoteBase) {
        $contents = $this->ftpClient->nlist($fromRemoteDir);
        if ($contents === false) {
            throw new Exceptoin('Can not list files in the ' . $fromRemoteDir . ' when rollback on backup fail.');
        }
        foreach ($contents as $file) {
            if ($file != $toRemoteDir) {
                if ($this->ftpClient->size($file) == -1) {
                    $this->rollback($file, $toRemoteDir, $fromRemoteBase);
                } else {
                    $idx = strpos($file, $fromRemoteBase);
                    $fileName = substr($file, $idx + strlen($fromRemoteBase));
                    $tmpFile = tempnam(md5(uniqid(rand())), '');
                    if ($tmpFile === false) {
                        throw new Exception('Can not create temp file for writing');
                    }
                    if (false === $this->ftpClient->get($tmpFile, $file)) {
                        throw new Exception('Can not download file (' . $file . ')');
                    }
                    $this->ftpClient->mkdirs($toRemoteDir . $fileName);
                    if (false === $this->ftpClient->put($toRemoteDir . $fileName, $tmpFile)) {
                        @unlink($tmpFile);
                        throw new Exception('Can not upload file to (' . $toRemoteDir . $fileName . '),please check permission.');
                    }
                    @unlink($tmpFile);
                }
            }
        }
    }

    public function rollbackWhenBackupFails($fromRemoteDir, $toRemoteDir) {
        KCUpgradeLog::appendLog("Rollback","from $fromRemoteDir");
        KCUpgradeLog::appendLog("Rollback","to $toRemoteDir");
        $this->rollback($fromRemoteDir, $toRemoteDir, $fromRemoteDir);
        KCUpgradeLog::setStepResult('Rollback',true);
    }

    public function upgrade($remotePluginPath, $downloadUrl, $version, $zipBase = '/') {
        $downloaded = false;
        $extracted = false;
        $tmpDownloadFile = false;
        $sysTempExtractDir = false;
        $uploading = false;
        $backupPath = '';
        try {
            // step 1: download the plugin file (zip) .
            KCUpgradeLog::appendLog('Downloading', '准备下载');
            $tmpDownloadFile = tempnam(uniqid(rand()), '');
            KCUpgradeLog::appendLog('Downloading', '插件保存路径: ' . $tmpDownloadFile);

            $pluginDownloader = new PluginDownloader($downloadUrl, $tmpDownloadFile);
            $downloadResult = $pluginDownloader->download();
            if ($downloadResult == false) {
                KCUpgradeLog::appendLog('Downloading', '下载失败');
                KCUpgradeLog::setStepResult('Downloading', false);
                return false;
            }
            // download successfully
            KCUpgradeLog::appendLog('Downloading', '下载成功');
            KCUpgradeLog::setStepResult('Downloading', true);

            // 解压插件
            KCUpgradeLog::appendLog('Extracting', '准备解压');
            $sysTempExtractDir = sys_get_temp_dir() . '/kancartplugin_' . time();
            KCUpgradeLog::appendLog('Extracting', '插件临时目录: ' . $sysTempExtractDir);
            if (!file_exists($sysTempExtractDir)) {
                if (!@mkdir($sysTempExtractDir)) {
                    KCUpgradeLog::appendLog('Extracting', '错误：无法创建插件临时目录');
                    // Cannot create temp file when upgrading
                    return false;
                }
                KCUpgradeLog::appendLog('Extracting', '插件临时目录创建成功');
            }

            KCUpgradeLog::appendLog('Extracting', 'extract base: ' . $zipBase);

            $extracter = new ZipExtracter($tmpDownloadFile, $sysTempExtractDir, $zipBase);

            $handler = new PluginZipExtracterHandler();
            $handler->setSkipPaths(array());
            $extracter->setZipExtracterHandler($handler);
            if ($extracter->extract() == false) {
                KCUpgradeLog::appendLog('Extracting', '解压失败');
                KCUpgradeLog::setStepResult('Extracting', false);
                return false;
            }
            // extract sucessfully
            KCUpgradeLog::appendLog('Extracting', '解压成功');
            KCUpgradeLog::setStepResult('Extracting', true);

            $extracted = true;

            // 备份插件
            // step 3: backup old plugin file ,and in the process of backup
            //            old plugin file will be deleted,the back up path is the child path of the plugin

            $backupPath = $remotePluginPath . '/backup/plugin_' . time();
            KCUpgradeLog::appendLog('Backuping', "备份路径：$backupPath");
            KCUpgradeLog::appendLog('Backuping', "插件路径：$remotePluginPath");

            $backupResult = $this->ftpBackup($remotePluginPath, $backupPath, $remotePluginPath);
            if ($backupResult == false) {
                KCUpgradeLog::appendLog('Backuping', "备份失败");
                KCUpgradeLog::setStepResult('Backuping', false);
                return false;
            }
            KCUpgradeLog::appendLog('Backuping', "备份成功");
            KCUpgradeLog::setStepResult('Backuping', true);

            // step 4: uplod the extrated plugin file to the server using ftp
            KCUpgradeLog::appendLog('Upgrading', '准备升级');
            $uploading = true;

            $uploadResult = $this->ftpClient->uploadDir($remotePluginPath, $sysTempExtractDir);
            if ($uploadResult == false) {
                KCUpgradeLog::appendLog('Upgrading', '错误：插件上传失败');
                KCUpgradeLog::setStepResult('Upgrading', false);
                return false;
            }
            KCUpgradeLog::appendLog('Upgrading', '插件上传成功');
            KCUpgradeLog::setStepResult('Upgrading', true);
            
            $this->ftpClient->quit();
            KCUpgradeLog::appendLog('Upgrading', '关闭FTP');
            
            // 清理
            // clean the temp directories and files

            KCUpgradeLog::appendLog('Cleaning', "清理临时下载文件：$tmpDownloadFile");
            @unlink($tmpDownloadFile);
            KCUpgradeLog::appendLog('Cleaning', "清理临时解压目录：$sysTempExtractDir");

            $this->deleteDir($sysTempExtractDir);
            KCUpgradeLog::appendLog('Cleaning', '清理成功');
            KCUpgradeLog::setStepResult('Cleaning', true);
            return true;
        } catch (Exception $e) {
            if ($downloaded) {
                @unlink($tmpDownloadFile);
            }
            if ($extracted) {
                $this->deleteDir($sysTempExtractDir);
            }
            if (!$uploading && $extracted) {
                try {
                    $this->rollbackWhenBackupFails($backupPath, $remotePluginPath);
                    KCUpgradeLog::appendLog('Rollback','Start rolling back ..');
                } catch (Exception $ex) {
                    KCUpgradeLog::setStepResult('Backuping', 'rollback fails due to ' . $ex);
                }
            }
            throw $e;
        }
        return true;
    }

}

?>
