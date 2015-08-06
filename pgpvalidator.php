<?php

// Use open ssl random bytes to give us a secure 256 byte string
// do not use rand or other less secure sources of entropy here
function generateRandomString($bytes = 256) {
    return bin2hex(openssl_random_pseudo_bytes($bytes));
}

// fix me
// there seems to be a bug in the release of gnupgp we're using that will crash
// the program when its given a badly formed key instead of just returning an error
// for now I've just put in some placeholder code but we'll need to do a full fix later
function keyCheck($key_text, $gpg) {
	
	if(strlen($key_text) < 50) {
		return False;
	}
	else {
		return True;
	}
		
}


// Class to encrypt a random authentication string with the users provided
// PGP key.  Stores their public key and the the authentication string in database 
class encryptStep {
	
	// Class variables
	private $db = NULL;
	private $gpg = NULL;
	private $loginstring = NULL;
	private $ciphertext = NULL;
	
	public $publickey = NULL;
	public $nick = NULL;
	public $keyhash = NULL;
	
	// Constructor to set class variables
	function __construct($db, $gpg, $publickey, $nick="none") {
		
		$this->db = $db;
		$this->gpg = $gpg;
		$this->publickey = $publickey;
		$this->nick = $nick;
				
		// Loginstring response for us to encrypt
		$this->loginstring = generateRandomString();
		
		// Import the public key into our gpg object
		$info = $this->gpg -> import( $this->publickey );
		$fingerprint = $info['fingerprint'];
		$this->gpg->addencryptkey( $fingerprint );	
							
		// Encrypting the message 
		$this->ciphertext = $this->gpg->encrypt( $this->loginstring );
						
		// Use key fingerprint for mysql indexing and store in php session
		$this->keyhash = $fingerprint;
						
	}
	
		
	// Encrypts the authentication string with the given key returns NULL if there's an error
	function returnCipher() {  
																
		//PDO sql insert statement to store record for this public key
		try {
			//insert keyhash, publickey, nickname and loginstring if its a duplicate key just update nick and loginstring
			$stmt = $this->db->prepare("INSERT INTO publickeys(keyhash,publickey,nick,loginstring) VALUES(:keyhash,:publickey,:nick,:loginstring) 
				ON DUPLICATE KEY UPDATE nick= VALUES(nick), loginstring= VALUES(loginstring)");
			$stmt->execute(array(':keyhash' => $this->keyhash,':publickey' => $this->publickey, ':nick' => $this->nick, ':loginstring' => $this->loginstring));
						
		} catch(PDOException $ex) {
			echo "An SQL Error occured!"; 
			return NULL;
		}
						
		// Return our cipertext
		return $this->ciphertext;
	}
	
}

// Class to perform decryption step operations
class decryptStep {
	
	// Class variables
	private $db = NULL;
	private $publickey = NULL;
	private $decmessage = NULL;
	private $keyhash = NULL;
	
	// Set private variables
	function __construct($db, $publickey, $decmessage, $keyhash){
		$this->db = $db;
		$this->publickey = $publickey;
		$this->decmessage = $decmessage;
		$this->keyhash = $keyhash;
	}
	
	// Checks the decrypted authentication string against the stored value
	// if it's the same user has provided proof they posses the corresponding
	// private key and we return true
	function checkDecrypt() {
			
		// PDO sql select statement to get record for this public key
		try {
			$stmt = $this->db->prepare("SELECT * FROM publickeys WHERE keyhash = :keyhash LIMIT 1");
			$stmt->execute(array(':keyhash' => $this->keyhash));
			$keyrow = $stmt->fetchAll();
			
			// Return only the first match
			// We do not allow duplicate keys so unless there is a hash collision should never be more than one match
			$loginstring = $keyrow[0]["loginstring"];
								
		} catch(PDOException $ex) {
				echo "An SQL Error occured!"; 
				return false;
		}
		
		// Compare the stored authentication string to the decrypted one
		if($this->decmessage === $loginstring) { 
			return true; 
		} else { 
			return false;	
		}
		
	}
	
}


// Start Here

// Require common code to initialize database and start session
require("common.php");

// Require code to initialize our gnupgp instance 
require("gnupgp.php");

// Login flag initialized to false by default
$login_ok = false;

// If they exist clean post variables from user forms 
if( !empty( $_POST["publickey"] ) ) { $publickey = $_POST["publickey"].trim(); }
if( !empty( $_POST["nick"] ) ) { $nick = $_POST["nick"].trim(); }
if( !empty( $_POST["decmessage"] ) ) { $decmessage = $_POST["decmessage"].trim(); }



// Switch execution if form submit is flagged as encryption step or decryption step
// First form submits the users public key and we return a ciphertext challenge
// Second form submits their response to the challenge and we authenticate based on its correctness
switch ($_POST["flag"])
	{
	// Encryption step
	case "encryptstep":
	
		// Clear session data from any previous login attempts
		//$_SESSION = array();
	
		// Check if the provided pgp key seems to be valid
		if( !keyCheck($publickey, $gpg) ) {
			die("Not a valid PGP Key!");
		}
	
		// Initialize encryption step object and fetch ciphertext
		$encrypt = new encryptStep($db, $gpg, $publickey, $nick);
		$ciphertext = $encrypt->returnCipher();
		
		// Function returns null if unsuccessful 
		if( is_null($ciphertext) ){
			echo "Encryption Step Failed";
		}
		// If successful set key info in session and return ciphertext 
		else {
			$_SESSION['publickey'] = $encrypt->publickey;
			$_SESSION['nick'] = $encrypt->nick;
			$_SESSION['keyhash'] = $encrypt->keyhash;
			echo $ciphertext;
		}
		break;

	// Decryption step
	case "decryptstep":
	
		// Check that our session variables are still set
		if(empty($_SESSION['publickey'])) {
			die("Session error. Please return to login page!");
		}
	
		// Initialize decryption step object
		$decrypt = new decryptStep($db, $_SESSION['publickey'], $decmessage, $_SESSION['keyhash']);
		
		// If their response to the ciphertext challenge is correct then execute this code
		if( $decrypt->checkDecrypt() ){
			// Set the login variable to true
			$login_ok = true;
			// Set the session user to their entered publickey
			$_SESSION['user'] = $_SESSION['publickey']; 
			
			echo "login successful";
		}
		
		// If response is incorrect execute this code
		else {
			// remove any existing login credentials and exit
			unset($_SESSION['user']);
			
			echo "login failed";
		}
		break;
	
	// Returns an error in case someone is messing with the client side
	// form values
	default:
		echo "Malformed Form Data";
		break;
	}

?>