<?php
/**
 * @file resetpassword.php
 * @brief resetpassword.php resets a user's password for them.
 * 
 * It requires a hashcode that is randomly generated when a user reports that 
 *   they've lost their password. That hashcode can only be found in the email 
 *   FriendComputer sends the member.
 * 
 * This file includes:
 * config.inc:
 * - Access to the db.
 * 
 * Possible Arguments:
 * POST:
 * - submit - Indicates the submit button was pushed, and we have work to do.
 * - hashcode - A pseudorandom hashcode which links a user to an email address,
 *   confirming their identity so we can let them reset their password.
 * - newpass - The desired new password.
 * - newpassconfirm - A copy of the new password, to make sure it's right.
 * 
 * GET:
 * - reset - This is a hashcode, which will populate the hashcode textbox if
 *   initialized. This allows us to link users to the reset password page,
 *   and autofill the hard part, right from their reset email.
 * 
 * @link http://www.worldsapartgames.org/fc/resetpassword.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

$title = "Forgotten Password Reset";
$version = "1.8d";
require 'config.inc';

//Certain functions need to be included without including funcs.inc, as funcs enforces login on all pages

/**
 * query submits a given query to the given database connection, and returns
 *   a MySQL Result.
 * @see funcs.inc
 * @param connection $cxn Connection to the database
 * @param string $sql Submitted SQL Query
 * @retval mixed MySQL Result
 */
function query($cxn, $sql)
{
    if (!$result = mysqli_query($cxn, $sql)) {
        echo "Query Error!<br>Query: $sql<br>SQL Error: " 
            . mysqli_error($cxn) . "<br>";
        return (false);
    } else {
        return ($result);
    }
}

/**
 * printMemberString
 * @param int $num Member ID whose name we are seeking.
 * @param int $order The order said member's name should be in.
 * @see funcs.inc
 * Order Codes:
 *   1 - fname lname
 *   2 - lname
 *   3 - fname
 *   4 - lname, F
 * @retval string|boolean Returns a string containing a member's name, or false
 */
function printMemberString($num, $order)
{
    $cxn = open_stream();
    $sql = "SELECT fname, lname FROM members WHERE ID='$num'";
    $result = query($cxn, $sql);
    if (!$row = mysqli_fetch_assoc($result)) {
        return false;
    }
    extract($row);
    switch($order)
    {
    case 1:
        return "$fname $lname ($num)";
        break;
    case 2: 
        return "$lname, $fname ($num)";
        break;
    case 3: 
        return "$fname";
        break;
    case 4: 
        $fname = substr($fname, 0, 1);
        return "$lname, $fname ($num)";
        break;
    case 5: 
        return "$fname $lname";
        break;
    }
    return "Invalid Display Type Requested";
}

echo "<HTML><HEAD><TITLE>$title</TITLE></HEAD>\n";
$cxn = open_stream();

echo"<hr>";

if (isset($_POST['submit'])) {
    extract($_POST);
    $sql = "SELECT * FROM passwordReset WHERE (hash='" . $_POST['hashcode'] . "' AND (DATE_ADD(NOW(), INTERVAL 1 HOUR) < expires))";
    $result = query($cxn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        if ($_POST['newpass'] == $_POST['newpassconfirm']) {
            $memID = $row['member'];
            $newpass = $_POST['newpass'];
            $newpass = hash('sha256', $newpass);
            $sql = "UPDATE members SET password='$newpass' WHERE ID='$memID'";
            if ($result = query($cxn, $sql)) {
                $sql = "DELETE FROM passwordReset WHERE (member='$memID' OR (DATE_ADD(NOW(), INTERVAL 1 HOUR) > expires))";
                $result = query($cxn, $sql);
                echo "Password Reset Successful!<br>";
                echo "<a href='index.php'>Click here to login!</a><br>";
            }
        }
    }
}

$temphash = "";
if (isset($_GET['reset'])) {
    $temphash = $_GET['reset'];
}

echo "<h2>Forgotten Password Reset</h2>
    <form method='post'>
    <table>
    <tr><td width=150>Confirmation Code:</td><td width=450><input type='text' name='hashcode' value='$temphash' width=400></td></tr>
    <tr><td>New Password:</td><td><input type='password' name='newpass' width=400></td></tr>
    <tr><td>Confirm Password:</td><td><input type='password' name='newpassconfirm' width=400></td></tr>
    </table>

    <input type='submit' name='submit' value='Submit'>
    </form>";

require 'footer.php';
?>
