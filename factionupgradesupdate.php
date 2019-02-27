<?php

$API_KEY_8085 = 'API_KEY_FOR_8085'; //j
$API_KEY_13851 = 'API_KEY_FOR_13851'; //vl
//$API_KEY_8085 = 'API_KEY_FOR_8085'; //sb
$API_KEY_8954 = 'API_KEY_FOR_8954'; //a
$API_KEY_12863 = 'API_KEY_FOR_12863'; //lt

$update_data = true;

if($update_data){
    getFactionUpgrades($API_KEY_8085, 8085);
    getFactionUpgrades($API_KEY_8954, 8954);
    //getFactionUpgrades($API_KEY_13851, 13851);
    getFactionUpgrades($API_KEY_12863, 12863);
}
else{
    echo 'Data update is turned off';
}

function getFactionUpgrades($API_KEY, $FactionID){
    try{

        if($API_KEY === ''){
            return;
        }

        $ctx = stream_context_create(array('http' => array('timeout' => 2)));
    
        $json = file_get_contents("https://api.torn.com/faction/?selections=basic,upgrades&key=".$API_KEY, 0, $ctx);
    
        if($json === false){return;}

        $obj = json_decode($json);

        if(isset($obj->error)){return;}

        if(isset($obj->ID)){
            if($obj->ID != $FactionID){return;}

            $upgrades = json_encode((object) ['upgrades' => $obj->upgrades]);
        
            $upgradesFile = fopen($FactionID.".json", "w") or die("Unable to open file!");
            fwrite($upgradesFile, $upgrades);
            fclose($upgradesFile);
            echo 'file ' . $FactionID . ' saved. ';
        }

    } catch (Exception $e) {
        
    }
}

?>