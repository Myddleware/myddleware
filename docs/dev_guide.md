# Developer guide

## Connector creation

### Requirement

It is important that the application you want to connect has a webservice API with functions to read data. This will make it easier to use foor source application and create/update data and also for target application.

First you will need to prepare Myddleware :

In the folder "myddleware/src/DataFixtures/LoadSoltionData.php"

We use doctrine to update the database, so you have modify the file "LoadSolutionData.php"

- Add a new row to the LoadSolutionData class for ...

![In LoadSolutionData](images/doc.PNG)

In the Folder Manager "myddleware/src/Manager/SolutionManager.php"

- Add ... to the SolutionManager file, in the same way as the others. This gives us, first of all, "use App\Soltions\..." 

Then still in SoltionManager, we add "..." to the construct function, following the others.

IMAGE

Add to Database : 

- In your terminal load Myddleware fixtures:

        php bin/console doctrine:fixtures:load-append

- In your terminal use the command corresponding to your API:
       composer require ...

!> Check in myddleware if the new connector is accessible

Now we need to create a new connector class, in "myddleware/src/Solutions", the file name should be the name service. The code should be this code, but change the name of the application:

IMAGE

Finally, if youu want to display the application logo add the image corresponding to your appliaction with the png format and size 64*64 pixels in the directory "myddleware/public/build/images/solution"

IMAGE

### Method getFieldLogin

In this new connector class, create the function getFieldLogin(). Here, you have to put the parameters required to connect to your solution.

For example, if you need an url and an APIkey you can creat this methode : 

IMAGE

!> Check that everything is working in Myddleware

IMAGE

We can now log into Myddleware.

### Method login 

Now to connect your connector, we need to create a new function in your class, we will call it "login".
You have to add this function login to check the connexion with you application.


