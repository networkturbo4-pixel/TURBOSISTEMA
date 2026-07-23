<?php
/**
 * ImageHelper - Utilidad para comprimir y marcar imágenes
 */

class ImageHelper {
    
    /**
     * Procesa una imagen: la comprime, ajusta tamaño máximo y coloca marca de agua + GPS.
     * 
     * @param string $sourceFile Ruta de la imagen original
     * @param string $destinationFile Ruta donde se guardará la imagen procesada
     * @param string|null $logoFile Ruta del logo a superponer (opcional)
     * @param string|null $latitude Latitud GPS
     * @param string|null $longitude Longitud GPS
     * @param int $maxWidth Ancho máximo permitido (px)
     * @param int $maxHeight Alto máximo permitido (px)
     * @param int $quality Calidad JPEG (0-100)
     * @return bool True si tuvo éxito, False en caso de error
     */
    public static function processAndWatermark($sourceFile, $destinationFile, $logoFile = null, $latitude = null, $longitude = null, $maxWidth = 1280, $maxHeight = 1280, $quality = 75) {
        if (!file_exists($sourceFile)) return false;
        
        $info = getimagesize($sourceFile);
        if (!$info) return false;
        
        $width = $info[0];
        $height = $info[1];
        $mime = $info['mime'];
        
        // Crear recurso de imagen
        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($sourceFile);
                break;
            case 'image/png':
                $image = imagecreatefrompng($sourceFile);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($sourceFile);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($sourceFile);
                break;
            default:
                return false; // Formato no soportado
        }
        
        if (!$image) return false;
        
        // Corregir rotación EXIF si es JPEG
        if ($mime == 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($sourceFile);
            if ($exif && isset($exif['Orientation'])) {
                $orientation = $exif['Orientation'];
                if ($orientation == 3) {
                    $image = imagerotate($image, 180, 0);
                } elseif ($orientation == 6) {
                    $image = imagerotate($image, -90, 0);
                    // Intercambiar width y height
                    $tmp = $width; $width = $height; $height = $tmp;
                } elseif ($orientation == 8) {
                    $image = imagerotate($image, 90, 0);
                    $tmp = $width; $width = $height; $height = $tmp;
                }
            }
        }
        
        // Redimensionar (Optimización)
        $newWidth = $width;
        $newHeight = $height;
        
        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);
            
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            // Preservar transparencia si aplica
            if ($mime == 'image/png' || $mime == 'image/webp' || $mime == 'image/gif') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }
        
        // Variables de fecha y hora
        date_default_timezone_set('America/Lima');
        $dateStr = date('d/m/Y');
        $timeStr = date('H:i:s');
        
        // Textos a dibujar
        $lines = [];
        $lines[] = "Fecha: $dateStr";
        $lines[] = "Hora:  $timeStr";
        
        if ($latitude && $longitude && $latitude !== 'null' && $longitude !== 'null') {
            $lines[] = "Lat: $latitude";
            $lines[] = "Lng: $longitude";
        } else {
            $lines[] = "GPS No Disponible";
        }
        
        // Dibujar franja inferior (Fondo semi transparente oscuro)
        $fontSize = 5; 
        $fontWidth = imagefontwidth($fontSize);
        $fontHeight = imagefontheight($fontSize);
        
        // Como la fuente nativa es pequeña, vamos a escalarla dibujándola en un lienzo temporal
        $scale = $newWidth > 800 ? 3 : ($newWidth > 400 ? 2 : 1);
        
        $lineHeight = $fontHeight + 4;
        $textBlockHeight = count($lines) * $lineHeight + 10; // 10 padding
        
        $scaledTextHeight = $textBlockHeight * $scale;
        
        // Logo
        $logoImg = null;
        $logoW = 0;
        $logoH = 0;
        if ($logoFile && file_exists($logoFile)) {
            $logoInfo = getimagesize($logoFile);
            if ($logoInfo) {
                if ($logoInfo['mime'] == 'image/png') $logoImg = imagecreatefrompng($logoFile);
                elseif ($logoInfo['mime'] == 'image/jpeg') $logoImg = imagecreatefromjpeg($logoFile);
                
                if ($logoImg) {
                    $targetLogoH = $scaledTextHeight - 20; 
                    if ($targetLogoH > 0) {
                        $ratio = $targetLogoH / $logoInfo[1];
                        $logoW = (int)($logoInfo[0] * $ratio);
                        $logoH = $targetLogoH;
                        
                        $resizedLogo = imagecreatetruecolor($logoW, $logoH);
                        imagealphablending($resizedLogo, false);
                        imagesavealpha($resizedLogo, true);
                        $transparent = imagecolorallocatealpha($resizedLogo, 255, 255, 255, 127);
                        imagefilledrectangle($resizedLogo, 0, 0, $logoW, $logoH, $transparent);
                        
                        imagecopyresampled($resizedLogo, $logoImg, 0, 0, 0, 0, $logoW, $logoH, $logoInfo[0], $logoInfo[1]);
                        imagedestroy($logoImg);
                        $logoImg = $resizedLogo;
                    }
                }
            }
        }
        
        // Dibujar el panel inferior oscuro
        $panelHeight = $scaledTextHeight;
        $panelY = $newHeight - $panelHeight;
        
        $blackAlpha = imagecolorallocatealpha($image, 0, 0, 0, 60); // 60 = 50% opacity in GD
        imagefilledrectangle($image, 0, $panelY, $newWidth, $newHeight, $blackAlpha);
        
        // Dibujar textos en un lienzo pequeño transparente y luego escalar
        $tempW = (int)($newWidth / $scale);
        $tempH = $textBlockHeight;
        
        $textCanvas = imagecreatetruecolor($tempW, $tempH);
        imagealphablending($textCanvas, false);
        imagesavealpha($textCanvas, true);
        $transparent = imagecolorallocatealpha($textCanvas, 0, 0, 0, 127);
        imagefilledrectangle($textCanvas, 0, 0, $tempW, $tempH, $transparent);
        
        imagealphablending($textCanvas, true);
        $white = imagecolorallocate($textCanvas, 255, 255, 255);
        $yellow = imagecolorallocate($textCanvas, 255, 235, 59);
        
        $y = 5;
        foreach ($lines as $i => $line) {
            $color = ($i >= 2) ? $yellow : $white; // Coordenadas en amarillo
            imagestring($textCanvas, $fontSize, 10, $y, $line, $color);
            $y += $lineHeight;
        }
        
        // Copiar y escalar el texto al lienzo original
        imagealphablending($image, true);
        imagecopyresampled($image, $textCanvas, 0, $panelY, 0, 0, $newWidth, $scaledTextHeight, $tempW, $tempH);
        imagedestroy($textCanvas);
        
        // Dibujar logo a la derecha
        if ($logoImg) {
            $logoX = $newWidth - $logoW - 10;
            $logoY = $newHeight - $logoH - 10;
            imagecopy($image, $logoImg, $logoX, $logoY, 0, 0, $logoW, $logoH);
            imagedestroy($logoImg);
        }
        
        // Guardar la imagen siempre como JPEG optimizado (a menos que se prefiera otro formato, pero JPEG ahorra espacio)
        $result = imagejpeg($image, $destinationFile, $quality);
        imagedestroy($image);
        
        return $result;
    }
}
