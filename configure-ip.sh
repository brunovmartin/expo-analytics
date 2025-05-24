#!/bin/bash

# Script para configurar IP automaticamente
# Uso: ./configure-ip.sh [IP] ou ./configure-ip.sh auto

echo "🔧 Configurador de IP - Expo Analytics"
echo ""

# Função para detectar IP automaticamente
detect_ip() {
    # macOS/Linux
    local ip=$(ifconfig | grep "inet " | grep -v 127.0.0.1 | head -1 | awk '{print $2}')
    
    # Se não encontrou, tentar outra forma
    if [[ -z "$ip" ]]; then
        ip=$(hostname -I 2>/dev/null | awk '{print $1}')
    fi
    
    # Se ainda não encontrou, usar localhost
    if [[ -z "$ip" ]]; then
        ip="localhost"
    fi
    
    echo "$ip"
}

# Parâmetro de entrada
TARGET_IP="$1"

if [[ "$TARGET_IP" == "auto" ]] || [[ -z "$TARGET_IP" ]]; then
    echo "🔍 Detectando IP automaticamente..."
    TARGET_IP=$(detect_ip)
fi

echo "📡 IP configurado: $TARGET_IP"
echo ""

# 1. Configurar backend/start-server.sh
if [[ -f "backend/start-server.sh" ]]; then
    echo "🔧 Configurando backend/start-server.sh..."
    
    # Backup
    cp backend/start-server.sh backend/start-server.sh.backup
    
    # Substituir IP
    sed -i.tmp "s/IP=\".*\"/IP=\"$TARGET_IP\"/" backend/start-server.sh
    rm backend/start-server.sh.tmp 2>/dev/null
    
    echo "✅ Backend configurado para: $TARGET_IP:8080"
else
    echo "❌ Arquivo backend/start-server.sh não encontrado"
fi

# 2. Configurar example/App.tsx
if [[ -f "example/App.tsx" ]]; then
    echo "🔧 Configurando example/App.tsx..."
    
    # Backup
    cp example/App.tsx example/App.tsx.backup
    
    # Substituir apiHost
    sed -i.tmp "s|http://[^']*:8080|http://$TARGET_IP:8080|g" example/App.tsx
    rm example/App.tsx.tmp 2>/dev/null
    
    echo "✅ App configurado para: http://$TARGET_IP:8080"
else
    echo "❌ Arquivo example/App.tsx não encontrado"
fi

echo ""
echo "🎯 Configuração concluída!"
echo ""
echo "📋 Próximos passos:"
echo "   1. Iniciar backend: ./start-backend.sh"
echo "   2. Executar app: cd example && npx expo run:ios"
echo "   3. Acessar dashboard: http://$TARGET_IP:8080/dashboard"
echo ""

# Verificar se está usando localhost
if [[ "$TARGET_IP" == "localhost" ]]; then
    echo "💡 Dica: Para dispositivo físico, execute:"
    echo "   ./configure-ip.sh auto"
    echo "   ou"
    echo "   ./configure-ip.sh 192.168.1.100"
fi

echo "🔄 Para reverter: usar os arquivos .backup criados" 