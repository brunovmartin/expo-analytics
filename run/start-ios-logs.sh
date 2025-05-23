#!/bin/bash

# Script para visualizar logs do iOS do Expo Analytics
# Uso: ./start-ios-logs.sh

echo "📱 Iniciando monitor de logs iOS - Expo Analytics"
echo "=================================================="
echo ""
echo "🔍 Este script mostra os logs do módulo Swift ExpoAnalytics"
echo "📋 Procure por mensagens com '[ExpoAnalytics]'"
echo ""

# Verificar se está no diretório correto
if [ ! -f "package.json" ]; then
    echo "⚠️  Execute este script na raiz do projeto Expo!"
    echo "📁 Navegue para o diretório que contém package.json"
    exit 1
fi

echo "🎯 Opções disponíveis para logs iOS no Expo:"
echo ""
echo "1️⃣  Console.app do macOS (Recomendado)"
echo "2️⃣  Simulator logs via terminal"
echo "3️⃣  Instalar React Native CLI"
echo ""

read -p "Escolha uma opção (1-3): " choice

case $choice in
    1)
        echo ""
        echo "📱 Abrindo Console.app do macOS..."
        echo ""
        echo "🔍 No Console.app:"
        echo "   1. Conecte seu dispositivo iOS ou use o Simulator"
        echo "   2. Selecione seu dispositivo na sidebar"
        echo "   3. Use o filtro: ExpoAnalytics"
        echo "   4. Execute seu app Expo"
        echo ""
        echo "💡 Logs NSLog aparecerão em tempo real!"
        
        # Abrir Console.app
        open /System/Applications/Utilities/Console.app
        
        echo ""
        echo "⏳ Console.app foi aberto. Configure o filtro e execute seu app!"
        ;;
        
    2)
        echo ""
        echo "📱 Tentando logs do Simulator..."
        
        # Verificar se há simulators rodando
        BOOTED_SIMULATORS=$(xcrun simctl list devices | grep "Booted" | wc -l)
        
        if [ "$BOOTED_SIMULATORS" -eq 0 ]; then
            echo "❌ Nenhum iOS Simulator rodando"
            echo ""
            echo "💡 Para usar esta opção:"
            echo "   1. Abra o iOS Simulator"
            echo "   2. Execute: npx expo start"
            echo "   3. Abra o app no simulator"
            echo "   4. Execute este script novamente"
            exit 1
        fi
        
        echo "✅ Encontrado(s) $BOOTED_SIMULATORS simulator(s) rodando"
        echo ""
        echo "🎯 Filtrando logs do ExpoAnalytics..."
        echo "   - Use Ctrl+C para parar"
        echo ""
        
        # Capturar logs do simulator
        xcrun simctl spawn booted log stream --predicate 'process CONTAINS "Expo"' --style compact | grep --line-buffered "ExpoAnalytics\|Analytics" || {
            echo ""
            echo "⚠️  Nenhum log encontrado com 'ExpoAnalytics'"
            echo ""
            echo "💡 Alternativas:"
            echo "   - Verifique se o app está rodando"
            echo "   - Use a opção 1 (Console.app) que é mais confiável"
            echo "   - Logs JavaScript aparecem no terminal do 'npx expo start'"
        }
        ;;
        
    3)
        echo ""
        echo "📦 Instalando React Native CLI..."
        
        # Instalar React Native CLI
        if npm install -g @react-native-community/cli; then
            echo ""
            echo "✅ React Native CLI instalado com sucesso!"
            echo ""
            echo "🚀 Tentando logs iOS..."
            
            npx react-native log-ios | grep --line-buffered "ExpoAnalytics\|expo-analytics\|Analytics" || {
                echo ""
                echo "⚠️  Comando ainda não funciona - isto é normal em projetos Expo"
                echo ""
                echo "💡 Use as opções 1 ou 2 que são específicas para Expo"
            }
        else
            echo ""
            echo "❌ Erro ao instalar React Native CLI"
            echo "💡 Use as opções 1 ou 2 como alternativa"
        fi
        ;;
        
    *)
        echo ""
        echo "❌ Opção inválida"
        echo "💡 Execute o script novamente e escolha 1, 2 ou 3"
        exit 1
        ;;
esac

echo ""
echo "📋 RESUMO:"
echo "   - Logs JavaScript: aparecem no terminal do 'npx expo start'"
echo "   - Logs NSLog Swift: use Console.app ou Simulator logs"
echo "   - Logs PHP: use './backend/start-server.sh'"
echo "" 