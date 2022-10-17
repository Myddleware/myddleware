# Advanced Usage

## Filters

You can apply filters to the data you want to send into your target application. 
To do so, click on the “Filters” tab while creating or editing your rule.

![Filters tab with multiple dropdown lists for each source field](images/advanced_usage/rule_filters_empty.png)

For each of your fields, you can select from a pre-set list of filters to apply.

![Filters tab with multiple dropdown lists for each source field](images/advanced_usage/rule_filters_dropdown_list.png)

And then input the reference to use as a filter. Myddleware will apply this filter to this field inside your rule, meaning
that only the documents matching this exact filter will be sent to the read() method. 

![Filters tab with multiple dropdown lists for each source field](images/advanced_usage/rule_filter_email.png)

## Formulae

> Myddleware embarks a set of valuable tools to facilitate data transformation, including the possibility to add what we call ``formulae``.
> Inside the Myddleware UI, you can create your own formulae to manipulate data before it is being transferred from your source application to your target.
> For instance, let's image your source application contains a ``first_name`` & a ``last_name`` field, but your target application only accepts a ``fullName`` field.
> This is no problem, as you can add a formula on the ``fullName`` target field in which you will be able to concatenate the 2 source fields for example.
> This is a basic example, however you are free to make much more complex formulae if you wish, thanks to a bunch of built-in PHP functions embarked with Myddleware.

### Fundamentals

For starters, formulas allow you to format or to set the values that will be sent to a given target field.
In other words, you have the option of adding fixed text to all uppercase, change timezones, concatenate several source fields etc.
From inside the Myddleware UI, you can create & peruse the available methods to create formulae during the rule creation process,
during the field mapping step. Indeed, once you've added a source field into a target field, you may then click on the ``Add formula`` button of
each target field and then a modal window will open allowing you to add your formula.

### Syntax

To help, syntax highlighting (1) is available to you right on your text box.
Furthermore, you will find below the list of source fields that you have chosen (2),
the available functions and their categories (3) and one or two dropdown list(s) (4) containing the different values
for the list type fields (as SalutationID example).

![Formula pop up](images/dev_guide/formula.PNG)

**Examples**

- To concatenate multiple fields, Myddleware uses the ``.`` symbol, just like in PHP

````php
{field1}.{field2}.' '.{field3}
{salutation}.' '.{first_name}.' '.{last_name}        // e.g. Miss Mary Spears
````
- To concatenate a fixed text with one or multiple fields “Client Name: “.{Firstname}.” “.{Lastname}

- Three-valued condition , “If the Greeting field is ‘Mr.’ then send 1, otherwise send 2” is written as followed : (({Greeting} == “Mr.”) ? “1” : “2”), those three-valued conditions can be nested in order, for example, to make the data correspond. Thus, ({resolution} == “10” ? “Open” : ({resolution} == “20” ? “Fixed” : ({resolution} == “30” ? “Reopened” : “Suspended”))) is correct and functional, this formula means “If resolution is 10 then ‘Open’ is sent, otherwise if resolution is 20 then ‘Fixed‘ is sent, otherwise if resolution is 30 then ‘Reopened’ is sent, otherwise ‘Suspended ‘ is sent.

- Add two fileds {field1} + {field2}

In this article we‘ll look at an important point in your synchronization rules and one of the many setting options offered by Myddleware, formulas.

**Functions**

In the formula of Myddleware, you can use the functions listed at the bottom right (see of the previous image).


Rounds a float, ([PHP](https://www.php.net/manual/fr/function.round.php)) **round(number [, clarification])**:

        round(525.6352, 2) // Returns 525.64

Rounds up, ([PHP](https://www.php.net/manual/fr/function.ceil.php)) **ceil(float)**:

        ceil(525.6352) // Returns 526

Returns the absolute value, ([PHP](https://www.php.net/manual/fr/function.abs.php)) **abs(number)**:

        abs(-5) // Returns 5

Deletes spaces (or other characters) at the beginning and the end of a string, ([PHP](https://www.php.net/manual/fr/function.trim.php)) **trim(string [, Masque])**:

        trim(” bonjour “) // Returns “bonjour”

Lowercases all characters, ([PHP](https://www.php.net/manual/fr/function.mb-strtolower.php)) **lower(STRING)**:

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



## Relationships

*This section is still under construction*

We can create relationships in Myddleware that not only allow us to transfer unrelated data items but whole data models entirely.

For instance, let's imagine we have a Prestashop to SuiteCRM rule which transfers customers. 
Now, if for instance we wanted to add another rule allowing us to send Prestashop ``orders`` to SuiteCRM ``opportunities``.
to send an ``order`` to SuiteCRM, we have to link it to the related ``account``. 
In order to do so, we need to retrieve the account's id corresponding to the customer linked to the order in Prestashop.

To do that, we will link this new rule to the previous rule where we will be able to find the SuiteCRM account id.

