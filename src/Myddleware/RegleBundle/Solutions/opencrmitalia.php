<?php
namespace Myddleware\RegleBundle\Solutions;

use Javanile\VtigerClient\VtigerClient;

class opencrmitaliacore extends vtigercrm
{
	/** @var array inventoryModules */
	protected $inventoryModules = [
		"Invoice",
		"SalesOrder",
		"Quotes",
		"PurchaseOrder",
		"GreenTimeControl",
		"DDT",
	];

	/** @var array OperationsMap */
	protected $clientOperationsMap = [
		'create' => 'advinv_create',
		'update' => 'advinv_update',
		'retrieve' => 'advinv_retrieve',
	];

    /**
     * List of modules managed through dbrecord webservices.
     */
    protected $dbRecordModules = [
        'ProductPricebook'
    ];

    /**
     *
     */
    protected $cacheDescribedModules = [];

	/**
	 * Make the login
	 *
	 * @param array $paramConnexion
	 * @return void|array
	 */
	public function login($paramConnexion)
	{
		parent::login($paramConnexion);

		try {
			$args = [
				'endpoint' => $this->paramConnexion['url'],
				'operationsMap' => $this->clientOperationsMap,
			];
			$client = new VtigerClient($args);
			$result = $client->login(trim($this->paramConnexion['username']), trim($this->paramConnexion['accesskey']));

			if (!$result['success']) {
				throw new \Exception($result['error']['message']);
			}

			$this->session = $client->getSessionName();
			$this->connexion_valide = true;
			$this->vtigerClient = $client;
		} catch (\Exception $e) {
			$error = $e->getMessage();
			$this->logger->error($error);

			return ['error' => $error];
		}
	}

    /**
     * Return of the modules without the specified ones.
     *
     * @param string $type
     * @return array|bool
     */
    public function get_modules($type = 'source')
    {
        if ($this->notVtigerClient()) {
            return $this->errorMissingVtigerClient();
        }

        $modules = parent::get_modules($type);

        $dbRecordModules = [];
        foreach ($this->dbRecordModules as $module) {
            $dbRecordModules[$module] = $module;
        }

        return array_merge($modules, $dbRecordModules);
    }

    /**
     * Return the fields for a specific module without the specified ones.
     *
     * @param string $module
     * @param string $type
     *
     * @return array|bool
     */
    public function get_module_fields($module, $type = 'source')
    {
        if ($this->notVtigerClient()) {
            return $this->errorMissingVtigerClient();
        }

        if (in_array($module, $this->dbRecordModules)) {
            $fields = [];
            $describe = $this->describeDbRecordModule($module);
            foreach ($describe['result']['result']['columns'] as $field) {
                $field['name'] = $field['paramname'];
                $field['label'] = $field['paramname'];
                if ($field['columntype'] == 'reference') {
                    $field['type']['name'] = 'reference';
                }
                $fields[] = $field;
            }
            //var_dump($fields);
            //die();
            return $this->populateModuleFieldsFromVtigerModule($fields, $module, $type);
        }

        return parent::get_module_fields($module, $type);
    }

    /**
     * Read Last
     *
     * @param array $param
     * @return array
     */
    public function read_last($param)
    {
        if (!in_array($param['module'], $this->dbRecordModules)) {
            return parent::read_last($param);
        }

        if ($this->notVtigerClient()) {
            return $this->errorMissingVtigerClient(['done' => -1]);
        }

        $result = [];
        $vtigerClient = $this->getVtigerClient();

        if (empty($param['query']['id'])) {
            $select = $vtigerClient->post([
                'form_params' => [
                    'operation' => 'dbrecord_crud_row',
                    'sessionName' => $vtigerClient->getSessionName(),
                    'name' => $param['module'],
                    'mode' => 'select',
                    'element' => json_encode([
                        'limit' => 1,
                        'offset' => 0
                    ]),
                ],
            ]);
            if (empty($select['success']) || empty($select['result']['success'])) {
                throw new \Exception($select["error"]["message"] ?? json_encode($select));
            }
            if (empty($select['result']['records'][0])) {
                throw new \Exception("No records found on module '{$param[module]}'");
            }
            $result['values'] = $select['result']['records'][0];
        } elseif (in_array('id', $param['fields'])) {
            throw new \Exception(json_encode($param));
            $query = $this->getVtigerClient()->retrieve($param['query']['id']);
            $query['result'][0] = $query['result'];
        } else {
            $query = $this->explodeIdQueryDbRecordModule($param['query']['id'], $param['module']);
            $read = $vtigerClient->post([
                'form_params' => [
                    'operation' => 'dbrecord_crud_row',
                    'sessionName' => $vtigerClient->getSessionName(),
                    'name' => $param['module'],
                    'mode' => 'read',
                    'element' => json_encode($query),
                ],
            ]);
            if (empty($read['success']) || empty($read['result']['success'])) {
                throw new \Exception($read["error"]["message"] ?? json_encode($read));
            }
            $result['values'] = $read['result']['record'];
        }

        $result['done'] = true;

        return $result;
    }

    /**
     * Read
     *
     * @param array $param
     * @return array
     */
    public function read($param)
    {
        //file_put_contents('../var/logs/mio.log', json_encode($param)."\n", FILE_APPEND);

        if (!in_array($param['module'], $this->dbRecordModules)) {
            return parent::create($param);
        }

        if ($this->notVtigerClient()) {
            return $this->errorMissingVtigerClient(['done' => false]);
        }

        if (empty($param['offset'])) {
            $param['offset'] = 0;
        }

        if (empty($param['limit'])) {
            $param['limit'] = $this->limitPerCall;
        }

        $vtigerClient = $this->getVtigerClient();

        $result = [
            'count' => 0,
        ];

        try {
            $select = $vtigerClient->post([
                'form_params' => [
                    'operation' => 'dbrecord_crud_row',
                    'sessionName' => $vtigerClient->getSessionName(),
                    'name' => $param['module'],
                    'mode' => 'select',
                    'element' => json_encode([
                        //'orderby' => ''
                        'limit' => $param['limit'],
                        'offset' => 0
                    ]),
                ],
            ]);

            if (empty($select['success']) || empty($select['result']['success'])) {
                throw new \Exception($select["error"]["message"] ?? json_encode($select));
            }

            if (empty($select['result']['records'][0])) {
                throw new \Exception('No record found in module: '.$param['module']);
            }

            $result['values'] = [];
            foreach($select['result']['records'] as $record) {
                $id = $this->assignIdDbRecordModule($param['module'], $record);
                $result['date_ref'] = date('Y-m-d H:s:i');
                $result['values'][$id] = $record;
                if (in_array($param['rule']['mode'], ['0', 'S'])) {
                    $result['values'][$id]['date_modified'] = date('Y-m-d H:s:i');
                } elseif ($param['rule']['mode'] == 'C') {
                    $result['values'][$id]['date_modified'] = date('Y-m-d H:s:i');
                }
                $result['count']++;
            }
        } catch (\Exception $e) {
            $result['error'] = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
        }

        return $result;
    }

        /**
     * Create new record in target
     *
     * @param array $param
     * @return array
     */
    public function create($param)
    {
        if (!in_array($param['module'], $this->dbRecordModules)) {
            return parent::create($param);
        }

        if ($this->notVtigerClient()) {
            return $this->errorMissingVtigerClient();
        }

        $result = [];
        $vtigerClient = $this->getVtigerClient();
        foreach ($param['data'] as $idDoc => $data) {
            try {
                $create = $vtigerClient->post([
                    'form_params' => [
                        'operation' => 'dbrecord_crud_row',
                        'sessionName' => $vtigerClient->getSessionName(),
                        'name' => $param['module'],
                        'mode' => 'create',
                        'element' => json_encode($data),
                    ],
                ]);
                if (empty($create['success']) || empty($create['result']['success'])) {
                    throw new \Exception($create["error"]["message"] ?? json_encode($create).' DATA: '.json_encode($data));
                }
                $result[$idDoc] = [
                    'id' => $this->assignIdDbRecordModule($param['module'], $create['result']['record']),
                    'error' => false,
                ];
            } catch (\Exception $e) {
                $result[$idDoc] = array(
                    'id' => '-1',
                    'error' => $e->getMessage()
                );
            }

            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    /**
     * Update existing record in target
     *
     * @param array $param
     * @return array
     */
    public function update($param)
    {
        if (!in_array($param['module'], $this->dbRecordModules)) {
            return parent::update($param);
        }

        if ($this->notVtigerClient()) {
            return $this->errorMissingVtigerClient();
        }

        $result = [];
        $vtigerClient = $this->getVtigerClient();
        try {
            foreach ($param['data'] as $idDoc => $data) {
                $update = $vtigerClient->post([
                    'form_params' => [
                        'operation' => 'dbrecord_crud_row',
                        'sessionName' => $vtigerClient->getSessionName(),
                        'name' => $param['module'],
                        'mode' => 'update',
                        'element' => json_encode($data),
                    ],
                ]);
                if (empty($update['success']) || empty($update['result']['success'])) {
                    throw new \Exception($update["error"]["message"] ?? json_encode($update).' DATA: '.json_encode($data));
                }
                if (empty($result[$idDoc]['id'])) {
                    $result[$idDoc]['id'] = $this->assignIdDbRecordModule($param['module'], $update['result']['record']);
                }
                $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
        }

        return $result;
    }

    /**
     *
     */
    protected function describeDbRecordModule($module)
    {
        if (isset($this->cacheDescribedModules[$module])) {
            return $this->cacheDescribedModules[$module];
        }

        $vtigerClient = $this->getVtigerClient();

        $describe = $vtigerClient->post([
            'form_params' => [
                'operation' => 'dbrecord_crud_row',
                'sessionName' => $vtigerClient->getSessionName(),
                'name' => $module,
                'mode' => 'describe'
            ],
        ]);

        if (empty($describe['success']) || empty($describe['result']['success'])) {
            throw new \Exception($describe["error"]["message"] ?? json_encode($describe).' MODULE: '.$module);
        }

        $this->cacheDescribedModules[$module] = $describe;

        return $describe;
    }

    /**
     * @param $module
     * @param $create
     * @return string
     */
    protected function assignIdDbRecordModule($module, $record)
    {
        $describe = $this->describeDbRecordModule($module);

        $id = [];
        foreach ($describe['result']['result']['columns'] as $field) {
            if ($field['isprimarykey']) {
                $keyPart = '0';
                if (isset($record[$field['columnname']]) && $record[$field['columnname']]) {
                    $keyPart = $record[$field['columnname']];
                } elseif (isset($record[$field['paramname']]) && $record[$field['paramname']]) {
                    $keyPart = $record[$field['paramname']];
                }
                $id[] = $keyPart;
            }
        }

        #file_put_contents('../var/logs/mio.log', json_encode($create['result'])."\n", FILE_APPEND);

        return implode('_', $id);
    }

    /**
     * @param $id
     * @param $module
     * @return string
     */
    protected function explodeIdQueryDbRecordModule($id, $module)
    {
        $describe = $this->describeDbRecordModule($module);

        $index = 0;
        $query = [];
        $id = explode('_', $id);
        foreach ($describe['result']['result']['columns'] as $field) {
            if ($field['isprimarykey']) {
                //$query[$field['columnname']] = $id[$index];
                $query[$field['paramname']] = $id[$index];
                $index++;
            }
        }

        #file_put_contents('../var/logs/mio.log', json_encode($create['result'])."\n", FILE_APPEND);

        return $query;
    }
}

/* * * * * * * *  * * * * * *  * * * * * *
    si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__ . '/../Custom/Solutions/opencrmitalia.php';
if (file_exists($file)) {
	require_once $file;
} else {
	//Sinon on met la classe suivante
	class opencrmitalia extends opencrmitaliacore
	{
	}
}
