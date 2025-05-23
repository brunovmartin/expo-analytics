<?php
// Script PHP simples para receber dados do Expo Analytics
// Para usar: php -S localhost:8080 api-receiver.php

// Configurações
date_default_timezone_set('America/Sao_Paulo');
$baseDir = __DIR__ . '/analytics-data';

// Função para criar diretórios se não existirem
function ensureDir($path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

// Função para salvar log
function saveLog($message) {
    global $baseDir;
    ensureDir($baseDir . '/logs');
    $logFile = $baseDir . '/logs/' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

// Função para responder com JSON
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Headers CORS para permitir requisições do app
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Obter dados da requisição
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$input = file_get_contents('php://input');
$data = json_decode($input, true);

saveLog("$method $uri - " . strlen($input) . " bytes");

// Roteamento
switch (true) {
    case $uri === '/upload':
        handleUpload($data);
        break;
    
    case $uri === '/upload-zip':
        handleUploadZip();
        break;
    
    case $uri === '/track':
        handleTrack($data);
        break;
    
    case $uri === '/init':
        handleInit($data);
        break;
    
    case $uri === '/take-screenshot':
        handleTakeScreenshot($data);
        break;
    
    case $uri === '/' || $uri === '/status':
        handleStatus();
        break;
    
    case $uri === '/delete-user':
        handleDeleteUser();
        break;
    
    case $uri === '/apps':
        if ($method === 'GET') {
            handleGetApps();
        } elseif ($method === 'POST') {
            handleCreateApp($data);
        } elseif ($method === 'PUT') {
            handleUpdateApp($data);
        } elseif ($method === 'DELETE') {
            handleDeleteApp();
        }
        break;
    
    case $uri === '/app-config':
        handleGetAppConfig();
        break;
    
    case $uri === '/dashboard.php' || $uri === '/dashboard':
        // Headers no-cache para HTML
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        // Incluir o dashboard diretamente
        include __DIR__ . '/dashboard.php';
        break;
    
    case strpos($uri, '/view-screenshot.php') === 0:
        // Incluir o visualizador de screenshots
        include __DIR__ . '/view-screenshot.php';
        break;
    
    case strpos($uri, '/view-video.php') === 0:
        // Incluir o visualizador de vídeos
        include __DIR__ . '/view-video.php';
        break;
    
    case strpos($uri, '/session-data.php') === 0:
        // Incluir a API de dados de sessão
        include __DIR__ . '/session-data.php';
        break;
    
    case $uri === '/index.html' || $uri === '/home':
        // Headers no-cache para HTML
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Type: text/html');
        // Servir a página inicial
        $indexContent = file_get_contents(__DIR__ . '/index.html');
        echo $indexContent;
        break;
    
    case strpos($uri, '/assets/') === 0:
        // Servir arquivos de assets (CSS, JS) SEM CACHE
        $filePath = __DIR__ . $uri;
        if (file_exists($filePath)) {
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $mimeTypes = [
                'css' => 'text/css',
                'js' => 'application/javascript',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml'
            ];
            
            if (isset($mimeTypes[$extension])) {
                header('Content-Type: ' . $mimeTypes[$extension]);
                
                // No-cache para CSS e JS
                if ($extension === 'css' || $extension === 'js') {
                    header('Cache-Control: no-cache, no-store, must-revalidate');
                    header('Pragma: no-cache');
                    header('Expires: 0');
                } else {
                    // Cache para imagens (podem ser cacheadas)
                    header('Cache-Control: public, max-age=86400');
                }
                
                readfile($filePath);
            } else {
                http_response_code(404);
                jsonResponse(['error' => 'File type not supported'], 404);
            }
        } else {
            http_response_code(404);
            jsonResponse(['error' => 'Asset not found'], 404);
        }
        break;
    
    case strpos($uri, '/event-screenshot.php') === 0:
        // Incluir o visualizador de screenshots de eventos
        include __DIR__ . '/event-screenshot.php';
        break;
    
    case strpos($uri, '/test-modal.html') === 0:
        // Servir arquivo de teste
        header('Content-Type: text/html; charset=UTF-8');
        readfile(__DIR__ . '/test-modal.html');
        break;
    
    default:
        jsonResponse(['error' => 'Endpoint not found'], 404);
}

// Handler para upload de screenshots
function handleUpload($data) {
    global $baseDir;
    
    // Verificar se dados estão comprimidos
    $contentEncoding = $_SERVER['HTTP_CONTENT_ENCODING'] ?? '';
    $input = file_get_contents('php://input');
    $originalInputSize = strlen($input);
    
    if ($contentEncoding === 'gzip') {
        // Tentar diferentes métodos de descompressão
        $decompressed = false;
        
        // Método 1: gzuncompress (para dados gzip simples)
        if (!$decompressed) {
            $decompressed = @gzuncompress($input);
        }
        
        // Método 2: gzdecode (para dados gzip com header)
        if (!$decompressed) {
            $decompressed = @gzdecode($input);
        }
        
        // Método 3: gzinflate (para dados deflate)
        if (!$decompressed) {
            $decompressed = @gzinflate($input);
        }
        
        if ($decompressed !== false) {
            $input = $decompressed;
            $decompressedSize = strlen($input);
            $compressionRatio = round((1 - $originalInputSize / $decompressedSize) * 100, 1);
            saveLog("📦 Dados descomprimidos: " . formatBytes($originalInputSize) . " → " . formatBytes($decompressedSize) . " (compressão: {$compressionRatio}%)");
        } else {
            saveLog("❌ Erro ao descomprimir dados gzip, usando dados originais");
            // Continuar com dados originais em caso de erro
        }
    } else {
        saveLog("📥 Dados não comprimidos recebidos: " . formatBytes($originalInputSize));
    }
    
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['userId'])) {
        saveLog("❌ Dados inválidos ou userId ausente");
        jsonResponse(['error' => 'Invalid data'], 400);
    }
    
    $userId = $data['userId'];
    $timestamp = isset($data['timestamp']) ? (int)$data['timestamp'] : time();
    $date = date('Y-m-d', $timestamp);
    
    // Criar diretórios
    $userDir = $baseDir . '/screenshots/' . $userId . '/' . $date;
    ensureDir($userDir);
    
    // Salvar metadados
    $metadata = [
        'userId' => $userId,
        'timestamp' => $timestamp,
        'userData' => $data['userData'] ?? [],
        'geo' => $data['geo'] ?? [],
        'receivedAt' => time(),
        'imageCount' => 0,
        'compressionUsed' => $contentEncoding === 'gzip',
        'originalDataSize' => $originalInputSize,
        'decompressedDataSize' => strlen($input)
    ];
    
    // Salvar imagens se existirem
    if (isset($data['images']) && is_array($data['images'])) {
        $metadata['imageCount'] = count($data['images']);
        $totalImageSize = 0;
        $imagesSizes = [];
        
        foreach ($data['images'] as $index => $base64Image) {
            $imageData = base64_decode($base64Image);
            if ($imageData !== false) {
                $imageSize = strlen($imageData);
                $totalImageSize += $imageSize;
                $imagesSizes[] = round($imageSize / 1024, 1); // KB
                
                // Verificar se é uma imagem JPEG válida
                $imageInfo = getimagesizefromstring($imageData);
                if ($imageInfo !== false) {
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                    
                    // Verificar se as dimensões estão corretas (480x960)
                    if ($width == 480 && $height == 960) {
                        $imageName = sprintf('screenshot_%d_%03d.jpg', $timestamp, $index);
                        file_put_contents($userDir . '/' . $imageName, $imageData);
                    } else {
                        saveLog("⚠️ Imagem $index com dimensões incorretas: {$width}x{$height} (esperado: 480x960)");
                        // Ainda salva, mas com um nome diferente para debug
                        $imageName = sprintf('screenshot_%d_%03d_wrong_size_%dx%d.jpg', $timestamp, $index, $width, $height);
                        file_put_contents($userDir . '/' . $imageName, $imageData);
                    }
                } else {
                    saveLog("❌ Imagem $index não é um JPEG válido");
                }
            }
        }
        
        $metadata['totalImageSize'] = $totalImageSize;
        $metadata['averageImageSize'] = $metadata['imageCount'] > 0 ? round($totalImageSize / $metadata['imageCount'] / 1024, 1) : 0; // KB
        $metadata['imagesSizesKB'] = array_slice($imagesSizes, 0, 5); // Primeiras 5 para debug
        
        saveLog("📸 {$metadata['imageCount']} imagens processadas - Total: " . formatBytes($totalImageSize) . " - Média: {$metadata['averageImageSize']}KB");
    }
    
    // Salvar metadados
    $metadataFile = $userDir . '/metadata_' . $timestamp . '.json';
    file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
    
    $compressionInfo = $contentEncoding === 'gzip' ? " (gzip)" : "";
    saveLog("✅ Upload salvo para usuário $userId{$compressionInfo} - {$metadata['imageCount']} imagens - {$metadata['averageImageSize']}KB média");
    
    jsonResponse([
        'success' => true, 
        'saved' => $metadata['imageCount'] . ' images',
        'totalSize' => formatBytes($totalImageSize ?? 0),
        'averageSize' => $metadata['averageImageSize'] . 'KB',
        'compression' => $contentEncoding === 'gzip'
    ]);
}

// Função auxiliar para formatar bytes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Handler para eventos
function handleTrack($data = null) {
    global $baseDir;
    
    // Verificar se é um upload multipart (com screenshot)
    $hasScreenshot = false;
    $screenshotData = null;
    
    if ($_SERVER['CONTENT_TYPE'] && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
        // Processar dados multipart
        if (isset($_POST['eventData'])) {
            $data = json_decode($_POST['eventData'], true);
        }
        
        // Processar screenshot se presente
        if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
            $screenshotData = file_get_contents($_FILES['screenshot']['tmp_name']);
            $hasScreenshot = true;
        }
    }
    
    if (!$data || !isset($data['userId'])) {
        jsonResponse(['error' => 'Invalid data'], 400);
    }
    
    $userId = $data['userId'];
    $eventName = $data['event'] ?? 'unknown';
    $timestamp = $data['timestamp'] ?? time();
    $date = date('Y-m-d', (int)$timestamp);
    
    // Buscar dados geográficos pelo IP do usuário (cache por sessão)
    static $geoCache = [];
    $clientIP = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    if (!isset($geoCache[$clientIP])) {
        $geoCache[$clientIP] = fetchGeoInfo($clientIP);
    }
    $geoData = $geoCache[$clientIP];
    
    // Criar diretório para eventos
    $eventsDir = $baseDir . '/events/' . $userId . '/' . $date;
    ensureDir($eventsDir);
    
    // Salvar screenshot se presente
    $screenshotPath = null;
    if ($hasScreenshot && $screenshotData) {
        $screenshotsDir = $baseDir . '/events-screenshots/' . $userId . '/' . $date;
        ensureDir($screenshotsDir);
        
        $screenshotFilename = 'event_' . (int)$timestamp . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $eventName) . '.jpg';
        $screenshotPath = $screenshotsDir . '/' . $screenshotFilename;
        
        if (file_put_contents($screenshotPath, $screenshotData)) {
            $screenshotSize = strlen($screenshotData);
            saveLog("📸 Screenshot do evento salvo: $screenshotFilename (" . formatBytes($screenshotSize) . ")");
        } else {
            saveLog("❌ Erro ao salvar screenshot do evento");
            $screenshotPath = null;
        }
    }
    
    // Preparar dados do evento
    $event = [
        'userId' => $userId,
        'event' => $eventName,
        'value' => $data['value'] ?? '',
        'timestamp' => (int)$timestamp,
        'userData' => $data['userData'] ?? [],
        'geo' => $geoData,
        'hasScreenshot' => $hasScreenshot,
        'screenshotPath' => $screenshotPath ? basename($screenshotPath) : null,
        'screenshotSize' => $hasScreenshot ? strlen($screenshotData ?? '') : 0,
        'receivedAt' => time()
    ];
    
    // Salvar evento
    $eventFile = $eventsDir . '/events_' . date('H') . '.jsonl';
    file_put_contents($eventFile, json_encode($event) . "\n", FILE_APPEND | LOCK_EX);
    
    $screenshotInfo = $hasScreenshot ? " + screenshot" : "";
    saveLog("📝 Evento '$eventName' do usuário $userId{$screenshotInfo} - {$geoData['flag']} {$geoData['city']}");
    
    jsonResponse([
        'success' => true, 
        'event' => $eventName,
        'hasScreenshot' => $hasScreenshot,
        'screenshotSize' => $hasScreenshot ? formatBytes(strlen($screenshotData ?? '')) : null
    ]);
}

// Handler para inicialização/info do usuário
function handleInit($data) {
    global $baseDir;
    
    if (!$data || !isset($data['userId'])) {
        jsonResponse(['error' => 'Invalid data'], 400);
    }
    
    $userId = $data['userId'];
    $timestamp = $data['timestamp'] ?? time();
    $date = date('Y-m-d', (int)$timestamp);
    
    // Buscar dados geográficos pelo IP do usuário
    $geoData = fetchGeoInfo();
    
    // Criar diretório
    $userDir = $baseDir . '/users/' . $userId;
    ensureDir($userDir);
    
    // Extrair informações detalhadas da userData
    $userData = $data['userData'] ?? [];
    
    // PROCESSAR DADOS DE DISPOSITIVO NO BACKEND
    $userData = processDeviceInfo($userData);
    
    $deviceInfo = $userData['device'] ?? 'Unknown Device';
    $appVersion = $userData['appVersion'] ?? '1.0.0';
    $bundleId = $userData['bundleId'] ?? 'unknown.app';
    $platform = $userData['platform'] ?? 'iOS';
    
    // Preparar dados do usuário com informações organizadas
    $userInfo = [
        'userId' => $userId,
        'userData' => $userData,
        'deviceInfo' => [
            'device' => $deviceInfo,
            'platform' => $platform,
            'appVersion' => $appVersion,
            'bundleId' => $bundleId
        ],
        'geo' => $geoData,
        'timestamp' => (int)$timestamp,
        'receivedAt' => time(),
        'date' => $date,
        'sessionData' => [
            'isFirstInit' => !file_exists($userDir . '/latest.json'),
            'lastSeen' => time()
        ]
    ];
    
    // Salvar informações do usuário
    $userFile = $userDir . '/info_' . date('Y-m-d_H-i-s', (int)$timestamp) . '.json';
    file_put_contents($userFile, json_encode($userInfo, JSON_PRETTY_PRINT));
    
    // Atualizar arquivo de usuário mais recente
    $latestFile = $userDir . '/latest.json';
    file_put_contents($latestFile, json_encode($userInfo, JSON_PRETTY_PRINT));
    
    $newUserFlag = $userInfo['sessionData']['isFirstInit'] ? ' (NOVO)' : '';
    saveLog("👤 Usuário $userId{$newUserFlag} - $deviceInfo - $appVersion - {$geoData['flag']} {$geoData['city']}");
    
    jsonResponse([
        'success' => true, 
        'geo' => $geoData,
        'device' => $deviceInfo,
        'isFirstInit' => $userInfo['sessionData']['isFirstInit']
    ]);
}

// Handler para screenshots manuais
function handleTakeScreenshot($data) {
    global $baseDir;
    
    if (!$data || !isset($data['userId']) || !isset($data['screenshotData'])) {
        jsonResponse(['error' => 'Invalid data or missing screenshot'], 400);
    }
    
    $userId = $data['userId'];
    $screenshotBase64 = $data['screenshotData'];
    $width = $data['width'] ?? 'unknown';
    $height = $data['height'] ?? 'unknown';
    $compression = $data['compression'] ?? 0.8;
    $timestamp = $data['timestamp'] ?? time();
    $date = date('Y-m-d', (int)$timestamp);
    $type = $data['type'] ?? 'manual';
    
    // Decodificar screenshot
    $screenshotData = base64_decode($screenshotBase64);
    if ($screenshotData === false) {
        jsonResponse(['error' => 'Invalid base64 screenshot data'], 400);
    }
    
    // Buscar dados geográficos pelo IP do usuário
    $geoData = fetchGeoInfo();
    
    // Salvar na pasta screenshots (mesma dos screenshots de sessão) para aparecer na aba Screenshots
    $screenshotsDir = $baseDir . '/screenshots/' . $userId . '/' . $date;
    ensureDir($screenshotsDir);
    
    // Nome do arquivo incluindo tipo manual
    $screenshotFilename = 'manual_screenshot_' . (int)$timestamp . '_' . $width . 'x' . $height . '.jpg';
    $screenshotPath = $screenshotsDir . '/' . $screenshotFilename;
    
    if (file_put_contents($screenshotPath, $screenshotData)) {
        $screenshotSize = strlen($screenshotData);
        saveLog("📸 Screenshot manual salvo: $screenshotFilename (" . formatBytes($screenshotSize) . ")");
        
        // Salvar metadados do screenshot manual na mesma pasta
        $metadata = [
            'userId' => $userId,
            'type' => 'manual_screenshot',
            'timestamp' => (int)$timestamp,
            'width' => $width,
            'height' => $height,
            'compression' => $compression,
            'size' => $screenshotSize,
            'filename' => $screenshotFilename,
            'geo' => $geoData,
            'userData' => $data['userData'] ?? [],
            'receivedAt' => time(),
            'isManual' => true
        ];
        
        $metadataFile = $screenshotsDir . '/metadata_manual_' . (int)$timestamp . '.json';
        file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
        
        jsonResponse([
            'success' => true,
            'message' => 'Screenshot manual salvo com sucesso',
            'filename' => $screenshotFilename,
            'size' => formatBytes($screenshotSize),
            'dimensions' => $width . 'x' . $height,
            'compression' => $compression,
            'savedTo' => 'screenshots'
        ]);
    } else {
        saveLog("❌ Erro ao salvar screenshot manual");
        jsonResponse(['error' => 'Failed to save screenshot'], 500);
    }
}

// Handler para status
function handleStatus() {
    global $baseDir;
    
    $stats = [
        'status' => 'running',
        'timestamp' => time(),
        'date' => date('Y-m-d H:i:s'),
        'dataDir' => $baseDir,
        'endpoints' => [
            '/upload' => 'POST - Recebe screenshots',
            '/track' => 'POST - Recebe eventos',
            '/init' => 'POST - Recebe info do usuário',
            '/status' => 'GET - Status da API'
        ]
    ];
    
    // Estatísticas básicas se o diretório existir
    if (is_dir($baseDir)) {
        $stats['directories'] = [
            'screenshots' => is_dir($baseDir . '/screenshots'),
            'events' => is_dir($baseDir . '/events'),
            'users' => is_dir($baseDir . '/users'),
            'logs' => is_dir($baseDir . '/logs')
        ];
    }
    
    jsonResponse($stats);
}

// Handler para deletar todos os dados de um usuário específico
function handleDeleteUser() {
    global $baseDir;
    
    if (!isset($_GET['userId'])) {
        jsonResponse(['error' => 'Missing userId'], 400);
    }
    
    $userId = $_GET['userId'];
    $deletedDirs = [];
    $totalSize = 0;
    
    // Lista de todas as pastas que contém dados do usuário
    $userDirectories = [
        'screenshots' => 'Screenshots de sessão e manuais',
        'events' => 'Eventos',
        'events-screenshots' => 'Screenshots de eventos',
        'videos' => 'Vídeos de sessão',
        'users' => 'Informações do usuário'
    ];
    
    // Deletar todos os dados do usuário
    foreach ($userDirectories as $dirName => $description) {
        $userDir = $baseDir . '/' . $dirName . '/' . $userId;
        
        if (is_dir($userDir)) {
            // Calcular tamanho antes de deletar
            $dirSize = getDirSize($userDir);
            $totalSize += $dirSize;
            
            // Deletar diretório
            deleteDir($userDir);
            $deletedDirs[] = [
                'type' => $description,
                'path' => $dirName . '/' . $userId,
                'size' => formatBytes($dirSize)
            ];
            
            saveLog("🗑️ Deletado: $description ($dirName/$userId) - " . formatBytes($dirSize));
        }
    }
    
    if (empty($deletedDirs)) {
        saveLog("⚠️ Nenhum dado encontrado para o usuário: $userId");
        jsonResponse([
            'success' => true,
            'message' => 'Usuário não tinha dados para deletar',
            'userId' => $userId,
            'deletedDirs' => [],
            'totalSize' => formatBytes(0)
        ]);
    } else {
        saveLog("✅ Usuário $userId deletado completamente - Total: " . formatBytes($totalSize));
        jsonResponse([
            'success' => true,
            'message' => 'Todos os dados do usuário foram deletados com sucesso',
            'userId' => $userId,
            'deletedDirs' => $deletedDirs,
            'totalSize' => formatBytes($totalSize)
        ]);
    }
}

// Função auxiliar para calcular tamanho de um diretório
function getDirSize($dir) {
    $size = 0;
    
    if (is_dir($dir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
    }
    
    return $size;
}

// Função auxiliar para deletar um diretório e seu conteúdo
function deleteDir($dir) {
    if (is_dir($dir)) {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $filePath = $dir . '/' . $file;
            if (is_dir($filePath)) {
                deleteDir($filePath);
            } else {
                unlink($filePath);
            }
        }
        rmdir($dir);
    }
}

// ===== GESTÃO DE APPS =====

// Handler para listar todos os apps
function handleGetApps() {
    global $baseDir;
    
    $appsDir = $baseDir . '/apps';
    $apps = [];
    
    if (is_dir($appsDir)) {
        $files = array_diff(scandir($appsDir), ['.', '..']);
        
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $appData = json_decode(file_get_contents($appsDir . '/' . $file), true);
                if ($appData) {
                    $apps[] = $appData;
                }
            }
        }
    }
    
    jsonResponse(['success' => true, 'apps' => $apps]);
}

// Handler para criar novo app
function handleCreateApp($data) {
    global $baseDir;
    
    if (!$data || !isset($data['bundleId']) || !isset($data['name']) || !isset($data['platform'])) {
        jsonResponse(['error' => 'Missing required fields: bundleId, name, platform'], 400);
    }
    
    $bundleId = $data['bundleId'];
    $appsDir = $baseDir . '/apps';
    ensureDir($appsDir);
    
    $appFile = $appsDir . '/' . $bundleId . '.json';
    
    // Verificar se app já existe
    if (file_exists($appFile)) {
        jsonResponse(['error' => 'App already exists'], 409);
    }
    
    $appData = [
        'bundleId' => $bundleId,
        'name' => $data['name'],
        'platform' => $data['platform'], // 'ios' ou 'android'
        'config' => [
            'recordScreen' => false,
            'framerate' => 10,
            'screenSize' => 480
        ],
        'createdAt' => time(),
        'updatedAt' => time()
    ];
    
    file_put_contents($appFile, json_encode($appData, JSON_PRETTY_PRINT));
    
    saveLog("App created: $bundleId");
    jsonResponse(['success' => true, 'app' => $appData]);
}

// Handler para atualizar app
function handleUpdateApp($data) {
    global $baseDir;
    
    if (!$data || !isset($data['bundleId'])) {
        jsonResponse(['error' => 'Missing bundleId'], 400);
    }
    
    $bundleId = $data['bundleId'];
    $appsDir = $baseDir . '/apps';
    $appFile = $appsDir . '/' . $bundleId . '.json';
    
    if (!file_exists($appFile)) {
        jsonResponse(['error' => 'App not found'], 404);
    }
    
    $appData = json_decode(file_get_contents($appFile), true);
    
    // Atualizar campos se fornecidos
    if (isset($data['name'])) {
        $appData['name'] = $data['name'];
    }
    
    if (isset($data['config'])) {
        $appData['config'] = array_merge($appData['config'], $data['config']);
    }
    
    $appData['updatedAt'] = time();
    
    file_put_contents($appFile, json_encode($appData, JSON_PRETTY_PRINT));
    
    saveLog("App updated: $bundleId");
    jsonResponse(['success' => true, 'app' => $appData]);
}

// Handler para deletar app
function handleDeleteApp() {
    global $baseDir;
    
    if (!isset($_GET['bundleId'])) {
        jsonResponse(['error' => 'Missing bundleId'], 400);
    }
    
    $bundleId = $_GET['bundleId'];
    $appsDir = $baseDir . '/apps';
    $appFile = $appsDir . '/' . $bundleId . '.json';
    
    if (!file_exists($appFile)) {
        jsonResponse(['error' => 'App not found'], 404);
    }
    
    unlink($appFile);
    
    saveLog("App deleted: $bundleId");
    jsonResponse(['success' => true]);
}

// Handler para app consultar sua configuração
function handleGetAppConfig() {
    global $baseDir;
    
    if (!isset($_GET['bundleId'])) {
        jsonResponse(['error' => 'Missing bundleId'], 400);
    }
    
    $bundleId = $_GET['bundleId'];
    $appsDir = $baseDir . '/apps';
    $appFile = $appsDir . '/' . $bundleId . '.json';
    
    if (!file_exists($appFile)) {
        // Retornar configuração padrão se app não existe
        $defaultConfig = [
            'recordScreen' => false,
            'framerate' => 10,
            'screenSize' => 480
        ];
        jsonResponse(['success' => true, 'config' => $defaultConfig]);
        return;
    }
    
    $appData = json_decode(file_get_contents($appFile), true);
    
    saveLog("Config requested for app: $bundleId");
    jsonResponse(['success' => true, 'config' => $appData['config']]);
}

// Handler para upload de screenshots em ZIP
function handleUploadZip() {
    global $baseDir;
    
    saveLog("📥 Processando upload de sessão...");
    
    // Verificar se é um upload multipart
    if (!isset($_FILES['screenshots']) || !isset($_POST['metadata'])) {
        saveLog("❌ Dados de sessão inválidos ou ausentes");
        jsonResponse(['error' => 'Missing ZIP file or metadata'], 400);
    }
    
    $uploadedFile = $_FILES['screenshots'];
    $metadata = json_decode($_POST['metadata'], true);
    
    if (!$metadata || !isset($metadata['userId'])) {
        saveLog("❌ Metadados de sessão inválidos ou userId ausente");
        jsonResponse(['error' => 'Invalid metadata'], 400);
    }
    
    $userId = $metadata['userId'];
    $sessionId = $metadata['sessionId'] ?? 'session_' . time();
    $timestamp = isset($metadata['timestamp']) ? (int)$metadata['timestamp'] : time();
    $sessionDuration = $metadata['sessionDuration'] ?? 0;
    $frameCount = $metadata['frameCount'] ?? 0;
    $framerate = $metadata['framerate'] ?? 10;
    $date = date('Y-m-d', $timestamp);
    
    // Buscar dados geográficos pelo IP do usuário
    $geoData = fetchGeoInfo();
    
    saveLog("📥 Sessão recebida:");
    saveLog("   Usuário: $userId");
    saveLog("   Sessão: $sessionId");
    saveLog("   Duração: " . round($sessionDuration, 1) . "s");
    saveLog("   Frames: $frameCount @ {$framerate}fps");
    saveLog("   Localização: {$geoData['flag']} {$geoData['city']}, {$geoData['region']}");
    saveLog("   Tamanho ZIP: " . formatBytes($uploadedFile['size']));
    
    // Criar diretórios
    $userDir = $baseDir . '/videos/' . $userId . '/' . $date;
    $tempDir = $baseDir . '/temp/' . $userId . '_' . $timestamp;
    ensureDir($userDir);
    ensureDir($tempDir);
    
    // Mover arquivo ZIP para pasta temporária
    $zipPath = $tempDir . '/session.zip';
    if (!move_uploaded_file($uploadedFile['tmp_name'], $zipPath)) {
        saveLog("❌ Erro ao mover arquivo ZIP");
        jsonResponse(['error' => 'Failed to process ZIP file'], 500);
    }
    
    // Extrair imagens do ZIP
    $extractedPath = $tempDir . '/extracted';
    ensureDir($extractedPath);
    
    $imageCount = extractZipImages($zipPath, $extractedPath);
    
    if ($imageCount === 0) {
        saveLog("❌ Nenhuma imagem extraída da sessão");
        cleanupTempDir($tempDir);
        jsonResponse(['error' => 'No images found in session'], 400);
    }
    
    saveLog("📸 $imageCount imagens extraídas da sessão");
    
    // Validar se o número de frames bate
    if ($frameCount > 0 && abs($imageCount - $frameCount) > 2) {
        saveLog("⚠️ Divergência na contagem de frames: esperado $frameCount, encontrado $imageCount");
    }
    
    // Gerar MP4 a partir das imagens
    $videoFileName = "session_{$sessionId}.mp4";
    $videoPath = $userDir . '/' . $videoFileName;
    
    $success = generateMP4FromImages($extractedPath, $videoPath, $metadata);
    
    if ($success) {
        $videoSize = file_exists($videoPath) ? filesize($videoPath) : 0;
        $compressionRatio = $uploadedFile['size'] > 0 ? 
            round((1 - $videoSize / $uploadedFile['size']) * 100, 1) : 0;
        
        // Salvar metadados completos da sessão
        $sessionMetadata = [
            'userId' => $userId,
            'sessionId' => $sessionId,
            'timestamp' => $timestamp,
            'sessionStartTime' => $timestamp - $sessionDuration,
            'sessionDuration' => $sessionDuration,
            'frameCount' => $frameCount,
            'actualImageCount' => $imageCount,
            'framerate' => $framerate,
            'userData' => $metadata['userData'] ?? [],
            'geo' => $geoData,
            'receivedAt' => time(),
            'videoFile' => $videoFileName,
            'originalZipSize' => $uploadedFile['size'],
            'videoSize' => $videoSize,
            'compressionRatio' => $compressionRatio,
            'platform' => $metadata['userData']['platform'] ?? 'unknown',
            'appVersion' => $metadata['userData']['appVersion'] ?? 'unknown',
            'effectiveFPS' => $sessionDuration > 0 ? round($imageCount / $sessionDuration, 1) : 0
        ];
        
        $metadataFile = $userDir . '/session_' . $sessionId . '.json';
        file_put_contents($metadataFile, json_encode($sessionMetadata, JSON_PRETTY_PRINT));
        
        saveLog("✅ Vídeo de sessão gerado: " . formatBytes($videoSize));
        saveLog("📊 Compressão: {$compressionRatio}% | FPS efetivo: {$sessionMetadata['effectiveFPS']}");
        
        // Atualizar dados do usuário com informações da sessão
        updateUserLatestInfo($userId, $sessionMetadata);
        
        // Limpar arquivos temporários (ZIP e imagens extraídas)
        cleanupTempDir($tempDir);
        
        jsonResponse([
            'success' => true,
            'sessionId' => $sessionId,
            'videoFile' => $videoFileName,
            'sessionDuration' => $sessionDuration,
            'frameCount' => $frameCount,
            'actualImageCount' => $imageCount,
            'originalSize' => formatBytes($uploadedFile['size']),
            'videoSize' => formatBytes($videoSize),
            'compressionRatio' => $compressionRatio . '%',
            'effectiveFPS' => $sessionMetadata['effectiveFPS']
        ]);
    } else {
        saveLog("❌ Erro ao gerar MP4 da sessão");
        cleanupTempDir($tempDir);
        jsonResponse(['error' => 'Failed to generate session video'], 500);
    }
}

// Função para atualizar dados mais recentes do usuário
function updateUserLatestInfo($userId, $sessionData) {
    global $baseDir;
    
    $userInfoDir = $baseDir . '/users/' . $userId;
    ensureDir($userInfoDir);
    
    $latestFile = $userInfoDir . '/latest.json';
    
    // Carregar dados existentes ou criar novos
    $latestInfo = file_exists($latestFile) ? 
        json_decode(file_get_contents($latestFile), true) : [];
    
    // PROCESSAR DADOS DE DISPOSITIVO SE PRESENTES
    $userData = $sessionData['userData'] ?? [];
    if (!empty($userData)) {
        $userData = processDeviceInfo($userData);
        $sessionData['userData'] = $userData;
    }
    
    // Atualizar com dados da sessão mais recente
    $latestInfo = array_merge($latestInfo, [
        'userId' => $userId,
        'lastSessionId' => $sessionData['sessionId'],
        'lastSessionTime' => $sessionData['timestamp'],
        'userData' => $sessionData['userData'],
        'geo' => $sessionData['geo'],
        'receivedAt' => $sessionData['receivedAt'],
        'totalSessions' => ($latestInfo['totalSessions'] ?? 0) + 1,
        'totalFrames' => ($latestInfo['totalFrames'] ?? 0) + $sessionData['actualImageCount'],
        'platform' => $sessionData['platform'],
        'appVersion' => $sessionData['appVersion']
    ]);
    
    file_put_contents($latestFile, json_encode($latestInfo, JSON_PRETTY_PRINT));
    
    saveLog("👤 Dados do usuário $userId atualizados - Total de sessões: {$latestInfo['totalSessions']}");
}

// Função para extrair imagens do ZIP
function extractZipImages($zipPath, $extractPath) {
    $imageCount = 0;
    
    // Tentar usar ZipArchive se disponível
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) === TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (preg_match('/\.(jpg|jpeg|png)$/i', $filename)) {
                    $content = $zip->getFromIndex($i);
                    if ($content !== false) {
                        $newName = sprintf('frame_%03d.jpg', $imageCount);
                        file_put_contents($extractPath . '/' . $newName, $content);
                        $imageCount++;
                    }
                }
            }
            $zip->close();
            saveLog("📦 ZipArchive: $imageCount imagens extraídas");
        } else {
            saveLog("❌ Erro ao abrir ZIP com ZipArchive");
        }
    }
    
    // Fallback: tentar comando unzip se ZipArchive falhou
    if ($imageCount === 0 && file_exists($zipPath)) {
        $cmd = "cd " . escapeshellarg($extractPath) . " && unzip -j " . escapeshellarg($zipPath) . " '*.jpg' '*.jpeg' '*.png' 2>/dev/null";
        exec($cmd, $output, $returnCode);
        
        if ($returnCode === 0) {
            $files = glob($extractPath . '/*.{jpg,jpeg,png}', GLOB_BRACE);
            $imageCount = count($files);
            
            // Renomear arquivos para ordem sequencial
            foreach ($files as $index => $file) {
                $newName = sprintf('frame_%03d.jpg', $index);
                rename($file, $extractPath . '/' . $newName);
            }
            
            saveLog("📦 Comando unzip: $imageCount imagens extraídas");
        } else {
            saveLog("❌ Erro ao extrair ZIP com comando unzip");
        }
    }
    
    return $imageCount;
}

// Função para gerar MP4 a partir das imagens
function generateMP4FromImages($imagesPath, $outputVideoPath, $metadata) {
    // Verificar se FFmpeg está disponível
    exec('which ffmpeg 2>/dev/null', $output, $returnCode);
    if ($returnCode !== 0) {
        // Tentar caminhos comuns do FFmpeg
        $ffmpegPaths = ['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/opt/homebrew/bin/ffmpeg'];
        $ffmpegCmd = null;
        
        foreach ($ffmpegPaths as $path) {
            if (file_exists($path)) {
                $ffmpegCmd = $path;
                break;
            }
        }
        
        if (!$ffmpegCmd) {
            saveLog("❌ FFmpeg não encontrado no sistema");
            return false;
        }
    } else {
        $ffmpegCmd = 'ffmpeg';
    }
    
    // Calcular FPS correto baseado na duração da sessão e quantidade de frames
    $sessionDuration = $metadata['sessionDuration'] ?? 0;
    $frameCount = $metadata['frameCount'] ?? 0;
    $originalFramerate = $metadata['framerate'] ?? 10;
    
    // Se temos duração da sessão e quantidade de frames, calcular FPS real
    if ($sessionDuration > 0 && $frameCount > 0) {
        $outputFPS = $frameCount / $sessionDuration;
        $outputFPS = max(0.1, min($outputFPS, 30)); // Limitar entre 0.1 e 30 fps
        saveLog("📊 Calculando FPS baseado na sessão: {$frameCount} frames / {$sessionDuration}s = {$outputFPS} fps");
    } else {
        $outputFPS = $originalFramerate;
        saveLog("📊 Usando framerate original: {$outputFPS} fps");
    }
    
    // Comando FFmpeg corrigido para usar duração específica
    if ($sessionDuration > 0) {
        // Usar -t para definir duração específica do vídeo
        $cmd = sprintf(
            '%s -y -framerate %.2f -i %s -t %.2f -c:v libx264 -preset faster -crf 28 -vf "scale=480:960:force_original_aspect_ratio=decrease,pad=480:960:(ow-iw)/2:(oh-ih)/2" -pix_fmt yuv420p -movflags +faststart %s 2>&1',
            escapeshellarg($ffmpegCmd),
            $outputFPS,
            escapeshellarg($imagesPath . '/frame_%03d.jpg'),
            $sessionDuration,
            escapeshellarg($outputVideoPath)
        );
        saveLog("🎬 Executando FFmpeg: fps={$outputFPS}, duração={$sessionDuration}s");
    } else {
        // Fallback para método original se não temos duração
        $cmd = sprintf(
            '%s -y -framerate %d -i %s -c:v libx264 -preset faster -crf 28 -vf "scale=480:960:force_original_aspect_ratio=decrease,pad=480:960:(ow-iw)/2:(oh-ih)/2" -pix_fmt yuv420p -movflags +faststart %s 2>&1',
            escapeshellarg($ffmpegCmd),
            $originalFramerate,
            escapeshellarg($imagesPath . '/frame_%03d.jpg'),
            escapeshellarg($outputVideoPath)
        );
        saveLog("🎬 Executando FFmpeg (fallback): framerate={$originalFramerate}");
    }
    
    exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($outputVideoPath)) {
        saveLog("✅ MP4 gerado com sucesso");
        return true;
    } else {
        saveLog("❌ Erro FFmpeg (código $returnCode): " . implode("\n", $output));
        return false;
    }
}

// Função para limpar diretório temporário
function cleanupTempDir($tempDir) {
    if (is_dir($tempDir)) {
        deleteDir($tempDir);
        saveLog("🧹 Arquivos temporários removidos: $tempDir");
    }
}

// Função para buscar informações geográficas pelo IP
function fetchGeoInfo($ip = null) {
    // Se não fornecer IP, usar o IP do cliente
    if (!$ip) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Se for IP local, tentar obter o IP público real
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost', 'unknown'])) {
            $publicIP = getRealPublicIP();
            if ($publicIP) {
                $ip = $publicIP;
                saveLog("🌐 IP público detectado: $ip");
            } else {
                $ip = '8.8.8.8'; // Google DNS para teste local apenas se falhar
                saveLog("⚠️ Usando IP fallback para desenvolvimento: $ip");
            }
        }
    }
    
    // Fazer chamada para ip-api.com
    $url = "http://ip-api.com/json/$ip";
    $context = stream_context_create([
        'http' => [
            'timeout' => 5, // 5 segundos timeout
            'user_agent' => 'Expo Analytics Server/1.0'
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        saveLog("❌ Erro ao buscar dados geográficos para IP: $ip");
        return [
            'ip' => $ip,
            'country' => 'Unknown',
            'countryCode' => 'XX',
            'region' => 'Unknown',
            'city' => 'Unknown',
            'flag' => '🌍',
            'error' => 'Failed to fetch geo data'
        ];
    }
    
    $geoData = json_decode($response, true);
    
    if (!$geoData || $geoData['status'] !== 'success') {
        saveLog("❌ Dados geográficos inválidos para IP: $ip");
        return [
            'ip' => $ip,
            'country' => 'Unknown',
            'countryCode' => 'XX',
            'region' => 'Unknown', 
            'city' => 'Unknown',
            'flag' => '🌍',
            'error' => 'Invalid geo data'
        ];
    }
    
    // Mapear códigos de país para bandeiras (emoji)
    $countryFlags = [
        'BR' => '🇧🇷', 'US' => '🇺🇸', 'CA' => '🇨🇦', 'MX' => '🇲🇽',
        'AR' => '🇦🇷', 'CL' => '🇨🇱', 'CO' => '🇨🇴', 'PE' => '🇵🇪',
        'GB' => '🇬🇧', 'FR' => '🇫🇷', 'DE' => '🇩🇪', 'IT' => '🇮🇹',
        'ES' => '🇪🇸', 'PT' => '🇵🇹', 'NL' => '🇳🇱', 'BE' => '🇧🇪',
        'CH' => '🇨🇭', 'AT' => '🇦🇹', 'SE' => '🇸🇪', 'NO' => '🇳🇴',
        'DK' => '🇩🇰', 'FI' => '🇫🇮', 'IS' => '🇮🇸', 'IE' => '🇮🇪',
        'PL' => '🇵🇱', 'CZ' => '🇨🇿', 'SK' => '🇸🇰', 'HU' => '🇭🇺',
        'RO' => '🇷🇴', 'BG' => '🇧🇬', 'HR' => '🇭🇷', 'SI' => '🇸🇮',
        'RS' => '🇷🇸', 'BA' => '🇧🇦', 'MK' => '🇲🇰', 'AL' => '🇦🇱',
        'GR' => '🇬🇷', 'TR' => '🇹🇷', 'RU' => '🇷🇺', 'UA' => '🇺🇦',
        'BY' => '🇧🇾', 'LT' => '🇱🇹', 'LV' => '🇱🇻', 'EE' => '🇪🇪',
        'JP' => '🇯🇵', 'CN' => '🇨🇳', 'KR' => '🇰🇷', 'IN' => '🇮🇳',
        'TH' => '🇹🇭', 'VN' => '🇻🇳', 'ID' => '🇮🇩', 'MY' => '🇲🇾',
        'SG' => '🇸🇬', 'PH' => '🇵🇭', 'TW' => '🇹🇼', 'HK' => '🇭🇰',
        'AU' => '🇦🇺', 'NZ' => '🇳🇿', 'ZA' => '🇿🇦', 'EG' => '🇪🇬',
        'NG' => '🇳🇬', 'KE' => '🇰🇪', 'MA' => '🇲🇦', 'SA' => '🇸🇦',
        'AE' => '🇦🇪', 'IL' => '🇮🇱', 'IR' => '🇮🇷', 'IQ' => '🇮🇶',
        'PK' => '🇵🇰', 'BD' => '🇧🇩', 'AF' => '🇦🇫', 'KZ' => '🇰🇿',
        'UZ' => '🇺🇿', 'MN' => '🇲🇳', 'AM' => '🇦🇲', 'GE' => '🇬🇪',
        'AZ' => '🇦🇿', 'LB' => '🇱🇧', 'JO' => '🇯🇴', 'SY' => '🇸🇾'
    ];
    
    $countryCode = $geoData['countryCode'] ?? 'XX';
    $flag = $countryFlags[$countryCode] ?? '🌍';
    
    $result = [
        'ip' => $ip,
        'country' => $geoData['country'] ?? 'Unknown',
        'countryCode' => $countryCode,
        'region' => $geoData['regionName'] ?? 'Unknown',
        'city' => $geoData['city'] ?? 'Unknown',
        'lat' => $geoData['lat'] ?? null,
        'lon' => $geoData['lon'] ?? null,
        'timezone' => $geoData['timezone'] ?? null,
        'isp' => $geoData['isp'] ?? null,
        'org' => $geoData['org'] ?? null,
        'flag' => $flag,
        'fetchedAt' => time()
    ];
    
    saveLog("🌍 Dados geográficos obtidos para IP $ip: {$result['flag']} {$result['country']}, {$result['city']}");
    
    return $result;
}

// Função para obter o IP público real da máquina
function getRealPublicIP() {
    $services = [
        'https://api.ipify.org',
        'https://icanhazip.com',
        'https://ipecho.net/plain',
        'https://checkip.amazonaws.com'
    ];
    
    foreach ($services as $service) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 3,
                'user_agent' => 'Expo Analytics Server/1.0'
            ]
        ]);
        
        $ip = @file_get_contents($service, false, $context);
        
        if ($ip !== false) {
            $ip = trim($ip);
            // Validar se é um IP válido
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return null;
}

// Função para mapear identificadores de dispositivos para nomes comerciais
function mapDeviceIdentifier($identifier) {
    $deviceMap = [
        // iPhone models
        'iPhone14,4' => 'iPhone 13 mini',
        'iPhone14,5' => 'iPhone 13',
        'iPhone14,2' => 'iPhone 13 Pro',
        'iPhone14,3' => 'iPhone 13 Pro Max',
        'iPhone14,7' => 'iPhone 14',
        'iPhone14,8' => 'iPhone 14 Plus',
        'iPhone15,2' => 'iPhone 14 Pro',
        'iPhone15,3' => 'iPhone 14 Pro Max',
        'iPhone15,4' => 'iPhone 15',
        'iPhone15,5' => 'iPhone 15 Plus',
        'iPhone16,1' => 'iPhone 15 Pro',
        'iPhone16,2' => 'iPhone 15 Pro Max',
        'iPhone17,1' => 'iPhone 16 Pro',
        'iPhone17,2' => 'iPhone 16 Pro Max',
        'iPhone17,3' => 'iPhone 16',
        'iPhone17,4' => 'iPhone 16 Plus',
        
        // iPad models
        'iPad13,18' => 'iPad Pro 12.9 (6th gen)',
        'iPad13,19' => 'iPad Pro 12.9 (6th gen)',
        'iPad14,3' => 'iPad Pro 11 (4th gen)',
        'iPad14,4' => 'iPad Pro 11 (4th gen)',
        'iPad13,16' => 'iPad Air (5th gen)',
        'iPad13,17' => 'iPad Air (5th gen)',
        'iPad14,10' => 'iPad Air (6th gen)',
        'iPad14,11' => 'iPad Air (6th gen)',
        
        // Simulators
        'x86_64' => 'iOS Simulator',
        'i386' => 'iOS Simulator',
        'arm64' => 'iOS Simulator'
    ];
    
    return $deviceMap[$identifier] ?? $identifier;
}

// Função para processar dados de dispositivo
function processDeviceInfo($userData) {
    // Processar identificador do dispositivo
    if (isset($userData['device'])) {
        // Extrair identificador do formato "x86_64 (iOS Simulator)" -> "x86_64"
        $deviceString = $userData['device'];
        
        // Se já está no formato "identificador (nome comercial)", extrair apenas o identificador
        if (preg_match('/^([^\s]+)\s*\(/', $deviceString, $matches)) {
            $identifier = $matches[1];
        } else {
            $identifier = $deviceString;
        }
        
        // Mapear para nome comercial
        $commercialName = mapDeviceIdentifier($identifier);
        
        // Se o identificador é diferente do nome comercial, criar formato completo
        if ($identifier !== $commercialName) {
            $userData['device'] = "$identifier ($commercialName)";
            $userData['deviceIdentifier'] = $identifier;
            $userData['deviceCommercialName'] = $commercialName;
        } else {
            $userData['device'] = $identifier;
            $userData['deviceIdentifier'] = $identifier;
            $userData['deviceCommercialName'] = $identifier;
        }
    }
    
    // Processar informações da tela
    if (isset($userData['screenSize'])) {
        $userData['screenResolution'] = $userData['screenSize'];
    }
    
    // Processar profundidade de cor
    if (isset($userData['depth'])) {
        $userData['colorDepth'] = $userData['depth'] . ' bits';
    }
    
    // Processar tamanho da fonte
    if (isset($userData['fontSize'])) {
        $userData['systemFontSize'] = $userData['fontSize'];
    }
    
    // Processar idioma do usuário
    if (isset($userData['userLanguage'])) {
        $userData['deviceLanguage'] = $userData['userLanguage'];
    }
    
    // Processar país e região (formato: EN-US, PT-BR)
    if (isset($userData['country'])) {
        $countryRegion = $userData['country'];
        
        // Separar idioma e país
        if (preg_match('/^([A-Z]{2})-([A-Z]{2})$/', $countryRegion, $matches)) {
            $languageCode = $matches[1];
            $countryCode = $matches[2];
            
            $userData['locale'] = $countryRegion;
            $userData['languageCode'] = $languageCode;
            $userData['countryCode'] = $countryCode;
            $userData['regionInfo'] = [
                'language' => $languageCode,
                'country' => $countryCode,
                'locale' => $countryRegion
            ];
        } else {
            $userData['locale'] = $countryRegion;
        }
    }
    
    return $userData;
}

saveLog("Request completed");
?> 