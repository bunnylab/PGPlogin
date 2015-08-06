<?php

// Make connection to the database and start the session
require("common.php");
    
// Check if the user is logged in
if(empty($_SESSION['user']))
{
	// If user is not logged in redirect to login
    header("Location: /secret");
        
    // Kill the session, without this users can simply bypass
	// our login restriction
    die("Redirecting to login page");
	} 
?>
Hello <?php echo htmlentities($_SESSION['nick'], ENT_QUOTES, 'UTF-8'); ?>, you made it to the restricted page!<br />
<a href="logout.php">Logout</a>