
// On document load do this stuff
$(document).ready(function(){
			
	// Hide the response elements until needed
   $("#returned").hide();
   
   
   // Function for public key form submission with ajax
	$('#loginform')
    .ajaxForm({
		url : 'pgpvalidator.php', 
        dataType : 'html',
		data: { flag: 'encryptstep' },
		success : function (response) {
           	$('#returned').show();
			$('#encmessage').val(response);
			$("#loginform").hide();
									
        }
    });
	
		
	// Function for decryption response form submission with ajax
	$('#responseform')
    .ajaxForm({
        url : 'pgpvalidator.php', 
        dataType : 'html',
		data: { flag: 'decryptstep' },
        success : function (response) {
			alert(response);
			            
			// On successful login redirect to private page
			if(response.trim() == 'login successful') {
				window.location.replace("/secret/private.php");
			} 
								
        }
    });
	
});