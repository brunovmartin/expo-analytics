#!/bin/bash

echo "🔍 Descobrindo IP da máquina para ExpoAnalytics..."
echo ""

# Função para detectar o OS
detect_os() {
    if [[ "$OSTYPE" == "darwin"* ]]; then
        echo "macOS"
    elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
        echo "Linux"
    elif [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "cygwin" ]]; then
        echo "Windows"
    else
        echo "Unknown"
    fi
}

OS=$(detect_os)
echo "💻 Sistema detectado: $OS"

# Descobrir IP baseado no OS
case $OS in
    "macOS")
        echo "🔍 Procurando IP no macOS..."
        IP=$(ifconfig | grep "inet " | grep -v 127.0.0.1 | grep -v "169.254" | head -1 | awk '{print $2}')
        if [ -z "$IP" ]; then
            # Fallback: tentar pegar IP da interface Wi-Fi
            IP=$(ifconfig en0 | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}')
        fi
        ;;
    "Linux")
        echo "🔍 Procurando IP no Linux..."
        IP=$(hostname -I | awk '{print $1}')
        if [ -z "$IP" ]; then
            # Fallback
            IP=$(ip route get 8.8.8.8 | grep -oP 'src \K\S+')
        fi
        ;;
    "Windows")
        echo "🔍 Procurando IP no Windows..."
        echo "⚠️  Para Windows, execute no PowerShell:"
        echo "   (Get-NetIPAddress | Where-Object {$_.AddressFamily -eq 'IPv4' -and $_.PrefixOrigin -eq 'Dhcp'}).IPAddress"
        exit 0
        ;;
    *)
        echo "❌ Sistema não suportado automaticamente"
        echo "💡 Tente manualmente:"
        echo "   • macOS/Linux: ifconfig | grep inet"
        echo "   • Windows: ipconfig"
        exit 1
        ;;
esac

if [ -z "$IP" ]; then
    echo "❌ Não foi possível descobrir o IP automaticamente"
    echo ""
    echo "💡 Métodos manuais:"
    case $OS in
        "macOS")
            echo "   ifconfig | grep 'inet ' | grep -v 127.0.0.1"
            echo "   ifconfig en0 | grep inet"
            ;;
        "Linux")
            echo "   hostname -I"
            echo "   ip addr show | grep inet"
            ;;
    esac
    exit 1
fi

echo ""
echo "✅ IP encontrado: $IP"
echo ""
echo "🔧 Configuração para ExpoAnalytics:"
echo "   apiHost: 'http://$IP:8080'"
echo ""
echo "📋 Exemplo de código JavaScript:"
echo "   await ExpoAnalytics.start({"
echo "     apiHost: 'http://$IP:8080',"
echo "     userId: 'seu_user_id'"
echo "   });"
echo ""
echo "🧪 Teste de conectividade:"
echo "   curl -I http://$IP:8080"
echo ""
echo "📝 Copie esta linha para usar no seu código:"
echo "   http://$IP:8080" 