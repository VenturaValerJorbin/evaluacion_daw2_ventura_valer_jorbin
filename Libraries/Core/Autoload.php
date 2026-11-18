<?php

// Busca la clase solicitada en el nucleo, los controladores y los modelos, en ese orden
spl_autoload_register(function ($className) {
    foreach (["Libraries/Core/", "Controllers/", "Models/"] as $carpeta) {
        $filePath = "{$carpeta}{$className}.php";
        if (file_exists($filePath)) {
            require_once $filePath;
            return;
        }
    }
});
