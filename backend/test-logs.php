<?php
// Script de teste para verificar logs PHP
// Para usar: php test-logs.php

echo "🧪 Testando Logs PHP\n";
echo "==================\n\n";

// Teste 1: Echo normal
echo "✅ Echo normal funcionando\n";

// Teste 2: Error log
error_log("📋 [Test] Esta é uma mensagem de erro para stderr");

// Teste 3: Trigger error
trigger_error("⚠️ [Test] Este é um warning de teste", E_USER_WARNING);

// Teste 4: Simular log de servidor
echo "[" . date('D M j H:i:s Y') . "] 127.0.0.1:12345 GET /test-logs.php - 200\n";

echo "\n🎯 Se você viu todas as mensagens acima, os logs estão funcionando!\n";
echo "📋 Para testar o servidor, execute: php -S localhost:8080 test-logs.php 2>&1\n";
?> 