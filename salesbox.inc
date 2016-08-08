<?php
/**
 * @file salesbox.inc
 * @brief salesbox.inc prints the quicksale boxes in the register.php page
 * 
 * It uses functions that were included in register.php
 * 
 * This file uses, but does not include:
 * funcs.inc:
 * - selectMember()
 * 
 * giftcert.inc:
 * - GiftCert::displaySelectForm()
 * - GiftCert::displayForm()
 * 
 * credits.inc:
 * - CreditSpending::displayCheckBoxes()
 * 
 * @link http://www.worldsapartgames.org/fc/register.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

echo "<hr>
    <table><tr><td valign=top rowspan=2>";
echo "<button name='submit' value='submit'>Update Order</button>
    <button name='close' value='1'>Conclude Order</button><p>";

echo "Member: ";
selectMember('member', $member);
echo "<br>Discount: <input type='text' name='discount' value='$discount' maxlength=9 size=4>%<br>
    Payment Type:
    <select name='pay'>\n";

// sets to account if a pickup is being made.
$payment = ($specOrder->pickUp == true) ? 4 : $_POST['pay'];

echo "<option value=0>Select Payment Type</option>\n";
echo ($payment == 1) ? "<option value=1 selected>Cash</option>\n" : "<option value=1>Cash</option>\n";
echo ($payment == 2) ? "<option value=2 selected>Credit Card</option>\n" : "<option value=2>Credit Card</option>\n";
echo ($payment == 3) ? "<option value=3 selected>Check</option>\n" : "<option value=3>Check</option>\n";
echo ($payment == 4) ? "<option value=4 selected>Account</option>\n" : "<option value=4>Account</option>\n";
echo ($payment == 5) ? "<option value=5 selected>Gift Certificate</option>\n" : "<option value=5>Gift Certificate</option>\n";
echo ($payment == 6) ? "<option value=6 selected>Mixed Payment</option>\n" : "<option value=6>Mixed Payment</option>\n";
echo "</select><p>";

if ($payment == 5) {
    $giftCert->displaySelectForm();
}

echo "Enter skus in this box: <a href='help/register.htm#skus' target='help'>(?)</a><br>
    <textarea cols=20 rows=7 name='sku'></textarea></td>
    <td valign=top align=right width=220 rowspan=2>$0.25 snacks <input type='text' name='specqty[731]' maxlength=3 size=3><br>
    $0.50 snacks <input type='text' name='specqty[732]' maxlength=3 size=3><br>
    $1.00 snacks <input type='text' name='specqty[734]' maxlength=3 size=3><br>
    $1.50 snacks <input type='text' name='specqty[1655]' maxlength=3 size=3><p>
    Water $1 <input type='text' name='specqty[1035]' maxlength=3 size=3><br>
    Polar $1.50 <input type='text' name='specqty[1031]' maxlength=3 size=3><br>
    Izze $2 <input type='text' name='specqty[1033]' maxlength=3 size=3><br>
    Nantucket$2 <input type='text' name='specqty[1030]' maxlength=3 size=3><br>
    Energy$2.50 <input type='text' name='specqty[1034]' maxlength=3 size=3><p>
    Snack Card <input type='text' name='specqty[784]' maxlength=3 size=3><br>         
    </td><td valign=top align=right width=220>
    50 Di(c)e <input type='text' name='specqty[741]' maxlength=3 size=3><br>
    75 Di(c)e <input type='text' name='specqty[735]' maxlength=3 size=3><br>
    1.00 Di(c)e <input type='text' name='specqty[736]' maxlength=3 size=3><br>
    1.25 Di(c)e <input type='text' name='specqty[1157]' maxlength=3 size=3><p>
    Draft: <input type='text' name='specqty[780]' maxlength=3 size=3><br>
    EDH: <input type='text' name='specqty[1223]' maxlength=3 size=3><br>
    FNM: <input type='text' name='specqty[1356]' maxlength=3 size=3><br>
    Prize Draft: <input type='text' name='specqty[1540]' maxlength=3 size=3><br>
    Magic League: <input type='text' name='specqty[1160]' maxlength=3 size=3><p>
    Copies/Char Sheets <input type='text' name='specqty[1161]' maxlength=3 size=3><p>
    Donation: \$<input type='text' name='specqty[1162]' maxlength=6 size 6><p>
    </td>";

// this will wipe out existing data, so we only want to show it if there is not already singles data
if (!isset($_POST['qty'][778])) {
    echo '<td valign=top align=left width=350 style="padding-left:15px">
        Total price of all<br>Magic Singles:<br>
        $<input type="text" name="price[778]" maxlength=8 size=8><br>
        Common - $0.25ea<br>
        Uncommon - $0.50ea<br>
        Rare - $1.00+ea, Click Below!<br>
        <a href="http://joehughto.com/newerPrices.php" target="_BLANK">Magic Price Lookup</a><br>
        <br>
        Case Cards: As Marked<br>
        Bulk Foils: $0.50ea<br>
        Bulk Box:<br>1/$1, 5/$4 10/$7, 20/$12, 50/$25<br>
        </td>';
}
echo"</tr>";

// display gift cert selling
echo "<tr><td colspan=2 align=right>\n";
$giftCert->displayForm();
echo "</td>";

// spend credits
if ($_POST['member'] > 0) {
    $creditSpending->displayCheckBoxes();
}

echo"</table><br>";
?>
