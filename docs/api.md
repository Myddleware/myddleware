
# Myddleware API

The Myddleware API was built using [API Platform](https://api-platform.com/). It therefore comes with a built-in Swagger UI documentation describing all available endpoints.
We strongly recommend using that Swagger UI doc, which is available at the ````/api/docs```` endpoint of your Myddleware instance.

## Route

The Myddleware API is available at the /api endpoint. 
For instance, if you're running Myddleware on localhost, you can access the API via
```http://localhost/api```.

## Authentication

The Myddleware API is protected via JWT authentication, which means that in order to execute a Myddleware API action,
you will first need to authenticate using your Myddleware credentials by sending a POST request to the ````/api/login_check```` endpoint.

## Actions

Currently, all Myddleware API endpoints are POST request endpoints.

### /api/synchro

You can execute a rule or all your active rules. Simply send the rule ID.

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

POST http://localhost:8000/api/read_record

````json
{
  "rule": "6331a6247f2140.75163260",
  "filterQuery": "",
  "filterValues" : ""
}
````

### /api/delete_record

### /api/mass_action

### /api/rerun_error

### /api/statistics


