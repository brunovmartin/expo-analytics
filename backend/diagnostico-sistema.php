<?php
// Script de diagnóstico completo do sistema Analytics

date_default_timezone_set('America/Sao_Paulo');
$baseDir = __DIR__ . '/analytics-data';

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

function checkDirectory($path) {
    if (!is_dir($path)) {
        return "❌ Não existe";
    }
    
    $files = 0;
    $totalSize = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $files++;
            $totalSize += $file->getSize();
        }
    }
    
    return "✅ $files arquivos (" . formatBytes($totalSize) . ")";
}

function checkFFmpeg() {
    exec('which ffmpeg 2>/dev/null', $output, $returnCode);
    if ($returnCode === 0) {
        exec('ffmpeg -version 2>&1 | head -1', $version);
        return "✅ " . ($version[0] ?? 'FFmpeg instalado');
    }
    
    // Verificar caminhos comuns
    $paths = ['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/opt/homebrew/bin/ffmpeg'];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            return "✅ FFmpeg encontrado em $path";
        }
    }
    
    return "❌ FFmpeg não encontrado";
}

function checkZipSupport() {
    if (class_exists('ZipArchive')) {
        return "✅ ZipArchive disponível";
    }
    
    exec('which unzip 2>/dev/null', $output, $returnCode);
    if ($returnCode === 0) {
        return "⚠️ Apenas comando unzip disponível";
    }
    
    return "❌ Nenhum suporte ZIP encontrado";
}

function analyzeEvents($baseDir) {
    $events = [];
    $eventsDir = $baseDir . '/events';
    
    if (!is_dir($eventsDir)) {
        return "❌ Diretório de eventos não existe";
    }
    
    $users = array_diff(scandir($eventsDir), ['.', '..']);
    $totalEvents = 0;
    
    foreach ($users as $userId) {
        $userPath = $eventsDir . '/' . $userId;
        
        if (is_dir($userPath) && !str_starts_with($userId, '.')) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($userPath, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) === 'jsonl') {
                    $lines = file($file->getPathname(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    $totalEvents += count($lines);
                }
            }
        }
    }
    
    return "✅ " . count($users) . " usuários, $totalEvents eventos";
}

function analyzeApp($baseDir) {
    $appsDir = $baseDir . '/apps';
    
    if (!is_dir($appsDir)) {
        return "❌ Diretório de apps não existe";
    }
    
    $files = array_diff(scandir($appsDir), ['.', '..']);
    $apps = [];
    
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $appData = json_decode(file_get_contents($appsDir . '/' . $file), true);
            if ($appData) {
                $apps[] = $appData;
            }
        }
    }
    
    return "✅ " . count($apps) . " apps cadastrados";
}

function checkLogs($baseDir) {
    $logsDir = $baseDir . '/logs';
    
    if (!is_dir($logsDir)) {
        return "❌ Diretório de logs não existe";
    }
    
    $files = glob($logsDir . '/*.log');
    if (empty($files)) {
        return "⚠️ Nenhum arquivo de log encontrado";
    }
    
    $latestLog = '';
    $latestTime = 0;
    
    foreach ($files as $file) {
        $time = filemtime($file);
        if ($time > $latestTime) {
            $latestTime = $time;
            $latestLog = $file;
        }
    }
    
    $lines = file($latestLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $size = formatBytes(filesize($latestLog));
    $lastModified = date('d/m/Y H:i:s', $latestTime);
    
    return "✅ " . count($files) . " arquivos, último: $size em $lastModified (" . count($lines) . " linhas)";
}

// Se for chamada via web, mostrar HTML
if (php_sapi_name() !== 'cli') {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Diagnóstico do Sistema - Analytics</title>
        <style>
            body { 
                font-family: 'Segoe UI', sans-serif; 
                max-width: 1000px; 
                margin: 2rem auto; 
                padding: 2rem; 
                background: #f5f7fa;
            }
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 2rem;
                border-radius: 12px;
                margin-bottom: 2rem;
                text-align: center;
            }
            .section {
                background: white;
                padding: 1.5rem;
                margin-bottom: 1.5rem;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .section h3 {
                margin-top: 0;
                color: #333;
                border-bottom: 2px solid #667eea;
                padding-bottom: 0.5rem;
            }
            .check-item {
                display: flex;
                justify-content: space-between;
                padding: 0.75rem 0;
                border-bottom: 1px solid #eee;
            }
            .check-item:last-child {
                border-bottom: none;
            }
            .status {
                font-weight: 600;
            }
            .actions {
                margin-top: 2rem;
                text-align: center;
            }
            .btn {
                display: inline-block;
                padding: 0.75rem 1.5rem;
                margin: 0.5rem;
                border-radius: 6px;
                text-decoration: none;
                font-weight: 600;
                transition: all 0.3s ease;
            }
            .btn-primary {
                background: #667eea;
                color: white;
            }
            .btn-primary:hover {
                background: #5a6fd8;
            }
            .btn-danger {
                background: #e74c3c;
                color: white;
            }
            .btn-danger:hover {
                background: #c0392b;
            }
            .btn-secondary {
                background: #6c757d;
                color: white;
            }
            .refresh-note {
                background: #e3f2fd;
                padding: 1rem;
                border-radius: 6px;
                border-left: 4px solid #2196f3;
                margin-top: 1rem;
                font-size: 0.9rem;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>🔍 Diagnóstico do Sistema Analytics</h1>
            <p>Verificação completa dos componentes do sistema</p>
        </div>
    <?php
}

echo "<div class='section'>\n";
echo "<h3>📊 Status Geral do Sistema</h3>\n";

$checks = [
    'Diretório Base' => is_dir($baseDir) ? "✅ Existe ($baseDir)" : "❌ Não existe",
    'Usuários' => checkDirectory($baseDir . '/users'),
    'Eventos' => analyzeEvents($baseDir),
    'Screenshots' => checkDirectory($baseDir . '/screenshots'),
    'Vídeos' => checkDirectory($baseDir . '/videos'),
    'Apps Cadastrados' => analyzeApp($baseDir),
    'Logs' => checkLogs($baseDir),
    'Arquivos Temporários' => checkDirectory($baseDir . '/temp')
];

foreach ($checks as $label => $status) {
    echo "<div class='check-item'>\n";
    echo "<span>$label:</span>\n";
    echo "<span class='status'>$status</span>\n";
    echo "</div>\n";
}

echo "</div>\n";

echo "<div class='section'>\n";
echo "<h3>🛠️ Dependências do Sistema</h3>\n";

$dependencies = [
    'PHP ZipArchive' => checkZipSupport(),
    'FFmpeg' => checkFFmpeg(),
    'Permissões de Escrita' => is_writable($baseDir) ? "✅ Diretório gravável" : "❌ Sem permissão de escrita",
    'PHP Version' => "✅ PHP " . phpversion()
];

foreach ($dependencies as $label => $status) {
    echo "<div class='check-item'>\n";
    echo "<span>$label:</span>\n";
    echo "<span class='status'>$status</span>\n";
    echo "</div>\n";
}

echo "</div>\n";

// Análise detalhada dos logs mais recentes
$logsDir = $baseDir . '/logs';
if (is_dir($logsDir)) {
    $logFiles = glob($logsDir . '/*.log');
    if (!empty($logFiles)) {
        $latestLog = array_reduce($logFiles, function($latest, $file) {
            return (filemtime($file) > filemtime($latest ?? '')) ? $file : $latest;
        });
        
        echo "<div class='section'>\n";
        echo "<h3>📝 Últimas Atividades do Sistema</h3>\n";
        
        $lines = file($latestLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $recentLines = array_slice($lines, -10); // Últimas 10 linhas
        
        echo "<div style='background: #f8f9fa; padding: 1rem; border-radius: 4px; font-family: monospace; font-size: 0.85rem; overflow-x: auto;'>\n";
        foreach ($recentLines as $line) {
            echo htmlspecialchars($line) . "<br>\n";
        }
        echo "</div>\n";
        echo "</div>\n";
    }
}

// Verificar se há problemas conhecidos
echo "<div class='section'>\n";
echo "<h3>⚠️ Possíveis Problemas Detectados</h3>\n";

$problems = [];

// Verificar se FFmpeg está disponível para vídeos
if (strpos(checkFFmpeg(), '❌') !== false) {
    $problems[] = "FFmpeg não encontrado - vídeos não podem ser gerados";
}

// Verificar se há eventos mas não há vídeos
$eventsExist = is_dir($baseDir . '/events') && count(array_diff(scandir($baseDir . '/events'), ['.', '..'])) > 0;
$videosExist = is_dir($baseDir . '/videos') && count(array_diff(scandir($baseDir . '/videos'), ['.', '..'])) > 0;

if ($eventsExist && !$videosExist) {
    $problems[] = "Eventos existem mas nenhum vídeo foi gerado - verificar endpoint /upload-zip";
}

// Verificar logs por erros
if (is_dir($logsDir) && !empty($logFiles)) {
    $latestLog = array_reduce($logFiles, function($latest, $file) {
        return (filemtime($file) > filemtime($latest ?? '')) ? $file : $latest;
    });
    
    $content = file_get_contents($latestLog);
    if (strpos($content, '❌') !== false || strpos($content, 'erro') !== false || strpos($content, 'Erro') !== false) {
        $problems[] = "Erros detectados nos logs - verificar arquivo de log mais recente";
    }
}

if (empty($problems)) {
    echo "<div class='check-item'>\n";
    echo "<span>✅ Nenhum problema crítico detectado</span>\n";
    echo "</div>\n";
} else {
    foreach ($problems as $problem) {
        echo "<div class='check-item'>\n";
        echo "<span style='color: #e74c3c;'>⚠️ $problem</span>\n";
        echo "</div>\n";
    }
}

echo "</div>\n";

if (php_sapi_name() !== 'cli') {
    ?>
    <div class="section">
        <h3>🔧 Ações Disponíveis</h3>
        <div class="actions">
            <a href="dashboard.php" class="btn btn-primary">
                📊 Ir para Dashboard
            </a>
            <a href="limpar-dados.php" class="btn btn-danger">
                🧹 Limpar Todos os Dados
            </a>
            <a href="?refresh=1" class="btn btn-secondary">
                🔄 Atualizar Diagnóstico
            </a>
        </div>
        
        <div class="refresh-note">
            💡 <strong>Dica:</strong> Se os vídeos não estão sendo gerados, verifique se o app móvel está enviando dados 
            para o endpoint <code>/upload-zip</code> e se o FFmpeg está instalado no sistema.
        </div>
    </div>
    
    </body>
    </html>
    <?php
} else {
    echo "\n\n";
    echo "=== RESUMO DO DIAGNÓSTICO ===\n";
    echo "Sistema: " . (empty($problems) ? "✅ Funcionando" : "⚠️ Com problemas") . "\n";
    echo "Data/Hora: " . date('d/m/Y H:i:s') . "\n";
    echo "Problemas: " . count($problems) . "\n";
    
    if (!empty($problems)) {
        echo "\nProblemas detectados:\n";
        foreach ($problems as $i => $problem) {
            echo ($i + 1) . ". $problem\n";
        }
    }
}
?> 