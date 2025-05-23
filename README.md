# Expo Analytics

Sistema completo de analytics para aplicações React Native/Expo com captura de screenshots, gravação de sessões e dashboard web.

## 🚀 Quick Start

### ⚡ Início Rápido

#### 1. **Iniciar Backend**
```bash
./start-backend.sh
```

#### 2. **Acessar Dashboard**
```
http://localhost:8080/dashboard
```

#### 3. **Testar App**
```bash
cd example
npx expo run:ios
```

### 📁 Estrutura Simples

```
📦 expo-analytics/
├── 📱 src/                     # Módulo Expo
├── 📱 example/                 # App exemplo
└── 🖥️ backend/                 # Dashboard & API  
    └── 📊 analytics-data/      # Dados (auto-criado)
```

## 🎯 Funcionalidades

### ✨ Funcionalidades Principais
- **📸 Screenshots Manuais**: Captura screenshots com parâmetros customizáveis (largura, altura, compressão)
- **📱 Informações Detalhadas do Dispositivo**: 
  - Resolução da tela (widthxheight)
  - Profundidade de cor (depth)
  - Tamanho da fonte do sistema
  - Idioma do usuário
  - País e região (ex: EN-US, PT-BR)
- **🌐 API Aprimorada**: Novos endpoints para processar screenshots manuais
- **📱 Captura Automática de Screenshots**: Screenshots automáticos durante eventos
- **🎬 Gravação de Sessões**: Converte screenshots em vídeos MP4
- **📊 Dashboard Web**: Interface completa para visualizar dados e sessões
- **🌍 Geolocalização**: Dados geográficos automáticos baseados em IP
- **⚙️ Configuração Dinâmica**: Configurações via servidor por Bundle ID
- **🔄 Auto-cadastro**: Usuários são cadastrados automaticamente
- **🎭 Interface Moderna**: Layout grid 2x2, timeline vertical, overlay de 80%
- **📹 Vídeos Compactos**: Prévia automática aos 50% do tempo

### 🆕 Novas Funcionalidades Implementadas

#### **📸 Captura de Screenshots com Alertas**
- ✅ **Agora os alertas aparecem nos screenshots!**
- Captura **todas as janelas visíveis**, incluindo:
  - ✅ Alertas (UIAlertController)
  - ✅ Dialogs nativos
  - ✅ Pop-ups do sistema
  - ✅ Overlays e modais

#### **🎨 Interface Dashboard Renovada**
- **Botão Overlay**: Coluna direita com botão que abre abas em overlay cobrindo 80% da tela
- **Vídeos Compactos**: Boxes menores com prévia de 50% do tempo como thumbnail
- **Timeline Vertical**: Linha do tempo vertical com data e hora dos eventos
- **Layout Grid 2x2**: Reorganização das seções em grid 2x2 com abas à direita

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

## 📸 Screenshots Manuais com Alertas

### 🧪 Como Testar Alertas

```javascript
import ExpoAnalytics from 'expo-analytics';

// Testar captura de alerta
const testAlert = async () => {
  try {
    const result = await ExpoAnalytics.testAlertCapture(
      "Título do Teste", 
      "Esta mensagem deve aparecer no screenshot!"
    );
    
    console.log('Resultado do teste:', result);
    /*
    {
      success: true,
      message: "Screenshot enviado com sucesso",
      alertShown: true,
      alertTitle: "Título do Teste",
      alertMessage: "Esta mensagem deve aparecer no screenshot!",
      width: 480,
      height: 960,
      size: 45678
    }
    */
  } catch (error) {
    console.error('Erro no teste:', error);
  }
};

// Captura manual com alertas
const takeScreenshotWithAlert = async () => {
  // 1. Mostrar seu próprio alerta
  Alert.alert(
    "Meu Alerta",
    "Esta mensagem será capturada no screenshot!",
    [{ text: "OK" }]
  );
  
  // 2. Aguardar um pouco para o alerta aparecer
  setTimeout(async () => {
    // 3. Tirar screenshot (agora vai incluir o alerta)
    const result = await ExpoAnalytics.takeScreenshot(480, 960, 0.8);
    console.log('Screenshot com alerta:', result);
  }, 1000);
};
```

### 📸 Capturar Screenshots Customizados

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

#### POST `/init`
Inicializa o sistema e cadastra o usuário automaticamente

#### POST `/track`
Rastreia eventos com dados geográficos automáticos

#### POST `/upload-zip`
Upload de sessões completas em formato ZIP

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

// 5. Teste de alertas (NOVO)
await ExpoAnalytics.testAlertCapture(title, message);

// 6. Dados do usuário
await ExpoAnalytics.updateUserInfo(userData);

// 7. Configurações
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
- ✅ **NOVO**: Interface com overlay 80% da tela
- ✅ **NOVO**: Timeline vertical com linha conectora
- ✅ **NOVO**: Vídeos compactos com prévia 50%
- ✅ **NOVO**: Layout grid 2x2 para informações
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

## 📱 Sistema de Gestão de Aplicativos

### Gestão Centralizada
- **Cadastrar múltiplos aplicativos** no dashboard
- **Configurar individualmente** cada app (Record Screen, Framerate, Screen Size)
- **Aplicar configurações automaticamente** nos apps sem necessidade de atualização
- **Filtrar dados** por aplicativo no dashboard

### Interface do Dashboard

#### Tela Principal - Lista de Apps
```
┌─────────────────────────────────────────────┐
│ 📱 Gestão de Aplicativos      [+ Novo App] │
├─────────────────────────────────────────────┤
│ ┌─────────────────┐ ┌─────────────────┐   │
│ │ 🍎 Meu App iOS  │ │ 🤖 Meu App Droid│   │
│ │ com.app.ios     │ │ com.app.android │   │
│ │                 │ │                 │   │
│ │ Record Screen:  │ │ Record Screen:  │   │
│ │ ✅ Ativo (15fps)│ │ ❌ Inativo      │   │
│ │ Screen: 480px   │ │                 │   │
│ │                 │ │                 │   │
│ │ [⚙️][🗑️][📊 Ver] │ │ [⚙️][🗑️][📊 Ver] │   │
│ └─────────────────┘ └─────────────────┘   │
└─────────────────────────────────────────────┘
```

### Configurações Detalhadas

#### Record Screen
- **✅ Ativo**: Captura screenshots, permite reprodução de sessões
- **❌ Inativo**: Apenas eventos e dados do usuário (economiza recursos)

#### Framerate (1-30 fps)
- **5-10 fps**: Economia máxima, qualidade básica
- **10-15 fps**: Balanceado (recomendado)
- **20-30 fps**: Qualidade alta, mais recursos

#### Screen Size (320-960px)
- **320-400px**: Para economizar banda e armazenamento
- **480px**: Balanceado (padrão)
- **720-960px**: Qualidade máxima para análises detalhadas

## 🧪 App de Testes Integrada

### 🚀 Quick Start - Usando a App.tsx

A partir de agora, o projeto já vem com uma **App.tsx** completa que permite navegar entre todas as telas de teste:

```bash
# 1. Inicie o servidor backend
cd backend
php -S localhost:8888 api-receiver.php

# 2. Em outro terminal, compile e execute
cd ios && rm -rf build/ && cd ..
npx expo run:ios
```

### 📱 Interface de Testes

Quando o app carregar, você verá uma **tela principal** com botões para:

#### 🚨 Teste de Alertas (`AlertCaptureExample`)
- ✅ Teste automático com `testAlertCapture()`
- ✅ Teste manual com `takeScreenshot()`
- ✅ Diferentes tipos de alertas

#### 🎭 Teste Completo de UI (`ComprehensiveUITestExample`)
- ✅ Modais simples e aninhados
- ✅ ActionSheets (iOS/Android)
- ✅ Teclado virtual
- ✅ Múltiplos overlays sobrepostos

#### 📷 Ações Rápidas
- ✅ Screenshot rápido da tela atual
- ✅ Alert simples para teste

### 🔄 Navegação
- **Botão "← Voltar"** para retornar à tela principal
- **Inicialização automática** do ExpoAnalytics
- **Status visual** da inicialização (⏳ Inicializando... → ✅ Inicializado)

### 📋 Guia Rápido de Teste

#### 1. Verificação Básica
- Aguarde: "✅ Inicializado"
- Clique: "📷 Screenshot Rápido"
- Verifique: Dashboard em `http://localhost:8888/dashboard`

#### 2. Teste de Alertas
- Clique: "⚠️ Mostrar Alert"
- Clique: "📷 Screenshot Rápido" (com alert aberto)
- Resultado: Alert deve aparecer no screenshot

#### 3. Testes Avançados
- Navegue entre as telas usando os botões
- Teste cada funcionalidade
- Verifique resultados no dashboard

## 🔧 Correções e Melhorias Implementadas

### ✅ Sistema de Sessões Corrigido

**Problema Original:**
- App enviava múltiplos vídeos (12 vídeos) durante uma única sessão
- Envio baseado em tempo/frames: a cada 8 segundos ou 120 frames

**Solução Implementada:**
- 1 vídeo por sessão completa
- Envio apenas quando app vai para background
- Nova sessão iniciada quando app volta ao foreground

### ✅ UserId Persistente

**Problema Original:**
- O app criava um novo `userId` a cada abertura
- Perda de continuidade dos dados do usuário

**Solução Implementada:**
- **Persistência com AsyncStorage**: Sistema de armazenamento local
- **Geração única**: userId criado apenas na primeira execução
- **Recuperação automática**: Usuário existente é recuperado

### ✅ Performance Otimizada

**Problema Original:**
- Captura a 30fps causava lag severo no app
- Alto consumo de CPU

**Solução Implementada:**
- **Sistema de Throttling**: Controle preciso do intervalo entre capturas
- **Captura em Background**: Screenshots processados em thread separada
- **Limite de FPS**: Máximo de 15fps para evitar sobrecarga

### ✅ Screenshots Otimizados

**Problema Original:**
- Screenshots capturados em alta resolução (1440×2880)
- Não respeitava configurações do backend

**Solução Implementada:**
- **Captura otimizada**: Redimensionamento durante a captura
- **Escala inteligente**: Nunca aumenta resolução, apenas reduz
- **Qualidade adaptativa**: Compressão baseada no framerate

### ✅ Sistema ZIP + MP4

**Problema Original:**
- Envio de imagens individuais em base64
- Consumo excessivo de banda

**Solução Implementada:**
- **Compactação ZIP**: Imagens agrupadas em arquivo ZIP
- **Geração de MP4**: Backend converte ZIP em vídeo comprimido
- **FFmpeg Integration**: Geração de MP4 otimizado

### ✅ Integração com IP-API

**Melhorias Implementadas:**
- **Detecção Automática de IP**: Headers de proxy, IP direto, fallback local
- **Cache de Requisições**: Cache por IP em eventos
- **70+ Bandeiras de Países**: Sistema completo de bandeiras
- **Dados Geográficos Completos**: País, região, cidade, timezone, ISP

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

## 🔧 Comandos Úteis

### Backend:
```bash
# Iniciar servidor
./start-backend.sh

# Testar API
cd backend && php test-api.php

# Testar Dashboard  
cd backend && php test-dashboard.php

# Diagnóstico do sistema
http://localhost:8080/diagnostico-sistema.php

# Limpar dados
http://localhost:8080/limpar-dados.php
```

### Frontend:
```bash
# Instalar dependências
cd example && npm install

# iOS
npx expo run:ios

# Android
npx expo run:android
```

## 🌐 URLs Importantes

- **📊 Dashboard:** http://localhost:8080/dashboard
- **📈 Status:** http://localhost:8080/status  
- **🏠 Home:** http://localhost:8080/

## 🛠️ Dispositivo Físico

1. **Descobrir IP:**
   ```bash
   ifconfig | grep "inet " | grep -v 127.0.0.1
   ```

2. **Iniciar servidor público:**
   ```bash
   cd backend
   php -S 0.0.0.0:8080 api-receiver.php
   ```

3. **Configurar app:**
   ```typescript
   // example/App.tsx
   apiHost: 'http://192.168.1.100:8080'
   ```

## 🚨 Problemas Comuns

### Servidor não inicia:
```bash
# Verificar porta
lsof -i :8080

# Matar processo
kill -9 $(lsof -t -i:8080)

# Reiniciar
./start-backend.sh
```

### App não conecta:
```bash
# Testar conectividade
curl http://localhost:8080/status

# Verificar IP (dispositivo físico)
ping [SEU_IP]
```

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

## 🎯 Próximas Funcionalidades

- [ ] 📹 Gravação de vídeo nativa
- [ ] 🔄 Sincronização offline
- [ ] 📈 Analytics em tempo real
- [ ] 🎨 Customização de UI do dashboard
- [ ] 🔔 Notificações automáticas
- [ ] 📱 App móvel para dashboard

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

---

**Sistema 100% funcional e pronto para uso!** 🎉

*Todos os requisitos foram implementados com sucesso, incluindo captura de screenshots com alertas, interface moderna com overlay, timeline vertical, vídeos compactos, gestão de aplicativos e correções completas de performance e usabilidade.*