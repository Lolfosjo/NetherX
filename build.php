<?php

$buildDir = __DIR__.'/build';
if (!is_dir($buildDir)) {
    mkdir($buildDir, 0777, true);
}

$pharFile = $buildDir.'/NetherX.phar';

if (file_exists($pharFile)) {
    unlink($pharFile);
}

$phar = new Phar($pharFile);

$phar->startBuffering();

$phar->buildFromDirectory(__DIR__, '/^(?!.*(build|vendor|composer\.lock)).*$/');

$phar->setStub('<?php __HALT_COMPILER(); ?>');

$phar->stopBuffering();

echo "PHAR built: {$pharFile}\n";
