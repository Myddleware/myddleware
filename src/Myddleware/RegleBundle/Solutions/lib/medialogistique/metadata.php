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
	
$moduleFields = array (
					'gestion_commande' => array(						
						'code_interne' => array('label' => 'Code interne', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'compte_client' => array('label' => 'Compte client', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'ref_commande' => array('label' => 'Ref commande', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'date_commande' => array('label' => 'Date commande', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'date_livraison_demandee' => array('label' => 'Date livraison demandee', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'commentaire' => array('label' => 'Commentaire', 'type' => TextType::class, 'type_bdd' => 'varchar(255)', 'required' => 0),
						'origine' => array('label' => 'Origine', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'transporteur' => array('label' => 'Transporteur', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'fichier_source' => array('label' => 'Fichier source', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'reference_destinataire' => array('label' => 'Reference destinataire', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'Livr_nom' => array('label' => 'Livraison Nom', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'Livr_adresse1' => array('label' => 'Livraison adresse1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'Livr_adresse2' => array('label' => 'Livraison adresse2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'Livr_cp' => array('label' => 'Livraison code postal', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'Livr_ville' => array('label' => 'Livraison ville', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'Livr_pays' => array('label' => 'Livraison pays (ISO)', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'Fact_nom' => array('label' => 'Facturation Nom', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'Fact_adresse1' => array('label' => 'Facturation adresse1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'Fact_adresse2' => array('label' => 'Facturation adresse2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'Fact_cp' => array('label' => 'Facturation code postal', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'Fact_ville' => array('label' => 'Facturation ville', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'Fact_pays' => array('label' => 'Facturation pays (ISO)', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'email' => array('label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'telephone' => array('label' => 'téléphone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'article_EAN' => array('label' => 'Article EAN', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'article_ref_client' => array('label' => 'Article ref client', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'quantite' => array('label' => 'Quantite', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'prix_unit_TTC' => array('label' => 'Prix unitaire TTC', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'remise' => array('label' => 'Remise (%)', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'total_frais_port_TTC' => array('label' => 'Total frais port TTC', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'total_commande_TTC' => array('label' => 'Total commande TTC', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),		
						'valide' => array('label' => 'Statut commande', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'option' => array(
																																				'3' => 'En préparation',
																																				'4' => 'Expédiée',
																																				'9' => 'Facturée',
																																				'10' => 'Expédiée Facturation externe',
																																				'21' => 'Annulée'
						)),		
					),
					
					'suivi_commande' => array(			
						'valide' => array('label' => 'Statut commande', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'option' => array(
																																				'3' => 'En préparation',
																																				'4' => 'Expédiée',
																																				'9' => 'Facturée',
																																				'10' => 'Expédiée Facturation externe',
																																				'21' => 'Annulée'
						)),		
						'bordereau' => array('label' => 'Bordereau', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'qte_cmd' => array('label' => 'Quantité commandée', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'qte_exp' => array('label' => 'Quantité expédiée', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
					),
					'gestion_article' => array(			
						'ean' => array('label' => 'Code EAN', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),		
						'stock' => array('label' => 'Quantité stock', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'reserve' => array('label' => 'Quantité réservée', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
					),
				);

$fieldsRelate = array(
					'suivi_commande' => array(			
						'ref_client' => array('label' => 'Ref commande', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
					),
					'gestion_article' => array(			
						'ref_client' => array('label' => 'Ref article', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
					),
				);


// Metadata override if needed
$file = __DIR__.'/../../../Custom/Solutions/lib/moodle/metadata.php';
if(file_exists($file)){
	require_once($file);
}						