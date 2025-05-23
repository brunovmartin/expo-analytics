# 📊 Dashboard Analytics - Expo Analytics

Dashboard moderno e interativo para visualizar dados de analytics e reproduzir sessões de usuário.

## 🎯 Funcionalidades

### 📈 **Visão Geral**
- **Estatísticas em tempo real** - Usuários, sessões, screenshots e eventos
- **Lista de usuários recentes** - Ordenados por último acesso
- **Interface responsiva** - Funciona em desktop e mobile

### 🎬 **Player de Sessão**
- **Reprodução de screenshots** como vídeo
- **Controles de reprodução** - Play/pause, seek, velocidade
- **Múltiplas velocidades** - 0.5x, 1x, 1.5x, 2x, 4x
- **Informações da sessão** - Metadados e dados do usuário
- **Timeline interativa** - Navegação frame a frame

### 🔄 **Atualização Automática**
- **Auto-refresh** das estatísticas a cada 30 segundos
- **Cache inteligente** de imagens para performance
- **Indicador de status** em tempo real

## 🚀 Como usar

### 1. **Inicie a API e o Dashboard**

```bash
# Terminal 1: Iniciar servidor da API
./start-server.sh

# Terminal 2: Acessar dashboard (ou mesmo servidor)
# Acesse: http://localhost:8080/dashboard.php
```

### 2. **Navegação no Dashboard**

1. **Página inicial** - Veja estatísticas gerais
2. **Clique em um usuário** - Veja sessões específicas
3. **Clique no botão play** - Reproduza a sessão como vídeo
4. **Use os controles** - Play/pause, velocidade, timeline

### 3. **Player de Vídeo**

#### **Controles Disponíveis:**
- **▶️ Play/Pause** - Iniciar/pausar reprodução
- **🎚️ Timeline** - Navegar para qualquer frame
- **⚡ Velocidade** - Alterar velocidade de reprodução
- **⏰ Tempo** - Ver progresso atual/total
- **ℹ️ Info** - Metadados da sessão no painel lateral

#### **Atalhos de Teclado:**
- **ESC** - Fechar player
- **Clique fora** - Fechar modal

## 📁 Estrutura de Arquivos

```
├── dashboard.php          # Dashboard principal
├── assets/
│   ├── style.css         # Estilos modernos
│   └── script.js         # JavaScript do player
├── view-screenshot.php   # Servidor de imagens
├── session-data.php      # API de dados de sessão
└── analytics-data/       # Dados salvos
    ├── screenshots/      # Imagens organizadas por user/data
    ├── events/          # Eventos em JSONL
    ├── users/           # Informações dos usuários
    └── logs/            # Logs da API
```

## 🎨 Design

### **Paleta de Cores**
- **Primária:** Gradiente roxo/azul (#667eea → #764ba2)
- **Secundária:** Rosa/vermelho (#f093fb → #f5576c)
- **Sucesso:** Azul/ciano (#4facfe → #00f2fe)
- **Aviso:** Verde/ciano (#43e97b → #38f9d7)

### **Características Visuais**
- **Glass morphism** - Transparências e blur
- **Animações suaves** - Transições de 0.3s
- **Cards responsivos** - Grid adaptativo
- **Ícones Font Awesome** - Interface consistente
- **Shadows modernas** - Elevação visual

## 📊 Dados Visualizados

### **Estatísticas Principais**
- **👥 Usuários Totais** - Quantidade única de usuários
- **🎬 Sessões Gravadas** - Total de sessões com screenshots
- **📸 Screenshots** - Número total de imagens capturadas
- **🖱️ Eventos Rastreados** - Ações dos usuários

### **Por Usuário**
- **Última atividade** - Timestamp do último acesso
- **Versão do app** - Se disponível nos userData
- **Informações customizadas** - Dados enviados pelo app

### **Por Sessão**
- **Data e hora** - Quando a sessão ocorreu
- **Quantidade de frames** - Screenshots capturados
- **Duração** - Tempo total da sessão
- **Metadados** - Geo, userData, etc.

## 🔧 Configurações

### **Player de Vídeo**
```javascript
// Em assets/script.js
this.frameRate = 2;  // FPS padrão
this.speeds = [0.5, 1, 1.5, 2, 4];  // Velocidades disponíveis
```

### **Auto-refresh**
```javascript
// Atualizar a cada 30 segundos
setInterval(autoRefresh, 30000);
```

### **Cache de Imagens**
```php
// Em view-screenshot.php
header('Cache-Control: public, max-age=3600'); // 1 hora
```

## 🛡️ Segurança

### **Validações Implementadas**
- **Path traversal protection** - Sanitização de parâmetros
- **Tipo de arquivo** - Apenas JPG/PNG permitidos
- **Formato de data** - Validação YYYY-MM-DD
- **Caracteres permitidos** - Alphanumeros e alguns símbolos

### **Headers de Segurança**
- **Content-Type** correto para cada tipo de arquivo
- **Cache headers** para otimização
- **CORS** habilitado para desenvolvimento

## 📱 Responsividade

### **Breakpoints**
- **Desktop** - Tela completa com sidebar
- **Tablet (1024px)** - Layout stack
- **Mobile (768px)** - Interface simplificada

### **Adaptações Mobile**
- **Cards empilhados** - Uma coluna
- **Player fullscreen** - Aproveita tela toda
- **Touch friendly** - Botões maiores

## 🚨 Troubleshooting

### **Problema: Imagens não carregam**
```bash
# Verificar permissões
chmod 755 analytics-data/
chmod 644 analytics-data/screenshots/*/*/*.jpg
```

### **Problema: Player não abre**
```javascript
// Verificar console do browser para erros
// Verificar se session-data.php está acessível
```

### **Problema: Dashboard em branco**
```php
// Verificar se analytics-data/ existe
// Verificar logs de erro do PHP
```

## 🔄 Atualizações

### **Dados em Tempo Real**
O dashboard atualiza automaticamente as estatísticas, mas para ver novos dados imediatamente:

1. **Refresh manual** - F5 na página
2. **Auto-refresh** - Aguarde 30 segundos
3. **Novo usuário** - Aparece na próxima atualização

## 🎯 Próximas Funcionalidades

- [ ] **Filtros avançados** - Por data, usuário, versão
- [ ] **Exportação de dados** - CSV, JSON
- [ ] **Gráficos interativos** - Estatísticas visuais
- [ ] **Notificações** - Alertas em tempo real
- [ ] **Busca** - Encontrar sessões específicas
- [ ] **Comparação** - Múltiplas sessões lado a lado

---

## 🎉 Dashboard pronto para uso!

Acesse `http://localhost:8080/dashboard.php` e explore seus dados de analytics de forma visual e interativa! 🚀 