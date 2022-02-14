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

use App\Solutions\airtable;
use App\Solutions\bittle;
use App\Solutions\cirrusshield;
use App\Solutions\dolist;
use App\Solutions\erpnext;
use App\Solutions\eventbrite;
use App\Solutions\facebook;
use App\Solutions\file;
use App\Solutions\hubspot;
use App\Solutions\magento;
use App\Solutions\mailchimp;
use App\Solutions\mautic;
use App\Solutions\medialogistique;
use App\Solutions\microsoftsql;
use App\Solutions\moodle;
use App\Solutions\mysql;
use App\Solutions\oracledb;
use App\Solutions\postgresql;
use App\Solutions\prestashop;
use App\Solutions\ringcentral;
use App\Solutions\sage50;
use App\Solutions\sagecrm;
use App\Solutions\sagelive;
use App\Solutions\salesforce;
use App\Solutions\sap;
use App\Solutions\sapcrm;
use App\Solutions\shopapplication;
use App\Solutions\sugarcrm;
use App\Solutions\suitecrm;
use App\Solutions\vtigercrm;
use App\Solutions\woocommerce;
use App\Solutions\wooeventmanager;
use App\Solutions\wordpress;
use App\Solutions\zuora;
use App\Solutions\sendinblue;
use Exception;

/**
 * Class SolutionManager.
 *
 * @package App\Manager
 *
 *
 */
class SolutionManager
{
    private $classes = [];

    public function __construct(
        wordpress $wordpress,
        woocommerce $woocommerce,
        wooeventmanager $wooeventmanager,
        erpnext $erpnext,
        facebook $facebook,
        mautic $mautic,
        hubspot $hubspot,
        zuora $zuora,
        cirrusshield $cirrusshield,
        ringcentral $ringcentral,
        file $file,
        moodle $moodle,
        magento $magento,
        microsoftsql $microsoftsql,
        oracledb $oracledb,
        mysql $mysql,
        sap $sap,
        sapcrm $sapcrm,
        sagecrm $sagecrm,
        vtigercrm $vtigercrm,
        suitecrm $suitecrm,
        eventbrite $eventbrite,
        mailchimp $mailchimp,
        prestashop $prestashop,
        postgresql $postgresql,
        sugarcrm $sugarcrm,
        salesforce $salesforce,
        airtable $airtable,
        sendinblue $sendinblue
    ) {
        $this->classes = [
            'wordpress' => $wordpress,
            'wooeventmanager' => $wooeventmanager,
            'woocommerce' => $woocommerce,
            'erpnext' => $erpnext,
            'facebook' => $facebook,
            'mautic' => $mautic,
            'hubspot' => $hubspot,
            'zuora' => $zuora,
            'cirrusshield' => $cirrusshield,
            'ringcentral' => $ringcentral,
            'file' => $file,
            'moodle' => $moodle,
            'magento' => $magento,
            'microsoftsql' => $microsoftsql,
            'oracledb' => $oracledb,
            'mysql' => $mysql,
            'sap' => $sap,
            'sapcrm' => $sapcrm,
            'sagecrm' => $sagecrm,
            'vtigercrm' => $vtigercrm,
            'suitecrm' => $suitecrm,
            'eventbrite' => $eventbrite,
            'mailchimp' => $mailchimp,
            'prestashop' => $prestashop,
            'postgresql' => $postgresql,
            'sugarcrm' => $sugarcrm,
            'salesforce' => $salesforce,
            'airtable' => $airtable,
            'sendinblue' => $sendinblue,
        ];
    }

    public function get(string $name)
    {
        if (!isset($this->classes[$name])) {
            throw new Exception('Solution '.$name.' not found. Please make sure that you have added this solution into Myddleware. ');
        }

        return $this->classes[$name];
    }
}
