# Developer's guide

All Myddleware improvements are welcome, and we particularly encourage community members to contribute to our connectors list by making a pull request so that the whole community can benefit from your work.
To help you write your own connector, please read the following guidelines for successful connector creation.
We also strongly recommend checking out the source code of all existing connectors inside the [/src/Solutions folder](https://github.com/Myddleware/myddleware/blob/main/src/Solutions/) to help you implementing all the required methods.

!>You may also have some very specific needs that simply require customising Myddleware to fit your own context. If that's the case, please refer to the "Ensuring your custom code is upgrade-safe in Myddleware" section of this documentation.

## Create your own connectors

### Requirements

Before you can connect a new application to Myddleware, you need to check that the application you want to connect has a webservice API with methods to read data (at the very least) 
and hopefully has a documentation website available to help you connect Myddleware to the target application. 

> Most Myddleware applications are connected using REST API, however this is not the only option.

#### Declare your new connector's name

First you will need to add your new connector to the ``solution`` table in your database, using Doctrine Fixtures,
and more specifically the ``LoadSolutionData`` class, located in
[/src/DataFixtures/LoadSolutionData.php](https://github.com/Myddleware/myddleware/blob/main/src/DataFixtures/LoadSolutionData.php). 
To do so, add a new entry in ``$solutionData`` in  for your new connector :

```php
    protected $solutionData = [
            ['name' => 'sugarcrm',   'active' => 1, 'source' => 1, 'target' => 1],
            ['name' => 'vtigercrm',   'active' => 1, 'source' => 1, 'target' => 1],
            ['name' => 'salesforce',  'active' => 1, 'source' => 1, 'target' => 1],
            ['name' => 'prestashop',  'active' => 1, 'source' => 1, 'target' => 1],
            // Your connector
            ['name' => 'myconnector',  'active' => 1, 'source' => 1, 'target' => 1],
    ];
```

In [/src/Manager/SolutionManager.php](https://github.com/Myddleware/myddleware/blob/main/src/Manager/SolutionManager.php), add your new connector to the ``SolutionManager`` class.

First, add the use statement at the top of the SolutionManager class :

```php
    use App\Solutions\WooEventManager;
    use App\Solutions\WordPress;
    use App\Solutions\Zuora;
    // Your new connector
    use App\Solutions\MyConnector;
```

Then still in ``SolutionManager``, add the new connector to the constructor.

```php
    public function __construct(
        WordPress $wordPress,
        WooCommerce $wooCommerce,
        WooEventManager $wooEventManager,
         // Your connector
         MyConnector $myConnector
    ) {
        $this->classes = [
            'wordpress' => $wordPress,
            'wooeventmanager' => $wooEventManager,
            'woocommerce' => $wooCommerce,
             // Your connector
            'myconnector' => $myConnector
        ];
    }
```

#### Download source API SDKs (optional)

!> This step is optional and will vary according to the type of API you would like to connect to Myddleware. For this part, you must refer to the source API documentation.

In your terminal, you might need to download an SDK for the new API. 
For instance, [the WooCommerce REST API documentation](https://woocommerce.github.io/woocommerce-rest-api-docs/#introduction)
tells us that we need to add the **automattic/woocommerce** dependency to our Myddleware project in order to be able to login to the REST API. To do so, we ran :

```bash
       composer require automattic/woocommerce
```

Then, we implemented the Client described in their documentation inside our login(), create(), update() & read() methods. 
Here is a sample of the code using the third-party client:

````php
<?php

namespace App\Solutions;

use Automattic\WooCommerce\Client;

...

class woocommercecore extends solution
{
    protected $apiUrlSuffix = '/wp-json/wc/v3/';
    protected $url;
    protected $consumerKey;
    protected $consumerSecret;
    protected $woocommerce;
    
    ...
    
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        $this->woocommerce = new Client(
            $this->paramConnexion['url'],
            $this->paramConnexion['consumerkey'],
            $this->paramConnexion['consumersecret'],
            [
                'wp_api' => true,
                'version' => 'wc/v3',
            ]
            );
        if ($this->woocommerce->get('data')) {
            $this->connexion_valide = true;
        }
    }
}

    public function upsert($method, $param)
    {
        ...
        foreach ($param['data'] as $idDoc => $data) {
                $param['method'] = $method;
                $module = $param['module'];
                $data = $this->checkDataBeforeCreate($param, $data, $idDoc);

                if ('create' === $method) {
                    unset($data['target_id']);
                    $recordResult = $this->woocommerce->post($module, $data);
                } else {
                    $targetId = $data['target_id'];
                    unset($data['target_id']);
                    $recordResult = $this->woocommerce->put($module.'/'.$targetId, $data);
                }          
            ...
        }
    }
    
    public function read($param){
    ...
        $response = $this->woocommerce->get($module, [
            'orderby' => 'modified',
            'per_page' => $this->callLimit,
            'page' => $page, ]
        );
    ...        
    }
    ...
````

### Add the new connector to your current database

In your terminal, load Myddleware fixtures. This will store your new connector's name inside the database.

```bash
        php bin/console doctrine:fixtures:load --append
```
Your new connector should appear inside the ``solution`` table of your database

![Database solutions table](images/dev_guide/solutions_table.png)

> Go to your Myddleware interface to check whether the new connector is already available.

### Creating the Connector file

Now, let's create a new solution(connector) class, in [/src/Solutions](https://github.com/Myddleware/myddleware/tree/main/src/Solutions). 
The file name must be the same as the name of your class (this is due to autoloading). 

!> All Connectors (Solutions) extend the Myddleware parent class ```Solution```. This class contains a variety of methods which you may override to fit your connector's needs. 
We strongly recommend you [check it out](https://github.com/Myddleware/myddleware/blob/main/src/Solutions/solution.php) when in doubt as this class acts as the backbone of all Myddleware connectors.

You can use the code of another class for inspiration. For example, check out [SuiteCRM.php](https://github.com/Myddleware/myddleware/tree/main/src/Solutions/suitecrm.php):

```php
        namespace App\Solutions;

        use Symfony\Component\Form\Extension\Core\Type\PasswordType;
        use Symfony\Component\Form\Extension\Core\Type\TextType;

        class SuiteCRM extends Solution
        {
        protected $limitCall = 100;
        protected $urlSuffix = '/service/v4_1/rest.php';
        // Enable to read deletion and to delete data
        protected $readDeletion = true;
        protected $sendDeletion = true;

        protected $required_fields = ['default' => ['id', 'date_modified', 'date_entered']];
        
        ...
        }
```

#### Add the solution's logo

Finally, if you want to display the application's logo, add the image corresponding to your application with the png format and size 64*64 pixels in [assets/images/solution](https://github.com/Myddleware/myddleware/tree/main/assets/images/solution)
Once you've added the new image to the assets directory, you need to build Myddleware again in order for the image to be loaded to the Myddleware UI.
To do so, you can run either ``yarn watch`` (in dev environment) or ```yarn build``` (in production environment).


### Error handling

!> Tip: regarding error handling, there are several options. You should throw exceptions using a try/catch method. 
You should also log errors using [Symfony logger](https://symfony.com/doc/current/logging.html). In case of errors, the error message will be sent to the ```background.log```, ```prod.log``` & ```dev.log``` files, depending on your environment.

Here is an example method from our [WordPress.php](https://github.com/Myddleware/myddleware/blob/main/src/Solutions/wordpress.php) file :

```php
        public function login($paramConnexion){
                parent::login($paramConnexion);
                try  {
                        ...
                }catch(\Exception $e){
                        $error = $e->getMessage();
                        $this->logger->error($error);
                        return array('error' => $error);
                }
        }
```

### Compulsory methods to implement

Here's a non-exhaustive list of all the methods you will need to implement inside your Connector class. Each method's implementation will vary according to your source application's specificities.
Please make sure you refer to its documentation for specifics such as modules lists, fields, formats, way to log in, etc.

!> Warning: these method names (and signatures), as well as some class names, properties & namespaces, will undergo some slight changes in Myddleware 4 for code quality & consistency reasons. For instance, get_module_fields() will become getModuleFields() to respect the camelCase standard. Some typos & spelling mistakes might get fixed too. 

| Method                       | Description                                                                                                                        | Arguments                      | Return type               |
|------------------------------|------------------------------------------------------------------------------------------------------------------------------------|--------------------------------|---------------------------|
| **getFieldsLogin()**         | This method retrieves the list of fields required to login to your source app. For instance, an email, a password & a URL.         |                                | array                     |
| **login()**                  | Connects to the source app.                                                                                                        | array $paramConnexion          | void                      |
| **get_modules()**            | Retrieves the list of modules your connector can read from inside your source application solution.                                | string $type = 'source'        | array                     |
| **get_modules_fields()**     | Retrieves the list of fields for each module your connector can read from inside your source application solution.                 | string $module, string $type = 'source', array $param = null | array $this->moduleFields |
| **read()**                   | The heart of Myddleware: this method reads data (documents) inside your source application and transforms it to Myddleware format. | array $param                   | array                     |
| **createData()**  *OPTIONAL* | Writes data inside your target application solution. (Not all APIs allow you to do so).                                            | array $param                   | array                     |
| **updateData()**  *OPTIONAL* | Similarly to createData(), this method allows you to update documents inside your target application solution.                     | array $param                   | array                     |

### getFieldsLogin() method

In the new connector class, you need to implement the ``getFieldsLogin()`` method. 
Here, you have to put the parameters required to connect to your solution.

For example, to log in to the WooCommerce API, we need a URL, a Consumer Key &  a Consumer Secret  :

```php

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

...

class woocommercecore extends solution
{
    public function getFieldsLogin()
    {
        return [
            [
                'name' => 'url',
                'type' => TextType::class,
                'label' => 'solution.fields.url',
            ],
            [
                'name' => 'consumerkey',
                'type' => PasswordType::class,
                'label' => 'solution.fields.consumerkey',
            ],
            [
                'name' => 'consumersecret',
                'type' => PasswordType::class,
                'label' => 'solution.fields.consumersecret',
            ],
        ];
    }
}
```

> Check that everything is working in Myddleware UI by  clicking on Connector->Creation, then select your application, the parameters you have added in your function should be visible.

![view fields_login](images/dev_guide/suitecrm_create.PNG)

*We can now log into Myddleware*

### login() method

Now to connect your connector, we need to implement the login() method in order for Myddleware to be able to connect to your source application when creating a connector & running a rule.
This method takes a ``$paramConnexion`` parameter which is an array containing the necessary data required to be able to log in.

Make sure every error is caught and ``this->connexion_valide`` is set to ``true`` if the connection was successful.

Example implementation : 

```php
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            $login_paramaters = [
                'user_auth' => [
                    'user_name' => $this->paramConnexion['login'],
                    'password' => md5($this->paramConnexion['password']),
                    'version' => '.01',
                ],
                'application_name' => 'myddleware',
            ];
            // remove index.php in the url
            $this->paramConnexion['url'] = str_replace('index.php', '', $this->paramConnexion['url']);
            // Add the suffix with rest parameters to the url
            $this->paramConnexion['url'] .= $this->urlSuffix;

            $result = $this->call('login', $login_paramaters, $this->paramConnexion['url']);

            if (false != $result) {
                if (empty($result->id)) {
                    throw new \Exception($result->description);
                }

                $this->session = $result->id;
                $this->connexion_valide = true;
            } else {
                throw new \Exception('Please check url');
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }
```

If you want to test out this method inside the Myddleware UI, you can already do it by filling in the "Create connector" form, filling in the fields & click on the "Test" button to check whether the Request & Response flow works properly.
In case of a successful connection to your source application, the light bulb icon should colour itself. Otherwise, an error message should appear below.

![Create connector form](/images/dev_guide/connector_method_login_test.png)

### get_modules() method

We now need to create a method which will display the list of modules available in our connector. As mentioned before, the way to retrieve modules will depend on your source application.
For instance, some applications will give you access to a method which will retrieve an up-to-date list of all available modules. You would then need to call this service and return its value as an
array here. However, some apps do not provide such method and you will therefore need to manually input the list of modules you would like to retrieve.

The ``type`` argument allows you to return a different set of modules depending on whether you are reading(``source``) or writing(``target``) inside your app. 
Indeed,some modules may be available as a source or as target only.

```php
<?php

namespace App\Solutions;
...

class myconnector extends solution
{
...
        public function get_modules($type = 'source')
        {
                return [
                        'contacts' => 'Contacts',
                        'orders'   => 'Orders',
                        'products' => 'Products',
                ]
        }
```


Now you can test out whether this method worked inside the Myddleware UI by going to the rule creation view, select your solution & then check whether the module list  returned in "Choose your module" is accurate.

![view modules select list](images/dev_guide/view_modules.PNG)


### get_module_fields() method

You have to indicate to Myddleware what fields are available for each module. 
If your application has a method which describes all fields for every module, you should use it. 
For example, you can check out the Salesforce & Prestashop connectors which resort to this strategy.
Otherwise, you will have to provide an array of all fields with a simple descrition for each one. 
We often store these lists in metadata files inside the [/src/Solutions/lib](https://github.com/Myddleware/myddleware/tree/main/src/Solutions/lib) folder.

#### Arguments

| Arguments                   | Description / values                                                                                                                                                | 
|-----------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| string **$module**          | The module to which a fields belongs to.                                                                                                                            |  
| string **$type = 'source'** | ``source`` or ``target`` <br/> If a module is only available as a target, set it to ``target``, else, the default is source (which works for both source & target)  |
| array **$param = null()**   | Additional optional parameters.                                                                                                                                     |

#### Signature

- An array with all the fields for the module

You should then add the related fields (field available to create relationship) and the property $this->fieldsRelate.

Example implementation :

````php
<?php

namespace App\Solutions;

...

class wordpresscore extends solution  {
    
    ...

    public function get_module_fields($module, $type = 'source', $param = null)
    {
        parent::get_module_fields($module, $type);
        try {
            require_once 'lib/wordpress/metadata.php';
            if (!empty($moduleFields[$module])) {
                $this->moduleFields = array_merge($this->moduleFields, $moduleFields[$module]);
            }

            if (!empty($fieldsRelate[$module])) {
                $this->fieldsRelate = $fieldsRelate[$module];
            }

            if (!empty($this->fieldsRelate)) {
                $this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
            }

            return $this->moduleFields;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());
            return false;
        }
    }
}   
````

Your fields will then be displayed after clicking on the button ``Go to fields mapping``.
You can refresh this page, your method will be called each time this page is loaded.

![view modules fields](images/dev_guide/modules_fields.PNG)


### read() method

> The read() method is the most important method of a connector. This is the true heart of Myddleware, where all the magic happens. Indeed, this method is the one that allows you to retrieve documents from your source application.

The read() method needs to be able to :

- Read records using a **reference** (usually a ``date_modified`` or ``updatedAt`` property)
- Read a specific record using the **record's id**
- Search for a record based on criteria (used in duplicate search) => only if you use your application as a target

If you want to check whether this method is correctly implemented inside the Myddleware UI, you can open your rule, click on the ``Parameters``, and click on ``Simulate documents``.
When you do, please remember to set a reference date in the past and to click on the ``Save`` button before launching the simulation. If all went well, you should get a number of documents to be read in the ``Estimated documents`` input.

![Simulate transfer](images/dev_guide/simulate_transfer.PNG)

You can also run your rule using a command prompt with : 

        php bin/console myddleware:synchro <your rule id> –-env=background

Here is an example of output value :

![Command synchro](images/dev_guide/command_synchro.PNG)

Parameters :

- Module is the module to read in your application
- Rule contains the parameters of the rule
- Date_ref is used to search all data modified or created after this reference
- RuleParams is the parameters of the rule
- Fields contains every fields mapped in the rule.
- Offset and limit are used only if your application has to be read with limited data
- jobId is the if of the job
- Manual indicate if the rule is run manually

Myddleware has to be able to read records from the source application. The list of fields returns must be the ones in the rule field mapping (input entry : fields). But some other fields are requiered : the id of the record and its reference (usually the modified record datetime). But the id and reference can be named differently depending on the application and the module.

> It is the reason why you have to create the attribute required_fields in your class :

IMAGE

The next step is to call the webservice function of your application depending on the input parameter :

If the entry query exists in input then it is prioritary, you have to use the query parameter to search records
If the entry query is empty then you have to use the reference. Myddleware must search all records created/modified ather the content of the parameter date_ref
The function should return an array with these entries :

count with the number of records read. Has to be 2 if no record are read.
date_ref with the new date_ref. It has to be the max date found in the list records returns.
values with an array of records. The key of these entries has to be the id of the record. The entry id and date_modified has to be present for each record. Date modified contains the value of the date_created or date_modified depending the type of the rule .

> Tips: for the date format, we need to create a function "DateTimeMyddleware", which converts the date Myddleware format, we can take inspiration from the function created in the ```myddleware/src/Solutions/woocommerce.php```. We will also need to convert the Myddleware format to your connector fromat if necessary.

The output of the function read should look like this:

IMAGE

### Method create

Create a rule now with your application in target. Then create the function public function create($param) in your class.

Run your rule as you did while developing the method read.

Here is an example of input value :

![Example input value](images/dev_guide/tuto_connecteur_method_create_input.png)

Parameters :

- data contains all the record Myddleware want to create in tour application. The key of each record is the id of the data trasfer in Myddleware
- module is the module to write in your application
- ruleId is teh id of the rule
- rule contains the parameters of the rule
- ruleFields contains the fields of the rule
- ruleParams is the parameters of the rule
- ruleRelationships contains the relationships with the current rule
- fieldsType contains the type of all fields in the rule
- jobId is the if of the job

The output of the function created should look like these :

        [
                [583f4dd2c4c843.39717569] => [
                        [id] => 65
                        [error] =>
                ]

                [583f4dd2c4c843.39717569] => [
                        [id] => 66
                        [error] =>
                ]
        ]

> To help writing code and not recreate time-consuming manipulation in Myddleware, we can use in your terminal :

Read a record:

        bin/console myddleware:readrecord <id of our rule> --env=background

Reread a document:

        bin/console myddleware:massaction rerun document <document id> --env=background

### Method update

The method update works in the same way as the method create. The output parameter must be built exactly like in the method create.

The only difference is that you have the entry “target_id” for each record in the array data. You will need this entry to update your data in your application.

## Create formula

In this article we‘ll look at an important point in your synchronization rules and one of the many setting options offered by Myddleware, formulas.

### The fundamentals

For starters, formulas allow you to format or to set the values that will be sent to a given target field . In other words, you have the option of adding fixed text to all uppercase, change timezones, concatenate several source fields etc.

### The syntaxe

To help, syntax highlighting (1) is available to you right on your text box. Furthermore you will find below, the list of source fields that you have chosen (2), the available functions and their categories (3) and one or two drop list(s) (4) of the different values for the list type fields (as SalutationID example).

![Formula](images/dev_guide/formula.PNG)

**Examples**

- Concatenate multiple fields, Myddleware uses the “.” as in PHP {field1}.{field2}.{fields3}

- Concatenate a fixed text with one or multiple fields “Client Name: “.{Firstname}.” “.{Lastname}

- Three-valued condition , “If the Greeting field is ‘Mr.’ then send 1, otherwise send 2” is written as followed : (({Greeting} == “Mr.”) ? “1” : “2”), those three-valued conditions can be nested in order, for example, to make the data correspond. Thus, ({resolution} == “10” ? “Open” : ({resolution} == “20” ? “Fixed” : ({resolution} == “30” ? “Reopened” : “Suspended”))) is correct and functional, this formula means “If resolution is 10 then ‘Open’ is sent, otherwise if resolution is 20 then ‘Fixed‘ is sent, otherwise if resolution is 30 then ‘Reopened’ is sent, otherwise ‘Suspended ‘ is sent.

- Add two fileds {field1} + {field2}

In this article we‘ll look at an important point in your synchronization rules and one of the many setting options offered by Myddleware, formulas.

**Functions**

In the formula of Myddleware, you can use the functions listed at the bottom right (see of the previous image).


This function round floating point (up), ([PHP](https://www.php.net/manual/fr/function.round.php)) **round(numbre [, clarification])**:

        round(525.6352, 2) // Gives 525.64

Rounds up, ([PHP](https://www.php.net/manual/fr/function.ceil.php)) **ceil(float)**:

        ceil(525.6352) // Gives 526

Returns the absolute value, ([PHP](https://www.php.net/manual/fr/function.abs.php)) **abs(number)**:

        abs(-5) // Gives 5

Deletes spaces (or other charachters) at the begenning and the end of a string, ([PHP](https://www.php.net/manual/fr/function.trim.php)) **trim(string [, Masque])**:

        trim(” bonjour “) // Returns “bonjour”

Lowercases all charachters, ([PHP](https://www.php.net/manual/fr/function.mb-strtolower.php)) **lower(STRING)**:

        lower(“BONJOUR”) // Returns “bonjour”

Uppercases all charachters, ([PHP](https://www.php.net/manual/fr/function.mb-strtoupper.php)) **upper(String)**:

        upper(“bonjour”) // Returns “BONJOUR”

Formats a local date/hour, ([PHP](https://www.php.net/manual/fr/function.date.php)) **date(Format [, Timestamp])**:

        date(“Y:m:d”) // Returns “2014:09:16”

Returns current Unix timestamp with microseconds, ([PHP](https://www.php.net/manual/fr/function.microtime.php)) **microtime([true if you want a float result])**:

        microtime(true) // Returns 1410338028.5745

Changes the timezone of the given date, ([PHP](https://www.php.net/manual/fr/timezones.php)) **changeTimeZone(Date you want to change, old timezone, new timezone)**:

        changeTimeZone(“2014-09-16 12:00:00”, “America/Denver”, “America/New_York”) // Returns “2014-09-16 14:00:00”

Changes the format of the given date, **changeFormatDate(Date you want to change, New format)**:

        changeTimeZone(“2014-09-16 12:00:00”, “Y/m/d H:i:s”) // Returns “2014/09/16 12:00:00”

Reads a string starting of the given Index, ([PHP](https://www.php.net/manual/fr/function.mb-substr.php)) **substr(String, Indexample)**:

        substr(“abcdef”, -1) // Returns “f”

Strips HTML and PHP tags from a string, ([PHP](https://www.php.net/manual/fr/function.strip-tags.php)) **striptags(String)**:

        striptags(“<p>Test paragraph.</p><!– Comment –> <a href=”#fragment”>Other text</a>”) // Returns “Test paragraph. Other text”

## Ensuring your custom code is upgrade-safe in Myddleware

**This section is still under construction**
