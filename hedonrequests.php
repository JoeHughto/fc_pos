<?php
   $title = "Pending Hedon Requests";
   include('funcs.inc');
   include('friendcomputer.inc');
   include('credits.inc');
    
    if(isset($_POST['submit']))
    {
       extract($_POST);
       
       if ($_POST['submit'] == 'submit approve')
       {
    	for ($i=0; $i<count($selectedReqs); $i++) {
    		approveHedonRequest($selectedReqs[$i]);
    		sleep(2);
    	}
       }
       if ($_POST['submit'] == 'submit deny')
       {
    	   for ($i=0; $i<count($selectedReqs); $i++) {
    		denyHedonRequest($selectedReqs[$i]);
    	}
       }
    }
   include('header.php');
   
   $cxn = open_stream();
   $message .= '';

   echo"<hr>";

   if($_SESSION['mem'] != 1)
   {
      die("You must be an officer to use this application");
   }
   
	
	echo "<form action='hedonrequests.php' method='post'>";
	
	displayAllHedonRequests();
	
	echo"<button name='submit' value='submit approve'>Approve</button>
		 <button name='submit' value='submit deny'>Deny</button><p></form>";

   include('footer.php');
?>
