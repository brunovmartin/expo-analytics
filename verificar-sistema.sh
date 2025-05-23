#!/bin/bash

echo "🔍 Verificação do Sistema Expo Analytics"
echo "========================================"

# Verificar backend
echo ""
echo "📡 Backend:"
if pgrep -f "php.*api-receiver.php" > /dev/null; then
    echo "✅ Backend rodando"
else
    echo "❌ Backend não está rodando"
    echo "   Para iniciar: cd backend && php -S localhost:8080 api-receiver.php"
fi

# Verificar FFmpeg
echo ""
echo "🎬 FFmpeg:"
if which ffmpeg > /dev/null; then
    echo "✅ FFmpeg instalado"
else
    echo "❌ FFmpeg não encontrado"
    echo "   Para instalar: brew install ffmpeg"
fi

# Verificar configuração do app
echo ""
echo "⚙️ Configuração do App:"
if curl -s "http://localhost:8080/app-config?bundleId=expo.modules.analytics.example" | grep -q '"recordScreen":true'; then
    echo "✅ Record Screen ativado"
else
    echo "❌ Record Screen desativado ou backend offline"
fi

# Verificar integração geográfica
echo ""
echo "🌍 Integração Geográfica:"
geo_response=$(curl -s -X POST "http://localhost:8080/init" -H "Content-Type: application/json" -d '{"userId":"test-verification","userData":{"test":true}}')
if echo "$geo_response" | grep -q '"flag"'; then
    flag=$(echo "$geo_response" | jq -r '.geo.flag // "❓"')
    country=$(echo "$geo_response" | jq -r '.geo.country // "Unknown"')
    echo "✅ Dados geográficos funcionando: $flag $country"
else
    echo "❌ Dados geográficos não funcionando"
fi

# Verificar estrutura de dados
echo ""
echo "📂 Estrutura de Dados:"
for dir in "events" "videos" "users" "logs" "apps"; do
    if [ -d "backend/analytics-data/$dir" ]; then
        echo "✅ $dir/"
    else
        echo "❌ $dir/ não existe"
    fi
done

# Verificar logs recentes
echo ""
echo "📝 Logs Recentes:"
log_file="backend/analytics-data/logs/$(date +%Y-%m-%d).log"
if [ -f "$log_file" ]; then
    echo "✅ Log de hoje existe"
    echo "   Últimas 3 linhas:"
    tail -3 "$log_file" | sed 's/^/   /'
else
    echo "❌ Nenhum log de hoje"
fi

# Verificar eventos
echo ""
echo "📊 Eventos:"
event_count=$(find backend/analytics-data/events -name "*.jsonl" -exec wc -l {} \; 2>/dev/null | awk '{sum+=$1} END {print sum+0}')
echo "✅ $event_count eventos salvos"

# Verificar vídeos
echo ""
echo "🎥 Vídeos:"
video_count=$(find backend/analytics-data/videos -name "*.mp4" 2>/dev/null | wc -l)
echo "✅ $video_count vídeos gerados"

# Status geral
echo ""
echo "🎯 Status Geral:"
if pgrep -f "php.*api-receiver.php" > /dev/null && which ffmpeg > /dev/null; then
    echo "✅ Sistema pronto para uso"
    echo ""
    echo "📱 Para testar:"
    echo "   1. cd example && npx expo run:ios"
    echo "   2. Clique em 'Iniciar Analytics'"
    echo "   3. Aguarde alguns segundos"
    echo "   4. Clique em 'Parar Analytics'"
    echo "   5. Verifique os logs: tail -20 $log_file"
else
    echo "❌ Sistema não está completamente configurado"
fi

echo ""
echo "========================================" 