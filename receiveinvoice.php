<?php
/**
 * @file receiveinvoice.php
 * @brief receiveinvoice.php is a page used to receive orders into the system, adding
 *   them to the inventory, and changing prices and costs as needed.
 * 
 * This file includes:
 * funcs.inc:
 * - Used for the config.inc include
 * - displayErrorDie()
 * - checkName()
 * - extractNums()
 * - displayError()
 * 
 * inventory.inc:
 * - displayExistingItem()
 * - displayBlankItem()
 * - displayDepartmentList()
 * - displayManufacturerList()
 * 
 * Possible Arguments:
 * SESSION:
 * - inv - Used to determine whether the active user has inventory
 *   privs.
 * - ID - The ID of the active user, required for recording the user
 *   receiving the invoice.
 * - invEvent_ID - This is an inventory event ID, used on this page to 
 *   determine if the active user has already started receiving an invoice
 *   or not.
 * 
 * POST:
 * - IEID - The page may provide an inventory event ID to be loaded, if
 *   one has already been started.
 * - submit - If this variable is set, it means we have skus to add to the
 *   invoice.
 * - close - If this variable is set, we need to check the invoice to make sure
 *   everything looks safe, and close it.
 * - sku - This is a string of skus, or search strings, separated by newlines, 
 *   to be read into the page as the items to be checked into the system.
 * - splitID - In the event of two items sharing a sku, this array is used to
 *   allow the active user to specify which item they meant when they entered
 *   the shared sku.
 * - price - This array will be filled with both new and old items, with old
 *   items at the index of their itemID, and new items at extremely high
 *   indexes.
 * - cost - This array will be filled with both new and old items.
 * - totalcost - This array may be filled as an alternative to filling the same
 *   index of cost[], the value that should fill cost will be calculated at
 *   runtime.
 * - newqty - This array is filled with the quantity that we are entering into
 *   the system for existing items only. These numbers will be added to 
 *   existing quantities when put into the database.
 * - description - This array of strings will populate the description field
 *   of new items.
 * - department - This array of department IDs will populate the department
 *   field of new items.
 * - manufacturer - This array of manufacturer IDs will populate the manufacturer
 *   field of new items.
 * - UPC - This is an array of skus to be set for new items.
 * - alternate1 - alternate1 and alternate2 are space for setting.
 * - alternate2 -   alternative skus for items with more than one sku.
 * - qty - This is an array of integers to be set as the quantities for new items.
 * - inv - This array of boolean values will set whether new items require
 *   tracking of inventory at all.
 * 
 * GET:
 * - ID - The page's URL may provide an inventory event ID to be loaded, if
 *   one has already been started. This variable is immediately injected into
 *   POST['IEID'], for further processing.
 * 
 * @link http://www.worldsapartgames.org/fc/receiveinvoice.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

$title='Recieve Invoice';
$version = "1.8d";
require_once 'funcs.inc';
require_once 'inventory.inc';
require_once 'header.php';

if ($_SESSION['inv'] != 1) {
    echo "You must have Inventory Permissions to recieve and invoice. 
        If you believe you have recieved this in error, please
        contact the General Manager or Quartermaster.<p>";
    include 'footer.php';
    exit();
}

echo "<form action='receiveinvoice.php' method='post'><br>"; // start the form since everything's in it

if (!$cxn = open_stream()) {
    displayErrorDie(
        "Error #3: error getting invEvent_ID<br>
        SQL query: $sql<br>
        SQL Error: " . mysqli_connect_error()
    );
}

// if an invEvent_ID was provided, use it
if (isset($_GET['ID']) || isset($_POST['IEID'])) {
    $ID = (isset($_GET['ID'])) ? $_GET['ID'] : $_POST['IEID'];
    $sql = "SELECT staffID, closed FROM invEvent WHERE ID='$ID'";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_assoc($result);
    if ($row['closed'] == 0 && $row['staffID'] == $_SESSION['ID']) {
        $_SESSION['invEvent_ID'] = $ID;
    } else {
        if ($row['closed'] != 0) {
            echo "Inventory Event #$ID already closed<br>";
        }
        if ($row['staffID'] != $_SESSION['ID']) {
            echo "Only original user may update inventory event<br>";
        }
    }
}

// check to see if there is already an active Inventory Event in progress 
// in this session. If not, assign one
if (!($_SESSION['invEvent_ID'] > 0)) {
    $sql = "INSERT INTO invEvent
        (staffID, closed)
        VALUES
        (" . $_SESSION['ID'] . ", 0)";
    if (mysqli_query($cxn, $sql)) {
        $sql = "SELECT ID
            FROM invEvent
            WHERE ID=(SELECT MAX(ID) FROM invEvent)
            AND staffID=" . $_SESSION['ID'];
        if ($result = mysqli_query($cxn, $sql)) {
            $row = mysqli_fetch_assoc($result);
            $_SESSION['invEvent_ID'] = $row['ID'];
        } else {
            displayErrorDie(
                "Error #3: error getting invEvent_ID<br>
                SQL query: $sql<br>
                SQL Error: " . mysqli_error($cxn)
            );
        }
    } else {
        displayErrorDie(
            "Error #3: error getting invEvent_ID<br>
            SQL query: $sql<br>
            SQL Error: " . mysqli_error($cxn)
        );
    }
}

// Setting the the Inventory Event ID
$IEID = $_SESSION['invEvent_ID'];
echo "Inventory Event ID: " . $IEID . "<p>\n";

// only do all this stuff if there is actually submitted data
if (($_POST['submit'] == 'submit') || ($_POST['submit'] == 'close') 
    || ($_POST['close'] == 1)
) {

    // take the list from the textarea and turn it into a useful array
    if (strlen($_POST['sku']) > 0) {
        $skuList = explode("\n", trim($_POST['sku']));
        if (end($skuList) == '') {
            array_pop($skuList);
        } // remove a trailing empty sku
        echo "<p>SkuList: ";
    }

    // add all the duplicate IDs to the sku list so they will be displayed
    // meaning skus for which there are multiple items
    if (is_array($_POST['splitID'])) {
        foreach ($_POST['splitID'] as $thisID) {
            array_push($skuList, $thisID);
        }
    }

    // if there are skus to be processed, we process them and display the data
    // this means if they are new we ask for all the data
    // they are already existing items, we ask for price, quantity and cost
    if (is_array($skuList)) {
        $first = true; // if this is true and affected is >0, then it will display the header

        $newItems = array();
        foreach ($skuList as $s) {
            // comma separated data
            // Format: UPC, qty, price, totalcost, description, tax, inv, dept, manu
            $firstComma = true; // true if we haven't deal with a CSV yet.
            if (strpos($s, ',')) {
                if ($firstComma) {
                    $endK = (is_array($_POST['description'])) 
                        ? endKey($_POST['description']) : 0;
                    $count = ($count<1000000) ? (($endK >= 1000000)
                        ? ($endK + 1)
                        : 1000000)
                        : $count;
                    $firstComma = false;
                }
                $cv = explode(",", $s);

                $UPC[$count] = $cv[0];
                $qty[$count] = $cv[1];
                $price[$count] = $cv[2];
                $totalcost[$count] = $cv[3];
                $description[$count] = $cv[4];
                $tax[$count] = $cv[5];
                $inv[$count] = $cv[6];
                $department[$count] = $cv[7];
                $manufacturer[$count] = $cv[8];
                $count++;
                continue;
            }

            $s = trim($s); 
            $cs = $cxn->real_escape_string($s);
            $sql = "SELECT * FROM items WHERE UPC='$cs' OR alternate1='$cs' "
                . "OR alternate2='$cs' OR ID='$cs' OR description LIKE '%$cs%'";
            if (!($result = mysqli_query($cxn, $sql))) {
                displayErrorDie(
                    "Error #2: Query: $sql<br>Query error: " 
                    . mysqli_error($cxn)
                );
            }
            $affected = mysqli_affected_rows($cxn);

            // if the item does not already exist, put it into the newItems array so it can be entered
            if ($affected == 0) {
                array_push($newItems, $s);
            } elseif ($affected == 1) {
                // if there is exactly one match
                // check to see if that item is already on the invoice
                // if it is, add one to qty
                $row = mysqli_fetch_assoc($result);
                if (is_array($_POST['ID']) && array_key_exists($row['ID'], $_POST['ID'])) {
                    $thisID = $row['ID'];
                    $_POST['newqty'][$thisID]++; 
                    // if they enter it again, they might want it again

                    // This is here because changing the quantity will prompt 
                    // the app to keep the totalcost the same later on
                    // which is a bad thing
                    $_POST['totalcost'][$thisID] 
                        = $_POST['newqty'][$thisID] * $_POST['cost'][$thisID];
                    continue;
                }

                if ($first) {
                    echo "<h2>Items just entered as skus that need your "
                        . "loving attention</h2><hr>\n";
                    $first = false;
                }
                $_POST['ID'][$row['ID']] = $_POST['ID']; 
                // set the POST so that it can't come up again
                if (!displayExistingItem($row, '', '', '')) {
                    echo "Error: Unable to display Item<p>";
                }
            } elseif (($some = mysqli_affected_rows($cxn)) > 1) {
                // multiple items can use the same sku because sometimes 
                // companies screw up like that, so we can deal with it
                // this displays each of the items with that sku and lets 
                // the user pick which one and enter the info at the same time
                if ($first) {
                    echo "<h2>Items just entered as skus that need your "
                        . "loving attention</h2><hr>\n";
                    $first = false;
                }

                echo "<b>You have $some items with the same sku of $s</b><br>
                    Check the ones you would like to use and enter quantity, 
                    cost, and price<br>Be aware that changes to price and 
                    cost will have no effect if box is not checked<p>\n";
                while ($row = mysqli_fetch_assoc($result)) {
                    extract($row);
                    echo "<input type='checkbox' name='splitID' value='$ID'> "
                        . "<b>$ID</b>: $description<br>
                        Quantity Recieved <input type='text' name='newqty[$ID]'
                        size=4 maxlength=4>
                        Price \$<input type='text' name='price[$ID]' "
                        . "value='$price' size=8 maxlength=8>
                        Cost \$<input type='text' name='cost[$ID]' "
                        . "value='$cost' size=8 maxlengtg=8><p>";
                }
            }
        }

        // now we display the new items for entering
        if (is_array($newItems)) {
            echo "<h2>New items that are new to the store and need data</h2>\n";

            $count = ($count<1000000) ? 1000000 : $count;
            // we set count high so that it will not overlap real IDs and it will be identifibable when it is processed
            foreach ($newItems as $newsku) {
                displayBlankItem($count++, $newsku);
            }
        }
    }

    // display blank item forms for new items requested
    $blanks = ($_POST['blanks'] > 0) ? $_POST['blanks'] : 1;
    if ($blanks > 0) {
        if ($count < 1000000) {
            $count = 1000000;
            // if count was not set above, set it here
        } 
        echo "Blanks: $blanks<br>
            You may notice that UPC has two lines. This is so that you can use 
            the bar code scanner which automatically
            adds a carriage return. You may still use tab to move into and 
            out of that field as usual.";
        for ($i = 0; $i < $blanks; $i++, $count++) {
            displayBlankItem($count, '');
        }
    }

    // PROCESSING BLANKS
    // next look at new items just entered
    extract($_POST);

    if (isset($description[1000000])) {
        echo "<h2>Items which you just entered</h2>";
    }

    // set the counter to the same thing that it starts at for when we put new ones in
    for ($icount = 1000000; isset($description[$icount]); $icount++) {
        if (strlen($description[$icount]) == 0) {
            continue;
        }

        // for each one, we will check the data and if it's good, we submit it. 
        // If not, we change it to %ERROR% and redisplay it
        if ($bad['description'] = ereg("[0-9]{12}", $description[$icount])) {
            $description[$icount] = '';
        } else {
            $description[$icount] 
                = mysqli_real_escape_string($cxn, $description[$icount]);
        }
        if ($bad['manufacturer'] = !checkName($manufacturer[$icount])) {
            $manufacturer[$icount] = '';
        } else {
            $manufacturer[$icount] 
                = mysqli_real_escape_string($cxn, $manufacturer[$icount]);
        }
        if ($bad['department'] = !checkName($department[$icount])) {
            $department[$icount] = '';
        } else {
            $department[$icount] 
                = mysqli_real_escape_string($cxn, $department[$icount]);
        }
        $UPC[$icount] = extractNums($UPC[$icount]);
        if ($bad['alternate1'] = !checkName($alternate1[$icount])) {
            $alternate1[$icount] = "ERROR%";
        } else {
            $alternate1[$icount] 
                = mysqli_real_escape_string($cxn, $alternate1[$icount]);
        }
        if ($bad['alternate2'] = !checkName($alternate2[$icount])) {
            $alternate2[$count] = "ERROR%";
        } else {
            $alternate2[$icount] 
                = mysqli_real_escape_string($cxn, $alternate2[$icount]);
        }
        if ($bad['qty'] = !(($qty[$icount]) >= 0)) {
            $qty[$icount] = "ERROR";
        }
        if ($bad['price'] = !(($price[$icount]) >= 0)) {
            $price[$icount] = "ERROR";
        }
        if ($marcost[$icount] > 0) {
            $cost[$icount] = $price[$icount] * ($marcost[$icount] / 100);
        }
        if ($cost[$icount] > 0) {
            $totalcost[$icount] = $cost[$icount] * $qty[$icount];
        } else {
            $cost[$icount] = $totalcost[$icount] / $qty[$icount];
        }

        // check to see if that description already exists, if so, it's no good
        if ($bad['description'] != true) {
            $sql = "SELECT ID FROM items WHERE description='" 
                . $description[$icount] . "'";
            if (!$result = mysqli_query($cxn, $sql)) {
                displayErrorDie(
                    "Error #5: Unable to check description for dups<br>"
                    . "Query: $sql<br>Error: " . mysqli_error($cxn)
                );
            }
            if (mysqli_affected_rows($cxn) > 0) {
                $bad['description'] = true;
                $description[$icount] = '%DUPLICATE: ' . $description[$icount];
            }
        }

        if (!in_array(true, $bad)) {
            // if nothing is bad then we can submit the info
            $inv[$icount] = ($inv[$icount]) ? 1 : 0; // SQL doesn't like 'true'
            $tax[$icount] = ($tax[$icount]) ? 1 : 0;

            $sql = "INSERT INTO items
                (department, manufacturer, price, cost, inv, UPC, alternate1, 
                alternate2, qty, description, tax)
                VALUES
                ('" . $department[$icount] .
                "', '" . $manufacturer[$icount] .
                "', '" . $price[$icount] .
                "', '" . $cost[$icount] .
                "', '" . $inv[$icount] .
                "', '" . $UPC[$icount] .
                "', '" . $alternate1[$icount] .
                "', '" . $alternate2[$icount] .
                "', '0
                 ', '" . $description[$icount] .
                "', '" . $tax[$icount] . "')";
            if (!mysqli_query($cxn, $sql)) {
                displayError(
                    "Error #4: Failure to insert new item<br>
                    SQL query: $sql<br>
                    SQL Error: " . mysqli_error($cxn)
                );
            }

            // getting the ID for the new item
            $sql = "SELECT * FROM items WHERE description='" 
                . $description[$icount] . "'";
            $result = query($cxn, $sql);
            $row = mysqli_fetch_assoc($result);
            $thisID = $row['ID'];
            $_POST['ID'][$thisID] = $thisID;

            // then we create the itemChange for it
            // First we check to make sure this is not already there. 
            // It shouldn't be, but it happens sometimes
            $sql = "SELECT ID FROM itemChange WHERE itemID='$thisID' "
                . "AND invEventID='$IEID'";
            $result = query($cxn, $sql);
            if (mysqli_affected_rows($cxn) == 0) {
                $sql = "INSERT INTO itemChange
                    (itemID, invEventID, qty, cost, price)
                    VALUES
                    ('$thisID', '" . $_SESSION['invEvent_ID'] . "', '" 
                    . $qty[$icount] ."', '" . $cost[$icount] . "', '" 
                    . $price[$icount] . "')";
                query($cxn, $sql);
            } else {
                $row = mysqli_fetch_assoc($result);
                $errID = $row['ID'];
                Echo "<font color=RED>Error entering Item Change for <b>" 
                    . $description[$icount] . "($icount). It already exists.
                    Perhaps you hit the refresh button?<br>
                    Item Change ID: $errID</font><p>";
            }

            // Now to echo it back to be seen and possibly edited in price, quantity and cost
            echo "<table><tr>
                <td><input type='hidden' name='ID[$thisID]' value='$thisID'>
                <b>ID# $thisID</b> ". $row['description']."</td><td>Dept: "
                . $row['department']."</td><td>Manuf: "
                . $row['manufacturer']."</td></tr>
                <tr><td>UPC: ". $row['UPC']."</td><td>Alt1: "
                . $row['alternate1']."</td><td>Alt2: "
                . $row['alternate2']."</td></tr>
                <tr><td>Current Quantity: ". $row['qty']."</td><td colspan=2>";

            if ($inv[$icount] == 1) {
                echo "Inventory Item (with quantity)";
            } else {
                echo "Non-Inventory Item (quantity not recorded)";
            }

            echo "</td></tr>
                <tr><td>Quantity recieved <input type='text' size=4 maxlength=4 
                name='newqty[$thisID]' value='" . $qty[$icount] . "'></td>
                <td>Price \$<input type='text' size=8 maxlength=8 
                name='price[$thisID]' value='".$row['price']."'></td></tr><tr>
                <td>Unit Cost \$<input type='text' size=8 maxlength=8 
                name='cost[$thisID]' value='".$row['cost']."'></td>
                <td>Total Cost \$<input type='text' size=8 maxlength=8 
                name='totalcost[$thisID]' value='" 
                . ($cost[$icount] * $qty[$icount]) . "'>
                </td><td>Change <b>either</b> unit cost or total cost. 
                If you change both, only change to unit cost will 
                count</td></tr>
                <tr><td colspan=3><input type='checkbox' name='bomb[$thisID]' 
                    value='somebody'> Set Up The Bomb (Delete this item)</td></tr>
                </table><hr>";
        } else {
            /**
             * @todo When this triggers, it does not allow further updating of
             *   the item in question. Find out why and fix.
             */
            echo "<table><tr><td colspan=4><font color=RED>Entry Error, 
                please fix errors</font></td></tr><tr>
                <td>Description: <input type='text' name='description[$icount]' "
                . "size=50 maxlength=50 value='".$description[$icount]."'></td>
                <td>Department: ";
            displayDepartmentList($thisID, $department[$icount]);
            echo "<br>Manufacturer: ";
            displayManufacturerList($thisID, $manufacturer[$icount]);
            echo "</td></tr><tr><td>UPC: <input type='text' name='UPC[$icount]' "
                . "size=35 maxlength=35 value='".$UPC[$icount]."'></td>
                <td>Alt1: <input type='text' name='alternate1[$icount]' size=35 "
                . "maxlength=35 value='".$alternate1[$icount]."'></td>
                <td>Alt2: <input type='text' name='alternate2[$icount]' size=35 "
                . "maxlength=35 value='".$alternate2[$icount]."'></td></tr>
                <tr><td>Quantity Recieved: <input type='text' name='qty[$icount]' "
                . "size=4 maxlength=4 value='".$qty[$icount]."'></td>
                <td>Price: \$<input type='text' name='price[$icount]' size=8 "
                . "maxlength=8 value='".$price[$icount]."'></td></tr>
                <tr><td>Cost: \$<input type='text' name='cost[$icount]' size=8 "
                . "maxlength=8 value='".$cost[$icount]."'></td>
                <td>Total Cost: \$<input type='text' name='totalcost[$icount] "
                . "size=8 maxlength=8 value='" . $cost[$icount] * $qty[$icount]
                . "'></td>
                <td>Inventory Item? <input type='checkbox' name='inv[$icount]' "
                . "value='1'";
            if ($inv[$icount]) {
                echo " checked";
            }
            echo "></td></tr>
                <tr><td>Percentage Cost: <input type='text' 
                name='marcost[$icount]' size=4 maxlength=4 value='" 
                . $marcost[$icount]."'></td></tr>
                This will overwrite any other cost put in for this item and 
                will make the cost this percentage of the price.
                </td></tr></table><hr>\n";

            unset($bad); // so that subsequent items don't show up as bad
            $entryError = true; 
            // this will be checked later to see if the invoice can be closed
        }
    }

    // This section looks at items which are not new but which may have
    // had quantity, price, or cost changed.
    // if quantity is not positive, the item is disgarded from the list
    $first = true;
    if (is_array($newqty)) {
        foreach ($newqty as $thisID => $q) {
            if ($bomb[$thisID] == "somebody") {
                $sql = "DELETE FROM items WHERE ID=$thisID";
                if (query($cxn, $sql)) {
                    echo "Item Number $thisID Deleted<br>";
                }
                $sql = "DELETE FROM itemChange WHERE itemID=$thisID";
                if (query($cxn, $sql) && $cxn->affected_rows > 0) {
                    echo "Item Change deleted<p>";
                }
                continue;
            }

            // the first time through, we need a header,
            // but only the first time and only if we come in at all
            if ($first) {
                echo "<h2>Items for which you may have edited price, quantity and/or cost</h2>";
                $first = false;
            }

            // look at new DEPARTMENTS and MANUFACTURERS for EXISTING items

            if ((checkName($manufacturer[$thisID]) && checkName($department[$thisID]))
                && (isset($manufacturer[$thisID]) && isset($department[$thisID]))
            ) {
                $diffman = false;
                $diffdep = false;
                $sql = "SELECT manufacturer, department FROM items WHERE ID='$thisID'";
                $result = query($cxn, $sql);
                $row = mysqli_fetch_assoc($result);
                $sql = "UPDATE items SET ";
                if ($manufacturer[$thisID] != $row['manufacturer']) {
                    $diffman = true;
                    $sql .= "manufacturer = '" . $manufacturer[$thisID] . "'";
                }
                if ($department[$thisID] != $row['department']) {
                    $diffdep = true;
                    if ($diffman) {
                        $sql .= ',';
                    }
                    $sql .= "department = '" . $department[$thisID] . "'";
                }
                $sql .= " WHERE ID='$thisID'";

                if ($diffman || $diffdep) {
                    if (query($cxn, $sql)) {
                        if ($diffman) {
                            echo "Manufacturer changed to " . $manufacturer[$thisID] . "<br>\n";
                        }
                        if ($diffdep) {
                            echo "Department changed to " . $department[$thisID] . "<br>]n";
                        }
                    }
                }
            }
            $q = floatval($q);
            if ($q > 0) {
                if ($marcost[$thisID] > 0) {
                    $cost[$thisID] = $price[$thisID] * ($marcost[$thisID] / 100);
                }

                // make sure that the inputs are proper
                if (($cost[$thisID] > 0 || $cost[$thisID] == '')
                    && ($totalcost[$thisID] > 0 || $totalcost[$thisID] == '')
                    && ($price[$thisID] > 0 || $price[$thisID] == '')
                    && (is_int($finqty[$thisID]) || $finqty[$thisID] == '')
                ) {
                    // if both cost and total cost are change, the cost counts, not totalcost
                    if (($totalcost[$thisID]  != ($oldcost[$thisID] * $newqty[$thisID])) 
                        && (($cost[$thisID] == $oldcost[$thisID]) || ($cost[$thisID] == 0))
                    ) {
                        $cost[$thisID] = $totalcost[$thisID] / $newqty[$thisID];
                    }
                }
                $sql = "SELECT ID FROM itemChange WHERE itemID='" . $ID[$thisID] 
                    . "' AND invEventID='" . $_SESSION['invEvent_ID'] . "'";
                if (mysqli_query($cxn, $sql)) {
                    // make the array elements easily usable vars.
                    // q was already done above
                    $c = $cost[$thisID];
                    $p = $price[$thisID];
                    $fq = $finqty[$thisID];

                    // see if the itemChange already exists
                    if (mysqli_affected_rows($cxn) == 0) { 
                        // if we have to make a new itemChange
                        $sql = "INSERT INTO itemChange
                            (itemID, invEventID, qty, cost, price)
                            VALUES
                            ('$thisID', '$IEID', '$q', '$c', '$p')";
                        if (!mysqli_query($cxn, $sql)) {
                            displayError(
                                "Error #5, Query Error<br>"
                                . "Query: $sql<br>Error: " . mysqli_error($cxn)
                            );
                        }
                    } else { // if there already is such an itemChange
                        $sql = "UPDATE itemChange
                            SET qty='$q',
                            cost='$c',
                            price='$p'
                            WHERE itemID='$thisID'
                            AND invEventID='$IEID'";
                        if (!mysqli_query($cxn, $sql)) {
                            displayError(
                                "Error #5, Query Error<br>"
                                . "Query: $sql<br>Error: " . mysqli_error($cxn)
                            );
                        }
                    }
                } else {
                    displayError(
                        "Error #5, Query Error<br>"
                        . "Query: $sql<br>Error: " . mysqli_error($cxn)
                    );
                }

                // then we display it
                $sql = "SELECT * FROM items WHERE ID='$thisID'";
                if ($result = mysqli_query($cxn, $sql)) {
                    displayExistingItem($result, $q, $c, $p);
                } else {
                    displayError(
                        "Error #5, Query Error<br>"
                        . "Query: $sql<br>Error: " . mysqli_error($cxn)
                    );
                }
            } else {
                // if qty = 0, cancel the changes for this item
                $sql = "DELETE FROM itemChange WHERE itemID='$thisID' "
                    . "AND InvEventID='$IEID'";
                if (query($cxn, $sql)) {
                    echo "<b>Item #$thisID removed from invoice</b><p>";
                }
            }
        }
    }
}

// look at all the change events in the database that have 
// not been looked at and display them
// there should be an $ID[x] for each item we have done something to
// so we can display the rest of the change events 
// that there is not an $ID[x] for
$sql = "SELECT * FROM itemChange WHERE invEventID='$IEID'";
if (!$result = query($cxn, $sql)) {
    displayError(
        "Error #9: Unable to open Item Changes<br>"
        . "Query: $sql<br>SQL Error: " . mysqli_error($cxn)
    );
} else {
    $first = true;
    while ($row = mysqli_fetch_assoc($result)) {
        // this prevents the warning of an empty array if there is no POST
        // we don't want to display it twice if it was also altered above
        if (($_POST['submit'] != 'submit' 
            && $_POST['close'] != '1')
            || !array_key_exists($row['itemID'], $cost)
        ) {
            $sql = "SELECT * FROM items WHERE ID='" . $row['itemID'] . "'";
            if ($itemResult = query($cxn, $sql)) {
                if ($first == true) {
                    echo "<h2>Items which are recorded in the pending order"
                        . "</h2><p>\n";
                    $first = false;
                }
                displayExistingItem(
                    $itemResult, $row['qty'], 
                    $row['cost'], $row['price']
                );
            }
        }
    }
}

// There are two buttons that the user could use on the form. 
// One submits for processing, the other closes the invoice
// If the one that closes the invoice is used, 
// we shall close the form if there are no errors.

// if the close invoice button was pressed, 
// we close the invoice if there are no errors
if ($_POST['close'] == 1) {
    if ($entryError) {
        echo "<b>Unable to close invoice, errors still exist</b>";
    } else {
        // get all inventory changes
        $sql = "SELECT * FROM itemChange WHERE invEventID='$IEID'";
        $result = query($cxn, $sql);
        $goodquery = true; // this becomes false if a query fails
        while (($row = mysqli_fetch_assoc($result)) && $goodquery) {
            extract($row);

            $sql = "UPDATE items
                SET price='$price',
                cost=(((cost * qty) + ($cost  * $qty))/(qty + $qty)),
                qty=(qty + $qty)
                WHERE ID='$itemID'";
            $goodquery = query($cxn, $sql);
        }
        // close invoice
        if ($goodquery) {
            $sql = "UPDATE invEvent SET closed='1', invDate=DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE ID='$IEID'";
            query($cxn, $sql);
            $_SESSION['invEvent_ID'] = 0;
            echo "<b>Inventory Event #$IEID CLOSED</b>
                <a href='receiveinvoice.php'>Click here for another invoice</a>.<br>";
            include 'footer.php';
            exit();
        }
    }
}

// display the box for entering skus
echo "<p>Enter UPCs and skus in this box:<br>
    <textarea cols=20 rows=20 name='sku'></textarea><br>
    Friend Computer, please give me <input type='text' name='blanks' 
    size=2 maxlength=2> blank item forms.<br>
    <input type='submit' name='submit' value='submit'>";
if (!$entryError) {
    // if there are no errors, the invoice can be closed
    echo "<button type='submit' name='close' value='1'>Close Invoice</button><p>";
}
require 'footer.php';
?>
