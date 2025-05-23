# Background Modes Configuration

Para que o módulo `ExpoAnalytics` funcione corretamente em background, você precisa configurar os **Background Modes** no seu app.

## 📋 Configuração Necessária

### 1. No `app.json` (Expo Managed Workflow):

```json
{
  "expo": {
    "ios": {
      "infoPlist": {
        "UIBackgroundModes": [
          "processing",
          "fetch",
          "background-app-refresh"
        ],
        "BGTaskSchedulerPermittedIdentifiers": [
          "com.expo.analytics.upload",
          "com.expo.analytics.sync"
        ]
      }
    }
  }
}
```

### 2. No `Info.plist` (Bare Workflow ou após eject):

```xml
<key>UIBackgroundModes</key>
<array>
    <string>processing</string>
    <string>fetch</string>
    <string>background-app-refresh</string>
</array>

<!-- OBRIGATÓRIO para iOS 13+ quando usando 'processing' -->
<key>BGTaskSchedulerPermittedIdentifiers</key>
<array>
    <string>com.expo.analytics.upload</string>
    <string>com.expo.analytics.sync</string>
</array>
```

## 🎯 O que cada modo permite:

### `processing`
- **Finalizar uploads** quando app vai para background
- **Salvar dados críticos** antes do app ser suspenso
- Tempo limite: ~30 segundos

### `fetch` 
- **Buscar configurações** do servidor periodicamente
- **Sincronizar dados** em background
- Execução periódica pelo sistema

### `background-app-refresh`
- **Atualizar dados** quando app não está ativo
- **Manter sincronização** com servidor
- Controlado pelo usuário nas configurações

## 🚨 **ERRO COMUM - App Store Connect:**

Se você receber este erro durante upload:

```
Missing Info.plist value. The Info.plist key 'BGTaskSchedulerPermittedIdentifiers' 
must contain a list of identifiers used to submit and handle tasks when 
'UIBackgroundModes' has a value of 'processing'.
```

**✅ SOLUÇÃO:** Adicione `BGTaskSchedulerPermittedIdentifiers` conforme mostrado acima.

### 🔄 **ALTERNATIVA - Configuração Mínima:**

Se você quiser evitar o `BGTaskScheduler`, pode usar apenas:

```json
{
  "expo": {
    "ios": {
      "infoPlist": {
        "UIBackgroundModes": [
          "fetch",
          "background-app-refresh"
        ]
      }
    }
  }
}
```

**⚠️ Limitações:** Tempo de background mais limitado, mas sem necessidade de `BGTaskSchedulerPermittedIdentifiers`.

## ⚠️ Importante:

1. **Background tasks são limitados** - o sistema pode encerrar a qualquer momento
2. **Usuário pode desabilitar** background refresh nas configurações
3. **Use com moderação** - impacta bateria do dispositivo
4. **iOS 13+ exige** `BGTaskSchedulerPermittedIdentifiers` para o modo `processing`

## 🔧 Implementação no Código:

O módulo já implementa automaticamente:

```swift
// Inicia background task antes de uploads críticos
self.startBackgroundTask()

// Finaliza task quando upload completa
defer {
    DispatchQueue.main.async { [weak self] in
        self?.endBackgroundTask()
    }
}
```

## 📱 Verificação:

Para testar se está funcionando:

1. **Ative o módulo** com recording habilitado
2. **Vá para background** (home button/gesture)  
3. **Verifique logs** - deve ver "Background task iniciado"
4. **Upload deve completar** mesmo em background

## 🚨 Sem Background Modes:

- ❌ **Uploads interrompidos** quando app vai para background
- ❌ **Perda de dados** de sessão
- ❌ **ZIPs não enviados** completamente
- ❌ **Analytics incompletos**

---

**✅ Com Background Modes configurados corretamente, o módulo garante que todos os dados sejam enviados com segurança, mesmo quando o app não está ativo.** 