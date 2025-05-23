<?php
// Dashboard principal para Analytics
date_default_timezone_set('America/Sao_Paulo');
$baseDir = __DIR__ . '/analytics-data';

// Função para obter estatísticas
function getStats($baseDir) {
    $stats = [
        'totalUsers' => 0,
        'totalSessions' => 0,
        'totalEvents' => 0,
        'totalScreenshots' => 0,
        'recentUsers' => [],
        'topEvents' => []
    ];
    
    if (!is_dir($baseDir)) return $stats;
    
    // Contar usuários
    $usersDir = $baseDir . '/users';
    if (is_dir($usersDir)) {
        $users = array_diff(scandir($usersDir), ['.', '..']);
        
        // Filtrar apenas diretórios reais de usuários
        $validUsers = [];
        foreach ($users as $item) {
            $itemPath = $usersDir . '/' . $item;
            // Verificar se é um diretório e não um arquivo de sistema
            if (is_dir($itemPath) && !str_starts_with($item, '.')) {
                $validUsers[] = $item;
            }
        }
        
        $stats['totalUsers'] = count($validUsers);
        
        // Usuários recentes - usar apenas usuários válidos
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
        
        // Ordenar por último acesso
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
            
            // Verificar se é um diretório válido de usuário
            if (is_dir($userPath) && !str_starts_with($userId, '.')) {
                $sessions = array_diff(scandir($userPath), ['.', '..']);
                
                foreach ($sessions as $sessionDate) {
                    $sessionPath = $userPath . '/' . $sessionDate;
                    
                    // Verificar se é um diretório válido de sessão
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
            
            // Verificar se é um diretório válido de usuário
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
    
    // Ordenar por data (mais recente primeiro)
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
    
    // Obter informações mais recentes do usuário
    $latestFile = $baseDir . '/users/' . $userId . '/latest.json';
    if (file_exists($latestFile)) {
        $userData['latestInfo'] = json_decode(file_get_contents($latestFile), true);
        $userData['lastSeen'] = $userData['latestInfo']['receivedAt'] ?? null;
        $userData['geoData'] = $userData['latestInfo']['geo'] ?? null;
    }
    
    // Obter dados históricos das sessões
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
                    
                    // Atualizar first/last seen
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
    
    // Contar eventos
    $userEventsDir = $baseDir . '/events/' . $userId;
    if (is_dir($userEventsDir)) {
        foreach (glob($userEventsDir . '/*/*/*.jsonl') as $eventFile) {
            $lines = file($eventFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $userData['totalEvents'] += count($lines);
        }
    }
    
    return $userData;
}

$stats = getStats($baseDir);
$selectedUser = $_GET['user'] ?? null;

// Validar se o usuário selecionado realmente existe
if ($selectedUser) {
    $userExists = false;
    foreach ($stats['recentUsers'] as $user) {
        if ($user['userId'] === $selectedUser) {
            $userExists = true;
            break;
        }
    }
    
    // Se o usuário não existe, limpar seleção
    if (!$userExists) {
        $selectedUser = null;
    }
}

$userSessions = $selectedUser ? getUserSessions($baseDir, $selectedUser) : [];
$userData = $selectedUser ? getUserData($baseDir, $selectedUser) : null;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard</title>
    
    <!-- No-cache headers -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- CSS com timestamp para evitar cache -->
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

            <!-- Main Dashboard Grid -->
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
                                    <a href="?user=<?= urlencode($user['userId']) ?>" class="user-link">
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
                                    <p>Aguardando dados de usuários do app...</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Coluna 2: Dados + Sessões -->
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
                            <a href="?" class="btn btn-secondary">
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
                                    <?php if (!empty($userData['geoData']['timezone'])): ?>
                                    <div class="detail-item">
                                        <label>Fuso horário:</label>
                                        <span><?= htmlspecialchars($userData['geoData']['timezone']) ?></span>
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
        </main>
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
</body>
</html> 