<?php
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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

namespace App\Manager;

use App\Solutions\Airtable;
use App\Solutions\CirrusShield;
use App\Solutions\ERPNext;
use App\Solutions\Eventbrite;
use App\Solutions\Facebook;
use App\Solutions\File;
use App\Solutions\Hubspot;
use App\Solutions\Magento;
use App\Solutions\Mailchimp;
use App\Solutions\Mautic;
use App\Solutions\MicrosoftSQL;
use App\Solutions\Moodle;
use App\Solutions\MySQL;
use App\Solutions\OracleDatabase;
use App\Solutions\PostgreSQL;
use App\Solutions\PrestaShop;
use App\Solutions\RingCentral;
use App\Solutions\SageCRM;
use App\Solutions\Salesforce;
use App\Solutions\SAP;
use App\Solutions\SAPCRM;
use App\Solutions\Sendinblue;
use App\Solutions\SugarCRM;
use App\Solutions\SuiteCRM;
use App\Solutions\VtigerCRM;
use App\Solutions\WooCommerce;
use App\Solutions\WooEventManager;
use App\Solutions\WordPress;
use App\Solutions\Zuora;
use Exception;

class SolutionManager
{
    private $classes = [];

    public function __construct(
        Airtable $airtable,
        CirrusShield $cirrusshield,
        ERPNext $erpnext,
        Eventbrite $eventbrite,
        Facebook $facebook,
        File $file,
        Hubspot $hubspot,
        Magento $magento,
        Mailchimp $mailchimp,
        Mautic $mautic,
        MicrosoftSQL $microsoftsql,
        Moodle $moodle,
        MySQL $mysql,
        OracleDatabase $oracledb,
        PostgreSQL $postgresql,
        PrestaShop $prestashop,
        RingCentral $ringcentral,
        SageCRM $sagecrm,
        Salesforce $salesforce,
        SAP $sap,
        SAPCRM $sapcrm,
        Sendinblue $sendinblue,
        SugarCRM $sugarcrm,
        SuiteCRM $suitecrm,
        VtigerCRM $vtigercrm,
        WooCommerce $woocommerce,
        WooEventManager $wooeventmanager,
        WordPress $wordpress,
        Zuora $zuora
    ) {
        $this->classes = [
            'airtable' => $airtable,
            'cirrusshield' => $cirrusshield,
            'erpnext' => $erpnext,
            'eventbrite' => $eventbrite,
            'facebook' => $facebook,
            'file' => $file,
            'hubspot' => $hubspot,
            'magento' => $magento,
            'mailchimp' => $mailchimp,
            'mautic' => $mautic,
            'microsoftsql' => $microsoftsql,
            'moodle' => $moodle,
            'mysql' => $mysql,
            'oracledb' => $oracledb,
            'postgresql' => $postgresql,
            'prestashop' => $prestashop,
            'ringcentral' => $ringcentral,
            'sagecrm' => $sagecrm,
            'salesforce' => $salesforce,   
            'sap' => $sap,
            'sapcrm' => $sapcrm,
            'sendinblue' => $sendinblue,
            'sugarcrm' => $sugarcrm,
            'suitecrm' => $suitecrm,
            'vtigercrm' => $vtigercrm,
            'woocommerce' => $woocommerce,
            'wooeventmanager' => $wooeventmanager,
            'wordpress' => $wordpress,
            'zuora' => $zuora,
        ];
    }

    public function get(string $name)
    {
        if (!isset($this->classes[$name])) {
            throw new Exception('Solution '.ucfirst($name).' not found. Please make sure that you have added this solution into Myddleware. ');
        }

        return $this->classes[$name];
    }
}
