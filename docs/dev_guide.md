# Developer's guide

All Myddleware improvements are welcome, and we particularly encourage community members to contribute to our connectors list by making a pull request so that the whole community can benefit from your work.
To help you write your own connector, please read the following guidelines for successful connector creation.

!>You may also have some very specific needs that simply require customising Myddleware to fit your own context. If that's the case, please refer to the "Ensuring your custom code is upgrade-safe in Myddleware" section of this documentation.

## Create your own connectors

### Requirements

Before you can connect a new application to Myddleware, you need to check that the application you want to connect has a webservice API with methods to read data (at the very least) 
and hopefully has a documentation website available to help you connect Myddleware to the target application. 

> Most Myddleware applications are connected using REST API, however this is not the only option.

#### Declare your new connector's name & store it in database

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

#### Download source API SDKs

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

#### Add the new connector to your current database

In your terminal, load Myddleware fixtures:

```bash
        php bin/console doctrine:fixtures:load --append
```

> Check in Myddleware if the new connector is already available.

Now, let's create a new connector class, in [/src/Solutions](https://github.com/Myddleware/myddleware/tree/main/src/Solutions), the file name must be the same as the name of your class (this is due to autoloading). You can use the code of another class for inspiration. For example, check out "SuiteCRM.php":

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

> Tip: regarding error handling, there are several options. You should throw exceptions using a try/catch method. You should also log errors using Symfony logger. In case of errors, the error message will be sent to the ```background.log```, ```prod.log``` & ```dev.log`` files, depending on your environment.

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

### getFieldsLogin() method

In the new connector class, you need to implement the getFieldsLogin() method. Here, you have to put the parameters required to connect to your solution.

For example, to login to the WooCommerce API, we need a URL, Consumer Key & Consumer Secret  :

```php
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
```

> Check that everything is working in Myddleware

![view fields_login](images/dev_guide/suitecrm_create.PNG)

*We can now log into Myddleware*

### login() method

Now to connect your connector, we need to create a new function in your class, we will call it "login".

Example code, available in the file ```myddleware/src/Solution/suitecrm.php```

You have to add this function login to check the connexion with you application.

Make sure every error is catched and "this->connexion_valide = true" if the connexion works.

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

To debug this function, you can click on the button "Test" and check the result in firebug for example. The function will be called each time you click on "Test", no need to refresh the page.

*Let's now create the first rule*

### Method get_modules

Still in your connector class, we need to create a function that will display the list of modules in our connector. Create a "get_modules" function.

Here, you have to add the module you want to connect in the method.

In input you have access to the type of connexion, if your solution is in the target or in source of the rule. Some module could be available only source or only in target.

You then return an array with a list of module:

```php
        //Get module list
        public function get_mudules($type = 'source')
        {
                return [
                        'contacts' => 'Contacts',
                        'orders'   => 'Orders',
                        'products' => 'Products',
                ]
        }
```

**Other code examples are available in the:**

```myddleware/src/Solutions/sugarcrm.php```

Now you can debug (with firebug for example) your function when the module list is called in the rule creation view(in "Choose your module") :

![view modules](images/dev_guide/view_modules.PNG)

Next step is the fields mapping, we now need to create a function for it.

### Method get_module_fields

You have to indicate to Myddleware what fields are available for each module. If your application has a function which describe all fields for every module, you should use it. For example, we did it for Salesforce or Prestashop. Otherwise you have to describe every field.

- Add the function get_module_fields in you class.

<!-- tabs:start -->

#### **Input**

- Module indicate from which module we need the fields

- Type indicate if the module is in source or in target in the rule

#### **Output**

- An array with all the fields for the module
You should then add the fields related (field available to create relationship) and the class attribute this->fieldsRelate

- Your fields will then be displayed after clicking on the button “Go to fields mapping”. You can refresh this page, your function will be called each time this page is loaded :

<!-- tabs:end -->

![view modules fields](images/dev_guide/modules_fields.PNG)

*Create a mapping and save the rule. We will now create the function read*

### Method read

> The read function is one of the most important function in the connector.

The read function has to be able to :

- Read records from a reference (usually the modified datetime)
- Read a specific record using the record id
- Search a record with a criteria (used in duplicate search) => only if you use your application as a target, not only a source
- You can open your rule, tab parameter, and click “Simulate transfer”, this button will call the read function :

![Simulate transfer](images/dev_guide/simulate_transfer.PNG)

You can also run your rule in a command prompt :

        php bin/console myddleware:synchro <your rule id> –env=background

Here is an example of input value :

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
