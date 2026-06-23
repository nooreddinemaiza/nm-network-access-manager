<?php


namespace App\Controllers;

use App\Models\Statistic;
use Core\Controllers\Controller;
use Core\Exception\ConnectionException;
use Core\Exception\CSRFExeption;
use Core\Exception\ValidationException;
use Core\Helper\Data;
use Core\Routing\Http\Request;
use Core\Routing\Http\Response;
use Core\System\CSRF;
use Core\System\Session;

class StatisticController extends Controller
{
    private Statistic $model;

    public function __construct()
    {
        $this->model = new Statistic();
        $this->data = [];
    }

    public function list(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'csrf_token',
                'search',
                'status',
                'online',
                'group',
                'year',
                'month',
                'day',
                'sort_by',
                'sort_order',
                'page',
                'per_page',
                'date_from',
                'date_to',
            ]);

            if (!CSRF::validateToken($data['csrf_token'])) {
                throw new CSRFExeption(
                    message: 'Le token de sécurité est invalide!'
                );
            }
            $this->has_groups();
            $data = $data->except(['csrf_token'])->sanitize();
            $errors = $data->validate([
                'search'  => 'min:3|max:100|no_sql',
                'status'  => 'in:all,active,suspended,expired|no_sql',
                'online'  => 'in:all,online,offline|no_sql',
                'year'    => 'no_sql',
                'month'   => 'no_sql',
                'day'   => 'no_sql',
                'page'    => 'integer|no_sql',
                'per_page' => 'integer|no_sql',
                'date_from' => 'no_sql',
                'date_to'   => 'no_sql',
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
                'year'       => $data['year'] ?? 'all',
                'month'      => $data['month'] ?? 'all',
                'day'       => $data['day'] ?? 'all',
                'sort_by'    => $data['sort_by'] ?? 'total_consumption',
                'sort_order' => $data['sort_order'] ?? 'desc',
                'date_from'  => $data['date_from'] ?? '',
                'date_to'    => $data['date_to'] ?? '',
            ];
            $hasDateRange = !empty($filters['date_from']) && !empty($filters['date_to']);
            $hasDay = $filters['day'] !== 'all';

            if ($hasDay) {
                if ($filters['year'] == 'all' || $filters['month'] == 'all') {
                    throw new ValidationException(message: 'Date non valide (AA-MM-JJ)');
                }
                $filters['day'] = strlen($filters['day']) == 2 ? $filters['day'] : '0' . $filters['day'];
                $filters['month'] = strlen($filters['month']) == 2 ? $filters['month'] : '0' . $filters['month'];
                $filters['day'] = "{$filters['year']}-{$filters['month']}-{$filters['day']}";
                $list = $this->model->daily($page, $perPage, $filters);
            } elseif ($hasDateRange) {
                $list = $this->model->range($page, $perPage, $filters);
            } else {
                $list = $this->model->list($page, $perPage, $filters);
            }

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
    public function groups(Request $request)
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
                    throw new ConnectionException(message: "Vous devez être le manager d'un groupe pour visonner les statistiques!");
                }
                $list = $this->model->groups($ids);
            } else {
                $list = $this->model->groups();
            }

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
    public function totals(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'csrf_token',
                'group',
                'year',
                'month',
                'date_from',
                'date_to',
            ]);

            if (!CSRF::validateToken($data['csrf_token'])) {
                throw new CSRFExeption(message: 'Le token de sécurité est invalide!');
            }

            $this->has_groups();

            $data = $data->except(['csrf_token'])->sanitize();
            $errors = $data->validate([
                'group'     => 'no_sql',
                'year'      => 'no_sql',
                'month'     => 'no_sql',
                'date_from' => 'no_sql',
                'date_to'   => 'no_sql',
            ]);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }

            $filters = [
                'group'     => $data['group']     ?? 'all',
                'year'      => $data['year']      ?? 'all',
                'month'     => $data['month']     ?? 'all',
                'date_from' => $data['date_from'] ?? '',
                'date_to'   => $data['date_to']   ?? '',
            ];

            $ids = [];
            if (Session::getUserType() !== 'root') {
                $groupController = new GroupController;
                $user_id = Session::getUserId();
                $groups_id = $groupController->getAdminGroups($user_id);
                $ids = array_map(fn($item) => $item['id'], $groups_id);
            }

            // Si filtre groupe spécifique, restreindre aux IDs autorisés
            $groupId = null;
            if (!empty($filters['group']) && $filters['group'] !== 'all') {
                $groupId = (int) $filters['group'];
                // Vérifier que l'admin a accès à ce groupe
                if (!empty($ids) && !in_array($groupId, $ids)) {
                    throw new ValidationException(message: 'Accès non autorisé à ce groupe');
                }
            }

            $list                = $this->model->listTotal($ids, $filters, $groupId);
            $most_consumer       = $this->model->mostConsumer($ids, $filters, $groupId);
            $most_consumer_group = $this->model->mostConsumerGroup($ids, $filters, $groupId);
            $most_visited_site   = $this->model->mostVisitedSite();
            $total_groups        = $this->model->totalGroups($ids ?: null);

            if (!$list) {
                throw new ConnectionException('Pas de résultat à lister!');
            }

            // Récupérer les groupes disponibles pour les filtres
            $groupController = (new GroupController);
            $groups = $groupController->getGroups();

            return Response::json([
                'success'           => true,
                'data'              => array_merge($list[0], [
                    'total_groups' => $total_groups,
                ]),
                'most_consumer'     => $most_consumer,
                'most_group'        => $most_consumer_group,
                'most_visited_site' => $most_visited_site,
                'groups'            => $groups,
            ]);
        } catch (CSRFExeption $e) {
            return $this->responseAjax(false, $e->getErrors());
        } catch (ValidationException $e) {
            return $this->responseAjax(success: false, errors: $e->getErrors(), message: $e->getMessage());
        } catch (ConnectionException $e) {
            return $this->responseAjax(success: false, message: $e->getMessage());
        }
    }
    public function sites(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'csrf_token',
                'search',
                'sort_by',
                'sort_order',
                'page',
                'per_page',
            ]);

            if (!CSRF::validateToken($data['csrf_token'])) {
                throw new CSRFExeption(message: 'Le token de sécurité est invalide!');
            }

            $data = $data->except(['csrf_token'])->sanitize();

            $errors = $data->validate([
                'search'     => 'min:3|max:100|no_sql',
                'sort_by'    => 'no_sql',
                'sort_order' => 'no_sql',
                'page'       => 'integer|no_sql',
                'per_page'   => 'integer|no_sql',
            ]);

            if ($errors) {
                throw new ValidationException(errors: $errors);
            }

            $page    = max(1, (int) ($data['page']     ?? 1));
            $perPage = min(100, max(10, (int) ($data['per_page'] ?? 25)));

            $filters = [
                'search'     => $data['search']     ?? '',
                'sort_by'    => $data['sort_by']    ?? 'total_visits',
                'sort_order' => $data['sort_order'] ?? 'desc',
            ];

            $list = $this->model->sites($page, $perPage, $filters);

            return Response::json([
                'success' => true,
                'data'    => $list['data'],
                'meta'    => [
                    'total'       => $list['total'],
                    'page'        => $list['page'],
                    'per_page'    => $list['per_page'],
                    'total_pages' => $list['total_pages'],
                ],
            ]);
        } catch (CSRFExeption $e) {
            return $this->responseAjax(false, $e->getErrors(), $e->getMessage());
        } catch (ValidationException $e) {
            return $this->responseAjax(false, $e->getErrors(), $e->getMessage());
        } catch (ConnectionException $e) {
            return $this->responseAjax(false, $e->getErrors(), $e->getMessage());
        }
    }
    private function has_groups()
    {
        if (Session::getUserType() !== 'root') {
            $groupController = (new GroupController);
            $user_id = Session::getUserId();
            $groups_id = $groupController->getAdminGroups($user_id);
            $ids = array_map(function ($item) {
                return $item['id'];
            }, $groups_id);
            if (!$ids) {
                throw new ConnectionException(message: "Vous devez être le manager d'un groupe pour visonner les statistiques!");
            }
        }
    }
    public function delete(Request $request)
    {
        try {
            $data = Data::create($request->all())->only([
                'csrf_token',
                'type',
                'target_id',
                'year',
                'month',
                'day',
                'date_from',
                'date_to',
                'date_mode',
            ]);

            if (!CSRF::validateToken($data['csrf_token'])) {
                throw new CSRFExeption(message: 'Le token de sécurité est invalide!');
            }

            $this->has_groups();

            $data = $data->except(['csrf_token'])->sanitize();

            $errors = $data->validate([
                'type'      => 'required|in:users,groups,sites|no_sql',
                'target_id' => $data['type'] !== 'sites' ? 'required|integer|no_sql' : 'no_sql',
                'year'      => 'no_sql',
                'month'     => 'no_sql',
                'day'       => 'no_sql',
                'date_from' => 'no_sql',
                'date_to'   => 'no_sql',
                'date_mode' => 'no_sql',
            ]);

            if ($errors) {
                throw new ValidationException(errors: $errors);
            }

            $type      = $data['type'];
            $targetId  = (int) $data['target_id'];
            $dateMode  = $data['date_mode'] ?? 'all';
            $filters   = [];

            if ($dateMode === 'range') {
                if (empty($data['date_from']) || empty($data['date_to'])) {
                    throw new ValidationException(message: 'Veuillez sélectionner une période valide.');
                }
                $filters['date_from'] = $data['date_from'];
                $filters['date_to']   = $data['date_to'];
            } elseif ($dateMode === 'picker') {
                $year  = $data['year']  ?? 'all';
                $month = $data['month'] ?? 'all';
                $day   = $data['day']   ?? 'all';

                if ($day !== 'all') {
                    if ($year === 'all' || $month === 'all') {
                        throw new ValidationException(message: 'Date non valide : année et mois requis.');
                    }
                    $day   = str_pad((string) $day,   2, '0', STR_PAD_LEFT);
                    $month = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
                    $filters['day'] = "{$year}-{$month}-{$day}";
                } else {
                    $filters['year']  = $year;
                    $filters['month'] = $month;
                }
            }
            // dateMode === 'all' → $filters vide → suppression totale

            $this->model->delete($type, $type !== 'sites' ? $targetId : 0, $filters);

            return Response::json([
                'success' => true,
                'message' => 'Statistiques supprimées avec succès.',
            ]);
        } catch (CSRFExeption $e) {
            return $this->responseAjax(false, $e->getErrors(), $e->getMessage());
        } catch (ValidationException $e) {
            return $this->responseAjax(false, $e->getErrors(), $e->getMessage());
        } catch (ConnectionException $e) {
            return $this->responseAjax(false, $e->getErrors(), $e->getMessage());
        }
    }
}
