<?php
$whitelist = array();
$whitelist[] = "getPunches";
$whitelist[] = "addCard";
$whitelist[] = "usePunch";
$whitelist[] = "getCreditTotal";

if(isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];
    $user = $_POST['user'];
    if(in_array($action, $whitelist)) {
        echo $action($user);
    }
}

function getPunches($user) {
    $cxn = open_stream();
   
    $sql = "SELECT SUM(netPunches) FROM snackCards WHERE memberID=$user";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_row($result);
    mysqli_close($cxn);
    return (($row[0] > 0) ? $row[0] : 0);
}

function addCard($user) {
    $creds = getCreditTotal($user);
    if ($creds < 12) {
        return getPunches($user);
    }
    if (!buyCard($user)) {
        return getPunches($user);
    }
    $cxn = open_stream();
    
    $sql = "INSERT INTO snackCards (memberID, netPunches, notes) VALUES ($user, 24, 'Bought a new snack card!')";
    $result = query($cxn, $sql);
    mysqli_close($cxn);
    return getPunches($user);
}

function buyCard($user)
{
    $cxn = open_stream();
    $sql = "INSERT INTO credits (daytime, memberID, credits, reason, senderID, notes) "
        . "VALUES (DATE_ADD(NOW(), INTERVAL 1 HOUR), 0, 12, 0, $user, 'Purchasing a digital snack card.')";
    
    $result = query($cxn, $sql);
    if ($result) {
        return true;
    } else {
        return false;
    }
}

function getCreditTotal($user) {
    $cxn = open_stream();
            
    $sql = "SELECT sum(credits)
        FROM (SELECT c1.credits
        FROM credits as c1
        WHERE memberID='$user'
        UNION ALL(SELECT (0 - c2.credits)
        FROM credits as c2
        WHERE senderID='$user'
        AND (reason='3' OR reason='0'))) AS cf";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_row($result);
    return $row[0];
}

function usePunch($user) {
    $cxn = open_stream();
   
    $sql = "INSERT INTO snackCards (memberID, netPunches, notes) VALUES ($user, -1, 'Using a snack punch.')";
    $result = query($cxn, $sql);
    mysqli_close($cxn);
    return getPunches($user);
}

function open_stream()
{
    $dbname="worldsap_fc";
    $host="localhost";
    $user="worldsap_fc";
    $pwd="37FUHupr";

    $access = new mysqli($host, $user, $pwd, $dbname);

    if ($err = mysqli_connect_errno()) {
        die ("Couldn't connect to server." . $err);
    } else {
        return $access;
    }
    return false;
}

function query($cxn, $sql)
{
    if (!$result = mysqli_query($cxn, $sql)) {
        //displayError(
        //    "Query Error!<br>Query: $sql<br>SQL Error: "
        //    . mysqli_error($cxn)
        //);
        return (false);
    } else {
        return ($result);
    }
}

?>