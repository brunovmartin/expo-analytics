<?php
// Script para testar o novo sistema de sessões

date_default_timezone_set('America/Sao_Paulo');

echo "🧪 Testando Sistema de Sessões Analytics\n";
echo "=" . str_repeat("=", 50) . "\n\n";

$baseURL = 'http://localhost:8080';

// Função para fazer requisições HTTP
function makeRequest($url, $data = null, $method = 'GET') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            if (is_array($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            }
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'code' => 0];
    }
    
    return [
        'code' => $httpCode,
        'body' => $response,
        'data' => json_decode($response, true)
    ];
}

// Teste 1: Verificar status do sistema
echo "📡 Teste 1: Status do Sistema\n";
$result = makeRequest("$baseURL/status");
if ($result['code'] === 200) {
    echo "✅ Sistema online\n";
    echo "   Resposta: {$result['body']}\n";
} else {
    echo "❌ Sistema offline (código: {$result['code']})\n";
    exit(1);
}

echo "\n";

// Teste 2: Inicializar usuário
echo "📱 Teste 2: Inicialização de Usuário\n";
$userId = 'test-user-' . time();
$initData = [
    'userId' => $userId,
    'userData' => [
        'platform' => 'test',
        'appVersion' => '1.0.0',
        'framerate' => 10
    ],
    'timestamp' => time()
];

$result = makeRequest("$baseURL/init", json_encode($initData), 'POST');
if ($result['code'] === 200) {
    echo "✅ Usuário inicializado\n";
    echo "   ID: $userId\n";
    if (isset($result['data']['geo'])) {
        $geo = $result['data']['geo'];
        echo "   Localização: {$geo['flag']} {$geo['city']}, {$geo['region']}\n";
    }
} else {
    echo "❌ Erro na inicialização (código: {$result['code']})\n";
    echo "   Resposta: {$result['body']}\n";
}

echo "\n";

// Teste 3: Enviar alguns eventos
echo "🎯 Teste 3: Envio de Eventos\n";
$events = [
    ['event' => 'app_start', 'value' => 'test_session'],
    ['event' => 'button_click', 'value' => 'test_button'],
    ['event' => 'screen_view', 'value' => 'home_screen'],
    ['event' => 'user_action', 'value' => 'test_interaction']
];

foreach ($events as $event) {
    $eventData = [
        'userId' => $userId,
        'event' => $event['event'],
        'value' => $event['value'],
        'timestamp' => time(),
        'userData' => $initData['userData']
    ];
    
    $result = makeRequest("$baseURL/track", json_encode($eventData), 'POST');
    if ($result['code'] === 200) {
        echo "   ✅ Evento '{$event['event']}' enviado\n";
    } else {
        echo "   ❌ Erro no evento '{$event['event']}' (código: {$result['code']})\n";
    }
    
    usleep(500000); // 0.5 segundo entre eventos
}

echo "\n";

// Teste 4: Simular sessão de vídeo (criar ZIP fake para teste)
echo "🎬 Teste 4: Simulação de Sessão de Vídeo\n";

// Criar um ZIP simples com imagens de teste
$sessionId = 'test-session-' . time();
$tempDir = sys_get_temp_dir() . '/test-session-' . $sessionId;
mkdir($tempDir, 0755, true);

// Criar algumas imagens de teste (JPG válidas de 480x960)
echo "   🖼️ Criando imagens de teste...\n";
for ($i = 0; $i < 5; $i++) {
    // Criar uma imagem de teste de 480x960 pixels
    $image = imagecreatetruecolor(480, 960);
    
    // Cores aleatórias para cada frame
    $colors = [
        imagecolorallocate($image, 255, 0, 0),    // Vermelho
        imagecolorallocate($image, 0, 255, 0),    // Verde
        imagecolorallocate($image, 0, 0, 255),    // Azul
        imagecolorallocate($image, 255, 255, 0),  // Amarelo
        imagecolorallocate($image, 255, 0, 255)   // Magenta
    ];
    
    // Preencher com cor
    imagefill($image, 0, 0, $colors[$i]);
    
    // Adicionar texto indicando o frame
    $white = imagecolorallocate($image, 255, 255, 255);
    $text = "Frame " . ($i + 1) . " - Test Session";
    imagestring($image, 5, 150, 450, $text, $white);
    
    // Salvar como JPG
    $filename = "$tempDir/frame_" . sprintf('%06d', $i) . "_" . (time() + $i) . ".jpg";
    imagejpeg($image, $filename, 85); // Qualidade 85%
    imagedestroy($image);
}

// Criar ZIP
$zipFile = "$tempDir/session_test.zip";
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
    $files = glob("$tempDir/frame_*.jpg");
    foreach ($files as $file) {
        $zip->addFile($file, basename($file));
    }
    $zip->close();
    
    echo "   📦 ZIP de teste criado com " . count($files) . " imagens\n";
    echo "   Tamanho: " . number_format(filesize($zipFile) / 1024, 1) . " KB\n";
    
    // Metadados da sessão
    $sessionMetadata = [
        'userId' => $userId,
        'sessionId' => $sessionId,
        'timestamp' => time(),
        'sessionDuration' => 25.5, // 25.5 segundos
        'frameCount' => 5,
        'framerate' => 10,
        'userData' => $initData['userData'],
        'format' => 'zip'
    ];
    
    // Enviar via upload multipart
    $boundary = 'Boundary-' . uniqid();
    $postData = "--$boundary\r\n";
    $postData .= "Content-Disposition: form-data; name=\"metadata\"\r\n\r\n";
    $postData .= json_encode($sessionMetadata) . "\r\n";
    $postData .= "--$boundary\r\n";
    $postData .= "Content-Disposition: form-data; name=\"screenshots\"; filename=\"session_$sessionId.zip\"\r\n";
    $postData .= "Content-Type: application/zip\r\n\r\n";
    $postData .= file_get_contents($zipFile) . "\r\n";
    $postData .= "--$boundary--\r\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseURL/upload-zip");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: multipart/form-data; boundary=$boundary",
        "Content-Length: " . strlen($postData)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "   ✅ Sessão enviada com sucesso\n";
        echo "   SessionId: {$data['sessionId']}\n";
        echo "   Duração: {$data['sessionDuration']}s\n";
        echo "   Frames: {$data['frameCount']} (real: {$data['actualImageCount']})\n";
        echo "   Tamanho original: {$data['originalSize']}\n";
        echo "   Tamanho vídeo: {$data['videoSize']}\n";
        echo "   Compressão: {$data['compressionRatio']}\n";
        echo "   FPS efetivo: {$data['effectiveFPS']}\n";
    } else {
        echo "   ❌ Erro no upload da sessão (código: $httpCode)\n";
        echo "   Resposta: $response\n";
    }
    
    // Limpar arquivos temporários
    array_map('unlink', glob("$tempDir/*"));
    rmdir($tempDir);
    
} else {
    echo "   ❌ Erro ao criar ZIP de teste\n";
}

echo "\n";

// Teste 5: Verificar dados no dashboard
echo "📊 Teste 5: Verificação dos Dados\n";
echo "   Dashboard: $baseURL/dashboard.php\n";
echo "   Diagnóstico: $baseURL/diagnostico-sistema.php\n";

// Verificar se arquivos foram criados
$baseDir = __DIR__ . '/analytics-data';
$userEventsDir = "$baseDir/events/$userId";
$userVideosDir = "$baseDir/videos/$userId";

if (is_dir($userEventsDir)) {
    $eventFiles = glob("$userEventsDir/*/*.jsonl");
    echo "   ✅ " . count($eventFiles) . " arquivo(s) de eventos criados\n";
} else {
    echo "   ⚠️ Nenhum arquivo de eventos encontrado\n";
}

if (is_dir($userVideosDir)) {
    $videoFiles = glob("$userVideosDir/*/*.mp4");
    $sessionFiles = glob("$userVideosDir/*/session_*.json");
    echo "   ✅ " . count($videoFiles) . " vídeo(s) criados\n";
    echo "   ✅ " . count($sessionFiles) . " arquivo(s) de metadados de sessão\n";
} else {
    echo "   ⚠️ Nenhum vídeo encontrado\n";
}

echo "\n";

echo "🎉 Teste Concluído!\n";
echo "\n";
echo "📋 Resumo:\n";
echo "   - Usuário: $userId\n";
echo "   - Eventos: " . count($events) . " enviados\n";
echo "   - Sessão: $sessionId\n";
echo "   - Duração da sessão: 25.5s\n";
echo "   - Sistema: " . ($result['code'] === 200 ? "Funcionando" : "Com problemas") . "\n";
echo "\n";
echo "🔗 Próximos passos:\n";
echo "   1. Abrir dashboard: $baseURL/dashboard.php\n";
echo "   2. Verificar usuário: $userId\n";
echo "   3. Ver linha do tempo de eventos\n";
echo "   4. Reproduzir vídeo da sessão\n";
echo "   5. Testar no app iOS com comportamento correto\n";

?> 