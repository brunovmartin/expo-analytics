#!/bin/bash

# Script para iniciar o servidor PHP local
# Uso: ./start-server.sh (executar da pasta backend)

IP="localhost"
PORT="8080"

echo "🚀 Iniciando servidor PHP Analytics..."
echo "📡 URL: http://$IP:$PORT"
echo "📁 Dados serão salvos em: ./analytics-data/"
echo "⚙️  Configurações: Upload máximo 50MB, Tempo limite 5min"
echo ""
echo "💡 Dicas:"
echo "   - Acesse http://$IP:$PORT/status para verificar se está funcionando"
echo "   - Use Ctrl+C para parar o servidor"
echo "   - Execute 'php tests/test-api.php' em outro terminal para testar"
echo ""
echo "📱 Para testar em dispositivo físico:"
echo "   - Descubra seu IP local: ifconfig | grep 'inet '"
echo "   - Use: php -S 0.0.0.0:$PORT -c php-config.ini api-receiver.php"
echo "   - No app, use: http://[SEU_IP]:$PORT"
echo ""
echo "📋 LOGS:"
echo "   - Logs PHP aparecerão abaixo"
echo "   - Para logs Swift/iOS: execute 'npx react-native log-ios' em outro terminal"
echo ""

# Verificar se o arquivo de configuração existe
if [ ! -f "php-config.ini" ]; then
    echo "⚠️  Arquivo php-config.ini não encontrado, usando configurações padrão"
    echo "🔥 Iniciando servidor com logs..."
    echo ""
    php -S $IP:$PORT api-receiver.php 2>&1
else
    echo "✅ Usando configurações customizadas (php-config.ini)"
    echo "🔥 Iniciando servidor com logs..."
    echo ""
    php -S $IP:$PORT -c php-config.ini api-receiver.php 2>&1
fi
