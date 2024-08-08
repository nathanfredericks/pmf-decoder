<?php
require_once(__DIR__."/Plugin.php");

header("Content-Type: application/json");

$response = [];

if (empty($_FILES) || $_FILES["plugin"]["error"]) {
    $response["message"] = "no file uploaded";
    http_response_code(400);
    echo json_encode($response);
    return;
}

$original_file = basename($_FILES["plugin"]["name"]);
$original_file_type = strtolower(pathinfo($original_file, PATHINFO_EXTENSION));
if ($original_file_type != "pmf") {
    echo "only pmf files are allowed";
    http_response_code(400);
    echo json_encode($response);
    return;
}

$temp_file = tempnam(sys_get_temp_dir(), $original_file);
if (!move_uploaded_file($_FILES["plugin"]["tmp_name"], $temp_file)) {
    $response["message"] = "error uploading file";
    http_response_code(500);
    echo json_encode($response);
    return;
}

try {
    $plugin = new PMFPlugin($temp_file);

    function utf8ize($mixed) {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
        }
        return $mixed;
    }

    $response["plugin"] = utf8ize($plugin->getPluginInfo())["code"];
} catch (Exception $e) {
    $response["message"] = "error processing plugin";
    http_response_code(500);
    echo json_encode($response);
    return;
}

echo json_encode($response);