<?php

header("Content-Type:application/json");
require_once "config.php";

$input= json_decode(file_get_contents("php://input"),true);


if (!$input || !isset($input["code"]) || !isset($input["file"])){
    echo json_encode(["error" => "Invalid input format expected JSON with code and file"]);
}

$file=$input["file"];
$code=$input["code"];
$prompt="You are an expert code reviewer.
You will receive a code snippet and return a JSON array of review items.
Each item MUST strictly follow this format:
[
  {
    \"severity\": \"low | medium | high\",
    \"file\": \"string\",
    \"issue\": \"short identifier of the problem\",
    \"suggestion\": \"concrete remediation step\"
  }
]
Code file name: $file
Code snippet:$code
Return only the JSON array, no explanations.
";
$ch=curl_init("https://api.openai.com/v1/chat/completions");
$curl_setopt_array($ch,[
    CURLOPT_RETURNTRANSFER =>true,
    CURLOPT_HTTPHEADER => [
        "Content-Type:application/json",
        "Authorization : Bearer" . OPENAI_KEY
    ],
    CURLOPT_POST =>true,
    CURLOPT_POSTFIELDS => json_encode([
        "model" => "gpt-4o-mini",
        "messages" => [["role"=> "user", "content " => $prompt]],
        "temprature" =>0.1
    ])
]);

$response=curl_exec($ch);

if (curl_errno($ch)){
    echo json_encode(["error" => curl_error($ch)]);
}
curl_close($ch);
$data=json_decode($response,true);
$ai_reply =$data["choices"][0]["message"]["content"];
$json_output=json_decode($ai_reply,true);

$allowed=ALLOWED_SEVERITIES;
$validated=[];
foreach($json_output as $item){
    if (isset($item["file"],$item["severity"],$item["issue"],$item["suggestion"] && in_array($item["severity"],allowed)))
{
    $validated[]=
    [
        "file" => $item["file"],
        "severity" => $item["severity"],
        "issue" => $item["issue"],
        "suggestion" =>$item["suggestion"]
    ];
}}

if (empty($validated)) {
    $validated[] = [
        "file" => $file,
        "severity" => "low",
        "issue" => "No valid issues found or schema mismatch",
        "suggestion" => "Ensure the AI returns correct structure"
    ];
}
echo json_encode($validated,JSON_PRETTY_PRINT);
?>