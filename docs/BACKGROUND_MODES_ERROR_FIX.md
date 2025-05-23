# 🚨 ERRO: BGTaskSchedulerPermittedIdentifiers

## O Problema

Se você está recebendo este erro ao fazer upload para App Store Connect:

```
Missing Info.plist value. The Info.plist key 'BGTaskSchedulerPermittedIdentifiers' 
must contain a list of identifiers used to submit and handle tasks when 
'UIBackgroundModes' has a value of 'processing'.
```

## 🎯 A Causa

A partir do **iOS 13**, quando você usa o background mode `processing`, o Apple exige que você declare quais tarefas específicas seu app vai executar através da chave `BGTaskSchedulerPermittedIdentifiers`.

## ✅ SOLUÇÃO RÁPIDA

### Para Expo Managed Workflow:

Adicione no seu `app.json`:

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

### Para Bare Workflow (Info.plist):

```xml
<key>UIBackgroundModes</key>
<array>
    <string>processing</string>
    <string>fetch</string>
    <string>background-app-refresh</string>
</array>

<key>BGTaskSchedulerPermittedIdentifiers</key>
<array>
    <string>com.expo.analytics.upload</string>
    <string>com.expo.analytics.sync</string>
</array>
```

## 🔄 ALTERNATIVA SIMPLES

Se você não quer lidar com `BGTaskScheduler`, remova o modo `processing`:

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

**⚠️ Nota:** Isso reduz o tempo disponível para uploads em background.

## 📋 Checklist

- [ ] ✅ Adicionei `BGTaskSchedulerPermittedIdentifiers` no app.json/Info.plist
- [ ] ✅ Os identificadores estão corretos (`com.expo.analytics.upload`, `com.expo.analytics.sync`)
- [ ] ✅ Rebuilo o app com `expo build:ios` ou `eas build`
- [ ] ✅ Testei o upload novamente

## 🚀 Após a Correção

1. **Rebuild seu app** completamente
2. **Upload novo binary** para App Store Connect
3. **Teste background functionality** em dispositivo real

## ❓ Ainda com Problemas?

Se o erro persistir:

1. **Verifique** se está usando a versão mais recente do Expo CLI
2. **Limpe cache** com `expo r -c`
3. **Delete** pasta `ios/` se existir e rebuild
4. **Confirme** que não há arquivos Info.plist conflitantes

---

**💡 TL;DR:** Adicione `BGTaskSchedulerPermittedIdentifiers` no seu app.json quando usar background mode `processing` no iOS 13+. 