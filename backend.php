<?php 
/**
 * Simple gallery backend
 */

define("GALLERY", true);
define("PATH", "pictures/");

if(!isset($_POST['action'])) {
    send_json(false, "Unknown action.");
}

session_start();

$user = new User();
$user->create('hans', 'peter');
$user->create('tom', 'password');

try {
    $db = new DB(PATH, "db.json");
}
catch (DBException $e) {
    send_json(false, $e->getMessage());
}

switch($_POST['action']) {
    case "start":
        // Initial request
    
        send_json(true, array(
            'pictures' => $db->length(), 
            'logged_in' => (int)$user->logged_in()
        ));
        break;
        
    case "upload":
        if(!$user->logged_in())  {
            send_json(false, "Please login before uploading.");
        }
        
        // Create file name
        $name = basename($_FILES['picture']['name']);
        
        // Check extension
        if(!in_array(get_extension($name), array('jpg', 'jpeg', 'gif', 'png'))) {
    		send_json(false, 'Only '.implode(',',$allowed_ext).' files are allowed!');
    	}
        
        // Calculate target
        $target = PATH . $name;
        $i = 0;
        while(file_exists($target)) {
            $target = PATH . $i . '_' . $name;
            $i++;
        }
        
        // Copy to PATH
        if(move_uploaded_file($_FILES['picture']['tmp_name'], $target)) {
            $id = $db->add($name, array());
            send_json(true, array("message" => "File uploaded!", "id" => $id, "pictures" => $db->length()));
        } else{
            send_json(false, "Can't upload the file.");
        }
        break;
        
    case "login":
        // Login
        
        if(empty($_POST['name']) || empty($_POST['password'])) {
            send_json(false, "Invalid data.");
        }
        else if(!$user->login($_POST['name'], $_POST['password'])) {
            send_json(false, "Invalid login data.");
        }
        send_json(true, "Welcome ". $_POST['name']);
        break;
        
    case "logout":
        // Logout
        
        $user->logout();
        send_json(true, "Good bye.");
        break;
        
    case "show":
        // Show picture
        
        if(!isset($_POST['id'])) {
            send_json(false, "Invalid picture.");
        }
        try {
            $entry = $db->get($_POST['id']);
        }
        catch(DBException $e) {
            send_json(false, $e->getMessage());
        }
        send_json(true, $entry);
        break;
        
    case "modify_tags":
        // Modify tags of a picture
        
        if(!isset($_POST['id']) || !isset($_POST['tags'])) {
            send_json(false, "Invalid data");
        }
        try {
            $db->modify_tags($_POST['id'], $_POST['tags']);
        }
        catch(DBException $e) {
            send_json(false, $e->getMessage());
        }
        send_json(true);
        break;
        
    default:
        send_json(false, "Unknown action.");
}

// Send response as json
function send_json($correct, $message = null) {
    $response = array();
    $response['status'] = $correct ? 'ok' : 'error';
    
    if(is_array($message)) {
        $response = array_merge($message, $response);
    }
    else if(!is_null($message)) {
        $response['message'] = $message;
    }
    header('Content-type: application/json');
    die(json_encode($response));
}

// Return file extension
function get_extension($file_name){
	$ext = explode('.', $file_name);
	$ext = array_pop($ext);
	return strtolower($ext);
}

// Load classes
function __autoload($class_name) {
    include 'class.' . $class_name . '.php';
}