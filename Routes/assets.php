<?php

use App\Workers\MainWorker;
use Core\Helper\AssetManager;
use Core\Routing\Http\Request;
use Core\Routing\Http\Response;
use Core\Routing\Router;

/**
 * @var Router $router
 */
$router->group(['prefix' => '/Assets'], function () use ($router) {
    $router->get('{label}/{filename}', function (Request $request, string $label, string $filename) {
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
    $router->get('{label}/admin/{filename}', function (Request $request, string $label, string $filename) {
        $filename = basename($filename);
        return AssetManager::serve($label, "admin/$filename");
    })->middleware('auth');
    $router->get('/download/{label}/{filename}', function (Request $request, string $label, string $filename) {
        $filename = basename($filename);
        return AssetManager::download($label, "$filename");
    });
});
$router->group(['prefix' => '/workers'], function () use ($router) {
    $worker = new MainWorker();
    $router->post("main", function (Request $request) use ($worker) {
        return $worker->create($request);
    })->name('worker');
});
