<?php

/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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

namespace App\Solutions;

class wooeventmanagercore extends wordpress
{
    protected $subModules = [
                                    'mep_event_more_date' => ['parent_module' => 'mep_events',
                                                                    'parent_id' => 'event_id', ],
                                    ];

    // Module without reference date
    protected $moduleWithoutReferenceDate = ['mep_cat', 'mep_org'];

    public function get_modules($type = 'source')
    {
        if ('source' === $type) {
            return [
                'mep_events' => 'Events',
                'mep_cat' => 'Categories',
                'mep_org' => 'Organizers',
                'mep_event_more_date' => 'Event More Date',
                // these modules are part of the PRO subscription to the Woocommerce Event Manager Plugin
                // 'mep_event_speaker'	 => 'Event Speaker List',
                // 'mep_event_attendee' =>	'Event Attendee List',
                ];
        }
    }
}

class wooeventmanager extends wooeventmanagercore
{
}
