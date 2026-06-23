<?php

namespace Core;

class Autoloader
{
    /**
     * Enregistre l'autoloader dans la pile d'autoload
     */
    public static function register()
    {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    /**
     * Autoload les classes en fonction de leur namespace
     * @param string $class Le nom complet de la classe avec son namespace
     */
    public static function autoload($class)
    {
        // Convertir le namespace en chemin du fichier
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);

        // Chemin du fichier relatif à la racine du projet
        $root = dirname(__DIR__);
        $file = $root . DIRECTORY_SEPARATOR . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
        } else {
            // Optionnel : Lever une exception si le fichier n'est pas trouvé
            throw new \Exception("Impossible de charger la classe : $class");
        }
    }
}
