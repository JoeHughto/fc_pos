<?php
   $title = "Forgotten Password Reset";
   include('config.inc');
   
	//Certain functions need to be included without including funcs.inc, as funcs enforces login on all pages
   
	// query
	// args: cxn: a SQL connection, sql: an SQL query
	// returns the result
	function query($cxn, $sql)
	{
	   if(!$result = mysqli_query($cxn, $sql))
	   {
		  echo "Query Error!<br>Query: $sql<br>SQL Error: " . mysqli_error($cxn) . "<br>";
		  return (FALSE);
	   }
	   else return ($result);
	}
	
	// printMemberString
	// args: member number,
	//       order - 1 for fname lname, 2 for lname, fname, 3 for fname only, 4, lname, F
	// returns the name as a string
	function printMemberString($num, $order)
	{
	   $cxn = open_stream();
	   $sql = "SELECT fname, lname FROM members WHERE ID='$num'";
	   $result = query($cxn, $sql);
	   if(!$row = mysqli_fetch_assoc($result)) return FALSE;
	   extract($row);
	   switch($order)
	   {
		  case 1: return "$fname $lname ($num)";
				  break;
		  case 2: return "$lname, $fname ($num)";
				  break;
		  case 3: return "$fname";
				  break;
		  case 4: $fname = substr($fname, 0, 1);
				  return "$lname, $fname ($num)";
				  break;
		  case 5: return "$fname $lname";
				  break;
	   }
	   return "Invalid Display Type Requested";
	}
	
   echo "<HTML><HEAD><TITLE>$title</TITLE></HEAD>\n";
   $cxn = open_stream();

   echo"<hr>";
   
   if(isset($_POST['submit']))
   {
	   extract($_POST);
	   $sql = "SELECT * FROM passwordReset WHERE (hash='" . $_POST['hashcode'] . "' AND (NOW() < expires))";
	   $result = query($cxn, $sql);
	   if ($row = mysqli_fetch_assoc($result))
	   {
		   if($_POST['newpass'] == $_POST['newpassconfirm'])
		   {
			   $memID = $row['member'];
			   $newpass = $_POST['newpass'];
			   $newpass = hash('sha256', $newpass);
			   $sql = "UPDATE members SET password='$newpass' WHERE ID='$memID'";
			   if ($result = query($cxn, $sql))
			   {
				   $sql = "DELETE FROM passwordReset WHERE (member='$memID' OR (NOW() > expires))";
				   $result = query($cxn, $sql);
				   echo "Password Reset Successful!<br>";
				   echo "<a href='index.php'>Click here to login!</a><br>";
			   }
		   }
	   }
   }
   
   $temphash = "";
   if(isset($_GET['reset'])) $temphash = $_GET['reset'];
   
   echo "<h2>Forgotten Password Reset</h2>
			<form method='post'>
			<table>
			<tr><td width=150>Confirmation Code:</td><td width=450><input type='text' name='hashcode' value='$temphash' width=400></td></tr>
			<tr><td>New Password:</td><td><input type='password' name='newpass' width=400></td></tr>
			<tr><td>Confirm Password:</td><td><input type='password' name='newpassconfirm' width=400></td></tr>
			</table>
			
			<input type='submit' name='submit' value='Submit'>
			</form>";

   include('footer.php');
?>
