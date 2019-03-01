<?php

$config = file_get_contents("keys.json");
$keys = json_decode($config)->keys;
$factions = json_decode(file_get_contents("config.json"))->factions;
$update_data = true;
if($update_data){
  foreach($keys as $key)
  {
    $id= $key->id;
      if($factions->$id->update)
      {
        getFactionUpgrades($key->key, $key->id);
      }
  }
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
        echo 'fail';
    }
}

?>
