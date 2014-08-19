<?php
   $title = "Unclaimed Bounties";
   include('funcs.inc');
   include('friendcomputer.inc');
   include('credits.inc');
   
   if(isset($_POST['submit']))
   {
	   extract($_POST);
	   
	   if ($_POST['submit'] == 'submit bounty')
	   {
		   $cxn = open_stream();

		   $note = strip_tags($bountyNote);

		   if($stmt = $cxn->prepare("INSERT INTO bounties
												 (daytime, hedons, notes)
												 VALUES
												 (NOW(), ?, ?)"))
		   {
			  $stmt->bind_param("ds", $bountyAmount, $note);
			  $stmt->execute();
		   }
		   else
		   {
			  displayError("Error Binding Query. Bounty not created. Contact your local High Programmer.");
		   }
	   }
	   if ($_POST['submit'] == 'submit claim')
	   {
		   for ($i=0; $i<count($selectedBounties); $i++) {
			claimBounty($selectedBounties[$i]);
			sleep(1);
		}
	   }
   }
   include('header.php');

   $cxn = open_stream();
   $message .= '';

   echo"<hr>";
   
   if($_SESSION['mem'] == 1)
   {
		 // display bounty create button to admins
		 echo "<b>Create Bounty</b><br>
         <form action='bounties.php' method='POST'>
         Amount offered: <input type='text' maxlength=4 size=4 name='bountyAmount'><br>
         Description: <input type='text' maxlength=50 size=12 name='bountyNote'>
         <input type='submit' name='submit' value='submit bounty'></form><hr>";
   }
	
	echo "<form action='bounties.php' method='post'>";
	
	displayAllBounties();
	
	echo "<button name='submit' value='submit claim'>Claim</button><p></form>";

   include('footer.php');
?>
