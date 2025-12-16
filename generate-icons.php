<?php
/**
 * Gerador de ícones para PWA
 * Execute uma vez: php generate-icons.php
 */

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$outputDir = __DIR__ . '/assets/icons/';

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

foreach ($sizes as $size) {
    // Cria imagem
    $image = imagecreatetruecolor($size, $size);
    
    // Cores
    $bgColor = imagecolorallocate($image, 59, 130, 246); // #3b82f6 (azul)
    $textColor = imagecolorallocate($image, 255, 255, 255); // branco
    
    // Preenche fundo
    imagefill($image, 0, 0, $bgColor);
    
    // Adiciona texto "$" no centro
    $fontSize = $size * 0.5;
    $text = '$';
    
    // Calcula posição central (aproximado)
    $x = $size * 0.32;
    $y = $size * 0.72;
    
    // Usa fonte padrão se não tiver TTF
    if (function_exists('imagettftext') && file_exists('C:/Windows/Fonts/arial.ttf')) {
        imagettftext($image, $fontSize, 0, $x, $y, $textColor, 'C:/Windows/Fonts/arial.ttf', $text);
    } else {
        // Fallback: usa fonte GD padrão
        $fontScale = (int)($size / 20);
        $fontScale = max(1, min(5, $fontScale));
        $charWidth = imagefontwidth($fontScale);
        $charHeight = imagefontheight($fontScale);
        $x = ($size - $charWidth) / 2;
        $y = ($size - $charHeight) / 2;
        imagestring($image, $fontScale, $x, $y, $text, $textColor);
    }
    
    // Salva PNG
    $filename = $outputDir . "icon-{$size}.png";
    imagepng($image, $filename);
    imagedestroy($image);
    
    echo "Criado: icon-{$size}.png\n";
}

echo "\nÍcones gerados com sucesso!\n";

