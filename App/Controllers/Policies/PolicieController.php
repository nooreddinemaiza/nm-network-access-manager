<?php

namespace App\Controllers\Policies;

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
use App\Controllers\UserController;
use App\Controllers\GroupController;
use Core\Exception\ConnectionException;
use Core\Exception\ValidationException;

class PolicieController extends Controller
{
    private Policy $model;
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

            $this->checkCSRFtoken($data['csrf_token']);
            $list = $this->model->list();

            if (!$list) {
                throw new ConnectionException('Pas de résultat à lister!');
            }
            return Response::json(
                [
                    'success' => true,
                    'data' => $list,
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
                'description',
                'name',
                'expires_at',
                'csrf_token',
            ]);

            $this->checkCSRFtoken($data['csrf_token']);
            $this->hasGroup();

            $data = $data->except(['csrf_token']);
            Data::addLabel([
                'expires_at' => "La date d'expération"
            ]);
            $rules = [
                'status' => 'required|in:active,inactive',
                'name' => 'required|string|min:3|max:100',
                'description' => 'string|max:100',
            ];

            $errors = $data->validate($rules);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }

            $created = $this->model->add($data->removeEmpty()->all());
            if (!$created) {
                throw new ConnectionException('Une erreur est survenue lors de la création de la politique!');
            }
            return $this->responseAjax(
                success: true,
                message: 'La politique a été créé avec succès'
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
    public function edit(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'id',
                'status',
                'description',
                'name',
                'expires_at',
                'csrf_token',
            ]);

            $this->checkCSRFtoken($data['csrf_token']);
            $data = $data->except(['csrf_token']);
            $rules = [];
            $rules['id'] = 'required|integer';

            if ($data->has('name')) {
                $rules['name'] = 'string|min:3|max:100';
            }
            if ($data->has('description')) {
                $rules['description'] = 'string|max:100';
            }
            if ($data->has('status')) {
                $rules['status'] = 'in:active,inactive';
            }
            if ($data->isCompletelyEmpty()) {
                throw new ValidationException('Aucun changement détecté.');
            }
            $errors = $data->validate($rules);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }
            $id = $data['id'];
            $policy = $this->model->findById($id);
            if (!$policy) {
                throw new ValidationException('Cette politique n\'existe pas!');
            }

            $dataToUpdate = new Data();
            foreach ($policy as $key => $value) {
                if ($data[$key] !== $policy[$key]) {
                    $dataToUpdate[$key] = $data[$key];
                }
            }
            $dataToUpdate = $dataToUpdate->removeNulls();
            if ($dataToUpdate->isCompletelyEmpty()) {
                throw new ValidationException('Aucun changement détecté.');
            }
            $edited = $this->model->edit($id, $dataToUpdate);
            if (!$edited) {
                throw new ConnectionException('Une erreur est survenue lors de la modification!');
            }
            return $this->responseAjax(
                success: true,
                message: 'Modification avec succès'
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
    public function toggleStatus(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'policy_id',
                'status',
                'csrf_token',
            ]);
            $rules = [
                'policy_id' => 'required|integer',
                'status' => 'required|in:active,inactive',
            ];
            $errors = $data->validate($rules);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }
            $this->checkCSRFtoken($data['csrf_token']);
            $this->hasGroup();
            $invite_exists = $this->model->findById($data['policy_id']);
            if (!$invite_exists) {
                throw new ValidationException('Cette politique n\'existe pas!');
            }

            $toggled = $this->model->toggleStatus($data['policy_id'], $data['status']);

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
            $this->checkCSRFtoken($data['csrf_token']);
            $user_exists = $this->model->findById($data['id']);
            if (!$user_exists) {
                throw new ValidationException('Cette politique n\'existe pas!');
            }

            $removed = $this->model->remove($data['id']);

            if (!$removed) {
                throw new ConnectionException('Une erreur est survenue lors de la suppression!');
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
    public function exists(int|string $id)
    {
        return $this->model->exists($id);
    }
    public function getUserPolicies(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'csrf_token',
                'user_id'
            ]);

            $this->checkCSRFtoken($data['csrf_token']);
            $errors = $data->validate([
                'user_id' => 'integer'
            ]);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }

            $listed = $this->getListedPolicies($data['user_id']);
            return Response::json(
                [
                    'success' => true,
                    'data' => $listed,
                ]
            );
        } catch (CSRFExeption $e) {
            return $this->responseAjax(
                false,
                $e->getErrors()
            );
        } catch (ConnectionException | ValidationException $e) {
            return $this->responseAjax(
                success: false,
                message: $e->getMessage()

            );
        }
    }
    private function getListedPolicies(int|string $user_id)
    {
        $userController = new UserController;
        $user_exists = $userController->userExists($user_id);
        if (!$user_exists) {
            throw new ValidationException('Utilisateur introuvable!');
        }

        $list = $this->model->list();
        if (!$list) {
            throw new ConnectionException('Pas de politiques à lister!');
        }
        $user_policies = $this->model->getUserPolicies($user_id);
        $tab = [];
        foreach ($user_policies as $policy) {
            $tab[$policy['id']] = $policy['scope'] == 'special';
        }
        $listed = [];
        $applied = array_keys($tab);
        if ($user_policies) {
            foreach ($list as $policy) {
                if (in_array($policy['id'], $applied)) {
                    $policy['applied'] = true;
                    $policy['is_special'] = $tab[$policy['id']];
                } else {
                    $policy['applied'] = false;
                    $policy['is_special'] = false;
                }
                unset(
                    $policy['created_at'],
                    $policy['updated_at']
                );
                $listed[] = $policy;
            }
        } else {
            $listed = array_map(function ($item) {
                unset(
                    $item['created_at'],
                    $item['updated_at']
                );
                $item['applied'] = false;
                return $item;
            }, $list);
        }
        return $listed;
    }
    public function setUserPolicies2(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'csrf_token',
                'user_id',
                'policies'
            ]);

            // Validation du token CSRF
            $this->checkCSRFtoken($data['csrf_token']);

            // Validation des données de base
            $rules = [
                'user_id' => 'required|integer',
                'policies' => 'required|array'
            ];

            $errors = $data->validate($rules);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }

            // Vérification que l'utilisateur existe
            $userController = new UserController;
            $user_exists = $userController->userExists($data['user_id']);
            if (!$user_exists) {
                throw new ValidationException('Utilisateur introuvable!');
            }

            // Vérification qu'il y a bien des politiques à modifier
            if (empty($data['policies'])) {
                throw new ValidationException('Aucune modification de politique détectée.');
            }

            // Validation de chaque politique dans le tableau
            $policies_to_apply = [];
            $special_policies_to_apply = [];
            $policies_to_remove = [];

            foreach ($data['policies'] as $policy) {
                // Vérifier que chaque politique a un id et un statut applied
                if (!isset($policy['id']) || !isset($policy['applied'])) {
                    throw new ValidationException('Format de politique invalide.');
                }

                // Vérifier que la politique existe
                if (!$this->model->findById($policy['id'])) {
                    throw new ValidationException("La politique avec l'ID {$policy['id']} n'existe pas!");
                }

                // Séparer les politiques à appliquer et celles à retirer
                if ($policy['applied'] === true || $policy['applied'] === 'true' || $policy['applied'] === 1) {
                    $policies_to_apply[] = (int)$policy['id'];
                } else {
                    $policies_to_remove[] = (int)$policy['id'];
                }
                if ($policy['is_special'] === true || $policy['is_special'] === 'true' || $policy['is_special'] === 1) {
                    $special_policies_to_apply[] = (int)$policy['id'];
                }
            }
            return Response::json([
                'policies_to_apply' => $policies_to_apply,
                'policies_to_remove' => $policies_to_remove,
                'special_policies_to_apply' => $special_policies_to_apply,
            ]);
            // Appliquer les nouvelles politiques
            if (!empty($policies_to_apply)) {
                foreach ($policies_to_apply as $policy_id) {
                    $this->model->applyPolicyToUser($data['user_id'], $policy_id);
                }
            }

            // Retirer les politiques désactivées
            if (!empty($policies_to_remove)) {
                foreach ($policies_to_remove as $policy_id) {
                    $this->model->removePolicyFromUser($data['user_id'], $policy_id);
                }
            }
            return $this->responseAjax(
                success: true,
                message: 'Les politiques de l\'utilisateur ont été mises à jour avec succès'
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

    public function setUserPolicies(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'csrf_token',
                'user_id',
                'policies'
            ]);

            // Validation du token CSRF
            $this->checkCSRFtoken($data['csrf_token']);

            // Validation des données de base
            $rules = [
                'user_id' => 'required|integer',
                'policies' => 'array'
            ];

            $errors = $data->validate($rules);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }

            $to_set = array_map(function ($item) {
                $policy = new Data($item);
                $errors = $policy->validate([
                    'id' => 'required|integer',
                    'applied' => 'required|boolean',
                    'is_special' => 'boolean',
                ]);
                if ($errors) {
                    throw new ValidationException(message: 'Données invalides!');
                }
                return $item;
            }, $data['policies']);

            $listed = $this->getListedPolicies($data['user_id']);

            $policies = [];
            $applied_ones = [];
            foreach ($listed as $policy) {
                $policies[$policy['id']] = [
                    'id' => $policy['id'],
                    'applied' => $policy['applied'],
                    'is_special' => $policy['is_special'] ?? false,
                ];
                if ($policy['applied']) {
                    $applied_ones[$policy['id']] = $policy['is_special'];
                }
            }
            $new_set = [];
            foreach ($to_set as $policy) {
                $id = $policy['id'];
                if (in_array($id, array_keys($policies))) {
                    if ($policy['applied'] && $policies[$id]['applied']) {
                        if (isset($policy['is_special']) && $policy['is_special']) {
                            $new_set['special'][] = $id;
                        } else {
                            $new_set['normal'][] = $id;
                        }
                    } else {
                        if ($policy['applied']) {
                            if (!isset($policy['is_special'])) {
                                $policy['is_special'] = false;
                            }
                            $new_set['add'][$id] = $policy;
                        } else {
                            if (in_array($id, array_keys($applied_ones))) {
                                $new_set['remove'][] = $id;
                            }
                        }
                    }
                }
            }
            if (!$new_set) {
                throw new ValidationException(message: 'Aucun changement détecté');
            }
            $ctrl = [
                'add' => true,
                'special' => true,
                'remove' => true,
                'normal' => true,
            ];
            if (!empty($new_set['add'])) {
                $add = [];
                foreach ($new_set['add'] as $item) {
                    $add[] = [
                        'user_id' => $data['user_id'],
                        'preset_id' => $item['id'],
                        'scope' => $item['is_special'] ? 'special' : 'normal',
                    ];
                }
                $ctrl['add'] = $this->model->applyPoliciesToUser($add);
            }
            if (!empty($new_set['remove'])) {
                $ctrl['remove'] = $this->model->removeUserPolicies($new_set['remove']);
            }

            if (!empty($new_set['special'])) {
                $ctrl['special'] = $this->model->userSpecialPolicies($new_set['special'], 'special');
            }
            if (!empty($new_set['normal'])) {
                $ctrl['normal'] = $this->model->userSpecialPolicies($new_set['normal']);
            }

            $success = true;
            $message = 'Modifications enregistrées avec succès';
            if (!$ctrl['special'] ||  !$ctrl['normal'] ||  !$ctrl['add'] ||  !$ctrl['remove']) {
                $message = 'Une modification a échoué, Veuillez actualiser et ressayer!';
                $success = false;
            }
            return Response::json([
                'success' => $success,
                'message' => $message,
            ]);
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
    public function getGroupPolicies(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'csrf_token',
                'user_id'
            ]);

            $this->checkCSRFtoken($data['csrf_token']);
            $errors = $data->validate([
                'user_id' => 'integer'
            ]);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }

            $groupController = new GroupController;
            $user_exists = $groupController->exists($data['user_id']);
            if (!$user_exists) {
                throw new ValidationException('Groupe introuvable!');
            }

            $list = $this->model->list();
            if (!$list) {
                throw new ConnectionException('Pas de politiques à lister!');
            }
            $group_policies = $this->model->getGroupPolicies($data['user_id']);
            $listed = [];
            $applied = array_map(fn($item) => $item['id'], $group_policies);
            if ($group_policies) {
                foreach ($list as $policy) {
                    if (in_array($policy['id'], $applied)) {
                        $policy['applied'] = true;
                    } else {
                        $policy['applied'] = false;
                    }
                    unset(
                        $policy['created_at'],
                        $policy['updated_at']
                    );
                    $listed[] = $policy;
                }
            } else {
                $listed = array_map(function ($item) {
                    unset(
                        $item['created_at'],
                        $item['updated_at']
                    );
                    $item['applied'] = false;
                    return $item;
                }, $list);
            }
            return Response::json(
                [
                    'success' => true,
                    'data' => $listed,
                ]
            );
        } catch (CSRFExeption $e) {
            return $this->responseAjax(
                false,
                $e->getErrors()
            );
        } catch (ConnectionException | ValidationException $e) {
            return $this->responseAjax(
                success: false,
                message: $e->getMessage()

            );
        }
    }

    public function setGroupPolicies(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'csrf_token',
                'user_id',
                'policies'
            ]);

            // Validation du token CSRF
            $this->checkCSRFtoken($data['csrf_token']);

            // Validation des données de base
            $rules = [
                'user_id' => 'required|integer',
                'policies' => 'required|array'
            ];

            $errors = $data->validate($rules);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }

            // Vérification que l'utilisateur existe
            $grouController = new GroupController;
            $user_exists = $grouController->exists($data['user_id']);
            if (!$user_exists) {
                throw new ValidationException('Utilisateur introuvable!');
            }

            // Vérification qu'il y a bien des politiques à modifier
            if (empty($data['policies'])) {
                throw new ValidationException('Aucune modification de politique détectée.');
            }

            // Validation de chaque politique dans le tableau
            $policies_to_apply = [];
            $policies_to_remove = [];

            foreach ($data['policies'] as $policy) {
                // Vérifier que chaque politique a un id et un statut applied
                if (!isset($policy['id']) || !isset($policy['applied'])) {
                    throw new ValidationException('Format de politique invalide.');
                }

                // Vérifier que la politique existe
                if (!$this->model->findById($policy['id'])) {
                    throw new ValidationException("La politique avec l'ID {$policy['id']} n'existe pas!");
                }

                // Séparer les politiques à appliquer et celles à retirer
                if ($policy['applied'] === true || $policy['applied'] === 'true' || $policy['applied'] === 1) {
                    $policies_to_apply[] = (int)$policy['id'];
                } else {
                    $policies_to_remove[] = (int)$policy['id'];
                }
            }

            // Appliquer les modifications
            $success = true;

            // Appliquer les nouvelles politiques
            if (!empty($policies_to_apply)) {
                foreach ($policies_to_apply as $policy_id) {
                    $applied = $this->model->applyPolicyToGroup($data['user_id'], $policy_id);
                    if (!$applied) {
                        $success = false;
                    }
                }
            }

            // Retirer les politiques désactivées
            if (!empty($policies_to_remove)) {
                foreach ($policies_to_remove as $policy_id) {
                    $this->model->removePolicyFromGroup($data['user_id'], $policy_id);
                }
            }
            return $this->responseAjax(
                success: true,
                message: 'Les politiques du groupe ont été mises à jour avec succès'
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
}
