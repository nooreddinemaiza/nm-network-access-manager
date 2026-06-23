<?php

namespace App\Controllers;

use App\Models\User;
use Core\Helper\Data;
use Core\System\CSRF;
use Core\Routing\Http\Request;
use Core\Routing\Http\Response;
use Core\Controllers\Controller;
use Core\Exception\CSRFExeption;
use Core\Exception\ConnectionException;
use Core\Exception\ValidationException;
use Core\System\Session;

class UserController extends Controller
{
    private User $model;

    public function __construct()
    {
        $this->model = new User();
        $this->data = [];
    }
    public function listed(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'csrf_token',
                'search',
                'status',
                'online',
                'group',
                'sort_by',
                'sort_order',
                'page',
                'per_page',
            ]);

            if (!CSRF::validateToken($data['csrf_token'])) {
                throw new CSRFExeption(
                    message: 'Le token de sécurité est invalide!'
                );
            }
            $this->hasGroup();
            $data = $data->except(['csrf_token'])->sanitize();
            $errors = $data->validate([
                'search'  => 'min:3|max:100|no_sql',
                'status'  => 'in:all,active,suspended,expired|no_sql',
                'online'  => 'in:all,online,offline|no_sql',
                'page'    => 'integer|no_sql',
                'per_page' => 'integer|no_sql',
            ]);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }
            $page    = max(1, (int) ($data['page'] ?? 1));
            $perPage = min(100, max(10, (int) ($data['per_page'] ?? 20)));

            $filters = [
                'search'     => $data['search'] ?? '',
                'status'     => $data['status'] ?? 'all',
                'online'     => $data['online'] ?? 'all',
                'group'      => $data['group'] ?? 'all',
                'sort_by'    => $data['sort_by'] ?? 'id',
                'sort_order' => $data['sort_order'] ?? 'desc',
            ];


            $list = $this->model->listed($page, $perPage, $filters);

            if (!$list) {
                throw new ConnectionException('Pas de résultat à lister!');
            }

            $groupController = (new GroupController);
            $groups = $groupController->getGroups();
            return Response::json([
                'success' => true,
                'data'    => $list['data'],
                'meta'    => [
                    'total'       => $list['total'],
                    'page'        => $list['page'],
                    'per_page'    => $list['per_page'],
                    'total_pages' => $list['total_pages'],
                ],
                'groups' => $groups,
            ]);
        } catch (CSRFExeption $e) {
            return $this->responseAjax(
                false,
                $e->getErrors(),
                $e->getMessage(),
            );
        } catch (ConnectionException $e) {
            return $this->responseAjax(
                success: false,
                errors: $e->getErrors(),
                message: $e->getMessage(),
            );
        } catch (ValidationException $e) {
            return $this->responseAjax(
                success: false,
                errors: $e->getErrors(),
                message: $e->getMessage(),
            );
        }
    }
    public function usernameExists(string $username)
    {
        return $this->model->usernameExists($username);
    }
    public function userExists(int|string $id)
    {
        return $this->model->findById($id) ? true : false;
    }
    public function add(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'fullname',
                'username',
                'password',
                'group',
                'csrf_token',
                'status',
            ]);

            if (!CSRF::validateToken($data['csrf_token'])) {
                throw new CSRFExeption(errors: [
                    'Le token de sécurité est invalide!'
                ]);
            }
            $this->hasGroup();

            $groupController = (new GroupController);
            $groups = $groupController->getGroups();

            $groups = array_map(function ($group) {
                return $group['id'];
            }, $groups);

            $rules = [
                'fullname' => 'max:100',
                'username' => 'required|username',
                'password' => 'required|min:6',
                'status'   => 'required|in:active,inactive',
            ];
            $admin_type = Session::getUserType();
            $group_rule = 'required|in:' . implode(',', $groups);
            if ($admin_type == 'root') {
                $group_rule .=   ',-1';
            }
            $rules['group'] = $group_rule;
            $errors = $data->validate($rules);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }
            $username_exists = $this->usernameExists($data['username']);
            if ($username_exists) {
                throw new ValidationException(errors: [
                    'username' => 'Un compte avec ce nom d\'utilisateur existe déjà!'
                ]);
            }
            $created = $this->model->add($data->except(['csrf_token', 'group'])->all());
            if (!$created) {
                throw new ConnectionException('Une erreur est survenue lors de la création du compte!');
            }

            if ($data['group'] !== '-1') {
                $hasPlace = $groupController->hasPlace($data['group']);
                if (!$hasPlace) {
                    $this->model->remove($created);
                    throw new ConnectionException('Le groupe a le max de membres!');
                }
                $groupController->joinGroup($created, $data['group']);
            }
            return $this->responseAjax(
                success: true,
                message: 'Le compte à été créé avec succès'
            );
        } catch (ValidationException $e) {
            return $this->responseAjax(
                false,
                $e->getErrors(),
                $e->getMessage() ?? 'Les données sont invalides!'
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
                'user_id',
                'csrf_token',
                'fullname',
                'username',
                'password',
                'status',
                'expires_at',
            ]);

            if (!CSRF::validateToken($data['csrf_token'])) {
                throw new CSRFExeption(errors: [
                    'Le token de sécurité est invalide!'
                ]);
            }
            $this->hasGroup();

            $data = $data->except(['csrf_token']);
            $rules = [];
            $rules['user_id'] = 'required|integer';

            if ($data->has('fullname')) {
                $rules['fullname'] = 'min:3|max:100';
            }
            if ($data->has('username')) {
                $rules['username'] = 'username';
            }
            if ($data->has('password')) {
                $rules['password'] = 'min:4';
            }
            if ($data->has('status')) {
                $rules['status'] = 'in:active,suspended,expired';
            }
            if ($data->isCompletelyEmpty()) {
                throw new ValidationException('Aucun changement détecté.');
            }
            $errors = $data->validate($rules);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }
            $id = $data['user_id'];
            $user = $this->model->findById($id);
            if (!$user) {
                throw new ValidationException('Ce compte n\'existe pas!');
            }

            $dataToUpdate = new Data();
            foreach ($user as $key => $value) {
                if ($data[$key] !== $user[$key]) {
                    $dataToUpdate[$key] = $data[$key];
                }
            }
            $dataToUpdate = $dataToUpdate->removeNulls();

            if ($data->has('password')) {
                $dataToUpdate['password'] = $data['password'];
            }
            if ($dataToUpdate->isCompletelyEmpty()) {
                throw new ValidationException('Pas de changement détecté.');
            }
            if ($dataToUpdate->has('username')) {
                $username_exists = $this->model->usernameExists($data['username']);
                if ($username_exists) {
                    throw new ValidationException(errors: [
                        'username' => 'Un compte avec ce nom d\'utilisateur existe déjà!'
                    ]);
                }
            }

            $edited = $this->model->edit($id, $dataToUpdate);
            if (!$edited) {
                throw new ConnectionException('Une erreur est survenue lors de la modification du compte!');
            }
            return $this->responseAjax(
                success: true,
                message: 'Le profil à été modifié avec succès'
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
            $this->hasGroup();

            $user_exists = $this->model->findById($data['id']);
            if (!$user_exists) {
                throw new ValidationException('Ce compte n\'existe pas!');
            }

            $removed = $this->model->remove($data['id']);

            if (!$removed) {
                throw new ConnectionException('Une erreur est survenue lors de la suppression du compte!');
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
    public function removeIn(array $members)
    {
        try {
            $this->hasGroup();
            if (!$members) throw new ValidationException();
            return $this->model->removeIn($members) ? true : throw new ConnectionException();
        } catch (ValidationException | ConnectionException $e) {
            return false;
        }
    }
    public function toggleStatus(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'user_id',
                'status',
                'csrf_token',
            ]);
            $rules = [
                'user_id' => 'required|integer',
                'status' => 'required|in:active,suspended,expired',
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
            $user_exists = $this->model->findById($data['user_id']);
            if (!$user_exists) {
                throw new ValidationException('Ce compte n\'existe pas!');
            }

            $toggled = $this->model->toggleStatus($data['user_id'], $data['status']);

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
    public function expire(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'expires_at',
                'user_id',
                'csrf_token',
            ]);

            if (!CSRF::validateToken($data['csrf_token'])) {
                throw new CSRFExeption(errors: [
                    'Le token de sécurité est invalide!'
                ]);
            }
            $this->hasGroup();

            $rules = [
                'user_id' => 'required|integer',
                'expires_at' => 'required',
            ];
            $data = $data->except(['csrf_token']);

            if ($data->isCompletelyEmpty()) {
                throw new ValidationException('Aucun changement détecté.');
            }
            $errors = $data->validate($rules);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }
            $user = $this->model->findById($data['user_id']);
            if (!$user) {
                throw new ValidationException('Ce compte n\'existe pas!');
            }

            $edited = $this->model->edit($data['user_id'], $data->only(['expires_at']));
            if (!$edited) {
                throw new ConnectionException('Une erreur est survenue lors de la modification!');
            }
            return $this->responseAjax(
                success: true,
                message: 'La date à été modifié avec succès'
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
    public function disconnect(Request $request)
    {
        try {
            throw new ConnectionException('Pas encore implémenté!');
            $data = Data::create($request->all())->only([
                'user_id',
                'csrf_token',
            ]);

            if (!CSRF::validateToken($data['csrf_token'])) {
                throw new CSRFExeption(errors: [
                    'Le token de sécurité est invalide!'
                ]);
            }
            $this->hasGroup();

            $rules = [
                'user_id' => 'required|integer',
            ];
            $data = $data->except(['csrf_token']);

            if ($data->isCompletelyEmpty()) {
                throw new ValidationException('Aucun changement détecté.');
            }
            $errors = $data->validate($rules);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }
            $user = $this->model->findById($data['user_id']);
            if (!$user) {
                throw new ValidationException('Ce compte n\'existe pas!');
            }

            $edited = $this->model->edit($data['user_id'], $data->only(['expires_at']));
            if (!$edited) {
                throw new ConnectionException('Une erreur est survenue lors de la modification!');
            }
            return $this->responseAjax(
                success: true,
                message: 'La date à été modifié avec succès'
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
    public function switchGroup(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'user_id',
                'group',
                'csrf_token',
            ]);
            $rules = [
                'user_id' => 'required|integer',
                'group' => 'required|integer',
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
            $user_exists = $this->model->findById($data['user_id']);
            if (!$user_exists) {
                throw new ValidationException('Ce compte n\'existe pas!');
            }

            $groupController = new GroupController;
            if ($data['group'] !== '-1') {

                $group_exists = $groupController->exists($data['group']);
                if (!$group_exists) {
                    throw new ValidationException('Ce groupe n\'existe pas!');
                }
                $hasPlace = $groupController->hasPlace($data['group']);

                if (!$hasPlace) {
                    throw new ConnectionException('Le groupe a le max de membres!');
                }
                $has_group = $groupController->hasGroup($data['user_id']);

                $groupController->joinGroup($data['user_id'], $data['group'], $has_group);
                return $this->responseAjax(
                    success: true,
                );
            } else {
                $groupController->noGroup($data['user_id']);
                return $this->responseAjax(
                    success: true,
                );
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
    public function connected(Request $request)
    {
        $this->model->getConnected();
    }

    public function addUser(Data $data)
    {
        return $this->model->add($data->all());
    }
}
