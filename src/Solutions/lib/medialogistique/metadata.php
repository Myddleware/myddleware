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

$moduleFields = [
    'gestion_commande' => [
        'code_interne' => ['label' => 'Code interne', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'compte_client' => ['label' => 'Compte client', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'ref_commande' => ['label' => 'Ref commande', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'date_commande' => ['label' => 'Date commande', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'date_livraison_demandee' => ['label' => 'Date livraison demandee', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'commentaire' => ['label' => 'Commentaire', 'type' => TextType::class, 'type_bdd' => 'varchar(255)', 'required' => 0],
        'origine' => ['label' => 'Origine', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'transporteur' => ['label' => 'Transporteur', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'fichier_source' => ['label' => 'Fichier source', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'reference_destinataire' => ['label' => 'Reference destinataire', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'Livr_nom' => ['label' => 'Livraison Nom', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'Livr_adresse1' => ['label' => 'Livraison adresse1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'Livr_adresse2' => ['label' => 'Livraison adresse2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'Livr_cp' => ['label' => 'Livraison code postal', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'Livr_ville' => ['label' => 'Livraison ville', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'Livr_pays' => ['label' => 'Livraison pays (ISO)', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'Fact_nom' => ['label' => 'Facturation Nom', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'Fact_adresse1' => ['label' => 'Facturation adresse1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'Fact_adresse2' => ['label' => 'Facturation adresse2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'Fact_cp' => ['label' => 'Facturation code postal', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'Fact_ville' => ['label' => 'Facturation ville', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'Fact_pays' => ['label' => 'Facturation pays (ISO)', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'email' => ['label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'telephone' => ['label' => 'téléphone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'article_EAN' => ['label' => 'Article EAN', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'article_ref_client' => ['label' => 'Article ref client', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'quantite' => ['label' => 'Quantite', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'prix_unit_TTC' => ['label' => 'Prix unitaire TTC', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'remise' => ['label' => 'Remise (%)', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'total_frais_port_TTC' => ['label' => 'Total frais port TTC', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'total_commande_TTC' => ['label' => 'Total commande TTC', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'valide' => ['label' => 'Statut commande', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'option' => [
            '3' => 'En préparation',
            '4' => 'Expédiée',
            '9' => 'Facturée',
            '10' => 'Expédiée Facturation externe',
            '21' => 'Annulée',
        ]],
    ],

    'suivi_commande' => [
        'valide' => ['label' => 'Statut commande', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'option' => [
            '3' => 'En préparation',
            '4' => 'Expédiée',
            '9' => 'Facturée',
            '10' => 'Expédiée Facturation externe',
            '21' => 'Annulée',
        ]],
        'bordereau' => ['label' => 'Bordereau', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'qte_cmd' => ['label' => 'Quantité commandée', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'qte_exp' => ['label' => 'Quantité expédiée', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
    ],
    'gestion_article' => [
        'ean' => ['label' => 'Code EAN', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'stock' => ['label' => 'Quantité stock', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'reserve' => ['label' => 'Quantité réservée', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
    ],
];

$fieldsRelate = [
    'suivi_commande' => [
        'ref_client' => ['label' => 'Ref commande', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
    ],
    'gestion_article' => [
        'ref_client' => ['label' => 'Ref article', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
    ],
];

// Metadata override if needed
$file = __DIR__.'/../../../Custom/Solutions/lib/moodle/metadata.php';
if (file_exists($file)) {
    require_once $file;
}
