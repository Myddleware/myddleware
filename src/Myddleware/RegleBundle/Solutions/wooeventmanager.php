<?php

/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  StÃ©phane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  StÃ©phane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com
 *
 * This file is part of Myddleware.
 *
 * Myddleware is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Myddleware is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
 *********************************************************************************/

 namespace Myddleware\RegleBundle\Solutions;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class wooeventmanagercore extends wordpress {

    protected $subModules = array(
                                    'mep_event_more_date' => array('parent_module' => 'mep_events',
                                                                    'parent_id' => 'event_id')
                                    );

    // Module without reference date
	protected $moduleWithoutReferenceDate = array('mep_cat', 'mep_org');

    public function get_modules($type = 'source') {
        if($type === 'source'){
            return array(
                'mep_events' =>	'Events',
                'mep_cat' =>	'Categories',
                'mep_org' =>	'Organizers',
                'mep_event_more_date' => 'Event More Date',
                // these modules are part of the PRO subscription to the Woocommerce Event Manager Plugin
                // 'mep_event_speaker'	 => 'Event Speaker List',
                'mep_event_attendee' =>	'Event Attendee List',
                );
        }
    }

    
}





// Include custom file if it exists : used to redefine Myddleware standard core
$file = __DIR__. '/../Custom/Solutions/wooeventmanager.php';
if(file_exists($file)){
    require_once($file);
} else { 
    class wooeventmanager extends wooeventmanagercore {

    }
}
