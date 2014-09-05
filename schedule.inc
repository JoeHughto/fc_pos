<?php
/**
 * @file schedule.inc
 * @brief schedule.inc is a library of functions used for displaying and editing
 *   the schedule.
 *
 * This file includes:
 * funcs.inc:
 * - Used for the config.inc include
 * - selectInputDate()
 * - checkDateNum()
 * - dateString()
 * 
 * salesreport.inc:
 * - displayList()
 * - displayTableByDay()
 * - displayTableByHour()
 * 
 * Possible Arguments:
 * SESSION:
 * - ID - The current user's member ID, allows for highlighting the
 *   the user's shifts.
 * 
 * @link http://www.worldsapartgames.org/fc/schedule.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

/**
 * selectVolunteer() echos a <select> box to the page, allowing the selection
 *   of any certified register volunteer. It is intended to be used for the
 *   non-recurring schedule.
 * @param string $name is the desired name for the <select> box.
 * @param int $default is an optional default member ID.
 * @param int $rsn is an optional backup default member ID, because of our two
 *   schedule system.
 */
function selectVolunteer($name, $default, $rsn) {
    $sql = "SELECT ID, fname, lname FROM members WHERE registerUse='1' ORDER BY lname, fname, ID";
    $cxn = open_stream();
    $result = query($cxn, $sql);

    if (($default == 0 && $rsn == 0) || ($default == -1)) {
        echo "<font color=RED>Open Shift</font><br>";
    } else {
        echo "<font color=GREEN>Assigned Shift</font><br>";
    }

    echo "<select name='$name'>";
    echo "<option value='0'>Default</option>
         <option value='-1'";

    if ($default == -1) {
        echo "selected";
    }
    echo ">OPEN SHIFT</option>\n";

    if ($rsn == 0) {
        echo "- NONE SET";
    }
    echo "</option>\n";
    while ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        if ($ID == $default) {
            echo "<option value='$ID' selected>$lname, $fname (#$ID)</option>\n";
        } else {
            echo "<option value='$ID'>$lname, $fname (#$ID)</option>\n";
        }
        if ($ID == $rsn) {
            $afname = $fname;
            $alname = $lname;
        }
    }
    echo "</select>";

    if (isset($afname)) {
        echo "<br>Default: $alname, $afname<br>";
    }

    mysqli_free_result($result);
    mysqli_close($cxn);
}

/**
 * selectSchedVolunteer() echos a <select> box to the page, allowing the selection
 *   of any certified register volunteer. It is intended to be used for the recurring
 *   schedule.
 * @param string $name is the desired name for the <select> box.
 * @param int $default is an optional default member ID.
 */
function selectSchedVolunteer($name, $default) {
    $sql = "SELECT ID, fname, lname FROM members WHERE registerUse='1' ORDER BY lname, fname, ID";
    $cxn = open_stream();
    $result = query($cxn, $sql);

    if ($default == 0) {
        echo "<b>Open Shift</b><br>";
    } else {
        echo "<font color=GREEN>Assigned Shift</font><br>";
    }


    echo "<select name='$name'>";
    echo "<option value='0'></option>\n";
    while ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        $fname = substr($fname, 0, 1);
        if ($ID == $default) {
            echo "<option value='$ID' selected>$lname, $fname (#$ID)</option>\n";
        } else {
            echo "<option value='$ID'>$lname, $fname (#$ID)</option>\n";
        }
    }
    echo "</select>";

    mysqli_free_result($result);
    mysqli_close($cxn);
}

/**
 * showVolunteer() echos a volunteer with a given ID.
 * @param int $ID is the first choice ID, if this is 0, we try to process using rsn.
 * @param int $rsn is the second choice ID, if ID is 0, we set ID to this value.
 */
function showVolunteer($ID, $rsn) {
    if ($ID > 0 || $rsn > 0) {
        $cxn = open_stream();
        $ID = ($ID > 0) ? $ID : $rsn;
        $sql = "SELECT fname, lname, ID FROM members WHERE ID=$ID";
        $result = query($cxn, $sql);
        if ($row = mysqli_fetch_assoc($result)) {
            extract($row);
            if ($ID == $_SESSION['ID']) {
                echo "<font color='000066'>";
            }
            echo "$lname, $fname (#$ID)";
            if ($ID == $_SESSION['ID']) {
                echo "</font>";
            }
            return;
        }
    }
    // if it makes it here, there is no one in this shift
    echo "<table><tr><td bgcolor=BLACK><font color=WHITE><center>OPEN SHIFT</center></td></tr></table>";
}
?>
