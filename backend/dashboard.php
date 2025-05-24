<?php
// Dashboard principal para Analytics com gestão de apps
date_default_timezone_set('America/Sao_Paulo');
$baseDir = __DIR__ . '/analytics-data';

// Função para buscar ícone da App Store com cache
function getAppStoreIcon($bundleId, $baseDir) {
    $cacheDir = $baseDir . '/cache';
    $cacheFile = $cacheDir . '/app-icons.json';
    
    // Criar diretório de cache se não existir
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    // Carregar cache existente
    $cache = [];
    if (file_exists($cacheFile)) {
        $cache = json_decode(file_get_contents($cacheFile), true) ?: [];
    }
    
    // Verificar se já temos o ícone em cache (válido por 7 dias)
    $cacheKey = $bundleId;
    $cacheValidFor = 7 * 24 * 60 * 60; // 7 dias em segundos
    
    if (isset($cache[$cacheKey]) && 
        isset($cache[$cacheKey]['timestamp']) && 
        isset($cache[$cacheKey]['icon']) &&
        (time() - $cache[$cacheKey]['timestamp']) < $cacheValidFor) {
        return $cache[$cacheKey]['icon'];
    }
    
    // Buscar ícone da App Store
    $appStoreIcon = null;
    $searchUrl = "https://itunes.apple.com/search?term=" . urlencode($bundleId) . "&entity=software&limit=1&country=US";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 3,
            'user_agent' => 'Mozilla/5.0 (compatible; Analytics Dashboard)'
        ]
    ]);
    
    $response = @file_get_contents($searchUrl, false, $context);
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['results'][0]['artworkUrl100'])) {
            // Usar ícone de 512px para melhor qualidade
            $appStoreIcon = str_replace('100x100bb', '512x512bb', $data['results'][0]['artworkUrl100']);
        }
    }
    
    // Salvar no cache
    $cache[$cacheKey] = [
        'icon' => $appStoreIcon,
        'timestamp' => time()
    ];
    
    file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
    
    return $appStoreIcon;
}

// Função para obter lista de apps
function getApps($baseDir) {
    $apps = [];
    $appsDir = $baseDir . '/apps';
    
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
    
    // Ordenar por nome
    usort($apps, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    return $apps;
}

// Função para obter app específico
function getApp($baseDir, $bundleId) {
    $appsDir = $baseDir . '/apps';
    $appFile = $appsDir . '/' . $bundleId . '.json';
    
    if (file_exists($appFile)) {
        return json_decode(file_get_contents($appFile), true);
    }
    
    return null;
}

// Função para obter eventos de um usuário específico
function getUserEvents($baseDir, $userId) {
    $events = [];
    $userEventsDir = $baseDir . '/events/' . $userId;
    
    if (is_dir($userEventsDir)) {
        // Procurar recursivamente por arquivos .jsonl
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($userEventsDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) === 'jsonl') {
                $lines = file($file->getPathname(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                
                foreach ($lines as $line) {
                    $event = json_decode($line, true);
                    if ($event) {
                        $events[] = $event;
                    }
                }
            }
        }
    }
    
    // Ordenar eventos por timestamp (mais recentes primeiro)
    usort($events, function($a, $b) {
        return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
    });
    
    return $events;
}

// Função para obter todos os eventos (para estatísticas)
function getAllEvents($baseDir, $selectedApp = null) {
    $events = [];
    $eventsDir = $baseDir . '/events';
    
    if (!is_dir($eventsDir)) {
        return $events;
    }
    
    $users = array_diff(scandir($eventsDir), ['.', '..']);
    
    foreach ($users as $userId) {
        $userPath = $eventsDir . '/' . $userId;
        
        if (is_dir($userPath) && !str_starts_with($userId, '.')) {
            $userEvents = getUserEvents($baseDir, $userId);
            $events = array_merge($events, $userEvents);
        }
    }
    
    return $events;
}

// Função para obter linha do tempo de eventos de um usuário
function getUserTimeline($baseDir, $userId) {
    $events = getUserEvents($baseDir, $userId);
    $timeline = [];
    
    foreach ($events as $event) {
        $date = date('Y-m-d', $event['timestamp']);
        $time = date('H:i:s', $event['timestamp']);
        
        if (!isset($timeline[$date])) {
            $timeline[$date] = [];
        }

        // Verificar se existe screenshot para este evento
        $eventDate = date('Y-m-d', $event['timestamp']);
        $eventTimestamp = $event['timestamp'];
        $eventName = $event['event'] ?? 'unknown';
        
        $screenshotFile = null;
        $screenshotPath = $baseDir . '/events-screenshots/' . $userId . '/' . $eventDate;
        
        if (is_dir($screenshotPath)) {
            // Procurar por arquivos que correspondam ao evento
            $possibleFiles = glob($screenshotPath . '/event_' . $eventTimestamp . '_' . $eventName . '.jpg');
            if (empty($possibleFiles)) {
                // Fallback: procurar por padrões mais flexíveis
                $possibleFiles = glob($screenshotPath . '/event_*_' . $eventName . '.jpg');
            }
            
            if (!empty($possibleFiles)) {
                $screenshotFile = basename($possibleFiles[0]);
            }
        }
        
        $timeline[$date][] = [
            'time' => $time,
            'timestamp' => $event['timestamp'],
            'event' => $event['event'] ?? 'unknown',
            'value' => $event['value'] ?? '',
            'geo' => $event['geo'] ?? [],
            'hasScreenshot' => !is_null($screenshotFile),
            'screenshot' => $screenshotFile ? [
                'filename' => $screenshotFile,
                'url' => 'event-screenshot.php?user=' . urlencode($userId) . '&date=' . urlencode($eventDate) . '&file=' . urlencode($screenshotFile)
            ] : null
        ];
    }
    
    // Ordenar dias (mais recentes primeiro)
    krsort($timeline);
    
    // Ordenar eventos do dia (mais recentes primeiro)
    foreach ($timeline as $date => &$dayEvents) {
        usort($dayEvents, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
    }
    
    return $timeline;
}

// Função para obter estatísticas filtradas por app
function getStats($baseDir, $selectedApp = null) {
    $stats = [
        'totalUsers' => 0,
        'totalSessions' => 0,
        'totalEvents' => 0,
        'totalScreenshots' => 0,
        'totalVideos' => 0,
        'recentUsers' => [],
        'topEvents' => []
    ];
    
    if (!is_dir($baseDir)) return $stats;
    
    // BUSCAR USUÁRIOS REGISTRADOS (pasta /users)
    $allUsers = [];
    $usersDir = $baseDir . '/users';
    
    if (is_dir($usersDir)) {
        $userFolders = array_diff(scandir($usersDir), ['.', '..']);
        
        foreach ($userFolders as $userId) {
            $userPath = $usersDir . '/' . $userId;
            
            if (is_dir($userPath) && !str_starts_with($userId, '.')) {
                $latestFile = $userPath . '/latest.json';
                
                if (file_exists($latestFile)) {
                    $userInfo = json_decode(file_get_contents($latestFile), true);
                    
                    if ($userInfo) {
                        // Verificar se o usuário pertence ao app selecionado
                        $userBundleId = $userInfo['userData']['bundleId'] ?? 
                                      $userInfo['deviceInfo']['bundleId'] ?? null;
                        
                        if (!$selectedApp || $userBundleId === $selectedApp) {
                            $allUsers[$userId] = [
                                'userId' => $userId,
                                'lastSeen' => $userInfo['receivedAt'] ?? $userInfo['timestamp'] ?? 0,
                                'userData' => $userInfo['userData'] ?? [],
                                'isRegisteredOnly' => true // Flag para indicar que é só registro
                            ];
                        }
                    }
                }
            }
        }
    }
    
    // Contar usuários únicos nos eventos
    $uniqueUsers = $allUsers; // Começar com usuários registrados
    $allEvents = getAllEvents($baseDir, $selectedApp);
    $eventTypes = [];
    
    foreach ($allEvents as $event) {
        if (isset($event['userId'])) {
            $userId = $event['userId'];
            
            // Se já existe, atualizar com dados do evento (mais recente)
            if (!isset($uniqueUsers[$userId]) || $event['timestamp'] > $uniqueUsers[$userId]['lastSeen']) {
                $uniqueUsers[$userId] = [
                    'userId' => $userId,
                    'lastSeen' => $event['timestamp'],
                    'userData' => $event['userData'] ?? [],
                    'isRegisteredOnly' => false // Tem eventos
                ];
            }
        }
        
        $eventName = $event['event'] ?? 'unknown';
        if (!isset($eventTypes[$eventName])) {
            $eventTypes[$eventName] = 0;
            }
        $eventTypes[$eventName]++;
    }
    
    $stats['totalUsers'] = count($uniqueUsers);
    $stats['totalEvents'] = count($allEvents);
    
    // Top eventos
    arsort($eventTypes);
    foreach (array_slice($eventTypes, 0, 5, true) as $eventName => $count) {
        $stats['topEvents'][] = [
            'name' => $eventName,
            'count' => $count
        ];
    }
    
    // Usuários recentes (combinar registrados + com eventos)
    uasort($uniqueUsers, function($a, $b) {
            return $b['lastSeen'] - $a['lastSeen'];
        });
    
    $stats['recentUsers'] = array_slice(array_values($uniqueUsers), 0, 10);
    
    // Contar sessões de screenshots (mantido para compatibilidade, mas não usado no card principal)
    $screenshotsDir = $baseDir . '/screenshots';
    if (is_dir($screenshotsDir)) {
        $users = array_diff(scandir($screenshotsDir), ['.', '..']);
        
        foreach ($users as $userId) {
            $userPath = $screenshotsDir . '/' . $userId;
            
            if (is_dir($userPath) && !str_starts_with($userId, '.')) {
                $sessions = array_diff(scandir($userPath), ['.', '..']);
                
                foreach ($sessions as $sessionDate) {
                    $sessionPath = $userPath . '/' . $sessionDate;
                    
                    if (is_dir($sessionPath) && !str_starts_with($sessionDate, '.')) {
                        // Não contabilizar no card principal - apenas adicionar aos screenshots
                        $screenshots = glob($sessionPath . '/*.jpg');
                        $stats['totalScreenshots'] += count($screenshots);
                    }
                }
            }
        }
    }

    // Contar screenshots de eventos
    $eventsScreenshotsDir = $baseDir . '/events-screenshots';
    if (is_dir($eventsScreenshotsDir)) {
        $eventScreenshots = glob($eventsScreenshotsDir . '/*/*/*.jpg');
        $stats['totalScreenshots'] += count($eventScreenshots);
    }
    
    // Contar vídeos (sessões gravadas) - ESTA É A CONTAGEM CORRETA PARA O CARD
    $videosDir = $baseDir . '/videos';
    if (is_dir($videosDir)) {
        $users = array_diff(scandir($videosDir), ['.', '..']);
        
        foreach ($users as $userId) {
            $userPath = $videosDir . '/' . $userId;
            
            if (is_dir($userPath) && !str_starts_with($userId, '.')) {
                foreach (glob($userPath . '/*/*.mp4') as $videoFile) {
                    $stats['totalVideos']++;
                    $stats['totalSessions']++; // Cada vídeo representa uma sessão gravada
                }
            }
        }
    }
    
    return $stats;
}

// Função para obter sessões de um usuário
function getUserSessions($baseDir, $userId) {
    $sessions = [];
    $userScreenshotsDir = $baseDir . '/screenshots/' . $userId;
    
    if (is_dir($userScreenshotsDir)) {
        foreach (glob($userScreenshotsDir . '/*') as $sessionDir) {
            if (is_dir($sessionDir)) {
                $date = basename($sessionDir);
                
                // Separar screenshots de sessão tradicionais dos manuais
                $allScreenshots = glob($sessionDir . '/*.jpg');
                $sessionScreenshots = [];
                
                foreach ($allScreenshots as $screenshot) {
                    $filename = basename($screenshot);
                    // Ignorar screenshots manuais (que começam com "manual_screenshot_")
                    if (!preg_match('/^manual_screenshot_/', $filename)) {
                        $sessionScreenshots[] = $screenshot;
                    }
                }
                
                // Só criar sessão se houver screenshots de sessão tradicionais
                if (!empty($sessionScreenshots)) {
                    $metadataFiles = glob($sessionDir . '/metadata_*.json');
                    
                    // Filtrar metadados manuais
                    $sessionMetadataFiles = [];
                    foreach ($metadataFiles as $metadataFile) {
                        $filename = basename($metadataFile);
                        if (!preg_match('/^metadata_manual_/', $filename)) {
                            $sessionMetadataFiles[] = $metadataFile;
                        }
                    }
                    
                    $metadata = [];
                    if (!empty($sessionMetadataFiles)) {
                        $metadata = json_decode(file_get_contents($sessionMetadataFiles[0]), true);
                    }
                    
                    $sessions[] = [
                        'date' => $date,
                        'path' => $sessionDir,
                        'screenshotCount' => count($sessionScreenshots),
                        'metadata' => $metadata,
                        'firstScreenshot' => !empty($sessionScreenshots) ? basename($sessionScreenshots[0]) : null
                    ];
                }
            }
        }
    }
    
    usort($sessions, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });
    
    return $sessions;
}

// Nova função para obter screenshots manuais
function getUserManualScreenshots($baseDir, $userId) {
    $manualScreenshots = [];
    $userScreenshotsDir = $baseDir . '/screenshots/' . $userId;
    
    if (is_dir($userScreenshotsDir)) {
        foreach (glob($userScreenshotsDir . '/*') as $sessionDir) {
            if (is_dir($sessionDir)) {
                $date = basename($sessionDir);
                $screenshots = glob($sessionDir . '/manual_screenshot_*.jpg');
                
                foreach ($screenshots as $screenshot) {
                    $filename = basename($screenshot);
                    $fileSize = filesize($screenshot);
                    $fileTime = filemtime($screenshot);
                    
                    // Extrair informações do nome do arquivo (manual_screenshot_timestamp_widthxheight.jpg)
                    if (preg_match('/^manual_screenshot_(\d+)_(\d+)x(\d+)\.jpg$/', $filename, $matches)) {
                        $timestamp = $matches[1];
                        $width = $matches[2];
                        $height = $matches[3];
                        
                        // Buscar metadados correspondentes
                        $metadataFile = $sessionDir . '/metadata_manual_' . $timestamp . '.json';
                        $metadata = null;
                        if (file_exists($metadataFile)) {
                            $metadata = json_decode(file_get_contents($metadataFile), true);
                        }
                        
                        $manualScreenshots[] = [
                            'type' => 'manual',
                            'date' => $date,
                            'filename' => $filename,
                            'path' => 'view-screenshot.php?user=' . urlencode($userId) . '&date=' . urlencode($date) . '&file=' . urlencode($filename),
                            'size' => $fileSize,
                            'timestamp' => $timestamp,
                            'width' => $width,
                            'height' => $height,
                            'fileTime' => $fileTime,
                            'metadata' => $metadata
                        ];
                    }
                }
            }
        }
    }
    
    // Ordenar por timestamp (mais recentes primeiro)
    usort($manualScreenshots, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    return $manualScreenshots;
}

// Função para obter screenshots de eventos de um usuário
function getUserEventScreenshots($baseDir, $userId) {
    $eventScreenshots = [];
    $eventsScreenshotsDir = $baseDir . '/events-screenshots/' . $userId;
    
    if (is_dir($eventsScreenshotsDir)) {
        // Percorrer todas as datas
        foreach (glob($eventsScreenshotsDir . '/*') as $dateDir) {
            if (is_dir($dateDir)) {
                $date = basename($dateDir);
                $screenshots = glob($dateDir . '/*.jpg');
                
                foreach ($screenshots as $screenshot) {
                    $filename = basename($screenshot);
                    $fileSize = filesize($screenshot);
                    $fileTime = filemtime($screenshot);
                    
                    // Extrair informações do nome do arquivo (event_timestamp_eventname.jpg)
                    if (preg_match('/^event_(\d+)_(.+)\.jpg$/', $filename, $matches)) {
                        $timestamp = $matches[1];
                        $eventName = str_replace('_', ' ', $matches[2]);
                        
                        $eventScreenshots[] = [
                            'type' => 'event',
                            'date' => $date,
                            'filename' => $filename,
                            'path' => 'event-screenshot.php?user=' . urlencode($userId) . '&date=' . urlencode($date) . '&file=' . urlencode($filename),
                            'size' => $fileSize,
                            'timestamp' => $timestamp,
                            'eventName' => $eventName,
                            'fileTime' => $fileTime
                        ];
                    }
                }
            }
        }
    }
    
    // Ordenar por timestamp (mais recentes primeiro)
    usort($eventScreenshots, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    return $eventScreenshots;
}

// Função para obter vídeos de um usuário
function getUserVideos($baseDir, $userId) {
    $videos = [];
    $userVideosDir = $baseDir . '/videos/' . $userId;
    
    if (is_dir($userVideosDir)) {
        foreach (glob($userVideosDir . '/*') as $dateDir) {
            if (is_dir($dateDir)) {
                $date = basename($dateDir);
                $videoFiles = glob($dateDir . '/*.mp4');
                $metadataFiles = glob($dateDir . '/session_*.json');
                
                foreach ($videoFiles as $videoFile) {
                    $videoName = basename($videoFile);
                    $videoSize = filesize($videoFile);
                    
                    // Buscar metadados correspondentes baseado no sessionId
                    $metadata = null;
                    $sessionId = null;
                    
                    // Extrair sessionId do nome do arquivo (formato: session_SESSIONID.mp4)
                    if (preg_match('/session_([^.]+)\.mp4/', $videoName, $matches)) {
                        $sessionId = $matches[1];
                        
                        // Buscar arquivo de metadados correspondente
                        $metadataFile = $dateDir . '/session_' . $sessionId . '.json';
                        if (file_exists($metadataFile)) {
                            $metadata = json_decode(file_get_contents($metadataFile), true);
                        }
                    }
                    
                    // Fallback para formato antigo (video_TIMESTAMP.mp4)
                    if (!$metadata && preg_match('/video_(\d+)\.mp4/', $videoName, $matches)) {
                        $timestamp = $matches[1];
                        $metadataFile = $dateDir . '/metadata_' . $timestamp . '.json';
                        if (file_exists($metadataFile)) {
                            $metadata = json_decode(file_get_contents($metadataFile), true);
                            $sessionId = 'legacy_' . $timestamp;
                        }
                    }
                    
                    $videos[] = [
                        'date' => $date,
                        'filename' => $videoName,
                        'path' => "view-video.php?user=" . urlencode($userId) . "&date=" . urlencode($date) . "&file=" . urlencode($videoName),
                        'size' => $videoSize,
                        'sessionId' => $sessionId,
                        'timestamp' => $metadata['timestamp'] ?? filemtime($videoFile),
                        'sessionDuration' => $metadata['sessionDuration'] ?? 0,
                        'frameCount' => $metadata['frameCount'] ?? $metadata['actualImageCount'] ?? 0,
                        'actualFrames' => $metadata['actualImageCount'] ?? $metadata['frameCount'] ?? 0,
                        'framerate' => $metadata['framerate'] ?? 10,
                        'effectiveFPS' => $metadata['effectiveFPS'] ?? 0,
                        'compressionRatio' => $metadata['compressionRatio'] ?? 0,
                        'platform' => $metadata['platform'] ?? 'unknown',
                        'appVersion' => $metadata['appVersion'] ?? 'unknown',
                        'metadata' => $metadata
                    ];
                }
            }
        }
    }
    
    // Ordenar por timestamp (mais recentes primeiro)
    usort($videos, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    return $videos;
}

// Função para obter dados detalhados de um usuário
function getUserData($baseDir, $userId) {
    $userData = [
        'userId' => $userId,
        'latestInfo' => null,
        'allSessions' => [],
        'allVideos' => [],
        'allEvents' => [],
        'timeline' => [],
        'totalEvents' => 0,
        'totalScreenshots' => 0,
        'totalVideos' => 0,
        'geoData' => null,
        'firstSeen' => null,
        'lastSeen' => null
    ];
    
    // Informações do usuário
    $latestFile = $baseDir . '/users/' . $userId . '/latest.json';
    if (file_exists($latestFile)) {
        $userData['latestInfo'] = json_decode(file_get_contents($latestFile), true);
        $userData['lastSeen'] = $userData['latestInfo']['receivedAt'] ?? null;
        $userData['geoData'] = $userData['latestInfo']['geo'] ?? null;
    }
    
    // Sessões de screenshots (tradicionais)
    $userData['allSessions'] = getUserSessions($baseDir, $userId);
    foreach ($userData['allSessions'] as $session) {
        $userData['totalScreenshots'] += $session['screenshotCount'];
                
        if ($session['metadata']) {
            $timestamp = $session['metadata']['timestamp'] ?? null;
            if ($timestamp) {
                if (!$userData['firstSeen'] || $timestamp < $userData['firstSeen']) {
                    $userData['firstSeen'] = $timestamp;
                    }
                if (!$userData['lastSeen'] || $timestamp > $userData['lastSeen']) {
                    $userData['lastSeen'] = $timestamp;
                    }
                }
        }
    }

    // Screenshots de eventos
    $userData['eventScreenshots'] = getUserEventScreenshots($baseDir, $userId);
    $userData['totalScreenshots'] += count($userData['eventScreenshots']);
    
    // Screenshots manuais
    $userData['manualScreenshots'] = getUserManualScreenshots($baseDir, $userId);
    $userData['totalScreenshots'] += count($userData['manualScreenshots']);
    
    // Vídeos
    $userData['allVideos'] = getUserVideos($baseDir, $userId);
    $userData['totalVideos'] = count($userData['allVideos']);
    
    // Eventos e linha do tempo
    $userData['allEvents'] = getUserEvents($baseDir, $userId);
    $userData['totalEvents'] = count($userData['allEvents']);
    $userData['timeline'] = getUserTimeline($baseDir, $userId);
    
    // Atualizar timestamps baseado nos eventos
    foreach ($userData['allEvents'] as $event) {
        $timestamp = $event['timestamp'] ?? null;
        if ($timestamp) {
            if (!$userData['firstSeen'] || $timestamp < $userData['firstSeen']) {
                $userData['firstSeen'] = $timestamp;
            }
            if (!$userData['lastSeen'] || $timestamp > $userData['lastSeen']) {
                $userData['lastSeen'] = $timestamp;
            }
        }
    }
    
    return $userData;
}

// Processamento de actions
$action = $_POST['action'] ?? $_GET['action'] ?? null;

if ($action) {
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'get_app':
            $bundleId = $_GET['bundleId'] ?? '';
            
            if (empty($bundleId)) {
                echo json_encode(['error' => 'bundleId é obrigatório']);
                exit;
            }
            
            $app = getApp($baseDir, $bundleId);
            
            if ($app) {
                echo json_encode(['success' => true, 'app' => $app]);
            } else {
                echo json_encode(['error' => 'App não encontrado']);
            }
            exit;
            
        case 'create_app':
            $bundleId = $_POST['bundleId'] ?? '';
            $name = $_POST['name'] ?? '';
            $platform = $_POST['platform'] ?? '';
            
            if (empty($bundleId) || empty($name) || empty($platform)) {
                echo json_encode(['error' => 'Campos obrigatórios: bundleId, name, platform']);
                exit;
            }
            
            $appsDir = $baseDir . '/apps';
            if (!is_dir($appsDir)) {
                mkdir($appsDir, 0755, true);
            }
            
            $appFile = $appsDir . '/' . $bundleId . '.json';
            
            if (file_exists($appFile)) {
                echo json_encode(['error' => 'App já existe']);
                exit;
            }
            
            $appData = [
                'bundleId' => $bundleId,
                'name' => $name,
                'platform' => $platform,
                'config' => [
                    'recordScreen' => false,
                    'framerate' => 10,
                    'screenSize' => 480
                ],
                'createdAt' => time(),
                'updatedAt' => time()
            ];
            
            file_put_contents($appFile, json_encode($appData, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'app' => $appData]);
            exit;
            
                    case 'update_app':
            $bundleId = $_POST['bundleId'] ?? '';
            $name = $_POST['name'] ?? '';
            $recordScreen = isset($_POST['recordScreen']) ? $_POST['recordScreen'] === 'true' : false;
            $framerate = isset($_POST['framerate']) ? (float)$_POST['framerate'] : null;
            $screenSize = isset($_POST['screenSize']) ? (int)$_POST['screenSize'] : null;
            
            // Log para debug
            error_log("Update App - bundleId: $bundleId");
            error_log("Update App - recordScreen received: " . ($_POST['recordScreen'] ?? 'NULL'));
            error_log("Update App - recordScreen processed: " . ($recordScreen ? 'true' : 'false'));
            
            if (empty($bundleId)) {
                echo json_encode(['error' => 'bundleId é obrigatório']);
                exit;
            }
            
            $appsDir = $baseDir . '/apps';
            $appFile = $appsDir . '/' . $bundleId . '.json';
            
            if (!file_exists($appFile)) {
                echo json_encode(['error' => 'App não encontrado']);
                exit;
            }
            
            $appData = json_decode(file_get_contents($appFile), true);
            
            if ($name) $appData['name'] = $name;
            $appData['config']['recordScreen'] = $recordScreen;
            if ($framerate !== null) $appData['config']['framerate'] = $framerate;
            if ($screenSize !== null) $appData['config']['screenSize'] = $screenSize;
            
            $appData['updatedAt'] = time();
            
            file_put_contents($appFile, json_encode($appData, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'app' => $appData]);
            exit;
            
        case 'delete_app':
            $bundleId = $_POST['bundleId'] ?? '';
            
            if (empty($bundleId)) {
                echo json_encode(['error' => 'bundleId é obrigatório']);
                exit;
            }
            
            $appsDir = $baseDir . '/apps';
            $appFile = $appsDir . '/' . $bundleId . '.json';
            
            if (!file_exists($appFile)) {
                echo json_encode(['error' => 'App não encontrado']);
                exit;
            }
            
            unlink($appFile);
            echo json_encode(['success' => true]);
            exit;
    }
}

// Obter dados para exibição
$apps = getApps($baseDir);
$selectedApp = $_GET['app'] ?? null;
$selectedUser = $_GET['user'] ?? null;
$currentApp = null;

// Validar se o app selecionado existe
if ($selectedApp) {
    $currentApp = getApp($baseDir, $selectedApp);
    if (!$currentApp) {
        $selectedApp = null;
        $selectedUser = null;
    }
}

$stats = $selectedApp ? getStats($baseDir, $selectedApp) : ['totalUsers' => 0, 'totalSessions' => 0, 'totalEvents' => 0, 'totalScreenshots' => 0, 'recentUsers' => []];

// Validar se o usuário selecionado realmente existe
if ($selectedApp && $selectedUser) {
    $userExists = false;
    foreach ($stats['recentUsers'] as $user) {
        if ($user['userId'] === $selectedUser) {
            $userExists = true;
            break;
        }
    }
    
    if (!$userExists) {
        $selectedUser = null;
    }
}

$userSessions = ($selectedApp && $selectedUser) ? getUserSessions($baseDir, $selectedUser) : [];
$userData = ($selectedApp && $selectedUser) ? getUserData($baseDir, $selectedUser) : null;

// Opções de tamanho de tela
$screenSizeOptions = [
    320 => '320px',
    360 => '360px', 
    375 => '375px',
    390 => '390px',
    400 => '400px',
    414 => '414px',
    480 => '480px',
    540 => '540px',
    600 => '600px',
    720 => '720px',
    768 => '768px',
    800 => '800px',
    900 => '900px',
    960 => '960px'
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard</title>
    
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <link rel="stylesheet" href="assets/style.css?v=<?= time() ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <h1><i class="fas fa-chart-line"></i> Analytics Dashboard</h1>
                <div class="header-stats">
                    <span class="live-indicator">
                        <i class="fas fa-circle"></i> Live
                    </span>
                    <span class="last-update">
                        Última atualização: <?= date('d/m/Y H:i:s') ?>
                    </span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            
            <?php if (!$selectedApp): ?>
            <!-- Tela de Seleção/Gestão de Apps -->
            <div class="app-management">
                <div class="page-header">
                    <h2><i class="fas fa-mobile-alt"></i> Gestão de Aplicativos</h2>
                    <button class="btn btn-primary" onclick="showCreateAppModal()">
                        <i class="fas fa-plus"></i> Novo Aplicativo
                    </button>
                </div>
                
                <div class="apps-grid">
                    <?php if (!empty($apps)): ?>
                        <?php foreach ($apps as $app): ?>
                        <?php
                        // Buscar ícone da App Store para cada app
                        $appStoreIcon = getAppStoreIcon($app['bundleId'], $baseDir);
                        ?>
                        <div class="app-card">
                            <div class="app-header">
                                <div class="app-icon-container">
                                    <?php if ($appStoreIcon): ?>
                                        <img src="<?= htmlspecialchars($appStoreIcon) ?>" alt="<?= htmlspecialchars($app['name']) ?>" class="app-store-icon">
                                    <?php else: ?>
                                <div class="app-icon">
                                            <i class="fab fa-<?= $app['platform'] === 'ios' ? 'apple' : 'android' ?>"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="app-info">
                                    <h3><?= htmlspecialchars($app['name']) ?></h3>
                                    <p><?= htmlspecialchars($app['bundleId']) ?></p>
                                    <span class="platform-badge <?= $app['platform'] ?>">
                                        <?= strtoupper($app['platform']) ?>
                                    </span>
                                </div>
                                <div class="app-actions">
                                    <button onclick="editApp('<?= htmlspecialchars($app['bundleId']) ?>')" class="btn-icon" title="Configurar">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <button onclick="deleteApp('<?= htmlspecialchars($app['bundleId']) ?>')" class="btn-icon danger" title="Deletar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="app-config">
                                <div class="config-item">
                                    <span class="config-label">Record Screen:</span>
                                    <span class="config-value <?= $app['config']['recordScreen'] ? 'active' : 'inactive' ?>">
                                        <i class="fas fa-<?= $app['config']['recordScreen'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                                        <?= $app['config']['recordScreen'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </div>
                                
                                <?php if ($app['config']['recordScreen']): ?>
                                <div class="config-item">
                                    <span class="config-label">Framerate:</span>
                                    <span class="config-value">
                                        <?php 
                                        $fps = (float)$app['config']['framerate'];
                                        if ($fps < 1) {
                                            echo number_format($fps, 1) . ' fps (1 frame a cada ' . number_format(1/$fps, 1) . 's)';
                                        } else {
                                            echo number_format($fps, 1) . ' fps';
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="config-item">
                                    <span class="config-label">Screen Size:</span>
                                    <span class="config-value"><?= $app['config']['screenSize'] ?>px</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="app-footer">
                                <a href="?app=<?= urlencode($app['bundleId']) ?>" class="btn btn-primary btn-block">
                                    <i class="fas fa-chart-bar"></i> Ver Analytics
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-mobile-alt"></i>
                            <h3>Nenhum aplicativo cadastrado</h3>
                            <p>Cadastre seu primeiro aplicativo para começar a coletar dados de analytics.</p>
                            <button class="btn btn-primary" onclick="showCreateAppModal()">
                                <i class="fas fa-plus"></i> Cadastrar Aplicativo
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php else: ?>
            <!-- Dashboard do App Selecionado -->
            <div class="app-dashboard">
                <!-- Breadcrumb e info do app -->
                <div class="app-breadcrumb">
                    <a href="?" class="breadcrumb-link">
                        <i class="fas fa-arrow-left"></i> Voltar aos Apps
                    </a>
                    <div class="current-app">
                        <div class="app-icon-container">
                            <?php
                            // Tentar buscar ícone da App Store pela API oficial
                            $appStoreIcon = getAppStoreIcon($currentApp['bundleId'], $baseDir);
                            ?>
                            
                            <?php if ($appStoreIcon): ?>
                                <img src="<?= htmlspecialchars($appStoreIcon) ?>" alt="<?= htmlspecialchars($currentApp['name']) ?>" class="app-store-icon">
                            <?php else: ?>
                        <div class="app-icon">
                                    <i class="fab fa-<?= $currentApp['platform'] === 'ios' ? 'apple' : 'android' ?>"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="app-details">
                            <h2>
                            <span class="platform-badge <?= $currentApp['platform'] ?>">
                                <?= strtoupper($currentApp['platform']) ?>
                            </span>
                                <?= htmlspecialchars($currentApp['name']) ?>
                            </h2>
                            <p><?= htmlspecialchars($currentApp['bundleId']) ?></p>
                        </div>
                        </div>
                        <button onclick="editApp('<?= htmlspecialchars($currentApp['bundleId']) ?>')" class="btn btn-secondary">
                            <i class="fas fa-cog"></i> Configurar
                        </button>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= number_format($stats['totalUsers']) ?></h3>
                            <p>Usuários Totais</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-video"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= number_format($stats['totalSessions']) ?></h3>
                            <p>Sessões Gravadas</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= number_format($stats['totalScreenshots']) ?></h3>
                            <p>Screenshots</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-mouse-pointer"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= number_format($stats['totalEvents']) ?></h3>
                            <p>Eventos Rastreados</p>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Grid -->
                <div class="dashboard-grid">
                    <!-- Coluna 1: Usuários -->
                    <div class="panel">
                        <div class="panel-header">
                            <h2><i class="fas fa-users"></i> Usuários Recentes</h2>
                        </div>
                        <div class="panel-content">
                            <div class="users-list">
                                <?php if (!empty($stats['recentUsers'])): ?>
                                    <?php foreach ($stats['recentUsers'] as $user): ?>
                                    <div class="user-item <?= $selectedUser === $user['userId'] ? 'active' : '' ?>">
                                        <a href="?app=<?= urlencode($selectedApp) ?>&user=<?= urlencode($user['userId']) ?>" class="user-link">
                                            <div class="user-avatar">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="user-info">
                                                <div class="user-id"><?= htmlspecialchars($user['userId']) ?></div>
                                                <div class="user-meta">
                                                    <small>
                                                        <i class="fas fa-clock"></i>
                                                        <?= date('d/m/Y H:i', (int)$user['lastSeen']) ?>
                                                    </small>
                                                </div>
                                                <?php if (!empty($user['userData']['appVersion'])): ?>
                                                <div class="user-version">
                                                    v<?= htmlspecialchars($user['userData']['appVersion']) ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="user-actions">
                                                <i class="fas fa-chevron-right"></i>
                                            </div>
                                        </a>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-users"></i>
                                        <h3>Nenhum usuário encontrado</h3>
                                        <p>Aguardando dados de usuários deste app...</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Coluna 2: Dados do Usuário -->
                    <div class="panel">
                        <div class="panel-header">
 
                            <h2>
                                <i class="fas fa-user-cog"></i> 
                                <?php if ($selectedUser): ?>
                                    Dados do Usuário - <?= htmlspecialchars($selectedUser) ?>
                                <?php else: ?>
                                    Informações do Usuário
                                <?php endif; ?>
                            </h2>
                            <div class="panel-actions">
                                <?php if ($selectedUser): ?>
                                <button onclick="deleteUserData('<?= htmlspecialchars($selectedUser) ?>')" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Deletar Dados
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="panel-content">
                            <?php if ($selectedUser && $userData): ?>
                            
                            <!-- Layout Principal: Duas Colunas -->
                            <div class="user-layout-container">
                                
                                <!-- Coluna Esquerda: Dados do Usuário -->
                            <div class="user-details">
                                    <!-- Botão Atividades do Usuário (movido para cima) -->
                                    <div class="user-activities-button-container">
                                        <button class="open-tabs-overlay-btn" onclick="openTabsOverlay()">
                                            <div class="tabs-summary-content">
                                                <h3><i class="fas fa-chart-line"></i> Atividades do Usuário</h3>
                                                <div class="tabs-summary-stats">
                                                    <div class="summary-stat">
                                                        <i class="fas fa-history"></i>
                                                        <span><?= $userData['totalEvents'] ?> Eventos</span>
                                                    </div>
                                                    <div class="summary-stat">
                                                        <i class="fas fa-film"></i>
                                                        <span><?= $userData['totalVideos'] ?> Vídeos</span>
                                                    </div>
                                                    <div class="summary-stat">
                                                        <i class="fas fa-camera"></i>
                                                        <span><?= $userData['totalScreenshots'] ?> Screenshots</span>
                                                    </div>
                                                </div>
                                                <div class="open-overlay-hint">
                                                    <i class="fas fa-expand"></i>
                                                    Clique para visualizar detalhes
                                                </div>
                                            </div>
                                        </button>
                                    </div>

                                    <!-- Grid 2x2 para Detail Sections -->
                                    <div class="user-details-grid">
                                <!-- Identificação -->
                                <div class="detail-section">
                                    <h3><i class="fas fa-id-card"></i> Identificação</h3>
                                    <div class="detail-grid">
                                        <div class="detail-item">
                                            <label>User ID:</label>
                                            <span><?= htmlspecialchars($userData['userId']) ?></span>
                                        </div>
                                        <?php if ($userData['firstSeen']): ?>
                                        <div class="detail-item">
                                            <label>Primeiro acesso:</label>
                                            <span><?= date('d/m/Y H:i:s', (int)$userData['firstSeen']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($userData['lastSeen']): ?>
                                        <div class="detail-item">
                                            <label>Último acesso:</label>
                                            <span><?= date('d/m/Y H:i:s', (int)$userData['lastSeen']) ?></span>
                                        </div>
                                        <?php endif; ?>

                                                <!-- Dados de Localização -->
                                                <?php if (!empty($userData['geoData'])): ?>
                                                    <?php if (!empty($userData['geoData']['country'])): ?>
                                        <div class="detail-item">
                                                        <label>País:</label>
                                                        <span>
                                                            <?php if (!empty($userData['geoData']['flag'])): ?>
                                                                <?= $userData['geoData']['flag'] ?> 
                                                            <?php endif; ?>
                                                            <?= htmlspecialchars($userData['geoData']['country']) ?>
                                                        </span>
                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($userData['geoData']['region'])): ?>
                                        <div class="detail-item">
                                                        <label>Estado/Região:</label>
                                                        <span><?= htmlspecialchars($userData['geoData']['region']) ?></span>
                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($userData['geoData']['city'])): ?>
                                        <div class="detail-item">
                                                        <label>Cidade:</label>
                                                        <span><?= htmlspecialchars($userData['geoData']['city']) ?></span>
                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($userData['geoData']['ip'])): ?>
                                                    <div class="detail-item">
                                                        <label>IP:</label>
                                                        <span><?= htmlspecialchars($userData['geoData']['ip']) ?></span>
                                                    </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Dados do App -->
                                        <?php if (!empty($userData['latestInfo']['userData']) || !empty($userData['latestInfo']['deviceInfo'])): ?>
                                <div class="detail-section">
                                    <h3><i class="fas fa-mobile-alt"></i> Dados do App</h3>
                                    <div class="detail-grid">
                                                <?php if (!empty($userData['latestInfo']['deviceInfo'])): ?>
                                                    <?php $deviceInfo = $userData['latestInfo']['deviceInfo']; ?>
                                                    <div class="detail-item">
                                                        <label>App Version:</label>
                                                        <span><?= htmlspecialchars($deviceInfo['appVersion'] ?? '1.0.0') ?></span>
                                                    </div>
                                                    <div class="detail-item">
                                                        <label>Device:</label>
                                                        <span><?= htmlspecialchars($deviceInfo['device'] ?? 'Unknown Device') ?></span>
                                                    </div>
                                                    <div class="detail-item">
                                                        <label>Platform:</label>
                                                        <span><?= htmlspecialchars($deviceInfo['platform'] ?? 'iOS') ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($userData['latestInfo']['userData'])): ?>
                                        <?php foreach ($userData['latestInfo']['userData'] as $key => $value): ?>
                                                        <?php if (!in_array($key, ['appVersion', 'device', 'platform', 'interactionCount', 'bundleId', 'lastAction', 'sessionStartTime', 'environment', 'initializedAt', 'initTime', 'initializeMethod', 'deviceIdentifier', 'deviceCommercialName'])): ?>
                                        <div class="detail-item">
                                            <label><?= htmlspecialchars($key) ?>:</label>
                                            <span><?= htmlspecialchars(is_array($value) ? json_encode($value) : $value) ?></span>
                                        </div>
                                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <!-- Placeholder quando não há dados do app -->
                                        <div class="detail-section detail-section-empty">
                                            <h3><i class="fas fa-mobile-alt"></i> Dados do App</h3>
                                            <div class="detail-grid">
                                                <div class="empty-data">
                                                    <p>Nenhum dado disponível</p>
                                                </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Overlay das Abas de Conteúdo -->
                            <div id="tabsOverlay" class="tabs-overlay">
                                <div class="tabs-overlay-content">
                                    <div class="tabs-overlay-header">
                                        <h2><i class="fas fa-user-cog"></i> Atividades - <?= htmlspecialchars($selectedUser) ?></h2>
                                        <button class="close-overlay-btn" onclick="closeTabsOverlay()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="tabs-container">
                                        <div class="tabs-header">
                                            <button class="tab-btn active" onclick="showTab('timeline')">
                                                <i class="fas fa-history"></i> Linha do Tempo (<?= $userData['totalEvents'] ?>)
                                            </button>
                                            <button class="tab-btn" onclick="showTab('videos')">
                                                <i class="fas fa-film"></i> Vídeos (<?= $userData['totalVideos'] ?>)
                                            </button>
                                            <button class="tab-btn" onclick="showTab('sessions')">
                                                <i class="fas fa-camera"></i> Screenshots (<?= $userData['totalScreenshots'] ?>)
                                            </button>
                                        </div>
                                        
                                        <!-- Aba Linha do Tempo - VERTICAL -->
                                        <div id="timeline-tab" class="tab-content active">
                                            <h3><i class="fas fa-history"></i> Linha do Tempo de Eventos</h3>
                                            <?php if (!empty($userData['timeline'])): ?>
                                            <div class="timeline-vertical-container">
                                                <?php foreach ($userData['timeline'] as $date => $dayEvents): ?>
                                                    <?php foreach ($dayEvents as $event): ?>
                                                    <div class="timeline-vertical-event">
                                                        <!-- Thumbnail do screenshot do evento -->
                                                        <?php if (!empty($event['hasScreenshot']) && !empty($event['screenshot'])): ?>
                                                        <div class="event-thumbnail" 
                                                             onclick="openScreenshotModal('<?= htmlspecialchars($event['screenshot']['url']) ?>', '<?= htmlspecialchars($event['event']) ?>')"
                                                             style="cursor: pointer;">
                                                            <img src="<?= htmlspecialchars($event['screenshot']['url']) ?>" 
                                                                 alt="Event screenshot" 
                                                                 loading="lazy">
                                                        </div>
                                                        <?php else: ?>
                                                        <div class="event-thumbnail no-screenshot">
                                                            <i class="fas fa-camera"></i>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <div class="event-time-info">
                                                            <div class="event-date">
                                                                <?= date('d/m/Y', $event['timestamp']) ?>
                                                            </div>
                                                            <div class="event-time">
                                                                <?= date('H:i:s', $event['timestamp']) ?>
                                                            </div>
                                                        </div>
                                                        <div class="event-marker-vertical">
                                                            <i class="fas fa-circle"></i>
                                                        </div>
                                                        <div class="event-content">
                                                            <div class="event-name">
                                                                <i class="fas fa-tag"></i>
                                                                <?= htmlspecialchars($event['event']) ?>
                                                                <?php if (!empty($event['hasScreenshot'])): ?>
                                                                <span class="screenshot-indicator">
                                                                    <i class="fas fa-camera" title="Evento com screenshot"></i>
                                                                </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <?php if (!empty($event['value'])): ?>
                                                            <div class="event-value">
                                                                <i class="fas fa-info-circle"></i>
                                                                <?= htmlspecialchars($event['value']) ?>
                                        </div>
                                        <?php endif; ?>
                                                            <?php if (!empty($event['geo']) && !empty($event['geo']['flag']) && (!isset($event['geo']['error']) || $event['geo']['country'] !== 'Unknown')): ?>
                                                            <div class="event-location">
                                                                <i class="fas fa-globe"></i>
                                                                <?= $event['geo']['flag'] ?> 
                                                                <?php if (!empty($event['geo']['country']) && $event['geo']['country'] !== 'Unknown'): ?>
                                                                    <?= htmlspecialchars($event['geo']['country']) ?>
                                                                    <?php if (!empty($event['geo']['city']) && $event['geo']['city'] !== 'Unknown'): ?>
                                                                        , <?= htmlspecialchars($event['geo']['city']) ?>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <?= htmlspecialchars($event['geo']['city'] ?? 'Localização indisponível') ?>
                                                                <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php else: ?>
                                            <div class="empty-timeline">
                                                <div class="empty-icon">
                                                    <i class="fas fa-chart-line"></i>
                                                </div>
                                                <div class="empty-content">
                                                    <h4>Linha do Tempo Vazia</h4>
                                                    <p>Os eventos do usuário aparecerão aqui conforme forem sendo capturados pelo aplicativo.</p>
                                                    <div class="empty-features">
                                                        <div class="feature-item">
                                                            <i class="fas fa-mouse-pointer"></i>
                                                            <span>Cliques e interações</span>
                                                        </div>
                                                        <div class="feature-item">
                                                            <i class="fas fa-camera"></i>
                                                            <span>Screenshots automáticos</span>
                                                        </div>
                                                        <div class="feature-item">
                                                            <i class="fas fa-map-marker-alt"></i>
                                                            <span>Dados de localização</span>
                                                        </div>
                                                    </div>
                                                </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                        
                                        <!-- Aba Vídeos - BOXES MENORES -->
                                        <div id="videos-tab" class="tab-content">
                                            <h3><i class="fas fa-film"></i> Sessões Gravadas</h3>
                                            <?php if (!empty($userData['allVideos'])): ?>
                                            <div class="videos-grid-compact">
                                                <?php foreach ($userData['allVideos'] as $video): ?>
                                                <div class="video-card-compact">
                                                    <div class="video-thumbnail-compact">
                                                        <video preload="metadata" muted>
                                                            <source src="<?= htmlspecialchars($video['path']) ?>" type="video/mp4">
                                                        </video>
                                                        <div class="video-overlay-compact">
                                                            <button class="play-video-btn-compact" onclick="playVideo('<?= htmlspecialchars($video['path']) ?>')">
                                                                <i class="fas fa-play"></i>
                                                            </button>
                                                        </div>
                                                        <div class="video-duration-compact">
                                                            <i class="fas fa-clock"></i>
                                                            <?php 
                                                            if ($video['sessionDuration'] > 0) {
                                                                $duration = $video['sessionDuration'];
                                                                echo sprintf('%02d:%02d', floor($duration / 60), $duration % 60);
                                                            } else {
                                                                echo '00:00';
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                    <div class="video-info-compact">
                                                        <h4><?= date('d/m H:i', $video['timestamp']) ?></h4>
                                                        <p><i class="fas fa-hdd"></i> <?= number_format($video['size'] / 1024 / 1024, 1) ?> MB</p>
                                                        <?php if ($video['sessionDuration'] > 0): ?>
                                                        <p><i class="fas fa-stopwatch"></i> <?= number_format($video['sessionDuration'], 1) ?>s</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php else: ?>
                                            <div class="empty-videos">
                                                <div class="empty-icon">
                                                    <i class="fas fa-video"></i>
                                                </div>
                                                <div class="empty-content">
                                                    <h4>Nenhuma Sessão Gravada</h4>
                                                    <p>As sessões de vídeo aparecerão aqui quando o aplicativo gravar a interação do usuário.</p>
                                                    <div class="empty-features">
                                                        <div class="feature-item">
                                                            <i class="fas fa-mobile-alt"></i>
                                                            <span>App entra em background</span>
                                                        </div>
                                                        <div class="feature-item">
                                                            <i class="fas fa-record-vinyl"></i>
                                                            <span>Gravação automática</span>
                                                        </div>
                                                        <div class="feature-item">
                                                            <i class="fas fa-play-circle"></i>
                                                            <span>Reprodução completa</span>
                                                        </div>
                                                    </div>
                                                    <div class="empty-hint">
                                                        <i class="fas fa-info-circle"></i>
                                                        <span>A gravação deve estar ativada nas configurações do app</span>
                                                    </div>
                                                </div>
                                </div>
                                <?php endif; ?>
                            </div>

                                        <!-- Aba Sessões (Screenshots) -->
                                        <div id="sessions-tab" class="tab-content">
                                            <h3><i class="fas fa-camera"></i> Screenshots</h3>
                                            
                                            <?php 
                                            $hasTraditionalSessions = !empty($userSessions);
                                            $hasEventScreenshots = !empty($userData['eventScreenshots']);
                                            $hasManualScreenshots = !empty($userData['manualScreenshots']);
                                            $hasAnyScreenshots = $hasTraditionalSessions || $hasEventScreenshots || $hasManualScreenshots;
                                            ?>
                                            
                                            <?php if ($hasAnyScreenshots): ?>
                                            
                                            <!-- Screenshots Manuais -->
                                            <?php if ($hasManualScreenshots): ?>
                                            <div class="screenshots-section">
                                                <h4><i class="fas fa-camera-retro"></i> Screenshots Manuais (<?= count($userData['manualScreenshots']) ?>)</h4>
                                                <div class="event-screenshots-grid">
                                                    <?php foreach ($userData['manualScreenshots'] as $manualScreenshot): ?>
                                                    <div class="event-screenshot-card" 
                                                         onclick="openScreenshotModal('<?= htmlspecialchars($manualScreenshot['path']) ?>', 'Screenshot Manual <?= $manualScreenshot['width'] ?>x<?= $manualScreenshot['height'] ?>')"
                                                         style="cursor: pointer;">
                                                        <div class="event-screenshot-thumbnail">
                                                            <img src="<?= htmlspecialchars($manualScreenshot['path']) ?>" 
                                                                 alt="Manual screenshot" loading="lazy">
                                                            <div class="event-screenshot-overlay">
                                                                <i class="fas fa-search-plus"></i>
                                                            </div>
                                                        </div>
                                                        <div class="event-screenshot-info">
                                                            <h5>Manual <?= $manualScreenshot['width'] ?>x<?= $manualScreenshot['height'] ?></h5>
                                                            <p><i class="fas fa-calendar"></i> <?= date('d/m H:i', $manualScreenshot['timestamp']) ?></p>
                                                            <p><i class="fas fa-hdd"></i> <?= number_format($manualScreenshot['size'] / 1024, 1) ?> KB</p>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <!-- Screenshots de Eventos -->
                                            <?php if ($hasEventScreenshots): ?>
                                            <div class="screenshots-section">
                                                <h4><i class="fas fa-tag"></i> Screenshots de Eventos (<?= count($userData['eventScreenshots']) ?>)</h4>
                                                <div class="event-screenshots-grid">
                                                    <?php foreach ($userData['eventScreenshots'] as $eventScreenshot): ?>
                                                    <div class="event-screenshot-card" 
                                                         onclick="openScreenshotModal('<?= htmlspecialchars($eventScreenshot['path']) ?>', '<?= htmlspecialchars($eventScreenshot['eventName']) ?>')"
                                                         style="cursor: pointer;">
                                                        <div class="event-screenshot-thumbnail">
                                                            <img src="<?= htmlspecialchars($eventScreenshot['path']) ?>" 
                                                                 alt="Event screenshot" loading="lazy">
                                                            <div class="event-screenshot-overlay">
                                                                <i class="fas fa-search-plus"></i>
                                                            </div>
                                                        </div>
                                                        <div class="event-screenshot-info">
                                                            <h5><?= htmlspecialchars($eventScreenshot['eventName']) ?></h5>
                                                            <p><i class="fas fa-calendar"></i> <?= date('d/m H:i', $eventScreenshot['timestamp']) ?></p>
                                                            <p><i class="fas fa-hdd"></i> <?= number_format($eventScreenshot['size'] / 1024, 1) ?> KB</p>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <!-- Sessões Tradicionais -->
                                            <?php if ($hasTraditionalSessions): ?>
                                            <div class="screenshots-section">
                                                <h4><i class="fas fa-images"></i> Sessões de Screenshots (<?= count($userSessions) ?>)</h4>
                                                <div class="sessions-grid">
                                                    <?php foreach ($userSessions as $session): ?>
                                                    <div class="session-card">
                                                        <div class="session-thumbnail">
                                                            <?php if ($session['firstScreenshot']): ?>
                                                            <img src="view-screenshot.php?user=<?= urlencode($selectedUser) ?>&date=<?= urlencode($session['date']) ?>&file=<?= urlencode($session['firstScreenshot']) ?>" 
                                                                 alt="Session thumbnail" loading="lazy">
                                                            <?php else: ?>
                                                            <div class="no-thumbnail">
                                                                <i class="fas fa-image"></i>
                                                            </div>
                                                            <?php endif; ?>
                                                            <div class="session-overlay">
                                                                <button class="play-btn" onclick="playSession('<?= $selectedUser ?>', '<?= $session['date'] ?>')">
                                                                    <i class="fas fa-play"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div class="session-info">
                                                            <h3><?= date('d/m/Y', strtotime($session['date'])) ?></h3>
                                                            <p><i class="fas fa-camera"></i> <?= $session['screenshotCount'] ?> screenshots</p>
                                                            <?php if (!empty($session['metadata']['timestamp'])): ?>
                                                            <p><i class="fas fa-clock"></i> <?= date('H:i', (int)$session['metadata']['timestamp']) ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php else: ?>
                                            <div class="empty-sessions">
                                                <div class="empty-icon">
                                                    <i class="fas fa-images"></i>
                                                </div>
                                                <div class="empty-content">
                                                    <h4>Nenhum Screenshot Encontrado</h4>
                                                    <p>Screenshots de eventos, manuais e sessões aparecerão aqui quando forem capturados.</p>
                                                    <div class="empty-features">
                                                        <div class="feature-item">
                                                            <i class="fas fa-tag"></i>
                                                            <span>Screenshots de eventos</span>
                                                        </div>
                                                        <div class="feature-item">
                                                            <i class="fas fa-hand-pointer"></i>
                                                            <span>Screenshots manuais</span>
                                                        </div>
                                                        <div class="feature-item">
                                                            <i class="fas fa-clock"></i>
                                                            <span>Screenshots de sessão</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-hand-point-left"></i>
                                <h3>Selecione um usuário</h3>
                                <p>Escolha um usuário da lista ao lado para ver informações detalhadas e suas sessões.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal para Criar App -->
    <div id="createAppModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> Novo Aplicativo</h2>
                <button class="modal-close" onclick="closeCreateAppModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="createAppForm">
                    <div class="form-group">
                        <label for="appName">Nome do Aplicativo *</label>
                        <input type="text" id="appName" name="name" required placeholder="Meu App">
                    </div>
                    
                    <div class="form-group">
                        <label for="bundleId">Bundle ID *</label>
                        <input type="text" id="bundleId" name="bundleId" required placeholder="com.empresa.meuapp">
                        <small>Identificador único do aplicativo (ex: com.empresa.app)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="platform">Plataforma *</label>
                        <select id="platform" name="platform" required>
                            <option value="">Selecione a plataforma</option>
                            <option value="ios">iOS</option>
                            <option value="android">Android</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" onclick="closeCreateAppModal()" class="btn btn-secondary">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Criar Aplicativo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Editar App -->
    <div id="editAppModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-cog"></i> Configurar Aplicativo</h2>
                <button class="modal-close" onclick="closeEditAppModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="editAppForm">
                    <input type="hidden" id="editBundleId" name="bundleId">
                    
                    <div class="form-group">
                        <label for="editAppName">Nome do Aplicativo</label>
                        <input type="text" id="editAppName" name="name" placeholder="Meu App">
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="recordScreen" name="recordScreen">
                            <span class="checkmark"></span>
                            Record Screen (Ativar gravação de screenshots)
                        </label>
                    </div>
                    
                    <div id="recordingSettings" class="recording-settings" style="display: none;">
                        <div class="form-group">
                            <label for="framerateSelect">Framerate Predefinido:</label>
                            <select id="framerateSelect" onchange="setFramerate(this.value)">
                                <option value="custom">Personalizado</option>
                                <option value="0.1">0.1 fps (1 frame a cada 10s)</option>
                                <option value="0.2">0.2 fps (1 frame a cada 5s)</option>
                                <option value="0.5">0.5 fps (1 frame a cada 2s)</option>
                                <option value="1">1 fps (1 frame por segundo)</option>
                                <option value="2">2 fps</option>
                                <option value="5">5 fps</option>
                                <option value="10">10 fps (recomendado)</option>
                                <option value="15">15 fps</option>
                                <option value="20">20 fps</option>
                                <option value="30">30 fps</option>
                                <option value="60">60 fps (máximo)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="framerate">Framerate Personalizado: <span id="framerateValue">10</span> fps</label>
                            <input type="range" id="framerate" name="framerate" min="0.1" max="60" step="0.1" value="10" oninput="updateFramerateValue(this.value)">
                            <div class="range-labels">
                                <span>0.1 fps</span>
                                <span>60 fps</span>
                            </div>
                            <small>Use valores baixos (0.1-1 fps) para capturas espaçadas que economizam recursos</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="screenSize">Tamanho da Tela</label>
                            <select id="screenSize" name="screenSize">
                                <?php foreach ($screenSizeOptions as $value => $label): ?>
                                <option value="<?= $value ?>"><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" onclick="closeEditAppModal()" class="btn btn-secondary">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Session Player Modal -->
    <div id="sessionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Reproduzindo Sessão</h2>
                <button class="modal-close" onclick="closeSessionModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="video-player">
                    <div class="video-container">
                        <img id="sessionFrame" src="" alt="Session frame">
                        <div class="video-controls">
                            <button id="playPauseBtn" onclick="togglePlayPause()">
                                <i class="fas fa-play"></i>
                            </button>
                            <div class="progress-container">
                                <input type="range" id="progressBar" min="0" max="100" value="0" onchange="seekTo(this.value)">
                                <div class="progress-fill"></div>
                            </div>
                            <span id="timeDisplay">00:00 / 00:00</span>
                            <button onclick="changeSpeed()">
                                <span id="speedDisplay">1x</span>
                            </button>
                        </div>
                    </div>
                    <div class="session-info-panel">
                        <h3>Informações da Sessão</h3>
                        <div id="sessionMetadata"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/script.js?v=<?= time() ?>"></script>
    <script>
        // Funções para controlar overlay das abas
        function openTabsOverlay() {
            const overlay = document.getElementById('tabsOverlay');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevenir scroll do body
        }
        
        function closeTabsOverlay() {
            const overlay = document.getElementById('tabsOverlay');
            overlay.classList.remove('active');
            document.body.style.overflow = 'auto'; // Restaurar scroll do body
        }
        
        // Fechar overlay com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeTabsOverlay();
            }
        });
        
        // Fechar overlay clicando fora
        document.addEventListener('click', function(e) {
            const overlay = document.getElementById('tabsOverlay');
            if (e.target === overlay) {
                closeTabsOverlay();
            }
        });

        // Funções para controlar abas
        function showTab(tabName) {
            // Ocultar todas as abas
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remover classe active de todos os botões
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Mostrar aba selecionada
            const selectedTab = document.getElementById(tabName + '-tab');
            if (selectedTab) {
                selectedTab.classList.add('active');
            }
            
            // Ativar botão correspondente
            const selectedButton = document.querySelector(`[onclick="showTab('${tabName}')"]`);
            if (selectedButton) {
                selectedButton.classList.add('active');
            }
        }
        
        // Função para reproduzir vídeo
        function playVideo(videoPath) {
            console.log('🎬 Reproduzindo vídeo:', videoPath);
            
            // TESTE SIMPLES: Criar um alert primeiro para verificar se a função está sendo chamada
            // alert('Função playVideo foi chamada para: ' + videoPath);
            
            // Remover qualquer modal existente
            const existingModal = document.querySelector('.video-modal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Criar modal de vídeo
            const modal = document.createElement('div');
            modal.className = 'video-modal';
            
            // Aplicar estilos inline para garantir exibição com !important
            modal.style.cssText = `
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                background: rgba(0, 0, 0, 0.9) !important;
                z-index: 999999 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                opacity: 1 !important;
            `;
            
            modal.innerHTML = `
                <div class="video-modal-content" style="
                    position: relative;
                    max-width: 95vw;
                    max-height: 95vh;
                    background: white;
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
                ">
                    <div class="video-modal-header" style="
                        padding: 1rem 1.5rem;
                        background: #667eea;
                        color: white;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    ">
                        <h3 style="margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-film"></i> Reproduzindo Sessão Gravada
                        </h3>
                        <button class="video-modal-close" onclick="closeVideoModal()" style="
                            background: none;
                            border: none;
                            color: white;
                            font-size: 1.5rem;
                            cursor: pointer;
                            padding: 0.5rem;
                            border-radius: 6px;
                            transition: background 0.3s ease;
                        ">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="video-modal-body" style="
                        padding: 0;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background: #000;
                    ">
                        <video id="mainVideo" controls autoplay style="width: 100%; max-height: 80vh;">
                            <source src="${videoPath}" type="video/mp4">
                            Seu navegador não suporta reprodução de vídeo.
                        </video>
                    </div>
                </div>
            `;
            
            console.log('📝 Modal HTML criado');
            
            document.body.appendChild(modal);
            document.body.style.overflow = 'hidden';
            
            console.log('🎯 Modal adicionado ao DOM');
            console.log('Modal element:', modal);
            console.log('Modal style display:', modal.style.display);
            console.log('Modal computed style:', window.getComputedStyle(modal).display);
            
            // Verificar se o modal está visível
            setTimeout(() => {
                const rect = modal.getBoundingClientRect();
                console.log('📏 Modal position and size:', {
                    top: rect.top,
                    left: rect.left,
                    width: rect.width,
                    height: rect.height,
                    visible: rect.width > 0 && rect.height > 0
                });
            }, 100);
            
            // Configurar evento de teclado para fechar
            const escapeHandler = function(e) {
                if (e.key === 'Escape') {
                    closeVideoModal();
                    document.removeEventListener('keydown', escapeHandler);
                }
            };
            document.addEventListener('keydown', escapeHandler);
            
            // Configurar clique fora para fechar
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeVideoModal();
                }
            });
            
            console.log('✅ Modal de vídeo criado e exibido com estilos inline');
        }
        
        // Função para fechar modal de vídeo
        function closeVideoModal() {
            console.log('🚪 Fechando modal de vídeo');
            const modal = document.querySelector('.video-modal');
            if (modal) {
                const video = modal.querySelector('video');
                if (video) {
                    video.pause();
                    video.currentTime = 0;
                }
                modal.remove();
                document.body.style.overflow = 'auto';
            }
        }
        
        // Função para deletar dados do usuário
        function deleteUserData(userId) {
            if (confirm('Tem certeza que deseja deletar TODOS os dados deste usuário?\n\nIsso incluirá:\n• Screenshots de sessão e manuais\n• Screenshots de eventos\n• Vídeos de sessão\n• Eventos rastreados\n• Informações pessoais\n\nEsta ação NÃO pode ser desfeita!')) {
                const loadingMessage = 'Deletando dados do usuário...';
                
                // Mostrar loading
                const originalButton = event.target;
                originalButton.disabled = true;
                originalButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deletando...';
                
                fetch('/delete-user?userId=' + encodeURIComponent(userId), {
                    method: 'GET'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let message = `Dados do usuário deletados com sucesso!\n\nResumo da exclusão:\n`;
                        
                        if (data.deletedDirs && data.deletedDirs.length > 0) {
                            data.deletedDirs.forEach(dir => {
                                message += `• ${dir.type}: ${dir.size}\n`;
                            });
                            message += `\nTotal removido: ${data.totalSize}`;
                        } else {
                            message += 'Nenhum dado foi encontrado para este usuário.';
                        }
                        
                        alert(message);
                        
                        // Voltar para a lista de usuários
                        window.location.href = '?app=' + encodeURIComponent('<?= $selectedApp ?>');
                    } else {
                        alert('Erro ao deletar dados do usuário: ' + (data.error || 'Erro desconhecido'));
                        
                        // Restaurar botão
                        originalButton.disabled = false;
                        originalButton.innerHTML = '<i class="fas fa-trash"></i> Deletar Dados';
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao deletar dados do usuário');
                    
                    // Restaurar botão
                    originalButton.disabled = false;
                    originalButton.innerHTML = '<i class="fas fa-trash"></i> Deletar Dados';
                });
            }
        }
        
        // Funções específicas do dashboard
        
        function showCreateAppModal() {
            document.getElementById('createAppModal').style.display = 'flex';
        }
        
        function closeCreateAppModal() {
            document.getElementById('createAppModal').style.display = 'none';
            document.getElementById('createAppForm').reset();
        }
        
        function editApp(bundleId) {
            // Buscar dados do app
            fetch('?action=get_app&bundleId=' + encodeURIComponent(bundleId))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const app = data.app;
                        console.log('App carregado:', app);
                        console.log('Record Screen value:', app.config.recordScreen);
                        
                        document.getElementById('editBundleId').value = app.bundleId;
                        document.getElementById('editAppName').value = app.name;
                        document.getElementById('recordScreen').checked = app.config.recordScreen;
                        document.getElementById('framerate').value = app.config.framerate;
                        document.getElementById('screenSize').value = app.config.screenSize;
                        
                        updateFramerateValue(app.config.framerate);
                        toggleRecordingSettings();
                        
                        document.getElementById('editAppModal').style.display = 'flex';
                    } else {
                        console.error('Erro ao buscar app:', data.error);
                        alert('Erro ao carregar dados do app: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição:', error);
                    // Fallback para dados locais
                    const apps = <?= json_encode($apps) ?>;
                    const app = apps.find(a => a.bundleId === bundleId);
                    
                    if (app) {
                        console.log('Usando dados locais:', app);
                        document.getElementById('editBundleId').value = app.bundleId;
                        document.getElementById('editAppName').value = app.name;
                        document.getElementById('recordScreen').checked = app.config.recordScreen;
                        document.getElementById('framerate').value = app.config.framerate;
                        document.getElementById('screenSize').value = app.config.screenSize;
                        
                        updateFramerateValue(app.config.framerate);
                        toggleRecordingSettings();
                        
                        document.getElementById('editAppModal').style.display = 'flex';
                    } else {
                        alert('App não encontrado');
                    }
                });
        }
        
        function closeEditAppModal() {
            document.getElementById('editAppModal').style.display = 'none';
            document.getElementById('editAppForm').reset();
        }
        
        function deleteApp(bundleId) {
            if (confirm('Tem certeza que deseja deletar este aplicativo? Esta ação não pode ser desfeita.')) {
                const formData = new FormData();
                formData.append('action', 'delete_app');
                formData.append('bundleId', bundleId);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erro ao deletar aplicativo: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Erro ao deletar aplicativo');
                });
            }
        }
        
        function updateFramerateValue(value) {
            const numValue = parseFloat(value);
            document.getElementById('framerateValue').textContent = numValue.toFixed(1);
            
            // Atualizar o select se corresponder a um valor predefinido
            const select = document.getElementById('framerateSelect');
            const matchingOption = Array.from(select.options).find(option => 
                option.value !== 'custom' && Math.abs(parseFloat(option.value) - numValue) < 0.05
            );
            
            if (matchingOption) {
                select.value = matchingOption.value;
            } else {
                select.value = 'custom';
            }
        }
        
        function setFramerate(value) {
            if (value !== 'custom') {
                const framerateSlider = document.getElementById('framerate');
                framerateSlider.value = value;
                updateFramerateValue(value);
            }
        }
        
        function toggleRecordingSettings() {
            const recordScreen = document.getElementById('recordScreen').checked;
            const settings = document.getElementById('recordingSettings');
            settings.style.display = recordScreen ? 'block' : 'none';
        }
        
        // Event listeners
        document.getElementById('recordScreen').addEventListener('change', toggleRecordingSettings);
        
        document.getElementById('createAppForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create_app');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro ao criar aplicativo: ' + data.error);
                }
            })
            .catch(error => {
                alert('Erro ao criar aplicativo');
            });
        });
        
        document.getElementById('editAppForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_app');
            
            // Garantir que o checkbox seja enviado corretamente
            const recordScreenCheckbox = document.getElementById('recordScreen');
            const recordScreenValue = recordScreenCheckbox.checked ? 'true' : 'false';
            console.log('Checkbox checked:', recordScreenCheckbox.checked);
            console.log('Enviando recordScreen:', recordScreenValue);
            
            if (recordScreenCheckbox.checked) {
                formData.set('recordScreen', 'true');
            } else {
                formData.set('recordScreen', 'false');
            }
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Resposta do servidor:', data);
                if (data.success) {
                    console.log('App atualizado com sucesso');
                    location.reload();
                } else {
                    alert('Erro ao atualizar aplicativo: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Erro no fetch:', error);
                alert('Erro ao atualizar aplicativo');
            });
        });
        
        // Fechar modais ao clicar fora
        window.addEventListener('click', function(e) {
            const createModal = document.getElementById('createAppModal');
            const editModal = document.getElementById('editAppModal');
            
            if (e.target === createModal) {
                closeCreateAppModal();
            }
            if (e.target === editModal) {
                closeEditAppModal();
            }
        });

        // Funções para modal de screenshot - VERSÃO SUPER SIMPLES
        function openScreenshotModal(imageUrl, eventName) {
            console.log('🎯 Modal chamado:', imageUrl);
            
            // Versão super simples que sempre funciona
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0, 0, 0, 0.9);
                z-index: 99999;
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;
            
            modal.innerHTML = `
                <div style="position: relative; max-width: 90vw; max-height: 90vh; background: white; border-radius: 12px; overflow: hidden;">
                    <div style="padding: 1rem 1.5rem; background: #667eea; color: white; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0;">Screenshot: ${eventName}</h3>
                        <button onclick="closeScreenshotModal()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">✕</button>
                    </div>
                    <div style="padding: 0; text-align: center;">
                        <img src="${imageUrl}" alt="Screenshot" style="max-width: 100%; max-height: 83vh; cursor: pointer;" onclick="closeScreenshotModal()">
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Exibir imediatamente
            setTimeout(() => {
                modal.style.opacity = '1';
            }, 10);
            
            // Fechar ao clicar fora
            modal.onclick = function(e) {
                if (e.target === modal) closeScreenshotModal();
            };
            
            console.log('✅ Modal criado e exibido');
        }
        
        function closeScreenshotModal() {
            console.log('🚪 Fechando modal');
            const modals = document.querySelectorAll('[style*="position: fixed"][style*="z-index: 99999"]');
            modals.forEach(modal => {
                modal.style.opacity = '0';
                setTimeout(() => modal.remove(), 300);
            });
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Função de teste para verificar se os cliques funcionam
        function testModal() {
            alert('🎯 Função JavaScript funcionando! Se você vê esta mensagem, o JS está OK.');
            console.log('🧪 Teste de função executado');
        }
        
        // Adicionar teste ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Dashboard carregado - JavaScript funcionando');
            
            // Testar se openScreenshotModal existe
            if (typeof openScreenshotModal === 'function') {
                console.log('✅ Função openScreenshotModal encontrada');
            } else {
                console.error('❌ Função openScreenshotModal NÃO encontrada');
            }
            
            // Adicionar evento de teste às imagens
            const screenshots = document.querySelectorAll('.event-thumbnail img, .event-screenshot-thumbnail img');
            console.log('📷 Encontradas', screenshots.length, 'imagens de screenshot');
            
            screenshots.forEach((img, index) => {
                console.log(`📸 Imagem ${index + 1}:`, img.src);
                console.log(`📸 Onclick da imagem ${index + 1}:`, img.getAttribute('onclick'));
                
                // Adicionar evento alternativo de teste
                img.addEventListener('click', function(e) {
                    console.log('🖱️ CLICK DETECTADO na imagem:', e.target.src);
                    console.log('🖱️ Onclick attribute:', e.target.getAttribute('onclick'));
                    
                    // Tentar executar a função diretamente
                    try {
                        console.log('🧪 Tentando executar openScreenshotModal diretamente...');
                        const imgSrc = e.target.src;
                        const eventName = 'teste-direto';
                        openScreenshotModal(imgSrc, eventName);
                    } catch (error) {
                        console.error('💥 Erro ao executar função diretamente:', error);
                    }
                });
            });
            
            // Adicionar listener global para QUALQUER clique
            document.addEventListener('click', function(e) {
                if (e.target.tagName === 'IMG') {
                    console.log('🖱️ CLIQUE EM QUALQUER IMAGEM:', e.target.src);
                    console.log('🖱️ Classes da imagem:', e.target.className);
                    console.log('🖱️ Onclick attr:', e.target.getAttribute('onclick'));
                }
            }, true); // capture = true para capturar antes de outros handlers
        });
    </script>
</body>
</html>
