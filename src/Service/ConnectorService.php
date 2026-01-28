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

namespace App\Service;

use App\Entity\Connector;
use App\Entity\ConnectorParam;
use Doctrine\ORM\EntityManagerInterface;

class ConnectorService
{
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    /**
     * Récupère les paramètres de connexion sous forme de tableau
     */
    public function resolveParams($connectorId): ?array
    {
        if (empty($connectorId)) return null;

        $id = is_numeric($connectorId) ? (int) $connectorId : $connectorId;
        $connector = $this->em->getRepository(Connector::class)->find($id);

        if (!$connector) return null;
        foreach (['getParameters', 'getParams', 'getParamConnexion', 'toArray'] as $m) {
            if (method_exists($connector, $m)) {
                $params = $connector->{$m}();
                if (is_array($params) && !empty($params)) {
                    return $params;
                }
            }
        }
        $params = [];
        $rows = $this->em->getRepository(ConnectorParam::class)->findBy(['connector' => $connector]);
        foreach ($rows as $row) {
            $k = null; $v = null;
            if (method_exists($row, 'getName'))  { $k = $row->getName(); }
            if (method_exists($row, 'getKey'))   { $k = $k ?? $row->getKey(); }
            if (method_exists($row, 'getValue')) { $v = $row->getValue(); }
            
            if ($k !== null) $params[(string)$k] = $v;
        }
        
        if (!empty($params)) return $params;

        $map = [
            'getUrl'            => 'url',
            'getToken'          => 'token',
            'getLogin'          => 'login',
            'getPassword'       => 'password',
            'getReferenceDate'  => 'date_ref',
        ];
        foreach ($map as $getter => $key) {
            if (method_exists($connector, $getter)) {
                $val = $connector->{$getter}();
                if ($val !== null && $val !== '') {
                    $params[$key] = $val;
                }
            }
        }

        return !empty($params) ? $params : null;
    }
}