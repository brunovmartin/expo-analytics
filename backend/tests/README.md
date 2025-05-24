# 🧪 Scripts de Teste - Expo Analytics

Esta pasta contém todos os scripts de teste organizados para facilitar a manutenção e execução dos testes do sistema.

## 📋 Scripts Disponíveis

### 🔧 **Testes de API**

#### `test-api.php`
- **Função**: Teste completo da API principal
- **Testa**: Endpoints, recebimento de dados, validações
- **Execução**: `php tests/test-api.php`

#### `test-new-features.php`
- **Função**: Teste das funcionalidades mais recentes
- **Testa**: Recursos implementados recentemente
- **Execução**: `php tests/test-new-features.php`

### 📊 **Testes de Interface**

#### `test-dashboard.php`
- **Função**: Teste do dashboard web
- **Testa**: Carregamento, dados, interface
- **Execução**: `php tests/test-dashboard.php`

#### `test-modal.html`
- **Função**: Teste de modais e overlays
- **Testa**: Funcionamento de pop-ups e interfaces
- **Execução**: Abrir no navegador

### 🎬 **Testes de Sessões**

#### `testar-sessoes.php`
- **Função**: Teste completo do sistema de sessões
- **Testa**: Gravação, reprodução, vídeos
- **Execução**: `php tests/testar-sessoes.php`

### 📸 **Testes de Imagens**

#### `test-image-size.php`
- **Função**: Teste de redimensionamento de imagens
- **Testa**: Otimização, qualidade, tamanhos
- **Execução**: `php tests/test-image-size.php`

### 📝 **Testes de Logs**

#### `test-logs.php`
- **Função**: Teste do sistema de logs
- **Testa**: Criação, escrita, rotação de logs
- **Execução**: `php tests/test-logs.php`

## 🚀 Execução dos Testes

### **Executar Todos os Testes**
```bash
# A partir da pasta backend
cd backend

# Executar sequencialmente
php tests/test-api.php
php tests/test-dashboard.php
php tests/test-new-features.php
php tests/test-image-size.php
php tests/test-logs.php
php tests/testar-sessoes.php
```

### **Executar Teste Específico**
```bash
# Exemplo: Testar apenas a API
cd backend && php tests/test-api.php

# Exemplo: Testar apenas o dashboard
cd backend && php tests/test-dashboard.php
```

## 📊 **Interpretação dos Resultados**

### ✅ **Resultado de Sucesso**
```
✅ Status da API               PASS
✅ Envio de dados do usuário    PASS  
✅ Dashboard com usuário        PASS
```

### ❌ **Resultado de Falha**
```
❌ Status da API               FAIL
❌ Endpoint não encontrado      ERROR
```

### ⚠️ **Resultado de Aviso**
```
⚠️ Dashboard com delay         WARNING
⚠️ Imagem grande demais        WARNING
```

## 🔧 **Configuração de Teste**

### **Pré-requisitos**
- Servidor PHP rodando em `localhost:8080`
- Pasta `analytics-data` com permissões corretas
- Extensões PHP necessárias ativas

### **Setup Rápido**
```bash
# 1. Iniciar servidor
cd backend && ./start-server.sh

# 2. Verificar se está funcionando
curl http://localhost:8080/status

# 3. Executar testes
php tests/test-api.php
```

## 🐛 **Debugging**

### **Logs de Teste**
Os testes criam logs em:
- `analytics-data/logs/test-[data].log`
- Console/terminal durante execução

### **Dados de Teste**
Os testes podem criar dados temporários em:
- `analytics-data/users/test-*`
- `analytics-data/screenshots/test-*`
- `analytics-data/events/test-*`

### **Limpeza Automática**
A maioria dos testes limpa automaticamente os dados criados. Para limpeza manual:
```bash
cd backend && php limpar-dados.php
```

## 📝 **Adicionando Novos Testes**

### **Estrutura Recomendada**
```php
<?php
// Nome do arquivo: test-minha-funcionalidade.php

echo "🧪 Testando Minha Funcionalidade...\n";

try {
    // Setup
    $testData = setupTestData();
    
    // Teste
    $result = minhaFuncionalidade($testData);
    
    // Validação
    if ($result === expected) {
        echo "✅ Minha Funcionalidade    PASS\n";
    } else {
        echo "❌ Minha Funcionalidade    FAIL\n";
    }
    
    // Cleanup
    cleanupTestData($testData);
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
```

### **Convenções**
- Arquivos PHP: `test-[nome].php`
- Arquivos HTML: `test-[nome].html`
- Prefixo nos dados: `test-` para facilitar limpeza
- Output formatado: `✅ ❌ ⚠️` para status
- Sempre fazer cleanup dos dados de teste

---

## 🎯 **Resumo**

Esta pasta organiza todos os testes do sistema de forma estruturada:

- **📡 API**: `test-api.php`, `test-new-features.php`
- **📊 Interface**: `test-dashboard.php`, `test-modal.html`
- **🎬 Sessões**: `testar-sessoes.php`
- **📸 Imagens**: `test-image-size.php`
- **📝 Logs**: `test-logs.php`

**Execução**: `cd backend && php tests/[nome-do-teste].php`

**Status**: ✅ Funcionando | ❌ Com erro | ⚠️ Com aviso 