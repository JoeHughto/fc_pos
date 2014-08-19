<?php
// gatheremail
// take email addresses and puts them into the member database

include ('funcs.inc');
include ('header.php');

function equery($sql)
{
  $dbname="worldsap_newsletter";
  $host="localhost";
  $user="worldsap_newslet";
  $pwd="ass1";

  $access = new mysqli($host, $user, $pwd, $dbname);

  if($err = mysqli_connect_errno())
     die ("Couldn't connect to server." . $err);
  else
  {
     if(!$result = mysqli_query($access, $sql))
     {
        displayError("Query Error!<br>Query: $sql<br>SQL Error: " . mysqli_error($access));
        return (FALSE);
     }
     else return ($result);
  }
}

$cxn = open_stream();

$sql = "SELECT email, fname, lname FROM info";
$result = equery($sql);

   while($row = mysqli_fetch_assoc($result))
   {
      extract($row);
      $login = strtolower(substr($fname, 0, 1) . $lname);
      $sql = "SELECT ID FROM members WHERE email='$email' OR (fname='$fname' AND lname='$lname') OR login='$login'";
      $r = query($cxn, $sql);
      if(!(mysqli_affected_rows($cxn) > 0))
      {
         $stmt = $cxn->prepare("INSERT INTO members (login, password, fname, lname, email, memberSince, contribExp, workingExp)
                                             VALUES (?,?,?,?,?, NOW(), NOW(), NOW())");
         $password = hash('sha256','changeme');
         $stmt->bind_param('sssss', $login, $password, $fname, $lname, $email);
         if($stmt->execute())
         {
            echo "<font color='GREEN'>$fname $lname created</font><br>";
         }
         else
         {
            echo "Error inserting $fname $lane<br>";
         }
      }
      else
      {
         echo "$fname $lane already there<br>";
      }
   }
   

?>
