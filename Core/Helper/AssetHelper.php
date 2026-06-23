<?php

declare(strict_types=1);

namespace Core\Helper;


/**
 * Classe helper pour faciliter l'utilisation des assets dans les vues
 */
class AssetHelper
{
    /**
     * Génère les balises CSS pour une page
     */
    public static function styles(array $assets): string
    {
        $html = '';
        foreach ($assets as $asset) {
            if (is_array($asset)) {
                $label = $asset['label'];
                $filename = $asset['file'];
                $attributes = $asset['attributes'] ?? [];
            } else {
                // Format: "label:filename"
                [$label, $filename] = explode(':', $asset, 2);
                $attributes = [];
            }

            $html .= AssetManager::css($label, $filename, $attributes) . "\n";
        }
        return $html;
    }

    /**
     * Génère les balises JS pour une page
     */
    public static function scripts(array $assets): string
    {
        $html = '';
        foreach ($assets as $asset) {
            if (is_array($asset)) {
                $label = $asset['label'];
                $filename = $asset['file'];
                $attributes = $asset['attributes'] ?? [];
            } else {
                [$label, $filename] = explode(':', $asset, 2);
                $attributes = [];
            }

            $html .= AssetManager::js($label, $filename, $attributes) . "\n";
        }
        return $html;
    }

    /**
     * Génère un bundle d'assets
     */
    public static function bundle(string $name, array $config): string
    {
        $html = '';

        // CSS
        if (isset($config['css'])) {
            $html .= self::styles($config['css']);
        }

        // JS
        if (isset($config['js'])) {
            $html .= self::scripts($config['js']);
        }

        return $html;
    }
}
