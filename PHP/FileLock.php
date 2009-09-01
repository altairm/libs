<?php
/**
 * use:
 * FileLock::lock(path to lock file);
 * ....
 * code
 * ....
 * FileLock::unlock(path to lock file);
 *
 */
/**
 * @desc Portable advisory file locking
 * @author michael
 */
class FileLock {
    /**
     * lock files
     * @var array
     */
    protected static $file = array();
    /**
     * lock file by path
     * @param string $path
     */
    public static function lock($path) {
        $hash = md5($path);
        if(!empty(self::$file[$hash])) {
            throw new Exception('File is already locked by this process. Use another path');
        }
        if(!file_exists(dirname($path)) || !is_writable(dirname($path))) {
            throw new Exception('Couldn\'t get the lock, wrong path to lock file');
        }
        self::$file[$hash] = fopen($path, 'w');
        if(!self::$file[$hash]) {
            throw new Exception('Couldn\'t get the lock, open file failed');
        }
        if(!flock(self::$file[$hash], LOCK_EX | LOCK_NB)){
            fclose(self::$file[$hash]);
            self::$file[$hash] = null;
            throw new Exception('File is locked by another process');
        }
    }
    /**
     * unlock file, locked by FileLock::lock function
     * @param string $path
     */
    public static function unlock($path) {
        $hash = md5($path);
        if(empty(self::$file[$hash])) {
            throw new Exception('File is already unlocked');
        }
        if(!flock(self::$file[$hash], LOCK_UN)) {
            throw new Exception('Couldn\'t unlock file');
        }
        fclose(self::$file[$hash]);
        self::$file[$hash] = null;
    }
}
?>