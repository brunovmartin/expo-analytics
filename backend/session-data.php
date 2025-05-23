<?php
// API para fornecer dados de sessão para o player
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$baseDir = __DIR__ . '/analytics-data';

// Validar parâmetros
$userId = $_GET['user'] ?? '';
$date = $_GET['date'] ?? '';

if (empty($userId) || empty($date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
    exit;
}

// Sanitizar entrada
$userId = preg_replace('/[^a-zA-Z0-9_-]/', '', $userId);
$date = preg_replace('/[^0-9-]/', '', $date);

// Validar formato da data
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Formato de data inválido']);
    exit;
}

// Caminho da sessão
$sessionDir = $baseDir . '/screenshots/' . $userId . '/' . $date;

if (!is_dir($sessionDir)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Sessão não encontrada']);
    exit;
}

try {
    // Obter lista de screenshots
    $screenshots = glob($sessionDir . '/*.jpg');
    
    if (empty($screenshots)) {
        echo json_encode(['success' => false, 'error' => 'Nenhum screenshot encontrado']);
        exit;
    }
    
    // Ordenar por nome de arquivo (que contém timestamp)
    sort($screenshots);
    
    // Preparar lista de frames para o player
    $frames = [];
    foreach ($screenshots as $screenshot) {
        $filename = basename($screenshot);
        $frames[] = [
            'filename' => $filename,
            'url' => 'view-screenshot.php?user=' . urlencode($userId) . '&date=' . urlencode($date) . '&file=' . urlencode($filename),
            'timestamp' => filemtime($screenshot)
        ];
    }
    
    // Obter metadados da sessão
    $metadata = [];
    $metadataFiles = glob($sessionDir . '/metadata_*.json');
    
    if (!empty($metadataFiles)) {
        $metadataContent = file_get_contents($metadataFiles[0]);
        $metadata = json_decode($metadataContent, true) ?: [];
    }
    
    // Obter eventos da sessão (se existirem)
    $events = [];
    $eventsDir = $baseDir . '/events/' . $userId . '/' . $date;
    
    if (is_dir($eventsDir)) {
        $eventFiles = glob($eventsDir . '/*.jsonl');
        
        foreach ($eventFiles as $eventFile) {
            $lines = file($eventFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $event = json_decode($line, true);
                if ($event) {
                    $events[] = $event;
                }
            }
        }
        
        // Ordenar eventos por timestamp
        usort($events, function($a, $b) {
            return ($a['timestamp'] ?? 0) - ($b['timestamp'] ?? 0);
        });
    }
    
    // Calcular duração da sessão
    $duration = 0;
    if (count($frames) > 1) {
        $firstFrame = reset($frames);
        $lastFrame = end($frames);
        $duration = $lastFrame['timestamp'] - $firstFrame['timestamp'];
    }
    
    // Estatísticas da sessão
    $sessionStats = [
        'totalFrames' => count($frames),
        'totalEvents' => count($events),
        'duration' => $duration,
        'frameRate' => $duration > 0 ? count($frames) / $duration : 0,
        'startTime' => !empty($frames) ? $frames[0]['timestamp'] : null,
        'endTime' => !empty($frames) ? $frames[count($frames) - 1]['timestamp'] : null
    ];
    
    // Preparar dados para o player
    $sessionData = [
        'success' => true,
        'userId' => $userId,
        'date' => $date,
        'frames' => $frames,
        'metadata' => $metadata,
        'events' => $events,
        'stats' => $sessionStats
    ];
    
    echo json_encode($sessionData);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}
?> 