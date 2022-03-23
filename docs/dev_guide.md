# Developer's guide

## Create your own connectors

### Requirements

The application you want to connect needs to have a webservice API with methods to read data (at the very least) and hopefully have a documentation website available to help you connect Myddleware to the target application. 

> Most Myddleware applications are connected using REST API, however this is not the only option.

First you will need to add your new connector to the solution table in your database, using Doctrine Fixtures, and more specifically the LoadSolutionData class, located in [/src/DataFixtures/LoadSolutionData.php](https://github.com/Myddleware/myddleware/blob/main/src/DataFixtures/LoadSolutionData.php). To do so, add a new entry in $solutionData in  for your new connector :

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

In [/src/Manager/SolutionManager.php](https://github.com/Myddleware/myddleware/blob/main/src/Manager/SolutionManager.php), add your new connector to the SolutionManager class.

First, add the use statement at the top of the SolutionManager class :

```php
...
use App\Solutions\WooEventManager;
use App\Solutions\WordPress;
use App\Solutions\Zuora;
// Your new connector
use App\Solutions\MyConnector;
```

Then still in SolutionManager, add the new connector to the constructor.

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

In your terminal, you might need to download an SDK for the new API. For instance, [the WooCommerce REST API documentation](https://woocommerce.github.io/woocommerce-rest-api-docs/#introduction) tells us that we need to add the **automattic/woocommerce** dependency to our Myddleware project in order to be able to login to the REST API. To do so, we ran :

```bash
       composer require automattic/woocommerce
```

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

## API Overwiew

The API is built to allow you to call Myddleware from your application using REST protocol. For example, you will be able to call a specific Myddlewere ‘s rule when a specific event happends in your application. You will also be able to synchronize a specific record using a call to Myddleware.

Please find the [postman collection here](https://documenter.getpostman.com/view/1328767/SzS7QmCj?version=latest#e564597d-ef6e-40e1-87f1-c69b7b2d7479).

Please find our [php sample code](https://github.com/Myddleware/myddleware_api).

### Authentification

The function login_check is used to get a bearer tocken from Myddleware. This token well be required for all calls to Myddleware.

Here are [CURL info](https://documenter.getpostman.com/view/1328767/SzS7QmCj?version=latest#e564597d-ef6e-40e1-87f1-c69b7b2d7479) :

```
        curl --location --request POST 'http://localhost/myddleware/web/api/v1_0/login_check' \
        --header 'Content-Type: application/json' \
        --data-raw '{
         "username":"username",
         "password":"password"
        }'
```

**Output :**

<!-- tabs:start -->
#### **if success :**

```php
        {
        « token »: <token>
        }
```

#### **If error :**
```php
        {
        « code »: <error code>,
        « message »: <error message>
        }
```
<!-- tabs:end -->

### Function synchro

Use the synchro function to run a specific rule or every active rules.

Here are [CURL info](https://documenter.getpostman.com/view/1328767/SzS7QmCj?version=latest#96de2d5c-7a77-4d50-aa15-2657663459b2) :

```
        curl --location --request POST 'http://localhost/myddleware/web/api/v1_0/synchro' \
        --header 'Content-Type: application/json' \
        --form 'rule=<rule id>'
```

Set the rule id you want to run in Myddleware. Set ALL if you want to reun avery active rules.

**Output :**

```php
        {
        « error »: «  »,
        « jobId »: « 5e78c2cc32c926.66619369 »,
        « jobData »: {
                « Close »: « 2 »,
                « Cancel »: 0,
                « Open »: 0,
                « Error »: 0,
                « paramJob »: « Synchro : 5e5e5535564c0 »,
                « solutions »: « ^6^,^27^ »,
                « duration »: 13.43,
                « myddlewareId »: « 5e78c2cc32c926.66619369 »,
                « Manual »: 1,
                « Api »: 1,
                « jobError »: «  »,
                « documents »: [
                {
                        « id »: « 5e78c2cd317ac3.60227368 »,
                        « rule_id »: « 5e5e5535564c0 »,
                        « date_created »: « 2020-03-23 14:08:13 »,
                        « date_modified »: « 2020-03-23 14:08:24 »,
                        « created_by »: « 1 »,
                        « modified_by »: « 1 »,
                        « status »: « Send »,
                        « source_id »: « 4×72 »,
                        « target_id »: « 878987c2-4000-7c15-c353-5e63a95c2397 »,
                        « source_date_modified »: « 2017-10-09 14:46:34 »,
                        « mode »: « 0 »,
                        « type »: « U »,
                        « attempt »: « 1 »,
                        « global_status »: « Close »,
                        « parent_id »: «  »,
                        « deleted »: « 0 »
                },
                {
                        « id »: « 5e78c2ceab9782.10596576 »,
                        « rule_id »: « 5e5e5535564c0 »,
                        « date_created »: « 2020-03-23 14:08:14 »,
                        « date_modified »: « 2020-03-23 14:08:25 »,
                        « created_by »: « 1 »,
                        « modified_by »: « 1 »,
                        « status »: « Send »,
                        « source_id »: « 4×73 »,
                        « target_id »: « d28dc52c-68db-f679-5c80-5e63a9287457 »,
                        « source_date_modified »: « 2020-03-09 15:33:14 »,
                        « mode »: « 0 »,
                        « type »: « U »,
                        « attempt »: « 1 »,
                        « global_status »: « Close »,
                        « parent_id »: «  »,
                        « deleted »: « 0 »
                }
                ]
        }
        }
```

### Function read record

Use the read record function to force Myddleware to read a specific record into your application. For example. you can call this function when a record is saved into your application. Myddleware will read it from your source application and send it to your target application. It is used when you need a real time synchronisation.

Here are [CURL info](https://documenter.getpostman.com/view/1328767/SzS7QmCj?version=latest#68ee49d2-2fea-47ca-92ad-5d2345b6ba0c) :

```
        curl --location --request POST 'http://localhost/myddleware/web/api/v1_0/read_record' \
        --form 'rule=<your rule id>' \
        --form 'filterQuery=<field>' \
        --form 'filterValues=<value>'
```

**Set these parameters :**

- rule : The rule you want to run
- filterQuery : The field used to build the query executed into your application by Myddleware. It is usually the « id » field ‘s name of your record.
- filterValue : The field value used in the parameter filterQuery. It is usually the id of your record.

**Output :**

```php
        {
        « error »: «  »,
        « jobId »: « 5e78c4c4ec8631.31640728 »,
        « jobData »: {
                « Close »: 0,
                « Cancel »: « 1 »,
                « Open »: 0,
                « Error »: 0,
                « paramJob »: « read records wilth filter id IN (4×60) »,
                « solutions »: « ^6^,^27^ »,
                « duration »: 2.42,
                « myddlewareId »: « 5e78c4c4ec8631.31640728 »,
                « Manual »: 1,
                « Api »: 1,
                « jobError »: «  »,
                « documents »: [
                {
                        « id »: « 5e78c4c5f27231.38354025 »,
                        « rule_id »: « 5e5e5535564c0 »,
                        « date_created »: « 2020-03-23 14:16:37 »,
                        « date_modified »: « 2020-03-23 14:16:39 »,
                        « created_by »: « 1 »,
                        « modified_by »: « 1 »,
                        « status »: « No_send »,
                        « source_id »: « 4×60 »,
                        « target_id »: « e559cfe4-4e41-e2da-235e-5e63a985d98e »,
                        « source_date_modified »: « 2017-10-09 14:46:24 »,
                        « mode »: « 0 »,
                        « type »: « U »,
                        « attempt »: « 0 »,
                        « global_status »: « Cancel »,
                        « parent_id »: «  »,
                        « deleted »: « 0 »
                }
                ]
        }
        }
```

### Function mass action

Use the mass action function to change (rerun, cancel, remove, restore or change the status)  a group of data transfer.

Here are [CURL info](https://documenter.getpostman.com/view/1328767/SzS7QmCj?version=latest#eef6a218-fc20-4d3b-8d49-59b9d3d221cb) :

```
        curl --location --request POST 'http://localhost/myddleware/web/api/v1_0/mass_action' \
        --header 'action: ' \
        --form 'action=restore' \
        --form 'dataType=rule' \
        --form 'ids=5e5e5535564c0' \
        --form 'forceAll=Y'
```

**Set these parameters :**

- action : rerun, cancel, remove, restore or changeStatus.
- dataType : rule or document. If you want to select data transfer using a rule (all data transfer of the rule) as the filter set « rule ». Otherwise set « document » if you want to filter your search by data transfer id.
- ids : set the id(s) of the data transfer (document) or the rule depending of what you have set in the parameter dataType. If you put several ids, then put commas to separate them.
- forceAll (optional) : Set Y to process action on all data transfer (not only open and error ones). In this case, be carefull, you could remove, cancel or change status of data successfully sent to your target application. Myddleware could generate duplicate data in your target application if you run again your rule without setting duplicate fields.
- fromStatus : Only used with action changeStatus action. Add filter to select data transfer depending of their status.
- toStatus : Only used with action changeStatus action. New status to be set on all data transfer selected.

**Output :**

```php
        {
        « error »: «  »,
        « jobId »: « 5e78dcacb8f1d0.97025863 »,
        « jobData »: {
                « Close »: « 30 »,
                « Cancel »: « 70 »,
                « Open »: 0,
                « Error »: 0,
                « paramJob »: « Mass remove on data type rule »,
                « solutions »: « ^6^,^27^ »,
                « duration »: 10.28,
                « myddlewareId »: « 5e78dcacb8f1d0.97025863 »,
                « Manual »: 1,
                « Api »: 1,
                « jobError »: «  »,
                « documents »: [
                {
                        « id »: « 5e6685af04d858.22701783 »,
                        « rule_id »: « 5e5e5535564c0 »,
                        « date_created »: « 2020-03-09 18:06:39 »,
                        « date_modified »: « 2020-03-23 15:58:36 »,
                        « created_by »: « 1 »,
                        « modified_by »: « 1 »,
                        « status »: « Cancel »,
                        « source_id »: « 4×72 »,
                        « target_id »: null,
                        « source_date_modified »: « 2020-03-09 19:06:39 »,
                        « mode »: « 0 »,
                        « type »: « D »,
                        « attempt »: « 0 »,
                        « global_status »: « Cancel »,
                        « parent_id »: «  »,
                        « deleted »: « 1 »
                },
                {
                        « id »: « 5e6685fd2cfbb8.35868016 »,
                        « rule_id »: « 5e5e5535564c0 »,
                        « date_created »: « 2020-03-09 18:07:57 »,
                        « date_modified »: « 2020-03-23 15:58:36 »,
                        « created_by »: « 1 »,
                        « modified_by »: « 1 »,
                        « status »: « Cancel »,
                        « source_id »: « 4×72 »,
                        « target_id »: null,
                        « source_date_modified »: « 2020-03-09 19:07:57 »,
                        « mode »: « 0 »,
                        « type »: « D »,
                        « attempt »: « 0 »,
                        « global_status »: « Cancel »,
                        « parent_id »: «  »,
                        « deleted »: « 1 »
                },
                {
                        « id »: « 5e668618964e93.34804135 »,
                        « rule_id »: « 5e5e5535564c0 »,
                        « date_created »: « 2020-03-09 18:08:24 »,
                        « date_modified »: « 2020-03-23 15:58:36 »,
                        « created_by »: « 1 »,
                        « modified_by »: « 1 »,
                        « status »: « Cancel »,
                        « source_id »: « 4×72 »,
                        « target_id »: null,
                        « source_date_modified »: « 2020-03-09 19:08:24 »,
                        « mode »: « 0 »,
                        « type »: « D »,
                        « attempt »: « 0 »,
                        « global_status »: « Cancel »,
                        « parent_id »: «  »,
                        « deleted »: « 1 »
                },
                {
                        « id »: « 5e669dcd915191.00190986 »,
                        « rule_id »: « 5e5e5535564c0 »,
                        « date_created »: « 2020-03-09 19:49:33 »,
                        « date_modified »: « 2020-03-23 15:58:37 »,
                        « created_by »: « 1 »,
                        « modified_by »: « 1 »,
                        « status »: « Cancel »,
                        « source_id »: « 4×72 »,
                        « target_id »: null,
                        « source_date_modified »: « 2020-03-09 20:49:33 »,

```

```
                […………………………………………………………………………]
                        « mode »: « 0 »,
                        « type »: « U »,
                        « attempt »: « 1 »,
                        « global_status »: « Close »,
                        « parent_id »: «  »,
                        « deleted »: « 1 »
                }
                ]
        }
        }
```

### Function rerun error

Use the rerun error function to run again data transfer in error.

Here are [CURL info](https://documenter.getpostman.com/view/1328767/SzS7QmCj?version=latest#34418cf2-e545-4423-8411-c2784785b2bd) :

```
        curl --location --request POST 'http://localhost/myddleware/web/api/v1_0/rerun_error' \
        --form 'limit=10' \
        --form 'attempt=5'
```

**Set these parameters :**

- limit : set the limit parameter to limit the number of data transfer selected by the job.
- attempt : Myddleware will read only data transfer with a number of attemps <= at this parameter

**Output :**

```php
        {
        « error »: «  »,
        « jobId »: « 5e78e6cdd789e7.70152075 »,
        « jobData »: {
                « Close »: 0,
                « Cancel »: 0,
                « Open »: 0,
                « Error »: « 3 »,
                « paramJob »: « Rerun error : limit 3, attempt 5 »,
                « solutions »: « ^14^,^6^,^3^ »,
                « duration »: 1.09,
                « myddlewareId »: « 5e78e6cdd789e7.70152075 »,
                « Manual »: 1,
                « Api »: 1,
                « jobError »: «  »,
                « documents »: [
                {
                        « id »: « 5e612055c98da7.22680820 »,
                        « rule_id »: « 5e611b50c0a6f »,
                        « date_created »: « 2020-03-05 15:52:53 »,
                        « date_modified »: « 2020-03-23 16:41:50 »,
                        « created_by »: « 1 »,
                        « modified_by »: « 1 »,
                        « status »: « Error_sending »,
                        « source_id »: « efa139c1-5e46-b247-739d-5ba8412aa24a »,
                        « target_id »: null,
                        « source_date_modified »: « 2018-09-24 01:43:35 »,
                        « mode »: « 0 »,
                        « type »: « C »,
                        « attempt »: « 6 »,
                        « global_status »: « Error »,
                        « parent_id »: «  »,
                        « deleted »: « 0 »
                },
                {
                        « id »: « 5e72552e4fe687.45181957 »,
                        « rule_id »: « 5e5cc8984ba84 »,
                        « date_created »: « 2020-03-18 17:06:54 »,
                        « date_modified »: « 2020-03-23 16:41:50 »,
                        « created_by »: « 1 »,
                        « modified_by »: « 1 »,
                        « status »: « Error_sending »,
                        « source_id »: « 8 »,
                        « target_id »: null,
                        « source_date_modified »: « 2019-02-06 22:07:59 »,
                        « mode »: « 0 »,
                        « type »: « C »,
                        « attempt »: « 6 »,
                        « global_status »: « Error »,
                        « parent_id »: «  »,
                        « deleted »: « 0 »
                },
                {
                        « id »: « 5e72552e51d612.98166090 »,
                        « rule_id »: « 5e5cc8984ba84 »,
                        « date_created »: « 2020-03-18 17:06:54 »,
                        « date_modified »: « 2020-03-23 16:41:50 »,
                        « created_by »: « 1 »,
                        « modified_by »: « 1 »,
                        « status »: « Error_sending »,
                        « source_id »: « 12 »,
                        « target_id »: « 15 »,
                        « source_date_modified »: « 2020-03-03 10:43:31 »,
                        « mode »: « 0 »,
                        « type »: « U »,
                        « attempt »: « 6 »,
                        « global_status »: « Error »,
                        « parent_id »: «  »,
                        « deleted »: « 0 »
                }
                ]
        }
        }
```

### Function delete record

Use the delete record function to delete a specific record into the target application using the id of the source application.

Here are [CURL info](https://documenter.getpostman.com/view/1328767/SzS7QmCj?version=latest#5f6f441e-ac1e-468e-bd5f-ab8fd67c6c88) :

```
        curl --location --request POST 'http://localhost/myddleware/web/api/v1_0/delete_record' \
        --form 'rule=5e5e5535564c0' \
        --form 'recordId=4x65' \
        --form 'reference=2020-03-09 12:14:36' \
        --form 'lastname=lastname01' \
        --form 'email=test@test.test' \
        --form 'firstname=firstname01'
```

**Set these parameters :**

- rule : The id of the rule
- recordId : The id of the record in the source application. Then Myddleware will use the rule to get the id of this record in the target application and delete it.
- reference : the reference date or id used in Myddleware. Use the reference field already used in this rule.
- Each field of the rule have to be added as input parameters

**Output :**

```php
        {
        « error »: «  »,
        « jobId »: « 5e78e7a7621400.01014726 »,
        « jobData »: {
                « Close »: 1,
                « Cancel »: 0,
                « Open »: 0,
                « Error »: 0,
                « paramJob »: « Delete record 4×63 in rule 5e5e5535564c0 »,
                « solutions »: « ^6^,^27^ »,
                « duration »: 0.32,
                « myddlewareId »: « 5e78e7a7621400.01014726 »,
                « Manual »: 1,
                « Api »: 1,
                « jobError »: «  »,
                « documents »: [
                {
                        « id »: « 5e78e7a766e656.99183539 »,
                        « rule_id »: « 5e5e5535564c0 »,
                        « date_created »: « 2020-03-23 16:45:27 »,
                        « date_modified »: « 2020-03-23 16:45:27 »,
                        « created_by »: « 1 »,
                        « modified_by »: « 1 »,
                        « status »: « Send »,
                        « source_id »: « 4×63 »,
                        « target_id »: null,
                        « source_date_modified »: « 2020-03-09 12:14:36 »,
                        « mode »: « 0 »,
                        « type »: « D »,
                        « attempt »: « 0 »,
                        « global_status »: « Close »,
                        « parent_id »: «  »,
                        « deleted »: « 0 »
                }
                ]
        }
        }
```

### Function statistics

Use the statistics function to get the statistics from Myddleware.

Here are [CURL info]() :

```
        curl --location --request POST 'http://localhost/myddleware/web/api/v1_0/statistics'
        No input parameter.
```

**Output :**

```php
        {
        « errorByRule »: [
                {
                « name »: « Product category »,
                « id »: « 5e5d3f8f570cb »,
                « cpt »: « 37 »
                },
                {
                « name »: « Enrolment »,
                « id »: « 5a7dfcfaea8ee »,
                « cpt »: « 9 »
                },
                {
                « name »: « Activity completion source »,
                « id »: « 5c7892bd02e90 »,
                « cpt »: « 6 »
                },
                {
                « name »: « Product Moodle to PS »,
                « id »: « 5e5cc8984ba84 »,
                « cpt »: « 3 »
                },
                {
                « name »: « Order datail »,
                « id »: « 5d63b4532292b »,
                « cpt »: « 2 »
                },
                {
                « name »: « employee »,
                « id »: « 5e611b50c0a6f »,
                « cpt »: « 1 »
                },
                {
                « name »: « Emails »,
                « id »: « 5ba1ba8c7c82f »,
                « cpt »: « 1 »
                },
                {
                « name »: « Customers »,
                « id »: « 5d63d65dba522 »,
                « cpt »: « 1 »
                },
                {
                « name »: « Orders »,
                « id »: « 5d6010d1164fe »,
                « cpt »: « 1 »
                },
                {
                « name »: « Shipping address »,
                « id »: « 5d63d4279a52a »,
                « cpt »: « 1 »
                },
                {
                « name »: « Billing address »,
                « id »: « 5d63d54bc8310 »,
                « cpt »: « 1 »
                },
                {
                « name »: « Product Moodle get Stock id »,
                « id »: « 5e71ec0cd4a41 »,
                « cpt »: « 1 »
                }
        ],
        « countTypeDoc »: [
                {
                « nb »: « 1074 »,
                « global_status »: « Cancel »
                },
                {
                « nb »: « 1056 »,
                « global_status »: « Close »
                },
                {
                « nb »: « 44 »,
                « global_status »: « Open »
                },
                {
                « nb »: « 22 »,
                « global_status »: « Error »
                }
        ],
        « listJobDetail »: [
                {
                « id »: « 5e78e7a7621400.01014726 »,
                « begin »: « 2020-03-23 16:45:27 »,
                « end »: « 2020-03-23 16:45:27 »,
                « status »: « End »,
                « message »: «  »,
                « duration »: « 0 »
                },
                {
                « id »: « 5e78e7981dcdf7.88565484 »,
                « begin »: « 2020-03-23 16:45:12 »,
                « end »: « 2020-03-23 16:45:12 »,
                « status »: « End »,
                « message »: «  »,
                « duration »: « 0 »
                },
                {
                « id »: « 5e78e791ed9662.98391952 »,
                « begin »: « 2020-03-23 16:45:05 »,
                « end »: « 2020-03-23 16:45:06 »,
                « status »: « End »,
                « message »: «  »,
                « duration »: « 1 »
                },
                {
                « id »: « 5e78e78d169314.78840502 »,
                « begin »: « 2020-03-23 16:45:01 »,
                « end »: « 2020-03-23 16:45:01 »,
                « status »: « End »,
                « message »: «  »,
                « duration »: « 0 »
                },
                {
                « id »: « 5e78e786166de0.64116198 »,
                « begin »: « 2020-03-23 16:44:54 »,
                « end »: « 2020-03-23 16:44:54 »,
                « status »: « End »,
                « message »: «  »,
                « duration »: « 0 »
                }
        ],
        « countTransferHisto »: {
                « 2020-03-17 »: {
                « date »: « Mar-17 »,
                « open »: 0,
                « error »: 0,
                « cancel »: 0,
                « close »: 0
                },
                « 2020-03-18 »: {
                « date »: « Mar-18 »,
                « open »: 0,
                « error »: « 1 »,
                « cancel »: « 16 »,
                « close »: « 28 »
                },
                « 2020-03-19 »: {
                « date »: « Mar-19 »,
                « open »: 0,
                « error »: 0,
                « cancel »: « 1 »,
                « close »: « 1 »
                },
                « 2020-03-20 »: {
                « date »: « Mar-20 »,
                « open »: 0,
                « error »: 0,
                « cancel »: « 1 »,
                « close »: « 1 »
                },
                « 2020-03-21 »: {
                « date »: « Mar-21 »,
                « open »: 0,
                « error »: 0,
                « cancel »: 0,
                « close »: 0
                },
                « 2020-03-22 »: {
                « date »: « Mar-22 »,
                « open »: 0,
                « error »: 0,
                « cancel »: 0,
                « close »: 0
                },
                « 2020-03-23 »: {
                « date »: « Mar-23 »,
                « open »: 0,
                « error »: « 10 »,
                « cancel »: « 6 »,
                « close »: 0
                }
        }
        }
 ```
