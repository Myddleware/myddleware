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
        if (in_array($module, $this->dbRecordModules)) {
            $vtigerClient = $this->getVtigerClient();
            $describe = $vtigerClient->post([
                 'form_params' => [
                     'operation' => 'dbrecord_crud_row',
                     'sessionName' => $vtigerClient->getSessionName(),
                     'name' => $module,
                     'mode' => 'describe'
                 ],
            ]);
            $fields = [];
            foreach ($describe['result']['result']['columns'] as $field) {
                $field['name'] = $field['paramname'];
                $fields[] = $field;
            }
            return $this->populateModuleFieldsFromVtigerModule($fields, $module, $type);
        }

        return parent::get_module_fields($module, $type);
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
