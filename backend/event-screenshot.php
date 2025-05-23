<?php
// Visualizador de screenshots de eventos
$baseDir = __DIR__ . '/analytics-data';

// Parâmetros obrigatórios
$userId = $_GET['user'] ?? '';
$date = $_GET['date'] ?? '';
$filename = $_GET['file'] ?? '';

if (empty($userId) || empty($date) || empty($filename)) {
    http_response_code(400);
    echo 'Parâmetros obrigatórios: user, date, file';
    exit;
}

// Validar formato da data (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo 'Formato de data inválido. Use YYYY-MM-DD';
    exit;
}

// Validar nome do arquivo (deve ser JPG e seguir padrão de evento)
if (!preg_match('/^event_\d+_[a-zA-Z0-9_-]+\.jpg$/', $filename)) {
    http_response_code(400);
    echo 'Nome de arquivo inválido';
    exit;
}

// Construir caminho do arquivo
$screenshotPath = $baseDir . '/events-screenshots/' . $userId . '/' . $date . '/' . $filename;

// Verificar se o arquivo existe
if (!file_exists($screenshotPath)) {
    http_response_code(404);
    echo 'Screenshot não encontrado';
    exit;
}

// Definir headers para imagem
header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($screenshotPath));

// Cache headers (screenshots não mudam)
header('Cache-Control: public, max-age=86400'); // 24 horas
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');

// Servir o arquivo
readfile($screenshotPath);
?> 