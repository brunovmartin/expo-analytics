# 📋 Guia Completo de Logs - Expo Analytics

## 🎯 **Problema Resolvido**

Os logs não apareciam nem no Metro nem no terminal PHP. Agora temos soluções específicas para cada tipo de log.

---

## 📱 **1. Logs Swift/iOS (Módulo Nativo)**

### **Por que NSLog não aparece no Metro?**
- `NSLog()` é específico do sistema iOS
- Metro do Expo só mostra `console.log` do JavaScript  
- Logs nativos precisam de ferramenta específica
- **IMPORTANTE**: `npx react-native log-ios` **NÃO funciona** em projetos Expo

### **✅ Soluções para Expo:**

#### **Opção 1: Console.app (Recomendado)**
```bash
./start-ios-logs-simple.sh
```
**Passos:**
1. Script abre Console.app automaticamente
2. Selecione seu dispositivo iOS ou Simulator
3. Digite "ExpoAnalytics" no filtro
4. Clique "Start streaming"
5. Execute seu app Expo

#### **Opção 2: Script Interativo**
```bash
./start-ios-logs.sh
```
**Opções disponíveis:**
- Console.app (mais confiável)
- Logs do Simulator via terminal
- Instalar React Native CLI (pode não funcionar)

#### **Opção 3: Manual via Console.app**
1. Abra: `/System/Applications/Utilities/Console.app`
2. Conecte dispositivo iOS ou abra Simulator
3. Selecione dispositivo na sidebar
4. Use filtro: `ExpoAnalytics`
5. Start streaming

### **📋 Logs que Você Verá:**
```
📸 [ExpoAnalytics] Screenshot: 480×960, 45KB
💾 [ExpoAnalytics] Frame 127 salvo: 45KB
📤 [ExpoAnalytics] Enviando buffer com 300 frames
🔄 [ExpoAnalytics] Iniciando processo de upload...
✅ [ExpoAnalytics] Upload concluído em 3.2s
🎉 [ExpoAnalytics] 300 imagens enviadas com sucesso!
```

### **❌ O que NÃO funciona em Expo:**
- `npx react-native log-ios` (projeto não é React Native CLI)
- Logs nativos no terminal do `npx expo start`
- Debugging direto no Metro para módulos nativos

---

## 🖥️ **2. Logs PHP (Backend)**

### **Por que não apareciam?**
- Logs PHP vão para `stderr` por padrão
- Precisam ser redirecionados para `stdout`

### **✅ Solução: Script Atualizado**

**Execute no backend:**
```bash
./start-server.sh
```

**O script agora inclui `2>&1` para capturar logs:**
```bash
php -S localhost:8080 -c php-config.ini api-receiver.php 2>&1
```

### **📋 Logs que Você Verá:**
```
PHP 8.2.0 Development Server started at Thu Jan 23 14:30:15 2025
Listening on localhost:8080
Document root is /path/to/backend
Press Ctrl-C to quit.

[Thu Jan 23 14:30:20 2025] 127.0.0.1:52341 POST /upload - 200
[Thu Jan 23 14:30:25 2025] 127.0.0.1:52342 POST /track - 200
[Thu Jan 23 14:30:30 2025] 127.0.0.1:52343 GET /dashboard - 200
```

---

## 🚀 **3. Setup Completo para Logs**

### **Terminal 1: Backend PHP**
```bash
cd backend
./start-server.sh
```

### **Terminal 2: Expo Metro + Logs JavaScript**
```bash
npx expo start
```
**Mostra:**
- Console.log do JavaScript
- Erros de JavaScript  
- Hot reload status
- Bundle information

### **Terminal 3: Logs iOS (Escolha uma opção)**

#### **Opção A: Console.app (Recomendado)**
```bash
./start-ios-logs-simple.sh
```

#### **Opção B: Script Interativo**
```bash
./start-ios-logs.sh
```

#### **Opção C: Manual**
- Abra Console.app manualmente
- Configure filtro "ExpoAnalytics"

---

## 🔍 **4. Tipos de Logs por Fonte**

### **📱 iOS Swift (Console.app ou Simulator)**
- Screenshots capturados
- Upload de dados  
- Erros nativos
- Compressão de dados
- Status de conexão

### **🖥️ PHP Backend (Terminal 1)**
- Requisições recebidas
- Uploads processados
- Erros de servidor
- Status de arquivos
- Dados salvos

### **⚛️ Expo Metro (Terminal 2)**
- JavaScript console.log
- Erros de JavaScript
- Hot reload
- Bundle status
- Expo CLI messages

---

## 🛠️ **5. Comandos de Debug Avançado**

### **Logs iOS para Expo:**
```bash
# Console.app (recomendado)
./start-ios-logs-simple.sh

# Script com opções
./start-ios-logs.sh

# Manual - abrir Console.app
open /System/Applications/Utilities/Console.app
```

### **Logs do Simulator (Expo):**
```bash
# Verificar simulators ativos
xcrun simctl list devices | grep Booted

# Logs do simulator (se disponível)
xcrun simctl spawn booted log stream --predicate 'process CONTAINS "Expo"'
```

### **Logs PHP com arquivo:**
```bash
php -S localhost:8080 api-receiver.php 2>&1 | tee server.log
```

### **Logs em tempo real:**
```bash
tail -f analytics-data/logs/$(date +%Y-%m-%d).log
```

---

## ❗ **6. Troubleshooting**

### **Logs iOS não aparecem:**
```bash
# 1. Verificar se dispositivo/simulator está conectado
xcrun simctl list devices | grep Booted

# 2. Verificar se app Expo está rodando
# Procure por processo Expo no Activity Monitor

# 3. Usar Console.app diretamente
open /System/Applications/Utilities/Console.app

# 4. Para projetos Expo, NÃO use:
# npx react-native log-ios  ❌ (não funciona)
```

### **Logs PHP não aparecem:**
```bash
# 1. Verificar se PHP está funcionando
php --version

# 2. Testar servidor simples
php -S localhost:8080 -t .

# 3. Verificar permissões
ls -la start-server.sh
chmod +x start-server.sh
```

### **Logs Metro não aparecem:**
```bash
# 1. Limpar cache do Metro
npx expo start --clear

# 2. Reiniciar Watchman (se instalado)
watchman watch-del-all

# 3. Verificar se console.log não foi removido
grep -r "console" src/
```

---

## 📊 **7. Exemplo de Sessão Completa**

### **Terminal 1 (Backend):**
```
🚀 Iniciando servidor PHP Analytics...
📡 URL: http://localhost:8080
✅ Usando configurações customizadas (php-config.ini)
🔥 Iniciando servidor com logs...

PHP 8.2.0 Development Server started at Thu Jan 23 14:30:15 2025
Listening on localhost:8080
[Thu Jan 23 14:30:20 2025] ::1:52341 POST /upload - 200
[Thu Jan 23 14:30:25 2025] ::1:52342 POST /track - 200
```

### **Terminal 2 (iOS Logs):**
```
📱 Iniciando monitor de logs iOS - Expo Analytics
🎯 Filtrando logs do ExpoAnalytics...

📸 [ExpoAnalytics] Screenshot: 480×960, 45KB
💾 [ExpoAnalytics] Frame 127 salvo: 45KB
📤 [ExpoAnalytics] Enviando buffer com 300 frames
✅ [ExpoAnalytics] Upload concluído em 3.2s
```

### **Terminal 3 (Metro):**
```
Starting Metro Bundler
Metro waiting on exp://192.168.1.100:19000
› Press a │ open Android
› Press i │ open iOS simulator
› Press w │ open web

› Press r │ reload app
› Press m │ toggle menu
› Press c │ clear cache
```

---

## ✅ **8. Checklist de Verificação**

- [ ] **Backend rodando** - Terminal 1 ativo
- [ ] **Logs iOS capturando** - Terminal 2 filtrando
- [ ] **Metro funcionando** - Terminal 3 servindo  
- [ ] **App conectado** - Dispositivo/Simulator
- [ ] **Logs aparecendo** - Mensagens nos terminais

---

## 🎉 **Resultado Final**

**Agora você tem visibilidade completa:**

1. **📱 Logs Swift** - Funcionamento interno do módulo
2. **🖥️ Logs PHP** - Requisições e processamento  
3. **⚛️ Logs Metro** - Interface e JavaScript
4. **📊 Dashboard** - Visualização dos dados

**Logs funcionando 100%! 🚀** 