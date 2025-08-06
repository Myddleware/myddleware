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

use App\Solutions\acton;
use App\Solutions\airtable;
use App\Solutions\brevo;
use App\Solutions\cirrusshield;
use App\Solutions\erpnext;
use App\Solutions\eventbrite;
use App\Solutions\facebook;
use App\Solutions\file;
use App\Solutions\hubspot;
use App\Solutions\internallist;
use App\Solutions\iomad;
use App\Solutions\magento;
use App\Solutions\mailchimp;
use App\Solutions\mautic;
use App\Solutions\microsoftsql;
use App\Solutions\moodle;
use App\Solutions\mysql;
use App\Solutions\oracledb;
use App\Solutions\postgresql;
use App\Solutions\prestashop;
use App\Solutions\ringcentral;
use App\Solutions\sagecrm;
use App\Solutions\salesforce;
use App\Solutions\sap;
use App\Solutions\sapcrm;
use App\Solutions\sendinblue;
use App\Solutions\sugarcrm;
use App\Solutions\suitecrm;
use App\Solutions\suitecrm8;
use App\Solutions\vtigercrm;
use App\Solutions\woocommerce;
use App\Solutions\wooeventmanager;
use App\Solutions\wordpress;
use App\Solutions\yousign;
use App\Solutions\zuora;
use Exception;

/**
 * Class SolutionManager.
 */
class SolutionManager
{
    private array $classes = [];
	
	public static array $solutions = [
							'erpnext',
							'hubspot',
							'zuora',
							'file',
							'moodle',
							'magento',
							'microsoftsql',
							'oracledb',
							'mysql',
							'vtigercrm',
							'suitecrm',
							'suitecrm8',
							'mailchimp',
							'prestashop',
							'postgresql',
							'sugarcrm',
							'salesforce',
							'airtable',
							'sendinblue',
							'internallist',
							'iomad',
							'yousign',
							'brevo',
							'acton'
						];

    public function __construct(
        erpnext $erpnext,
        hubspot $hubspot,
        zuora $zuora,
        file $file,
        moodle $moodle,
        magento $magento,
        microsoftsql $microsoftsql,
        oracledb $oracledb,
        mysql $mysql,
        vtigercrm $vtigercrm,
        suitecrm $suitecrm,
        suitecrm8 $suitecrm8,
        mailchimp $mailchimp,
        prestashop $prestashop,
        postgresql $postgresql,
        sugarcrm $sugarcrm,
        salesforce $salesforce,
        airtable $airtable,
        sendinblue $sendinblue,
        internallist $internallist,
        iomad $iomad,
        yousign $yousign,
        brevo $brevo,
        acton $acton
    ) {
		// Load the solution classes
		if (!empty(self::$solutions)) {
			foreach(self::$solutions as $solution) {
				$this->classes[$solution] = $$solution;
			}
		}
    }

    public function get(string $name)
    {
        if (!isset($this->classes[$name])) {
            throw new Exception('Solution ' . $name . ' not found. Please make sure that you have added this solution into Myddleware. ');
        }

        return $this->classes[$name];
    }
}
