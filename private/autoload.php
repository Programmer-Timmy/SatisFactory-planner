<?php
spl_autoload_register(/**
 * @throws Exception
 */
function ($className) {
    $classFile = ucfirst(str_replace('\\', '/', $className) . '.php');

    // Check if the class file exists in Controllers or Managers
    if (file_exists(__DIR__ . '/Controllers/' . $classFile)) {
    require __DIR__ . '/Controllers/' . $classFile;
    } elseif (file_exists(__DIR__ . '/Managers/' . $classFile)) {
    require __DIR__ . '/Managers/' . $classFile;
    } else {
        // If the class file doesn't exist, throw an error
        throw new Exception("Class $className not found in " . __DIR__ . '/Controllers/' . $classFile, 500, null);
    }
});