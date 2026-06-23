<?php


namespace App\Controllers;

use App\Models\User;
use Core\Controllers\Controller;
use Core\Exception\ConnectionException;
use Core\Exception\CSRFExeption;
use Core\Exception\ValidationException;
use Core\Helper\Data;
use Core\Routing\Http\Request;
use Core\Routing\Http\Response;
use Core\Routing\RouteException;
use Core\System\CSRF;
use Core\System\Session;
use Core\ViewEngine\View;

class HomeController extends Controller
{
    private User $model;
    private const MAX_LOGIN_ATTEMPTS = 50;
    private const LOGIN_BLOCK_DURATION = 900; // 15 minutes
    public function __construct()
    {
        $this->model = new User();
    }
    public function home(): Response
    {
        $this->data = [
            'view' => 'home.php',
            'label' => 'views',
            'message' => $this->data['message'] ?? "",
            'portal_link' => "http://pfsense.idosr.net:8002/index.php?zone=ista",
            'csrf_token' => $this->data['csrf_token'] ?? CSRF::generateToken(),
            'xData' => "{menuOpen: false }",
        ];
        return View::response(
            'views',
            'home.php',
            $this->data
        );
    }
    public function process(Request $request)
    {
        try {
            $this->data['old'] = $request->all();
            $data = Data::create($request->all())
                ->only([
                    'csrf_token',
                    'username',
                    'password',
                ])->sanitize();

            // Vérifier la limitation de tentatives
            $attemptKey = 'login:' . ($data['username'] ?? 'unknown');
            $attemptStatus = Session::incrementAttempt(
                $attemptKey,
                self::MAX_LOGIN_ATTEMPTS,
                self::LOGIN_BLOCK_DURATION
            );

            if ($attemptStatus['blocked']) {
                $remainingMinutes = ceil($attemptStatus['remaining_time'] / 60);
                throw new ValidationException(errors: [
                    'global' => [
                        "Trop de tentatives de connexion. Veuillez réessayer dans {$remainingMinutes} minute(s)."
                    ]
                ]);
            }
            Data::addLabel([
                'username' => "Nom d'utilisateur",
                'password' => 'mot de passe',
            ]);
            $errors = $data->validate([
                'csrf_token' => 'required|string',
                'username' => 'required|username',
                'password' => 'required|min:6',
            ]);

            if ($errors) {
                throw new ValidationException(errors: $errors);
            }

            if (!CSRF::validateToken($data['csrf_token'])) {
                throw new CSRFExeption();
            }

            $result = $this->model->connect($data->except(['csrf_token']));
            $result = $this->model->statistic($data['username']);
            $this->data['user'] = $result;
            $this->data['portal_link'] = "http://pfsense.idosr.net:8002/index.php?zone=ista";
            $this->data['client_ip'] = $request->ip();
            return View::response(
                'users_views',
                'dashboard.php',
                $this->data
            );
        } catch (ValidationException $e) {
            $this->data['errors']['global'] = $e->getErrors();
            return $this->form();
        } catch (CSRFExeption $e) {
            $this->data['errors']['csrf'] = $e->getErrors();
            $this->data['errors']['csrf'][] = 'Veuillez recharger la page!';
            return $this->form();
        } catch (ConnectionException $e) {
            $this->data['errors']['credentials'] = $e->getErrors();
            return $this->form();
        } catch (RouteException $e) {
            return RouteException::handleDesactivated($request);
        }
    }
    public function form(): Response
    {
        $this->data['type'] = 'text';
        $this->data['type_label'] = 'Nom d\'utilisateur';
        $this->data['type_name'] = 'username';
        $this->data['action'] = '/user/login';
        $this->data['title'] = 'Connexion utilisateur';
        $this->data['type_notification'] = "Nom d'utilisateur non valide";
        $this->data['csrf_token'] =  $this->data['csrf_token'] ?? CSRF::generateToken();
        return $this->view();
    }
}
