<?php

namespace App\Controllers;

use App\Models\Group;
use Core\Helper\Data;
use Core\System\CSRF;
use Core\System\Session;
use Core\Routing\Http\Response;
use Core\Controllers\Controller;
use Core\Exception\CSRFExeption;
use Core\Exception\ConnectionException;
use Core\Exception\ValidationException;
use Core\Routing\Http\Request;

class GroupController extends Controller
{

    private Group $model;

    public function __construct()
    {
        $this->model = new Group();
    }
    public function getGroups(?Request $request = null, ?bool $withNul = true)
    {
        try {
            if ($request) {
                $data = Data::create($request->all())->only([
                    'csrf_token',
                ]);
                if (!CSRF::validateToken($data['csrf_token'])) {
                    throw new CSRFExeption(errors: [
                        'Le token de sécurité est invalide!'
                    ]);
                }
            }
            $groups = $this->model->listForUse();
            // if ($withNul && Session::getUserType() == 'root') {
            //     $groups[] = [
            //         'name' => '-Sans-',
            //         'id' => '-1',
            //     ];
            // }
            return $groups;
        } catch (CSRFExeption $e) {
            return $this->responseAjax(
                false,
                $e->getErrors()
            );
        }
    }
    public function get(Request $request)
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
            $list = $this->model->list();
            if (!$list) {
                throw new ConnectionException('Pas de résultat à lister!');
            }
            $moderators = (new AdminController)->listForInUse();
            $moderators[] = [
                'id' => -1,
                'fullname' => '-Sans-'
            ];
            return Response::json(
                [
                    'success' => true,
                    'data' => $list,
                    'moderators' => $moderators
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
                'name',
                'moderator',
                'description',
                'max_members',
                'csrf_token',
            ]);

            if (!CSRF::validateToken($data['csrf_token'])) {
                throw new CSRFExeption(errors: [
                    'Le token de sécurité est invalide!'
                ]);
            }
            $errors = $data->validate([
                'name' => 'required|max:100',
                'description' => 'min:3|max:340',
                'moderator' => 'integer',
                'max_members' => 'integer',
            ]);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }
            $name_exists = $this->model->nameExists($data['name']);
            if ($name_exists) {
                throw new ValidationException(errors: [
                    'name' => 'Un groupe avec ce nom existe déjà!'
                ]);
            }
            $created = $this->model->add($data->only(['name', 'description', 'max_members'])->all());
            if (!$created) {
                throw new ConnectionException('Une erreur est survenue lors de la création du groupe!');
            }
            if ($data['moderator'] !== '-1' && $data['moderator']) {
                $adminController = new AdminController;
                $moderator_exists = $adminController->exists($data['moderator']);
                if (!$moderator_exists) {
                    throw new ValidationException('Manager n\'existe pas!');
                }
                $this->model->setModerator($data['moderator'], $created);
            }
            return $this->responseAjax(
                success: true,
                message: 'Le groupe à été créé avec succès'
            );
        } catch (ValidationException $e) {
            return $this->responseAjax(
                false,
                $e->getErrors(),
                'Les données sont invalides!'
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
                'name',
                'moderator',
                'description',
                'max_members',
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

            if ($data->has('name')) {
                $rules['name'] = 'min:3|max:100';
            }
            if ($data->has('description')) {
                $rules['description'] = 'min:3|max:340';
            }
            if ($data->has('max_members')) {
                $rules['max_members'] = 'integer';
            }
            if ($data->isCompletelyEmpty()) {
                throw new ValidationException('Aucun changement détecté.');
            }
            $errors = $data->validate($rules);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }
            $id = $data['id'];
            $group = $this->model->findById($id);
            if (!$group) {
                throw new ValidationException('Ce groupe n\'existe pas!');
            }

            $dataToUpdate = new Data();
            foreach ($group as $key => $value) {
                if ($data[$key] !== $group[$key]) {
                    $dataToUpdate[$key] = $data[$key];
                }
            }
            if ($data->has('moderator')) {
                $dataToUpdate['moderator'] = $data['moderator'];
            }
            $dataToUpdate = $dataToUpdate->removeNulls();

            if ($dataToUpdate->isCompletelyEmpty()) {
                throw new ValidationException('Aucun changement détecté.');
            }
            if ($dataToUpdate->has('name')) {
                $name_exists = $this->model->nameExists($data['name']);
                if ($name_exists) {
                    throw new ValidationException(errors: [
                        'name' => 'Un groupe avec ce nom existe déjà!'
                    ]);
                }
            }
            if ($dataToUpdate->has('moderator')) {
                $has_moderator = $this->model->hasModerator($id);
                if ($has_moderator) {
                    $this->model->noModerator($id);
                }
                $this->model->setModerator($data['moderator'], $id);
                $dataToUpdate = $dataToUpdate->except(['moderator']);
            }
            if (!$dataToUpdate->isCompletelyEmpty()) {
                $edited = $this->model->edit($id, $dataToUpdate);
                if (!$edited) {
                    throw new ConnectionException('Une erreur est survenue lors de la modification du groupe!');
                }
            }
            return $this->responseAjax(
                success: true,
                message: 'Le groupe à été modifié avec succès'
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
                'additionnals'
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
                throw new ValidationException('Ce groupe n\'existe pas!');
            }
            if ($data['additionnals']) {
                $members = $this->model->getMembersIds($data['id']);
                if ($members) {
                    $members = array_map(function ($item) {
                        return $item['user_id'];
                    }, $members);
                    $userController = new UserController;
                    $userController->removeIn($members);
                }
            }
            $removed = $this->model->remove($data['id']);

            if (!$removed) {
                throw new ConnectionException('Une erreur est survenue lors de la suppression du groupe!');
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
    public function hasPlace(int|string $group_id)
    {
        return $this->model->hasPlace($group_id);
    }
    public function joinGroup(int|string $user_id, int|string $group_id, bool $has_group = false)
    {
        $result = $this->model->joinGroup($user_id, $group_id, $has_group);
        return $result ? true : false;
    }
    public function noGroup(int|string $user_id)
    {
        return $this->model->noGroup($user_id);
    }
    public function getGroup(int|string $user_id, ?string $type = "user")
    {
        return $this->model->getGroup($user_id, $type);
    }
    public function getAdminGroups(int|string $admin_id)
    {
        return $this->model->getAdminGroups($admin_id);
    }
    public function hasGroup(?int $user_id = null, ?string $type = "user")
    {
        return $this->model->hasGroup($user_id, $type) ? true : false;
    }
    /**
     * Trouve un admin par ID
     */
    public function exists(int|string $id): bool
    {
        return $this->model->groupExists($id);
    }
    public function switchModerator(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'group_id',
                'moderator',
                'csrf_token',
            ]);
            $rules = [
                'group_id' => 'required|integer',
                'moderator' => 'required|integer',
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
            $group_exists = $this->model->findById($data['group_id']);
            if (!$group_exists) {
                throw new ValidationException('Ce groupe n\'existe pas!');
            }

            $adminController  = (new AdminController);
            if ($data['moderator'] !== '-1') {
                $moderator_exists = $adminController->exists($data['moderator']);
                if (!$moderator_exists) {
                    throw new ValidationException('Manager n\'existe pas!');
                }

                $has_moderator = $this->model->hasModerator($data['group_id']);
                if ($has_moderator) {
                    $this->model->noModerator($data['group_id']);
                }
                $this->model->setModerator($data['moderator'], $data['group_id']);

                return $this->responseAjax(
                    success: true,
                );
            } else {
                $this->model->noModerator($data['group_id']);
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
}
