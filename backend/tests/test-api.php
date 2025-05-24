<?php
// Script de teste para a API local
// Uso: php test-api.php (executar da pasta backend)

$apiUrl = 'http://localhost:8080';

echo "🧪 Testando API Analytics...\n\n";

// Função para fazer requisições
function makeRequest($url, $data = null, $method = 'GET') {
    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => 'Content-Type: application/json',
            'content' => $data ? json_encode($data) : null
        ]
    ]);
    
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result, true);
    
    return $response;
}

// Test 1: Status
echo "1️⃣ Testando /status...\n";
try {
    $response = makeRequest($apiUrl . '/status');
    echo "✅ Status: " . $response['status'] . "\n";
    echo "📅 Data: " . $response['date'] . "\n\n";
} catch (Exception $e) {
    echo "❌ Erro no status: " . $e->getMessage() . "\n\n";
}

// Test 2: Init
echo "2️⃣ Testando /init...\n";
try {
    $userData = [
        'userId' => 'test_user_' . time(),
        'userData' => [
            'appVersion' => '1.0.0',
            'platform' => 'test'
        ],
        'geo' => [
            'country' => 'BR',
            'city' => 'Test City'
        ]
    ];
    
    $response = makeRequest($apiUrl . '/init', $userData, 'POST');
    echo "✅ Init: " . ($response['success'] ? 'Sucesso' : 'Falha') . "\n\n";
} catch (Exception $e) {
    echo "❌ Erro no init: " . $e->getMessage() . "\n\n";
}

// Test 3: Track Event
echo "3️⃣ Testando /track...\n";
try {
    $eventData = [
        'userId' => 'test_user_' . time(),
        'event' => 'test_event',
        'value' => 'test_value',
        'timestamp' => time(),
        'userData' => ['test' => true],
        'geo' => ['country' => 'BR']
    ];
    
    $response = makeRequest($apiUrl . '/track', $eventData, 'POST');
    echo "✅ Track: " . ($response['success'] ? 'Sucesso' : 'Falha') . "\n\n";
} catch (Exception $e) {
    echo "❌ Erro no track: " . $e->getMessage() . "\n\n";
}

// Test 4: Upload (com imagem fake)
echo "4️⃣ Testando /upload...\n";
try {
    // Criar uma imagem PNG 1x1 em base64 para teste
    $fakeImage = base64_encode(file_get_contents('data://image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
    
    $uploadData = [
        'userId' => 'test_user_' . time(),
        'timestamp' => time(),
        'userData' => ['test' => true],
        'geo' => ['country' => 'BR'],
        'images' => [$fakeImage]
    ];
    
    $response = makeRequest($apiUrl . '/upload', $uploadData, 'POST');
    echo "✅ Upload: " . ($response['success'] ? 'Sucesso' : 'Falha') . "\n";
    if (isset($response['saved'])) {
        echo "📸 " . $response['saved'] . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Erro no upload: " . $e->getMessage() . "\n\n";
}

echo "🏁 Testes concluídos!\n";
echo "📁 Verifique a pasta analytics-data/ para ver os dados salvos.\n";
?> 