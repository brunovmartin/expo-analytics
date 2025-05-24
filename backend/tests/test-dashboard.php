<?php
// Script para testar todas as rotas do dashboard
// Uso: php test-dashboard.php (executar da pasta backend)

$baseUrl = 'http://localhost:8080';

echo "🧪 Testando Dashboard Analytics...\n\n";

// Função para fazer requisições
function testRoute($url, $description) {
    echo "🔍 Testando: $description\n";
    echo "📡 URL: $url\n";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $result = @file_get_contents($url, false, $context);
    
    if ($result !== false) {
        $httpCode = 200;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $httpCode = (int)$matches[1];
                    break;
                }
            }
        }
        
        if ($httpCode === 200) {
            echo "✅ Funcionando - HTTP $httpCode\n";
            return true;
        } else {
            echo "⚠️  HTTP $httpCode\n";
            return false;
        }
    } else {
        echo "❌ Erro de conexão\n";
        return false;
    }
}

// Lista de rotas para testar
$routes = [
    '/' => 'Página inicial (status)',
    '/status' => 'Status da API',
    '/dashboard' => 'Dashboard principal',
    '/dashboard.php' => 'Dashboard (extensão .php)',
    '/index.html' => 'Página index HTML',
    '/assets/style.css' => 'Arquivo CSS',
    '/assets/script.js' => 'Arquivo JavaScript'
];

$successCount = 0;
$totalCount = count($routes);

foreach ($routes as $route => $description) {
    if (testRoute($baseUrl . $route, $description)) {
        $successCount++;
    }
    echo "\n";
}

echo "📊 Resultado dos Testes:\n";
echo "✅ Funcionando: $successCount/$totalCount rotas\n";

if ($successCount === $totalCount) {
    echo "🎉 Todos os testes passaram! Dashboard está funcionando perfeitamente.\n";
    echo "\n🚀 Acesse: http://localhost:8080/dashboard\n";
} else {
    echo "⚠️  Alguns testes falharam. Verifique o servidor.\n";
}

echo "\n📝 Rotas disponíveis:\n";
echo "   🏠 http://localhost:8080/ (página inicial)\n";
echo "   📊 http://localhost:8080/dashboard (dashboard principal)\n";
echo "   📈 http://localhost:8080/status (status da API)\n";
echo "   🔧 http://localhost:8080/assets/style.css (CSS)\n";
echo "   ⚙️  http://localhost:8080/assets/script.js (JavaScript)\n";

echo "\n💡 Se alguma rota não funcionar, reinicie o servidor:\n";
echo "   ./start-server.sh\n";
?> 