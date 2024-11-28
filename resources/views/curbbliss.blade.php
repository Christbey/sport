<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $webhook_url = "https://hooks.slack.com/services/T07J9LQCWBB/B08235KG2JW/p2u43LpuHDFQDURRi3eLVM7";
    $fields = ['name', 'email', 'phone', 'subject', 'comment'];
    $data = array_map(fn($field) => htmlspecialchars($_POST[$field] ?? 'N/A'), $fields);

    $message = [
        "text" => "New Contact Form Submission",
        "blocks" => [
            ["type" => "section", "text" => ["type" => "mrkdwn", "text" => "*New Contact Form Submission*"]],
            ["type" => "section", "fields" => array_map(fn($field, $value) => ["type" => "mrkdwn", "text" => "*$field:* $value"], $fields, $data)]
        ]
    ];

    $ch = curl_init($webhook_url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($message),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        header("Location: /thank-you.html");
        exit;
    } else {
        echo "Something went wrong. Please try again later.";
    }
} else {
    header("Location: /");
    exit;

    error_log(print_r($response, true)); // Log Slack's response
    error_log(print_r($http_code, true)); // Log HTTP status code

}