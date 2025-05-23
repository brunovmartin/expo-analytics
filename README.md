# 📊 Expo Analytics

Sistema completo de analytics para apps React Native/Expo com dashboard interativo e reprodução de sessões.

## 🏗️ Estrutura do Projeto

```
expo-analytics/
├── 📱 Frontend (Expo Module)
│   ├── src/                    # Código TypeScript do módulo
│   ├── ios/                    # Código Swift (iOS)
│   ├── android/                # Código Kotlin (Android)
│   └── example/                # App de exemplo
│
└── 🖥️ Backend (Dashboard & API)
    ├── api-receiver.php        # API principal
    ├── dashboard.php           # Dashboard interativo
    ├── view-screenshot.php     # Servidor de imagens
    ├── session-data.php        # API de dados de sessão
    ├── assets/                 # CSS, JS, recursos
    ├── test-*.php             # Scripts de teste
    ├── *.md                   # Documentação do backend
    └── analytics-data/         # 📊 Dados coletados (auto-criado)
        ├── screenshots/        # Imagens por usuário/data
        ├── events/            # Eventos em JSONL
        ├── users/             # Informações dos usuários
        └── logs/              # Logs da API
```

## 🚀 Como usar

### 1. **Iniciar o Backend (Dashboard)**

```bash
# Da raiz do projeto
./start-backend.sh

# Ou manualmente
cd backend
./start-server.sh
```

### 2. **Acessar o Dashboard**

```
http://localhost:8080/dashboard
```

### 3. **Configurar o App**

No `example/App.tsx`, o app já está configurado para:
```typescript
apiHost: 'http://localhost:8080'
```

### 4. **Testar o Sistema**

```bash
# Testar API
cd backend
php test-api.php

# Testar Dashboard
php test-dashboard.php
```

## ✨ Funcionalidades

### 📱 **Módulo Expo (Frontend)**
- **Captura de screenshots** automática em 30 FPS
- **Rastreamento de eventos** customizáveis
- **Informações do usuário** e geolocalização
- **Upload automático** quando app vai para background
- **Multiplataforma** (iOS, Android)

### 🎛️ **Dashboard (Backend)**
- **Estatísticas em tempo real** - Usuários, sessões, eventos
- **Player de vídeo** - Reproduz sessões como vídeo
- **Interface moderna** - Glass morphism, responsiva
- **Controles avançados** - Play/pause, velocidade, timeline
- **Auto-refresh** - Dados atualizados automaticamente

### 🔧 **API & Infraestrutura**
- **Endpoints RESTful** - `/upload`, `/track`, `/init`
- **Segurança** - Validação, sanitização, cache
- **Performance** - Cache de imagens, compressão
- **Logs completos** - Todas as requisições logadas

## 📊 Dados Coletados

### **Por Sessão:**
- **Screenshots** em intervalos regulares (30 FPS)
- **Metadados** - timestamp, user data, geo
- **Eventos customizados** - ações do usuário

### **Por Usuário:**
- **Informações** - ID, versão do app, dados customizados
- **Geolocalização** - país, cidade, IP
- **Atividade** - última vez online, sessões

### **Estatísticas:**
- **Usuários únicos**
- **Total de sessões**
- **Screenshots capturados**
- **Eventos rastreados**

## 🎮 Player de Vídeo

### **Funcionalidades:**
- **▶️ Reprodução** fluida de screenshots
- **🎚️ Timeline** interativa para navegação
- **⚡ Velocidades** 0.5x, 1x, 1.5x, 2x, 4x
- **📊 Metadados** exibidos em painel lateral
- **⌨️ Atalhos** ESC para fechar, clique fora

### **Controles:**
- **Play/Pause** - Reproduzir ou pausar
- **Seek** - Navegar para qualquer momento
- **Speed** - Alterar velocidade de reprodução
- **Timeline** - Barra de progresso interativa

## 🛠️ Configuração para Produção

### **Para dispositivos físicos:**
1. **Descubra seu IP:**
   ```bash
   ifconfig | grep "inet " | grep -v 127.0.0.1
   ```

2. **Inicie servidor público:**
   ```bash
   cd backend
   php -S 0.0.0.0:8080 api-receiver.php
   ```

3. **Configure no app:**
   ```typescript
   apiHost: 'http://192.168.1.100:8080'
   ```

### **Segurança recomendada:**
- Use HTTPS em produção
- Implemente autenticação
- Configure rate limiting
- Use banco de dados real

## 📱 Desenvolvimento do App

### **Instalar dependências:**
```bash
cd example
npm install
```

### **Executar no iOS:**
```bash
npx expo run:ios
```

### **Executar no Android:**
```bash
npx expo run:android
```

## 🎨 Personalização

### **Modificar captura:**
- **FPS:** Altere `framerate` nas opções
- **Qualidade:** Modifique `compressionQuality` no Swift
- **Frequência de upload:** Ajuste `frameCount >= 300`

### **Customizar dashboard:**
- **Cores:** Edite `assets/style.css` variáveis CSS
- **Layout:** Modifique `dashboard.php`
- **Funcionalidades:** Estenda `assets/script.js`

## 🚨 Troubleshooting

### **Backend não inicia:**
```bash
# Verificar PHP
php --version

# Verificar porta
lsof -i :8080

# Reiniciar servidor
./start-backend.sh
```

### **App não conecta:**
```bash
# Testar conectividade
curl http://localhost:8080/status

# Verificar firewall/rede
ping localhost
```

### **Dashboard em branco:**
```bash
# Verificar permissões
chmod -R 755 backend/
chmod -R 755 backend/analytics-data/

# Verificar logs PHP
tail -f /var/log/php_errors.log
```

## 📚 Documentação Adicional

- **[📡 README-API.md](backend/README-API.md)** - Documentação da API
- **[📊 README-Dashboard.md](backend/README-Dashboard.md)** - Guia do Dashboard

## 🎯 Próximas Funcionalidades

- [ ] **Autenticação** - Login e controle de acesso
- [ ] **Banco de dados** - PostgreSQL/MySQL
- [ ] **Métricas avançadas** - Heatmaps, funnels
- [ ] **Alertas** - Notificações em tempo real
- [ ] **Exportação** - CSV, PDF, vídeos
- [ ] **Multi-tenant** - Múltiplos projetos

---

## 🎉 Pronto para usar!

1. **Execute:** `./start-backend.sh`
2. **Acesse:** `http://localhost:8080/dashboard`
3. **Use o app** para gerar dados
4. **Explore** as sessões no dashboard

**Sistema completo de analytics funcionando! 🚀✨** 