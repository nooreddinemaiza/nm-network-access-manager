<?php

use App\Workers\MainWorker;
use Core\File;
use Core\Helper\AssetManager;
use Core\Logger;
use Core\Routing\Http\Request;
use Core\Routing\Http\Response;
use Core\Routing\Router;

/**
 * @var Router $router
 */
$router->group(['prefix' => '/Assets'], function () use ($router) {
    $router->get('{label}/admin/{filename}', function (Request $request, string $label, string $filename) {
        return AssetManager::serveAdmin($request, $label, $filename);
    })->middleware('admin_asset');
    $router->get('{label}/{filename}', function (Request $request, string $label, string $filename) {
        
            Logger::debug("AssetManager::serve() label={$label} file={$filename}");
            Logger::debug("File exists: " . (File::exists($label, $filename) ? 'yes' : 'no'));
            Logger::debug("File path: " . File::getPath($label, $filename));
        $filename = basename($filename);
        $version = $request->query('v');
        if ($version) {
            $info = AssetManager::getInfo($label, $filename);
            if (!empty($info) && $info['modified'] != $version) {
                $newUrl = AssetManager::url($label, $filename, true);
                return Response::redirect($newUrl, 301);
            }
        }
        return AssetManager::serve($label, $filename);
    });

    $router->get('/download/{label}/{filename}', function (Request $request, string $label, string $filename) {
        $filename = basename($filename);
        return AssetManager::download($label, "$filename");
    })->middleware('auth');
});

