#!/bin/bash

# Script simplificado para logs iOS - Expo Analytics
# Uso: ./start-ios-logs-simple.sh

echo "📱 Abrindo Console.app para logs iOS - Expo Analytics"
echo "=================================================="
echo ""
echo "🎯 INSTRUÇÕES:"
echo ""
echo "1️⃣  O Console.app será aberto automaticamente"
echo "2️⃣  No Console.app:"
echo "     • Selecione seu dispositivo iOS ou Simulator na sidebar"
echo "     • No campo de busca, digite: ExpoAnalytics"
echo "     • Clique em 'Start streaming'"
echo ""
echo "3️⃣  Execute seu app Expo e veja os logs em tempo real!"
echo ""
echo "📋 Logs que você verá:"
echo "     📸 [ExpoAnalytics] Screenshot: 480×960, 45KB"
echo "     💾 [ExpoAnalytics] Frame 127 salvo: 45KB"
echo "     📤 [ExpoAnalytics] Enviando buffer com 300 frames"
echo "     ✅ [ExpoAnalytics] Upload concluído em 3.2s"
echo ""

# Abrir Console.app
if open /System/Applications/Utilities/Console.app; then
    echo "✅ Console.app aberto com sucesso!"
    echo ""
    echo "💡 PRÓXIMOS PASSOS:"
    echo "   1. Configure o filtro 'ExpoAnalytics' no Console.app"
    echo "   2. Execute: npx expo start (em outro terminal)"
    echo "   3. Abra seu app no dispositivo/simulator"
    echo "   4. Use o módulo Analytics e veja os logs!"
else
    echo "❌ Erro ao abrir Console.app"
    echo ""
    echo "💡 ALTERNATIVA MANUAL:"
    echo "   1. Abra manualmente: /System/Applications/Utilities/Console.app"
    echo "   2. Configure conforme instruções acima"
fi

echo ""
echo "🔗 Para mais opções, execute: ./start-ios-logs.sh"
echo "" 