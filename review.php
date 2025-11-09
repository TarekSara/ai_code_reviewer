<?php

$config= require "config.php";
$apikey= $config['api_key'];
$severities= $config['severities'];

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
echo json_encode(["error" => "Only POST allowed"]);
exit;
}

$contentType = $_SERVER["CONTENT_TYPE"] ?? '';
if (!preg_match("/application\/json/i", $contentType)) {
echo json_encode(["error" => "Content-Type must be application/json"]);
exit;
}

$php_input = file_get_contents("php://input");
$data = json_decode($php_input,true);

if(!$data || !isset($data["code"]) || !isset($data["file"])) {
echo json_encode(["error"=>"Missing code or file in request"]);
exit;
}

$file= $data["file"];
$code= $data["code"];
$prompt = "You are an expert code reviewer.
You will receive a code snippet and return a JSON array containing **exactly one review item**.
The review item must strictly follow this format:
[
  {
    \"severity\": \"low | medium | high\",
    \"file\": \"string\",
    \"issue\": \"short identifier of the most important problem\",
    \"suggestion\": \"concrete remediation step\"
  }
]
Code file name: $file
Code snippet:
$code
Return only the JSON array, no explanations.";


$url = "https://api.openai.com/v1/chat/completions";

$postData = [
"model" => "gpt-4",
"messages" => [ ["role" => "user", "content" => $prompt] ],
"temperature" => 0
];

$options = [
"http" => [
"header" => "Content-type: application/json\r\nAuthorization: Bearer $apikey\r\n",
"method" => "POST",
"content" => json_encode($postData)
]
];

$context = stream_context_create($options);
$result = @file_get_contents($url, false, $context);

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
?>