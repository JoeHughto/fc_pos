<?php
/**
 * @file Profile.php is a page that allows the active user to modify the information
 *   on their account stored in the database.
 * 
 * PHP version 5.4
 *
 * LICENSE: TBD
 *
 * @package   FriendComputer\Mutator\MemberInfo
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @license   TBD
 * @version   GIT:$ID$
 * @link      http://www.worldsapartgames.org/fc/profile.php
 * @since     Project has existed since time immemorial.
 */

/**
 * This file includes:
 * funcs.inc:
 *   Used for the config.inc include
 *   displayErrorDie()
 *   checkName()
 *   check_email_address()
 *   extractNums()
 *   checkDate()
 *   displayError()
 *   selectInputDate()
 */
$title = "Member Profile";
$version = "1.8d";
require_once 'funcs.inc';
require_once 'header.php';

/**
 * Possible Arguments:
 * SESSION:
 *   ID - The ID of the active user, required for loading the active user's
 *     profile.
 * POST:
 *   submit - If this is set, we have work to do.
 *   newpwd1 - If newpwd1 != '' && newpwd1 == newpwd2 then
 *   newpwd2 -   change password to newpwd1!
 *   login - The member's username.
 *   fname - The member's first name.
 *   lname - The member's last name.
 *   street - The member's street address.
 *   city - The member's city.
 *   state - The member's state.
 *   ZIP - The member's zip code.
 *   email - The member's email address
 *   phone1 - The member's phone number.
 *   phone2 - The member's alternate phone number
 *   status - Presumably nothing. ???
 *   DOBDay - This is a day selection box, gets combined with
 *   DOBMonth - the month selection box and year selection  box
 *   DOBYear -    in order to build a composite date of birth.
 */

$ID = $_SESSION['ID'];

if (!$cxn = open_stream()) {
    displayErrorDie("Error #7: Unable to open datastream<br>SQL Error: " . mysqli_connect_error());
} else {
    if (isset($_POST['submit'])) {
        // check data
        extract($_POST);

        if (strlen($newpwd1) > 0) {
            if ($bad['password match'] = ($newpwd1 != $newpwd2)) {
                echo "New password must be entered the same twice.<br>";
            } else {
                $password = $newpwd1;
            }
            $bad['PASSWORD'] = !(strlen($password) > 0);
        }

        if ($bad['login'] = !checkName($login)) {
            $login = '';
        } else {
            $login = mysqli_real_escape_string($cxn, $login);
        }
        if ($bad['fname'] = !checkName($fname)) {
            $fname = '';
        } else {
            $fname = mysqli_real_escape_string($cxn, $fname);
        }
        if ($bad['lname'] = !checkName($lname)) {
            $lname = '';
        } else {
            $lname = mysqli_real_escape_string($cxn, $lname);
        }
        $street = mysqli_real_escape_string($cxn, $street);
        if ($bad['city'] = !checkName($city)) {
            $city = '';
        } else {
            $city = mysqli_real_escape_string($cxn, $city);
        }
        $state = strtoupper($state);
        if ($bad['state'] = (!ereg("^[A-Z]{0,2}$", $state))) {
            $state='';
        }
        if ($bad['ZIP'] = (!ereg("^([0-9]{0,5}-?[0-9]{0,4})|([A-Z0-9]{3} [A-Z0-9]{3})$", $ZIP))) {
            $ZIP='';
        }
        if (!check_email_address($email)) {
            $email = '';
        }
        $phone1 = extractNums($phone1);
        $phone2 = extractNums($phone2);
        //$status = (int)$status;
        if (!checkDate($DOBMonth, $DOBDay, $DOBYear)) {
            $DOBDay = 0;
            $DOBMonth = 0;
            $DOBYear = 0;
        } else {
            $DOB=$DOBYear . '-' . $DOBMonth . '-' . $DOBDay;
        }

        // if its all good, ram it in there
        if (!in_array(true, $bad)) {
            if (strlen($password) > 0) {
                // password is checked above and only set if valid
                // this will be added only if there is a password change
                $pwdsql = ",password='" . hash('sha256', $password) . "'";
            }
            $stmt = $cxn->prepare(
                "UPDATE members
                SET login=?,
                fname=?,
                lname=?,
                street=?,
                city=?,
                state=?,
                ZIP=?,
                email=?,
                phone1=?,
                phone2=?,
                DOB=?
                $pwdsql
                WHERE ID='$ID'"
            );
            $stmt->bind_param("sssssssssss", $login, $fname, $lname, $street, $city, $state, $ZIP, $email, $phone1, $phone2, $DOB);
            if ($stmt->execute()) {
                echo "Information Updated Successfully<p>\n";
            } else {
                displayError("Error updating information");
            }
        } else {
            echo "There are errors in your submission<p>";
            foreach ($bad as $key => $value) {
                if ($value) {
                    echo "Invalid $key<br>";
                }
            }
            echo "Please make necessary corrections and try again.<hr>";
        }
    }

    $sql = "SELECT * FROM members WHERE ID='$ID'";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_assoc($result);
    extract($row);
    $DOBYear = intval(substr($DOB, 0, 4));
    $DOBMonth = intval(substr($DOB, 5, 2));
    $DOBDay = intval(substr($DOB, 8, 2));
} // if stream opens

// display form
echo "Update Your Information<p>
    <form action='profile.php' method='post'>";

echo "<table>
    <tr><td>Login:</td><td><input type='text' name='login' value='$login' size=25 maxlength=25></td></tr>";
echo "<tr><td>New Password:</td><td><input type='password' name='newpwd1' size=25 maxlength=100></td></tr>
    <tr><td>New Password:</td><td><input type='password' name='newpwd2' size=25 maxlength=100></td></tr>
    <tr><td>First Name:</td><td><input type='text' name='fname' value='$fname' size=25 maxlength=25></td></tr>
    <tr><td>Last Name:</td><td><input type='text' name='lname' value='$lname' size=25 maxlength=25></td></tr>
    <tr><td>Street Address:</td><td><input type='text' name='street' value='$street' size=50 maxlength=50></td></tr>
    <tr><td>City:</td><td><input type='text' name='city' value='$city' size=25 maxlength=25></td></tr>
    <tr><td>State:</td><td><input type='text' name='state' value='$state' size=2 maxlength=2></td></tr>
    <tr><td>ZIP:</td><td><input type='text' name='ZIP' value='$ZIP' size=12 maxlength=12></td></tr>
    <tr><td>Email:</td><td><input type='text' name='email' value='$email' size=50 maxlength=255</td></tr>
    <tr><td>Phone:</td><td><input type='text' name='phone1' value='$phone1' size=15 maxlength=20></td></tr>
    <tr><td>Alt Phone:</td><td><input type='text' name='phone2' value='$phone2' size=15 maxlength=20></td></tr>
    <tr><td>Date of Birth:</td><td>";
selectInputDate('DOBMonth', 'DOBDay', 'DOBYear', 1910, 2004, $DOBMonth, $DOBDay, $DOBYear);
echo "<br><input type='submit' name='submit' value='submit'>
    </td></tr></table></form>\n";
require 'footer.php';
?>
