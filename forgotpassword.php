<?php
/**
 * @file ForgotPassword.php is a page designed to allow members to reset their
 *   passwords easily via email.
 *
 * PHP version 5.4
 *
 * LICENSE: TBD
 *
 * @package   FriendComputer\Utility
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @license   TBD
 * @version   GIT:$ID$
 * @link      http://www.worldsapartgames.org/fc/forgotpassword.php
 * @since     Project has existed since time immemorial.
 */

/**
 * This file includes:
 * config.inc:
 *   MySQL Bindings
 *   displayError()
 * Reimplements:
 *   funcs.query()
 *   funcs.printMemberString()
 *   funcs.check_email_address()
 */
$title = "Forgotten Password Reset";
$version = "1.8d";
require_once 'config.inc';

//Certain functions need to be included without including funcs.inc,
//  as funcs.inc enforces login on all pages

/**
 * Query is a reimplementation of funcs.query().
 * @param mixed  $cxn Connection to the database.
 * @param string $sql SQL Query String.
 * @retval boolean Returns either a result, or false.
 */
function query($cxn, $sql)
{
    if (!$result = mysqli_query($cxn, $sql)) {
        displayError("Query Error!<br>Query: $sql<br>SQL Error: " . mysqli_error($cxn));
        return false;
    } else {
        return $result;
    }
}

/**
 * PrintMemberString is a reimplementation of funcs.printMemberString()
 * 
 * Order Values:
 * 1 fname lname
 * 2 lname, fname
 * 3 fname
 * 4 lname, F
 * 
 * @param type $num   is the ID number of the given member.
 * @param type $order An integer representing the order of the name.
 * @retval string Returns a string of the member's name.
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

/**
 * Check_Email_Address is a reimplementation of funcs.check_email_address()
 * @param string $email is the email address of the member who has forgotten
 *   their password.
 * @retval boolean Returns true if the string $email is a legal email address.
 */
function check_email_address($email)
{
    // First, we check that there's one @ symbol, and that the lengths are right
    if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
        // Email invalid because wrong number of characters in one section, or wrong number of @ symbols.
        return false;
    }

    // Split it into sections to make life easier
    $email_array = explode("@", $email);
    $local_array = explode(".", $email_array[0]);
    for ($i = 0; $i < sizeof($local_array); $i++) {
        if (!ereg(
            "^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}"
            . "~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i]
        )
        ) {
            return false;
        }
    }
    // Check if domain is IP. If not, it should be valid domain name
    if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
        $domain_array = explode(".", $email_array[1]);
        if (sizeof($domain_array) < 2) {
            return false; // Not enough parts to domain
        }
        for ($i = 0; $i < sizeof($domain_array); $i++) {
            if (!ereg(
                "^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|"
                . "([A-Za-z0-9]+))$", $domain_array[$i]
            )
            ) {
                return false;
            }
        }
    }
    return true;
}

echo "<HTML><HEAD><TITLE>$title</TITLE></HEAD>\n";
$cxn = open_stream();

echo"<hr>";

if (isset($_POST['submit'])) {
    extract($_POST);

    if (check_email_address($_POST['email'])) {
        $sql = "SELECT * FROM members WHERE email='" . $_POST['email'] . "'";
        $result = query($cxn, $sql);
        if ($row = mysqli_fetch_assoc($result)) {
            $now = date("Y-m-d");
            $date = date_create();
            $date->modify("+7 day");
            $expires = date_format($date, "Y-m-d");

            $tmpstring = $now . $row['password'] . $row['email'];
            $emailhash = hash('sha256', $tmpstring);

            $sql = "SELECT * FROM passwordReset WHERE (member='" . $row['ID']
                .  "' AND (NOW() < expires))";
            $result = query($cxn, $sql);
            if ($tmp = mysqli_fetch_assoc($result)) {
                $sql = "UPDATE passwordReset SET hash='$emailhash', expires='"
                    . "$expires' WHERE (member='" . $row['ID'] . "' AND (NOW()"
                    . " < expires))";
            } else {
                $sql = "INSERT INTO passwordReset (member, hash, expires) VALUES ('" . $row['ID'] . "', '$emailhash', '$expires')";
            }
            if ($result = query($cxn, $sql)) {
                //Send email + notify user that email was sent.
                $subject = "Friend Computer Password Reset Request";
                $body = "Greetings, citizen!\n\n" .
                    "We recently received a request from you to reset your password.\n\n" .

                    "If you did not recently request your password reset, please contact\n" .
                    "Worlds Apart Games to let us know you received this message in error.\n\n" .

                    "To complete the password reset, please click the link below, and you\n" .
                    "will be asked to input a new password.\n\n" .

                    "Click here, and paste this code into the provided box:\n$emailhash\n\n" .
                    "www.worldsapartgames.org/fc/resetpassword.php?reset=$emailhash\n\n" .

                    "This link will expire 7 days from the initial request.\n\n" .

                    "Thanks for being a great citizen!\n" .
                    "High Programmer\n" .
                    "Worlds Apart Games\n" .
                    "www.worldsapartgames.org\n\n" .

                    "Follow us on Facebook!\n" .
                    "www.facebook.com/worldsapartgames";
                if (mail($row['email'], $subject, $body)) {
                    echo "Message sent to " . printMemberString($row['ID'], 1) . "<br>";
                } else {
                    echo "Error: Unable to send email to member, do they lack an email address?";
                }
            }
        } else {
            echo "No member found.";
        }
    } else {
        echo "Incorrectly formatted email address.<br>";
    }
}

echo "<h2>Forgotten Password Reset</h2>
    <form method='post'>
    Email: <input type='text' name='email' width=300>
    <input type='submit' name='submit' value='Submit'>
    </form>";

require_once 'footer.php';
?>
