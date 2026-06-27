<?php

use App\Controllers\AdminController;
use App\Controllers\HomeController;
use App\Controllers\LinkController;
use App\Workers\MainWorker;
use Core\Helper\Helper;
use Core\Routing\Http\Request;
use Core\Routing\Router;

/**
 * @var Router $router
 */
$router->group(['prefix' => '/'], function () use ($router) {
    $router->get("", function () {
        $homeController = new HomeController();
        return $homeController->home();
    })->name('space.home');
    $router->get("about", function () {
        $homeController = new HomeController();
        return $homeController->about();
    })->name('space.home');
    $router->group(['prefix' => '/user'], function () use ($router) {
        $router->get('login', [HomeController::class, 'form'])->name('space.login');
        $userController = new HomeController;
        $router->post('login', function (Request $request) use ($userController) {
            return $userController->process($request);
        });
    });

    $router->group(['prefix' => '/invite'], function () use ($router) {
        $linkController = new LinkController;
        $router->get("", function (Request $request) use ($linkController) {
            return $linkController->invite($request);
        })->name('invite.home');
        $router->post("", function (Request $request) use ($linkController) {
            return $linkController->request($request);
        })->name('invite.post');
    });
    $router->group(['prefix' => '/helper'], function () use ($router) {
        $router->get("/time", function (Request $request) {
            return Helper::getServerTime();
        })->name('helper.post')->middleware('auth_post');
    });
    $router->get('/logout', [AdminController::class, 'logout'])->name('dashboard.logout');
    $router->get('/login', [AdminController::class, 'loginForm'])->name('login.page');
});

$router->group(['prefix' => '/workers'], function () use ($router) {
    $worker = new MainWorker();
    $router->post("main", function (Request $request) use ($worker) {
        return $worker->create($request);
    })->name('worker');
});
