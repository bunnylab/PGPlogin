<?php

	// Set environment for gnupgp instance
	putenv("GNUPGHOME=/tmp");
	
	// Create new GnuPG object
	$gpg = new gnupg();
	
	// Clear any encryption keys stored just in case
	$gpg -> clearencryptkeys();
	
	// Throw exception if error occurs
	$gpg->seterrormode(gnupg::ERROR_EXCEPTION);
	
// No ending tags because php is like that