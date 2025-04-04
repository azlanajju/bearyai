<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$apiKey = 'AIzaSyCVix472dUIn6AEMLIHc8Lulx-aARyIHeI';

session_start();
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

function formatChatHistory($history) {
    $formatted = [];
    foreach ($history as $index => $chat) {
        $formatted[] = [
            'index' => $index + 1,
            'user_message' => $chat['question'],
            'assistant_response' => $chat['answer'],
            'timestamp' => $chat['timestamp'] ?? date('Y-m-d H:i:s')
        ];
    }
    return $formatted;
}

function isAskingAboutPrevious($question) {
    $keywords = [
        'previous prompt',
        'previous question',
        'last question',
        'what did i ask',
        'earlier question',
        'before this',
        'last time'
    ];

    $question = strtolower(trim($question));

    foreach ($keywords as $keyword) {
        if (preg_match("/\b" . preg_quote($keyword, '/') . "\b/", $question)) {
            return true;
        }
    }

    return false;
}

function getGeminiResponse($prompt, $userQuestion, $apiKey) {
    $url = "https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent";

    $recentHistory = array_slice($_SESSION['chat_history'], -3);
    $contextHistory = "";
    foreach ($recentHistory as $chat) {
        $contextHistory .= "User: {$chat['question']}\nAssistant: {$chat['answer']}\n\n";
    }

    $fullPrompt = $prompt . "\n\nRecent Conversation:\n" . $contextHistory . "\nCurrent Question: " . $userQuestion;

    $postData = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $fullPrompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 1024,
        ]
    ];

    $headers = [
        "x-goog-api-key: $apiKey",
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    if ($response === false) {
        throw new Exception('Failed to connect to API: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("API Error: HTTP code $httpCode");
    }

    $result = json_decode($response, true);
    return $result['candidates'][0]['content']['parts'][0]['text']
           ?? throw new Exception('Unexpected response format');
}

try {
    $rawData = file_get_contents('php://input');
    if (!$rawData) {
        throw new Exception('No input received');
    }

    $data = json_decode($rawData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }

    if (empty($data['question'])) {
        throw new Exception('Question is required');
    }

    $question = trim($data['question']);

    if (isAskingAboutPrevious($question)) {
        if (empty($_SESSION['chat_history'])) {
            echo json_encode([
                'status' => 'success',
                'message' => 'No previous questions found in this conversation.',
                'history' => []
            ]);
            exit;
        }

        $history = formatChatHistory($_SESSION['chat_history']);
        $lastConversation = end($history);

        echo json_encode([
            'status' => 'success',
            'previous_question' => $lastConversation['user_message'],
            'previous_answer' => $lastConversation['assistant_response'],
            'timestamp' => $lastConversation['timestamp'],
            'full_history' => $history
        ]);
        exit;
    }

    // Load dataset from 'dataset.json'
    $datasetFile = 'dataset.json';
    if (!file_exists($datasetFile)) {
        throw new Exception('Dataset file not found');
    }

    $dataset = json_decode(file_get_contents($datasetFile), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Failed to read the dataset file');
    }

    // Load prompts from 'prompt.txt'
    $promptFile = 'prompt.txt';
    if (!file_exists($promptFile)) {
        throw new Exception('Prompt file not found');
    }

    $prompt = file_get_contents($promptFile);
    if ($prompt === false) {
        throw new Exception('Failed to read the prompt file');
    }

    // Construct the system prompt with dataset and pre-prompt content
    $systemPrompt = $prompt . "\n\nDATASET:\n";
    foreach ($dataset as $section => $content) {
        $systemPrompt .= strtoupper($section) . ":\n";
        foreach ($content as $key => $value) {
            $systemPrompt .= "- $key: $value\n";
        }
        $systemPrompt .= "\n";
    }

    $response = getGeminiResponse($systemPrompt, $question, $apiKey);

    $_SESSION['chat_history'][] = [
        'question' => $question,
        'answer' => $response,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    if (count($_SESSION['chat_history']) > 10) {
        array_shift($_SESSION['chat_history']);
    }

    echo json_encode([
        'status' => 'success',
        'answer' => $response,
        'conversation_id' => session_id(),
        'history_available' => true,
        'conversation_count' => count($_SESSION['chat_history'])
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
