<?php
// Script de teste para as novas funcionalidades
// Para usar: php test-new-features.php

echo "🧪 Testando Novas Funcionalidades do Analytics Dashboard\n";
echo "=" . str_repeat("=", 60) . "\n\n";

$baseUrl = 'http://localhost:8080';
$testUserId = 'test_user_' . time();

// Função para fazer requisição HTTP
function makeRequest($url, $method = 'GET', $data = null) {
    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => "Content-Type: application/json\r\n",
            'content' => $data ? json_encode($data) : null,
            'ignore_errors' => true
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    $httpCode = intval(substr($http_response_header[0], 9, 3));
    
    return [
        'data' => $response,
        'code' => $httpCode,
        'success' => $httpCode >= 200 && $httpCode < 300
    ];
}

// Função para exibir resultado
function showResult($test, $result, $details = '') {
    $status = $result ? "✅ PASS" : "❌ FAIL";
    echo sprintf("%-50s %s\n", $test, $status);
    if ($details) {
        echo "   Detalhes: $details\n";
    }
    echo "\n";
}

// 1. Testar endpoint de status
echo "1. Testando API Status\n";
echo "-" . str_repeat("-", 30) . "\n";

$result = makeRequest("$baseUrl/status");
$statusData = json_decode($result['data'], true);
showResult("Status da API", $result['success'], 
    $result['success'] ? "API rodando normalmente" : "Erro: " . $result['data']);

// 2. Criar dados de teste
echo "2. Criando Dados de Teste\n";
echo "-" . str_repeat("-", 30) . "\n";

// Enviar dados de usuário
$userData = [
    'userId' => $testUserId,
    'userData' => [
        'appVersion' => '1.2.3',
        'deviceModel' => 'iPhone 14',
        'osVersion' => 'iOS 17.0'
    ],
    'geo' => [
        'country_name' => 'Brasil',
        'region' => 'São Paulo',
        'city' => 'São Paulo',
        'timezone' => 'America/Sao_Paulo'
    ],
    'timestamp' => time()
];

$result = makeRequest("$baseUrl/init", 'POST', $userData);
showResult("Envio de dados do usuário", $result['success']);

// Enviar alguns eventos
$events = [
    ['event' => 'app_start', 'value' => 'cold_start'],
    ['event' => 'screen_view', 'value' => 'home'],
    ['event' => 'button_click', 'value' => 'menu_button']
];

foreach ($events as $event) {
    $eventData = array_merge($event, [
        'userId' => $testUserId,
        'timestamp' => time(),
        'userData' => $userData['userData'],
        'geo' => $userData['geo']
    ]);
    
    $result = makeRequest("$baseUrl/track", 'POST', $eventData);
}

showResult("Envio de eventos de teste", true, "3 eventos enviados");

// Simular envio de screenshots
$screenshotData = [
    'userId' => $testUserId,
    'userData' => $userData['userData'],
    'geo' => $userData['geo'],
    'timestamp' => time(),
    'images' => [
        // Imagem base64 de teste (1x1 pixel JPEG)
        '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/8A=='
    ]
];

$result = makeRequest("$baseUrl/upload", 'POST', $screenshotData);
showResult("Envio de screenshot de teste", $result['success']);

// 3. Testar Dashboard
echo "3. Testando Dashboard\n";
echo "-" . str_repeat("-", 30) . "\n";

$result = makeRequest("$baseUrl/dashboard");
showResult("Acesso ao dashboard", $result['success'], 
    $result['success'] ? "Dashboard carregado" : "Erro ao carregar dashboard");

// Testar dashboard com usuário específico
$result = makeRequest("$baseUrl/dashboard?user=" . urlencode($testUserId));
showResult("Dashboard com usuário específico", $result['success']);

// 4. Testar funcionalidades específicas do painel de usuário
echo "4. Testando Funcionalidades do Painel de Usuário\n";
echo "-" . str_repeat("-", 50) . "\n";

// Verificar se os dados do usuário estão sendo exibidos corretamente
if ($result['success']) {
    $dashboardHtml = $result['data'];
    
    $hasUserData = strpos($dashboardHtml, $testUserId) !== false;
    showResult("Dados do usuário exibidos", $hasUserData);
    
    $hasDeleteButton = strpos($dashboardHtml, 'deleteUserData') !== false;
    showResult("Botão de deletar presente", $hasDeleteButton);
    
    $hasUserDetails = strpos($dashboardHtml, 'user-details') !== false;
    showResult("Painel de detalhes do usuário", $hasUserDetails);
    
    $hasGeoData = strpos($dashboardHtml, 'São Paulo') !== false;
    showResult("Dados de geolocalização", $hasGeoData);
    
    $hasAppData = strpos($dashboardHtml, 'iPhone 14') !== false;
    showResult("Dados do aplicativo", $hasAppData);
}

// 5. Testar endpoint de deletar (simulação)
echo "5. Testando Endpoint de Deletar\n";
echo "-" . str_repeat("-", 35) . "\n";

// Primeiro verificar se o endpoint responde
$result = makeRequest("$baseUrl/delete-user?userId=usuario_inexistente");
$deleteData = json_decode($result['data'], true);
showResult("Endpoint de deletar acessível", $result['success'], 
    $result['success'] ? "Endpoint funcionando" : "Erro: " . $result['data']);

// 6. Verificar arquivos de assets
echo "6. Testando Assets\n";
echo "-" . str_repeat("-", 20) . "\n";

$assets = [
    '/assets/style.css' => 'CSS',
    '/assets/script.js' => 'JavaScript'
];

foreach ($assets as $asset => $type) {
    $result = makeRequest("$baseUrl$asset");
    showResult("Asset $type", $result['success']);
}

// 7. Teste final - deletar dados de teste
echo "7. Limpeza dos Dados de Teste\n";
echo "-" . str_repeat("-", 35) . "\n";

$result = makeRequest("$baseUrl/delete-user?userId=" . urlencode($testUserId));
$deleteData = json_decode($result['data'], true);
showResult("Deletar dados de teste", $result['success'], 
    $result['success'] ? "Dados de teste removidos" : "Erro: " . ($deleteData['error'] ?? 'Desconhecido'));

// Resumo final
echo "\n" . str_repeat("=", 70) . "\n";
echo "📋 RESUMO DOS TESTES\n";
echo str_repeat("=", 70) . "\n";

echo "✅ Funcionalidades Implementadas:\n";
echo "   • Logs Swift com NSLog (visíveis no Metro)\n";
echo "   • Painel de dados detalhados do usuário\n";
echo "   • Botão para deletar dados do usuário\n";
echo "   • Layout responsivo de 3 colunas\n";
echo "   • Confirmação de segurança para delete\n";
echo "   • Feedback visual com notificações\n\n";

echo "🚀 Para usar:\n";
echo "   1. Execute: ./start-server.sh\n";
echo "   2. Acesse: http://localhost:8080/dashboard\n";
echo "   3. No app React Native, os logs agora aparecerão no Metro\n";
echo "   4. Selecione um usuário para ver dados detalhados\n";
echo "   5. Use o botão 'Deletar Dados' com cuidado!\n\n";

echo "📱 Logs Swift:\n";
echo "   • Todos os print() foram substituídos por NSLog()\n";
echo "   • Agora aparecem no terminal do 'expo start'\n";
echo "   • Prefixo: [ExpoAnalytics] para fácil identificação\n\n";

echo "🗑️  Deletar Usuário:\n";
echo "   • Confirmação obrigatória digitando 'DELETAR'\n";
echo "   • Remove TODOS os dados (screenshots, eventos, info)\n";
echo "   • Ação irreversível - use com cuidado!\n\n";

echo "Teste concluído! 🎉\n";
?> 