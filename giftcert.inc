<?php
/**
 * @file giftcert.inc
 * @brief giftcert.inc is a library used for selling and converting gift certificates.
 * 
 * @todo This file uses some functions, and they should be noted, even if the
 *   file doesn't include or require them.
 * 
 * @link http://www.worldsapartgames.org/fc/index.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

/**
 * giftCertBalance takes a gift certificate ID, and returns the balance
 *   remaining on that gift certificate. If there is no cert with that ID,
 *   it will return false.
 * @param int $ID is the ID of the gift certificate to be tested.
 * @retval float Returns the balance on the certificate, or false, if there
 *   is no certificate at that ID, returns false instead.
 */
function giftCertBalance($ID)
{
    $cxn = open_stream();
   
    $sql = "SELECT SUM(amount) FROM giftCerts WHERE giftCertID='$ID'";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_row($result);
    return($row[0]);
}

/**
 * giftCertToAccount allows members to inject their gift certificate
 *   balance directly into their store credit balance.
 * @param int $giftCert is the ID of the gift certificate.
 * @param int $member   in the selected member's ID.
 * @retval boolean Returns true if the gift certificate is successfully
 *   converted to the member's store credit, and false otherwise.
 */
function giftCertToAccount($giftCert, $member)
{
    $cxn = open_stream();

    $balance = giftCertBalance($giftCert);
    $unbalance = -$balance;
    if ($balance > 0) {
        $stmt = $cxn->prepare(
            "INSERT INTO giftCerts (giftCertID, transactionID, whenAcct, "
            . "amount) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), ?)"
        );
        $amount = -$amount;
        $stmt->bind_param(
            "iid", $giftCert, $_SESSION['transaction_ID'], $unbalance
        );
        if ($stmt->execute()) {
            accountTransact(
                $member, $balance, $_SESSION['transaction_ID'],
                "Redeem gift cert #$giftCert"
            );
            return true;
        } else {
            displayError("Failed to create Gift Certificate transaction. Error #1");
            return false;
        }
    } else {
        return false;
    }
}
            
/**
 * showAllGiftCerts prints a list of all gift certificates in the system, and
 *   all relavent information for each.
 */
function showAllGiftCerts()
{
    $giftCert = new giftCert;

    //This doesn't work like you think it does...
    for ($giftCert->certNum = 1; $giftCert->displayInfo(); $giftCert->certNum++);
}

/**
 * giftCert is a class for managing gift certificates consistently.
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @since     Project has existed since time immemorial.
 */
class giftCert
{
    var $name;
    var $newCert;
    var $notes;

    var $spentCert;
    var $certNum;

    /**
     * Constructor for GiftCert objects.
     */
    function giftCert()
    {
        $this->name      = (checkName($_POST['GCname'])) ? $_POST['GCname'] : '';
        $this->newCert   = greaterThanZero($_POST['GCnewCert']);
        $this->certNum   = greaterThanZero($_POST['GCcertNum']);
        $this->notes     = (checkName($_POST['GCnotes'])) ? $_POST['GCnotes'] : '';
    }

    /**
     * displayForm prints a form which is compatible with the
     *   constructor giftCert(). Designed for register.php
     */
    function displayForm()
    {
        echo "<b>Purchase Gift Certificate</b><br>
            Recipient: <input type='text' name='GCname' value='" . $this->name . "' size=20 maxlength=40><br>
            Value: \$<input type='text' name='GCnewCert' value='" . moneyND($this->newCert) . "' size=7 maxlength=7><br>";
    }

    /**
     * displayInfo prints all relavent information for a given gift
     *   certificate to the page on which it is called.
     * @retval boolean Returns true if successful, false otherwise.
     */
    function displayInfo()
    {
        $cxn = open_stream();

        $balance = giftCertBalance($this->certNum);
        if ($balance) {
            $sql = "SELECT * FROM giftCerts WHERE giftCertID='" . $this->certNum . "'";
            if ($result = query($cxn, $sql)) {
                // the first one is done differently because it is where it is set up
                $row = mysqli_fetch_assoc($result);
                extract($row);
                echo "<b>Gift Certificate #" . $giftCertID . " details</b><br>
                    Recipient: $name<br>
                    Starting Value: " . money($amount) . "<br>";

                // now we show any transactions if any
                $first = true;
                while ($row = mysqli_fetch_assoc($result)) {
                    if ($first) {
                        echo "Previous Gift Certificate Uses:<br>
                            <table border><tr><td>Date</td><td>Amount Spent</td></tr>";
                        $first=false;
                    }

                    extract($row);
                    $date = date_create("$whenAcct");
                    echo "<tr><td>" . $date->format("F jS, Y, g:i A")
                        . "</td><td>" . money(-$amount) . "</td></tr>";
                } // end while
                if (!$first) {
                    echo "</table><br>";
                }

                echo "Remaining Balance: " . money($balance) . "<p>";
            } else {
                displayError(
                    "Unable to retrieve Gift Certificate info<br>
                    SQL: $sql<br>
                    SQL Error" . $cxn->error
                );
                return false;
            }
        } else {
            echo "<font color=RED>There is no gift certificate #" . $this->certNum . "<p>";
            return false;
        }
    }

    /**
     * displaySelectForm prints a form for use in redeeming gift certificates.
     */
    function displaySelectForm()
    {
        echo "Redeem Gift Certificate #: <input type='text' name='GCcertNum' size=3 maxlength=3 value='" . $this->certNum . "'><br>";
    }

    /**
     * redeem takes an amount, and reduces the gift certificate's balance
     *   by that amount.
     * @param float $amount is the amount being deducted from the balance.
     * @retval boolean Returns true if amount is deducted, false otherwise.
     */
    function redeem($amount)
    {
        $cxn = open_stream();
        $balance = giftCertBalance($this->certNum);
        if ($amount <= $balance) {
            $stmt = $cxn->prepare(
                "INSERT INTO giftCerts (giftCertID, transactionID, whenAcct, amount)
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), ?)"
            );
            $amount = -$amount;
            $stmt->bind_param("iid", $this->certNum, $_SESSION['transaction_ID'], $amount);
            if ($stmt->execute()) {
                return true;
            } else {
                displayError(
                    "Failed to create Gift Certificate transaction. Error #2<br>
                    GiftCertID: {$this->certNum}, Transaction number: {$_SESSION['transaction_ID']}, amount: $amount<br>
                    {$stmt->error}<p>"
                );
                return false;
            }
        } elseif ($balance) {
            // if the gift cert exists but there is not enough in the account
            echo "<font color=RED>Insufficient value in gift certificate.</font><br>
                Gift Certificate #" . $this->certNum . " value is ". money($balance) ."<br>
                You tried to spend " . money($amount) . "<p>";
            return false;
        } elseif (!$balance) {
            echo "<font color='RED'>No gift certificate #" . $this->certNum . "</font><p>";
            return false;
        }
    }

    /**
     * sell is called to create new gift certificates in the database. This
     * function should not be called until after payment is concluded.
     * @retval int Returns the gift certificate ID that has been created, or
     *   false if the creation failed.
     */
    function sell()
    {
        $cxn = open_stream();

        $sql = "SELECT MAX(giftCertID) FROM giftCerts";
        $result = query($cxn, $sql);
        $row = mysqli_fetch_row($result);
        $max = $row[0] + 1;

        $stmt = $cxn->prepare(
            "INSERT INTO giftCerts (name, giftCertID, transactionID, whenAcct, amount)
            VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), ?)"
        );
        $stmt->bind_param("siid", $this->name, $max, $_SESSION['transaction_ID'], $this->newCert);
        if ($stmt->execute()) {
            return($max);
        } else {
            echo "<font color='RED'>Error: $stmt->error</font><p>";;
            return (false);
        }
    }
}
?>
