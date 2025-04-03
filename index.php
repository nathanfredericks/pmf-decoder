<?php

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set("display_errors", 0);

require_once __DIR__ . "/Plugin.php";

use Google\CloudFunctions\FunctionsFramework;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

function pmfDecoder(ServerRequestInterface $request): ResponseInterface
{
    $allowedOrigin = "https://pmt.mcpe.fun";

    if ($request->getMethod() === "OPTIONS") {
        return new Response(204, [
            "Access-Control-Allow-Origin" => $allowedOrigin,
            "Access-Control-Allow-Methods" => "POST, OPTIONS",
            "Access-Control-Allow-Headers" => "Content-Type",
        ]);
    }

    if ($request->getMethod() !== "POST") {
        return new Response(
            405,
            [
                "Access-Control-Allow-Origin" => $allowedOrigin,
                "Content-Type" => "application/json",
            ],
            json_encode([
                "success" => false,
                "message" => "Method not allowed",
            ])
        );
    }

    $headers = [
        "Access-Control-Allow-Origin" => $allowedOrigin,
        "Content-Type" => "application/json",
    ];

    $uploadedFiles = $request->getUploadedFiles();
    if (empty($uploadedFiles) || !isset($uploadedFiles["plugin"])) {
        return new Response(
            400,
            $headers,
            json_encode([
                "success" => false,
                "message" => "No file uploaded",
            ])
        );
    }

    $uploadedFile = $uploadedFiles["plugin"];
    if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
        return new Response(
            400,
            $headers,
            json_encode([
                "success" => false,
                "message" => "Error uploading file",
            ])
        );
    }

    if ($uploadedFile->getSize() > 5 * 1024 * 1024) {
        return new Response(
            400,
            $headers,
            json_encode([
                "success" => false,
                "message" => "File size exceeds 5MB",
            ])
        );
    }

    $original_file = $uploadedFile->getClientFilename();
    $original_file_type = strtolower(
        pathinfo($original_file, PATHINFO_EXTENSION)
    );
    if ($original_file_type != "pmf") {
        return new Response(
            400,
            $headers,
            json_encode([
                "success" => false,
                "message" => "Invalid file type. Only PMF files are allowed.",
            ])
        );
    }

    $temp_file = tempnam(sys_get_temp_dir(), "pmf_");
    try {
        $stream = $uploadedFile->getStream();
        file_put_contents($temp_file, $stream->getContents());

        $plugin = new PMFPlugin($temp_file);

        function utf8ize($mixed)
        {
            if (is_array($mixed)) {
                foreach ($mixed as $key => $value) {
                    $mixed[$key] = utf8ize($value);
                }
            } elseif (is_string($mixed)) {
                return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
            }
            return $mixed;
        }

        return new Response(
            200,
            $headers,
            json_encode([
                "success" => true,
                "plugin" => utf8ize($plugin->getPluginInfo())["code"],
            ])
        );
    } catch (Exception $e) {
        return new Response(
            500,
            $headers,
            json_encode([
                "success" => false,
                "message" => "Error processing file: " . $e->getMessage(),
            ])
        );
    } finally {
        if (file_exists($temp_file)) {
            unlink($temp_file);
        }
    }
}
