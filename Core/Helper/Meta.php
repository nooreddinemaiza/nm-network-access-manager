<?php

declare(strict_types=1);

namespace Core\Helper;

use DateTime;
use Core\System\Config;
use Core\ViewEngine\View;

/**
 * Gestionnaire de métadonnées HTML avancé
 * Support pour Open Graph, Twitter Cards, JSON-LD, et plus
 */
class Meta
{
    private array $data = [
        'title' => '',
        'name' => [],
        'properties' => [],
        'twitter' => [],
        'rel' => [],
        'jsonLd' => [],
        'httpEquiv' => [],
    ];

    private array $config = [
        'titleSuffix' => '',
        'titlePrefix' => '',
        'titleSeparator' => ' | ',
        'defaultImage' => '',
        'siteName' => '',
        'locale' => 'fr_FR',
        'twitterSite' => '',
    ];

    public function __construct(array $defaults = [], array $config = [])
    {
        $this->data = array_merge_recursive($this->data, $defaults);
        $this->config = array_merge($this->config, $config);

        $this->setDefaultMetas();
    }

    // === Configuration ===

    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    public function getConfig(?string $key = null)
    {
        return $key ? ($this->config[$key] ?? null) : $this->config;
    }

    // === Fluent Setters ===

    public function setTitle(string $title): self
    {
        $cleanTitle = strip_tags($title);
        $fullTitle = $this->buildFullTitle($cleanTitle);

        $this->data['title'] = ucfirst($fullTitle);
        $this->data['properties']['og:title'] = $fullTitle;
        $this->data['twitter']['twitter:title'] = $fullTitle;

        return $this;
    }

    public function setDescription(string $description): self
    {
        $description = $this->truncateText(strip_tags($description), 160);
        $this->data['name']['description'] = $description;
        $this->data['properties']['og:description'] = $description;
        $this->data['twitter']['twitter:description'] = $description;
        return $this;
    }
    public function setAuthor(string $author): self
    {
        $this->data['name']['author'] = strip_tags($author);
        return $this;
    }
    public function setCSRF(string $csrftoken): self
    {
        $this->data['name']['csrf-token'] = $csrftoken;
        return $this;
    }

    public function setCanonical(string $url): self
    {
        $this->data['rel']['canonical'] = $this->validateUrl($url);
        $this->data['properties']['og:url'] = $this->validateUrl($url);
        return $this;
    }

    public function setImage(string $url, array $options = []): self
    {
        $validUrl = $this->validateUrl($url);
        $this->data['properties']['og:image'] = $validUrl;
        $this->data['twitter']['twitter:image'] = $validUrl;

        // Options supplémentaires pour l'image
        if (isset($options['width'])) {
            $this->data['properties']['og:image:width'] = (int)$options['width'];
        }
        if (isset($options['height'])) {
            $this->data['properties']['og:image:height'] = (int)$options['height'];
        }
        if (isset($options['alt'])) {
            $this->data['properties']['og:image:alt'] = strip_tags($options['alt']);
        }

        return $this;
    }

    public function setType(string $type): self
    {
        $this->data['properties']['og:type'] = $type;
        return $this;
    }

    public function setLocale(string $locale): self
    {
        $this->data['properties']['og:locale'] = $locale;
        $this->config['locale'] = $locale;
        return $this;
    }

    public function setSiteName(string $siteName): self
    {
        $this->data['properties']['og:site_name'] = strip_tags($siteName);
        $this->config['siteName'] = strip_tags($siteName);
        return $this;
    }

    public function setTwitterCard(string $cardType = 'summary_large_image'): self
    {
        $this->data['twitter']['twitter:card'] = $cardType;
        return $this;
    }

    public function setTwitterSite(string $handle): self
    {
        $handle = $this->formatTwitterHandle($handle);
        $this->data['twitter']['twitter:site'] = $handle;
        $this->config['twitterSite'] = $handle;
        return $this;
    }

    public function setTwitterCreator(string $handle): self
    {
        $this->data['twitter']['twitter:creator'] = $this->formatTwitterHandle($handle);
        return $this;
    }
    public function addRel(string $rel, string $href, array $attributes = []): self
    {
        $this->data['rel'][$rel] = [
            'href' => $this->validateUrl($href),
            'attributes' => $attributes
        ];
        return $this;
    }

    public function addJsonLd(array $jsonLd): self
    {
        // Validation basique du JSON-LD
        if (isset($jsonLd['@context']) || isset($jsonLd['@type'])) {
            $this->data['jsonLd'][] = $jsonLd;
        }
        return $this;
    }

    public function setRobots(string $robots): self
    {
        $validRobots = ['index', 'noindex', 'follow', 'nofollow', 'archive', 'noarchive', 'snippet', 'nosnippet'];
        $robotsArray = array_map('trim', explode(',', $robots));
        $robotsArray = array_filter($robotsArray, fn($r) => in_array(strtolower($r), $validRobots));

        $this->data['name']['robots'] = implode(', ', $robotsArray);
        return $this;
    }

    public function setCharset(string $charset = 'UTF-8'): self
    {
        $this->data['name']['charset'] = $charset;
        return $this;
    }
    public function addMeta(string $name, string $value): self
    {
        $this->data['name'][$name] = $value;
        return $this;
    }

    public function setViewport(string $viewport = 'width=device-width, initial-scale=1.0'): self
    {
        $this->data['name']['viewport'] = $viewport;
        return $this;
    }

    public function setRefresh(int $seconds, string $url = ''): self
    {
        $content = (string)$seconds;
        if ($url) {
            $content .= '; url=' . $this->validateUrl($url);
        }
        $this->data['httpEquiv']['refresh'] = $content;
        return $this;
    }

    public function setContentSecurityPolicy(string $policy): self
    {
        $this->data['httpEquiv']['Content-Security-Policy'] = $policy;
        return $this;
    }

    // === Méthodes spécialisées ===

    public function addBreadcrumbJsonLd(array $breadcrumbs): self
    {
        $items = [];
        foreach ($breadcrumbs as $index => $breadcrumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $breadcrumb['title'],
                'item' => Config::get('app.url') . $breadcrumb['url'] ?? null
            ];
        }

        $this->addJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        ]);

        return $this;
    }

    public function addArticleJsonLd(array $article): self
    {
        $published_at = (new DateTime($article['published_at']))->format(DateTime::ATOM);
        $updated_at   = (new DateTime($article['updated_at']))->format(DateTime::ATOM);

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article['title'] ?? '',
            'description' => $article['meta_description'] ?? $article['excerpt'] ?? '',
            'datePublished' => $published_at ?? '',
            'dateModified' => $updated_at  ?? $published_at ?? '',
            'author' => [
                '@type' => 'Person',
                'name' => $article['creator']['fullname'] ?? ''
            ]
        ];

        if (isset($article['image'])) {
            $jsonLd['image'] = $article['image'];
        }

        $jsonLd['publisher'] = [
            '@type' => 'Organization',
            'name' => View::getShared('name') ?? '',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => View::getShared('infos')['logo'] ?? ''
            ]
        ];

        $this->addJsonLd($jsonLd);
        return $this;
    }
    public function addProductJsonLd(array $product): self
    {
        // Déterminer le prix actuel en tenant compte de la réduction
        $hasReduction = (
            isset($product['reduction'], $product['apply_reduction_on']) &&
            floatval($product['reduction']) > 0 &&
            floatval($product['apply_reduction_on']) > 0
        );

        $finalPrice = $hasReduction ? floatval($product['apply_reduction_on']) : floatval($product['price']);

        // Images (doit être un tableau d'URLs absolues)
        $images = is_array($product['images']) ? $product['images'] : [];

        // Catégories (optionnelles)
        $categories = [];
        foreach (is_array($product['categories']) ? $product['categories'] : [] as $category) {
            $categories[] = $category['title'];
        }

        // Construction du JSON-LD
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product['title'] ?? '',
            'description' => $product['meta_description'] ?? $product['description'] ?? '',
            'sku' => (string)($product['sku'] ?? ''),
            'mpn' => (string)($product['sku'] ?? ''),
            'brand' => [
                '@type' => 'Brand',
                'name' => $product['brand'] ?? View::getShared('name') ?? ''
            ],
            'category' => implode(', ', $categories),
            'image' => $images,
            'offers' => [
                '@type' => 'Offer',
                'url' => Config::get('app.url') . ($product['slug'] ?? ''),
                'priceCurrency' => View::getShared('infos')['currency'] ?? 'MAD',
                'price' => number_format($finalPrice, 2, '.', ''),
                'priceValidUntil' => date('Y-m-d', strtotime('+6 months')),
                'itemCondition' => 'https://schema.org/NewCondition',
                'availability' => 'https://schema.org/InStock',
                'seller' => [
                    '@type' => 'Organization',
                    'name' => View::getShared('name') ?? ''
                ]
            ]
        ];

        // GTIN / identifier_exists
        if (!empty($product['gtin'])) {
            $length = strlen($product['gtin']);
            if ($length === 13) {
                $jsonLd['gtin13'] = $product['gtin'];
            } elseif ($length === 12) {
                $jsonLd['gtin12'] = $product['gtin'];
            } elseif ($length === 8) {
                $jsonLd['gtin8'] = $product['gtin'];
            }
            $jsonLd['identifier_exists'] = true;
        } else {
            // Produit sans GTIN officiel
            $jsonLd['identifier_exists'] = false;
        }

        $this->addJsonLd($jsonLd);
        return $this;
    }

    public function addOrganizationJsonLd(array $organization): self
    {
        $this->addJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $organization['name'],
            'url' => $organization['url'] ?? '',
            'logo' => $organization['logo'] ?? '',
            'description' => $organization['description'] ?? '',
            'address' => $organization['address'] ?? [],
            'contactPoint' => $organization['contactPoint'] ?? []
        ]);

        return $this;
    }

    // === Getters ===

    public function get(): array
    {
        return $this->data;
    }

    public function getTitle(): string
    {
        return $this->data['title'];
    }

    public function getDescription(): string
    {
        return $this->data['name']['description'] ?? '';
    }

    // === Render ===

    public function render(): string
    {
        $html = [];

        // Title
        if (!empty($this->data['title'])) {
            $html[] = "<title>{$this->data['title']}</title>";
        }

        // Charset (doit être en premier)
        if (isset($this->data['name']['charset'])) {
            $html[] = '<meta charset="' . $this->escape($this->data['name']['charset']) . '">';
            unset($this->data['name']['charset']);
        }

        // HTTP-Equiv
        foreach ($this->data['httpEquiv'] as $httpEquiv => $content) {
            if (!empty($content)) {
                $html[] = '<meta http-equiv="' . $this->escape($httpEquiv) . '" content="' . $this->escape($content) . '">';
            }
        }

        // Meta name
        foreach ($this->data['name'] as $name => $content) {
            if (!empty($content)) {
                $html[] = '<meta name="' . $this->escape($name) . '" content="' . $this->escape($content) . '">';
            }
        }

        // // Meta properties (Open Graph)
        // foreach ($this->data['properties'] as $property => $content) {
        //     if (empty($content) && $content !== 0 && $content !== '0') continue;

        //     if (is_array($content)) {
        //         foreach ($content as $c) {
        //             $html[] = '<meta property="' . $this->escape($property) . '" content="' . $this->escape($c) . '">';
        //         }
        //     } else {
        //         $html[] = '<meta property="' . $this->escape($property) . '" content="' . $this->escape($content) . '">';
        //     }
        // }

        // // Twitter
        // foreach ($this->data['twitter'] as $name => $content) {
        //     if (!empty($content)) {
        //         $html[] = '<meta name="' . $this->escape($name) . '" content="' . $this->escape($content) . '">';
        //     }
        // }

        // Rel links
        foreach ($this->data['rel'] as $rel => $data) {
            if (is_string($data)) {
                // Rétrocompatibilité
                $html[] = '<link rel="' . $this->escape($rel) . '" href="' . $this->escape($data) . '">';
            } elseif (is_array($data) && !empty($data['href'])) {
                $attributes = '';
                if (!empty($data['attributes'])) {
                    foreach ($data['attributes'] as $attr => $value) {
                        $attributes .= ' ' . $this->escape($attr) . '="' . $this->escape($value) . '"';
                    }
                }
                $html[] = '<link rel="' . $this->escape($rel) . '" href="' . $this->escape($data['href']) . '"' . $attributes . '>';
            }
        }
        // Regrouper tous les JSON-LD dans un seul script
        if (!empty($this->data['jsonLd'])) {
            $html[] = '<script type="application/ld+json">' .
                json_encode($this->data['jsonLd'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) .
                '</script>';
        }

        return implode("\n", $html);
    }

    public function __toString(): string
    {
        return $this->render();
    }

    // === Méthodes privées ===

    private function setDefaultMetas(): void
    {
        $this->setCharset();
        $this->setViewport();

        if ($this->config['siteName']) {
            $this->setSiteName($this->config['siteName']);
        }

        if ($this->config['twitterSite']) {
            $this->setTwitterSite($this->config['twitterSite']);
        }
        $this->setLocale($this->config['locale']);
        $this->setTwitterCard();
        $this->setType('website');
    }

    private function buildFullTitle(string $title): string
    {
        $fullTitle = $this->config['titlePrefix'] . $title;

        if ($this->config['titleSuffix']) {
            $fullTitle .= $this->config['titleSeparator'] . ucfirst(strtolower($this->config['titleSuffix']));
        }

        return $fullTitle;
    }

    private function truncateText(string $text, int $maxLength): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }

        return substr($text, 0, $maxLength - 3) . '...';
    }

    private function validateUrl(string $url): string
    {
        // Validation basique d'URL
        if (filter_var($url, FILTER_VALIDATE_URL) === false && !str_starts_with($url, '/')) {
            throw new \InvalidArgumentException("URL invalide : {$url}");
        }

        return $url;
    }

    private function formatTwitterHandle(string $handle): string
    {
        $handle = trim($handle);
        return str_starts_with($handle, '@') ? $handle : '@' . $handle;
    }

    private function escape(string|int|float $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // === Méthodes statiques utilitaires ===

    public static function fromArray(array $data, array $config = []): self
    {
        $meta = new self([], $config);

        if (isset($data['title'])) $meta->setTitle($data['title']);
        if (isset($data['description'])) $meta->setDescription($data['description']);
        if (isset($data['author'])) $meta->setAuthor($data['author']);
        if (isset($data['canonical'])) $meta->setCanonical($data['canonical']);
        if (isset($data['image'])) $meta->setImage($data['image']);
        if (isset($data['type'])) $meta->setType($data['type']);

        return $meta;
    }

    public function merge(Meta $other): self
    {
        $this->data = array_merge_recursive($this->data, $other->get());
        return $this;
    }

    public function reset(): self
    {
        $this->data = [
            'title' => '',
            'name' => [],
            'properties' => [],
            'twitter' => [],
            'rel' => [],
            'jsonLd' => [],
            'httpEquiv' => [],
        ];
        $this->setDefaultMetas();
        return $this;
    }
    // === Méthodes de suppression ===

    public function removeName(string $name): self
    {
        unset($this->data['name'][$name]);
        return $this;
    }

    public function removeProperty(string $property): self
    {
        unset($this->data['properties'][$property]);
        return $this;
    }

    public function removeTwitter(string $name): self
    {
        unset($this->data['twitter'][$name]);
        return $this;
    }

    public function removeRel(string $rel): self
    {
        unset($this->data['rel'][$rel]);
        return $this;
    }

    public function removeHttpEquiv(string $httpEquiv): self
    {
        unset($this->data['httpEquiv'][$httpEquiv]);
        return $this;
    }

    public function removeJsonLd(int $index): self
    {
        if (isset($this->data['jsonLd'][$index])) {
            array_splice($this->data['jsonLd'], $index, 1);
        }
        return $this;
    }

    public function removeJsonLdByType(string $type): self
    {
        $this->data['jsonLd'] = array_filter($this->data['jsonLd'], function ($jsonLd) use ($type) {
            return ($jsonLd['@type'] ?? '') !== $type;
        });
        // Réindexer le tableau
        $this->data['jsonLd'] = array_values($this->data['jsonLd']);
        return $this;
    }

    public function clearJsonLd(): self
    {
        $this->data['jsonLd'] = [];
        return $this;
    }

    public function removeTitle(): self
    {
        $this->data['title'] = '';
        return $this;
    }

    // Méthodes pour supprimer plusieurs propriétés d'un coup
    public function removeNames(array $names): self
    {
        foreach ($names as $name) {
            unset($this->data['name'][$name]);
        }
        return $this;
    }

    public function removeProperties(array $properties): self
    {
        if (!count($properties)) {
            unset($this->data['properties']);
        }
        foreach ($properties as $property) {
            unset($this->data['properties'][$property]);
        }
        return $this;
    }

    public function removeTwitterMetas(?array $names = []): self
    {
        if (!$names) {
            unset($this->data['twitter']);
            return $this;
        }
        foreach ($names as $name) {
            unset($this->data['twitter'][$name]);
        }
        return $this;
    }

    public function removeRels(array $rels): self
    {
        foreach ($rels as $rel) {
            unset($this->data['rel'][$rel]);
        }
        return $this;
    }

    // Méthode générique pour supprimer n'importe quel type de meta
    public function removeMeta(string $type, string $key): self
    {
        if (isset($this->data[$type][$key])) {
            unset($this->data[$type][$key]);
        }
        return $this;
    }

    // Vider complètement une section
    public function clearNames(): self
    {
        $this->data['name'] = [];
        return $this;
    }

    public function clearProperties(): self
    {
        $this->data['properties'] = [];
        return $this;
    }

    public function clearTwitter(): self
    {
        $this->data['twitter'] = [];
        return $this;
    }

    public function clearRels(): self
    {
        $this->data['rel'] = [];
        return $this;
    }

    public function clearHttpEquiv(): self
    {
        $this->data['httpEquiv'] = [];
        return $this;
    }
}
