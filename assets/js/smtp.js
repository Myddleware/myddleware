/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  St�phane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  St�phane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com	
 
 This file is part of Myddleware.
 
 Myddleware is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Myddleware is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
*********************************************************************************/

// panneaux soumis à condition
const apiKey = 'regle_bundlemanagement_smtp_ApiKey';
const port = 'regle_bundlemanagement_smtp_port';
const host = 'regle_bundlemanagement_smtp_host';
const auth_mode = 'regle_bundlemanagement_smtp_auth_mode';
const encryption = 'regle_bundlemanagement_smtp_encryption';
const user = 'regle_bundlemanagement_smtp_user';
const password = 'regle_bundlemanagement_smtp_password';

// liste de tous les panneaux soumis à condition
const apiTab = [
    apiKey
];

const smptTabs = [
    port,
    host,
    auth_mode,
    encryption,
    user,
    password
];



$(function () {
    if ($('#regle_bundlemanagement_smtp_transport').val() == "sendinblue") {
        displayTabs(apiTab);
        hideAllTabs(smptTabs);

    } else {
        displayTabs(smptTabs);
        hideAllTabs(apiTab);
    }

    $("#regle_bundlemanagement_smtp_transport").on("change", function () {
        var transport = $("#regle_bundlemanagement_smtp_transport").val();
        if (transport === 'sendinblue') {
            displayTabs(apiTab);
            hideAllTabs(smptTabs);
        } else {
            displayTabs(smptTabs);
            hideAllTabs(apiTab);
        }

    });


});


// cacher les sous-panneaux soumis à conditions
function hideAllTabs(tabsArray) {
    for (let index in tabsArray) {
        hideTab(tabsArray[index]);
    }
}

// afficher les sous-panneaux soumis à conditions
function displayTabs(tabsArray) {
    for (let index in tabsArray) {
        displayTab(tabsArray[index]);
    }
}

function displayTab(tabname) {
    $(`#${tabname}`).show();
    $(`#${tabname}`).parent().show();
    $(`#${tabname}`).parent().parent().show();

}

function hideTab(tabname) {
    $(`#${tabname}`).hide();
    $(`#${tabname}`).parent().hide();
    $(`#${tabname}`).parent().parent().hide();
}
