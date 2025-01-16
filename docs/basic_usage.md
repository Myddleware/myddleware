# Basic usage

## Connectors

In order to be able to synchronise data from your applications, Myddleware relies on *connectors*.
A connector is the object which allows Myddleware to connect to your solution (SuiteCRM, WooCommerce or Prestahop for example). 
Thanks to connectors, Myddleware can read and/or write data into the connected solution. Therefore, each solution you would like Myddleware
to interact with, whether it be as a source or target application, will need to have its own Myddleware connector.

> For the same solution, you can have multiple connectors. For example, let's imagine you own 3 WooCommerce websites, you can have a connector for each of these. 
> You can even have multiple connectors for the same application, for instance by connecting to the app using various credentials.

### Create a connector

To create a connector, navigate to the **Connectors** tab and select *Creation*

![Create connector button](images/basic_usage/connector/create_connector_button.png)

Now, first select the solution you would like to connect. In the example below, we've selected SuiteCRM. Each solution uses their own custom credentials.
A form will therefore be displayed which you will need to fill in using the credentials provided by your solution.
To ensure your credentials are accurate, you can click on the **Test** button and Myddleware will let you know right away whether the connection was successful or not.

![Connector connection failed](images/basic_usage/connector/create_connector_failure.png)

![Connector connection succeeded](images/basic_usage/connector/create_connector_success.png)

If the credentials are right, you may now save your connector. You will then be redirected towards the list of all your connectors.


### View my connectors

To access the list of all your connectors, simply go to the **Connectors** tab and select *List*.

![Create connector button](images/basic_usage/connector/connectors_list.png)

From here, you will be able to either update or delete a connector if needed by clicking on the icons in the **options** section.

### Edit or delete a connector

If your solution credentials have changed, you can update a connector, test whether the credentials are still valid and then save your updated connector. 

![Connector connection failed](images/basic_usage/connector/edit_connector.png)

## Rules

### Create a rule

> Rules are at the core of how Myddleware works. You can create as many as you want and as many as you need, but each rule will require a **source** connector & a **target** connector.

A rule is basically a job which sends your data from a module to another module. It’s just like transfering something from a box to another, with all the changes it implicates if the first box is square and the second is a circle. The transfer is a copy, so no data can be erased.

To create a rule, log in to your Myddleware instance then click on **Rules** then **Creation** in the navbar.

![Create rule](images/basic_usage/rule/rule1.PNG)

#### Select connectors 

First, you need to choose your environment's source and target connectors (see 1), then you will select the two modules you need to sync (see 2 & 3).

![Create rule](images/basic_usage/rule/rule2.png)

Do not forget to give a name to your rule, otherwise you won’t be able to select your modules. In the example above, we've chosen to create a rule which will have Prestashop as a **source** from which to read data, which will then be sent to SuiteCRM as a **target**.

#### Select modules

In our example, we selected  ```the e-shop s customers``` as a source module, meaning that Myddleware will read data from there, transform it and then send it to SuiteCRM's ```Accounts```  target module.  

!> It is important to know exactly from which module the data you need comes from, and in which module you want it to be copied. Indeed, you won't be able to change this part later.

#### Map some fields

Once you've named & decided on the modules you want to synchronise, you will be redirected to the fields mapping step. This is where you will define the general pattern for each data transfer made by your rule, field by field.

To map your fields, you just have to drag and drop the source field to the target field. For instance, here we've selected the ```email``` source field from the ```Customers``` module of our PrestaShop application and we've place it in the ```email1``` target field from the ```Accounts``` module of our SuiteCRM application. You can map as many fields as you need and can even send multiple sources into one target.

> NB: please note you don't need to map *all* source/target fields, you can simply select a few if that's what you need. However, some of them will be required, depending on the target application. Required fields will be marked with a star symbol next to their name in Myddleware.

![Create rule](images/basic_usage/rule/rule3.png)

#### Apply formulae to transform data before it is sent to the target app

Sometimes, the source data mapping doesn't quite match the target app's own mapping. But don't worry! 
Myddleware allows you to operate transformations on the data you want to send in order to fit with the target requirements.
This is possible thanks to Myddleware's *formulas* system.
Indeed, for each target field, you can create a formula to modify the source data to fit the type, length, format... and other requirements from the target field.

###### Simple string concatenation formula example

In our example, we want to map PrestaShop customers' data to be sent to SuiteCRM's ```Accounts``` module. 
However, PrestaShop only maps customers' ```first_name``` and ```last_name``` whereas SuiteCRM provides us with a ```name``` field. 
Ideally, we would want to add up our source's  ```first_name``` and ```last_name``` in order to fit SuiteCRM's name field's logic. 
To do so, we need to drag and drop ```first_name``` and ```last_name```, and create a formula to concatenate them.
Then, click on the **Create a formula** button.

![Create a formula by dragging 2 source fields inside a target field](images/basic_usage/rule/rule_required_field.png)

You can double-click on the data in the “Fields” section to make it appear in the formula area at the top. 
The code you will use for this formula is PHP. Therefore, in order to concatenate 2 fields, we will need to use ```." ".``` between our 2 variables. 
For example, if you input the following formula: 

````php
    {firstname}." ".{lastname}
````

You would get the following result inside the target application :

````text
    John Doe
````

![Formula modal - simple concatenation example](images/basic_usage/rule/rule5.PNG)

###### Pre-formatting data using the target application

Formulas also allow you to add pre-formatted data inside your target application which does not necessarily come from your source app. 
For instance, when sending customers from Prestashop to SuiteCRM's ```Account``` module,
you might want to tell Myddleware that all the accounts will have a ```Customer``` type by default in the ```account_type``` field in the target application (SuiteCRM).
To do so, you will need to click on the target field's **Create a formula** button (here, we will use ```account_type```).
The formula modal will open. From there, click on the **Target**  dropdown list at the bottom and select the default value you want to use.
In our case, we will select ***Customer**.

![Formula target dropdown list](images/basic_usage/rule/rule_formula_target_type.png)

Then, you need to click on the small arrow next to the dropdown list in order to 'push' this value into the top right field. Don't forget to click on 
the **confirm** button.

![Formula - click on arrow to push value at the top](images/basic_usage/rule/formula_arrow_confirm.png)

Now, you should be able to see the value you've selected in your formula directly inside the target field's box.

![Fields mapping - "Customer" formula has been saved](images/basic_usage/rule/mapped_fields_formula.png)


#### Simulate a data transfer 

>To test your formula and your fields' mapping, you can go to the  ```simulation``` tab

![Rule data transfer simulation tab](images/basic_usage/rule/rule6.png)

Then you can run a **Simple simulation** to visualize a sample data transfer. 
You can also check whether your formulae actually transform the data the way you intend them to.

![Rule - simple simulation](images/basic_usage/rule/rule_simulation.png)

If you would like to verify your data using a specific record from your source application, you can run a **Manual simulation** to visualize
the transformations on that specific record. To do so, you will therefore need to get that document's ID from inside your source application.
For example, if I want to check my data using the PrestaShop customer whose ID is 2, 
I will input ```2``` into the field at the top, and then run the **Manual simulation**
button, which will get me the following result:

![Rule - manual simulation](images/basic_usage/rule/rule_manual_simulation.png)

If you are satisfied with the current simulation, you can either directly save your rule by clicking the ````confirm```` button on the ```Confirmation``` tab,
or you can make even further transformations to your data by adding ```Filters``` & ```Relationships``` to your rule. To find out more about these options,
please go to the  [Advanced usage section](advanced_usage.md?id=relationships) of this documentation. 


#### Confirm & create the rule

##### Avoid duplicate fields

Once all the fields are mapped, you may be prompted to select which field (from the target application) to use to prevent sending duplicate data. 
For instance, in the example we've used so far, we want to avoid duplicating customers' accounts. 
We therefore need to tell Myddleware which field needs to be unique. 
In our case, we will use the ```email1``` field. 
This way, Myddleware will only update the affected records instead of creating a duplicate. 

![Rule - confirm creation](images/basic_usage/rule/rule_confirm_duplicate_email.png)

!> In order for this feature to work, the field used to avoid duplicate records must be mapped. If needed, go back to the fields mapping section and add it.

##### Synchronisation type

Depending on the solution, you could have the choice to read newly created data or all data, created or modified in the source module.
In our example, if you only want to send new customers created in Prestashop to SuiteCRM, then select ```Create data only```.
Otherwise, if you only want to send customers’ modifications in Prestashop to SuiteCRM, then select ```Create and update data```.
In our example we selected ```Create and update data```. This process is based on the reference date that you can set up.

![Rule - synchronisation type options](images/basic_usage/rule/rule_sync_type.png)

You may now click on ```Confirm``` to create and save the rule.

### Rule details

You can view a summary of each of your rules by selecting it from the list.

> NB: if your user has super admin privileges, you will be able to see a variety of actions above your rule, such as the ability to delete the previous documents associated with this rule. 
> Otherwise, fewer actions will be available to you.
> If you need to access these actions, you can ask your administrator to upgrade your Myddleware privileges.


| Action            | Description                                                                                                                                                               | Permissions |
|-------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-------------|
| Run the rule      | Run the rule manually, even if the **Enabling the automatic synchronisation** switch is off                                                                               | user        |
| Edit the rule     | Edit this rule. You can change the fields that being mapped, filters, relationships and other parameters.<br/> However, you may not change the connectors & modules used. | user        |
| Display documents | View all data transfers (also called documents) generated by the rule so far                                                                                              | user        |
| Duplicate rule    | Generate a perfect copy of this rule.                                                                                                                                     | user        |
| Delete the rule   | Delete this rule                                                                                                                                                          | user        |
| Delete documents  | Delete all previous Myddleware documents (data transfers) related to this rule.                                                                                           | admin       |
| Cancel documents  | Cancel all Myddleware documents (data transfers) related to this rule which have not been sent yet. These are documents in error or still pending.                        | admin       |


![Rule - details view as a super admin](images/basic_usage/rule/rule_detail_super_admin.png)

You can see an **On/Off** switch which allows you to enable automatic synchronisation. 
If this option is on, the job which synchronises data as a background task will run this rule. 
If it is off, the background job will not run this rule. 
Checkout the [Job scheduler & cron tasks section](cron_jobs.md) for more details about background jobs and how to turn them on. 

### Fields mapping summary

If you go to the **Fields mapping** tab, you can see a summary of the fields that are mapped for this rule.

![Rule - fields mapping summary](images/basic_usage/rule/rule_mapping_summary.png)

### Rule Parameters

From the rule's detail view, you can access the **parameters** tab. 

![Rule parameters view](images/basic_usage/rule/rule_params_default.png)

| Parameter      | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             | 
|----------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Reference date | The date Myddleware uses to start reading data from the source application. <br/>For example, if you set today’s date, only today’s created or modified data will be sent to your target application. <br/>(This also depends on the mode of your rule : the “Create and update data” rule mode concerns modified data while the “Create data only” rule mode concerns created data). If you want to migrate your data, all you need to do is put this date in the past. <br/>Then all created/modified data after this date will be read by Myddleware. | 
| Data deletion  | You can ask the system to delete the transferred data of your rule after x days. To be able to do so, you must first set the ```clear data``` background job via the [job scheduler](cron_jobs.md).<br/> This job will delete all previously-transferred documents for this rule, except for their IDs, which Myddleware needs.                                                                                                                                                                                                                         |
| Limit          | The maximum number of records read in one go by Myddleware (pagination system).                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |

![Rule parameters view -  params panel - reference date - Datetime picker ](images/basic_usage/rule/rule_params_datetime_picker.png)

The estimated documents panel allows you to simulate the number of records which will be read if you ran this rule.
You can change the reference date if you need to then click on **Save**. 

!> For a rule to be able to run, you absolutely MUST click on this save button. Otherwise, Myddleware will execute the rule using the current timestamp, so no documents will be read.


![Rule parameters view - estimated documents simulation panel ](images/basic_usage/rule/rule_params_simulation_number.png)

### Run a rule

To manually run a rule, go to its details view and click  **Run the rule**.

![Rule details view - run the rule button ](images/basic_usage/rule/rule_execute.png)

Once the rule has been launched, you may access the running task via the link displayed above. 
You will be redirected to a summary of the task that Myddleware is running.

#### Task details

![Currently running task summary](images/basic_usage/rule/task_running.png)

You can also view the list of all the previously ran tasks.

![Tasks list](images/basic_usage/rule/tasks_list.png)

#### Documents

Documents (data transfers) can be opened by clicking on **Display documents**.

![Documents list](images/basic_usage/rule/rule_documents.png)

You can also access the detail for each individual document by clicking on it.

![Single document details](images/basic_usage/rule/document_detail.png)

You will then be able to see what Myddleware has read in the source application and what has been sent into the target application.

##### Handling documents in error

When a document (data transfer) is in error, you can modify it, by resending it or cancelling it. 
To be able to modify the document data, double-click on target data, change the data and click on the validation icon.

![Single documents view: double-click on target data transfer field to tweak it & then click on reload or cancel the transfer](images/basic_usage/document/document_tweak_data_transfer_error.png)

##### Mass actions

You can reload or cancel multiple documents at the same time.

> Only documents in error can be reloaded or cancelled.

Click on the checkboxes to select the transfer(s) you want to reload or cancel and click on ``Cancel transfers`` or ``Reload transfers``.

![Select the documents in error then click on cancel documents or reload documents](images/basic_usage/document/documents_list_cancel_rerun_documents_in_error_buttons.png)

## Going further

If you would like to find out about more complex use cases, such as adding relationships between rules, filters and other amazing features, please checkout the [Advanced Usage section](advanced_usage.md)
