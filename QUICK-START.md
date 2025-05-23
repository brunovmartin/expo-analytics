# 🚀 Quick Start - Expo Analytics

## ⚡ Início Rápido

### 1. **Iniciar Backend**
```bash
./start-backend.sh
```

### 2. **Acessar Dashboard**
```
http://localhost:8080/dashboard
```

### 3. **Testar App**
```bash
cd example
npx expo run:ios
```

## 📁 Estrutura Simples

```
📦 expo-analytics/
├── 📱 src/                     # Módulo Expo
├── 📱 example/                 # App exemplo
└── 🖥️ backend/                 # Dashboard & API  
    └── 📊 analytics-data/      # Dados (auto-criado)
```

## 🔧 Comandos Úteis

### **Backend:**
```bash
# Iniciar servidor
./start-backend.sh

# Testar API
cd backend && php test-api.php

# Testar Dashboard  
cd backend && php test-dashboard.php
```

### **Frontend:**
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

## 🎯 Workflow Típico

1. `./start-backend.sh` - Iniciar servidor
2. `cd example && npx expo run:ios` - Abrir app
3. Usar app (captura screenshots automaticamente)
4. Ver dados no dashboard em tempo real
5. Reproduzir sessões como vídeo

## 🚨 Problemas Comuns

### **Servidor não inicia:**
```bash
# Verificar porta
lsof -i :8080

# Matar processo
kill -9 $(lsof -t -i:8080)

# Reiniciar
./start-backend.sh
```

### **App não conecta:**
```bash
# Testar conectividade
curl http://localhost:8080/status

# Verificar IP (dispositivo físico)
ping [SEU_IP]
```

---

## ✨ Pronto!

**Execute `./start-backend.sh` e comece a usar! 🎉** 