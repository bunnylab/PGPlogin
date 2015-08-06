 <?php

    // MySQL database constants
    $username = "root";
    $password = "sometimesiwonderaboutentropy";
    $host = "localhost";
    $dbname = "publickey_test";

    // Set database options to use UTF-8 
    $options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8');
    
    // Try to open database die with exception on failure
    try
    {
        // Initialize PDO database 
        $db = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8", $username, $password, $options);
    }
    catch(PDOException $ex)
    {
        // Output exception and terminate program
        die("Failed to connect to the database: " . $ex->getMessage());
    }
    
    // Set PDO to throw and exception when we hit an error so we can catch them
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set PDO to return rows as associative arrays
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	
	// Set PDO to use real prepared statements for security
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // If magic quotes are used in the get, post or cookie data remove them
	// This is a security fix, magic quotes are awful
    if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc())
    {
        function undo_magic_quotes_gpc(&$array)
        {
            foreach($array as &$value)
            {
                if(is_array($value))
                {
                    undo_magic_quotes_gpc($value);
                }
                else
                {
                    $value = stripslashes($value);
                }
            }
        }
    
        undo_magic_quotes_gpc($_POST);
        undo_magic_quotes_gpc($_GET);
        undo_magic_quotes_gpc($_COOKIE);
    }
    
    // Tell the browser content is in html and utf-8 encoding
    header('Content-Type: text/html; charset=utf-8');
    
    // Start our php session
    session_start();

    // Closing tag omitted to prevent known buggy behavior with header()
	// Function and inserting extra white space, wtf php