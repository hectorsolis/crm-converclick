#!/bin/bash
# Script para iniciar o servidor de desenvolvimento PHP
# Uso: ./start_server.sh

PORT=8000
HOST=localhost

echo "Iniciando servidor PHP em http://$HOST:$PORT"
echo "Pressione Ctrl+C para parar."

php -S $HOST:$PORT -t public dev_router.php
