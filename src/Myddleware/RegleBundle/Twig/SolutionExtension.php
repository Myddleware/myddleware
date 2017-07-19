<?php

/* * *******************************************************************************
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
 * ******************************************************************************* */

namespace Myddleware\RegleBundle\Twig;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Myddleware\RegleBundle\Entity\Solution;
/**
 * @author Dolyveen Renault <d.renault@karudev-informatique.fr>
 */
class SolutionExtension extends \Twig_Extension {

    private $_container;

    public function __construct(Container $container) 
    {
        $this->_container = $container;
    }

    public function getFunctions() 
    {
        return array(
            new \Twig_SimpleFunction('getFieldType', array($this, 'getFieldType')),
            new \Twig_SimpleFunction('getDecryptValue', array($this, 'getDecryptValue')),
        );
    }

    public function getFieldType(Solution $solution, $fieldName) 
    {
        $rule = $this->_container->get('myddleware_rule.' .  $solution->getName());

        // Default value
        $type = TextType::class;

        foreach ($rule->getFieldsLogin() as $k => $v) {

            if ($v['name'] == $fieldName) {
                $type = $v['type'];
            }
        }

        return $type;
    }
    
    public function getDecryptValue($value)
    {
        return $this->decrypt_params($value);
    }

    // Décrypte les paramètres de connexion d'une solution
    private function decrypt_params($tab_params) 
    {
        // Instanciate object to decrypte data
        $encrypter = new \Illuminate\Encryption\Encrypter(substr($this->_container->getParameter('secret'), -16));
        if (is_array($tab_params)) {
            $return_params = array();
            foreach ($tab_params as $key => $value) {
                if (is_string($value)) {
                    $return_params[$key] = $encrypter->decrypt($value);
                }
            }
            return $return_params;
        } else {
            return $encrypter->decrypt($tab_params);
        }
    }

    // Nom de l'extension
    public function getName() 
    {
        return 'solution_extension';
    }

}
