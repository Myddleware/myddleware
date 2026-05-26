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
use App\Solutions\dolibarr;
use App\Solutions\dynamicsbusiness;
use App\Solutions\dynamicscrm;
use App\Solutions\erpnext;
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
use App\Solutions\salesforce;
use App\Solutions\salesforce_v2;
use App\Solutions\sendinblue;
use App\Solutions\sugarcrm;
use App\Solutions\suitecrm;
use App\Solutions\suitecrm8;
use App\Solutions\vtigercrm;
use App\Solutions\yousign;
use App\Solutions\zuora;
use App\Service\DebugLogger;
use Exception;

/**
 * Class SolutionManager.
 */
class SolutionManager
{
    private DebugLogger $debugLogger;
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
        'mautic',
        'mysql',
        'dolibarr',
        'vtigercrm',
        'suitecrm',
        'mailchimp',
        'prestashop',
        'postgresql',
        'sugarcrm',
        'salesforce',
        'salesforce_v2',
        'airtable',
        'sendinblue',
        'internallist',
        'suitecrm8',
        'yousign',
        'brevo',
        'acton',
        'dynamicsbusiness',
        'dynamicscrm',
        'iomad',
    ];

    public function __construct(
        DebugLogger $debugLogger,
        erpnext $erpnext,
        hubspot $hubspot,
        zuora $zuora,
        file $file,
        moodle $moodle,
        magento $magento,
        microsoftsql $microsoftsql,
        oracledb $oracledb,
        mysql $mysql,
        dolibarr $dolibarr,
        mautic $mautic,
        vtigercrm $vtigercrm,
        suitecrm $suitecrm,
        mailchimp $mailchimp,
        prestashop $prestashop,
        postgresql $postgresql,
        sugarcrm $sugarcrm,
        salesforce $salesforce,
        salesforce_v2 $salesforce_v2,
        airtable $airtable,
        sendinblue $sendinblue,
        internallist $internallist,
        yousign $yousign,
        suitecrm8 $suitecrm8,
        brevo $brevo,
        acton $acton,
        dynamicsbusiness $dynamicsbusiness,
        dynamicscrm $dynamicscrm,
        iomad $iomad,
    ) {
        $this->classes = [
            'erpnext' => $erpnext,
            'hubspot' => $hubspot,
            'zuora' => $zuora,
            'file' => $file,
            'moodle' => $moodle,
            'magento' => $magento,
            'microsoftsql' => $microsoftsql,
            'oracledb' => $oracledb,
            'mautic' => $mautic,
            'mysql' => $mysql,
            'dolibarr' => $dolibarr,
            'vtigercrm' => $vtigercrm,
            'suitecrm' => $suitecrm,
            'mailchimp' => $mailchimp,
            'prestashop' => $prestashop,
            'postgresql' => $postgresql,
            'sugarcrm' => $sugarcrm,
            'salesforce' => $salesforce,
            'salesforce_v2' => $salesforce_v2,
            'airtable' => $airtable,
            'sendinblue' => $sendinblue,
            'internallist' => $internallist,
            'suitecrm8' => $suitecrm8,
            'yousign' => $yousign,
            'brevo' => $brevo,
            'acton' => $acton,
            'dynamicsbusiness' => $dynamicsbusiness,
            'dynamicscrm' => $dynamicscrm,
            'iomad' => $iomad,
        ];
		$this->debugLogger = $debugLogger;
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
            throw new \Exception('Requested solution was not found. Please make sure that you have added this solution into Myddleware.');
        }
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['name' => $name]);
        $__debugReturn = null;
        try {
            if (!isset($this->classes[$name])) {
                throw new Exception('Solution ' . $name . ' not found. Please make sure that you have added this solution into Myddleware. ');
            }

            return $__debugReturn = $this->classes[$name];
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }
}
