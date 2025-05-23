#!/bin/bash

echo "🧪 ExpoAnalytics - Script de Teste Completo"
echo "=========================================="
echo ""

# Verificar se estamos no diretório correto
if [ ! -f "App.tsx" ]; then
    echo "❌ Execute este script no diretório raiz do projeto expo-analytics"
    exit 1
fi

# Verificar dependências
echo "🔍 Verificando dependências..."

if ! command -v php &> /dev/null; then
    echo "❌ PHP não encontrado. Instale o PHP para executar o servidor backend."
    exit 1
fi

if ! command -v npx &> /dev/null; then
    echo "❌ Node.js/NPX não encontrado. Instale o Node.js."
    exit 1
fi

echo "✅ Dependências verificadas!"
echo ""

# Verificar se a pasta backend existe
if [ ! -d "backend" ]; then
    echo "❌ Pasta 'backend' não encontrada"
    exit 1
fi

# Verificar se os arquivos de exemplo existem
if [ ! -f "examples/AlertCaptureExample.tsx" ]; then
    echo "❌ Arquivo AlertCaptureExample.tsx não encontrado"
    exit 1
fi

if [ ! -f "examples/ComprehensiveUITestExample.tsx" ]; then
    echo "❌ Arquivo ComprehensiveUITestExample.tsx não encontrado"
    exit 1
fi

echo "✅ Arquivos de teste verificados!"
echo ""

# Função para verificar se o servidor está rodando
check_server() {
    if curl -s http://localhost:8888/status > /dev/null 2>&1; then
        return 0
    else
        return 1
    fi
}

# Verificar se o servidor já está rodando
if check_server; then
    echo "✅ Servidor backend já está rodando em localhost:8888"
else
    echo "🚀 Iniciando servidor backend..."
    cd backend
    php -S localhost:8888 api-receiver.php &
    SERVER_PID=$!
    cd ..
    
    # Aguardar servidor iniciar
    echo "⏳ Aguardando servidor inicializar..."
    sleep 3
    
    if check_server; then
        echo "✅ Servidor backend iniciado com sucesso!"
    else
        echo "❌ Falha ao iniciar servidor backend"
        kill $SERVER_PID 2>/dev/null
        exit 1
    fi
fi

echo ""
echo "🎯 TUDO PRONTO PARA TESTE!"
echo ""
echo "📱 PRÓXIMOS PASSOS:"
echo "1. Em outro terminal, execute:"
echo "   cd ios && rm -rf build/ && cd .."
echo "   npx expo run:ios"
echo ""
echo "2. Quando o app carregar, você verá a tela principal com botões:"
echo "   🚨 Teste de Alertas"
echo "   🎭 Teste Completo de UI"
echo "   📷 Screenshot Rápido"
echo "   ⚠️ Mostrar Alert"
echo ""
echo "3. Acesse o dashboard em:"
echo "   http://localhost:8888/dashboard"
echo ""
echo "🔍 TESTES DISPONÍVEIS:"
echo "• Alertas básicos e avançados"
echo "• Modais simples e aninhados"
echo "• ActionSheets (iOS/Android)"
echo "• Teclado virtual"
echo "• Múltiplos overlays sobrepostos"
echo ""
echo "📋 GUIA COMPLETO:"
echo "• README.md"
echo "• QUICK_START_TESTING.md"
echo "• TESTING_GUIDE.md"
echo ""
echo "🎉 Agora TODOS os overlays aparecerão nos screenshots!"
echo ""

# Manter o script rodando para mostrar logs do servidor
if [ ! -z "$SERVER_PID" ]; then
    echo "💡 Pressione Ctrl+C para parar o servidor backend"
    echo "📊 Logs do servidor:"
    echo "===================="
    wait $SERVER_PID
fi 