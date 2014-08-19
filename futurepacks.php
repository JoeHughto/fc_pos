<?php
   $title = "Future Packs";
   include('funcs.inc');
   include('friendcomputer.inc');
   include('header.php');
   $cxn = open_stream();
   $message = '';

   echo"<hr>";
   
   if(isset($_POST['submit']))
   {
	   extract($_POST);
	   
	   if ($_POST['submit'] == 'Add Packs')
	   {
		   adjustPacksOnAcct($_POST['target'], $qty, htmlspecialchars($_POST['notes'], ENT_QUOTES));
	   }
	   if ($_POST['submit'] == 'Remove Packs')
	   {
		   adjustPacksOnAcct($_POST['target'], ($qty * -1), htmlspecialchars($_POST['notes'], ENT_QUOTES));
	   }
	   if ($_POST['submit'] == 'Convert Packs')
	   {
		   convertPacksToStoreCredit($_POST['target'], $qty);
	   }
   }
   
   if ($_SESSION['eve'] == 1 || $_SESSION['adm'] == 1 || $_SESSION['reg'] == 1)
   {
	   echo "<h3>Select Member</h3>
			 <form method='post'>";
			 selectMember('selMem', 0);
	   echo "<input type='submit' name='update' value='Get Member Info'></form>";
   }
   
   if ($_POST['update'] == 'Get Member Info')
   {
	   extract($_POST);
	   
	   printMember($selMem, 1);
	   $numpacks = getAccountPacks($selMem);
	   
	   echo "<br>Current packs: " . $numpacks . "<br>";
	   echo "<form method='post'>";
	   echo "<input type='hidden' name='update' value='Get Member Info'>
			 <input type='hidden' name='selMem' value='$selMem'>
			 <input type='hidden' name='target' value='$selMem'>";
	   echo "Quantity of Packs:<input type='text' name='qty'><br>";
	   echo "Why Adding/Removing?:<input type='text' name='notes'><br>";
	   
	   if ($_SESSION['eve'] == 1 || $_SESSION['adm'] == 1)
	   {
		   echo "<input type='submit' name='submit' value='Add Packs'>";
	   }
	   
	   echo "<input type='submit' name='submit' value='Remove Packs'>
			 <input type='submit' name='submit' value='Convert Packs'><br>
			 </form>";
   }
   
   echo "<h3>Your Future Packs</h3>";
   
   $numpacks = getAccountPacks($_SESSION['ID']);
   
   echo "You currently have " . $numpacks . " future packs on account.<br>
		 What would you like to do with them?<br><br>";
	
	echo "<form method='post'>
		  <input type='hidden' name='target' value='" . $_SESSION['ID'] . "'>";
	echo "Qty:<input type='text' name='qty'><br>
		  <input type='submit' name='submit' value='Convert Packs'><br>
		  </form>";
	
	if ($_SESSION['adm'] == 1)
	{
		echo "<hr><h3>Future Packs Report</h3>";
		$sql = "SELECT memberID, SUM( qty ) FROM futurepacks GROUP BY memberID ORDER BY SUM( qty ) DESC";
		$result = query($cxn, $sql);
		echo "<table><tr><th width=250>Name</th><th width=100>Packs</th></tr>";
		while($row = mysqli_fetch_row($result))
		{
		    if($row[1] <= 0)
		        break;
			echo "<tr><td>" . printMemberString($row[0], 1) . "</td><td align='center'>" . $row[1] . "</td></tr>";
		}
		echo "</table>";
	}

   include('footer.php');
?>
