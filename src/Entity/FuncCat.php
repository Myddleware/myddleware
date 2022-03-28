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

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 *  * TODO: to avoid confusion, this Entity should probably be renamed to something which:
 * 1) isn't an abbreviation
 * 2) clearly reflects intent (for example FunctionCategory, FunctionType or PHPFunctionCategory ?)
 * 
 * @ORM\Table(name="funccat")
 * @ORM\Entity(repositoryClass="App\Repository\FuncCatRepository")
 */
class FuncCat
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=100)
     */
    private $name;

    /**
     * @var ArrayCollection
     */
    private $functions;

    public function __construct()
    {
        $this->functions = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $nameYml): self
    {
        $this->name = $nameYml;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addFunction(Functions $functions): self
    {
        $this->functions[] = $functions;

        return $this;
    }

    public function removeFunction(Functions $functions)
    {
        $this->functions->removeElement($functions);
    }

    public function getFunctions(): Collection
    {
        return $this->functions;
    }
}
