
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

### /api/read_record

### /api/delete_record

### /api/mass_action

### /api/rerun_error

### /api/statistics


