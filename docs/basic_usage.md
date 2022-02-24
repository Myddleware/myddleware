# Basic usage

## Set up your jobscheduler (Jobscheduler/crontab)

### Using jobscheduler in the Myddleware interface

On your Myddleware interface you have the possibility to create your periodic tasks, click on your username on the top right and click on ```"jobscheduler"``` :  

![Jobscheduler 1](images/basic_usage/jobscheduler_1.png)

Here you will find the list of your tasks, with the possibility to modify or delete a task through the action column. 

![Jobscheduler 1](images/basic_usage/jobscheduler_2.png)

To create a new task click the New command button. You will then be redirected to the command creation page:  

Here you will first have to select the type of command you want to create, depending on your choice you will have different parameters to enter.

![Jobscheduler 1](images/basic_usage/jobscheduler_create.png)

**For the following fields **

<!-- tabs:start -->
#### **Period**

this is the time interval corresponding to the frequency of execution of your task 

#### **Job order**

This is the order in which the tasks will be executed

#### **Active**

 Active ? Allows you to deactivate/activate a scheduled task

<!-- tabs:end -->


### Using crontab in the Myddleware interface

Just like with Jobscheduler you can use to create new periodic tasks directly via Myddleware, to do this click again on your username, then click on ```"Crontab"```.

![Crontab list](images/basic_usage/crontan_list.PNG)

<!-- tabs:start -->
#### **Arguments**

#### **Number**

#### **Description**

#### **Running instance**

#### **Period **

 As for jobscheduler, period is a time interval corresponding to the frequency of execution of your task. Here on the other hand the syntax to use is precise example (*/5 * * * * : in the order of writing, minute, hours, day of the month, day of the week)

<!-- tabs:end -->

Here you will find a table with all your periodic tasks, to create a new command click on the ```"create crontab"``` button

![Crontab list](images/basic_usage/create_crontab.PNG)