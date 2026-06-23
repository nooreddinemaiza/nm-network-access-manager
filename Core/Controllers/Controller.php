<?php

namespace Core\Controllers;

use Core\System\CSRF;
use Core\System\Session;
use Core\ViewEngine\View;
use Core\Routing\Http\Response;
use Core\Exception\CSRFExeption;
use App\Controllers\GroupController;
use Core\Exception\ValidationException;

class Controller
{
    protected array $data;
    public function __construct()
    {
        $this->data = [];
    }
    protected function responseAjax(bool $success, array $errors = [], ?string $message = '')
    {
        $result = [
            'success' => $success,
            'message' => $message,
            'errors' => $errors,
        ];
        if ($message) {
            $result['message'] = $message;
        }
        if ($errors) {
            $result['errors'] = $errors;
        }
        return Response::json($result);
    }

    protected function view()
    {
        $view = $this->data['view'] ?? 'login.php';
        if (isset($this->data['view'])) {
            unset($this->data['view']);
        }
        return View::response(
            $this->data['label'] ?? 'admin_views',
            $view,
            $this->data
        );
    }
    protected function hasGroup(?int $user_id = null, ?string $type = "user")
    {
        $admin_type = Session::getUserType();
        $groupController = (new GroupController);
        if ($admin_type !== 'root') {
            $user_id = Session::getUserId();
            $group_id = $groupController->getGroup($user_id, 'admin');
            if (!$group_id || $group_id == -1) {
                throw new ValidationException(message: "Vous devez être le manager d'un groupe avant cette action!");
            }
            return $group_id;
        }
    }
    protected function checkCSRFtoken(string $token)
    {
        if (!CSRF::validateToken($token)) {
            throw new CSRFExeption(errors: [
                'Le token de sécurité est invalide!'
            ]);
        }
    }
}
