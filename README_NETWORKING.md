# Configuração de Rede - ExpoAnalytics

## Problema: "Falha ao enviar screenshot para o servidor"

Este erro geralmente ocorre porque o módulo está tentando se conectar a `localhost:8080`, que não funciona em dispositivos reais (apenas no simulador).

## Solução

### 1. Descobrir o IP da sua máquina

#### macOS
```bash
ifconfig | grep "inet " | grep -v 127.0.0.1
```

#### Windows
```cmd
ipconfig | findstr IPv4
```

#### Linux
```bash
hostname -I
```

### 2. Usar o IP descoberto

No seu código React Native, ao inicializar o ExpoAnalytics:

```javascript
import * as ExpoAnalytics from 'expo-analytics';

// ❌ Não funciona em dispositivos reais
await ExpoAnalytics.start({
  apiHost: 'http://localhost:8080',
  userId: 'user123'
});

// ✅ Funciona em dispositivos reais
await ExpoAnalytics.start({
  apiHost: 'http://192.168.1.100:8080', // Use o IP da sua máquina
  userId: 'user123'
});
```

### 3. Script automático para descobrir IP

Salve este script como `get-ip.sh`:

```bash
#!/bin/bash
echo "🔍 Descobrindo IP da máquina..."

if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    IP=$(ifconfig | grep "inet " | grep -v 127.0.0.1 | head -1 | awk '{print $2}')
elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
    # Linux
    IP=$(hostname -I | awk '{print $1}')
else
    echo "Sistema não suportado. Use: ifconfig (macOS/Linux) ou ipconfig (Windows)"
    exit 1
fi

if [ -z "$IP" ]; then
    echo "❌ Não foi possível descobrir o IP automaticamente"
    echo "💡 Tente manualmente: ifconfig | grep inet"
else
    echo "✅ IP encontrado: $IP"
    echo "🔧 Use esta configuração no seu app:"
    echo "   apiHost: 'http://$IP:8080'"
fi
```

Execute com:
```bash
chmod +x get-ip.sh
./get-ip.sh
```

### 4. Verificar conectividade

Para testar se seu servidor está acessível:

```bash
# Substituir 192.168.1.100 pelo seu IP
curl -I http://192.168.1.100:8080/health
```

### 5. Firewall

Certifique-se que o firewall permite conexões na porta 8080:

#### macOS
```bash
sudo pfctl -f /etc/pf.conf
```

#### Windows
- Painel de Controle > Windows Defender Firewall
- Permitir app através do firewall
- Adicionar porta 8080

#### Linux (Ubuntu)
```bash
sudo ufw allow 8080
```

## Debugging

Para ver logs detalhados no iOS:
1. Abra o Xcode
2. Window > Devices and Simulators
3. Selecione seu dispositivo
4. Clique em "Open Console"
5. Procure por logs com "[ExpoAnalytics]"

## Exemplo completo

```javascript
// App.js
import * as ExpoAnalytics from 'expo-analytics';

export default function App() {
  useEffect(() => {
    const initAnalytics = async () => {
      try {
        // Use o IP da sua máquina aqui
        await ExpoAnalytics.start({
          apiHost: 'http://192.168.1.100:8080',
          userId: 'user_' + Math.random().toString(36).substr(2, 9),
          userData: {
            appName: 'MeuApp',
            version: '1.0.0'
          }
        });
        console.log('✅ Analytics iniciado com sucesso');
      } catch (error) {
        console.error('❌ Erro ao iniciar analytics:', error);
      }
    };
    
    initAnalytics();
  }, []);
  
  return (
    // Seu app aqui
  );
}
``` 