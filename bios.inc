<?php
/**
 * Bios.inc is a library for the depreciated Bio Module.
 *
 * PHP version 5.4
 *
 * LICENSE: TBD
 *
 * @category   Library
 * @package    FriendComputer
 * @author     Michael Whitehouse 
 * @author     Crideke Crouch 
 * @author     Desmond Duval 
 * @copyright  2009-2014 Pioneer Valley Gaming Collective
 * @license    TBD
 * @version    GIT:$ID$
 * @link       http://www.worldsapartgames.org/fc
 * @since      Project has existed since time immemorial.
 * @deprecated since version 1.2
 */

/**
 * displayOneBio()
 * @param integer $ID
 * This function takes an integer ID of a member, and displays
 * that member's bio wherever it is called from on a page.
 */
function displayOneBio($ID)
{
    $cxn = open_stream();
    $sql = "SELECT * FROM bios WHERE ID=$ID";
    $result = query($cxn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        echo "<table border><tr><td rowspan=2 bgcolor=BLACK valign=top>"
        .  "<img src='http://www.worldsapartgames.org/bios/$ID.png' alt='Image goes here'></td>
            <td><b>$name</b></td>
            <td>$title</td></tr><tr><td colspan=2>" .
            ((strlen($forumName) > 0) ? "Forum Name: $forumName<hr>" : "") .
            "$bio
            <hr>Favorite Games: $favGames" .
            ((strlen($quote) > 0) ? "Quote: $quote" : "" )
            . "</td></tr></table>";
    } else {
        echo "Bio #$ID not found.<br>";
    }
}           

/**
 * displayBios()
 * This function displays a list of all members with bios, and their
 * abbrieviated bios alongside.
 */
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
   
    while ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        echo "<tr><td rowspan=2 bgcolor=BLACK valign=top>"
        .  "<img src='http://www.worldsapartgames.org/bios/$ID.png' alt='Image goes here'></td>
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
      