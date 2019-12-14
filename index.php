<?php

require_once(__DIR__."/Plugin.php");

header('Access-Control-Allow-Origin: *');
header('Content-Type: text/plain');

$original_file = basename($_FILES["fileToUpload"]["name"]);
$temp_file = tempnam(sys_get_temp_dir(), $original_file);
$uploadOk = 1;
$pmfType = strtolower(pathinfo($original_file,PATHINFO_EXTENSION));

// Check file size
if ($_FILES["fileToUpload"]["size"] > 5000000) {
    echo "Sorry, your file is too large.";
    $uploadOk = 0;
    return;
}
// Allow certain file formats
if($pmfType != "pmf") {
    echo "Sorry, only PMF files are allowed.";
    $uploadOk = 0;
    return;
}
// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
    return;
// if everything is ok, try to upload file
} else {
    if (!move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $temp_file)) {
        echo "Sorry, there was an error uploading your file.";
        return;
    }
}

$plugin = new PMFPlugin($temp_file);

function utf8ize( $mixed ) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } elseif (is_string($mixed)) {
        return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
    }
    return $mixed;
}

header('Content-Type: application/json');
$result = json_encode(utf8ize($plugin->getPluginInfo()));
echo $result;