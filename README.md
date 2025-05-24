# Expo Analytics

<div align="center">

![Expo Analytics](https://img.shields.io/badge/Expo-Analytics-000020?style=for-the-badge&logo=expo)
![Version](https://img.shields.io/badge/version-0.1.0-blue?style=for-the-badge)
![React Native](https://img.shields.io/badge/React_Native-0.79.2-61DAFB?style=for-the-badge&logo=react)
![Expo](https://img.shields.io/badge/Expo-53.0.9-000020?style=for-the-badge&logo=expo)
![License](https://img.shields.io/badge/license-MIT-green?style=for-the-badge)

**Sistema completo de analytics para aplicações React Native/Expo com captura de screenshots, gravação de sessões e dashboard web.**

[🚀 Quick Start](#-quick-start) • 
[📦 Instalação](#-instalação) • 
[📖 Documentação](#-documentação) • 
[🎯 Funcionalidades](#-funcionalidades) • 
[💻 Demo](#-demo)

</div>

---

## 🚀 Quick Start

### ⚡ Início Rápido em 3 Passos

#### 1. **Iniciar Backend**
```bash
./start-backend.sh
```

#### 2. **Acessar Dashboard**
```
http://localhost:8080/dashboard
```

#### 3. **Executar App Exemplo**
```bash
cd example
npx expo run:ios
```

### 📁 Estrutura do Projeto

```
📦 expo-analytics/
├── 📱 src/                     # Módulo Expo (TypeScript)
├── 📱 example/                 # App exemplo com testes
├── 🖥️ backend/                 # Dashboard PHP & API  
│   ├── 📊 analytics-data/      # Dados persistidos
│   ├── 🎨 assets/              # Assets do dashboard
│   └── 🧪 tests/               # Scripts de teste organizados
├── 📱 ios/                     # Código nativo iOS
├── 📱 android/                 # Código nativo Android
└── 📄 docs/                    # Documentação completa
```

---

## 🎯 Funcionalidades

### ✨ Funcionalidades Principais

| Funcionalidade | Descrição | Status |
|---|---|---|
| **📸 Screenshots Manuais** | Captura com parâmetros customizáveis (largura, altura, compressão) | ✅ |
| **🚨 Captura de Alertas** | Screenshots incluem alertas, dialogs e overlays | ✅ |
| **📱 Informações do Dispositivo** | Resolução, idioma, país, versão do SO | ✅ |
| **🛡️ Persistência iOS Antigo** | Zero perda de dados (iOS 10+ até iOS 17+) | ✅ |
| **🎬 Gravação de Sessões** | Converte screenshots em vídeos MP4 | ✅ |
| **📊 Dashboard Web** | Interface completa para visualizar dados | ✅ |
| **🌍 Geolocalização** | Dados geográficos automáticos via IP | ✅ |
| **⚙️ Configuração Dinâmica** | Configurações remotas por Bundle ID | ✅ |
| **🔄 Auto-cadastro** | Usuários cadastrados automaticamente | ✅ |

### 🆕 Últimas Implementações

#### **📸 Captura Avançada de Screenshots**
- ✅ **Alertas incluídos**: Captura todas as janelas visíveis
- ✅ **Qualidade customizável**: Controle de compressão e resolução
- ✅ **Upload automático**: Screenshots enviados direto para o dashboard

#### **🎨 Dashboard Moderno**
- ✅ **Interface responsiva**: Layout grid 2x2 com overlay 80%
- ✅ **Timeline vertical**: Linha do tempo com conectores visuais
- ✅ **Vídeos compactos**: Prévia automática aos 50% do tempo
- ✅ **Gestão de apps**: Múltiplos aplicativos com configurações individuais

#### **🛡️ Sistema de Persistência Robusto**
- ✅ **Compatibilidade total**: iOS 10+ até iOS 17+
- ✅ **Salvamento contínuo**: Dados persistidos a cada 2 segundos
- ✅ **Recuperação automática**: Sessões restauradas após reinicialização
- ✅ **Captura de terminação**: Handler para fechamento abrupto

---

## 📦 Instalação

### Pré-requisitos

- **Node.js** 16+ 
- **PHP** 7.4+ (para backend)
- **Expo CLI** `npm install -g @expo/cli`
- **iOS Simulator** ou **Device** físico

### Instalação do Módulo

```bash
# NPM
npm install expo-analytics

# Yarn
yarn add expo-analytics

# PNPM  
pnpm add expo-analytics
```

### Setup do Projeto Completo

```bash
# 1. Clonar repositório
git clone https://github.com/brunovmartin/expo-analytics.git
cd expo-analytics

# 2. Instalar dependências
npm install

# 3. Setup do exemplo
cd example && npm install

# 4. Iniciar backend
cd ../backend
php -S localhost:8080 api-receiver.php

# 5. Executar app exemplo (novo terminal)
cd ../example
npx expo run:ios
```

---

## 🛠️ Configuração

### 1. Inicialização Básica

```typescript
import * as ExpoAnalytics from 'expo-analytics';

// Configuração mínima
await ExpoAnalytics.init({
  userId: 'user-123',
  apiHost: 'http://localhost:8080',
  userData: {
    appVersion: '1.0.0',
    userType: 'premium'
  }
});
```

### 2. Configuração Avançada

```typescript
// Configuração completa com sessões
await ExpoAnalytics.init({
  userId: 'user-' + Date.now(),
  apiHost: 'http://192.168.1.100:8080', // IP para dispositivo físico
  userData: {
    appVersion: '1.0.0',
    userType: 'premium',
    subscription: 'monthly'
  }
});

// Iniciar gravação de sessões
await ExpoAnalytics.start({
  framerate: 10,      // FPS da gravação (1-30)
  screenSize: 480     // Resolução dos screenshots (320-960)
});
```

### 3. Configuração para Produção

```typescript
// Configuração otimizada para produção
await ExpoAnalytics.init({
  userId: await getStoredUserId(), // Função para recuperar userId persistido
  apiHost: 'https://seu-servidor.com',
  userData: {
    appVersion: Constants.expoConfig?.version,
    buildNumber: Constants.expoConfig?.ios?.buildNumber,
    environment: 'production'
  }
});
```

---

## 🎮 API Completa

### Métodos Principais

```typescript
// 1. 🔧 Inicialização (obrigatório)
await ExpoAnalytics.init(options);

// 2. 🎬 Controle de sessões
await ExpoAnalytics.start(options);
await ExpoAnalytics.stop();

// 3. 📊 Rastreamento de eventos
await ExpoAnalytics.trackEvent(event, value);

// 4. 📸 Screenshots manuais
await ExpoAnalytics.takeScreenshot(width?, height?, compression?);

// 5. 🚨 Teste de captura de alertas
await ExpoAnalytics.testAlertCapture(title, message);

// 6. 👤 Dados do usuário
await ExpoAnalytics.updateUserInfo(userData);

// 7. ⚙️ Configurações
const config = await ExpoAnalytics.fetchAppConfig(apiHost, bundleId);
```

### Exemplos de Uso

#### 📸 Screenshots Customizados

```typescript
// Screenshot de alta qualidade
const hd = await ExpoAnalytics.takeScreenshot(1080, 1920, 0.9);

// Screenshot compacto para economizar dados
const compact = await ExpoAnalytics.takeScreenshot(320, 640, 0.6);

// Screenshot com alertas
Alert.alert("Importante", "Esta mensagem será capturada!");
setTimeout(async () => {
  const result = await ExpoAnalytics.takeScreenshot(640, 1280, 0.8);
  console.log('Screenshot com alerta:', result);
}, 1000);
```

#### 📊 Rastreamento de Eventos

```typescript
// E-commerce
await ExpoAnalytics.trackEvent('product_view', 'product_123');
await ExpoAnalytics.trackEvent('add_to_cart', 'product_123');
await ExpoAnalytics.trackEvent('purchase', 'order_456');

// Jogos
await ExpoAnalytics.trackEvent('level_complete', 'level_5');
await ExpoAnalytics.trackEvent('achievement_unlock', 'first_win');
await ExpoAnalytics.trackEvent('game_over', 'score_1250');

// Formulários
await ExpoAnalytics.trackEvent('form_start', 'contact_form');
await ExpoAnalytics.trackEvent('field_filled', 'email');
await ExpoAnalytics.trackEvent('form_submit', 'contact_form');
```

### Tipos TypeScript

```typescript
interface InitOptions {
  userId: string;
  apiHost: string;
  userData?: Record<string, any>;
}

interface StartOptions {
  framerate?: number;    // 1-30 FPS
  screenSize?: number;   // 320-960 pixels
}

interface TakeScreenshotResult {
  success: boolean;
  message?: string;
  width?: number;
  height?: number;
  size?: number;
  error?: string;
}
```

---

## 🌐 Backend & Dashboard

### Inicialização do Servidor

```bash
# Método 1: Script automático
./start-backend.sh

# Método 2: Manual
cd backend
php -S localhost:8080 api-receiver.php

# Método 3: Servidor público (dispositivo físico)
php -S 0.0.0.0:8080 api-receiver.php
```

### URLs Importantes

| Função | URL |
|---|---|
| **📊 Dashboard Principal** | http://localhost:8080/dashboard |
| **📈 Status do Sistema** | http://localhost:8080/status |
| **🏠 Página Inicial** | http://localhost:8080/ |
| **🔧 Diagnóstico** | http://localhost:8080/diagnostico-sistema.php |
| **🗑️ Limpar Dados** | http://localhost:8080/limpar-dados.php |

### API Endpoints

```bash
# Inicialização de usuário
POST /init

# Rastreamento de eventos  
POST /track

# Upload de screenshots
POST /take-screenshot

# Upload de sessões
POST /upload-zip

# Configurações de apps
GET /apps/{bundleId}/config
POST /apps
```

### Gestão de Aplicativos

```bash
# Cadastrar novo app
curl -X POST http://localhost:8080/apps \
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

---

## 📱 App de Testes

### Interface de Testes Integrada

O projeto inclui uma **App.tsx** completa com navegação entre telas de teste:

```bash
# Executar app de testes
cd example
npx expo run:ios
```

### Telas Disponíveis

| Tela | Funcionalidade | Testes |
|---|---|---|
| **🏠 Principal** | Navegação e ações rápidas | Screenshots, alertas |
| **🚨 Alertas** | `AlertCaptureExample` | Captura automática e manual |
| **🎭 UI Completa** | `ComprehensiveUITestExample` | Modais, ActionSheets, teclado |

### Guia de Teste Rápido

```typescript
// 1. ✅ Verificação básica
// Aguarde: "✅ Inicializado"
// Clique: "📷 Screenshot Rápido"
// Verifique: Dashboard em http://localhost:8080/dashboard

// 2. 🚨 Teste de alertas
// Clique: "⚠️ Mostrar Alert"
// Clique: "📷 Screenshot Rápido" (com alert aberto)
// Resultado: Alert deve aparecer no screenshot

// 3. 🎭 Testes avançados
// Navegue entre as telas usando os botões
// Teste cada funcionalidade
// Verifique resultados no dashboard
```

---

## 📚 Documentação Técnica Completa

### 📖 API Backend

O backend PHP é responsável por receber e processar todos os dados enviados pelo módulo Expo Analytics.

#### 🚀 Inicialização da API

```bash
# Método 1: Script automático
./start-backend.sh

# Método 2: Manual  
cd backend
php -S localhost:8080 api-receiver.php

# Método 3: Servidor público (dispositivo físico)
php -S 0.0.0.0:8080 api-receiver.php
```

#### 📡 Endpoints Disponíveis

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| **POST** | `/init` | Inicialização e cadastro de usuário |
| **POST** | `/track` | Rastreamento de eventos |
| **POST** | `/take-screenshot` | Upload de screenshots manuais |
| **POST** | `/upload-zip` | Upload de sessões em ZIP |
| **POST** | `/delete-user` | Deletar dados de usuário |
| **GET** | `/status` | Status da API e estatísticas |
| **GET** | `/apps/{bundleId}/config` | Configurações do app |

#### 📁 Estrutura dos Dados Salvos

```
analytics-data/
├── screenshots/          # Screenshots organizados
│   └── [userId]/
│       └── [date]/
│           ├── screenshot_[timestamp]_000.jpg
│           ├── screenshot_[timestamp]_001.jpg
│           └── metadata_[timestamp].json
├── events/              # Eventos em formato JSONL
│   └── [userId]/
│       └── [date]/
│           └── events_[hour].jsonl
├── users/               # Informações dos usuários
│   └── [userId]/
│       ├── info_[datetime].json
│       └── latest.json
├── videos/              # Sessões convertidas em MP4
│   └── [userId]/
│       └── session_[timestamp].mp4
└── logs/                # Logs da API
    └── [date].log
```

#### 🔒 Validações de Segurança

- **Path traversal protection** - Sanitização de parâmetros
- **Tipo de arquivo** - Apenas JPG/PNG permitidos  
- **Formato de data** - Validação YYYY-MM-DD
- **Caracteres permitidos** - Alphanumeros e símbolos específicos
- **Content-Type** correto para cada tipo de arquivo
- **CORS** habilitado para desenvolvimento

### 📊 Dashboard Web Interativo

Dashboard moderno para visualizar dados de analytics e reproduzir sessões de usuário.

#### 🎯 Funcionalidades do Dashboard

##### 📈 **Visão Geral**
- **Estatísticas em tempo real** - Usuários, sessões, screenshots e eventos
- **Lista de usuários recentes** - Ordenados por último acesso
- **Interface responsiva** - Funciona em desktop e mobile
- **Auto-refresh** das estatísticas a cada 30 segundos

##### 🎬 **Player de Sessão**
- **Reprodução de screenshots** como vídeo
- **Controles de reprodução** - Play/pause, seek, velocidade  
- **Múltiplas velocidades** - 0.5x, 1x, 1.5x, 2x, 4x
- **Informações da sessão** - Metadados e dados do usuário
- **Timeline interativa** - Navegação frame a frame

##### 👤 **Painel de Dados do Usuário**
- **Layout 2 colunas** com informações detalhadas:
  - **Identificação**: User ID, primeiro/último acesso
  - **Estatísticas**: Total de sessões, screenshots, eventos
  - **Dados do App**: Versão, dispositivo, OS, dados customizados
  - **Localização**: País, estado, cidade, timezone, IP
- **Lista dedicada de sessões** com thumbnails e controles
- **Botão para deletar dados** com confirmação de segurança

#### 🎨 Design System

- **Paleta de Cores**:
  - Primária: Gradiente roxo/azul (#667eea → #764ba2)
  - Secundária: Rosa/vermelho (#f093fb → #f5576c)
  - Sucesso: Azul/ciano (#4facfe → #00f2fe)
- **Glass morphism** - Transparências e blur
- **Animações suaves** - Transições de 0.3s
- **Ícones Font Awesome** - Interface consistente

#### 🔧 Configurações do Dashboard

```javascript
// Player de Vídeo
this.frameRate = 2;  // FPS padrão
this.speeds = [0.5, 1, 1.5, 2, 4];  // Velocidades disponíveis

// Auto-refresh (30 segundos)
setInterval(autoRefresh, 30000);

// Cache de Imagens (1 hora)
header('Cache-Control: public, max-age=3600');
```

### 🏗️ Arquitetura do Backend

#### 📁 Organização dos Arquivos

```
📦 backend/                 # Backend centralizado
├── 📡 API & Servidor
│   ├── api-receiver.php    # API principal (roteamento)
│   ├── dashboard.php       # Dashboard interativo
│   ├── view-screenshot.php # Servidor de imagens
│   ├── session-data.php    # API de dados de sessão
│   ├── event-screenshot.php # Servidor de screenshots de eventos
│   ├── view-video.php      # Servidor de vídeos
│   ├── limpar-dados.php    # Utilitário para limpeza
│   └── diagnostico-sistema.php # Diagnóstico do sistema
│
├── 🎨 Interface
│   ├── index.html          # Página inicial com status
│   └── assets/             # CSS, JS, recursos
│       ├── style.css       # Estilos do dashboard
│       └── script.js       # JavaScript do player
│
├── 🧪 Testes Organizados
│   ├── start-server.sh     # Script para iniciar servidor
│   └── tests/              # Scripts de teste
│       ├── test-api.php        # Teste da API
│       ├── test-dashboard.php  # Teste do dashboard
│       ├── test-new-features.php # Teste das novas funcionalidades
│       ├── test-image-size.php # Teste de tamanho de imagens
│       ├── test-logs.php       # Teste do sistema de logs
│       ├── test-modal.html     # Teste de modais
│       └── testar-sessoes.php  # Teste de sessões
│
└── 📊 analytics-data/      # Dados organizados
    ├── screenshots/        # Imagens por usuário/data
    ├── events/            # Eventos rastreados
    ├── users/             # Informações dos usuários
    ├── videos/            # Sessões em MP4
    └── logs/              # Logs da API
```

#### ✅ Vantagens da Arquitetura

- **Backend Centralizado**: Tudo em um lugar - API, dashboard, dados e assets
- **Fácil deployment**: Uma pasta contém todo o backend
- **Isolamento**: Frontend (Expo) e backend (PHP) completamente separados
- **Portabilidade**: Backend pode ser movido independentemente
- **Estrutura Profissional**: Organização escalável e fácil manutenção

### 🚀 Melhorias Técnicas Implementadas

#### ✅ **Sistema de Logs Melhorado**
- **Logs Swift visíveis no Metro** - NSLog() com prefixo [ExpoAnalytics]
- **Script dedicado** - `./start-ios-logs.sh` para capturar logs iOS  
- **Logs estruturados** com timestamps e categorias
- **Debug em tempo real** durante desenvolvimento

#### ✅ **Gestão de Dados de Usuário**
- **Botão para deletar dados** com confirmação obrigatória
- **Segurança rigorosa** - digitação de "DELETAR" para confirmar
- **Remoção completa** - screenshots, eventos, informações pessoais
- **Feedback visual** com loading states e notificações

#### ✅ **Persistência Robusta iOS**
- **Compatibilidade total** - iOS 10+ até iOS 17+
- **Salvamento contínuo** - Dados persistidos a cada 2 segundos  
- **Recuperação automática** - Sessões restauradas após reinicialização
- **Captura de terminação** - Handler para fechamento abrupto
- **Zero perda de dados** - 100% dos dados preservados

#### ✅ **Otimizações de Performance**
- **Sistema de Throttling** - Controle preciso do intervalo entre capturas
- **Captura em Background** - Screenshots processados em thread separada
- **Limite de FPS** - Máximo de 15fps para evitar sobrecarga  
- **Compactação ZIP** - Imagens agrupadas para economizar banda
- **Geração de MP4** - Backend converte ZIP em vídeo comprimido

#### ✅ **Interface Moderna**
- **Layout responsivo** - Grid 2x2 com overlay 80%
- **Timeline vertical** - Linha do tempo com conectores visuais
- **Vídeos compactos** - Prévia automática aos 50% do tempo
- **Gestão de apps** - Múltiplos aplicativos com configurações individuais

### 🧪 Sistema de Testes

#### Scripts de Teste Automatizados

```bash
# Teste completo da API
cd backend && php tests/test-api.php

# Teste do dashboard  
cd backend && php tests/test-dashboard.php

# Teste das novas funcionalidades
cd backend && php tests/test-new-features.php

# Teste de tamanho de imagens
cd backend && php tests/test-image-size.php

# Teste do sistema de logs
cd backend && php tests/test-logs.php

# Teste de sessões
cd backend && php tests/testar-sessoes.php
```

#### Resultado dos Testes

```
✅ Status da API               PASS
✅ Envio de dados do usuário    PASS  
✅ Dashboard com usuário        PASS
✅ Painel de detalhes          PASS
✅ Botão de deletar            PASS
✅ Endpoint de deletar         PASS
✅ Assets CSS/JS               PASS
✅ Player de vídeo             PASS
✅ Servidor de imagens         PASS
```

---

## 🔧 Solução de Problemas

### Problemas Comuns

#### ❌ Servidor não inicia
```bash
# Verificar porta ocupada
lsof -i :8080

# Matar processo
kill -9 $(lsof -t -i:8080)

# Reiniciar
./start-backend.sh
```

#### ❌ App não conecta
```bash
# Testar conectividade
curl http://localhost:8080/status

# Verificar IP (dispositivo físico)
ifconfig | grep "inet " | grep -v 127.0.0.1
```

#### ❌ Screenshots não enviados
```typescript
// Verificar resultado
const result = await ExpoAnalytics.takeScreenshot();
if (!result.success) {
  console.log('Erro:', result.error);
}

// Verificar dashboard
// http://localhost:8080/dashboard -> aba Screenshots
```

#### ❌ Dados não persistem
```bash
# Verificar logs do servidor
tail -f backend/analytics-data/logs/$(date +%Y-%m-%d).log

# Verificar permissões
ls -la backend/analytics-data/
```

### Debug Avançado

```typescript
// Ativar logs detalhados
await ExpoAnalytics.init({
  // ... outras opções
  debug: true  // Ativa logs detalhados
});

// Verificar configuração atual
const config = await ExpoAnalytics.getConfig();
console.log('Configuração atual:', config);
```

---

## 🚧 Roadmap

### 🔜 Próximas Funcionalidades

- [ ] **📹 Gravação de vídeo nativa** - Gravação direta em MP4
- [ ] **🔄 Sincronização offline** - Cache local com sync automático
- [ ] **📈 Analytics em tempo real** - WebSockets para dados live
- [ ] **🎨 Customização de UI** - Temas personalizáveis no dashboard
- [ ] **🔔 Notificações automáticas** - Alertas via email/webhook
- [ ] **📱 App móvel do dashboard** - Dashboard nativo iOS/Android
- [ ] **🧪 Testes A/B integrados** - Framework de experimentação
- [ ] **🔒 Autenticação avançada** - OAuth, JWT, roles

### 🎯 Versões Futuras

#### v0.2.0 - Analytics Avançado
- Funis de conversão
- Cohort analysis  
- Segmentação de usuários
- Métricas customizadas

#### v0.3.0 - Performance & Scale
- Otimização para alta escala
- Clustering de dados
- CDN para assets
- Compressão avançada

#### v1.0.0 - Produção Ready
- Documentação completa
- Testes automatizados
- CI/CD pipeline
- Monitoramento integrado

---

## 🤝 Contribuição

### Como Contribuir

1. **Fork** o repositório
2. **Clone** seu fork localmente
3. **Crie** uma branch para sua feature
4. **Implemente** suas mudanças
5. **Teste** completamente
6. **Submeta** um Pull Request

```bash
# Setup para contribuição
git clone https://github.com/seu-usuario/expo-analytics.git
cd expo-analytics
npm install
cd example && npm install
```

### Diretrizes de Contribuição

- **📝 Documentação**: Atualize README e docs relevantes
- **🧪 Testes**: Adicione testes para novas funcionalidades
- **📏 Linting**: Execute `npm run lint` antes do commit
- **💬 Commit**: Use conventional commits (feat, fix, docs, etc.)

### Reportar Bugs

Abra uma [issue](https://github.com/brunovmartin/expo-analytics/issues) com:

- **📱 Ambiente**: iOS/Android, versão do Expo, versão do RN
- **🔄 Reprodução**: Passos para reproduzir o bug
- **📄 Logs**: Logs relevantes do console
- **📸 Screenshots**: Se aplicável

---

## 📄 Licença

Este projeto está licenciado sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para detalhes completos.

```
MIT License

Copyright (c) 2024 Bruno Verçosa

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:
```

---

## 👨‍💻 Autor

**Bruno Verçosa**
- **GitHub**: [@brunovmartin](https://github.com/brunovmartin)
- **Email**: bruno.vmartins@hotmail.com
- **LinkedIn**: [Bruno Verçosa](https://linkedin.com/in/bruno-versosa)

---

## 🙏 Agradecimentos

- **Expo Team** - Pela excelente plataforma de desenvolvimento
- **React Native Community** - Pelas bibliotecas e ferramentas
- **IP-API** - Pelo serviço de geolocalização
- **FFmpeg** - Pela conversão de vídeos
- **Contributors** - A todos que contribuíram para este projeto

---

<div align="center">

**⭐ Se este projeto foi útil, considere dar uma estrela!**

**🚀 Sistema 100% funcional e pronto para uso!** 

*Todas as funcionalidades foram implementadas com sucesso, incluindo captura de screenshots com alertas, interface moderna, persistência robusta e dashboard completo.*

---

[![Stars](https://img.shields.io/github/stars/brunovmartin/expo-analytics?style=social)](https://github.com/brunovmartin/expo-analytics/stargazers)
[![Forks](https://img.shields.io/github/forks/brunovmartin/expo-analytics?style=social)](https://github.com/brunovmartin/expo-analytics/network/members)
[![Issues](https://img.shields.io/github/issues/brunovmartin/expo-analytics)](https://github.com/brunovmartin/expo-analytics/issues)
[![Pull Requests](https://img.shields.io/github/issues-pr/brunovmartin/expo-analytics)](https://github.com/brunovmartin/expo-analytics/pulls)

</div>