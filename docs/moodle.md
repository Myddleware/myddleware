# Module list

Here is the list of available modules in source (reading) and target (writing) :

| Module                         | Source | Target |
|--------------------------------|--------|--------|
| Courses                        | Yes    | Yes    |
| Users                          | Yes    | Yes    |
| Group members                  | No     | Yes    |
| Groups                         | No     | Yes    |
| Enrollment                     | Yes    | Yes    |
| Unenrollment                   | No     | Yes    |
| Notes                          | No     | Yes    |
| Courses completion             | Yes    | No     |
| Activities completion          | Yes    | No     |
| Courses last access            | Yes    | No     |
| Competencies module completion | Yes    | No     |
| User competencies              | Yes    | No     |
| User grades                    | Yes    | No     |



# Install the plugin Myddleware in Moodle. 

<video src="file/tuto_moodle_new.mp4" width="800"  controls></video>

[![YouTube Channel Views](https://img.shields.io/youtube/channel/views/UCxI0ziSiRXXTqQ-XfFJr7-w?style=social)](https://www.youtube.com/channel/UCxI0ziSiRXXTqQ-XfFJr7-w)

Go to the administration of the site and then ``Plugins`` and ``Install plugins``

![Install plugins](images/moodle/install_plugins.PNG)

Then you can choose to install it from Moodle plugins directory if you Moodle instance is registered or you can install it from the archive that you can [download it here](https://moodle.org/plugins/local_myddleware)

![Choose file](images/moodle/choose_file.PNG)

> This plugin contains several custom webservice functions required by Myddleware. 

Then click on ``Continue`` :  

![Continue](images/moodle/continue_1.PNG)

Click again on ``Continue`` :  

![Continue](images/moodle/continue_2.PNG)

Then click on ``Upgrade Moodle database now`` :  

![Upgrade database moodle](images/moodle/upgrade_database_moodle.PNG)

Then click on ``Continue`` :  

![Upgrade new version](images/moodle/upgrade_new_version.PNG)

# Enable web services

Go to ``Site administration`` and then ``Server Web services`` and ``Overview``

![Overview](images/moodle/overview.PNG)

In the overview page, click on ``Enable web services`` and check the box :  

![Enable web services](images/moodle/enable_web_service.PNG)

In the overview page, click on ``Enable protocols`` and enable the REST one : 

![Enable protocols](images/moodle/enable_protocols.PNG)

In the overview, you should have ``Yes`` and ``REST``, then click on ``Create a specific user`` to create a user for Myddleware :

![Create a specific use](images/moodle/create_specific_use.PNG)

# Create user and role 

Create a new user, click on ``Site administration`` and then  ``Users`` and ``Add new user``

![Create user](images/moodle/create_user.PNG)

> Create the user that the web service will use, set an username, first name, surname, email address and set web service authentication. No need to set a password : 

![Create user and role](images/moodle/create_user_role.PNG)

Then import Myddleware’ s role in Go to ``Site administration`` -> ``Users`` -> ``Permissions`` -> ``Define roles`` :  

![import Myddleware](images/moodle/import_myddleware.PNG)

Then click on ``Add a new role`` :  

![Add role](images/moodle/add_role.PNG)

Download Myddleware’s role <a href="file/myddleware_moodle_role_1.4.xml" download>here</a>. 

Add the xml file here and click on ``Continue``:  

![Add role](images/moodle/add_role_xml.PNG)

Then go to the bottom of the page and click on ``Create this role`` :  

![Create this role](images/moodle/create_this_role.PNG)

Assign this role to the user you have created. Go to ``Site administration`` -> ``Users`` -> ``Permissions`` -> ``Assign system roles``

![Assign role](images/moodle/assign_role.PNG)

> Click on ``Myddleware`` :  

![Myddleware](images/moodle/myddleware.PNG)

Then select Myddleware ‘s user and add it to the left column :  

![Myddleware system](images/moodle/myddleware_system.PNG)

# Authorised users

Go to Site administration -> Server -> Web services -> External services 

![Moodle token](images/moodle/moodle_token.PNG)

Click on ``Authorised users`` :  

![External service](images/moodle/external_service.PNG)

Then select Myddleware‘s user and add it to the left column :  

![Select myddleware](images/moodle/select_myddleware.PNG)


> You shouldn’t have any missing capacity at the bottom of the page. 

Go to Site administration -> Server -> Web services -> Manage tokens 

![Manage token](images/moodle/manage_token.PNG)

Select Myddleware ‘s user and Myddleware service, then click on Save Changes :  

![Create token](images/moodle/create_token.PNG)

Then copy your token :  

![Copy token](images/moodle/copy_token.PNG)

# Generate Moodle’s token 

Finally, you can create your Moodle Myddleware connector by filling in your Moodle URL and your token : 

![Finally](images/moodle/finally.PNG)