<?php

namespace App\Entity;

// namespace Shapecode\Bundle\CronBundle\Entity;

use Cron\CronExpression;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Shapecode\Bundle\CronBundle\Entity\CronJob as CoreCronJob;

/**
 * @ORM\Entity(repositoryClass="Shapecode\Bundle\CronBundle\Repository\CronJobRepository")
 */
// class CronJob extends CoreCronJob
class CronJob extends CoreCronJob
{
   
	// CronJob is redefined because we need the method setCommand to generate the form
    public function setCommand(string $command): self
    {
        $this->command = $command;
		
        return $this;
    }

}
