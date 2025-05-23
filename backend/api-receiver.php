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
function handleTrack($data) {
    global $baseDir;
    
    if (!$data || !isset($data['userId'])) {
        jsonResponse(['error' => 'Invalid data'], 400);
    }
    
    $userId = $data['userId'];
    $date = date('Y-m-d');
    
    // Criar diretório
    $eventsDir = $baseDir . '/events/' . $userId . '/' . $date;
    ensureDir($eventsDir);
    
    // Preparar dados do evento
    $event = [
        'userId' => $userId,
        'event' => $data['event'] ?? 'unknown',
        'value' => $data['value'] ?? '',
        'timestamp' => $data['timestamp'] ?? time(),
        'userData' => $data['userData'] ?? [],
        'geo' => $data['geo'] ?? [],
        'receivedAt' => time()
    ];
    
    // Salvar evento
    $eventFile = $eventsDir . '/events_' . date('H') . '.jsonl';
    file_put_contents($eventFile, json_encode($event) . "\n", FILE_APPEND | LOCK_EX);
    
    saveLog("Event tracked for user $userId: {$event['event']}");
    jsonResponse(['success' => true]);
}

// Handler para inicialização/info do usuário
function handleInit($data) {
    global $baseDir;
    
    if (!$data || !isset($data['userId'])) {
        jsonResponse(['error' => 'Invalid data'], 400);
    }
    
    $userId = $data['userId'];
    $date = date('Y-m-d');
    
    // Criar diretório
    $userDir = $baseDir . '/users/' . $userId;
    ensureDir($userDir);
    
    // Preparar dados do usuário
    $userInfo = [
        'userId' => $userId,
        'userData' => $data['userData'] ?? [],
        'geo' => $data['geo'] ?? [],
        'timestamp' => $data['timestamp'] ?? time(),
        'receivedAt' => time(),
        'date' => $date
    ];
    
    // Salvar informações do usuário
    $userFile = $userDir . '/info_' . date('Y-m-d_H-i-s') . '.json';
    file_put_contents($userFile, json_encode($userInfo, JSON_PRETTY_PRINT));
    
    // Atualizar arquivo de usuário mais recente
    $latestFile = $userDir . '/latest.json';
    file_put_contents($latestFile, json_encode($userInfo, JSON_PRETTY_PRINT));
    
    saveLog("User info updated for user $userId");
    jsonResponse(['success' => true]);
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
    
    // Deletar todos os dados do usuário
    $userDir = $baseDir . '/screenshots/' . $userId;
    if (is_dir($userDir)) {
        deleteDir($userDir);
    }
    
    $userDir = $baseDir . '/events/' . $userId;
    if (is_dir($userDir)) {
        deleteDir($userDir);
    }
    
    $userDir = $baseDir . '/users/' . $userId;
    if (is_dir($userDir)) {
        deleteDir($userDir);
    }
    
    saveLog("User $userId data deleted");
    jsonResponse(['success' => true]);
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
    
    saveLog("📦 Processando upload ZIP...");
    
    // Verificar se é um upload multipart
    if (!isset($_FILES['screenshots']) || !isset($_POST['metadata'])) {
        saveLog("❌ Dados ZIP inválidos ou ausentes");
        jsonResponse(['error' => 'Missing ZIP file or metadata'], 400);
    }
    
    $uploadedFile = $_FILES['screenshots'];
    $metadata = json_decode($_POST['metadata'], true);
    
    if (!$metadata || !isset($metadata['userId'])) {
        saveLog("❌ Metadados inválidos ou userId ausente");
        jsonResponse(['error' => 'Invalid metadata'], 400);
    }
    
    $userId = $metadata['userId'];
    $timestamp = isset($metadata['timestamp']) ? (int)$metadata['timestamp'] : time();
    $date = date('Y-m-d', $timestamp);
    
    saveLog("📥 ZIP recebido para usuário $userId - Tamanho: " . formatBytes($uploadedFile['size']));
    
    // Criar diretórios
    $userDir = $baseDir . '/videos/' . $userId . '/' . $date;
    $tempDir = $baseDir . '/temp/' . $userId . '_' . $timestamp;
    ensureDir($userDir);
    ensureDir($tempDir);
    
    // Mover arquivo ZIP para pasta temporária
    $zipPath = $tempDir . '/screenshots.zip';
    if (!move_uploaded_file($uploadedFile['tmp_name'], $zipPath)) {
        saveLog("❌ Erro ao mover arquivo ZIP");
        jsonResponse(['error' => 'Failed to process ZIP file'], 500);
    }
    
    // Extrair imagens do ZIP
    $extractedPath = $tempDir . '/extracted';
    ensureDir($extractedPath);
    
    $imageCount = extractZipImages($zipPath, $extractedPath);
    
    if ($imageCount === 0) {
        saveLog("❌ Nenhuma imagem extraída do ZIP");
        cleanupTempDir($tempDir);
        jsonResponse(['error' => 'No images found in ZIP'], 400);
    }
    
    saveLog("📸 $imageCount imagens extraídas do ZIP");
    
    // Gerar MP4 a partir das imagens
    $videoFileName = "video_" . $timestamp . ".mp4";
    $videoPath = $userDir . '/' . $videoFileName;
    
    $success = generateMP4FromImages($extractedPath, $videoPath, $metadata);
    
    if ($success) {
        // Salvar metadados do vídeo
        $videoMetadata = [
            'userId' => $userId,
            'timestamp' => $timestamp,
            'userData' => $metadata['userData'] ?? [],
            'geo' => $metadata['geo'] ?? [],
            'receivedAt' => time(),
            'imageCount' => $imageCount,
            'videoFile' => $videoFileName,
            'originalZipSize' => $uploadedFile['size'],
            'videoSize' => file_exists($videoPath) ? filesize($videoPath) : 0,
            'compressionRatio' => file_exists($videoPath) ? 
                round((1 - filesize($videoPath) / $uploadedFile['size']) * 100, 1) : 0
        ];
        
        $metadataFile = $userDir . '/metadata_' . $timestamp . '.json';
        file_put_contents($metadataFile, json_encode($videoMetadata, JSON_PRETTY_PRINT));
        
        saveLog("✅ MP4 gerado com sucesso: " . formatBytes(filesize($videoPath)));
        saveLog("📊 Taxa de compressão: {$videoMetadata['compressionRatio']}%");
        
        // Limpar arquivos temporários (ZIP e imagens extraídas)
        cleanupTempDir($tempDir);
        
        jsonResponse([
            'success' => true,
            'videoFile' => $videoFileName,
            'imageCount' => $imageCount,
            'originalSize' => formatBytes($uploadedFile['size']),
            'videoSize' => formatBytes($videoMetadata['videoSize']),
            'compressionRatio' => $videoMetadata['compressionRatio'] . '%'
        ]);
    } else {
        saveLog("❌ Erro ao gerar MP4");
        cleanupTempDir($tempDir);
        jsonResponse(['error' => 'Failed to generate MP4'], 500);
    }
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
    
    // Detectar framerate dos metadados ou usar padrão
    $framerate = isset($metadata['userData']['framerate']) ? 
        max(1, min($metadata['userData']['framerate'], 30)) : 10;
    
    // Comando FFmpeg otimizado para compressão
    $cmd = sprintf(
        '%s -y -framerate %d -i %s -c:v libx264 -preset faster -crf 28 -vf "scale=480:960:force_original_aspect_ratio=decrease,pad=480:960:(ow-iw)/2:(oh-ih)/2" -pix_fmt yuv420p -movflags +faststart %s 2>&1',
        escapeshellarg($ffmpegCmd),
        $framerate,
        escapeshellarg($imagesPath . '/frame_%03d.jpg'),
        escapeshellarg($outputVideoPath)
    );
    
    saveLog("🎬 Executando FFmpeg: framerate=$framerate");
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

saveLog("Request completed");
?> 