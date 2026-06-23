<?php

use App\Controllers\AdminController;
use App\Controllers\GroupController;
use App\Controllers\LinkController;
use App\Controllers\Policies\ItemController;
use App\Controllers\Policies\PolicieController;
use App\Controllers\StatisticController;
use App\Controllers\UserController;
use Core\Routing\Http\Request;
use Core\Routing\Http\Response;
use Core\Routing\Router;

/**
 * @var Router $router
 */
$router->group(['prefix' => '/dashboard'], function () use ($router) {
    $adminController = new AdminController;
    $router->group(['prefix' => '/login'], function () use ($router, $adminController) {
        $router->get('', [AdminController::class, 'loginForm'])->name('login.page');
        $router->post('', function (Request $request) use ($adminController) {
            return $adminController->login($request);
        });
    });
    $router->group(['prefix' => ''], function () use ($router) {
        $router->get('', [AdminController::class, 'dashboard'])->name('dashboard.page');
    });
    $router->group(['prefix' => '/managers', 'middleware' => ['auth_post', 'isRoot']], function () use ($router, $adminController) {
        $router->post('/list', function (Request $request) use ($adminController) {
            return $adminController->listModerators($request);
        })->name('managers.list');
        $router->post('/add', function (Request $request) use ($adminController) {
            return $adminController->createModerator($request);
        })->name('managers.add');
        $router->post('/edit', function (Request $request) use ($adminController) {
            return $adminController->editModerator($request);
        })->name('managers.edit');
        $router->post('/remove', function (Request $request) use ($adminController) {
            return $adminController->removeModerator($request);
        })->name('managers.remove');
        $router->post('/toggle-status', function (Request $request) use ($adminController) {
            return $adminController->toggleModeratorStatus($request);
        })->name('managers.list');
        $router->post('/switch-user-group', function (Request $request) {
            $userController = new UserController;
            return $userController->switchGroup($request);
        })->name('users.switch-group');
    });
    $router->group(['prefix' => '/profile'], function () use ($router, $adminController) {
        $router->post('/data', function (Request $request) use ($adminController) {
            return $adminController->profile($request);
        })->name('dashboard.profile.data');
        $router->post('/edit', function (Request $request) use ($adminController) {
            return $adminController->editProfile($request);
        })->name('dashboard.profile.edit');
        $router->post('/reset-password', function (Request $request) use ($adminController) {
            return $adminController->resetPassword($request);
        })->name('dashboard.profile.reset-password');
    });
    $router->group(['prefix' => '/users', 'middleware' => ['auth_post',]], function () use ($router) {
        $userController = new UserController;
        $router->post('/listed', function (Request $request) use ($userController) {
            return $userController->listed($request);
        })->name('users.listed');
        $router->post('/add', function (Request $request) use ($userController) {
            return $userController->add($request);
        })->name('users.add');
        $router->post('/edit', function (Request $request) use ($userController) {
            return $userController->edit($request);
        })->name('users.edit');
        $router->post('/remove', function (Request $request) use ($userController) {
            return $userController->remove($request);
        })->name('users.remove');
        $router->post('/toggle-status', function (Request $request) use ($userController) {
            return $userController->toggleStatus($request);
        })->name('users.toggle-status');
        $router->post('/update-expiry', function (Request $request) use ($userController) {
            return $userController->expire($request);
        })->name('users.update-expiry');
        $router->post('/disconnect', function (Request $request) use ($userController) {
            return $userController->disconnect($request);
        })->name('users.disconnect');
    });
    $router->post('/groups/list-for-use', function (Request $request) {
        $groupController = new GroupController;
        $result = $groupController->getGroups($request);
        $result = $result ? [
            'success' => true,
            'data' => $result
        ] : [
            'success' => false,
        ];
        return Response::json($result);
    });
    $router->group(['prefix' => '/groups', 'middleware' => ['auth_post', 'isRoot']], function () use ($router) {
        $groupController = new GroupController;
        $router->post('/list', function (Request $request) use ($groupController) {
            return $groupController->get($request);
        })->name('groups.list');
        $router->post('/switch-moderator', function (Request $request) use ($groupController) {
            return $groupController->switchModerator($request);
        })->name('users.switch-group');
        $router->put('/add', function (Request $request) use ($groupController) {
            return $groupController->add($request);
        })->name('groups.add');
        $router->post('/edit', function (Request $request) use ($groupController) {
            return $groupController->edit($request);
        })->name('groups.edit');
        $router->post('/remove', function (Request $request) use ($groupController) {
            return $groupController->remove($request);
        })->name('groups.remove');
    });
    $router->group(['prefix' => '/links', 'middleware' => ['auth_post']], function () use ($router) {
        $linkController = new LinkController;
        $router->post('/list', function (Request $request) use ($linkController) {
            return $linkController->list($request);
        })->name('links.list');
        $router->post('/add', function (Request $request) use ($linkController) {
            return $linkController->add($request);
        })->name('links.add');
        $router->post('/edit', function (Request $request) use ($linkController) {
            return $linkController->edit($request);
        })->name('links.edit');
        $router->post('/remove', function (Request $request) use ($linkController) {
            return $linkController->remove($request);
        })->name('links.remove');
        $router->post('/toggle-status', function (Request $request) use ($linkController) {
            return $linkController->toggleStatus($request);
        })->name('links.toggle-status');
    });
    $router->group(['prefix' => '/stats', 'middleware' => ['auth_post']], function () use ($router) {
        $statisticController = new StatisticController;
        $router->post('/list', fn(Request $r) => $statisticController->list($r))->name('stats.list');
        $router->post('/groups', fn(Request $r) => $statisticController->groups($r))->name('stats.groups');
        $router->post('/totals', fn(Request $r) => $statisticController->totals($r))->name('stats.totals');
        $router->post('/sites', fn(Request $r) => $statisticController->sites($r))->name('stats.sites');
        $router->post('/delete', fn(Request $r) => $statisticController->delete($r))->name('stats.delete');
    });
    $router->group(['prefix' => '/policies', 'middleware' => ['auth_post', 'isRoot']], function () use ($router) {
        $router->group(['prefix' => ''], function () use ($router) {
            $policieController = new PolicieController;
            $router->post('/list', function (Request $request) use ($policieController) {
                return $policieController->list($request);
            })->name('policies.list');
            $router->put('/add', function (Request $request) use ($policieController) {
                return $policieController->add($request);
            })->name('policies.add');
            $router->put('/edit', function (Request $request) use ($policieController) {
                return $policieController->edit($request);
            })->name('policies.edit');
            $router->post('/remove', function (Request $request) use ($policieController) {
                return $policieController->remove($request);
            })->name('policies.remove');
            $router->put('/toggle-status', function (Request $request) use ($policieController) {
                return $policieController->toggleStatus($request);
            })->name('policies.toggle-status');
        });
        $router->group(['prefix' => '/items'], function () use ($router) {
            $policieItemController = new ItemController;
            $router->post('/get', function (Request $request) use ($policieItemController) {
                return $policieItemController->get($request);
            })->name('policies.items.get');
            $router->post('/edit', function (Request $request) use ($policieItemController) {
                return $policieItemController->edit($request);
            })->name('policies.items.edit');
        });
        $router->group(['prefix' => '/user'], function () use ($router) {
            $policieController = new PolicieController;
            $router->post('/get', function (Request $request) use ($policieController) {
                return $policieController->getUserPolicies($request);
            })->name('policies.user.get');
            $router->post('/set', function (Request $request) use ($policieController) {
                return $policieController->setUserPolicies($request);
            })->name('policies.user.set');
        });
        $router->group(['prefix' => '/group'], function () use ($router) {
            $policieController = new PolicieController;
            $router->post('/get', function (Request $request) use ($policieController) {
                return $policieController->getGroupPolicies($request);
            })->name('policies.group.get');
            $router->post('/set', function (Request $request) use ($policieController) {
                return $policieController->setGroupPolicies($request);
            })->name('policies.group.set');
        });
    });
});
