<?php
/**
 * Inventory.inc is the primary library for inventory related functions.
 *   It also contains two classes, specialOrder and viewreceipt.
 *
 * PHP version 5.4
 *
 * LICENSE: TBD
 *
 * @category  Library
 * @package   FriendComputer
 * @author    Michael Whitehouse
 * @author    Creidieki Crouch
 * @author    Desmond Duval
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @license   TBD
 * @version   GIT:$ID$
 * @link      http://www.worldsapartgames.org/fc/index.php
 * @since     Project has existed since time immemorial.
 */

/**
 * This file includes:
 * Nothing!
 */
$TAXRATE = .0625;

/**
 * DisplayBlankItem displays a blank item with set values.
 * @param int $count  is the ID of the item.
 * @param int $newsku is the sku of the item.
 * @return void
 */
function displayBlankItem($count, $newsku)
{
    echo "<table><tr>
        <td>Description: <input type='text' name='description[$count]' size=50 maxlength=60></td>
        <td>Department: ";
    displayDepartmentList($count, '');
    echo "</td><td>Manufacturer: ";
    displayManufacturerList($count, '');
    echo "</td></tr><tr><td>UPC: <textarea cols=35 rows=1 name='UPC[$count]'>$newsku</textarea></td>
        <td>Alt1: <input type='text' name='alternate1[$count]' size=35 maxlength=35></td>
        <td>Alt2: <input type='text' name='alternate2[$count]' size=35 maxlength=35></td></tr>
        <tr><td>Quantity Recieved: <input type='text' name='qty[$count]' size=4 maxlength=4></td>
        <td>Price: \$<input type='text' name='price[$count]' size=8 maxlength=8></td>
        <td>Taxable Item? <input type='checkbox' name='tax[$count]' value='1' checked></td></tr>
        <tr><td>Cost: \$<input type='text' name='cost[$count]' size=8 maxlength=8></td>
        <td>Total Cost: \$<input type='text' name='totalcost[$count]' size=8 maxlength=8></td>
        <td>Inventory Item? <input type='checkbox' name='inv[$count]' value='1' checked></td>
        </tr><tr><td>Percentage Cost: <input type='text' name='marcost[$count]' size=4 maxlength=4 value='".$marcost[$icount]."'><br>
        There are three options for cost. Choose one. Cost is per unit. Total cost is total for all of this item. Percentage
        is a percentage of the price of the item.
        </td></tr></table><hr>\n";
    $count++;
}

/**
 * DisplayDepartmentList prints a <select> list of departments
 *   with the name 'department[$count]'
 * @param int    $count   is the key of the POST variable you want your
 *   <select> list to push to.
 * @param string $default is the name of the desired default department.
 * @return void
 */
function displayDepartmentList($count, $default)
{
    $cxn = open_stream();
    $sql = "SELECT name FROM departments ORDER BY name";
    $result = query($cxn, $sql);

    echo "<select name='department[$count]'>
        <option value=''></option>\n";
    while ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        if ($name == $default) {
            echo "<option value='$name' selected>$name</option>\n";
        } else {
            echo "<option value='$name'>$name</option>\n";
        }
    }
    echo "</select>\n";
}

/**
 * DisplayDepartmentListScalar prints a <select> list of departments
 *   with a chosen name.
 * @param string $default is the name of the desired default department.
 * @param string $name    is the chosen name of the <select> list. This defaults
 *   to 'department' for legacy reasons.
 * @return void
 */
function displayDepartmentListScalar($default, $name='department')
{
    $cxn = open_stream();
    $sql = "SELECT name FROM departments ORDER BY name";
    $result = query($cxn, $sql);

    echo "<select name='$name'>
        <option value=''></option>\n";
    while ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        if ($name == $default) {
            echo "<option value='$name' selected>$name</option>\n";
        } else {
            echo "<option value='$name'>$name</option>\n";
        }
    }
    echo "</select>\n";
}

/**
 * DisplayExistingItem takes a query result for an item during the receiving
 *   process, and displays the item with fields for new cost, qty, price.
 * @param object $result   is a query result containing an item.
 * @param int    $newqty   is the number of that item that are being received.
 * @param float  $newcost  is the cost of the items, as of the most recent invoice.
 * @param float  $newprice is the price we are setting for the item.
 * @return boolean Returns false if no item found, and true if an item was found.
 */
function displayExistingItem($result, $newqty, $newcost, $newprice)
{
    if (is_array($result)) {
        $row = $result;
    } elseif (!$row = mysqli_fetch_assoc($result)) {
        return (false);
    }
    extract($row);

    $newcost = ($newcost == '') ? $cost : $newcost;
    $newqty = ($newqty == '') ? 0 : $newqty;
    $newprice = ($newprice == '') ? $price : $newprice;

    if ($qty == 0 && !($cost > 0)) {
        $cost = 'N/A';
    }

    echo "<table><tr>";
    if (strlen($image) > 3) {
        echo "<td rowspan=5><img src='" . $IMAGEROOT . $image . "</td>";
    }
    echo "<td colspan=3><b>Existing Item ID: $ID</b>: $description</td></tr>
        <tr><td>Dept: ";
    displayDepartmentList($ID, $department);
    echo "</td><td>Manuf: ";
    displayManufacturerList($ID, $manufacturer);
    echo "</td></tr>
        <tr><td>UPC: $UPC</td><td>Alt1: $alternate1</td><td>Alt2: $alternate2</td></tr>
        <tr><td>Current Quantity: $qty</td><td>Current Cost: $cost</td><td>";
    echo "<input type='hidden' name='oldcost[$ID]' value='$newcost'>";

    if ($inv==1) {
        echo "Inventory Item (with quantity)";
    } else {
        echo "Non-Inventory Item (quantity not recorded)";
    }

    echo "</td></tr>
        <tr><td>Quantity recieved <input type='text' size=4 maxlength=4 name='newqty[$ID]' value='$newqty'></td>
        <td>Price \$<input type='text' size=8 maxlength=8 name='price[$ID]' value='$newprice'></td>
        <td>";
    if ($tax==1) {
        echo "Taxable Item";
    } else {
        echo "Item which is Non-Taxed";
    }

    echo "</td></tr><tr>
        <td>New Unit Cost \$<input type='text' size=8 maxlength=8 name='cost[$ID]' value='$newcost'></td>
        <td>Total Cost \$<input type='text' size=8 maxlength=8 name='totalcost[$ID]' value='" . $newcost * $newqty . "'>
        </td><td>Change <b>either</b> unit cost or total cost. If you change both, only change to unit cost will count
        <input type='hidden' name='ID[$ID]' value=$ID></td></tr></table><hr>";
    return (true);
}

/**
 * DisplayManufacturerList prints a <select> list of manufacturers
 *   with the name 'manufacturer[$count]'
 * @param int    $count   is the key of the POST variable you want your
 *   <select> list to push to.
 * @param string $default is the name of the desired default manufacturer.
 * @return void
 */
function displayManufacturerList($count, $default)
{
    $cxn = open_stream();
    $sql = "SELECT name FROM manufacturers ORDER BY name";
    $result = query($cxn, $sql);

    echo "<select name='manufacturer[$count]'>
        <option value=''></option>\n";
    while ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        if ($name == $default) {
            echo "<option value='$name' selected>$name</option>\n";
        } else {
            echo "<option value='$name'>$name</option>\n";
        }
    }
    echo "</select>\n";
}

/**
 * DisplayManufacturerListScalar prints a <select> list of manufacturers
 *   with a chosen name.
 * @param string $default is the name of the desired default manufacturer.
 * @param string $name    is the chosen name of the <select> list. This defaults
 *   to 'manufacturer' for legacy reasons.
 * @return void
 */
function displayManufacturerListScalar($default, $name='manufacturer')
{
    $cxn = open_stream();
    $sql = "SELECT name FROM manufacturers ORDER BY name";
    $result = query($cxn, $sql);

    echo "<select name='$name'>
        <option value=''></option>\n";
    while ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        if ($name == $default) {
            echo "<option value='$name' selected>$name</option>\n";
        } else {
            echo "<option value='$name'>$name</option>\n";
        }
    }
    echo "</select>\n";
}

/**
 * DisplayPreorderSelect prints a <select> list of all preorders.
 * @param string $name is the chosen name for the <select> list.
 * @return void
 */
function displayPreorderSelect($name)
{
    $cxn = open_stream();
    $sql = "SELECT * FROM preorders WHERE itemID IS NULL";
    $result = query($cxn, $sql);

    echo "<select name='$name'>\n
        <option value=''>Preorder List</option>\n";
    while ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        echo "<option value='$ID'>$description MSRP: " . money($price) .
            " Prepay: " . money(($price + (($tax == 1) ? (0.05 * $price) : 0)) * .85) .
            "</option>\n";
    }
    echo "</select>";
}

/**
 * DisplayPreorderForm prints a form prepopulated with data from a preorder
 *   as a blank and includes a hidden called preorder with the preorderID
 *   so the preorder can be linked with the form's data.
 * @param int $ID is the ID of the chosen preorder, to be pre-filled in the form.
 * @return void
 */
function displayPreorderForm($ID)
{
    $cxn = open_stream();

    $sql = "SELECT * FROM preorders WHERE ID='$ID'";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_assoc($result);
    $count = 1000000;
    extract($row);

    echo "<table><tr>
        <td><b>Preorder Item</b></td></tr><tr>
        <td>Description: <input type='text' name='description[$count]' "
        . "value='$description' size=50 maxlength=60></td>
        <td>Department: ";
    displayDepartmentList($count, $department);
    echo "</td><td>Manufacturer: ";
    displayManufacturerList($count, $manufacturer);
    echo "</td></tr><tr><td>UPC: <textarea cols=35 rows=1 "
        . "name='UPC[$count]'>$newsku</textarea></td>
        <td>Alt1: <input type='text' name='alternate1[$count]'
        size=35 maxlength=35></td>
        <td>Alt2: <input type='text' name='alternate2[$count]'
        size=35 maxlength=35></td></tr>
        <tr><td>Quantity Recieved: <input type='text' name='qty[$count]'
        size=4 maxlength=4></td>
        <td>Price: \$<input type='text' name='price[$count]' "
        . "value='$price' size=8 maxlength=8></td>
        <td>Taxable Item? <input type='checkbox' name='tax[$count]' "
        . "value='1'" . (($tax == 1) ? " checked" : "") . "></td></tr>
        <tr><td>Cost: \$<input type='text' name='cost[$count]' size=8
        maxlength=8></td>
        <td>Total Cost: \$<input type='text' name='totalcost[$count]'
        size=8 maxlength=8></td>
        <td>Inventory Item? <input type='checkbox' name='inv[$count]'
        value='1' checked></td>
        </tr><tr><td>Percentage Cost: <input type='text'
        name='marcost[$count]' size=4 maxlength=4 value='".$marcost[$icount]."'><br>
        There are three options for cost. Choose one. Cost is per unit.
        Total cost is total for all of this item. Percentage
        is a percentage of the price of the item.
        </td></tr></table><hr>
        <input type='hidden' name='preorder' value='$ID'>\n";
}

/**
 * DisplaySaleItem takes a query result of an item, and prints an input box
 *   for that item's quantity.
 * @param object $result is the query result containing the item.
 * @param int    $qty    is the initial value of the qty input box.
 * @return boolean Returns false if unsuccessful, and true if successful.
 */
function displaySaleItem($result, $qty)
{
    if (!$row = mysqli_fetch_assoc($result)) {
        return (false);
    }
    extract($row);

    echo "<input type='text' name='qty[$ID]' value='$qty' size=3 maxlength=3> ($ID) $description \$$price<p>";
    return true;
}

/**
 * DisplaySpecialOrders prints a list of all special orders for a given member.
 * @param int $member is the ID of the chosen member.
 * @return void
 */
function displaySpecialOrders($member)
{
    $cxn = open_stream();

    $sql = "SELECT * FROM specialOrders WHERE custID='$member' ORDER BY price";
    $result = query($cxn, $sql);

    echo "<table><tr><td>Item</td><td>Price</td><td>Date Ordered</td></tr>";

    while ($row = mysqli_fetch_assoc($result)) {
        extract($row);

        if ($price == 0) {
            echo "<tr><td>$item</td><td>Request</td><td>$dateMade</td></tr>";
        } else {
            echo "<tr><td>$item</td><td>\$";
            printf("%01.2f", $price);
            echo "</td><td>$dateMade</td></tr>";
        }
    }
    echo "</table>";
}

/**
 * DisplayRegisterItem prints a qty form for a given item, including custom price
 *   and description labels.
 * @param int    $ID          is the ID of the chosen item.
 * @param int    $qty         is the qty of the item being purchased.
 * @param float  $price       is the current price of that item.
 * @param string $description is a brief description of the item.
 * @return void
 */
function displayRegisterItem($ID, $qty, $price, $description)
{
    echo "<input type='text' name='qty[$ID]' value='$qty' size=3 maxlength=3> ($ID) $description \$";
    printf("%01.2f", $price);
    echo "<p>";
}

/**
 * DisplayRegisterItemPrice prints a form for a given variable priced item,
 *   including qty and price inputs, and a custom description label.
 * @param int    $ID          is the ID of the chosen item.
 * @param int    $qty         is the qty of the item being purchased.
 * @param float  $price       is the current price of that item.
 * @param string $description is a brief description of the item.
 * @return void
 */
function displayRegisterItemPrice($ID, $qty, $price, $description)
{
    echo "Variable price item<br><input type='text' name='qty[$ID]' value='$qty' size=3 maxlength=3> ($ID) $description \$
        <input type='text' name='price[$ID]' value='";
    printf("%01.2f", $price);
    echo "' size=8 maxlength=8><p>";
}

/**
 * GetItemQuantity returns the current inventory quantity of a given item.
 * @param int $item is the ID of the chosen item.
 * @return int Returns the qty of the given item as it is in the system.
 */
function getItemQuantity($item)
{
    $cxn = open_stream();

    $sql = "SELECT qty FROM items WHERE ID='$item'";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_assoc($result);
    return($row['qty']);
}

/**
 * GetPreorderAssociated returns an itemID for the item associated with
 *   a given preorderID
 * @param int $ID is the chosen preorderID.
 * @return int Returns an itemID corresponding to the given preorder.
 */
function getPreorderAssociate($ID)
{
    $cxn = open_stream();

    $sql = "SELECT itemID FROM preorders WHERE ID = '$ID'";

    $result = query($cxn, $sql);
    $row = mysqli_fetch_row($result);
    $desc = $row[0];
    return ($desc);
}

/**
 * GetPreorderSpec returns a preorderID for the preorder associated with
 *   a given special order ID.
 * @param int $ID is the ID of the chosen special order.
 * @return int Returns a preorderID for the corresponding special order.
 */
function getPreorderSpec($ID)
{
    $cxn = open_stream();

    $sql = "SELECT preorderID FROM specialOrders WHERE ID = '$ID'";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_row($result);
    $id = $row[0];
    return ($id);
}

/**
 * GetPreorderName returns the name for the preorder associated with a
 *   given preorderID.
 * @param int $ID is the chosen preorderID.
 * @return string Returns the name of the chosen preorder.
 */
function getPreorderName($ID)
{
    $cxn = open_stream();

    $sql = "SELECT description FROM preorders WHERE ID = '$ID'";

    $result = query($cxn, $sql);
    $row = mysqli_fetch_row($result);
    $desc = $row[0];
    return ($desc);
}

/**
 * GetPreorderPrice returns the price for the preorder associated with a
 *   given preorderID, less a potential additional discount.
 * @param int   $ID           is the chosen preorderID.
 * @param float $addlDiscount is an optional additional discount that can be
 *   applied after the standard 15%.
 * @return float Returns the price after discount of the given preorder.
 */
function getPreorderPrice($ID, $addlDiscount=0)
{
    $cxn = open_stream();

    $sql = "SELECT price, tax FROM preorders WHERE ID = '$ID'";

    $result = query($cxn, $sql);
    $row = mysqli_fetch_assoc($result);
    extract($row);
    $taxbux = (($tax == 1) ? (0.05 * $price + .005) : 0);
    return (round((($price + $taxbux) * ((100 - 15 - $addlDiscount)/100)), 2));
}

/**
 * MemberDiscount returns the discount for a given member, and optionally
 *   prints this information to the current page.
 * @param int     $member is the chosen memberID.
 * @param boolean $print  determines whether anything is printed to the
 *   page. This variable defaults to true.
 * @return float Returns the given member's discount
 */
function memberDiscount($member, $print=true)
{
    if ($member > 0) {
        if ($memType = checkMember($member)) {
            if ($memType >= 2 && $memType <= 3) {
                $memDiscount = 10;
                if ($print) {
                    echo "Regular Members get 10% off<p>";
                }
            } elseif ($memType == 10) {
                $memDiscount = 20;
                if ($print) {
                    echo "Double Members get 20% off<p>";
                }
            } else {
                $memDiscount = 0;
                if ($print) {
                    echo "Guest Member<p>";
                }
            }

            $FCdisc = FG_discountNow($_POST['member']);
            if ($FCdisc > 0) {
                if ($print) {
                    echo "Frequent Gamer Discount from last month: $FCdisc%<p>";
                }
            }
            $memDiscount = ($FCdisc > $memDiscount) ? $FCdisc : $memDiscount;
        }
        return ($memDiscount);
    }
    if ($print) {
        echo "No member selected<p>";
    }
    return 0;
}

/**
 * RightPrice takes two potential prices, and returns the correct one in any
 *   given case.
 * @param float $price     is the original price.
 * @param float $salePrice is the sale price.
 * @return float Returns whichever price is lower, and still above 0.
 */
function rightPrice($price, $salePrice)
{
    $result = ($salePrice <> 0) ? $salePrice : $price;
    return(round($result, 2));
}

/**
 * SalePrice returns true or false depending o whether the tested value
 *   is equal to 0.
 * @param float $salePrice is the price being tested.
 * @return boolean Returns true if saleprice != 0, false if saleprice == 0.
 */
function salePrice($salePrice)
{
    return($salePrice <> 0);
}

/**
 * SpecialOrder is a class used for creating and mutating special
 *   orders in FriendComputer.
 *
 * PHP version 5.4
 *
 * LICENSE: TBD
 *
 * @category  Library
 * @package   FriendComputer
 * @author    Michael Whitehouse
 * @author    Creidieki Crouch
 * @author    Desmond Duval
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @license   TBD
 * @version   GIT:$ID$
 * @link      http://www.worldsapartgames.org/fc/index.php
 * @since     Project has existed since time immemorial.
 */
class specialOrder
{
    var $message; // the message which will be output by Friend Computer
    var $total; // total amount which has been submitted
    var $amount; // array of amounts
    var $item; // array of items
    var $other; // money submitted to account for the heck of it
    var $note; // note that goes with other
    var $count; // number of items requested/ordered

    var $lines; // the number of special order lines to show

    var $intrans; // array of prices of items picked up
    var $preintrans; // array of IDs of preorders picked up

    var $preorder; // array of preorders being ordered
    var $pickUp; // true if there is a preorder being picked up

    /**
     * SpecialOrder is the constructor for class SpecialOrder
     */
    function specialOrder()
    {
        // if SOpreorder is blank it should be removed
        if (is_array($_POST['SOpreorder'])) {
            foreach ($_POST['SOpreorder'] as $i => $j) {
                if ($j == '') {
                    unset($_POST['SOpreorder'][$i]);
                }
            }
        }

        if (count($_POST['SOpreorder']) == 0) {
            unset($_POST['SOpreorder']);
        }

        // killing preorders if requested
        if (is_array($_POST['SOkillPreorder']) && in_array(1, $_POST['SOkillPreorder'])) {
            foreach ($_POST['SOkillPreorder'] as $kill => $stuff) {
                if ($stuff == 1) {
                    echo "<p>Removed " . getPreorderName($kill) . " from order<p>\n";
                    unset($_POST['SOpreorder'][$kill]);
                }
            }
        }

        // check SOpreorderQty array in post, and if it is valid, put it into class info
        if (is_array($_POST['SOpreorderQty'])) {
            foreach ($_POST['SOpreorderQty'] as $key => $value) {
                $this->preorderQty[$key] = ($value > 1) ? $value : 1;
            }
        }

        $this->preorder = $_POST['SOpreorder'];
        if (is_array($this->preorder)) {
            $this->preorderTotal = 0;
            foreach ($this->preorder as $key => $PID) {
                $this->preorderTotal += getPreorderPrice($PID, memberDiscount($_POST['member'])) * $this->preorderQty[$key];
            }
        }


        // These datum are stored this way so that they will be there for processing and will also be there to
        // put back in as default values before the order is concluded

        // orders
        $this->amount = (is_array($_POST['SOamount'])) ? $_POST['SOamount'] : array($_POST['SOamount']);
        $this->item = (is_array($_POST['SOitem'])) ? $_POST['SOitem'] : array($_POST['SOitem']);
        $this->other = ($_POST['SOother'] > 0) ? $_POST['SOother'] : false;
        $this->note = $_POST['SOnote'];
        $this->total = round(
            ($this->preorderTotal
            + ((is_array($_POST['SOamount'])) ? array_sum($_POST['SOamount'])
            : $_POST['SOamount'])), 2
        )
            + $this->other;

        // pick ups
        $this->intrans = $_POST['SOintrans']; // checked items for completing orders
        $this->preintrans = $_POST['SOpreintrans']; // checked items for preorders completed
        $this->pochecked = $_POST['pochecked']; // pos that have been dealt with, so we don't duplicate auto adds

        // put preorders to order
        if (is_array($this->preintrans)) {
            $cxn = open_stream();
            foreach ($this->preintrans as $key => $thing) {
                if ($_POST['pochecked'][$key] != 1 && ($key > 0)) {
                    // key > 0 checks for valid data
                    $this->pickUp = true; // this variable indicates that a preorder is being picked up
                    $newkey = getPreorderSpec($key);
                    $associatedItem = getPreorderAssociate($newkey);

                    // get quantity preordered
                    $sql = "SELECT qty FROM specialOrders WHERE ID='$key'";
                    $row = queryAssoc($cxn, $sql);
                    $qty = $row['qty'];

                    $_POST['specqty'][$associatedItem] = $qty;
                    $_POST['discount'] = 15;
                }
                $this->pochecked[$key] = 1; // this variable allows us to know that this has already been dealt with

                $sql = "SELECT price FROM items WHERE ID='$associatedItem'";
                $row = queryAssoc($cxn, $sql);
                $price = $row['price'];
                $this->intrans[$key] = $price;
            }
        }


        // number of lines defaults to 2, but one more is added if the two are both used.
        $this->count = 0;
        foreach ($this->item as $it) {
            if ($it != '') {
                $this->count++;
            }
        }

        $this->lines = ($count == 0) ? 2 : $count + 1;

        // a boolean that is true if there is anything to process
        $this->toProcess = (($this->count > 0) || ($this->other > 0) || (is_array($this->preorder)));

    } // end function specialOrder

    /**
     * Process takes all field information about the current object, and
     *   attempts to submit it to the database. First the store credit is added,
     *   and if that is successful, the special order is added to the table.
     * @return boolean Returns true if successful, false if not.
     */
    function process()
    {
        $cxn = open_stream();

        $member = $_POST['member'];

        $memberName = printMemberString($member, 1);
        if ($memberName) {
            $message .= "Submitting Orders for $memberName<br>";
        } else {
            // if member is invalid
            $member = strip_tags($member);
            $this->message .= "Attempted to submit order for invalid member '$member'.<br>";
            return false;
        }

        // submit into account
        $a = array_sum($this->amount) + $this->preorderTotal;
        if ($a > 0) {
            foreach ($this->item as $name) {
                $notes .= (strlen($name) > 1) ? $name . ',' : '';
            }

            if (is_array($this->preorder)) {
                foreach ($this->preorder as $po) {
                    $notes .= getPreorderName($po) . ',';
                }
            }
            $notes = "Spec Order " . $notes;

            $stmt = $cxn->prepare("INSERT INTO storeAccount (transactionID, memberID, whenAcct, amount, notes) VALUES (?, ?, NOW(), ?, ?)");
            $stmt->bind_param("ddds", $_SESSION['transaction_ID'], $member, $a, $notes);
            if ($stmt->execute()) {
                $message .= $notes . " for $a put in Member's account<br>";
                $stmt->close();
                $accountFail = false;
            } else {
                $message .= "Failed to submit account for ". $notes . "<br>";
                $stmt->close();
                $accountFail = true;
            }
        } // end if a > 0


        // submit special order/requests
        if ($accountFail != true) {
            foreach ($this->item as $num => $it) {
                if ($this->item[$num] == '') {
                    continue;
                }

                // make sure that $a is legit but allow it to be 0 also
                $a = ($this->amount[$num] > 0) ? $this->amount[$num] : 0;

                if ($a >= 0) {

                    $stmt = $cxn->prepare("INSERT INTO specialOrders (custID, dateMade, item, price) VALUES (?, NOW(), ?, ?)");
                    $stmt->bind_param("dsd", $member, $this->item[$num], $a);
                    if ($stmt->execute()) {
                        $message .= ($a > 0) ? "Special order for " . $this->item[$num] . " submitted<br>"
                            : "Request for " .$this->item[$num] . "submitted<br>";
                    } else {
                        $message .= "Failed to submit " . $this->item[$num] . "<br>";
                    }
                } // if $a >= 0
            } // foreach item

            if (is_array($this->preorder)) {
                foreach ($this->preorder as $key => $po) {
                    if ($po > 0) {
                        $sql = "INSERT INTO specialOrders (custID, dateMade, item, price, qty)
                            VALUES ('$member',
                            NOW(),
                            '" . $cxn->escape_string(getPreorderName($po)) . "',
                            '" . getPreorderPrice($po, memberDiscount($_POST['member'])) . "',
                            '$po',
                            '" . $this->preorderQty[$key] . "')";
                        if (query($cxn, $sql)) {
                            $message .= "Special order for " . getPreorderName($po) . " submitted<br>\n";
                        } else {
                            $message .= "ERROR submitting order for " .getPreorderName($po) . "<br>\n";
                        }
                    } else {
                        displayError("ERROR, PO number is not a number. This could be the result of hacking!");
                    }
                }
            }

        } // if account not fail

        // if money is being submitted just to increase account
        if ($this->other > 0) {
            $stmt = $cxn->prepare("INSERT INTO storeAccount (memberID, whenAcct, amount, notes) VALUES (?, NOW(), ?, ?)");
            $stmt->bind_param("ids", $member, $this->other, $this->note);
            if ($stmt->execute()) {
                $notes = strip_tags($this->note);
                $message .= "Account increased by " . money($this->other) . " for #$member<br>
                    Reason: $notes";
            } else {
                $message .= "Failed to submit account increase for $member<br>
                    Error: " . $stmt->error . "<p>";
            }
            $stmt->close();
        } // else if othher


        $this->message = $message;
        return true;
    } // end process

    /**
     * Deliver processes orders which are being picked up.
     *   Sets date taken to current time, but will not allow process to occur if 
     *   more items are being picked up than are being paid for in account.
     * @param float $totalPrice is the total amount being paid.
     * @return boolean Returns true if the items are successfully marked
     *   picked up, false if unsuccessful.
     */
    function deliver($totalPrice)
    {
        // check for items which have been picked up
        if (isset($this->intrans)) {
            // check to make sure that the pick up is correct
            $total = 0;
            foreach ($this->intrans as $val) {
                $total += $val * .9;
            }
            if ($total > $totalPrice) {
                $this->message .= "<font color=RED>Cannot pick up a value of orders greater than the value being paid. Did you forget to
                    scan the item into the transaction?</font><br>
                    VAL: $val<br>
                    Total: $total<br>\n";
                return false;
            }

            $cxn = open_stream();
            foreach ($this->intrans as $ID => $num) {
                $sql = "UPDATE specialOrders SET dateTaken=NOW() WHERE ID=$ID";
                if (query($cxn, $sql)) {
                    $sql = "SELECT item FROM specialOrders WHERE ID=$ID";
                    $row = queryAssoc($cxn, $sql);
                    $item = $row['item'];
                    $this->message .= "$item picked up<br>";
                }
            }
        }
        return true;
    } // end deliver

    /**
     * DeliverSum returns sum of items being picked up.
     *   intrans is an array of prices of special orders being picked up.
     *   preintrans is an array of specialOrderIDs of preorders being picked up
     * @return float Returns the total sum of all special orders and preorders
     *   getting picked up.
     */
    function deliverSum()
    {
        return ((is_array($this->intrans)) ? array_sum($this->intrans) : 0);
    }

    /**
     * DisplayForm displays the special order form with defaults set to 
     *   existing values.
     * @return void
     */
    function displayForm()
    {
        echo "<b>Other Special</b><p>";

        echo "Enter the price prepaid for the item. Enter 0 if it is a request, not a special order.<br>\n";

        for ($i = 1; $i <= $this->lines; $i++) {
             echo "Item: <input type='text' name='SOitem[$i]' value='" . $this->item[$i] . "' size=40 maxlength=100>
                \$<input type='text' name='SOamount[$i]' value='" . moneyND($this->amount[$i]) . "'size=8 maxlength=8><br>\n";
        }

        echo "<br><b>Putting money into account by paying for it</b><br>";
        echo "\$<input type='text' name='SOother' size=8 maxlength=8 value='" . $this->other . "'>
            Reason: <input type='text' name='SOnote' size=20 maxlength=50 value='" . $this->note . "'><br>";
    }

    /**
     * DisplayOrderable displays a select element of all preorder 
     *   items which are still available
     * @return void
     */
    function displayOrderable()
    {
        $cxn = open_stream();
        $sql = "SELECT * FROM preorders WHERE orderDate >= CURDATE() ORDER BY description";
        $result = query($cxn, $sql);

        if (is_array($this->preorder)) {
            // sorts to eliminate empty spots.
            sort($this->preorder);
        }
        $count = count($this->preorder); // this allows it to set the element for the SOpreorder as the next one

        if (!($count > 0)) {
            echo "<b>Order Preorders here.</b> Other special orders are found at the bottom of the page<br>\n";
        }

        if ($count > 0) {
            echo "<b>Selected Preorders</b> Check box to remove.<br>\n";
            foreach ($this->preorder as $key => $order) {
                echo "<input type='checkbox' name='SOkillPreorder[$key]' value='1'>"
                    . getPreorderName($order) . " - Prepay Price: " . money(getPreorderPrice($order, memberDiscount($_POST['member'])))
                    . " Qty: <input name='SOpreorderQty[$key]' type='text' value='" . $this->preorderQty[$key] . "' size=3 maxlength=3>
                    <input type='hidden' name='SOpreorder[$key]' value='$order'><br>\n";
            }
        }
        echo "<select name='SOpreorder[$count]'>\n
            <option value=''>Preorder List</option>\n";
        while ($row = mysqli_fetch_assoc($result)) {
            extract($row);
            echo "<option value='$ID'>$description MSRP: " . money($price) .
                " Prepay: " . money(($price + (($tax == 1) ? (0.05 * $price) : 0)) * .85) .
                "</option>\n";
        }
        echo "</select>";
    } // end function

    /**
     * ShowOrders shows outstanding orders/requests for member
     * @return void
     */
    function showOrders()
    {
        $member = $_POST['member'];

        $cxn = open_stream();

        // preorders
        $sql = "SELECT ID, item, price, dateMade, qty
            FROM specialOrders
            WHERE custID='$member'
            AND dateTaken='0000-00-00 00:00:00'
            AND price > 0";
        $result = query($cxn, $sql);
        $first = true;
        while ($row = mysqli_fetch_assoc($result)) {
            extract($row);

            if ($first) {
                $first = false;
                echo "<p><b>Outstanding Preorders for ";
                printMember($member, 1);
                echo"<br>
                    <i>Checking these boxes <b>will</b> make an item appear. 
                    You won't need to add the item separately.</i><br>
                    <table border cellpadding=3><tr><td>In this<br>Transaction?
                    </td><td>Date Ordered</td><td>Item</td><td>Prepaid Amount</td></tr>";
            }

            $date = date_create($dateMade);
            echo "<tr><td><input type='checkbox' name='SOpreintrans[$ID]' "
                . "value='$price'";
            if (isset($this->intrans[$ID])) {
                echo "checked";
            }
            echo "></td><td>" . $date->format("D M-j-y") 
                . "</td><td>$item</td><td>" . money($price) . " x $qty = "
                . money($price * $qty) . "</td></tr>\n";
        }
        if (!$first) {
            echo "</table>";
        }

        // put hiddens in so that we know that it has already been checked if it has
        if (is_array($this->pochecked)) {
            foreach ($this->pochecked as $key=>$value) {
                if ($value == 1) {
                    echo "\n<input type='hidden' name='pochecked[$key]' value=1>";
                }
            }
        }

        // special orders
        $sql = "SELECT ID, item, price, dateMade
            FROM specialOrders
            WHERE custID='$member'
            AND dateTaken='0000-00-00 00:00:00'
            AND price > 0";
        $result = query($cxn, $sql);
        $first = true;
        while ($row = mysqli_fetch_assoc($result)) {
            extract($row);

            if ($first) {
                $first = false;
                echo "<p><b>Outstanding Special Orders for ";
                printMember($member, 1);
                echo"<br>
                    <table border cellpadding=3><tr><td>In this<br>Transaction?
                    </td><td>Date Ordered</td><td>Item</td><td>Prepaid Amount</td></tr>";
            }

            $date = date_create($dateMade);
            echo "<tr><td><input type='checkbox' name='SOintrans[$ID]' value='$price'";
            if (isset($this->intrans[$ID])) {
                echo "checked";
            }
            echo "></td><td>" . $date->format("D M-j-y") 
                . "</td><td>$item</td><td>" . money($price) . "</td></tr>\n";
        }
        if (!$first) {
            echo "</table>";
        }

        // requests
        $sql = "SELECT ID, item, price, dateMade FROM specialOrders "
            . "WHERE custID='$member' AND dateTaken='0000-00-00 00:00:00' "
            . "AND price = 0";
        $result = query($cxn, $sql);
        $first = true;
        while ($row = mysqli_fetch_assoc($result)) {
            extract($row);

            if ($first) {
                $first = false;
                echo "<p><b>Outstanding Requests for ";
                printMember($member, 1);
                echo "<br>
                    <table border cellpadding=3><tr><td>In this<br>Transaction?
                    </td><td>Date Ordered</td><td>Item</td></tr>";
            }

            $date = date_create($dateMade);
            echo "<tr><td><input type='checkbox' name='SOintrans[$ID]' value='0'";
            if (isset($this->intrans[$ID])) {
                echo "checked";
            }
            echo "></td><td>" . $date->format("D M-j-y") . "</td><td>$item</td></tr>\n";
        }
        if (!$first) {
            echo "</table>";
        }

    } // end showOrders

} // end class

/**
 * ViewReceipt is a class used for generating printable receipts.
 *
 * PHP version 5.4
 *
 * LICENSE: TBD
 *
 * @category  Library
 * @package   FriendComputer
 * @author    Michael Whitehouse
 * @author    Creidieki Crouch
 * @author    Desmond Duval
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @license   TBD
 * @version   GIT:$ID$
 * @link      http://www.worldsapartgames.org/fc/index.php
 * @since     Project has existed since time immemorial.
 */
class viewreceipt
{
    var $start;
    var $end;
    var $first;
    var $last;
    var $staff;
    var $customer;

    /**
     * ViewReceipt is the constructor for class ViewReceipt.
     */
    function viewreceipt()
    {
        // this allows the use of forms to select dates
        if (isset($_GET['startmonth'])) {
            $_GET['start'] = $_GET['startyear'] . '-' . $_GET['startmonth'] 
                . '-' . $_GET['startday'];
        }
        if (isset($_GET['endmonth'])) {
            $_GET['end'] = $_GET['endyear'] . '-' . $_GET['endmonth'] 
                . '-' . $_GET['endday'];
        }

        $this->start    
            = (checkDateNum($_GET['start']))   ? $_GET['start'] : false;
        $this->end      
            = (checkDateNum($_GET['end']))     ? $_GET['end'] : false;
        $this->first    
            = ($_GET['first'] > 0)             ? $_GET['first'] : false;
        $this->last    
            = ($_GET['last'] > $this->first)  ? $_GET['last'] : false;
        $this->staff    
            = (checkMember($_GET['staff']))    ? $_GET['staff'] : false;
        $this->customer 
            = (checkMember($_GET['customer'])) ? $_GET['customer'] : false;

        // add time so that it includes the last day
        $this->start .= " 00:00:00";
        $this->end .= "23:59:59";
    }

    /**
     * ShowList prints the current object's current list.
     * @return void
     */
    function showList()
    {
        $cxn = open_stream();
        $PAY = array (1 => 'Cash', 'Credit Card', 'Check', 'Account', 'Gift Certificate', 'Mixed');
        $PC = array (1 => '006600' , 'BLUE', '660099', 'BLACK', '666666');

        if ($this->start || $this->end) {
            if ($this->start) {
                $limits = "whensale >= '" . $this->start . "' ";
            }
            if ($this->end) {
                $limits .= (isset($limits)) ? "AND whensale <= '" . $this->end . "' " : "whensale <= '" . $this->end . "' ";
            }
        }
        if ($this->first || $this->last) {
            if ($this->first) {
                $limits .= (isset($limits)) ? "AND ID >= '" . $this->first . "' " : "ID >= '" . $this->first . "' ";
            }
            if ($this->last) {
                $limits .= (isset($limits)) ? "AND ID <= '" . $this->last . "'" : "ID <= '" . $this->last . "' ";
            }
        }

        if ($this->staff) {
            $limits .= (isset($limits)) ? "AND staffID = '" . $this->staff . "'" : "staffID '" . $this->staff . "' ";
        }
        if ($this->customer) {
            $limits .= (isset($limits)) ? "AND customerID = '" . $this->customer . "'" : "customerID '" . $this->customer . "' ";
        }

        if (isset($limits)) {
            $limits = 'WHERE ' . $limits;
        }

        $sql = "SELECT * FROM transactions $limits ORDER BY whensale DESC";
        $result = query($cxn, $sql);

        echo "<font color='RED'>Red</font> indicates that transaction is still open<br>
            Payment colors: <font color='006600'>Cash</font>, <font color='BLUE'>Check</font>,
            <font color='660099'>Credit Card</font>, <font color='BLACK'>Account</font>, <font color='666666'>Split</font><p>
            <table border><tr><td>ID</td><td>Date</td><td>Staff</td><td>Customer</td><td>Payment</td><td>Price</td><td>Tax</td>
            <td>Total</td></tr>\n";

        while ($row = mysqli_fetch_assoc($result)) {
            extract($row);

            echo "<tr><td>ID#$ID</td>";
            echo "<td>$whensale</td>
                <td>" . printMemberString($staffID, 1) . "</td>
                <td>" . printMemberString($customerID, 1) . "</td>
                <td><font color=" . $PC[$payMethod] . ">" . $PAY[$payMethod] . "</font></td>
                <td><font color=" . $PC[$payMethod] . ">" . money($totalPrice) . "</font></td>
                <td><font color=" . $PC[$payMethod] . ">" . money($tax) . "</font></td>";
            echo "<td><font color=" . $PC[$payMethod] . ">" . money($totalPrice + $tax) . "</font></td>";

            if ($closed == 1) {
                // if it is closed, then it can be viewed
                echo "<td><a href='viewreceipts.php?view=$ID'>Finished:<br>View</a></td>
                    <td><a href='viewprintablereceipt.php?ID=$ID' target='receipt'>View<br>Printable</a></td>";
            } else {
                echo "<td><a href='register.php?ID=$ID'>Reopen</a></td><td></td>";
            }
            echo "</tr>\n";
        } // end while
        echo "</table><br>";
    } // end function
} // end class

/**
 * DisplayReceipt displays a receipt for a given transactionID.
 * @global real $TAXRATE is the current tax rate, set at the top of this library.
 * @param int $recID is the ID of the chosen transaction.
 * @return void
 */
function displayReceipt($recID)
{
    $cxn = open_stream();

    $PAY = array (1 => 'Cash', 'Credit Card', 'Check', 'Account', 'Gift Certificate', 'Mixed');

    $sql = "SELECT * FROM transactions WHERE ID=$recID";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_assoc($result);
    extract($row);

    echo "<font size=+3>Receipt Reprint</font><p>
        <table border cellpadding=3><tr><td>ID</td><td>Date</td><td>Staff</td><td>Customer</td><td>Pay Method</td><td>Price</td><td>Tax</td><td>Total</td></tr>";
    echo "<td>$ID</td>
        <td>$whensale</td>
        <td>" . printMemberString($staffID, 1) . "</td>
        <td>" . printMemberString($customerID, 1) . "</td>
        <td>" . $PAY[$payMethod] . "</td>
        <td>" . money($totalPrice) . "</td>
        <td>" . money($tax) . "</td>
        <td>" . ($totalPrice + $tax) . "</td></tr></table><p>";

    $sql = "SELECT i.description name,
        s.itemID ID,
        s.price price,
        s.tax tax,
        s.qty qty
        FROM soldItem s
        JOIN items i
        ON s.itemID = i.ID
        WHERE s.transactionID='$recID'";
    $result = query($cxn, $sql);

    echo "Items sold<br>
        <table border cellpadding=3><tr><td>ID</td><td>Name</td><td>Price</td><td>Tax</td><td>Quantity</td></tr>\n";
    while ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        global $TAXRATE;
        $taxpaid = ($tax == 1) ? ($price * $TAXRATE) : 0;
        echo "<tr><td>$ID</td>
            <td>$name</td>
            <td>" . money($price) . "</td>
            <td>" . money($taxpaid) . "</td>
            <td>$qty</td></tr>";
    }
    echo "</table><br>";
}

?>
