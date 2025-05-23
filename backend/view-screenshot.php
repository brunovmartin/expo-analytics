<?php
// Arquivo para servir screenshots de forma segura
$baseDir = __DIR__ . '/analytics-data';

// Validar parâmetros
$userId = $_GET['user'] ?? '';
$date = $_GET['date'] ?? '';
$file = $_GET['file'] ?? '';

// Validação básica de segurança
if (empty($userId) || empty($date) || empty($file)) {
    http_response_code(400);
    die('Parâmetros inválidos');
}

// Sanitizar entrada para evitar path traversal
$userId = preg_replace('/[^a-zA-Z0-9_-]/', '', $userId);
$date = preg_replace('/[^0-9-]/', '', $date);
$file = preg_replace('/[^a-zA-Z0-9_.-]/', '', $file);

// Validar formato da data
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    die('Formato de data inválido');
}

// Validar extensão do arquivo
$allowedExtensions = ['jpg', 'jpeg', 'png'];
$fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
if (!in_array($fileExtension, $allowedExtensions)) {
    http_response_code(400);
    die('Tipo de arquivo não permitido');
}

// Construir caminho do arquivo
$filePath = $baseDir . '/screenshots/' . $userId . '/' . $date . '/' . $file;

// Verificar se o arquivo existe e está dentro do diretório permitido
$realPath = realpath($filePath);
$allowedBasePath = realpath($baseDir . '/screenshots');

if (!$realPath || !$allowedBasePath || strpos($realPath, $allowedBasePath) !== 0) {
    http_response_code(404);
    die('Arquivo não encontrado');
}

if (!file_exists($realPath)) {
    http_response_code(404);
    die('Arquivo não encontrado');
}

// Determinar tipo MIME
$mimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png'
];

$mimeType = $mimeTypes[$fileExtension] ?? 'application/octet-stream';

// Definir headers para cache
$lastModified = filemtime($realPath);
$etag = md5_file($realPath);

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($realPath));
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
header('ETag: "' . $etag . '"');
header('Cache-Control: public, max-age=3600'); // Cache por 1 hora
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

// Verificar se o cliente tem uma versão em cache
$ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
$ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';

if ($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModified) {
    http_response_code(304);
    exit;
}

if ($ifNoneMatch && $ifNoneMatch === '"' . $etag . '"') {
    http_response_code(304);
    exit;
}

// Servir o arquivo
readfile($realPath);
?> 