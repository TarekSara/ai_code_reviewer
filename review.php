<?php

$config= require "config.php";
$apikey= $config['api_key'];
$severities= $config['severities'];

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"]=== 'POST' && !empty($_SERVER['CONTENT_TYPE']) 
&& preg_match("/application\/json/i") && $_SERVER['CONTENT_TYPE']){

    $php_input = file_get_contents("php://input");

    $data = json_decode($php_input,true);

    if(!$data || !isset($data["code"]) || !isset($data["file"])){
        echo json_encode(["error"=>"Missing code or file in request"]);
        exit;
    }
        $file= $data["file"];
        $code= $data["code"];

};

$prompt ="Review this code and return a JSON array with fields: severity, file, issue, suggestion.
File: $file
code: $code";

$url = "https://api.openai.com/v1/chat/completions";

$data = [
    "model" => "gpt-4",
    "messages" => [ ["role" => "user", "content" => $prompt] ],
    "temperature" => 0
];

$options = [
    "http" => [
        "header"  => "Content-type: application/json\r\n" ."Authorization: Bearer $apikey\r\n",
        "method"  => "POST",
        "content" => json_encode($data)
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    $review = [
        [
            "file" => $file,
            "severity" => "low",
            "issue" => "Failed to contact AI API",
            "suggestion" => "Check API Key or connection"
        ]
    ];

} else {
    $resJson = json_decode($result, true);
    $aiText = $resJson["choices"][0]["message"]["content"] ?? "";


    $review = json_decode($aiText, true);

    if (!$review) {
        $review = [
            [
                "file" => $file,
                "severity" => "low",
                "issue" => "AI failed to generate JSON",
                "suggestion" => "Check API response"
            ]
        ];
    }
}
echo json_encode($review);
