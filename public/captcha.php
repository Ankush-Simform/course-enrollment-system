<?php
session_start();

$code = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 5);
$_SESSION['captcha_code'] = $code;

header("Content-type: image/png");

$image = imagecreate(120, 40);  

$bg = imagecolorallocate($image, 255, 255, 255);  
$text = imagecolorallocate($image, 0, 0, 0);       
$line = imagecolorallocate($image, 200, 200, 200);  
for ($i = 0; $i <5; $i++) {
    imageline($image, rand(0,20), rand(40,120), rand(100,20), rand(0,40), $line);
}


imagestring($image, 5, 0, 10, $code, $text);

imagepng($image);
imagedestroy($image);
?>  