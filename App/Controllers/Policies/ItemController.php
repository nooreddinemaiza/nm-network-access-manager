<?php

namespace App\Controllers\Policies;

use Core\Helper\Data;
use Core\System\CSRF;
use App\Models\Policies\Item;
use Core\Routing\Http\Request;
use Core\Routing\Http\Response;
use Core\Controllers\Controller;
use Core\Exception\CSRFExeption;
use Core\Exception\ConnectionException;
use Core\Exception\ValidationException;

class ItemController extends Controller
{
    private Item $model;

    // Mapping des attributs entre front et RADIUS
    private const ATTRIBUTE_MAPPING = [
        'max_session' => 'Session-Timeout',
        'max_inactive' => 'Idle-Timeout',
        'sessions' => 'Simultaneous-Use',
        'accounting' => 'Acct-Interim-Interval',
        'max_upload' => 'WISPr-Bandwidth-Max-Up',
        'max_download' => 'WISPr-Bandwidth-Max-Down',
    ];

    // Champs attendus pour chaque item
    private const REQUIRED_FIELDS = ['id', 'value', 'priority', 'enabled'];

    public function __construct()
    {
        $this->model = new Item();
    }

    /**
     * Récupère les items d'une policy
     */
    public function get(Request $request)
    {
        try {
            // Validation des données entrantes
            $data = Data::create($request->all())->only(['id', 'csrf_token']);

            $this->validateCsrfToken($data['csrf_token']);

            $errors = $data->validate(['id' => 'required|integer']);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }

            // Récupération des données
            $items = $this->getItemsData($data['id']);

            return Response::json([
                'success' => true,
                'data' => $items,
            ]);
        } catch (ValidationException | CSRFExeption $e) {
            return $this->responseAjax(false, $e->getErrors(), $e->getMessage());
        } catch (ConnectionException $e) {
            return $this->responseAjax(false, [], $e->getMessage());
        }
    }

    /**
     * Édite les items d'une policy
     */
    public function edit(Request $request)
    {
        try {
            // Extraction et validation des données
            $data = Data::create($request->all())->only([
                'policy_id',
                'csrf_token',
                'max_session',
                'max_inactive',
                'sessions',
                'accounting',
                'max_upload',
                'max_download',
            ]);

            $this->validateCsrfToken($data['csrf_token']);

            // Validation de base
            $errors = $data->validate([
                'policy_id' => 'integer',
                'max_session' => 'array',
                'max_inactive' => 'array',
                'sessions' => 'array',
                'accounting' => 'array',
                'max_upload' => 'array',
                'max_download' => 'array',
            ]);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }
            // Vérifier que la policy existe
            $this->checkPolicyExists($data['policy_id']);

            // Valider la structure des données envoyées
            $new_set = array_map(function ($item) {
                Data::addLabel([
                    'value' => 'Valeur',
                    'priority' => 'Priorité',
                    'enabled' => 'Active/Inactive',
                ]);
                $policy_item = new Data($item);
                $errors = $policy_item->validate([
                    'id' => 'numeric',
                    'value' => 'numeric',
                    'priority' => 'numeric',
                    'enabled' => 'boolean',
                ]);
                foreach ($item as $key => $value) {
                    if ($key == 'priority' && is_null($value)) {
                        $item['priority'] = 100;
                    }
                }
                if ($errors) {
                    throw new ValidationException(
                        message: 'Données invalides!',
                        errors: $errors
                    );
                }
                return $item;
            }, $data->except(['policy_id', 'csrf_token'])->all());
            // Récupérer les données actuelles
            $currentItems = $this->getItemsData($data['policy_id'], false);
            $to_set = [
                'add' => [],
                'edit' => [],
                'delete' => []
            ];
            foreach ($new_set as $key => $item) {
                if (in_array($key, array_keys($currentItems))) {
                    $it = [];
                    foreach ($item as $k => $value) {
                        if ($k == 'value' && boolval(!$this->normalizeValue($value)) || $k == 'enabled' && boolval(!$this->normalizeBoolean($value))) {
                            $to_set['delete'][] = $item['id'];
                            break;
                        } elseif ($currentItems[$key][$k] !== $value) {
                            $it[$k] = $value;
                        }
                    }
                    if ($it) {
                        if (!in_array($item['id'], $to_set['delete'])) {
                            if (in_array($key, [
                                'max_session',
                                'max_inactive',
                            ]) && boolval($this->normalizeValue($value))) {
                                $it['value'] = (string)((int)$item['value'] * 60);
                            } elseif (in_array($key, [
                                'max_upload',
                                'max_download',
                            ]) && boolval($this->normalizeValue($value))) {
                                $it['value'] = (string)((int)$item['value'] * 1024 * 1024 * 8);
                            }
                            $item['preset_id'] = $data['policy_id'];
                            $to_set['edit'][$item['id']] = $it;
                        }
                    }
                } else {
                    if ($this->normalizeValue($item['value']) && $this->normalizeBoolean($item['enabled'])) {
                        if (in_array($key, [
                            'max_session',
                            'max_inactive',
                        ])) {
                            $item['value'] = (string)((int)$item['value'] * 60);
                        } elseif (in_array($key, [
                            'max_upload',
                            'max_download',
                        ])) {
                            $item['value'] = (string)((int)$item['value'] * 1024 * 1024 * 8);
                        }
                        $item['preset_id'] = $data['policy_id'];
                        $item['attribute'] = self::ATTRIBUTE_MAPPING[$key];
                        $to_set['add'][] = $item;
                    }
                }
            }
            $result = [
                'add' => true,
                'edit' => true,
                'delete' => true
            ];
            if ($to_set) {
                if (!empty($to_set['add'])) {
                    $result['add'] &= $this->model->add($to_set['add']);
                }
                if (!empty($to_set['edit'])) {
                    foreach ($to_set['edit'] as $key => $value) {
                        $result['edit'] &= boolval($this->model->edit($key, $value));
                    }
                }
                if (!empty($to_set['delete'])) {
                    $result['delete'] &= $this->model->removeItems($to_set['delete']);
                }
            }
            $success = true;
            $message = 'Modifications enregistrées avec succès';
            if (!$result['add'] ||  !$result['edit'] ||  !$result['delete']) {
                $message = 'Une modification a échoué, Veuillez actualiser et ressayer!';
                $success = false;
            }
            return Response::json([
                'success' => $success,
                'message' => $message,
            ]);
        } catch (ValidationException | CSRFExeption $e) {
            return $this->responseAjax(false, $e->getErrors(), $e->getMessage());
        } catch (ConnectionException $e) {
            return $this->responseAjax(false, [], $e->getMessage());
        }
    }
    private function setItems(int|string $policy_id, array $data)
    {
        $toAdd = [];
        $toUpdate = [];
        $toRemove = [];
        $result = [
            'added' => true,
            'updated' => true,
            'removed' => true,
        ];
        foreach ($data as $key => $value) {
            if (in_array($key, ['max_session', 'max_inactive'])) {
                if (!$value['value']) {
                    $value['value'] = 0;
                } else {
                    $value['value'] = (int)$value['value'] * 60;
                }
            }
            if (!isset($value['enabled'])) {
                $value['enabled'] = 0;
            }
            if (!$value['id']) {
                $value['preset_id'] = $policy_id;
                unset($value['id']);
                if (!$value['priority']) {
                    $value['priority'] = 0;
                }
                $toAdd[] = $value;
            } else if (!$value['value']) {
                $toRemove[] = $value['id'];
            } else {
                unset($value['attribute']);
                $toUpdate[] = $value;
            }
        }
        if ($toAdd) {
            foreach ($toAdd as $item) {
                $result['added'] &= ($this->model->add($item) ? true : false);
            }
        }

        if ($toUpdate) {
            foreach ($toUpdate as $item) {
                $result['updated'] &=  ($this->model->edit($item['id'], $item) ? true : false);
            }
        }
        if ($toRemove) {
            $result['removed'] &=  ($this->model->remove($toRemove) ? true : false);
        }
        return  $result;
    }
    /**
     * Récupère et formate les items d'une policy
     */
    private function getItemsData(int|string $policyId, bool $with_default = true): array
    {
        $this->checkPolicyExists($policyId);

        $items = $this->model->getItems($policyId);
        if (!$items) {
            return [];
        }
        $formatted = [];

        // Formater les items existants
        foreach ($items as $item) {
            $key = array_search($item['attribute'], self::ATTRIBUTE_MAPPING);
            if ($key !== false) {
                $formatted[$key] = [
                    'id' => $item['id'],
                    'value' => $item['value'],
                    'priority' => $item['priority'],
                    'enabled' => (bool) $item['enabled'],
                ];
                if (in_array($key, [
                    'max_session',
                    'max_inactive',
                ])) {
                    $formatted[$key]['value'] = (int)($item['value'] / 60);
                }
                if (in_array($key, [
                    'max_upload',
                    'max_download',
                ])) {
                    $formatted[$key]['value'] = (int)($item['value'] / 1024 / 1024 / 8);
                }
            }
        }
        if ($with_default) {
            // Ajouter les items manquants avec valeurs par défaut
            foreach (array_keys(self::ATTRIBUTE_MAPPING) as $key) {
                if (!isset($formatted[$key])) {
                    $formatted[$key] = [
                        'id' => null,
                        'value' => null,
                        'priority' => null,
                        'enabled' => false,
                    ];
                }
            }
        }

        return $formatted;
    }

    /**
     * Valide la structure des items envoyés
     * @throws ValidationException
     */
    private function validateItemsStructure(array $items): void
    {
        if (empty($items)) {
            throw new ValidationException('Aucune donnée à traiter');
        }

        foreach ($items as $key => $item) {
            // Vérifier que la clé est valide
            if (!array_key_exists($key, self::ATTRIBUTE_MAPPING)) {
                throw new ValidationException("Clé invalide: $key");
            }

            // Vérifier que c'est un tableau
            if (!is_array($item)) {
                throw new ValidationException("Format invalide pour '$key'");
            }

            // Vérifier que tous les champs requis sont présents
            foreach (self::REQUIRED_FIELDS as $field) {
                if (!array_key_exists($field, $item)) {
                    throw new ValidationException("Champ manquant '$field' pour '$key'");
                }
            }
        }
    }

    /**
     * Détecte les changements entre les données actuelles et les nouvelles
     */
    private function detectChanges(array $current, array $new): array
    {
        $changes = [];

        foreach ($new as $key => $newItem) {
            if (!isset($current[$key])) {
                continue; // Ne devrait pas arriver après validation
            }

            $currentItem = $current[$key];
            $itemChanges = [
                'id' => $newItem['id'] ?? $currentItem['id'],
                'attribute' => self::ATTRIBUTE_MAPPING[$key],
            ];

            // Si la value est vide, ignorer les changements de priority/enabled
            $newValue = $this->normalizeValue($newItem['value']);
            $currentValue = $this->normalizeValue($currentItem['value']);

            // Cas 1: value passe de vide à vide → pas de changement
            if ($this->isEmpty($newValue) && $this->isEmpty($currentValue)) {
                continue;
            }

            // Cas 2: value change (vide→rempli, rempli→vide, ou modification)
            if ($newValue !== $currentValue) {
                $itemChanges['value'] = $newValue;
                $itemChanges['priority'] = $this->normalizeValue($newItem['priority']);
                $itemChanges['enabled'] = $this->normalizeBoolean($newItem['enabled']);
                $changes[$key] = $itemChanges;
                continue;
            }

            // Cas 3: value existe et n'a pas changé, vérifier priority et enabled
            if (!$this->isEmpty($newValue)) {
                $hasChange = false;

                if ($this->normalizeValue($newItem['priority']) !== $this->normalizeValue($currentItem['priority'])) {
                    $itemChanges['priority'] = $this->normalizeValue($newItem['priority']);
                    $hasChange = true;
                }

                if ($this->normalizeBoolean($newItem['enabled']) !== $this->normalizeBoolean($currentItem['enabled'])) {
                    $itemChanges['enabled'] = $this->normalizeBoolean($newItem['enabled']);
                    $hasChange = true;
                }

                if ($hasChange) {
                    // Inclure la value même si elle n'a pas changé (pour l'update complet)
                    $itemChanges['value'] = $newValue;
                    $changes[$key] = $itemChanges;
                }
            }
        }

        return $changes;
    }

    /**
     * Valide les valeurs des changements
     */
    private function validateChangesValues(array $changes): void
    {
        foreach ($changes as $key => $change) {
            // Valider que value est un nombre positif si présent et non null
            if (isset($change['value']) && !is_null($change['value'])) {
                if (!is_numeric($change['value']) || $change['value'] < 0) {
                    throw new ValidationException("Valeur invalide pour '$key': doit être un nombre positif");
                }
            }

            // Valider priority si présent et non null
            if (isset($change['priority']) && !is_null($change['priority'])) {
                if (!is_numeric($change['priority']) || $change['priority'] < 0) {
                    throw new ValidationException("Priorité invalide pour '$key': doit être un nombre positif");
                }
            }

            // Valider enabled
            if (isset($change['enabled'])) {
                if (!is_bool($change['enabled']) && !in_array($change['enabled'], [0, 1], true)) {
                    throw new ValidationException("Valeur 'enabled' invalide pour '$key'");
                }
            }
        }
    }

    /**
     * Vérifie si une policy existe
     * @throws ValidationException
     */
    private function checkPolicyExists(int|string $policyId): void
    {
        $policyController = new PolicieController();
        if (!$policyController->exists($policyId)) {
            throw new ValidationException('Cette politique n\'existe pas');
        }
    }

    /**
     * Valide le token CSRF
     */
    private function validateCsrfToken(?string $token): void
    {
        if (!CSRF::validateToken($token)) {
            throw new CSRFExeption(errors: ['Le token de sécurité est invalide!']);
        }
    }

    /**
     * Normalise une valeur (gère null, string vide, etc.)
     */
    private function normalizeValue($value)
    {
        if (is_null($value) || $value === '' || $value === 'null') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $value;
    }

    /**
     * Normalise une valeur booléenne
     */
    private function normalizeBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array($value, [1, '1', true, 'true'], true);
    }

    /**
     * Vérifie si une valeur est considérée comme vide
     */
    private function isEmpty($value): bool
    {
        return is_null($value) || $value === '' || $value === 'null';
    }
}
