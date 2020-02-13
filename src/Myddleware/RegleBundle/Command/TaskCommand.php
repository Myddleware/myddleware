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

namespace Myddleware\RegleBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TaskCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->setName('myddleware:synchro')
            ->setDescription('Synchronisation des données')
            ->addArgument('rule', InputArgument::REQUIRED, "Alias de la règle")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$step = 1;
		try {		
			$logger = $this->getContainer()->get('logger');		
					
			// Source -------------------------------------------------
			// alias de la règle en params
			$rule = $input->getArgument('rule');
			// Récupération du Job			
			$job = $this->getContainer()->get('myddleware_job.job');
			// Clear message in case this task is run by jobscheduler. In this case message has to be refreshed.
			$job->message = '';			
			
			if ($job->initJob('Synchro : '.$rule)) {
				$output->writeln( '1;'.$job->id );  // Ne pas supprimer car nécessaire pour afficher les log d'un job manuel
				
				if (!empty($rule)) {			
					if ($rule == 'ERROR') {
						// Premier paramètre : limite d'enregistrement traités
						// Deuxième paramètre, limite d'erreur : si un flux a plus de tentative que le paramètre il n'est pas relancé
						$job->runError( 50 , 100);	
					}
					else {
						// Envoi du job sur toutes les règles demandées. Si ALL est sélectionné alors on récupère toutes les règle dans leur ordre de lancement sinon on lance seulement la règle demandée.
						if ($rule == 'ALL') {
							$rules = $job->getRules();
						}
						else {
							$rules[] = $rule;
						}								
						if (!empty($rules)) {
							foreach ($rules as $key => $value) {								
								echo $value.chr(10);
								$output->writeln('Read data for rule : <question>'.$value.'</question>');
								// Chargement des données de la règle
								if ($job->setRule($value)) {		
									// Sauvegarde des données sources dans les tables de myddleware
									$output->writeln($value.' : Create documents.');			
									$nb = $job->createDocuments();
									$output->writeln($value.' : Number of documents created : '.$nb); 

									// Permet de filtrer les documents
									$job->filterDocuments();
									
									// Permet de valider qu'aucun document précédent pour la même règle et le même id n'est pas bloqué
									$job->ckeckPredecessorDocuments();

									// Permet de valider qu'au moins un document parent(relation père) est existant
									$job->ckeckParentDocuments();
									
									// Permet de transformer les docuement avant d'être envoyés à la cible
									$job->transformDocuments();	

									// Historisation des données avant modification dans la cible
									$job->getTargetDataDocuments();

									// Envoi des documents à la cible
									$job->sendDocuments();	
								}
							}
						}
					}
				}	
			}
		}
		catch(\Exception $e) {
			$job->message .= $e->getMessage();
		}
		
		// Close job if it has been created
		if($job->createdJob === true) {
			$job->closeJob();
		}
		
		// Retour en console --------------------------------------
		if (!empty($job->message)) {
			$output->writeln( '0;<error>'.$job->message.'</error>');
			$logger->error( $job->message );
		} 
	}
}