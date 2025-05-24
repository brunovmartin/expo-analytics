<?php
// Script para verificar tamanhos e dimensões das imagens
// Uso: php test-image-size.php

$baseDir = __DIR__ . '/analytics-data';

echo "🔍 Verificando dimensões e tamanhos das imagens...\n\n";

function analyzeImages($dir) {
    $images = glob($dir . '/*.jpg');
    
    if (empty($images)) {
        echo "❌ Nenhuma imagem encontrada em: $dir\n";
        return;
    }
    
    echo "📁 Pasta: " . basename(dirname($dir)) . "/" . basename($dir) . "\n";
    echo "📸 Total de imagens: " . count($images) . "\n\n";
    
    $totalSize = 0;
    $sizes = [];
    $dimensions = [];
    
    foreach ($images as $index => $image) {
        $size = filesize($image);
        $totalSize += $size;
        $sizes[] = $size;
        
        $imageInfo = getimagesize($image);
        if ($imageInfo !== false) {
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $key = "{$width}x{$height}";
            $dimensions[$key] = ($dimensions[$key] ?? 0) + 1;
            
            // Mostrar detalhes das primeiras 3 e últimas 3 imagens
            if ($index < 3 || $index >= count($images) - 3) {
                echo sprintf("🖼️  %s: %dx%d, %dKB\n", 
                    basename($image), $width, $height, round($size/1024));
            } elseif ($index == 3) {
                echo "   ... (" . (count($images) - 6) . " imagens intermediárias)\n";
            }
        }
    }
    
    echo "\n📊 Estatísticas:\n";
    echo "   Total: " . round($totalSize/1024/1024, 2) . "MB\n";
    echo "   Média: " . round(($totalSize/count($images))/1024, 1) . "KB por imagem\n";
    echo "   Min: " . round(min($sizes)/1024, 1) . "KB\n";
    echo "   Max: " . round(max($sizes)/1024, 1) . "KB\n";
    
    echo "\n📐 Dimensões encontradas:\n";
    foreach ($dimensions as $dimension => $count) {
        $status = $dimension === '480x960' ? '✅' : '❌';
        echo "   $status $dimension: $count imagens\n";
    }
    
    // Verificar se todas as imagens estão no tamanho correto
    $correctSize = $dimensions['480x960'] ?? 0;
    $totalImages = count($images);
    
    if ($correctSize === $totalImages) {
        echo "\n🎉 Todas as $totalImages imagens estão em 480x960! ✅\n";
    } else {
        echo "\n⚠️  Apenas $correctSize de $totalImages imagens estão em 480x960\n";
    }
    
    // Verificar tamanho médio
    $avgSizeKB = round(($totalSize/count($images))/1024, 1);
    if ($avgSizeKB >= 20 && $avgSizeKB <= 60) {
        echo "✅ Tamanho médio OK: {$avgSizeKB}KB (esperado: 20-60KB)\n";
    } else {
        echo "⚠️  Tamanho médio fora do esperado: {$avgSizeKB}KB (esperado: 20-60KB)\n";
    }
    
    echo "\n" . str_repeat("─", 60) . "\n\n";
}

// Verificar todas as sessões
$screenshotsDir = $baseDir . '/screenshots';

if (!is_dir($screenshotsDir)) {
    echo "❌ Pasta de screenshots não encontrada: $screenshotsDir\n";
    echo "💡 Execute o app primeiro para gerar algumas imagens.\n";
    exit(1);
}

$userDirs = glob($screenshotsDir . '/*', GLOB_ONLYDIR);

if (empty($userDirs)) {
    echo "❌ Nenhum usuário encontrado\n";
    exit(1);
}

foreach ($userDirs as $userDir) {
    $dateDirs = glob($userDir . '/*', GLOB_ONLYDIR);
    
    foreach ($dateDirs as $dateDir) {
        analyzeImages($dateDir);
    }
}

echo "🏁 Análise concluída!\n";
echo "\n💡 Dicas:\n";
echo "   - Imagens devem ter 480x960 pixels\n";
echo "   - Tamanho ideal: 20-60KB (50% compressão JPEG)\n";
echo "   - Se encontrar problemas, verifique o código iOS\n";
?> 