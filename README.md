# Expo Analytics

Sistema completo de analytics para aplicações React Native/Expo com captura de screenshots, gravação de sessões e dashboard web.

## 🚀 Funcionalidades

### ✨ Novas Funcionalidades Implementadas
- **📸 Screenshots Manuais**: Captura screenshots com parâmetros customizáveis (largura, altura, compressão)
- **📱 Informações Detalhadas do Dispositivo**: 
  - Resolução da tela (widthxheight)
  - Profundidade de cor (depth)
  - Tamanho da fonte do sistema
  - Idioma do usuário
  - País e região (ex: EN-US, PT-BR)
- **🌐 API Aprimorada**: Novos endpoints para processar screenshots manuais

### Funcionalidades Core
- **📱 Captura Automática de Screenshots**: Screenshots automáticos durante eventos
- **🎬 Gravação de Sessões**: Converte screenshots em vídeos MP4
- **📊 Dashboard Web**: Interface completa para visualizar dados e sessões
- **🌍 Geolocalização**: Dados geográficos automáticos baseados em IP
- **⚙️ Configuração Dinâmica**: Configurações via servidor por Bundle ID
- **🔄 Auto-cadastro**: Usuários são cadastrados automaticamente

## 📦 Instalação

```bash
# Instalar o pacote
npm install expo-analytics

# ou com Yarn
yarn add expo-analytics
```

## 🛠️ Configuração Básica

### 1. Inicializar o Sistema

```typescript
import * as ExpoAnalytics from 'expo-analytics';

// Inicializar e cadastrar usuário automaticamente
await ExpoAnalytics.init({
  userId: 'user-123',
  apiHost: 'http://localhost:8888',
  userData: {
    appVersion: '1.0.0',
    userType: 'premium'
  }
});
```

### 2. Iniciar Tracking (Opcional)

```typescript
// Iniciar gravação de sessões (se habilitado no servidor)
await ExpoAnalytics.start({
  framerate: 10,      // FPS da gravação
  screenSize: 480     // Resolução dos screenshots
});
```

### 3. Rastrear Eventos

```typescript
// Rastrear eventos (com screenshot automático)
await ExpoAnalytics.trackEvent('button_click', 'purchase_button');
await ExpoAnalytics.trackEvent('page_view', 'product_details');
```

## 📸 Nova Funcionalidade: Screenshots Manuais

```typescript
// Capturar screenshot e enviar para o servidor
const result = await ExpoAnalytics.takeScreenshot(
  640,    // largura
  1280,   // altura  
  0.8     // compressão (0.0 a 1.0)
);

if (result.success) {
  console.log('Screenshot enviado para o dashboard!');
  console.log(`Tamanho: ${result.width}x${result.height}`);
  console.log(`Arquivo: ${result.size} bytes`);
  console.log(result.message);
} else {
  console.error('Erro:', result.error);
}
```

### Exemplos de Uso de Screenshots

```typescript
// Screenshot de alta qualidade
const hd = await ExpoAnalytics.takeScreenshot(1080, 1920, 0.9);

// Screenshot compacto para economizar dados
const compact = await ExpoAnalytics.takeScreenshot(320, 640, 0.6);

// Screenshot com qualidade média
const standard = await ExpoAnalytics.takeScreenshot(640, 1280, 0.8);

// Os screenshots aparecerão automaticamente na aba "Screenshots" do dashboard
```

## 📱 Informações Automáticas do Dispositivo

O sistema agora coleta automaticamente as seguintes informações:

```typescript
// Informações coletadas automaticamente:
{
  platform: "iOS 17.0",                    // Sistema operacional
  device: "iPhone15,2 (iPhone 14 Pro)",   // Modelo do dispositivo
  appVersion: "1.0.0.(123)",              // Versão do app
  screenSize: "1179x2556",                // Resolução da tela
  depth: "32 bits",                       // Profundidade de cor
  fontSize: "17pt (system: 16pt)",        // Tamanho da fonte
  userLanguage: "pt",                     // Idioma do usuário
  country: "PT-BR",                       // País e região
  bundleId: "com.example.app"             // Bundle ID
}
```

## 🌐 API Backend

### Novos Endpoints

#### POST `/take-screenshot`
Recebe screenshots manuais capturados via `takeScreenshot()` e salva na pasta screenshots para aparecer no dashboard.

```json
{
  "userId": "user-123",
  "screenshotData": "base64_image_data",
  "width": 640,
  "height": 1280,
  "compression": 0.8,
  "timestamp": 1640995200,
  "type": "manual"
}
```

### Estrutura de Pastas no Servidor

```
analytics-data/
├── users/              # Dados dos usuários
├── events/             # Eventos rastreados
├── events-screenshots/ # Screenshots de eventos
├── screenshots/        # Screenshots de sessão E manuais
├── videos/            # Sessões convertidas em vídeo
└── logs/              # Logs do sistema
```

## 🎮 API Completa

### Métodos Principais

```typescript
// 1. Inicialização (obrigatório)
await ExpoAnalytics.init(options);

// 2. Controle de sessões
await ExpoAnalytics.start(options);
await ExpoAnalytics.stop();

// 3. Eventos
await ExpoAnalytics.trackEvent(event, value);

// 4. Screenshots manuais (NOVO)
await ExpoAnalytics.takeScreenshot(width?, height?, compression?);

// 5. Dados do usuário
await ExpoAnalytics.updateUserInfo(userData);

// 6. Configurações
const config = await ExpoAnalytics.fetchAppConfig(apiHost, bundleId);
```

### Tipos TypeScript

```typescript
interface TakeScreenshotResult {
  success: boolean;
  message?: string;     // Mensagem de sucesso
  width?: number;       // Largura real
  height?: number;      // Altura real
  size?: number;        // Tamanho em bytes
  error?: string;       // Mensagem de erro
}
```

## 🔧 Configuração do Servidor

### 1. Iniciar Servidor PHP

```bash
cd backend
php -S localhost:8888 api-receiver.php
```

### 2. Dashboard Web

Acesse: `http://localhost:8888/dashboard`

**Funcionalidades do Dashboard:**
- ✅ Lista de usuários e apps
- ✅ Visualização de eventos e timeline
- ✅ Galeria de screenshots de eventos
- ✅ Player de vídeos de sessões
- ✅ **NOVO**: Galeria de screenshots manuais
- ✅ **NOVO**: Informações detalhadas do dispositivo
- ✅ Dados geográficos com bandeiras
- ✅ Configurações dinâmicas por app

### 3. Configurar App no Servidor

```bash
curl -X POST http://localhost:8888/apps \
  -H "Content-Type: application/json" \
  -d '{
    "bundleId": "com.example.app",
    "name": "Meu App",
    "platform": "ios",
    "config": {
      "recordScreen": true,
      "framerate": 10,
      "screenSize": 480
    }
  }'
```

## 📊 Casos de Uso

### E-commerce
```typescript
// Produto visualizado
await ExpoAnalytics.trackEvent('product_view', 'product_123');

// Screenshot da tela de checkout (salvo no dashboard)
await ExpoAnalytics.takeScreenshot(640, 1280, 0.8);

// Compra finalizada
await ExpoAnalytics.trackEvent('purchase', 'order_456');
```

### Jogos
```typescript
// Level completado
await ExpoAnalytics.trackEvent('level_complete', 'level_5');

// Screenshot de conquista (salvo no dashboard)
await ExpoAnalytics.takeScreenshot(1080, 1920, 0.9);

// Game over
await ExpoAnalytics.trackEvent('game_over', 'score_1250');
```

### Formulários
```typescript
// Campo preenchido
await ExpoAnalytics.trackEvent('field_filled', 'email');

// Screenshot do erro (salvo no dashboard)
await ExpoAnalytics.takeScreenshot(320, 640, 0.6);

// Formulário enviado
await ExpoAnalytics.trackEvent('form_submit', 'contact_form');
```

## 🔒 Privacidade e Segurança

- **Dados Locais**: Screenshots ficam no servidor configurado
- **Geolocalização**: Baseada apenas em IP público
- **Opt-out**: Usuário pode desabilitar funcionalidades
- **Compressão**: Screenshots são otimizados automaticamente
- **Auto-limpeza**: Arquivos temporários são removidos automaticamente

## 🎯 Próximas Funcionalidades

- [ ] 📹 Gravação de vídeo nativa
- [ ] 🔄 Sincronização offline
- [ ] 📈 Analytics em tempo real
- [ ] 🎨 Customização de UI do dashboard
- [ ] 🔔 Notificações automáticas
- [ ] 📱 App móvel para dashboard

## 🐛 Solução de Problemas

### Screenshots não são enviados
```typescript
// Verificar se o envio foi bem-sucedido
const result = await ExpoAnalytics.takeScreenshot();
if (!result.success) {
  console.log('Erro:', result.error);
}

// Verificar se o screenshot aparece no dashboard em:
// http://localhost:8888/dashboard -> aba Screenshots
```

### Servidor não recebe dados
```bash
# Verificar logs do servidor
tail -f backend/analytics-data/logs/$(date +%Y-%m-%d).log
```

### Dashboard não carrega
```bash
# Verificar se o servidor está rodando
curl http://localhost:8888/status
```

## 📄 Licença

MIT License - veja [LICENSE](LICENSE) para detalhes.

---

## 🚀 Exemplo Completo

```typescript
import * as ExpoAnalytics from 'expo-analytics';

export default function App() {
  useEffect(() => {
    initializeAnalytics();
  }, []);

  const initializeAnalytics = async () => {
    // 1. Inicializar sistema
    await ExpoAnalytics.init({
      userId: 'user-' + Date.now(),
      apiHost: 'http://localhost:8888',
      userData: {
        appVersion: '1.0.0',
        userType: 'premium'
      }
    });

    // 2. Iniciar gravação (opcional)
    await ExpoAnalytics.start({
      framerate: 10,
      screenSize: 480
    });
  };

  const handleButtonPress = async () => {
    // 3. Rastrear evento
    await ExpoAnalytics.trackEvent('button_press', 'main_cta');
    
    // 4. Capturar screenshot e enviar para dashboard
    const result = await ExpoAnalytics.takeScreenshot(640, 1280, 0.8);
    
    if (result.success) {
      console.log('Screenshot enviado para o dashboard!');
    }
  };

  return (
    <View>
      <Button title="Pressione Aqui" onPress={handleButtonPress} />
    </View>
  );
}
```

**Dashboard disponível em:** http://localhost:8888/dashboard 