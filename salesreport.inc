<?php
/**
 * @file salesreport.inc
 * @brief salesreport.inc is a library of functions used to display the sales report
 *   on salesreport.php.
 * 
 * This file uses but does not include:
 * funcs.inc:
 * - Used for the config.inc include
 * - dateDiff()
 * - money()
 * - lastDayOfMonth()
 * - checkDateNum()
 * - dateStringVar()
 * 
 * @link http://www.worldsapartgames.org/fc/salesreport.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8b
 * @since     Project has existed since time immemorial.
 */

 /**
  * displayList() displays all items sold in specified time frame
  * @param string $start is a datestring for the beginning of the desired period.
  * @param string $end is a datestring for the end of the desired period.
  * @param int $order describes for the function how to sort the returned data.
  * @param int $staff is an optional memberID to restrict returned data by
  *   the staff who checked out the sale.
  * @param int $cust is an optional memberID to restrict returned data by
  *   the customer in the sale.
  */
function displayList($start, $end, $order, $staff, $cust) {
    $cxn = open_stream();

    $datestart = $start;
    $dateend = (date_create() < date_create($end)) ? date("Y-m-d") : $end;
    $start .= " 00:00:00";
    $end .= " 23:59:59";

    switch ($order) {
        case 1 :
        case 'NAME' : 
            $sorder = 'I.description';
            break;
        case 2 :
        case 'DEPT' : 
            $sorder = 'I.department, I.description';
            break;
        case 3 :
        case 'MANU' : 
            $sorder = 'I.manufacturer, I.description';
            break;
        case 4 : 
            $sorder = 'I.cost, I.description';
            break;
        default : 
            $sorder = 'I.description';
    }

    $staffSQL = ($staff > 0) ? "AND T.staffID = '$staff'" : "";
    $custSQL = ($cust > 0) ? "AND T.customerID = '$cust'" : "";
    $staffSQL2 = ($staff > 0) ? "AND staffID = '$staff'" : "";
    $custSQL2 = ($cust > 0) ? "AND customerID = '$cust'" : "";

    $sql = "SELECT I.description dsc,
        I.department dept,
        I.manufacturer manu,
        T.whensale whensale,
        T.ID tid,
        SI.itemID ID,
        SI.price price,
        SI.cost cost,
        SI.qty qty,
        SI.tax tax
        FROM soldItem SI
        JOIN transactions T
        ON SI.transactionID = T.ID
        JOIN items I
        ON SI.itemID = I.ID
        WHERE T.whensale >= '$start'
        AND T.whensale <= '$end'
        $staffSQL
        $custSQL
        ORDER BY $sorder, T.whensale";
    $result = query($cxn, $sql);

    $onesale = false;
    $totalTax = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $onesale = true;
        extract($row);
        $thisPrice = round(($price * $qty), 2);
        $Tprice[$ID] += $thisPrice;
        $Tcost[$ID] += round(($cost * $qty), 2);
        $Ttaxable[$ID] += ($tax == 1) ? $thisPrice : 0;
        $Ttax[$ID] = $tax;
        $Tqty[$ID] += $qty;
        $Tdesc[$ID] = $dsc;
        $Tdept[$ID] = $dept;
        $Tmanu[$ID] = $manu;
        $Ttid[$ID] = $tid;
        $Tdate[$ID] = $whensale;

        $deptQty[$dept] += $qty;
        $deptPrice[$dept] += $thisPrice;
        $manuQty[$manu] += $qty;
        $manuPrice[$manu] += $thisPrice;
    }

    // Determine total tax collected
    $sql = "SELECT SUM(tax) 
        FROM transactions             
        WHERE whensale >= '$start'
        AND whensale <= '$end'
        $staffSQL2
        $custSQL2
        ORDER BY whensale";

    $totalTax = queryOnce($cxn, $sql);

    if (!$onesale) {
        echo "<font size=+2>No sales during this period</font>";
        include('footer.php');
        exit();
    }

    // Get Payment Methods
    $sql = "SELECT sum(cash) payca, sum(creditcard) paycc, sum(checkpay) paych, sum(account) payac, sum(giftCert) paygc 
        FROM transactions             
        WHERE whensale >= '$start'
        AND whensale <= '$end'
        $staffSQL
        $custSQL";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_assoc($result);
    extract($row);

    // Get Account Change Info
    $sql = "SELECT sum(amount) FROM storeAccount WHERE amount > 0 AND whenAcct >= '$start' AND whenAcct <= '$end' $staffSQL $custSQL";
    $accountUp = queryOnce($cxn, $sql);
    $sql = "SELECT sum(amount) FROM storeAccount WHERE amount < 0 AND whenAcct >= '$start' AND whenAcct <= '$end' $staffSQL $custSQL";
    $accountDown = queryOnce($cxn, $sql);
    $sql = "SELECT sum(amount) FROM storeAccount";
    $accountTotal = queryOnce($cxn, $sql);

    // determine number of days in period
    $days = dateDiff('-', $datestart, $dateend) + 1;
    echo "Days: $days";

    $totalPrice = round(array_sum($Tprice), 2);
    $totalCost = round(array_sum($Tcost), 2);
    $totalTaxable = round(array_sum($Ttaxable), 2);
    $totalNontaxable = $totalPrice - $totalTaxable;
    $totalGross = $totalPrice - $totalCost;
    $margin = ($totalPrice != 0) ? (100 * $totalGross / $totalPrice) : 'NO SALES';
    $margin = round($margin);
    $sixty = round(($totalPrice * .6), 2);
    echo "<table cellpadding=4><tr><td>
        <b>Total Sales: " . money($totalPrice) . "<br>
        Total Cost: " . money($totalCost) . "<br>
        Gross Profit: " . money($totalGross) . "<br>
        Margin: $margin%<br>
        60% of Sales: " . money($sixty) . "
        </td><td valign=top width=200>
        Sales/Day: " . money($totalPrice / $days) . "<br>
        Cost/Day: " . money($totalCost / $days) . "<br>
        Profit/Day: " . money($totalGross / $days) . "
        </td><td valign=top>
        Est Final Sales: " . money($totalPrice * lastDayOfMonth(date('n')) / $days) . "<br>
        Est Final Cost: " . money($totalCost * lastDayOfMonth(date('n')) / $days) . "<br>
        Est Final Profit: " . money($totalGross * lastDayOfMonth(date('n')) / $days) . "<br>
        </td><td valign=top>
        Total Taxable Sales: " . money($totalTaxable) . "<br>
        Total Non-Taxable Sales: " . money($totalNontaxable) . "<br>
        Total Sales Tax: " . money($totalTax) . "
        </td></tr></table><hr>";
    if ($_SESSION['mem'] == 1)
        echo "<table cellpadding=5><tr><td>Cash:<br>" . money($payca) . "</td>
            <td>Account:<br>" . money($payac) . "</td>
            <td>Gift Certificates:<br>" . money($paygc) . "</td>
            <td>Account Added:<br>" . money($accountUp) . "</td>
            <td>Account Change:<br>" . money($accountUp + $accountDown) . "</td></tr>
            <tr><td>Credit Card:<br>" . money($paycc) . "</td>
            <td>Checks:<br>" . money($paych) . "</td>
            <td></td><td>Account Spent:<br>" . money($accountDown) . "</td>
            <td>Total Account Balance:<br>" . money($accountTotal) . "</td></tr></table><hr>
            ";


    echo "<table cellpadding=5 border><tr><td><b>Name</b></td><td>Qty<br>Sold</td><td>Total<br>Price</td><td>Tax</td><td>Total<br>Cost</td><td>Total<br>Gross</td>
        <td>Department<br>Manufacturer</td><td>Last Sale</td></tr>\n";

    switch ($order) {
    case 'DEPT' : asort($Tdept);
        foreach ($Tdept as $ID => $d) {

            // set date for last sale
            $date = date_create($Tdate[$ID]);

            // if its a new department, we put a header there
            if ($d != $lastD) {
                echo "<tr><td colspan=5><b>$d</b></td></tr>\n";
            }
            $lastD = $d; // set it for next time

            echo "<tr><td>D" . $Tdesc[$ID] . "</td>
                <td>" . $Tqty[$ID] . "</td>
                <td>" . $Tprice[$ID] . "</td>
                <td>" . (($Ttax[$ID] == 1) ? "Yes" : "No") . "</td>
                <td>" . $Tcost[$ID] . "</td>
                <td>" . ($Tprice[$ID] - $Tcost[$ID]) . "</td>
                <td>" . $d . "<br>" . $Tmanu[$ID] . "</td>
                <td><a href='viewreceipts.php?view={$Ttid[$ID]}' target='viewlast'>" . $date->format("M-j") . "</td></tr>";
        } // end foreach
        break;
    case 'MANU' : asort($Tmanu);
        foreach ($Tmanu as $ID => $m) {
            // set date for last sale
            $date = date_create($Tdate[$ID]);

            // if its a new department, we put a header there
            if ($m != $lastM) {
                echo "<tr><td colspan=5><b>$d</b></td></tr>\n";
            }
            $lastM = $m; // set it for next time

            echo "<tr><td>M" . $Tdesc[$ID] . "</td>
                <td>" . $Tprice[$ID] . "</td>
                <td>" . (($Ttax[$ID] == 1) ? "Yes" : "No") . "</td>
                <td>" . $Tcost[$ID] . "</td>
                <td>" . $Tprice[$ID] - $Tcost[$ID] . "</td>
                <td>" . $Tqty[$ID] . "</td>
                <td>" . $Tdept[$ID] . "<br>" . $m . "</td>                                 
                <td><a href='viewreceipts.php?view={$Ttid[$ID]}' target='viewlast'>" . $date->format("M-j-y") . "</td></tr>\n";
        } // end foreach
        break;
    case 'NAME':
    default : 
        foreach ($Tdesc as $ID => $desc) {
            // set date for last sale
            $date = date_create($Tdate[$ID]);

            echo "<tr><td>" . $desc . "</td>";
            echo "<td>" . $Tqty[$ID] . "</td>";
            echo "<td>" . $Tprice[$ID] . "</td>";
            echo "<td>" . (($Ttax[$ID] == 1) ? "Yes" : "No") . "</td>";
            echo "<td>" . $Tcost[$ID] . "</td>";
            echo "<td>" . ($Tprice[$ID] - $Tcost[$ID]) . "</td>";
            echo "<td>" . $Tdept[$ID] . "<br>" . $Tmanu[$ID] . "</td>                                 
                <td><a href='viewreceipts.php?view={$Ttid[$ID]}' target='viewlast'>" . $date->format('M-j') . "</td></tr>\n";
        }
        break;
    } // end switch

    echo "</table>";

    // Display totals by Manufacturuer and Department
    echo "<b>Sales By Department</b><br>
        <table border cellpadding=3><td>Department</td><td>Price</td><td>Quantity</td><td>Proportion</td></tr>\n";
    arsort($deptPrice);
    foreach ($deptPrice as $dept => $price) {
        $qty = $deptQty[$dept];
        echo "<tr><td>$dept</td><td>" . money($price) . "</td><td>$qty</td><td>" . round(($price / $totalPrice * 100), 0) . "%</td></tr>";
    }
    echo "</table>";

    echo "<p><b>Sales By Manufacturer</b><br>
        <table border cellpadding=3><td>Manufacturer</td><td>Price</td><td>Quantity</td></tr>\n";
    arsort($manuPrice);
    foreach ($manuPrice as $manu => $price) {
        $qty = $manuQty[$manu];
        echo "<tr><td>$manu</td><td>" . money($price) . "</td><td>$qty</td><td>" . round(($price / $totalPrice * 100), 0) . "%</td></tr>";
    }
    echo "</table>";
}

/**
 * displayTableByDay() prints a table of sales organised by day.
 * @param string $start is a datestring for the start of the desired period.
 * @param string $end is a datestring for the end of the desired period.
 */
function displayTableByDay($start, $end) {
    if (checkDateNum($start) && checkDateNum($end)) {
        $cxn = open_stream();
        $sql = "SELECT totalPrice,
            whensale
            FROM transactions
            WHERE whensale >= '$start'
            AND whensale <= '$end'
            ORDER BY whensale";
        $result = query($cxn, $sql);

        $curdate = 0;
        echo "<table>\n";
        while ($row = mysqli_fetch_assoc($result)) {
            extract($row);

            if ($curdate != 0 && dateStringVar($curdate) != dateStringVar($whensale)) {
                $date = date_create($curdate);
                $datestr = $date->format("F jS");
                $day = $date->format("l");
                echo "<tr><td>$datestr</td><td>$day</td><td>" . money($total) . "</td>";
                $barlength = round($total / 5);
                for ($i = 0; $i < $barlength; $i++)
                    echo "<td bgcolor=BLUE></td>";
                echo "</tr>\n";

                $total = 0;
            }

            $curdate = $whensale;
            $total += $totalPrice;
        }

        // print last day
        $date = date_create($curdate);
        $datestr = $date->format("F jS");
        $day = $date->format("l");
        echo "<tr><td>$datestr</td><td>$day</td><td>" . money($total) . "</td>";
        $barlength = round($total / 5);
        for ($i = 0; $i < $barlength; $i++)
            echo "<td bgcolor=BLUE></td>";
        echo "</tr>\n";

        $total = 0;

        echo "</table>\n";
    } else {
        displayError("Invalid Date for displayTableByDay in salesreport.inc");
    }
}

/**
 * displayTableByHour() prints a table of sales organised by hour.
 * @param string $start is a datestring for the start of the desired period.
 * @param string $end is a datestring for the end of the desired period.
 */
function displayTableByHour($start, $end) {
    $cxn = open_stream();
    $sql = "SELECT totalPrice,
        extract(hour FROM whensale) hr
        FROM transactions
        WHERE whensale >= '$start'
        AND whensale <= '$end'
        ORDER BY extract(hour FROM whensale)";
    $result = query($cxn, $sql);

    while ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        $hour[$hr] += $totalPrice;
    }

    echo "<table>\n";
    for ($count = 0; $count < 24; $count++) {
        echo "<tr><td>$count:00</td><td>\$" . $hour[$count] . "</td>\n";
        $barlength = round($hour[$count] / 5);
        for ($i = 0; $i < $barlength; $i++) {
            echo "<td bgcolor=BLUE></td>";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
}

?>
