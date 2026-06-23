<?php

namespace Core\ViewEngine;

use Core\File;
use Core\Helper\Data;
use Core\Helper\Meta;
use RuntimeException;
use InvalidArgumentException;
use Core\Routing\Http\Response;

/**
 * Classe View pour gérer les vues PHP natives
 * Utilise la classe File pour toutes les interactions avec les fichiers
 * Support des layouts, partials et composants avec notation (label, filename)
 */
class View
{
    protected Data $data;
    protected ?string $layout = null;
    protected string $layoutLabel = '';
    protected array $sections = [];
    protected array $stack = [];
    protected bool $rendering = false;
    protected static array $sharedData = [];
    protected static array $globalData = [];
    protected static Meta $meta;

    public function __construct()
    {
        $this->data = Data::create();
    }

    /**
     * Factory method pour créer une instance View
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Rend une vue et retourne le contenu
     */
    public static function render(string $label, string $filename, array $data = []): string
    {
        $view = new self();
        return $view->make($label, $filename, $data)->getContent();
    }

    /**
     * Rend une vue et retourne une Response
     */
    public static function response(string $label, string $filename, array $data = [], int $status = 200, array $headers = []): Response
    {
        $content = self::render($label, $filename, $data);
        return Response::view($content, $status, $headers);
    }

    /**
     * Crée une vue avec des données
     */
    public function make(string $label, string $filename, array $data = []): self
    {
        $this->data = Data::create(array_merge(
            self::$globalData,
            self::$sharedData,
            $data
        ));

        if ($this->rendering) {
            throw new RuntimeException('Cannot render view while already rendering');
        }

        $this->rendering = true;

        try {
            $content = $this->renderView($label, $filename);

            // Si un layout est défini, on l'applique
            if ($this->layout !== null) {
                $content = $this->renderLayout($content);
            }

            $this->data->set('content', $content);
        } finally {
            $this->rendering = false;
        }

        return $this;
    }

    /**
     * Définit un layout
     */
    public function layout(string $label, string $filename): self
    {
        $this->layout = $filename;
        $this->layoutLabel = $label;
        return $this;
    }

    /**
     * Ajoute des données à la vue
     */
    public function with(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->data->set($k, $v);
            }
        } else {
            $this->data->set($key, $value);
        }
        return $this;
    }

    /**
     * Partage des données avec toutes les vues
     */
    public static function share(string|array $key, mixed $value = null): void
    {
        if (is_array($key)) {
            self::$sharedData = array_merge(self::$sharedData, $key);
        } else {
            self::$sharedData[$key] = $value;
        }
    }

    public static function setMeta(Meta $meta): void
    {
        self::$meta = $meta;
    }
    public static function meta(): Meta
    {
        return self::$meta ?? new Meta();
    }

    /**
     * Ajoute des données globales
     */
    public static function addGlobal(string|array $key, mixed $value = null): void
    {
        if (is_array($key)) {
            self::$globalData = array_merge(self::$globalData, $key);
        } else {
            self::$globalData[$key] = $value;
        }
    }

    /**
     * Inclut une vue partielle
     */
    public function include(string $label, string $filename, array $data = []): string
    {
        $mergedData = array_merge($this->data->all(), $data);
        return $this->renderViewFile($label, $filename, $mergedData);
    }

    /**
     * Inclut une vue partielle si elle existe
     */
    public function includeIf(string $label, string $filename, array $data = []): string
    {
        if (File::exists($label, $filename)) {
            return $this->include($label, $filename, $data);
        }
        return '';
    }

    /**
     * Inclut une vue partielle seulement si une condition est vraie
     */
    public function includeWhen(bool $condition, string $label, string $filename, array $data = []): string
    {
        if ($condition) {
            return $this->include($label, $filename, $data);
        }
        return '';
    }

    /**
     * Démarre une section
     */
    public function section(string $name): void
    {
        if (in_array($name, $this->stack)) {
            throw new InvalidArgumentException("Section '$name' is already being rendered");
        }

        $this->stack[] = $name;
        ob_start();
    }

    /**
     * Termine une section
     */
    public function endSection(): void
    {
        if (empty($this->stack)) {
            throw new RuntimeException('No section to end');
        }

        $name = array_pop($this->stack);
        $content = ob_get_clean();
        $this->sections[$name] = $content;
    }

    /**
     * Affiche le contenu d'une section
     */
    public function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * Démarre une section avec du contenu par défaut
     */
    public function show(string $name, string $default = ''): string
    {
        return $this->yield($name, $default);
    }

    /**
     * Empile du contenu dans une section
     */
    public function push(string $name): void
    {
        $this->section($name);
    }

    /**
     * Termine l'empilement dans une section
     */
    public function endPush(): void
    {
        $this->endSection();
    }

    /**
     * Affiche le contenu empilé d'une section
     */
    public function stack(string $name): string
    {
        return $this->yield($name);
    }

    /**
     * Échapper des données pour l'affichage HTML
     */
    public function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Alias pour escape
     */
    public function e(string $value): string
    {
        return $this->escape($value);
    }

    /**
     * Obtient le contenu rendu
     */
    public function getContent(): string
    {
        return $this->data->get('content', '');
    }

    /**
     * Rend la vue vers une string
     */
    public function __toString(): string
    {
        return $this->getContent();
    }

    /**
     * Rend le fichier de vue principal
     */
    protected function renderView(string $label, string $filename): string
    {
        return $this->renderViewFile($label, $filename, $this->data->all());
    }

    /**
     * Rend le layout avec le contenu
     */
    protected function renderLayout(string $content): string
    {
        $layoutData = array_merge($this->data->all(), [
            'content' => $content,
            'sections' => $this->sections
        ]);

        return $this->renderViewFile($this->layoutLabel, $this->layout, $layoutData);
    }

    /**
     * Rend un fichier de vue avec des données
     */
    protected function renderViewFile(string $label, string $filename, array $data): string
    {
        if (!File::exists($label, $filename)) {
            throw new RuntimeException("View file not found: $label/$filename");
        }

        // Extraction des variables pour la vue
        extract($data, EXTR_SKIP);

        // Rendre $this disponible dans les vues pour les méthodes helper
        $view = $this;

        ob_start();

        try {
            $filePath = File::getPath($label, $filename);
            include $filePath;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new RuntimeException("Error rendering view $label/$filename: " . $e->getMessage(), 0, $e);
        }

        return ob_get_clean();
    }

    /**
     * inclure un composant, partie ou n'import fichier
     */
    public function inc($label, $filename, $data = [])
    {
        $mergedData = array_merge($this->data->all(), $data);
        $mergedData['view'] = $this;
        File::include($label, $filename, $mergedData);
    }
    /**
     * Vérifie si une vue existe
     */
    public static function exists(string $label, string $filename): bool
    {
        return File::exists($label, $filename);
    }

    /**
     * Obtient toutes les données partagées
     */
    public static function getSharedData(): array
    {
        return self::$sharedData;
    }

    public static function getShared(string $key): mixed
    {
        return self::$sharedData[$key] ?? null;
    }

    /**
     * Obtient toutes les données globales
     */
    public static function getGlobalData(): array
    {
        return self::$globalData;
    }

    /**
     * Vide les données partagées
     */
    public static function clearSharedData(): void
    {
        self::$sharedData = [];
    }

    /**
     * Vide les données globales
     */
    public static function clearGlobalData(): void
    {
        self::$globalData = [];
    }

    /**
     * Composant réutilisable
     */
    public function component(string $label, string $filename, array $data = []): string
    {
        return $this->include($label, $filename, $data);
    }

    /**
     * Rend une collection d'éléments avec une vue
     */
    public function each(string $label, string $filename, array $items, string $itemVar = 'item', string $keyVar = 'key'): string
    {
        $output = '';
        foreach ($items as $key => $item) {
            $data = [
                $itemVar => $item,
                $keyVar => $key
            ];
            $output .= $this->include($label, $filename, $data);
        }
        return $output;
    }
}
