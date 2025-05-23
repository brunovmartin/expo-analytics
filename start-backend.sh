#!/bin/bash

# Script para iniciar o backend Analytics
# Uso: ./start-backend.sh (executar da raiz do projeto)

echo "🚀 Iniciando Backend Analytics..."
echo ""

# Verificar se a pasta backend existe
if [ ! -d "backend" ]; then
    echo "❌ Pasta 'backend' não encontrada!"
    echo "💡 Execute este script da raiz do projeto expo-analytics"
    exit 1
fi

# Verificar se analytics-data existe dentro do backend
if [ ! -d "backend/analytics-data" ]; then
    echo "📁 Criando pasta backend/analytics-data..."
    mkdir -p backend/analytics-data
fi

echo "📂 Entrando na pasta backend..."
cd backend

echo "🎯 Executando servidor..."
echo "📡 Acesse: http://localhost:8080/dashboard"
echo ""

# Executar o servidor
./start-server.sh 