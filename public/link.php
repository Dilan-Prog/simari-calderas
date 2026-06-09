<?php
$target = dirname(__DIR__) . '/storage/app/public';
$link   = __DIR__ . '/storage';

echo "Target: $target<br>";
echo "Link: $link<br>";
echo "Target exists: " . (file_exists($target) ? 'YES' : 'NO') . "<br>";
echo "Link exists: " . (file_exists($link) ? 'YES' : 'NO') . "<br>";

if (file_exists($link)) {
    echo "El symlink ya existe.";
} else {
    if (@symlink($target, $link)) {
        echo "Symlink creado correctamente.";
    } else {
        $err = error_get_last();
        echo "Error: " . ($err['message'] ?? 'symlink() no permitido en este servidor');
    }
}
