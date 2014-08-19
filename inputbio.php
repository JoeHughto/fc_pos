<?php
// inputbio.php

// allows users to create and edit bios

// if row exists for user, then presents for editing
// if not, shows blank form

// POST - info to add into database

// GET - ID - shows info for that ID. ADM only

// Bio images should be no wider than 150 pixels

include('funcs.inc');
include('header.php');
include('bios.inc');


$cxn = open_stream();
$infoID = $_SESSION['ID'];

$safePost = array('ID', 'name', 'title', 'bio', 'favGames', 'quote', 'forumName', 'active', 'submit');
safePost($safePost);

extract($_POST);

// If post data comes in from someone who is not authorized
if(($ID != $_SESSION['ID']) && ($submit == 'submit'))
{
   if($_SESSION['adm'] != 1)
   {
      echo "<font color=RED>You do not have authority to edit another member's bio<p>";
      include('footer.php');
      die();
   }
   else
   {
      echo "Editing ID#$infoID<p>";
   }
}


// Check for POST and process - show results and end
if($submit == 'submit')
{
   $bio = strip_tags(substr($bio, 0, 2500));
   $bio = ereg_replace("\n\r", "<p>", $bio);
   $name = strip_tags($name);
   $title = strip_tags($title);
   $favGames = strip_tags($favGames);
   $quote = strip_tags($quote);
   $forumName = strip_tags($forumName);
   
$sql = "SELECT * FROM bios WHERE ID='$ID'";
$result = query($cxn, $sql);
if(!($row = mysqli_fetch_assoc($result)))
   {
      $stmt = $cxn->prepare("INSERT INTO bios (ID, name, title, bio, favGames, quote, forumName, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

      $stmt->bind_param("dssssssd", $ID, $name, $title, $bio, $favGames, $quote, $forumName, $active);
      if($stmt->execute())
      {
         echo "Bio #$ID successfully added<p>";
      }
      else
      {
         echo "Failure to add bio<p>";
      }
   }
   else
   {
      // only admins may approve bios. If changed, they must be approved.
      if($_SESSION['adm'] != 1)
         $active = 0;
      
      $stmt = $cxn->prepare("UPDATE bios
                                SET name=?,
                                    title=?,
                                    bio=?,
                                    favGames=?,
                                    quote=?,
                                    forumName=?,
                                    active=?
                              WHERE ID=?");
      $stmt->bind_param("ssssssdd", $name, $title, $bio, $favGames, $quote, $forumName, $active, $ID);
      if($stmt->execute())
      {
         echo "Bio #$ID successfully updated<p>";
         displayOneBio($ID);
         include('footer.php');
         die();
      }
      else
      {
         echo "Failure to update bio<p>";
         include('footer.php');
         die();
      }
   }
}

// Check for GET if ADM
if($_GET['ID'] > 0)
{
   if($_SESSION['adm'] == 1)
   {
      $infoID = $_GET['ID'];
      $self = FALSE;
      echo "Admin Editing ID#$infoID<p>";
   }
   else
   {
      $infoID = $_SESSION['ID'];
      $self = TRUE;
   }
}
else
{
   $infoID = $_SESSION['ID'];
}




// Load existing info if it exists
$sql = "SELECT * FROM bios WHERE ID='$infoID'";
$result = query($cxn, $sql);
if($row = mysqli_fetch_assoc($result))
{
   extract($row);
   displayOneBio($infoID);
}

// Display Form
if($infoID == $_SESSION['ID'])
   echo "Updating Your Bio #$infoID<p>";
else
   echo "Updating Bio for " . printMemberString($infoID, 1) . " (#$infoID)<p>";

$bio = ereg_replace("<p>", "\n\r", $bio);

echo "<form action='inputbio.php' method='POST'>
<input type='hidden' name='ID' value='$infoID'>
Name to Display: <input type='text' name='name' value='$name' size=50 maxlength=50><p>
Forum Name: <input type='text' name='forumName' value='$forumName' size=30 maxlength=30><p>
Title (official titles only): <input type='text' name='title' value='$title' size=40 maxlength=40><p>
Bio (max 2000 characters, no HTML):<br>
<textarea rows=20 cols=50 name='bio'>$bio</textarea><p>
Favorite Games: <input type='text' name='favGames' value='$favGames' size=50 maxlength=255><p>
Quote: <input type='text' name='quote' value='$quote' size=50 maxlength=255><p>\n" .
(($_SESSION['adm'] == 1) ? ((($active == 1) ? "<input type='checkbox' name='active' value=1 checked>"
                                           : "<input type='checkbox' name='active' value=1>") . " Active?<p>")
                         : "<input type='hidden' name='active' value=0>") .
"<input type='submit' name='submit' value='submit'></form><p>";

include('footer.php');
?>