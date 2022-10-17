# List of available connectors and how to link them to Myddleware

> Each Myddleware connector will require different set of credentials for Myddleware to be able to link your applications together.

## CMS apps

### WordPress

## CRM apps

### Airtable

### Cirrusshield

Myddleware is able to read and write all Cirrus Shield modules, even the custom ones.

To create your Cirrus Shield connector, you will need :

- Your username 
- Your password


### Hubspot

Here is the list of available modules in source (reading) and target (writing) :


| Module                                            | Source | Target |
|---------------------------------------------------|--------|--------|
| Companies                                         | Yes    | Yes    | 
| Contacts                                          | Yes    | Yes    | 
| Deals                                             | Yes    | Yes    | 
| Engagement  (task, email, meeting, note and call) | Yes    | No     | 

Need more features ? Please contact us.

To create your hubspot connector, you will need our api key :

> Please, follow this tutorial to get your API key : https://knowledge.hubspot.com/articles/kcs_article/integrations/how-do-i-get-my-hubspot-api-key

### SageCRM

Myddleware can read and write all Sage CRM modules, even the custom ones.

To create a SageCRM connector, you will need:

– Your user email address
– Your password
– A WSDL file that you need to download

The user and the password are those you use to access your SageCRM account.

To download the WSDL file, you must connect to your SageCRM account and click on “Administration”.

Once you’re in “Manage My Account”, click on “External Access” and then on “View Web Services WSDL”.

A new page opens. You have to save it.

Now, go to your Myddleware environment where you will create a new connector. 
After typing in your email address and password, click on “wsdl file”. Choose the file you just downloaded and then transfer it.

The file has been transferred successfully.

Test your connection. If the light bulb is green, you're good to go!

Choose a name for your connector and save it.

### Salesforce

Myddleware can read and write all Salesforce modules, even the custom ones.

To create your Salesforce connector, you will need the following credentials: 

- Your username 
- Your password 
- Your token 
- Your consumer key 
- Your consumer secret 
- Indicate if you are connecting a sandbox or not (0 or 1)

#### Where to find your security token ?

Log in to Salesforce with your username and password.
From the homepage of your account, click on your username then on “My Settings”.
Then choose “Personal”.
Finally, click on “Reset my security token” (it will be sent by email).

#### Consumer Key and Consumer Secret

You need to create an app.

From Setup, enter “Apps” in the Quick Find box, then select “Apps”. Then select “Create”, then “Apps”.

Create a connected app by clicking on “New”

After typing your App Name, API name and email address, enable OAuth Settings.

Put your Salesforce URL and choose “Full access”. Save.

You will be redirected to the presentation page of your app. There you’ll find your Consumer Key and Consumer Secret.

Fill in the connector creation form in Myddleware :

### SAP CRM

The SAP CRM connector can only be installed by our team.

To know more about this connector, please fill in [this contact form](http://www.myddleware.com/contact-us).


### SugarCRM

Myddleware is compatible with SugarCRM CE and SugarCRM PRO v6.2 and upper versions.

Myddleware can read and write all SugarCRM modules, even the custom ones.

To connect SugarCRM to Myddleware, you need your username, your password and your Url :

However there is a subtlety about your URL. You need to add the following information :  service/v4/rest.php

Using the example above, the URL should be : “your-crm.fr/sugarcrm/service/v4/rest.php”

Tip: Use the username and password of a user having the right to make the rules, write access, read, edit …

### SuiteCRM

Myddleware can read and write all SuiteCRM modules, even the custom ones.

To connect SuiteCRM to Myddleware, you need your user name, your password and your URL:

### VTiger

Myddleware can read and write all Vtiger modules, even the custom ones.

To connect Vtiger to Myddleware, you need your username, your access key and your URL:

To get your access key, open your Vtiger profil preferences :

## ERP apps

### ERPNExt

Myddleware can read and write all ERPNext modules, even the custom ones.

To connect ERPNext to Myddleware, you need :

- your username
- your password
- your URL

## E-commerce apps

### Magento

Myddleware is compatible with Magento 2.

Here is the list of available modules in source (reading) and target (writing) :

| Module                                             | Source | Target |
|----------------------------------------------------|--------|--------|
| Customers                                          | Yes    | Yes    |
| Customer Addresses                                 | Yes    | No     |
| Orders                                             | Yes    | No     |

Need more functionalities ? Please Contact us

To connect Magento to Myddleware, you need your username, your password and your URL:

Use the username and password of a user that has the rights you need to use Myddleware. Add the URL of your shop as well.

### Prestashop

Every module can be read by Myddleware as a source & target.

It will allow you to create the PrestaShop connector.

To do so, you will need the URL of your shop and an API key that you will have to generate yourself.
How do you generate this key?
To generate this key, you should go in the back office of your shop. Go to “Advanced Settings” and then “Webservice“.
Click on “enable webservice” (save if the webservice was not activated) then click on “Add new webservice key” (black arrow)
Finally, click on “generate” to generate the key and set the “Status” button to “Yes”. 
Select the check boxes “View”, “Edit”, “Add” and “Fast view” (just like in the example below).
Finally, save and get your API key to create your connector.

### WooCommerce (WordPress)

### Shop Application

Myddleware can write all Shop Application modules and read the module customer.

To connect Shop application to Myddleware, you need these parameters :

- URL of your shop application
- API key. Ask the shop application team to give it to you.


## E-marketing platforms

### Dolist


Here is the list of available modules in source (reading) and target (writing) :

| Module                    | Source | Target |
|---------------------------|--------|--------|
| Campaign                  | Yes    | No     |
| Contacts                  | Yes    | Yes    |
| Body segment statistics   | No     | Yes    |
| Header segment statistics | No     | Yes    |
| Finished statistics       | Yes     | No    |
| Clicks statistics         | Yes     | No    |
| Unsubscribed statistics   | Yes     | No    |
| Unfinished statistics     | Yes     | No    |
| Open statistics           | Yes     | No    |

– Connecting to your Dolist environment
– Creating a Dolist authentication
– Retrieving the information needed to create the connector in Myddleware.

1st step: Connect to your Dolist environment:

Go to your Dolist login portal and log in.

Once you are logged in, you will be redirected to the home page. Click on the administration tab as shown in the image below :

The administration tab is now open. You will find in the left menu a subsection named “Web Services”. Click on it.

The following tabs should appear. The one which interests us is “key management”.

You are now ready to move to the second step.

2nd step: Creating a Dolist authentication

While you are on the “manage keys” tab, click on “Add a new Authentication”

You must now fill in the necessary fields. We advise you not to choose the restriction by IP and to check the “permanent validity”. If you however wish to add Myddleware IP to your restrictions, please contact us.

Congratulations, you’ve just created your Dolist authentication for Myddleware !

3rd Step: Retrieving the necessary information

For this last step, just click on the “See Key” button or the “See the authentication key” button, if you are still on of your authentication page.

Enter your logins to display the following information:

Take note of your Account ID and your authentication key so that you can fill in the fields when creating your Dolist connector in Myddleware.

### Mailchimp

Here is the list of available modules in source (reading) and target (writing) :

| Module                           | Source | Target  |
|----------------------------------|--------|---------|
| Campaigns                        | No     | Yes     |
| Lists                            | No     | Yes     |
| List members                     | No     | Yes     |

Connect to Mailchimp an open your profile

Then click on  « Extra -> API Keys » and copy you API key :

Add this key in your Myddleware connector :

### Mautic 

Here is the list of available modules in source (reading) and target (writing) :

| Module   | Source | Target |
|----------|--------|--------|
| Company  | No     | Yes    |
| Contacts | No     | Yes    |

To connect Mautic to Myddleware, you need :

- your username
- your password
- your URL

### Sendinblue

## Database apps

### Microsoft SQL

### MySQL

## Others

### Facebook

### File (FTP)

### Moodle

### RingCentral

### Sage50

### Zuora



