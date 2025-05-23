# Script PowerShell para descobrir IP para ExpoAnalytics
# Execute: PowerShell -ExecutionPolicy Bypass -File scripts/get-ip.ps1

Write-Host "🔍 Descobrindo IP da máquina para ExpoAnalytics..." -ForegroundColor Cyan
Write-Host ""

Write-Host "💻 Sistema detectado: Windows" -ForegroundColor Green

try {
    # Tentar pegar IP da conexão ativa (Wi-Fi ou Ethernet)
    $IP = (Get-NetIPAddress | Where-Object {
        $_.AddressFamily -eq 'IPv4' -and 
        $_.PrefixOrigin -eq 'Dhcp' -and 
        $_.IPAddress -notlike '127.*' -and 
        $_.IPAddress -notlike '169.254.*'
    }).IPAddress | Select-Object -First 1

    if (-not $IP) {
        # Fallback: tentar método alternativo
        $IP = (Get-WmiObject -Class Win32_NetworkAdapterConfiguration | Where-Object {
            $_.IPEnabled -eq $true -and $_.IPAddress -ne $null
        }).IPAddress | Where-Object { 
            $_ -notlike '127.*' -and $_ -notlike '169.254.*' 
        } | Select-Object -First 1
    }

    if (-not $IP) {
        throw "Nenhum IP encontrado"
    }

    Write-Host ""
    Write-Host "✅ IP encontrado: $IP" -ForegroundColor Green
    Write-Host ""
    Write-Host "🔧 Configuração para ExpoAnalytics:" -ForegroundColor Yellow
    Write-Host "   apiHost: 'http://$IP:8080'"
    Write-Host ""
    Write-Host "📋 Exemplo de código JavaScript:" -ForegroundColor Yellow
    Write-Host "   await ExpoAnalytics.start({"
    Write-Host "     apiHost: 'http://$IP:8080',"
    Write-Host "     userId: 'seu_user_id'"
    Write-Host "   });"
    Write-Host ""
    Write-Host "🧪 Teste de conectividade:" -ForegroundColor Yellow
    Write-Host "   curl -I http://$IP:8080"
    Write-Host "   ou no PowerShell:"
    Write-Host "   Invoke-WebRequest -Uri http://$IP:8080 -Method Head"
    Write-Host ""
    Write-Host "📝 Copie esta linha para usar no seu código:" -ForegroundColor Magenta
    Write-Host "   http://$IP:8080" -ForegroundColor White -BackgroundColor DarkBlue

} catch {
    Write-Host "❌ Não foi possível descobrir o IP automaticamente" -ForegroundColor Red
    Write-Host ""
    Write-Host "💡 Métodos manuais no Windows:" -ForegroundColor Yellow
    Write-Host "   ipconfig | findstr IPv4"
    Write-Host "   Get-NetIPAddress | Where-Object AddressFamily -eq IPv4"
    Write-Host ""
    Write-Host "🔧 Ou use uma dessas ferramentas:" -ForegroundColor Yellow
    Write-Host "   • Painel de Controle > Central de Rede e Compartilhamento"
    Write-Host "   • Configurações > Rede e Internet > Status"
    exit 1
}

Write-Host ""
Write-Host "ℹ️  Para executar novamente:" -ForegroundColor Gray
Write-Host "   PowerShell -ExecutionPolicy Bypass -File scripts/get-ip.ps1" 