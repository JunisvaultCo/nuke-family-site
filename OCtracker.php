<?php

$API_KEY_8085 = 'API_KEY_FOR_8085'; //j
$API_KEY_13851 = 'API_KEY_FOR_13851'; //vl
$API_KEY_17133 = 'API_KEY_FOR_17133'; //sb
$API_KEY_8954 = 'API_KEY_FOR_8954'; //a
$API_KEY_12863 = 'API_KEY_FOR_12863'; //lt
$API_KEY_9745 = 'API_KEY_FOR_9745'; //brux


$crimesArray = [];

$crimesReadyArray = [];

$crimesNotifications = [];

getCrimeNotificationsData($crimesNotifications);

getCrimeData($API_KEY_8085, 8085, $crimesArray, $crimesReadyArray);
getCrimeData($API_KEY_8954, 8954, $crimesArray, $crimesReadyArray);
getCrimeData($API_KEY_13851, 13851, $crimesArray, $crimesReadyArray);
getCrimeData($API_KEY_12863, 12863, $crimesArray, $crimesReadyArray);
getCrimeData($API_KEY_9745, 9745, $crimesArray, $crimesReadyArray);
getCrimeData($API_KEY_17133, 17133, $crimesArray, $crimesReadyArray);

saveData($crimesArray, $crimesReadyArray, $crimesNotifications);

function getCrimeData($API_KEY, $FactionID, array & $crimesArray, array & $crimesReadyArray){
    try {
        if($API_KEY === ''){
            return;
        }

        $ctx = stream_context_create(array( 
            'http' => array( 
                'timeout' => 2 
                ) 
            ) 
        );

        $json = file_get_contents("https://api.torn.com/faction/?selections=basic,crimes&key=$API_KEY", 0, $ctx);

        if($json === false){
            return;
        }

        $obj = json_decode($json);

        if(isset($obj->error)){
            return;
        }

        if(isset($obj->ID)){
            if($obj->ID != $FactionID){
                return;
            }

            /*
            $crimes = json_encode((object) ['crimes' => $obj->crimes]);
            $crimesFile = fopen($FactionID."crimes.json", "w") or die("Unable to open file!");
            fwrite($crimesFile, $crimes);
            fclose($crimesFile);
            echo 'file ' . $FactionID . 'crimes saved. ';
            */

            foreach ($obj->crimes as $key1 => $value1) {
                if($value1->initiated != 1){
                    foreach ($value1->participants as $key2 => $value2) {
                        $crime = new stdClass();

                        $crime->faction_id = $obj->ID;
                        $crime->faction_name = $obj->name;
                        $crime->crime_id =  $key1;
                        $crime->crime_name =  $value1->crime_name;
                        $crime->time_ready =  $value1->time_ready;
                        $crime->user_id =  $key2;
                        $crime->user_name = $obj->members->$key2->name;
                        $crime->last_action = $obj->members->$key2->last_action;

                        if($value2[1] == ""){
                            $crime->user_status = $value2[0];
                            $crime->executable = $value2[0] == "Okay" ? 1 : 0;
                        }
                        else {
                            $crime->user_status = implode(', ', $value2);
                            $crime->executable = $value2[0] == "Okay" ? 1 : 0;
                        }

                        $crime->time_left = $value1->time_left;

                        array_push($crimesArray, $crime);

                        if($value1->time_left == 0){
                            array_push($crimesReadyArray, $crime);
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {

    }
}


function getCrimeNotificationsData(array & $crimesNotifications){
    $json = file_get_contents("crimesNotifications.json");

    if($json === false){
        return;
    }

    $obj = json_decode($json);

    if(!isset($obj->crimes)){
        return;
    }
    else{
        $crimesNotifications = (array) $obj->crimes;
    }

}

function saveData(array & $crimesArray, array & $crimesReadyArray, array & $crimesNotificationsArray){
    $crimes = json_encode((object) ['crimes' => $crimesArray]);

    $crimesReady = json_encode((object) ['crimes' => $crimesReadyArray]);

    $crimesFile = fopen("crimes.json", "w") or die("Unable to open file!");

    fwrite($crimesFile, $crimes);

    fclose($crimesFile);
    echo 'crimes file saved ';

    /*
    $crimesReadyFile = fopen("crimesReady.json", "w") or die("Unable to open file!");
    fwrite($crimesReadyFile, $crimesReady);
    fclose($crimesReadyFile);
    */

    if(sizeof($crimesReadyArray) > 0){
        callWebHook($crimesReadyArray, $crimesNotificationsArray);
    }

    $crimesNotifications = json_encode((object) ['crimes' => $crimesNotificationsArray]);
    $crimesNotificationsFile = fopen("crimesNotifications.json", "w") or die("Unable to open file!");

    fwrite($crimesNotificationsFile, $crimesNotifications);
    fclose($crimesNotificationsFile);
}

function callWebHook(array & $crimesReadyArray, array & $crimesNotificationsArray){

    $crimes = [];

    foreach($crimesReadyArray as $participant){

        if(array_key_exists($participant->crime_id, $crimes)){
            $member = new stdClass();

            $member->user_id = $participant->user_id;
            $member->user_name = $participant->user_name;
            $member->last_action = $participant->last_action;
            $member->user_status = $participant->user_status;

            array_push($crimes[$participant->crime_id]->participants, $member);

            if($crimes[$participant->crime_id]->executable == 1){
                $crimes[$participant->crime_id]->executable = $participant->executable;
            }
        } 
        else {
            $crime = new stdClass();

            $crime->faction_id = $participant->faction_id;
            $crime->faction_name = $participant->faction_name;
            $crime->crime_id = $participant->crime_id;
            $crime->crime_name = $participant->crime_name;
            $crime->time_ready = $participant->time_ready;
            $crime->executable = $participant->executable;
            $crime->time_left = $participant->time_left;
            $crime->participants = [];

            $member = new stdClass();

            $member->user_id = $participant->user_id;
            $member->user_name = $participant->user_name;
            $member->last_action = $participant->last_action;
            $member->user_status = $participant->user_status;

            array_push($crime->participants, $member);

            $crimes[$crime->crime_id] = $crime;
        }
    }

    $newNotifications = [];

    foreach($crimes as $crime){
        //Mention
        $msg = "";

        $data = new stdClass();

        if($crime->executable == 1){
            switch($crime->faction_id){
                case 8954: 
                    $msg = "<@&328471106753265668>"; //NA Member
                    break;
                case 8085:
                    $msg = "<@&462024210231984128>"; //NB Member
                    break;
		        case 13851:
                    $msg = "<@&515643591452917784>"; //NC Member
                    break;
                case 12863:
                    $msg = "<@&521402798932230145>"; //ND Member
                    break;
                case 9745:
                    $msg = "<@&540257920647168011>"; //ER Member
                    break;
                case 17133:
                    $msg = "<@&540296281189384205>"; //TM Member
                    break;
                default:
                    $msg = "<@&504910296414945296>"; //OC Manager
            }
        }
        else {
            $msg = "\r\n";

            $crimeNotification = new stdClass();
            $crimeNotification->participants = [];

            $newNotifications[$crime->crime_id] = $crimeNotification;
        }

        date_default_timezone_set('UTC');

        $dateReady = new DateTime();
        $dateReady->setTimestamp($crime->time_ready);

        //$dateReady->format('dd M yyyy H:i:s') . "\n";

        $dateNow = new DateTime();
        //$dateNow->format('dd M yyyy H:i:s') . "\n";

        $embed = new stdClass();
        $embed->title = $crime->faction_name;
        $embed->description = "```" . $crime->crime_name . " [$crime->crime_id] \r\n" . $dateReady->format('dS M Y H:i:s') ."```";
        $embed->footer = new stdClass();
        $embed->footer->text = "Report time: ". $dateNow->format('dS M Y H:i:s');
        $embed->color = $crime->executable == 1 ? 65280 : 16711680;

/*
        $msg .= "```diff";
        $msg .= "\r\n";
        $msg .= $crime->faction_name;
        $msg .= "\r\n";
        $msg .= $crime->crime_name;
        $msg .= "\r\n";
        $msg .= $crime->executable == 1 ? "+ READY" : "- DELAYED";
*/

        $embed->fields = [];

        foreach($crime->participants as $participant){
            $member = new stdClass();

            $member->user_id = $participant->user_id;
            $member->user_name = $participant->user_name;
            $member->last_action = $participant->last_action;
            $member->user_status = $participant->user_status;

            array_push($newNotifications[$crime->crime_id]->participants, $member);

            $field = new stdClass();

            $field->name = "\r\n$participant->user_name [$participant->user_id]";
            $field->value = ($participant->user_status == "Okay" ? ":white_check_mark: " : ":no_entry_sign: ") . $participant->user_status . "\r\n" . "Last action " . $participant->last_action;

            array_push($embed->fields, $field);

/*
            $msg .= "\r\n";
            $msg .= "-------------------------";
            $msg .= "\r\n";
            $msg .= "  $participant->user_name [$participant->user_id] ";
            $msg .= "\r\n";
            $msg .= "" . $msg .= "" . ($participant->user_status == "Okay" ? "+ " : "- ") . $participant->user_status;
            $msg .= "\r\n";
            $msg .= "  Last action " . $participant->last_action;
            $msg .= "\r\n";
            $msg .= "-------------------------";
*/

        }

/*
        $msg .= "```";
*/

        if(trim($msg) !== ""){
            $data->content = $msg;
        }

        $sendMsg = false;

        if($crime->executable !== 1){
            $alreadySent = false;

            foreach($crimesNotificationsArray as $key=>$value){
                
                if($key = $crime->crime_id){
                    $alreadySent = true;

                    foreach($value->participants as $participant1){
                        
                        if(!$sendMsg){

                            foreach($newNotifications[$crime->crime_id]->participants as $participant2){

                                if($participant1->user_id == $participant2->user_id){

                                    $p1_status = explode(" ", $participant1->user_status);
                                    $p2_status = explode(" ", $participant2->user_status);

                                    if(sizeof($p1_status) == sizeof($p2_status)){
                                        if(sizeof($p1_status) >= 2){
                                            if($p1_status[0] == $p2_status[0] && $p1_status[1] == $p2_status[1]){
                                                $sendMsg = false;
                                                echo '<br/>false1 ';
                                            }

                                            else{
                                                $sendMsg = true;
                                                echo '<br/>true1 ';
                                                break;
                                            }
                                        }
                                        else{
                                            if($p1_status[0] == $p2_status[0]){
                                                $sendMsg = false;
                                                echo '<br/>false2 ';
                                            }
                                            else{
                                                $sendMsg = true;
                                                echo '<br/>true2 ';
                                                break;
                                            }
                                        }
                                    }
                                    else{
                                        $sendMsg = true;
                                        echo '<br/>true3 ';
                                        break;
                                    }
                                }
                            }
                        }
                        else{
                            break;
                        }
                    }
                }
            }

            if(!$alreadySent){
                $sendMsg = true;
                echo '<br/>true4 ';
            }
        }
        else{
            $sendMsg = true;
            echo '<br/>true5 ';
        }

        if($sendMsg){

            $data->embeds = [];

            array_push($data->embeds, $embed);

            $url = ''; //something :P
            //$url = 'https://discordapp.com/api/webhooks/510799561342713871/zRBD5ERbM3LhRn63PUnhw1AERasjexSADopMNJ8-32kJhIwdmoUMqfb_FHxnKJYxiTYT'; //Test

            switch($crime->faction_id){
                case 8954: 
                    $url = 'https://discordapp.com/api/webhooks/522394409778544670/orz8NlBW_40bo4zrYHvt5KvAw3W3Ng0Tz8Mq5M-cPBvAi89bdpj2S7vEO1wvsUFNnXWu'; //Production NA
                    break;
                case 8085:
                    $url = 'https://discordapp.com/api/webhooks/522395149955891212/o6dEB12lFTTfHEV2ALKaX0kQKVQq95gEL4FdcTsFfn0o1A0Ohqv7Cn4qPlem29pD2Qn2'; //Production NB
                    break;
		        case 13851:
                    $url = 'https://discordapp.com/api/webhooks/522395287780851723/GrqWlv_Uze24l73Te3CJ_BGEk2fdk25-uJ4ckCmWNPVkpkpUcYvJpCNxvh-4DjGJc2nZ'; //Production NC
                    break;
                case 12863:
                    $url = 'https://discordapp.com/api/webhooks/522395384207769620/jd31WXo3ILf6CROXtRsMf-NAeiBICXOomexCkhOLZxWxLscRWJRgA5SdeX46NQoQHzfn'; //Production ND
                    break;
                case 9745:
                    $url = 'https://discordapp.com/api/webhooks/542747201563262978/ghROfQ9NjDqttQgKM7rjLwdYrGoiKeWO67vw5vx3vbP7UqQuxLj8FU18L09cgDueWY2S'; //Production ER
                    break;
                case 17133:
                    $url = 'https://discordapp.com/api/webhooks/542805037672562688/IvkgOlUg-NApexqX6x6pTeuZJRwobucmwdPw1ZKBWVYQf9qRfLd2WYUUQpILE6u52cxJ'; //Production TM
                    break;
                default:
                    $url = 'https://discordapp.com/api/webhooks/510830422574497795/TsZIMD3Z2-lC3nOabnWmPqw89l5Q6SK-dynkKOK2B96GV2-BPPAHJP8UbbVaKIcCjMAw'; //Production OC Manager
            }
            sendMessageToDiscord($data, $url);
        }
    }

    $crimesNotificationsArray = $newNotifications;
}

function sendMessageToDiscord($data, $url){

    //calling discord web hook to send message

    usleep(500000);

    $options = array(
            'http' => array(
            'header'  => "Content-type: application/json",
            'method'  => 'POST',
            'content' => json_encode($data),
        )
    );

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    echo json_encode($http_response_header);

    if($result === false){
        return false;
    }
    else{
        return true;
    }

    echo '<br/>web hook called';
}


?>