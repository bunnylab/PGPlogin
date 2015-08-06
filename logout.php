 <?php

    // First we execute our common code to connection to the database and start the session
    require("common.php");
    
    // We remove the user's data from the session
    unset($_SESSION['user']);
	
	// Clear all other session data
	$_SESSION = array();
    
    // Redirect them to the login page
    header("Location: /secret");
    die("Redirecting to login page"); 