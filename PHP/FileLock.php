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
    const FILELOCK_ERR  = 0;
    const FILELOCK_NTC  = 1;
    
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
            throw new Exception('File is already locked by this process. Use another path', self::FILELOCK_NTC);

        }
        if(!file_exists(dirname($path)) || !is_writable(dirname($path))) {
            throw new Exception('Couldn\'t get the lock, wrong path to lock file', self::FILELOCK_ERR);
        }
        self::$file[$hash] = fopen($path, 'w');
        if(!self::$file[$hash]) {
            throw new Exception('Couldn\'t get the lock, open file failed', self::FILELOCK_ERR);
        }
        if(!flock(self::$file[$hash], LOCK_EX | LOCK_NB)){
            fclose(self::$file[$hash]);
            self::$file[$hash] = null;
            throw new Exception('File is locked by another process', self::FILELOCK_NTC);
        }
    }
    /**
     * try to lock already locked file
     * @param string $path
     */
    public static function check($path) {
        $hash = md5($path);
        if(empty(self::$file[$hash])) {
            throw new Exception('File not locked by this process. Use another path', self::FILELOCK_NTC);
        }
        if(!file_exists(dirname($path)) || !is_writable(dirname($path))) {
            throw new Exception('Couldn\'t get the lock, wrong path to lock file', self::FILELOCK_ERR);
        }
        self::$file[$hash] = fopen($path, 'w');
        if(!self::$file[$hash]) {
            throw new Exception('Couldn\'t get the lock, open file failed', self::FILELOCK_ERR);
        }
        if(!flock(self::$file[$hash], LOCK_EX | LOCK_NB)){
            fclose(self::$file[$hash]);
            self::$file[$hash] = null;
            throw new Exception('File is locked by another process', self::FILELOCK_NTC);
        }
    }
    /**
     * unlock file, locked by FileLock::lock function
     * @param string $path
     */
    public static function unlock($path) {
        $hash = md5($path);
        if(empty(self::$file[$hash])) {
            throw new Exception('File is already unlocked', self::FILELOCK_NTC);
        }
        if(!flock(self::$file[$hash], LOCK_UN)) {
            throw new Exception('Couldn\'t unlock file', self::FILELOCK_ERR);
        }
        fclose(self::$file[$hash]);
        self::$file[$hash] = null;
    }
}
?>