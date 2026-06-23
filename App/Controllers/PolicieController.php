<?php

namespace App\Controllers;

use Exception;
use Core\Helper\Data;
use Core\Helper\Meta;
use Core\System\CSRF;
use DateTimeImmutable;
use Core\System\Session;
use Core\Security\Encrypter;
use Core\Routing\Http\Request;
use App\Models\Policies\Policy;
use Core\Routing\Http\Response;
use Core\Controllers\Controller;
use Core\Exception\CSRFExeption;
use Core\Routing\RouteException;
use Core\Exception\ConnectionException;
use Core\Exception\ValidationException;

class PolicieController extends Controller
{
    private Policy $model;
    private const MAX_LINKS = 3;
    public function __construct()
    {
        $this->model = new Policy();
    }
    public function list(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'csrf_token',
            ]);

            if (!CSRF::validateToken($data['csrf_token'])) {
                throw new CSRFExeption(errors: [
                    'Le token de sécurité est invalide!'
                ]);
            }
            $list = [];
            $admin_type = Session::getUserType();
            $groupController = (new GroupController);

            if ($admin_type !== 'root') {
                $user_id = Session::getUserId();
                $groups_id = $groupController->getAdminGroups($user_id);
                $ids = array_map(function ($item) {
                    return $item['id'];
                }, $groups_id);
                if (!$ids) {
                    throw new ConnectionException(message: "Vous devez avoir un groupe pour créer un lien d'invitation!");
                }

                $list = $this->model->listForAdmin($ids);
            } else {
                $list = $this->model->list();
            }
            if (!$list) {
                throw new ConnectionException('Pas de résultat à lister!');
            }
            $upList = [];
            foreach ($list as $invite) {
                $invite['link'] = $request->mainUrl() . '/invite?token=' . $invite['token'];
                $upList[] = $invite;
            }
            $groups = array_map(function ($item) {
                return ['id' => $item['group_id'], 'name' => $item['group']];
            }, $list);

            return Response::json(
                [
                    'success' => true,
                    'data' => $upList,
                    'groups' => $groups,
                ]
            );
        } catch (CSRFExeption $e) {
            return $this->responseAjax(
                false,
                $e->getErrors()
            );
        } catch (ConnectionException $e) {
            return $this->responseAjax(
                success: false,
                message: $e->getMessage()

            );
        }
    }
    public function add(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'status',
                'max_uses',
                'group',
                'expires_at',
                'csrf_token',
            ]);

            if (!CSRF::validateToken($data['csrf_token'])) {
                throw new CSRFExeption(errors: [
                    'Le token de sécurité est invalide!'
                ]);
            }
            $this->hasGroup();

            $data = $data->except(['csrf_token']);
            Data::addLabel([
                'expires_at' => "La date d'expération"
            ]);
            $rules = [
                'status' => 'required|in:active,expired,revoked',
                'max_uses' => 'required|integer',
                'expires_at' => 'required',
            ];

            $admin_type = Session::getUserType();
            if ($admin_type != 'root') {
                $links = $this->model->countGroupLinks($data['group']);
                if ($links >= self::MAX_LINKS) {
                    throw new ValidationException('Ce groupe à atteint le maximum de liens!');
                }
            }
            $groupController = (new GroupController);

            $groups = $groupController->getGroups(withNul: false);
            $groups = array_map(function ($group) {
                return $group['id'];
            }, $groups);
            $group_rule = 'required|in:' . implode(',', $groups);
            $rules['group'] = $group_rule;

            $errors = $data->validate($rules);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }
            $data['token'] = $this->generateToken();

            $created = $this->model->add($data->all());
            if (!$created) {
                throw new ConnectionException('Une erreur est survenue lors de la création du lien!');
            }
            return $this->responseAjax(
                success: true,
                message: 'Le lien à été créé avec succès'
            );
        } catch (ValidationException $e) {
            return $this->responseAjax(
                false,
                $e->getErrors(),
                $e->getMessage(),
            );
        } catch (CSRFExeption $e) {
            return $this->responseAjax(
                false,
                $e->getErrors()
            );
        } catch (ConnectionException $e) {
            return $this->responseAjax(
                success: false,
                message: $e->getMessage()

            );
        } catch (Exception $e) {
            return $this->responseAjax(
                success: false,
                message: $e->getMessage()

            );
        }
    }

    /**
     * Génère un token aléatoire sécurisé de 64 caractères
     *
     * @return string
     * @throws Exception
     */
    private function generateToken(): string
    {
        // 32 octets = 64 caractères hexadécimaux
        return Encrypter::generateUuid64();
    }
    public function toggleStatus(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'id',
                'status',
                'csrf_token',
            ]);
            $rules = [
                'id' => 'required|integer',
                'status' => 'required|in:active,expired,revoked',
            ];
            $errors = $data->validate($rules);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }
            if (!CSRF::validateToken($data['csrf_token'])) {
                throw new CSRFExeption(errors: [
                    'Le token de sécurité est invalide!'
                ]);
            }
            $this->hasGroup();
            $invite_exists = $this->model->findById($data['id']);
            if (!$invite_exists) {
                throw new ValidationException('Ce compte n\'existe pas!');
            }

            $toggled = $this->model->toggleStatus($data['id'], $data['status']);

            if (!$toggled) {
                throw new ConnectionException('Une erreur est survenue lors de la modification du status!');
            }
            return $this->responseAjax(
                success: true,
            );
        } catch (ValidationException $e) {
            return $this->responseAjax(
                false,
                $e->getErrors(),
                $e->getMessage(),
            );
        } catch (CSRFExeption $e) {
            return $this->responseAjax(
                false,
                $e->getErrors()
            );
        } catch (ConnectionException $e) {
            return $this->responseAjax(
                success: false,
                message: $e->getMessage()

            );
        }
    }
    public function edit(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'id',
                'max_uses',
                'expires_at',
                'status',
                'csrf_token',
            ]);

            if (!CSRF::validateToken($data['csrf_token'])) {
                throw new CSRFExeption(errors: [
                    'Le token de sécurité est invalide!'
                ]);
            }
            $data = $data->except(['csrf_token']);
            $rules = [];
            $rules['id'] = 'required|integer';

            if ($data->has('max_uses')) {
                $rules['max_uses'] = 'integer|min:1|max:100';
            }
            if ($data->has('expires_at')) {
                $rules['expires_at'] = 'datetime';
            }
            if ($data->has('status')) {
                $rules['status'] = 'in:active,expired,revoked';
            }
            if ($data->isCompletelyEmpty()) {
                throw new ValidationException('Aucun changement détecté.');
            }
            $errors = $data->validate($rules);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }
            $id = $data['id'];
            $link = $this->model->findById($id);
            if (!$link) {
                throw new ValidationException('Ce lien n\'existe pas!');
            }

            $dataToUpdate = new Data();
            foreach ($link as $key => $value) {
                if ($data[$key] !== $link[$key]) {
                    $dataToUpdate[$key] = $data[$key];
                }
            }
            $dataToUpdate = $dataToUpdate->removeNulls();
            if ($dataToUpdate->isCompletelyEmpty()) {
                throw new ValidationException('Aucun changement détecté.');
            }
            $edited = $this->model->edit($id, $dataToUpdate);
            if (!$edited) {
                throw new ConnectionException('Une erreur est survenue lors de la modification du lien!');
            }
            return $this->responseAjax(
                success: true,
                message: 'Le lien à été modifié avec succès'
            );
        } catch (ValidationException $e) {
            return $this->responseAjax(
                false,
                $e->getErrors(),
                $e->getMessage(),
            );
        } catch (CSRFExeption $e) {
            return $this->responseAjax(
                false,
                $e->getErrors()
            );
        } catch (ConnectionException $e) {
            return $this->responseAjax(
                success: false,
                message: $e->getMessage()

            );
        }
    }
    public function remove(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'id',
                'csrf_token',
            ]);
            $rules = [
                'id' => 'required|integer',
            ];
            $errors = $data->validate($rules);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }
            if (!CSRF::validateToken($data['csrf_token'])) {
                throw new CSRFExeption(errors: [
                    'Le token de sécurité est invalide!'
                ]);
            }
            $user_exists = $this->model->findById($data['id']);
            if (!$user_exists) {
                throw new ValidationException('Ce lien n\'existe pas!');
            }

            $removed = $this->model->remove($data['id']);

            if (!$removed) {
                throw new ConnectionException('Une erreur est survenue lors de la suppression du lien!');
            }
            return $this->responseAjax(
                success: true,
            );
        } catch (ValidationException $e) {
            return $this->responseAjax(
                false,
                $e->getErrors(),
                $e->getMessage(),
            );
        } catch (CSRFExeption $e) {
            return $this->responseAjax(
                false,
                $e->getErrors()
            );
        } catch (ConnectionException $e) {
            return $this->responseAjax(
                success: false,
                message: $e->getMessage()

            );
        }
    }
    public function invite(Request $request)
    {
        try {
            if (Session::has('used_invite')) {
                throw new RouteException('Vous ne pouvez utiliser le lien qu\'une seule fois!');
            }
            $data = Data::create($request->all())->only([
                'token',
            ]);
            $errors = $data->validate([
                'token' => 'required|string'
            ]);
            if ($errors) {
                throw new RouteException('Lien d\'invitation introuvable!');
            }
            $token = $data['token'];
            $invite = $this->model->getByToken($token);
            if (!$invite) {
                throw new RouteException('Lien d\'invitation introuvable!');
            }

            if ($invite['uses'] >= $invite['max_uses']) {
                throw new RouteException('Le lien a eu son max d\'utilisations: ' . $invite['max_uses'] . ' utilisations');
            }
            $expiresAt = new DateTimeImmutable($invite['expires_at']);
            $now       = new DateTimeImmutable();

            if ($expiresAt < $now) {
                throw new RouteException('Le lien est expiré!');
            }
            if ($invite['status'] != 'active') {
                throw new RouteException('Le lien est inactive');
            }
            $this->data['invite'] = [
                'group' => $invite['group'],
                'description' => $invite['description'],
                'uses' => $invite['uses'],
                'max_uses' => $invite['max_uses'],
                'expires_at' => $invite['expires_at'],
            ];
            $this->data['csrf_token'] = CSRF::getToken();
            $this->data['label'] = 'views';
            $this->data['view'] = 'invite.php';
            $meta = new Meta();
            $meta->setTitle('Invitation de groupe: ' . ucfirst($invite['group']))
                ->setRobots('');
            $this->data['meta'] = $meta;

            return $this->view();
        } catch (ValidationException | RouteException $e) {
            return RouteException::brokenInvite(
                $request,
                $e->getMessage()
            );
        }
    }
    public function request(Request $request)
    {
        try {
            if (Session::has('used_invite')) {
                throw new ConnectionException('Vous pouvez utiliser le lien une seule fois!');
            }
            $data = Data::create($request->all())->only([
                'fullname',
                'password',
                'username',
                'csrf_token',
            ]);
            $data['token'] = $request->getUrlParam('token');
            if (!CSRF::validateToken($data['csrf_token'])) {
                throw new CSRFExeption(
                    message: 'Le token de sécurité est invalide!'
                );
            }
            $errors = $data->validate([
                'fullname' => 'required|min:5|max:100',
                'password' => 'required|min:8',
                'username' => 'required|username',
                'token' => 'required|string',
            ]);

            if ($errors) {
                throw new ValidationException(errors: $errors);
            }
            $valid_token = $this->model->getByToken($data['token']);
            if (!$this->isInviteValid($valid_token)) {
                throw new ValidationException(message: 'Le token utilisé est invalide vuillez recharger la page!');
            }

            $userController = new UserController();
            $username_exists = $userController->usernameExists($data['username']);
            if ($username_exists) {
                throw new ValidationException(errors: [
                    'username' => 'Ce nom d\'utilisateur existe déjà!'
                ]);
            }
            $data['status'] = 'active';
            $created = $userController->addUser($data);
            if (!$created) {
                throw new ConnectionException('Une erreur est survenue lors de la création du compte!');
            }
            $group_id = $valid_token['group_id'];

            if ($group_id) {
                $groupController = new GroupController();
                $groupController->joinGroup($created, $group_id);
            }
            $this->model->upateUses($valid_token);
            Session::set('used_invite', true);
            return $this->responseAjax(
                success: true,
                message: 'Le compte a été créé avec succès'
            );
        } catch (ValidationException $e) {
            return $this->responseAjax(
                false,
                $e->getErrors(),
                $e->getMessage()
            );
        } catch (CSRFExeption $e) {
            return $this->responseAjax(
                success: false,
                message: $e->getMessage()
            );
        } catch (ConnectionException $e) {
            return $this->responseAjax(
                success: false,
                message: $e->getMessage()
            );
        }
    }

    function isInviteValid(array $data): bool
    {
        if (!$data) {
            return false;
        }
        // 1. Vérifier le status
        if (($data['status'] ?? null) !== 'active') {
            return false;
        }

        // 2. Vérifier le nombre d'utilisations
        $uses     = (int) ($data['uses'] ?? 0);
        $maxUses  = (int) ($data['max_uses'] ?? 0);

        if ($uses >= $maxUses) {
            return false;
        }

        // 3. Vérifier l'expiration
        if (empty($data['expires_at'])) {
            return false;
        }

        $now        = new DateTimeImmutable('now');
        $expiresAt = new DateTimeImmutable($data['expires_at']);

        if ($expiresAt < $now) {
            return false;
        }

        return true;
    }
}
