<?php
// Script para limpar todos os dados e logs do sistema Analytics

date_default_timezone_set('America/Sao_Paulo');
$baseDir = __DIR__ . '/analytics-data';

function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    
    foreach ($files as $file) {
        $filePath = $dir . '/' . $file;
        if (is_dir($filePath)) {
            deleteDirectory($filePath);
        } else {
            unlink($filePath);
        }
    }
    
    return rmdir($dir);
}

function clearDirectoryContents($dir) {
    if (!is_dir($dir)) {
        logMessage("   ⚠️ Diretório não existe: $dir");
        return false;
    }
    
    logMessage("   🔍 Escaneando diretório: $dir");
    $files = array_diff(scandir($dir), array('.', '..'));
    $deletedCount = 0;
    
    foreach ($files as $file) {
        $filePath = $dir . '/' . $file;
        if (is_dir($filePath)) {
            logMessage("   📁 Removendo subdiretório: $file");
            if (deleteDirectory($filePath)) {
                $deletedCount++;
            } else {
                logMessage("   ❌ Erro ao remover: $file");
            }
        } else {
            logMessage("   📄 Removendo arquivo: $file");
            if (unlink($filePath)) {
                $deletedCount++;
            } else {
                logMessage("   ❌ Erro ao remover arquivo: $file");
            }
        }
    }
    
    logMessage("   ✅ Removidos: $deletedCount itens");
    return true;
}

function logMessage($message) {
    echo "[" . date('Y-m-d H:i:s') . "] $message\n";
}

// Verificar se é chamada via CLI ou via web
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    // Se for via web, só executar se receber confirmação
    if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'sim') {
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>Limpar Dados - Analytics</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
                .warning { background: #ffebee; border: 1px solid #f44336; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .button { display: inline-block; padding: 12px 24px; margin: 10px; text-decoration: none; border-radius: 5px; font-weight: bold; }
                .danger { background: #f44336; color: white; }
                .secondary { background: #666; color: white; }
            </style>
        </head>
        <body>
            <h1>🧹 Limpar Todos os Dados</h1>
            
            <div class="warning">
                <h3>⚠️ ATENÇÃO!</h3>
                <p>Esta ação irá <strong>DELETAR PERMANENTEMENTE</strong> todos os dados do sistema:</p>
                <ul>
                    <li>Todos os usuários</li>
                    <li>Todos os eventos</li>
                    <li>Screenshots de eventos</li>
                    <li>Screenshots de sessão e manuais</li>
                    <li>Todas as gravações/vídeos</li>
                    <li>Todos os logs</li>
                    <li>Cache e dados temporários</li>
                </ul>
                <p><strong>Esta ação NÃO PODE ser desfeita!</strong></p>
            </div>
            
            <a href="?confirm=sim" class="button danger">✅ Confirmar - Limpar Tudo</a>
            <a href="../" class="button secondary">❌ Cancelar</a>
        </body>
        </html>
        <?php
        exit;
    }
    
    header('Content-Type: text/plain');
}

logMessage("🧹 Iniciando limpeza completa dos dados...");

// Mostrar status atual do sistema
logMessage("📊 Status atual do sistema:");
logMessage("🗂️ Diretório base: $baseDir");

if (!is_dir($baseDir)) {
    logMessage("❌ ERRO: Diretório base não existe!");
    if (!$isCLI) {
        echo "❌ ERRO: Diretório base não existe: $baseDir";
    }
    exit(1);
}

// Diretórios para limpar
$directories = [
    'users' => $baseDir . '/users',
    'events' => $baseDir . '/events', 
    'events-screenshots' => $baseDir . '/events-screenshots',
    'screenshots' => $baseDir . '/screenshots',
    'videos' => $baseDir . '/videos',
    'logs' => $baseDir . '/logs',
    'temp' => $baseDir . '/temp',
    'cache' => $baseDir . '/cache'
];

// Verificar o que existe atualmente
logMessage("📋 Verificando diretórios existentes:");
foreach ($directories as $name => $path) {
    if (is_dir($path)) {
        $fileCount = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $fileCount++;
            }
        }
        
        logMessage("   ✅ $name: $fileCount arquivos");
    } else {
        logMessage("   ❌ $name: não existe");
    }
}

logMessage("");
logMessage("🔄 Iniciando processo de limpeza...");

$totalDeleted = 0;

foreach ($directories as $name => $path) {
    if (is_dir($path)) {
        logMessage("📂 Limpando diretório: $name");
        
        // Contar arquivos antes
        $fileCount = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $fileCount++;
            }
        }
        
        if ($fileCount > 0) {
            clearDirectoryContents($path);
            logMessage("   ✅ $fileCount arquivos removidos");
            $totalDeleted += $fileCount;
        } else {
            logMessage("   ℹ️ Diretório já vazio");
        }
    } else {
        logMessage("   ⚠️ Diretório não existe: $name");
    }
}

// Preservar estrutura de diretórios básica
foreach ($directories as $name => $path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        logMessage("📁 Diretório recriado: $name");
    }
}

// Criar arquivo de log da limpeza
$cleanupLog = $baseDir . '/logs/' . date('Y-m-d') . '.log';
$logEntry = "[" . date('Y-m-d H:i:s') . "] 🧹 Sistema limpo - $totalDeleted arquivos removidos\n";
file_put_contents($cleanupLog, $logEntry, FILE_APPEND | LOCK_EX);

logMessage("");
logMessage("✅ Limpeza concluída!");
logMessage("📊 Total de arquivos removidos: $totalDeleted");
logMessage("🕒 Horário: " . date('d/m/Y H:i:s'));
logMessage("");

if (!$isCLI) {
    echo "\n\n📝 Para voltar ao dashboard: <a href='../dashboard.php'>Clique aqui</a>";
}
?> 