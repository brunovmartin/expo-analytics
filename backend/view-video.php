<?php
$baseDir = __DIR__ . '/analytics-data';

if (!isset($_GET['user']) || !isset($_GET['date']) || !isset($_GET['file'])) {
    http_response_code(400);
    die('Parâmetros obrigatórios: user, date, file');
}

$userId = $_GET['user'];
$date = $_GET['date'];
$filename = $_GET['file'];

// Validar o formato da data
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    die('Formato de data inválido');
}

// Validar o nome do arquivo (apenas MP4)
if (!preg_match('/^session_[a-zA-Z0-9_-]+\.mp4$/', $filename)) {
    http_response_code(400);
    die('Nome de arquivo inválido');
}

// Construir o caminho do vídeo
$videoPath = $baseDir . '/videos/' . $userId . '/' . $date . '/' . $filename;

// Verificar se o arquivo existe
if (!file_exists($videoPath)) {
    http_response_code(404);
    die('Vídeo não encontrado');
}

// Definir headers apropriados para vídeo MP4
header('Content-Type: video/mp4');
header('Content-Length: ' . filesize($videoPath));
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=3600');

// Suporte para Range requests (necessário para seek no vídeo)
if (isset($_SERVER['HTTP_RANGE'])) {
    $size = filesize($videoPath);
    $start = 0;
    $end = $size - 1;
    
    if (preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
        $start = intval($matches[1]);
        if (!empty($matches[2])) {
            $end = intval($matches[2]);
        }
    }
    
    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $start-$end/$size");
    header('Content-Length: ' . ($end - $start + 1));
    
    $file = fopen($videoPath, 'rb');
    fseek($file, $start);
    echo fread($file, $end - $start + 1);
    fclose($file);
} else {
    // Enviar arquivo completo
    readfile($videoPath);
}
?> 