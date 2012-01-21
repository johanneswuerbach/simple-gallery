<?php defined('GALLERY') or die('No direct script access.');
/**
 * Access to our photo / tag db
 */
class DB {
    private $path, $db_file, $db;
    
    /**
     * Create or read the current db
     */
    public function __construct($path, $db_file) {
        $this->path = $path;
        $this->db_file = $db_file;
        if(!file_exists($this->db_file())) {
            $status = $this->create();
            if($status === false) {
                throw new DBException("Can't create db file \"".$this->db_file()."\".");
            }
        }
        else {
            $this->read();
        }
    }
    
    /**
     * Create 
     */
    private function create() {
        $files = scandir($this->path);
        $this->db = array();
        foreach($files as $file) {
            if($file[0] != ".") {
                $entry = array();
                $entry['file'] = $file;
                $entry['tags'] = array();
                $this->db[] = $entry;
            }
        }
        return $this->write();
    }
    
    /**
     * Full path to db file
     */
    private function db_file() {
        return $this->path.$this->db_file;
    }
    
    /**
     * Write db to disk
     */
    private function write() {
        return file_put_contents($this->db_file(), json_encode($this->db), LOCK_EX);
    }
    
    /**
     * Read db from disk
     */
    private function read($reload = false) {
        if($reload || empty($this->db)) {
            $json = file_get_contents($this->db_file());
            $this->db = json_decode($json, true);
        }
        return $this->db;
    }
    
    /**
     * Modify tags of a picture
     */
    public function modify_tags($id, $tags) {
        $id = intval($id);
        if(!is_array($tags)) {
            $tags = explode(",", $tags);
        }
        if(!isset($this->db[$id])) {
            throw new Exception("Invalid id.");
        }
        $this->db[$id]['tags'] = $tags;
        $this->write();
    }
    
    /**
     * Add a picture
     */
    public function add($file, $tags) {
        $this->read(true);
        $this->db[] = array('file' => $file, 'tags' => $tags);
        $this->write();
        return $this->length() - 1;
    }
    
    /**
     * Number of pictures
     */
    public function length() {
        return count($this->db);
    }
    
    /**
     * Get a picture
     */
    public function get($id) {
        $id = intval($id);
        if(!isset($this->db[$id])) {
            throw new DBException("Invalid id.");
        }
        $entry = $this->db[$id];
        $entry['url'] = rawurlencode($this->path . $entry['file']);
        return $entry;
    }
}

class DBException extends Exception {}