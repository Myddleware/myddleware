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
		'delete' => 'advinv_delete',
		'retrieve' => 'advinv_retrieve',
	];

    /**
     * List of modules managed through dbrecord webservices.
     */
    protected $dbRecordModules = [
        'ProductPricebook'
    ];

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
                    throw new \Exception($resultCreate["error"]["message"] ?? json_encode($create).' DATA: '.json_encode($data));
                }
                $result[$idDoc] = [
                    'id' => $this->assignIdDbRecordModule($param['module'], $create),
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
     *
     */
    protected function describeDbRecordModule($module)
    {
        $vtigerClient = $this->getVtigerClient();

        $describe = $vtigerClient->post([
            'form_params' => [
                'operation' => 'dbrecord_crud_row',
                'sessionName' => $vtigerClient->getSessionName(),
                'name' => $module,
                'mode' => 'describe'
            ],
        ]);

        return $describe;
    }

    /**
     * @param $module
     * @param $create
     * @return string
     */
    protected function assignIdDbRecordModule($module, $create)
    {
        $describe = $this->describeDbRecordModule($module);

        $id = [];
        foreach ($describe['result']['result']['columns'] as $field) {
            if ($field['isprimarykey']) {
                $id[] = $create['result']['record'][$field['paramname']];
            }
        }

        #file_put_contents('../var/logs/mio.log', json_encode($create['result'])."\n", FILE_APPEND);

        return implode('_', $id);
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
