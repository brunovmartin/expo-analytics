# Alteração da API - init() em vez de registerUser()

## 📋 Resumo da Mudança

A API foi simplificada para usar uma função `init()` que **automaticamente** cadastra o usuário internamente, removendo a necessidade de chamar `registerUser()` manualmente.

## 🔄 Antes vs Depois

### ❌ API Anterior (Removida)
```typescript
// Duas funções separadas - mais complexo
await ExpoAnalytics.registerUser({
  userId: currentUserId,
  apiHost: 'http://localhost:8888',
  userData: { ... }
});

await ExpoAnalytics.start({ ... });
```

### ✅ Nova API (Simplificada)
```typescript
// Uma função que faz tudo automaticamente
await ExpoAnalytics.init({
  userId: currentUserId,
  apiHost: 'http://localhost:8888',
  userData: { ... }
});
// ↑ Usuário é cadastrado AUTOMATICAMENTE (interno/oculto)

await ExpoAnalytics.start({ ... });
```

## 🎯 Vantagens da Nova API

### 1. **Mais Simples**
- ✅ Apenas uma função `init()` 
- ✅ Cadastro de usuário **automático** e interno
- ✅ Menos código no app

### 2. **Mais Intuitiva**
- ✅ `init()` sugere inicialização do sistema
- ✅ Cadastro de usuário é transparente
- ✅ API mais limpa e focada

### 3. **Menos Propensa a Erros**
- ✅ Desenvolvedor não precisa lembrar de chamar `registerUser()`
- ✅ Cadastro sempre acontece automaticamente
- ✅ Impossível esquecer de registrar o usuário

## 🔧 Implementação Técnica

### iOS Module (Swift)
```swift
AsyncFunction("init") { (options: [String: Any]?) in
  NSLog("🚀 [ExpoAnalytics] Inicializando sistema...")
  
  // Configurar dados do usuário
  if let config = options {
    if let id = config["userId"] as? String { self.userId = id }
    if let host = config["apiHost"] as? String { self.apiHost = host }
    if let data = config["userData"] as? [String: Any] { self.userData = data }
  }

  // Adicionar informações automáticas do device
  self.userData["appVersion"] = self.getFormattedAppVersion()
  self.userData["bundleId"] = self.getBundleIdentifier()
  self.userData["platform"] = self.getIOSVersion()
  self.userData["device"] = self.getFormattedDeviceInfo()

  // CADASTRAR USUÁRIO AUTOMATICAMENTE (interno/oculto)
  NSLog("👤 [ExpoAnalytics] Cadastrando usuário automaticamente...")
  self.sendUserInfoPayload()

  NSLog("✅ [ExpoAnalytics] Sistema inicializado e usuário cadastrado!")
}
```

### TypeScript Definitions
```typescript
declare class ExpoAnalyticsModule extends NativeModule<ExpoAnalyticsModuleEvents> {
  /**
   * Inicializa o sistema e cadastra o usuário automaticamente
   */
  init(options?: StartOptions): Promise<void>;
  
  // registerUser() removido - não é mais público
}
```

### Exported Functions
```typescript
/**
 * Inicializa o sistema Expo Analytics e cadastra o usuário automaticamente
 * Esta função configura todas as informações necessárias e registra o usuário internamente
 */
export async function init(options?: StartOptions): Promise<void> {
  return ExpoAnalyticsModule.init(options);
}

// registerUser() removido das exportações
```

## 📊 Logs da Nova API

### Logs Esperados
```
🚀 Inicializando sistema Expo Analytics...
🚀 [ExpoAnalytics] Inicializando sistema...
👤 [ExpoAnalytics] Cadastrando usuário automaticamente...
📤 [ExpoAnalytics] Enviando dados do usuário para cadastro:
   User ID: user-1748017377383-oyjblq
   Platform: iOS 18.3.1
   Device: x86_64 (iOS Simulator)
   App Version: 1.0.0.(1)
✅ [ExpoAnalytics] Usuário cadastrado com sucesso no sistema!
✅ Sistema inicializado e usuário cadastrado!
```

## 🎨 Exemplo Completo de Uso

### App.tsx
```typescript
import * as ExpoAnalytics from 'expo-analytics';

const initializeUser = async () => {
  try {
    // 1. Gerar/recuperar user ID
    let storedUserId = await AsyncStorage.getItem('analytics_user_id');
    
    if (!storedUserId) {
      const timestamp = Date.now();
      const random = Math.random().toString(36).substring(2, 8);
      storedUserId = `user-${timestamp}-${random}`;
      await AsyncStorage.setItem('analytics_user_id', storedUserId);
    }
    
    // 2. Inicializar sistema (cadastro automático)
    console.log('🚀 Inicializando sistema Expo Analytics...');
    await ExpoAnalytics.init({
      userId: storedUserId,
      apiHost: 'http://localhost:8888',
      userData: {
        initializeMethod: 'automatic',
        initializedAt: new Date().toISOString()
      }
    });
    console.log('✅ Sistema inicializado e usuário cadastrado!');
    
    // 3. Opcionalmente iniciar captura de tela
    await ExpoAnalytics.start();
    
    return storedUserId;
  } catch (error) {
    console.error('❌ Erro ao inicializar:', error);
  }
};
```

## ✅ Resultado

- **Menos código** no app
- **API mais limpa** e intuitiva
- **Cadastro automático** transparente
- **Mesma funcionalidade** mantida
- **Zero breaking changes** para o usuário final

O sistema continua funcionando exatamente igual, mas com uma API mais simples e elegante! 