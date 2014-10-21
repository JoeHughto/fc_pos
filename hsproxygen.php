<?php

function getAllMatches($searchName)
{
    $searchName = urlencode($searchName);
    $curl = curl_init('http://www.hearthpwn.com/cards?filter-name='.$searchName.'&display=3&cookieTest=1');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
    $resp = curl_exec($curl);
    
    $count = substr_count($resp, '<div class="card-image-item">');

    if ($count > 0) {
        $position[] = array();
        $trimmed[] = array();
        for ($i = 0; $i < $count; $i++)
        {
            if ($i == 0) {
                $position[$i] = strpos($resp, '<div class="card-image-item">');
            } else {
                $position[$i] = strpos($resp, '<div class="card-image-item">', $position[$i-1]+1);
            }
        }
        for ($i = 0; $i < $count; $i++)
        {
            $trimmed[$i] = substr($resp, $position[$i]);
        }
        
        for ($i = 0; $i < $count; $i++)
        {
            $trimmed[$i] = substr($trimmed[$i], strpos($trimmed[$i], '<img src="'));
            $trimmed[$i] = substr($trimmed[$i], strpos($trimmed[$i], '"')+1);
            $trimmed[$i] = substr($trimmed[$i], 0, strpos($trimmed[$i], '"'));
        }
        
        $retval = $trimmed;
        
    } else {
        $retval = urlencode("$searchName not found");
        $retval = "http://placehold.it/253x356&text=$retval";
    }
    return $retval;
}

function delFromList($del_val, $arr)
{
    $tmp = array_values($arr);
    foreach (array_keys($tmp, $del_val, false) as $key) {
        unset ($tmp[$key]);
    }
    return $tmp;
}

function printfimage($url, $size = '30%')
{
    echo "<img src=".$url." width=$size>";
}

function fimage($url, $size = '30%')
{
    return "<img src=".$url." width=$size>";
}

function getCardUrl($searchName, $index = 0)
{
    if (substr($searchName, 0, 4) == "http") {
        return $searchName;
    }
    
    $options = getAllMatches($searchName);
    if (is_array($options)){
        if (array_key_exists($index, $options)) {
            return $options[$index];
        } else {
            return $options[0];
        }
    } else {
        return $options;
    }
}

function getMultiples($searchArray)
{
    $addToArray = array();
    for ($key = 0; $key < count($searchArray); $key++)
    {
        $val = $searchArray[$key];
        if (is_numeric(substr($val, 0, 1))) {
            $multiples = (int)(substr($val, 0, 1));
            $tmpval = substr($val, 2);
            $searchArray[$key] = $tmpval;
            for($i = 1; $i < $multiples; $i++)
            {
                $addToArray[] = $tmpval;
            }
        }
    }
    $searchArray = array_merge($searchArray, $addToArray);
    sort($searchArray);
    return $searchArray;
}

echo "<html>";
echo "<body>";
if ($_POST) {
    $output = "";
    $listarray = array();
    $choicearray = array();
    $cardarray = array();
    
    if($_POST['cardList']) {
        $listarray = preg_split('/\r\n|[\r\n]/', $_POST['cardList']);
    }
    
    $listarray = array_filter($listarray);
    $listarray = getMultiples($listarray);
    
    if (array_key_exists('count', $_POST)) {
        for ($i = 0; $i < $_POST['count']; $i++)
        {
            $listarray[]= $_POST["card[$i]"];
        }
    }
    if (array_key_exists('submit', $_POST))
    {
        if ($_POST['submit'] == "Update") {
            $cardarray = $_POST['card'];
            for ($i = 0; $i < $_POST['count']; $i++)
            {
                printfimage(getCardUrl($cardarray[$i]));
            }
            die();
        }
    }
    
    foreach ($listarray as $var) {
        if ($var == "") continue;
        $matches = getAllMatches($var);
        if (is_array($matches))
        {
            if (count($matches) > 1) {
                $choicearray[]= $matches;
                $listarray = delFromList($var, $listarray);
            } else {
                $output .= fimage($matches[0]);
            }
        } else {
            $output .= fimage($matches);
        }
    }
    
    $choices = array();
    if (array_key_exists('choices', $_POST)) {
        $choices = $_POST['choices'];
    }
    
    if (count($choices) == count($choicearray))
    {
        $tmpcount = 0;
        foreach ($choicearray as $printme){
            printfimage($printme[$choices[$tmpcount]]);
            $tmpcount++;
        }
        foreach ($listarray as $printme) {
            printfimage(getCardUrl($printme));
        }
    } elseif (count($choicearray) > 0) {
        $i = 0;
        echo "<h2>Specificity Required:</h2>";
        echo "Select the radio button next to each card you intended to find.<br>";
        echo "Then click the Update button.<hr>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='count' value='".(count($listarray)+count($choicearray))."'>";
        foreach ($choicearray as $options) {
            foreach ($options as $choice) {
                printfimage($choice, '15%');
                echo "<input type='radio' name='card[$i]' value='$choice'>";
            }
            echo "<hr>";
            $i++;
        }
        foreach ($listarray as $var)
        {
            if (!is_array($var)) {
                echo "<input type='hidden' name='card[$i]' value='$var'>";
            }
            $i++;
        }
        echo "<input type='submit' name='submit' value='Update'></form>";
        
    } else {
        echo $output;
    }
} else {
    echo "<h2>Hearthstone Proxy Generator</h2>";
    echo "Helpful hints:<br>";
    echo "* Shortened names work, as long as you don't start in the middle of a word.<br>";
    echo "eg 'Starving', and 'Buzzard', and even 'Buzz' will return the same thing, but 'rving' will not.<br><br>";
    echo "* If there are multiple cards that could fit a provided name, you will be asked to specify which you meant.<br>";
    echo "eg 'Arch' could return 'Archmage', 'Archmage Antonidas', or 'Elven Archer', so it will ask which you wanted.<br><br>";
    echo "* Inserting a number at the start of a line will print the given number quantity of that card.<br>";
    echo "eg 'Leeroy', and '1 Leeroy' print a single card. '2 Leeroy' and up will return the number you provide.<br>";
    echo "<br>";
    echo "<form method='post'>";
    echo "Input card names, one per line:<br>";
    echo "<textarea name='cardList'></textarea><br>";
    echo "<input type='submit'>";
    echo "</form>";
}

echo "</body>";
echo "</html>";
?>
