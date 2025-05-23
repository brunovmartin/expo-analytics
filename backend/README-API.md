# API Receiver PHP - Expo Analytics

Script PHP simples para receber e salvar dados do módulo Expo Analytics localmente.

## 🚀 Como usar

### 1. Iniciar o servidor PHP local

```bash
php -S localhost:8080 api-receiver.php
```

### 2. Atualizar o código do app

No seu `example/App.tsx`, altere o `apiHost` para apontar para o servidor local:

```typescript
const options: StartOptions = {
  apiHost: 'http://localhost:8080', // Ou seu IP local como http://192.168.1.100:8080
  userId: 'user123',
  framerate: 30,
  userData: {
    appVersion: '1.0.0',
    userType: 'premium'
  }
};
```

## 📁 Estrutura dos dados salvos

O script criará a seguinte estrutura de pastas:

```
analytics-data/
├── screenshots/
│   └── [userId]/
│       └── [date]/
│           ├── screenshot_[timestamp]_000.jpg
│           ├── screenshot_[timestamp]_001.jpg
│           └── metadata_[timestamp].json
├── events/
│   └── [userId]/
│       └── [date]/
│           └── events_[hour].jsonl
├── users/
│   └── [userId]/
│       ├── info_[datetime].json
│       └── latest.json
└── logs/
    └── [date].log
```

## 📡 Endpoints disponíveis

- **POST `/upload`** - Recebe screenshots em base64
- **POST `/track`** - Recebe eventos de tracking
- **POST `/init`** - Recebe informações do usuário
- **GET `/status`** - Status da API e estatísticas

## 🔍 Testando

Acesse `http://localhost:8080/status` no browser para ver se a API está funcionando.

## 📱 Configuração para dispositivo físico

Se você quiser testar em um dispositivo físico (iPhone/Android), você precisa:

1. **Descobrir seu IP local:**
   ```bash
   # macOS/Linux
   ifconfig | grep "inet " | grep -v 127.0.0.1
   
   # Windows
   ipconfig
   ```

2. **Iniciar o servidor com seu IP:**
   ```bash
   php -S 0.0.0.0:8080 api-receiver.php
   ```

3. **Usar o IP no app:**
   ```typescript
   apiHost: 'http://192.168.1.100:8080' // Substitua pelo seu IP
   ```

## 📊 Visualizando os dados

### Screenshots
As imagens ficam salvas em `analytics-data/screenshots/[userId]/[date]/`

### Eventos
Os eventos ficam em formato JSONL em `analytics-data/events/[userId]/[date]/events_[hour].jsonl`

### Logs
Todos os requests ficam logados em `analytics-data/logs/[date].log`

## ⚠️ Notas importantes

- Este é um script **apenas para desenvolvimento local**
- Não use em produção sem implementar autenticação e validação adequada
- As imagens são salvas em base64 decodificado como arquivos JPG
- Os logs incluem timestamp de todos os requests recebidos 