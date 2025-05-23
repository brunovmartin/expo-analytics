<?php
// Dashboard principal para Analytics com gestão de apps
date_default_timezone_set('America/Sao_Paulo');
$baseDir = __DIR__ . '/analytics-data';

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

// Função para obter estatísticas filtradas por app
function getStats($baseDir, $selectedApp = null) {
    $stats = [
        'totalUsers' => 0,
        'totalSessions' => 0,
        'totalEvents' => 0,
        'totalScreenshots' => 0,
        'recentUsers' => [],
        'topEvents' => []
    ];
    
    if (!is_dir($baseDir)) return $stats;
    
    // TODO: Implementar filtro por bundle ID quando tivermos essa informação nos dados
    // Por enquanto, retorna todas as estatísticas
    
    // Contar usuários
    $usersDir = $baseDir . '/users';
    if (is_dir($usersDir)) {
        $users = array_diff(scandir($usersDir), ['.', '..']);
        
        $validUsers = [];
        foreach ($users as $item) {
            $itemPath = $usersDir . '/' . $item;
            if (is_dir($itemPath) && !str_starts_with($item, '.')) {
                $validUsers[] = $item;
            }
        }
        
        $stats['totalUsers'] = count($validUsers);
        
        foreach ($validUsers as $userId) {
            $latestFile = $usersDir . '/' . $userId . '/latest.json';
            if (file_exists($latestFile)) {
                $userData = json_decode(file_get_contents($latestFile), true);
                $stats['recentUsers'][] = [
                    'userId' => $userId,
                    'lastSeen' => $userData['receivedAt'] ?? 0,
                    'userData' => $userData['userData'] ?? []
                ];
            }
        }
        
        usort($stats['recentUsers'], function($a, $b) {
            return $b['lastSeen'] - $a['lastSeen'];
        });
        $stats['recentUsers'] = array_slice($stats['recentUsers'], 0, 10);
    }
    
    // Contar sessões de screenshots
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
                        $stats['totalSessions']++;
                        $screenshots = glob($sessionPath . '/*.jpg');
                        $stats['totalScreenshots'] += count($screenshots);
                    }
                }
            }
        }
    }
    
    // Contar eventos
    $eventsDir = $baseDir . '/events';
    if (is_dir($eventsDir)) {
        $users = array_diff(scandir($eventsDir), ['.', '..']);
        
        foreach ($users as $userId) {
            $userPath = $eventsDir . '/' . $userId;
            
            if (is_dir($userPath) && !str_starts_with($userId, '.')) {
                foreach (glob($userPath . '/*/*/*.jsonl') as $eventFile) {
                    $lines = file($eventFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    $stats['totalEvents'] += count($lines);
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
                $screenshots = glob($sessionDir . '/*.jpg');
                $metadataFiles = glob($sessionDir . '/metadata_*.json');
                
                $metadata = [];
                if (!empty($metadataFiles)) {
                    $metadata = json_decode(file_get_contents($metadataFiles[0]), true);
                }
                
                $sessions[] = [
                    'date' => $date,
                    'path' => $sessionDir,
                    'screenshotCount' => count($screenshots),
                    'metadata' => $metadata,
                    'firstScreenshot' => !empty($screenshots) ? basename($screenshots[0]) : null
                ];
            }
        }
    }
    
    usort($sessions, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });
    
    return $sessions;
}

// Função para obter dados detalhados de um usuário
function getUserData($baseDir, $userId) {
    $userData = [
        'userId' => $userId,
        'latestInfo' => null,
        'allSessions' => [],
        'totalEvents' => 0,
        'totalScreenshots' => 0,
        'geoData' => null,
        'firstSeen' => null,
        'lastSeen' => null
    ];
    
    $latestFile = $baseDir . '/users/' . $userId . '/latest.json';
    if (file_exists($latestFile)) {
        $userData['latestInfo'] = json_decode(file_get_contents($latestFile), true);
        $userData['lastSeen'] = $userData['latestInfo']['receivedAt'] ?? null;
        $userData['geoData'] = $userData['latestInfo']['geo'] ?? null;
    }
    
    $userScreenshotsDir = $baseDir . '/screenshots/' . $userId;
    if (is_dir($userScreenshotsDir)) {
        foreach (glob($userScreenshotsDir . '/*') as $sessionDir) {
            if (is_dir($sessionDir)) {
                $date = basename($sessionDir);
                $screenshots = glob($sessionDir . '/*.jpg');
                $metadataFiles = glob($sessionDir . '/metadata_*.json');
                
                $sessionData = [
                    'date' => $date,
                    'screenshotCount' => count($screenshots),
                    'metadata' => null
                ];
                
                if (!empty($metadataFiles)) {
                    $metadata = json_decode(file_get_contents($metadataFiles[0]), true);
                    $sessionData['metadata'] = $metadata;
                    
                    if (!$userData['firstSeen'] || $metadata['timestamp'] < $userData['firstSeen']) {
                        $userData['firstSeen'] = $metadata['timestamp'];
                    }
                    if (!$userData['lastSeen'] || $metadata['timestamp'] > $userData['lastSeen']) {
                        $userData['lastSeen'] = $metadata['timestamp'];
                    }
                }
                
                $userData['allSessions'][] = $sessionData;
                $userData['totalScreenshots'] += count($screenshots);
            }
        }
    }
    
    $userEventsDir = $baseDir . '/events/' . $userId;
    if (is_dir($userEventsDir)) {
        foreach (glob($userEventsDir . '/*/*/*.jsonl') as $eventFile) {
            $lines = file($eventFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $userData['totalEvents'] += count($lines);
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
            $framerate = isset($_POST['framerate']) ? (int)$_POST['framerate'] : null;
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
                        <div class="app-card">
                            <div class="app-header">
                                <div class="app-icon">
                                    <i class="fas fa-<?= $app['platform'] === 'ios' ? 'apple' : 'android' ?>"></i>
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
                                    <span class="config-value"><?= $app['config']['framerate'] ?> fps</span>
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
                        <div class="app-icon">
                            <i class="fas fa-<?= $currentApp['platform'] === 'ios' ? 'apple' : 'android' ?>"></i>
                        </div>
                        <div class="app-details">
                            <h2><?= htmlspecialchars($currentApp['name']) ?></h2>
                            <p><?= htmlspecialchars($currentApp['bundleId']) ?></p>
                            <span class="platform-badge <?= $currentApp['platform'] ?>">
                                <?= strtoupper($currentApp['platform']) ?>
                            </span>
                        </div>
                        <button onclick="editApp('<?= htmlspecialchars($currentApp['bundleId']) ?>')" class="btn btn-secondary">
                            <i class="fas fa-cog"></i> Configurar
                        </button>
                    </div>
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
                                <a href="?app=<?= urlencode($selectedApp) ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Voltar
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="panel-content">
                            <?php if ($selectedUser && $userData): ?>
                            
                            <!-- Dados do Usuário -->
                            <div class="user-details">
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
                                    </div>
                                </div>

                                <!-- Estatísticas -->
                                <div class="detail-section">
                                    <h3><i class="fas fa-chart-bar"></i> Estatísticas</h3>
                                    <div class="detail-grid">
                                        <div class="detail-item">
                                            <label>Total de sessões:</label>
                                            <span><?= count($userData['allSessions']) ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <label>Total de screenshots:</label>
                                            <span><?= number_format($userData['totalScreenshots']) ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <label>Total de eventos:</label>
                                            <span><?= number_format($userData['totalEvents']) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dados do App -->
                                <?php if (!empty($userData['latestInfo']['userData'])): ?>
                                <div class="detail-section">
                                    <h3><i class="fas fa-mobile-alt"></i> Dados do App</h3>
                                    <div class="detail-grid">
                                        <?php foreach ($userData['latestInfo']['userData'] as $key => $value): ?>
                                        <div class="detail-item">
                                            <label><?= htmlspecialchars($key) ?>:</label>
                                            <span><?= htmlspecialchars(is_array($value) ? json_encode($value) : $value) ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Localização -->
                                <?php if (!empty($userData['geoData'])): ?>
                                <div class="detail-section">
                                    <h3><i class="fas fa-map-marker-alt"></i> Localização</h3>
                                    <div class="detail-grid">
                                        <?php if (!empty($userData['geoData']['city'])): ?>
                                        <div class="detail-item">
                                            <label>Cidade:</label>
                                            <span><?= htmlspecialchars($userData['geoData']['city']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($userData['geoData']['region'])): ?>
                                        <div class="detail-item">
                                            <label>Estado:</label>
                                            <span><?= htmlspecialchars($userData['geoData']['region']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($userData['geoData']['country_name'])): ?>
                                        <div class="detail-item">
                                            <label>País:</label>
                                            <span><?= htmlspecialchars($userData['geoData']['country_name']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Sessões de Gravação -->
                            <div class="detail-section">
                                <h3><i class="fas fa-video"></i> Sessões de Gravação (<?= count($userSessions) ?>)</h3>
                                <?php if (!empty($userSessions)): ?>
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
                                <?php else: ?>
                                <div class="empty-sessions">
                                    <i class="fas fa-video"></i>
                                    <p>Nenhuma sessão encontrada para este usuário</p>
                                </div>
                                <?php endif; ?>
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
                            <label for="framerate">Framerate: <span id="framerateValue">10</span> fps</label>
                            <input type="range" id="framerate" name="framerate" min="1" max="30" value="10" oninput="updateFramerateValue(this.value)">
                            <div class="range-labels">
                                <span>1 fps</span>
                                <span>30 fps</span>
                            </div>
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
            document.getElementById('framerateValue').textContent = value;
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
    </script>
</body>
</html> 