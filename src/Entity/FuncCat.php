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
use Doctrine\ORM\Mapping as ORM;

/**
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
    private int $id;

    /**
     * @ORM\Column(name="name", type="string", length=100)
     */
    private string $name;

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

    public function setName($nameYml): self
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

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFunctions()
    {
        return $this->functions;
    }
}
