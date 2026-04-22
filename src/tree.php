<?php

function printTree(string $dir, string $prefix = ''): void
{
    if (!is_dir($dir)) {
        throw new InvalidArgumentException("Path non valido: $dir");
    }

    $items = scandir($dir);

    // Rimuove . e ..
    $items = array_filter($items, function ($item) {
        return $item !== '.' && $item !== '..';
    });

    // Riordino per leggibilità
    sort($items);

    $total = count($items);
    $index = 0;

    foreach ($items as $item) {
        $index++;
        $path = $dir . DIRECTORY_SEPARATOR . $item;

        $isLast = $index === $total;

        $connector = $isLast ? '└── ' : '├── ';

        echo $prefix . $connector . $item . PHP_EOL;

        if (is_dir($path)) {
            $newPrefix = $prefix . ($isLast ? '    ' : '│   ');
            printTree($path, $newPrefix);
        }
    }
}

// =====================
// USO
// =====================

$path = $argv[1] ?? __DIR__;

echo basename($path) . PHP_EOL;
printTree($path);