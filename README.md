# Expo Analytics

Sistema completo de analytics para aplicativos React Native/Expo com captura de screenshots, rastreamento de eventos e dashboard web interativo.

## 🆕 Novidades - Sistema de Gestão de Aplicativos

### Gestão Centralizada de Apps
- **Dashboard de Apps**: Interface para cadastrar e gerenciar múltiplos aplicativos
- **Configurações por App**: Cada aplicativo possui configurações independentes
- **Controle Remoto**: Configurações são buscadas automaticamente pelo app

### Configurações Disponíveis

#### Record Screen
- **Ativar/Desativar**: Controla se o app deve capturar screenshots
- **Aplicação**: Se desabilitado, o app não inicia a gravação mesmo chamando `start()`

#### Framerate (quando Record Screen ativo)
- **Range**: 1 a 30 fps
- **Controle**: Slider para ajuste fino da qualidade vs performance

#### Screen Size (quando Record Screen ativo)
- **Opções**: 320px a 960px (largura)
- **Proporção**: Mantém automaticamente proporção 1:2 (largura:altura)
- **Otimização**: Permite ajustar qualidade e tamanho dos uploads

## 🚀 Como Usar

### 1. Cadastrar Aplicativo no Dashboard

1. Acesse o dashboard: `http://localhost:8080/dashboard`
2. Clique em "Novo Aplicativo"
3. Preencha:
   - **Nome**: Nome amigável do app
   - **Bundle ID**: Identificador único (ex: `com.empresa.meuapp`)
   - **Plataforma**: iOS ou Android

### 2. Configurar o Aplicativo

1. Na lista de apps, clique no ícone de configuração (⚙️)
2. Configure:
   - **Record Screen**: Ativar/desativar gravação
   - **Framerate**: Quantos frames por segundo capturar
   - **Screen Size**: Resolução das capturas

### 3. Usar no Aplicativo React Native

```typescript
import * as ExpoAnalytics from 'expo-analytics';

// Iniciar analytics - busca configurações automaticamente
await ExpoAnalytics.start({
  apiHost: 'http://localhost:8080',
  userId: 'user123',
  userData: {
    appVersion: '1.0.0',
    platform: 'iOS'
  }
});

// Opcionalmente, buscar configurações manualmente
const config = await ExpoAnalytics.fetchAppConfig('http://localhost:8080');
console.log('Configurações:', config);
// Output: { recordScreen: true, framerate: 15, screenSize: 480 }
```

## 🎯 Fluxo de Funcionamento

1. **App Inicia**: Ao chamar `start()`, o módulo nativo busca configurações pelo Bundle ID
2. **Configurações Aplicadas**: 
   - Se `recordScreen: false`, não inicia gravação
   - Se `recordScreen: true`, aplica framerate e screenSize configurados
3. **Override Local**: Parâmetros passados para `start()` podem sobrescrever configurações do servidor
4. **Dashboard**: Mostra dados apenas do app selecionado

## 📱 Interface do Dashboard

### Tela Principal - Gestão de Apps
- **Grid de Apps**: Cartões com informações e configurações resumidas
- **Ações Rápidas**: Configurar, deletar, ver analytics
- **Status Visual**: Indicadores claros de Record Screen ativo/inativo

### Tela de Analytics (por App)
- **Breadcrumb**: Navegação clara com botão "Voltar aos Apps"
- **Estatísticas Filtradas**: Dados específicos do app selecionado
- **Configuração Rápida**: Botão para ajustar configurações sem sair da página

## 🔧 Estrutura de Dados

### Arquivo de Configuração do App
```json
{
  "bundleId": "com.empresa.meuapp",
  "name": "Meu Aplicativo",
  "platform": "ios",
  "config": {
    "recordScreen": true,
    "framerate": 15,
    "screenSize": 480
  },
  "createdAt": 1640995200,
  "updatedAt": 1640995200
}
```

### Endpoint de Configurações
```
GET /app-config?bundleId=com.empresa.meuapp

Response:
{
  "success": true,
  "config": {
    "recordScreen": true,
    "framerate": 15,
    "screenSize": 480
  }
}
```

## 🔧 APIs Adicionadas

### Backend (PHP)
- `POST /apps` - Criar novo app
- `PUT /apps` - Atualizar configurações do app  
- `DELETE /apps` - Deletar app
- `GET /apps` - Listar todos os apps
- `GET /app-config` - Buscar configurações por Bundle ID

### Módulo Nativo (TypeScript/Swift)
- `fetchAppConfig(apiHost, bundleId?)` - Buscar configurações do servidor
- `start()` modificado para buscar configurações automaticamente
- Configurações aplicadas: recordScreen, framerate, screenSize

## 📊 Benefícios

### Para Desenvolvedores
- **Controle Remoto**: Ajustar configurações sem atualizar o app
- **A/B Testing**: Diferentes configurações para diferentes versões
- **Debug Remoto**: Ativar/desativar gravação para usuários específicos

### Para Performance
- **Otimização Dinâmica**: Ajustar qualidade baseado na capacidade do servidor
- **Controle de Banda**: Reduzir framerate em conexões lentas
- **Economia de Recursos**: Desativar gravação quando não necessário

### Para Analytics
- **Dados Organizados**: Separação clara por aplicativo
- **Escalabilidade**: Suporte a múltiplos projetos
- **Configuração Granular**: Controle fino sobre cada aspecto da coleta

## 🛠️ Instalação e Configuração

### 1. Iniciar o Backend
```bash
cd backend
php -S localhost:8080 api-receiver.php
```

### 2. Acessar Dashboard
```
http://localhost:8080/dashboard
```

### 3. Configurar App React Native
```typescript
// No seu App.tsx
import * as ExpoAnalytics from 'expo-analytics';

await ExpoAnalytics.start({
  apiHost: 'http://localhost:8080',
  userId: 'user123'
});
```

## 📂 Estrutura do Projeto

```
expo-analytics/
├── src/                     # Módulo TypeScript
├── ios/                     # Implementação Swift
├── android/                 # Implementação Kotlin  
├── example/                 # App demo
├── backend/                 # API PHP e Dashboard
│   ├── dashboard.php        # Interface de gestão
│   ├── api-receiver.php     # API endpoints
│   └── assets/              # CSS e JS
├── analytics-data/          # Dados coletados
│   └── apps/                # Configurações dos apps
└── README.md
```

## 🔍 Monitoramento

### Logs do Módulo Nativo
```bash
# iOS (XCode Console)
📱 [ExpoAnalytics] Bundle ID: com.empresa.meuapp
🔍 [ExpoAnalytics] Buscando configurações para: com.empresa.meuapp
✅ [ExpoAnalytics] Configurações recebidas: {"recordScreen":true,"framerate":15,"screenSize":480}
🔧 [ExpoAnalytics] Configurações aplicadas:
   Record Screen: true
   Framerate: 15 fps  
   Screen Size: 480x960
🎬 [ExpoAnalytics] Captura de tela iniciada com 15 fps
```

### Dashboard em Tempo Real
- Auto-refresh a cada 30 segundos
- Indicadores visuais de status
- Estatísticas filtradas por app

---

Sistema desenvolvido para fornecer analytics completos com controle total sobre a coleta de dados, performance otimizada e interface administrativa intuitiva. 