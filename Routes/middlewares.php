<?php

use Core\Routing\Http\Request;
use Core\Routing\Http\Response;
use Core\Routing\RouteException;
use Core\System\Session;
use Core\Routing\Router;

/**
 * @var Router $router
 */
$router->middleware('auth', function (Request $request, Closure $next) {
    // Vérifier si l'utilisateur est déjà connecté
    if (!Session::isAuthenticated()) {
        return Response::redirect('/login', 302);
    }
    return $next($request);
});
$router->middleware('auth_post', function (Request $request, Closure $next) {
    // Vérifier si l'utilisateur est déjà connecté
    if (!Session::isAuthenticated()) {
        return Response::json([
            'status' => 'session_timed_out',
            'success' => false,
            'message' => 'Votre session est terminée, veuillez vous reconnecter !'
        ], 302);
    }
    return $next($request);
});
$router->middleware('isRoot', function (Request $request, Closure $next) {
    $root = Session::getUserType() === 'root';
    if (!$root) {
        return RouteException::handleForbidden($request);
    }
    return $next($request);
});
