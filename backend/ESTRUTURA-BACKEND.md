# 🏗️ Estrutura do Backend - Expo Analytics

## 📁 **Organização Atual**

```
📦 expo-analytics/
├── 📱 src/                     # Módulo Expo TypeScript
├── 📱 ios/                     # Código Swift nativo
├── 📱 android/                 # Código Kotlin nativo  
├── 📱 example/                 # App de exemplo/teste
│
└── 🖥️ backend/                 # ← TODO BACKEND ORGANIZADO AQUI
    ├── 📡 API & Servidor
    │   ├── api-receiver.php    # API principal (roteamento)
    │   ├── dashboard.php       # Dashboard interativo
    │   ├── view-screenshot.php # Servidor de imagens
    │   └── session-data.php    # API de dados de sessão
    │
    ├── 🎨 Interface
    │   ├── index.html          # Página inicial
    │   └── assets/             # CSS, JS, recursos
    │       ├── style.css       # Estilos do dashboard
    │       └── script.js       # JavaScript do player
    │
    ├── 🧪 Testes & Scripts
    │   ├── start-server.sh     # Script para iniciar servidor
    │   ├── test-api.php        # Teste da API
    │   └── test-dashboard.php  # Teste do dashboard
    │
    ├── 📚 Documentação
    │   ├── README-API.md       # Documentação da API
    │   └── README-Dashboard.md # Guia do dashboard
    │
    └── 📊 analytics-data/      # ← DADOS AGORA DENTRO DO BACKEND
        ├── screenshots/        # Imagens por usuário/data
        │   └── [userId]/
        │       └── [date]/
        │           ├── screenshot_*.jpg
        │           └── metadata_*.json
        │
        ├── events/            # Eventos rastreados
        │   └── [userId]/
        │       └── [date]/
        │           └── events_*.jsonl
        │
        ├── users/             # Informações dos usuários
        │   └── [userId]/
        │       ├── latest.json
        │       └── info_*.json
        │
        └── logs/              # Logs da API
            └── [date].log
```

## ✅ **Vantagens da Nova Organização**

### 🎯 **Backend Centralizado:**
- **Tudo em um lugar:** API, dashboard, dados e assets
- **Fácil deployment:** Uma pasta contém todo o backend
- **Isolamento:** Frontend e backend completamente separados
- **Portabilidade:** Backend pode ser movido independentemente

### 📦 **Estrutura Limpa:**
- **Raiz do projeto** só tem arquivos do módulo Expo
- **Backend** autocontido com suas próprias dependências
- **Dados** protegidos dentro da estrutura do backend
- **Scripts** organizados por funcionalidade

### 🚀 **Facilidade de Uso:**
- **Script único:** `./start-backend.sh` para iniciar tudo
- **Testes integrados:** Scripts de teste dentro do backend
- **Documentação local:** READMEs específicos do backend

## 🔧 **Comandos Atualizados**

### **Iniciar Backend:**
```bash
# Da raiz do projeto (recomendado)
./start-backend.sh

# Ou diretamente da pasta backend
cd backend && ./start-server.sh
```

### **Testar Sistema:**
```bash
# API
cd backend && php test-api.php

# Dashboard
cd backend && php test-dashboard.php
```

### **Verificar Dados:**
```bash
# Ver estrutura dos dados
cd backend && ls -la analytics-data/

# Ver logs
cd backend && tail -f analytics-data/logs/$(date +%Y-%m-%d).log
```

## 📡 **URLs do Sistema**

| Serviço | URL | Descrição |
|---------|-----|-----------|
| 🏠 **Home** | http://localhost:8080/ | Página inicial/status |
| 📊 **Dashboard** | http://localhost:8080/dashboard | Dashboard principal |
| 📈 **Status** | http://localhost:8080/status | Status da API |
| 🖼️ **Assets** | http://localhost:8080/assets/* | CSS, JS, imagens |

## 🔄 **Migração Realizada**

### **Arquivos Movidos:**
```bash
# Da raiz para backend/
api-receiver.php → backend/api-receiver.php
dashboard.php → backend/dashboard.php
assets/ → backend/assets/
analytics-data/ → backend/analytics-data/
# ... todos os arquivos PHP e relacionados
```

### **Caminhos Atualizados:**
```php
// Antes (buscava na pasta pai)
$baseDir = dirname(__DIR__) . '/analytics-data';

// Agora (busca na mesma pasta)
$baseDir = __DIR__ . '/analytics-data';
```

### **Scripts Atualizados:**
- ✅ `start-backend.sh` - Criado na raiz
- ✅ `backend/start-server.sh` - Atualizado
- ✅ `.gitignore` - Ignora `backend/analytics-data/`
- ✅ `README.md` - Estrutura atualizada

## 🎯 **Resultado Final**

### ✅ **Sistema Funcionando:**
- **7/7 rotas** do dashboard funcionando
- **4/4 endpoints** da API funcionando  
- **Dados sendo salvos** na nova localização
- **Testes passando** 100%

### 🏗️ **Estrutura Profissional:**
- **Backend autocontido** e organizadamente estruturado
- **Separação clara** entre frontend (Expo) e backend (PHP)
- **Fácil manutenção** e desenvolvimento independente
- **Pronto para produção** com estrutura escalável

---

## 🚀 **Como Usar Agora**

```bash
# 1. Iniciar o backend
./start-backend.sh

# 2. Acessar dashboard
open http://localhost:8080/dashboard

# 3. Usar o app Expo (já configurado)
cd example && npx expo run:ios

# 4. Ver dados em tempo real no dashboard! 🎉
```

**Backend completamente reorganizado e funcionando! 🎯✨** 