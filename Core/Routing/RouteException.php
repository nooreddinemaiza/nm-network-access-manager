<?php

namespace Core\Routing;

use Core\Helper\Meta;
use Core\System\Config;
use Core\ViewEngine\View;
use Core\Routing\Http\Request;
use Core\Routing\Http\Response;
use Exception;

class RouteException extends Exception
{

    // === Error Handlers ===

    /**
     * Handle 404 Not Found
     */
    public static function handleNotFound(Request $request): Response
    {
        if ($request->expectsJson()) {
            return Response::json([
                'success' => false,
                'error' => 'Not Found'
            ], 404);
        }
        $data = [
            'title' => 'Page non trouvée',
            'message' => 'Désolé, la page que vous recherchez n\'existe pas ou a été déplacée.',
            'error' => 404,
            'currentPage' => 'error_404',
            'has_header' => false,
            'has_footer' => false,
        ];
        return View::response('views', 'error.php', $data, 404);
    }
    public static function handleAssetNotFound(): Response
    {
        $data = [
            'title' => 'Fichier introuvable',
            'message' => 'Désolé, le fichier que vous recherchez n\'existe pas ou a été déplacé.',
            'error' => 404,
            'currentPage' => 'error_404',
            'has_header' => false,
            'has_footer' => false,
        ];
        return View::response('views', 'error.php', $data, 404);
    }

    /**
     * Handle 401 Unauthorized
     */
    public static function handleUnauthorized(Request $request): Response
    {
        if ($request->expectsJson()) {
            return Response::json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'Authentication is required to access this resource'
            ], 401);
        }

        $data = [
            'title' => 'Accès non autorisé!',
            'message' => 'Désolé, Vous n\'avez pas les permessions demandées pour consulter cette page!',
            'error' => 401,
            'currentPage' => 'error_401',
            'has_header' => false,
            'has_footer' => false,
        ];
        return View::response('views', 'error.php', $data, 401);
    }

    /**
     * Handle 403 Forbidden
     */
    public static function handleForbidden(Request $request): Response
    {
        if ($request->expectsJson()) {
            return Response::json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'Vous n\'avez pas les permissions nécessaires.'
            ], 403);
        }
        $data = [
            'title' => 'Forbidden',
            'message' => 'Vous n\'avez pas les permissions nécessaires.',
            'error' => 403,
            'has_header' => false,
            'has_footer' => false,
        ];
        return View::response('views', 'error.php', $data, 403);
    }

    /**
     * Handle 500 Internal Server Error
     */
    public static function handleInternalServerError(?Request $request = null, ?\Throwable $exception = null): Response
    {
        if ($request) {
            if ($request->expectsJson()) {
                $errorData = [
                    'success' => false,
                    'error' => 'Internal Server Error',
                    'message' => 'An unexpected error occurred'
                ];

                // Include exception details in development mode
                if ($exception && self::isDebugMode()) {
                    $errorData['debug'] = [
                        'message' => $exception->getMessage(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'trace' => $exception->getTraceAsString()
                    ];
                }

                return Response::json($errorData, 500);
            }
        }
        View::setMeta(new Meta());
        $data = [
            'title' =>  'Erreur interne',
            'message' => 'Une erreur est survenue, Veuillez essayer ulterieurement!',
            'error' => 500,
            'has_header' => false,
            'has_footer' => false,
        ];
        return View::response(
            label: 'views',
            filename: 'error.php',
            data: $data,
            status: 500
        );
    }

    /**
     * Handle 503 Service Unavailable
     */
    public static function handleServiceUnavailable(Request $request): Response
    {
        if ($request->expectsJson()) {
            return Response::json([
                'success' => false,
                'error' => 'Service Unavailable',
                'message' => 'The service is temporarily unavailable. Please try again later.'
            ], 503);
        }
        $data = [
            'title' =>  'Service Unavailable',
            'message' => 'Le service est tomporaireent inaccessible, Veuillez essayer ulterieurement!',
            'error' => 503,
            'has_header' => false,
            'has_footer' => false,
        ];
        return View::response('views', 'error.php', $data, 503);
    }
    /**
     * Handle 503 Service Unavailable
     */
    public static function handleDesactivated(Request $request): Response
    {
        if ($request->expectsJson()) {
            return Response::json([
                'success' => false,
                'error' => 'Votre Compte est désactivé!',
                'message' => 'Veuilelz contactez votre administrateur'
            ], 503);
        }
        $data = [
            'title' =>  'Compte bloqué',
            'message' => 'Votre Compte est désactivé! Veuilelz contactez votre administrateur',
            'error' => 'desactive_account',
            'has_header' => false,
            'has_footer' => false,
        ];
        return View::response('views', 'error.php', $data, 503);
    }
    public static function brokenInvite(Request $request, ?string $message = ''): Response
    {
        if ($request->expectsJson()) {
            return Response::json([
                'success' => false,
                'error' => 'Not Found'
            ], 404);
        }
        $meta = View::meta();
        $meta->setTitle($message ?? 'Lien d\'invitation introuvable');
        $data = [
            'title' => 'Lien d\'invitation introuvable',
            'message' => $message ?? 'Le lien utilisé est invalide',
            'error' => "invite",
            'meta' => $meta,
            'currentPage' => 'error_404',
            'has_header' => false,
            'has_footer' => false,
        ];
        return View::response('views', 'error.php', $data, 404);
    }


    /**
     * Check if application is in debug mode
     */
    protected static function isDebugMode(): bool
    {

        return Config::get('app.env') === 'development' || Config::get('app.env') === 'local';
    }
}
