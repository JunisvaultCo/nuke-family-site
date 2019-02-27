<?php

$json = file_get_contents("8954.json");

$data = json_decode($json);

//var_dump($data->upgrades);

$upgrades = array();

//Preparing array
foreach ($data->upgrades as $key => $value){
    if($value->branch != "Core"){
        if(!array_key_exists($value->branch, $upgrades)){
            $upgrades[$value->branch] = array();
        }
        array_push($upgrades[$value->branch], array($value->name, $value->ability));
    }
}

//var_dump($upgrades);



header("Content-Type: image/png");

$img_width = 400;
$img_height = 0;

$current_posy = 10;

$bg_color = [0,0,0];//[63,63,63];
$text_color = [255,255,255];//[100,200,50];


$strings = array();

foreach($upgrades as $key => $value){
    //ovo mi je u stvari top margina
    $current_posy += 20;

    $text = $key;

    $font = 20;

    $th = 26; //imagefontheight($font); /https://reeddesign.co.uk/test/points-pixels.html

    $tposx = /*($img_size[0] - $tw) / 2*/ 10;
    $tposy = /*($img_size[1] - $th) / 2*/ $current_posy + ($th / 2);

    $current_posy += $th + 0;

    array_push($strings, array($font, $tposx, $tposy, $text, $text_color));

    foreach($value as $value1){
        $text1 = $value1[0];

        $font1 = 15;

        $th = 21; //imagefontheight($font1); //https://reeddesign.co.uk/test/points-pixels.html

        $tposx1 = 40;
        $tposy1 = $current_posy + ($th / 2);

        $current_posy += $th + 0;

        array_push($strings, array($font1, $tposx1, $tposy1, $text1, $text_color));

        $text2 = $value1[1];

        $font2 = 10;

        $th = 13; //imagefontheight($font2); //https://reeddesign.co.uk/test/points-pixels.html

        $tposx2 = 40;
        $tposy2 = $current_posy + ($th / 2);

        $current_posy += $th + 15;

        array_push($strings, array($font2, $tposx2, $tposy2, $text2, $text_color));
    }
}





$img_height = $current_posy + 10;

$img_size = [$img_width, $img_height];

$img = @imagecreatetruecolor($img_size[0], $img_size[1]) or die("Cannot Initialize new GD image stream");

$background_color = imagecolorallocate($img, $bg_color[0], $bg_color[1], $bg_color[2]);
//$text_color = imagecolorallocate($img, $text_color[0], $text_color[1], $text_color[2]);

// Make the background transparent
//imagecolortransparent($img, $background_color);

//for transparent background
imagealphablending($img, false);
imagesavealpha($img, true);
$col=imagecolorallocatealpha($img,255,255,255,127);
imagefill($img, 0, 0, $col);
//for transparent background

//imagestring($img, $font, $tposx, $tposy, $text, $text_color);

foreach($strings as $value){
    //var_dump($value);
    //imagestring($img, $value[0], $value[1], $value[2], $value[3], imagecolorallocate($img, $value[4][0], $value[4][1], $value[4][2]));
    //BEBAS.ttf
    imagettftext ($img, $value[0], 0, $value[1], $value[2], imagecolorallocate($img, $value[4][0], $value[4][1], $value[4][2]), "./Truly Madly Dpad.otf", $value[3]);
}

imagepng($img);

imagedestroy($img);


?>