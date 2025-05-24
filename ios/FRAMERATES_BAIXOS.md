# Suporte a Framerates Baixos (0.1 - 60 FPS)

## 🚀 Nova Funcionalidade Implementada

O sistema agora suporta framerates extremamente baixos, permitindo capturas espaçadas que economizam recursos e são ideais para monitoramento de longa duração.

## 📊 Ranges Suportados

### Framerates Muito Baixos (0.1 - 1.0 fps)
- **0.1 fps**: 1 frame a cada 10 segundos
- **0.2 fps**: 1 frame a cada 5 segundos  
- **0.5 fps**: 1 frame a cada 2 segundos
- **1.0 fps**: 1 frame por segundo

### Framerates Normais (1.0 - 60 fps)
- **1-5 fps**: Economia de recursos
- **10-15 fps**: Balanceado (recomendado)
- **20-30 fps**: Qualidade alta
- **60 fps**: Máximo suportado

## 🔧 Mudanças Implementadas

### 1. **ExpoAnalyticsModule.swift**

#### Tipo de dados mudado para Double:
```swift
// ANTES:
private var framerate: Int = 30

// DEPOIS:
private var framerate: Double = 10.0
```

#### Suporte a valores decimais na configuração:
```swift
// Suporte a framerate decimal (0.1 a 60 fps)
if let framerateDouble = serverConfig["framerate"] as? Double {
  self.framerate = max(min(framerateDouble, 60.0), 0.1) // Limite: 0.1-60 FPS
} else if let framerateInt = serverConfig["framerate"] as? Int {
  self.framerate = max(min(Double(framerateInt), 60.0), 0.1) // Compatibilidade
}
```

#### DisplayLink otimizado para framerates baixos:
```swift
let idealDisplayFramerate: Int
if self.framerate >= 30.0 {
    idealDisplayFramerate = min(Int(self.framerate) + 10, 60)  // Framerates altos
} else if self.framerate >= 5.0 {
    idealDisplayFramerate = Int(self.framerate) * 2  // Framerates médios
} else if self.framerate >= 1.0 {
    idealDisplayFramerate = 15  // Framerates baixos (1-5 fps)
} else {
    idealDisplayFramerate = 10  // Framerates muito baixos (<1 fps)
}
```

#### Logs informativos para framerates baixos:
```swift
if self.framerate < 1.0 {
    NSLog("   ⚠️ Framerate muito baixo: um frame a cada \(String(format: "%.1f", 1.0/self.framerate)) segundos")
}
```

### 2. **Dashboard (dashboard.php)**

#### Select com opções predefinidas:
```html
<select id="framerateSelect" onchange="setFramerate(this.value)">
    <option value="0.1">0.1 fps (1 frame a cada 10s)</option>
    <option value="0.2">0.2 fps (1 frame a cada 5s)</option>
    <option value="0.5">0.5 fps (1 frame a cada 2s)</option>
    <option value="1">1 fps (1 frame por segundo)</option>
    <!-- ... mais opções ... -->
</select>
```

#### Range slider expandido:
```html
<input type="range" id="framerate" name="framerate" 
       min="0.1" max="60" step="0.1" value="10">
```

#### Exibição inteligente do framerate:
```php
$fps = (float)$app['config']['framerate'];
if ($fps < 1) {
    echo number_format($fps, 1) . ' fps (1 frame a cada ' . number_format(1/$fps, 1) . 's)';
} else {
    echo number_format($fps, 1) . ' fps';
}
```

## 💡 Casos de Uso

### Monitoramento de Longa Duração
```javascript
// Configurar para capturar 1 frame a cada 10 segundos
await ExpoAnalytics.start({
    framerate: 0.1,  // 0.1 fps
    screenSize: 480
});
```

### Economia de Recursos
```javascript
// Para apps que rodam em background por muito tempo
await ExpoAnalytics.start({
    framerate: 0.2,  // 1 frame a cada 5 segundos
    screenSize: 320  // Resolução menor também
});
```

### Capturas Estratégicas
```javascript
// Para capturar mudanças lentas na interface
await ExpoAnalytics.start({
    framerate: 0.5,  // 1 frame a cada 2 segundos
    screenSize: 640
});
```

## 🎯 Vantagens dos Framerates Baixos

### ✅ **Economia de Recursos**
- Menos uso de CPU
- Menor consumo de bateria
- Redução do armazenamento local

### ✅ **Monitoramento Eficiente**
- Ideal para apps de produtividade
- Perfeito para sessões longas
- Captura mudanças importantes sem spam

### ✅ **Flexibilidade Total**
- 0.1 fps: Monitoramento extremo (1 frame/10s)
- 0.5 fps: Balanceado para sessões longas
- 1-5 fps: Captura regular com economia
- 10+ fps: Experiência completa

## 📱 Interface do Dashboard

### Seleção Rápida
O dashboard agora oferece opções predefinidas para facilitar a configuração:

```
┌─────────────────────────────────────┐
│ Framerate Predefinido:              │
│ [Personalizado ▼]                   │
│   • 0.1 fps (1 frame a cada 10s)    │
│   • 0.2 fps (1 frame a cada 5s)     │
│   • 0.5 fps (1 frame a cada 2s)     │
│   • 1 fps (1 frame por segundo)     │
│   • 10 fps (recomendado)            │
│   • 60 fps (máximo)                 │
└─────────────────────────────────────┘
```

### Slider Personalizado
Para valores específicos não listados:

```
┌─────────────────────────────────────┐
│ Framerate Personalizado: 2.3 fps    │
│ ●────────────○──────────────────────│
│ 0.1 fps              60 fps         │
│                                     │
│ Use valores baixos para economizar  │
│ recursos em capturas espaçadas      │
└─────────────────────────────────────┘
```

## 🔍 Logs de Debug

### Framerates Normais (≥1 fps):
```
🔧 [ExpoAnalytics] Configurações aplicadas:
   Framerate: 10.0 fps (intervalo: 0.100s)
   DisplayLink: 20 fps
```

### Framerates Baixos (<1 fps):
```
🔧 [ExpoAnalytics] Configurações aplicadas:
   Framerate: 0.1 fps (intervalo: 10.000s)
   DisplayLink: 10 fps
   ⚠️ Framerate muito baixo: um frame a cada 10.0 segundos
```

## ⚙️ Configuração via API

### Criar app com framerate baixo:
```bash
curl -X POST http://localhost:8888/apps \
  -H "Content-Type: application/json" \
  -d '{
    "bundleId": "com.exemplo.app",
    "name": "App Monitoramento",
    "platform": "ios",
    "config": {
      "recordScreen": true,
      "framerate": 0.1,
      "screenSize": 480
    }
  }'
```

### Atualizar framerate existente:
```bash
curl -X POST http://localhost:8888/dashboard \
  -F "action=update_app" \
  -F "bundleId=com.exemplo.app" \
  -F "framerate=0.2"
```

## 🚨 Considerações Importantes

### Performance
- **Framerates <1 fps**: DisplayLink roda a 10fps, throttling controla captura
- **Framerates 1-5 fps**: DisplayLink roda a 15fps fixo
- **Framerates >5 fps**: DisplayLink otimizado dinamicamente

### Compatibilidade
- ✅ Mantém compatibilidade com valores inteiros antigos
- ✅ Suporte automático a Double e Int
- ✅ Fallback seguro para valores inválidos

### Limitações
- **Mínimo**: 0.1 fps (1 frame a cada 10 segundos)
- **Máximo**: 60 fps
- **Precisão**: 0.1 fps (1 casa decimal)

## 🎉 Exemplo Completo

```typescript
// App de produtividade com monitoramento espaçado
await ExpoAnalytics.start({
    framerate: 0.2,      // 1 frame a cada 5 segundos
    screenSize: 480,     // Resolução balanceada
    userData: {
        monitoringType: 'long-session',
        expectedDuration: 3600  // 1 hora
    }
});

// Resultado: ~720 frames em 1 hora (em vez de 36,000 a 10fps)
// Economia: 98% menos frames, mesmo insight de uso
```

---

**✅ Sistema agora suporta de 0.1 fps até 60 fps com configuração flexível e interface amigável!** 