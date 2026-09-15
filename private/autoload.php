<?php
spl_autoload_register(/**
 * @throws Exception
 */
function ($className) {
    $classFile = ucfirst(str_replace('\\', '/', $className) . '.php');

    // Check if the class file exists in controllers or dataClasses
    if (file_exists(__DIR__ . '/controllers/' . $classFile)) {
        require __DIR__ . '/controllers/' . $classFile;
    } elseif (file_exists(__DIR__ . '/dataClasses/' . $classFile)) {
        require __DIR__ . '/dataClasses/' . $classFile;
    } else {
        // If the class file doesn't exist, throw an error
        throw new Exception("Class $className not found in " . __DIR__ . '/controllers/' . $classFile, 500, null);
    }
});