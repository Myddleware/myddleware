<?php

declare(strict_types=1);
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

use Doctrine\ORM\Mapping as ORM;

/**
 * TODO: to avoid confusion, this Entity should probably be renamed to something which:
 * 1) isn't plural
 * 2) isn't a reserved keyword
 * 3) reflects intent (for example PHPFunction, PHPBuiltInFunction or PHPInternalFunction?).
 *
 * @ORM\Entity(repositoryClass="App\Repository\FunctionsRepository")
 */
class Functions implements \Stringable
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="FuncCat")
     * @ORM\JoinColumn(name="fcat_id", referencedColumnName="id")
     */
    private $categoryId;

    /**
     * @ORM\Column(name="name", type="string", length=20)
     */
    private $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setCategoryId(FuncCat $categoryId): self
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    public function getCategoryId()
    {
        return $this->categoryId;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
