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

namespace Myddleware\RegleBundle\DataFixtures\ORM;
 
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Myddleware\RegleBundle\Entity\FuncCat;
use Myddleware\RegleBundle\Entity\Functions;
 
class LoadFunctionData implements FixtureInterface
{
    private $manager; 
	protected $functionData = array(
									'mathematical' 	=> array('round','ceil','abs'), 
									'text'			=> array('trim','ltrim','rtrim','lower','upper','substr','striptags','changeValue','htmlEntityDecode','replace','utf8encode','utf8decode','htmlentities','htmlspecialchars','strlen','urlencode','chr','json_decode','json_encode','getValueFromArray'),
									'date'			=> array('date','microtime','changeTimeZone','changeFormatDate')
								);
 
    public function load(ObjectManager $manager){
        $this->manager = $manager; 
        $this->generateEntities(); 
        $this->manager->flush();
    }
 
    public function getOrder() {
        return 1; 
    }
 
    private function generateEntities() {
        foreach($this->functionData as $cat => $functions) {
            $this->newEntity($cat,$functions);
        }
    }
 
    private function newEntity($cat,$functions) {
	
		// Check if the function category doesn't exist in Myddleware we create it
		$funcCat = $this->manager
					 ->getRepository('RegleBundle:FuncCat')
					 ->findOneByName($cat);
		if (
				empty($funcCat)
			 || empty($funcCat->getId()	)
		) {	
			$funcCat = new FuncCat();
			$funcCat->setName($cat);
			$this->manager->persist($funcCat);
		}	
		foreach($functions as $function) {
			// Check if the function  doesn't exist in Myddleware we create it else we update it
			$func = $this->manager
						 ->getRepository('RegleBundle:Functions')
						 ->findOneByName($function);
			if (
					empty($func)
				 || empty($func->getId()	)
			) {	
				$func = new Functions();
			}
			$func->setName($function);
			$func->setCategorieId($funcCat);
			$this->manager->persist($func);
		} 
    }
}