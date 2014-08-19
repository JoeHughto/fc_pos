<?php
class ProxyGen
{
    private static function get_data($url)
    {
    	$ch = curl_init();
    	$timeout = 5;
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    	$data = curl_exec($ch);
    	curl_close($ch);
    	return $data;
    }
    
    public static function cardSearch($name)
    {
        $name = urlencode($name);
        
        return json_decode(self::get_data("http://searchthecity.me/api?q=$name"), true);
    }
    
    public static function printCard($cardData, $type)
    {
        echo "<table width=222 height=319 border=1 style='float:left;'><td>";
        
        if (is_null($cardData['cards']))
        {
            echo "Invalid Card";
            echo "</td></table>";
            return;
        }
        
        if($type == 0)
        {
            echo $cardData['cards'][0]['name']."<br>";
            echo "Casting Cost: ".$cardData['cards'][0]['attrs']['manacost']." (CMC ".$cardData['cards'][0]['attrs']['cmc'].")<br><br>";
            echo "Type: ".$cardData['cards'][0]['attrs']['type']."<br>";
            echo "Subtype: ".$cardData['cards'][0]['attrs']['subtype']."<br><br>";
            $count = strlen($cardData['cards'][0]['rules-text']);
            if ($count <= 150)
                echo $cardData['cards'][0]['rules-text']."<br><br>";
            elseif ($count <= 300)
            {
                echo "<div style='font-size:13px'>";
                echo $cardData['cards'][0]['rules-text']."<br><br>";
                echo "<div style='font-size:medium'>";
            }
            elseif ($count <= 500)
            {
                echo "<div style='font-size:10px'>";
                echo $cardData['cards'][0]['rules-text']."<br><br>";
                echo "<div style='font-size:medium'>";
            }
            else
            {
                echo "<div style='font-size:7px'>";
                echo $cardData['cards'][0]['rules-text']."<br><br>";
                echo "<div style='font-size:medium'>";
            }
            
            if(!is_null($cardData['cards'][0]['attrs']['power']))
            {
                echo "P/T: ".$cardData['cards'][0]['attrs']['power']."/".$cardData['cards'][0]['attrs']['toughness'];
            }
            elseif(!is_null($cardData['cards'][0]['attrs']['loyalty']))
            {
                echo "Loyalty: ".$cardData['cards'][0]['attrs']['loyalty'];
            }
        }
        elseif($type == 1)
        {
            echo "<img width=212 height=309 src=".$cardData['cards'][0]['img'].">";
        }
        echo "</td></table>";
    }
}
?>