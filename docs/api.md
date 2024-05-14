# Myddleware REST API

The Myddleware API was built using [API Platform](https://api-platform.com/). 
It therefore comes with a built-in Swagger UI documentation describing all available endpoints.
We strongly recommend using that Swagger UI doc, which is available at the ````/api/docs```` endpoint of your Myddleware instance.
You can find our [Postman collection & documentation here](https://documenter.getpostman.com/view/1328767/SzS7QmCj?version=latest#e564597d-ef6e-40e1-87f1-c69b7b2d7479)
You can also find a PHP example of Myddleware API implementation [here](https://github.com/Myddleware/myddleware_api). 

## Route

The Myddleware API is available at the /api endpoint. 
For instance, if you're running Myddleware on localhost, you can access the API via
```http://localhost/api```.

## Authentication

The Myddleware API is protected via JWT authentication, which means that in order to execute a Myddleware API action,
you will first need to authenticate using your Myddleware credentials by sending a POST request to the ````/api/login_check```` endpoint.

### Generating RSA Keys

To ensure secure communication with the Myddleware API, you may need to generate RSA key pairs. You can use OpenSSL . Here's how you can generate a pair of RSA keys:

Before generating the keys, make sure to create the directory `/config/jwt/` where you want to store the keys.

```bash
openssl genrsa -out private.pem 2048
```
```bash
openssl rsa -in private.pem -outform PEM -pubout -out public.pem
```
The first command generates a private RSA key stores it in a file named private.pem. The second command extracts the corresponding public key from the private key and stores it in a file named public.pem.

### /api/login_check

> POST http://localhost:8000/api/login_check

Example authentication request :

````json
{
  "username" : "sophie.example@email.com",
  "password" : "myverysecretivepassword123"
}
````

Example successful response : 

````json
{
  "token": "eyJ1c2VybmFtZSI6InNvcGhpZS5leGFtcGxlQGVtYWlsLmNvbSIsInBhc3N3b3JkIjoibXl2ZXJ5c2VjcmV0aXZlcGFzc3dvcmQxMjMiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.P0L7H4g9nZoHmVQRQHNi4G5Pfq6CS0JM9_xv-98OJ5Q"
}
````

Upon success, you can now paste this token into all your other API requests into the Bearer Token header of each request.

## Actions

Currently, all Myddleware API endpoints are POST request endpoints.

### /api/synchro

You can execute a rule or all your active rules. Simply send the rule ID.

> POST http://localhost:8000/api/synchro

Example body:

````json
{
    "rule": "631848032ab82"
}
````

Example response:

```json
{
    "error": "",
    "jobId": "6331b6f35a1ff4.39548442",
    "jobData": {
        "Close": 0,
        "Cancel": 0,
        "Open": 0,
        "Error": 0,
        "paramJob": "Synchro : 631848032ab82",
        "solutions": "",
        "duration": 0.64,
        "myddlewareId": "6331b6f35a1ff4.39548442",
        "Manual": 1,
        "Api": 1,
        "jobError": "",
        "documents": []
    }
}
```

#### Execute all active rules

If  you want to synchronise all your active rules at once, you need to send 'ALL' instead of a rule ID.

````json
{
    "rule": "ALL"
}
````


### /api/read_record

Use the read record method to force Myddleware to read a specific record from your application. For example, 
you can call this method when a record is saved into your application. 
Myddleware will read it from your source application and send it to your target application.
It is used when you need real time synchronisation.

> POST http://localhost:8000/api/read_record

You need to send the following parameters in your request body :

````json
{
  "rule": "6331a6247f2140.75163260",
  "filterQuery": "<field>",
  "filterValues" : "<value>"
}
````

| Parameter   | Description                                                                                                                           | 
|-------------|---------------------------------------------------------------------------------------------------------------------------------------|
| rule        | The rule you want to run.                                                                                                             |
| filterQuery | The field used to build the query executed by Myddleware inside your application. It is usually the "id" field‘s name of your record. |
| filterValue | The field value used as a filterQuery parameter. It is usually your record's id.                                                      |

Example response:

````json
{
    "error": "",
    "jobId": "5e78c4c4ec8631.31640728",
    "jobData": {
        "Close": 0,
        "Cancel": "1",
        "Open": 0,
        "Error": 0,
        "paramJob": "read records wilth filter id IN (4×60)",
        "solutions": "^6^,^27^",
        "duration": 2.42,
        "myddlewareId": "5e78c4c4ec8631.31640728",
        "Manual": 1,
        "Api": 1,
        "jobError": "",
        "documents": [
            {
                "id": "5e78c4c5f27231.38354025",
                "rule_id": "5e5e5535564c0",
                "date_created": "2020-03-23 14:16:37",
                "date_modified": "2020-03-23 14:16:39",
                "created_by": "1",
                "modified_by": "1",
                "status": "No_send",
                "source_id": "4×60",
                "target_id": "e559cfe4-4e41-e2da-235e-5e63a985d98e",
                "source_date_modified": "2017-10-09 14:46:24",
                "mode": "0",
                "type": "U",
                "attempt": "0",
                "global_status": "Cancel",
                "parent_id": "",
                "deleted": "0"
            }
        ]
    }
}
````

### /api/delete_record

Use the delete_record method to delete a specific record inside the target application using the source application id.

> POST http://localhost:8000/api/delete_record


| Parameter     | Description / value                                                                                                                    |
|---------------|----------------------------------------------------------------------------------------------------------------------------------------|
| **rule**      | Rule ID.                                                                                                                               |
| **recordId**  | Record ID inside the source application. Myddleware will use the rule to get this record's id in the target application and delete it. |
| **reference** | The reference date or id used in Myddleware. Use the reference field already configured for this rule.                                 |
|               | All of the rule's fields have to be added as input parameters.                                                                         |

Example CURL request :

````curl
curl --location --request POST 'http://localhost:8000/api/delete_record' \
--form 'rule=5e5e5535564c0' \
--form 'recordId=4x65' \
--form 'reference=2020-03-09 12:14:36' \
--form 'lastname=lastname01' \
--form 'email=test@test.test' \
--form 'firstname=firstname01'
````

Example response:

````json
    {
        "error": "",
        "jobId": "5e78e7a7621400.01014726",
        "jobData": {
            "Close": 1,
            "Cancel": 0,
            "Open": 0,
            "Error": 0,
            "paramJob": "Delete record 4×63 in rule 5e5e5535564c0",
            "solutions": "^6^,^27^",
            "duration": 0.32,
            "myddlewareId": "5e78e7a7621400.01014726",
            "Manual": 1,
            "Api": 1,
            "jobError": "",
            "documents": [
                {
                    "id": "5e78e7a766e656.99183539",
                    "rule_id": "5e5e5535564c0",
                    "date_created": "2020-03-23 16:45:27",
                    "date_modified": "2020-03-23 16:45:27",
                    "created_by": "1",
                    "modified_by": "1",
                    "status": "Send",
                    "source_id": "4×63",
                    "target_id": null,
                    "source_date_modified": "2020-03-09 12:14:36",
                    "mode": "0",
                    "type": "D",
                    "attempt": "0",
                    "global_status": "Close",
                    "parent_id": "",
                    "deleted": "0"
                }
            ]
        }
    }
````

### /api/mass_action

Use the mass action method to change (rerun, cancel, remove, restore or change the status of) a group of data transfers (documents).

> POST http://localhost:8000/api/mass_action

 | Parameter                 | Description / value                                                                                                                                                                                                                                                                                                                                                                 |
|---------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **action**                | ``rerun``, ``cancel``, ``remove``, ``restore`` or ``changeStatus``                                                                                                                                                                                                                                                                                                                  |
| **dataType**              | ``rule`` or ``document`` <br/>If you want to select documents using a rule (all of the rule's data transfers) as the filter set it to ``rule``, otherwise set it to ``document`` if you want to filter your search by data transfer (document) id.                                                                                                                                  |
| **ids**                   | Set the id(s) of the data transfer (document) or the rule depending on what you have set as a **dataType** parameter. If you put several ids, use commas to separate them.                                                                                                                                                                                                          |
| **forceAll**   (OPTIONAL) | Set to ``Y`` to process action on all data transfers (not only open and error ones). <br/>In this case, be careful, you could remove, cancel or change the status of data successfully sent to your target application. <br/> Myddleware could generate duplicate data in your target application if you run your rule again without setting up the ``duplicate fields`` parameter. |
| **fromStatus**            | Only used when the **action** parameter is set to  ``changeStatus``. Adds a filter to select documents based on their status.                                                                                                                                                                                                                                                       |
| **toStatus**              | Only used when the **action** parameter is set to  ``changeStatus``. New status to be set on all selected documents.                                                                                                                                                                                                                                                                |

Example response: 

````json
    {
        "error": "",
        "jobId": "5e78dcacb8f1d0.97025863",
        "jobData": {
            "Close": "30",
            "Cancel": "70",
            "Open": 0,
            "Error": 0,
            "paramJob": "Mass remove on data type rule",
            "solutions": "^6^,^27^",
            "duration": 10.28,
            "myddlewareId": "5e78dcacb8f1d0.97025863",
            "Manual": 1,
            "Api": 1,
            "jobError": "",
            "documents": [
                {
                    "id": "5e6685af04d858.22701783",
                    "rule_id": "5e5e5535564c0",
                    "date_created": "2020-03-09 18:06:39",
                    "date_modified": "2020-03-23 15:58:36",
                    "created_by": "1",
                    "modified_by": "1",
                    "status": "Cancel",
                    "source_id": "4×72",
                    "target_id": null,
                    "source_date_modified": "2020-03-09 19:06:39",
                    "mode": "0",
                    "type": "D",
                    "attempt": "0",
                    "global_status": "Cancel",
                    "parent_id": "",
                    "deleted": "1"
                },
                {
                    "id": "5e6685fd2cfbb8.35868016",
                    "rule_id": "5e5e5535564c0",
                    "date_created": "2020-03-09 18:07:57",
                    "date_modified": "2020-03-23 15:58:36",
                    "created_by": "1",
                    "modified_by": "1",
                    "status": "Cancel",
                    "source_id": "4×72",
                    "target_id": null,
                    "source_date_modified": "2020-03-09 19:07:57",
                    "mode": "0",
                    "type": "D",
                    "attempt": "0",
                    "global_status": "Cancel",
                    "parent_id": "",
                    "deleted": "1"
                },
                {
                    "id": "5e668618964e93.34804135",
                    "rule_id": "5e5e5535564c0",
                    "date_created": "2020-03-09 18:08:24",
                    "date_modified": "2020-03-23 15:58:36",
                    "created_by": "1",
                    "modified_by": "1",
                    "status": "Cancel",
                    "source_id": "4×72",
                    "target_id": null,
                    "source_date_modified": "2020-03-09 19:08:24",
                    "mode": "0",
                    "type": "D",
                    "attempt": "0",
                    "global_status": "Cancel",
                    "parent_id": "",
                    "deleted": "1"
                },
                {
                    "id": "5e669dcd915191.00190986",
                    "rule_id": "5e5e5535564c0",
                    "date_created": "2020-03-09 19:49:33",
                    "date_modified": "2020-03-23 15:58:37",
                    "created_by": "1",
                    "modified_by": "1",
                    "status": "Cancel",
                    "source_id": "4×72",
                    "target_id": null,
                    "source_date_modified": "2020-03-09 20:49:33",
                    "mode": "0",
                    "type": "U",
                    "attempt": "1",
                    "global_status": "Close",
                    "parent_id": "",
                    "deleted": "1"
                }
            ]
        }
    }
````

### /api/rerun_error

Use the rerun_error method to execute documents in error again.

> POST http://localhost:8000/api/rerun_error>

| Parameter               | Description / value                                                                              |
|-------------------------|--------------------------------------------------------------------------------------------------|
| **limit**               | limit the number of documents selected by the job.                                               |
| **attempt**             | Myddleware will only read documents with a number of attempts less than or equal to this number. |

Example response:

````json
    {
        "error": "",
        "jobId": "5e78e6cdd789e7.70152075",
        "jobData": {
            "Close": 0,
            "Cancel": 0,
            "Open": 0,
            "Error": "3",
            "paramJob": "Rerun error : limit 3, attempt 5",
            "solutions": "^14^,^6^,^3^",
            "duration": 1.09,
            "myddlewareId": "5e78e6cdd789e7.70152075",
            "Manual": 1,
            "Api": 1,
            "jobError": "",
            "documents": [
                {
                    "id": "5e612055c98da7.22680820",
                    "rule_id": "5e611b50c0a6f",
                    "date_created": "2020-03-05 15:52:53",
                    "date_modified": "2020-03-23 16:41:50",
                    "created_by": "1",
                    "modified_by": "1",
                    "status": "Error_sending",
                    "source_id": "efa139c1-5e46-b247-739d-5ba8412aa24a",
                    "target_id": null,
                    "source_date_modified": "2018-09-24 01:43:35",
                    "mode": "0",
                    "type": "C",
                    "attempt": "6",
                    "global_status": "Error",
                    "parent_id": "",
                    "deleted": "0"
                },
                {
                    "id": "5e72552e4fe687.45181957",
                    "rule_id": "5e5cc8984ba84",
                    "date_created": "2020-03-18 17:06:54",
                    "date_modified": "2020-03-23 16:41:50",
                    "created_by": "1",
                    "modified_by": "1",
                    "status": "Error_sending",
                    "source_id": "8",
                    "target_id": null,
                    "source_date_modified": "2019-02-06 22:07:59",
                    "mode": "0",
                    "type": "C",
                    "attempt": "6",
                    "global_status": "Error",
                    "parent_id": "",
                    "deleted": "0"
                },
                {
                    "id": "5e72552e51d612.98166090",
                    "rule_id": "5e5cc8984ba84",
                    "date_created": "2020-03-18 17:06:54",
                    "date_modified": "2020-03-23 16:41:50",
                    "created_by": "1",
                    "modified_by": "1",
                    "status": "Error_sending",
                    "source_id": "12",
                    "target_id": "15",
                    "source_date_modified": "2020-03-03 10:43:31",
                    "mode": "0",
                    "type": "U",
                    "attempt": "6",
                    "global_status": "Error",
                    "parent_id": "",
                    "deleted": "0"
                }
            ]
        }
    }
````

### /api/statistics

Get Myddleware statistics.

> POST http://localhost:8000/api/statistics

Example response:

````json
    {
        "errorByRule": [
            {
                "name": "Product category",
                "id": "5e5d3f8f570cb",
                "cpt": "37"
            },
            {
                "name": "Enrolment",
                "id": "5a7dfcfaea8ee",
                "cpt": "9"
            },
            {
                "name": "Activity completion source",
                "id": "5c7892bd02e90",
                "cpt": "6"
            },
            {
                "name": "Product Moodle to PS",
                "id": "5e5cc8984ba84",
                "cpt": "3"
            },
            {
                "name": "Order datail",
                "id": "5d63b4532292b",
                "cpt": "2"
            },
            {
                "name": "employee",
                "id": "5e611b50c0a6f",
                "cpt": "1"
            },
            {
                "name": "Emails",
                "id": "5ba1ba8c7c82f",
                "cpt": "1"
            },
            {
                "name": "Customers",
                "id": "5d63d65dba522",
                "cpt": "1"
            },
            {
                "name": "Orders",
                "id": "5d6010d1164fe",
                "cpt": "1"
            },
            {
                "name": "Shipping address",
                "id": "5d63d4279a52a",
                "cpt": "1"
            },
            {
                "name": "Billing address",
                "id": "5d63d54bc8310",
                "cpt": "1"
            },
            {
                "name": "Product Moodle get Stock id",
                "id": "5e71ec0cd4a41",
                "cpt": "1"
            }
        ],
        "countTypeDoc": [
            {
                "nb": "1074",
                "global_status": "Cancel"
            },
            {
                "nb": "1056",
                "global_status": "Close"
            },
            {
                "nb": "44",
                "global_status": "Open"
            },
            {
                "nb": "22",
                "global_status": "Error"
            }
        ],
        "listJobDetail": [
            {
                "id": "5e78e7a7621400.01014726",
                "begin": "2020-03-23 16:45:27",
                "end": "2020-03-23 16:45:27",
                "status": "End",
                "message": "",
                "duration": "0"
            },
            {
                "id": "5e78e7981dcdf7.88565484",
                "begin": "2020-03-23 16:45:12",
                "end": "2020-03-23 16:45:12",
                "status": "End",
                "message": "",
                "duration": "0"
            },
            {
                "id": "5e78e791ed9662.98391952",
                "begin": "2020-03-23 16:45:05",
                "end": "2020-03-23 16:45:06",
                "status": "End",
                "message": "",
                "duration": "1"
            },
            {
                "id": "5e78e78d169314.78840502",
                "begin": "2020-03-23 16:45:01",
                "end": "2020-03-23 16:45:01",
                "status": "End",
                "message": "",
                "duration": "0"
            },
            {
                "id": "5e78e786166de0.64116198",
                "begin": "2020-03-23 16:44:54",
                "end": "2020-03-23 16:44:54",
                "status": "End",
                "message": "",
                "duration": "0"
            }
        ],
        "countTransferHisto": {
            "2020-03-17": {
                "date": "Mar-17",
                "open": 0,
                "error": 0,
                "cancel": 0,
                "close": 0
            },
            "2020-03-18": {
                "date": "Mar-18",
                "open": 0,
                "error": "1",
                "cancel": "16",
                "close": "28"
            },
            "2020-03-19": {
                "date": "Mar-19",
                "open": 0,
                "error": 0,
                "cancel": "1",
                "close": "1"
            },
            "2020-03-20": {
                "date": "Mar-20",
                "open": 0,
                "error": 0,
                "cancel": "1",
                "close": "1"
            },
            "2020-03-21": {
                "date": "Mar-21",
                "open": 0,
                "error": 0,
                "cancel": 0,
                "close": 0
            },
            "2020-03-22": {
                "date": "Mar-22",
                "open": 0,
                "error": 0,
                "cancel": 0,
                "close": 0
            },
            "2020-03-23": {
                "date": "Mar-23",
                "open": 0,
                "error": "10",
                "cancel": "6",
                "close": 0
            }
        }
    }
````
