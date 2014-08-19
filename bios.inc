<?php
// bios.inc

// displayOneBio
// copied from events.inc
// ID - ID of bio to display
// displays single bio
function displayOneBio($ID)
{
   $cxn = open_stream();
   $sql = "SELECT * FROM bios WHERE ID=$ID";
   $result = query($cxn, $sql);
   if($row = mysqli_fetch_assoc($result))
   {
      extract($row);
      echo "<table border><tr><td rowspan=2 bgcolor=BLACK valign=top><img src='http://www.worldsapartgames.org/bios/$ID.png' alt='Image goes here'></td>
            <td><b>$name</b></td>
            <td>$title</td></tr><tr><td colspan=2>" .
            ((strlen($forumName) > 0) ? "Forum Name: $forumName<hr>" : "") .
            "$bio
            <hr>Favorite Games: $favGames" .
            ((strlen($quote) > 0) ? "Quote: $quote" : "" )
            . "</td></tr></table>";
   }
   else
   {
      echo "Bio #$ID not found.<br>";
   }
}           

// displayBios
// displays all Bios
function displayBios()
{
   $cxn = open_stream();
   $sql = "SELECT b.name name,
                  b.ID ID,
                  b.title title,
                  b.forumName forumName,
                  b.bio bio,
                  b.favGames favGames,
                  b.quote quote
             FROM bios b
             JOIN members m
               ON b.ID = m.ID
         ORDER BY b.order,
                  m.lname,
                  m.fname";
   $result = query($cxn, $sql);
   
   echo "<table border>\n";
   
   while($row = mysqli_fetch_assoc($result))
   {
      extract($row);
      echo "<tr><td rowspan=2 bgcolor=BLACK valign=top><img src='http://www.worldsapartgames.org/bios/$ID.png' alt='Image goes here'></td>
            <td><b>$name</b></td>
            <td>$title</td></tr><tr><td colspan=2>" .
            ((strlen($forumName) > 0) ? "Forum Name: $forumName<hr>" : "") .
            "$bio
            <hr>Favorite Games: $favGames" .
            ((strlen($quote) > 0) ? "Quote: $quote" : "" )
            . "</td></tr><tr><td bgcolor=BLACK colspan=3 height=20></td></tr>\n";
   }
   echo "</table><p>";
}
      