# 🚀 Logs Funcionando - Expo Analytics

## ⚠️ **Problema Identificado**
O comando `npx react-native log-ios` **NÃO funciona** em projetos Expo porque:
- Este é um projeto **Expo**, não React Native CLI
- Logs nativos iOS precisam de ferramentas específicas 
- Metro do Expo só mostra logs JavaScript

---

## ✅ **SOLUÇÃO RÁPIDA (3 Passos)**

### **1. Backend PHP (Terminal 1)**
```bash
cd backend
./start-server.sh
```
✅ **Resultado**: Logs PHP aparecerão (requisições, uploads, erros)

### **2. Logs iOS (Método Simples)**
```bash
./start-ios-logs-simple.sh
```
✅ **Resultado**: Console.app abre automaticamente
- Configure filtro: `ExpoAnalytics`
- Logs NSLog aparecerão em tempo real

### **3. Expo Metro (Terminal 2)**
```bash
npx expo start
```
✅ **Resultado**: Logs JavaScript aparecerão automaticamente

---

## 🎯 **O que você verá em cada terminal:**

### **📱 Console.app (Logs iOS)**
```
📸 [ExpoAnalytics] Screenshot: 480×960, 45KB
💾 [ExpoAnalytics] Frame 127 salvo: 45KB
📤 [ExpoAnalytics] Enviando buffer com 300 frames
✅ [ExpoAnalytics] Upload concluído em 3.2s
🎉 [ExpoAnalytics] 300 imagens enviadas com sucesso!
```

### **🖥️ Terminal Backend (Logs PHP)**
```
🚀 Iniciando servidor PHP Analytics...
PHP 8.2.0 Development Server started...
[timestamp] 127.0.0.1:port POST /upload - 200
[timestamp] 127.0.0.1:port POST /track - 200
[timestamp] 127.0.0.1:port GET /dashboard - 200
```

### **⚛️ Terminal Metro (Logs JavaScript)**
```
Starting Metro Bundler
› Press a │ open Android
› Press i │ open iOS simulator
› Press w │ open web

console.log("App iniciado");  // Seus logs JavaScript
console.warn("Aviso");        // Aparecem aqui
```

---

## 📋 **Scripts Disponíveis**

| Script | Função | Quando Usar |
|--------|--------|-------------|
| `./start-ios-logs-simple.sh` | ✅ Abre Console.app | **Recomendado** - sempre funciona |
| `./start-ios-logs.sh` | 🔧 Menu com opções | Quando quer escolher método |
| `./backend/start-server.sh` | 🖥️ Servidor PHP com logs | **Obrigatório** para backend |

---

## 🔧 **Configuração Console.app**

### **Primeira vez usando:**
1. Execute: `./start-ios-logs-simple.sh`
2. Console.app abre automaticamente
3. **No Console.app:**
   - Selecione seu dispositivo/simulator na sidebar esquerda
   - No campo "Search", digite: `ExpoAnalytics`
   - Clique em "Start" ou "Start streaming"
4. Execute seu app Expo
5. Logs aparecerão automaticamente!

### **Próximas vezes:**
- Apenas execute o script, Console.app lembra as configurações

---

## ❌ **O que NÃO fazer em projetos Expo:**

```bash
# ❌ ESTE COMANDO NÃO FUNCIONA:
npx react-native log-ios

# ❌ Motivo: Projeto é Expo, não React Native CLI
# ❌ Erro: "@react-native-community/cli" missing
```

## ✅ **O que fazer:**

```bash
# ✅ Use Console.app para logs iOS:
./start-ios-logs-simple.sh

# ✅ Use Metro para logs JavaScript:
npx expo start

# ✅ Use backend script para logs PHP:
cd backend && ./start-server.sh
```

---

## 🎉 **Teste Rápido**

1. **Execute os 3 comandos acima**
2. **Abra seu app Expo no simulator/device**
3. **Use o módulo Analytics**
4. **Veja logs em tempo real nos 3 lugares!**

**Logs funcionando 100%! 🚀** 