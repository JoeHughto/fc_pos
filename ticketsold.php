<?php
// ticketsold.php

// shows how many Repo Tix have sold

function open_stream()
{
  $dbname="worldsap_fc";
  $host="localhost";
  $user="worldsap_fc";
  $pwd="37FUHupr";

  $access = new mysqli($host, $user, $pwd, $dbname);

  if($err = mysqli_connect_errno())
     die ("Couldn't connect to server." . $err);
  else
     return $access;
}

// query
// args: cxn: a SQL connection, sql: an SQL query
// returns the result
function query($cxn, $sql)
{
   if(!$result = mysqli_query($cxn, $sql))
   {
      displayError("Query Error!<br>Query: $sql<br>SQL Error: " . mysqli_error($cxn));
      return (FALSE);
   }
   else return ($result);
}

echo "<html><head><title>Tix Sold</title></head><body>";
echo "Tickets Sold:<Br>\n";

$cxn = open_stream();

$sql = "SELECT SUM(qty) FROM soldItem WHERE itemID='1474'";
$result = query($cxn, $sql);
$row = mysqli_fetch_row($result);

echo "Feb 6th : {$row[0]}<p>\n";

$sql = "SELECT SUM(qty) FROM soldItem WHERE itemID='1475'";
$result = query($cxn, $sql);
$row = mysqli_fetch_row($result);

echo "Feb 13th : {$row[0]}<p>\n";

echo "</body></html>";
?>
