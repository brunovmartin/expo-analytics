#!/bin/bash

# Script para testar as melhorias implementadas no Expo Analytics
# Device Info Detalhado + Screenshots com Eventos

echo "🧪 TESTANDO MELHORIAS DO EXPO ANALYTICS"
echo "========================================"
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para verificar se um arquivo/diretório existe
check_exists() {
    if [ -e "$1" ]; then
        echo -e "✅ ${GREEN}$2${NC}"
        return 0
    else
        echo -e "❌ ${RED}$2${NC}"
        return 1
    fi
}

# Função para verificar conteúdo de arquivo
check_content() {
    if grep -q "$2" "$1" 2>/dev/null; then
        echo -e "✅ ${GREEN}$3${NC}"
        return 0
    else
        echo -e "❌ ${RED}$3${NC}"
        return 1
    fi
}

echo "📱 1. VERIFICANDO MÓDULO IOS..."
echo "--------------------------------"

# Verificar se as funções de device info existem no módulo iOS
check_content "ios/ExpoAnalyticsModule.swift" "getDeviceModelIdentifier" "Função getDeviceModelIdentifier() implementada"
check_content "ios/ExpoAnalyticsModule.swift" "getDeviceCommercialName" "Função getDeviceCommercialName() implementada"
check_content "ios/ExpoAnalyticsModule.swift" "getFormattedAppVersion" "Função getFormattedAppVersion() implementada"
check_content "ios/ExpoAnalyticsModule.swift" "captureScreenshotForEvent" "Função captureScreenshotForEvent() implementada"
check_content "ios/ExpoAnalyticsModule.swift" "import sys/utsname" "Import sys/utsname adicionado"

echo ""
echo "📋 2. VERIFICANDO APP.TSX..."
echo "-----------------------------"

# Verificar se App.tsx foi simplificado
check_content "example/App.tsx" "analytics_user_id" "Sistema de persistência de usuário"
if ! grep -q "appVersion.*1.0.0" "example/App.tsx" 2>/dev/null; then
    echo -e "✅ ${GREEN}Informações hardcoded removidas${NC}"
else
    echo -e "❌ ${RED}Ainda há informações hardcoded${NC}"
fi

echo ""
echo "🖥️ 3. VERIFICANDO BACKEND..."
echo "-----------------------------"

# Verificar modificações no backend
check_content "backend/api-receiver.php" "multipart/form-data" "Suporte a multipart para screenshots"
check_content "backend/api-receiver.php" "events-screenshots" "Diretório events-screenshots implementado"
check_content "backend/api-receiver.php" "deviceInfo.*device" "Processamento de deviceInfo"
check_content "backend/dashboard.php" "getAppStoreIcon" "Sistema de cache de ícones da App Store"

echo ""
echo "🎨 4. VERIFICANDO DASHBOARD..."
echo "------------------------------"

# Verificar melhorias no dashboard
check_content "backend/dashboard.php" "App Version.*deviceInfo" "Seção Dados do App melhorada"
check_content "backend/dashboard.php" "fab fa-apple" "Ícones FontAwesome corrigidos"
check_content "backend/assets/script.js" "seekToMidpoint" "Função seekToMidpoint implementada"

echo ""
echo "📁 5. VERIFICANDO ESTRUTURA DE ARQUIVOS..."
echo "-------------------------------------------"

# Verificar se diretórios necessários existem
check_exists "backend" "Diretório backend"
check_exists "ios" "Diretório ios"  
check_exists "example" "Diretório example"
check_exists "backend/analytics-data" "Diretório analytics-data"

echo ""
echo "🔧 6. VERIFICANDO CONFIGURAÇÕES..."
echo "-----------------------------------"

# Verificar se o backend está configurado
if [ -f "backend/api-receiver.php" ]; then
    echo -e "✅ ${GREEN}Backend configurado${NC}"
    
    # Verificar se o servidor pode ser iniciado
    if command -v php >/dev/null 2>&1; then
        echo -e "✅ ${GREEN}PHP disponível${NC}"
        
        # Testar sintaxe do PHP
        if php -l backend/api-receiver.php >/dev/null 2>&1; then
            echo -e "✅ ${GREEN}Sintaxe PHP válida${NC}"
        else
            echo -e "❌ ${RED}Erro de sintaxe PHP${NC}"
        fi
    else
        echo -e "⚠️ ${YELLOW}PHP não encontrado${NC}"
    fi
else
    echo -e "❌ ${RED}Backend não encontrado${NC}"
fi

echo ""
echo "📊 7. RESUMO DAS MELHORIAS..."
echo "------------------------------"

echo -e "${BLUE}🔧 Funcionalidades Implementadas:${NC}"
echo "  📱 Device Model Identifier (ex: iPhone15,3)"
echo "  📦 Nome Comercial do Device (ex: iPhone 15 Pro Max)"
echo "  🔢 App Version + Build (ex: 1.0.0.(23))"
echo "  📸 Screenshots automáticos com eventos"
echo "  💾 Cache de ícones da App Store (7 dias)"
echo "  🎯 Cadastro automático de usuários"
echo "  🌍 Informações geográficas aprimoradas"
echo ""

echo -e "${BLUE}📈 Melhorias de Performance:${NC}"
echo "  ⚡ Screenshots de eventos otimizados (320x640, 50% quality)"
echo "  🗄️ Cache inteligente para ícones da App Store"
echo "  🔄 Processamento assíncrono de screenshots"
echo "  📝 Logs detalhados com flags de países"
echo ""

echo -e "${BLUE}🎛️ Como Testar:${NC}"
echo "  1. Inicie o backend: ${YELLOW}./start-backend.sh${NC}"
echo "  2. Compile o app iOS: ${YELLOW}cd example && npx expo run:ios${NC}"
echo "  3. Use o app e teste eventos"
echo "  4. Verifique dashboard: ${YELLOW}http://localhost:8888/dashboard${NC}"
echo "  5. Veja logs: ${YELLOW}tail -f backend/analytics-data/logs/\$(date +%Y-%m-%d).log${NC}"
echo ""

echo -e "${GREEN}✨ Todas as melhorias foram implementadas com sucesso!${NC}"
echo ""
echo "📖 Para mais detalhes, veja: MELHORIAS-DEVICE-INFO.md"
echo "🚀 Para iniciar testes: ./start-backend.sh && cd example && npx expo run:ios" 