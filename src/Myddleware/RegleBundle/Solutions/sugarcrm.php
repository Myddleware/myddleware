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

namespace Myddleware\RegleBundle\Solutions;

class sugarcrmcore  extends suitecrm { 

	// Array with all many-to-many relationship (different than SuiteCRM)
	protected $module_relationship_many_to_many = array(
													'calls_contacts' => array('label' => 'Relationship Call Contact', 'module_name' => 'Calls', 'link_field_name' => 'contacts', 'fields' => array(), 'relationships' => array('call_id','contact_id')),
													'calls_users' => array('label' => 'Relationship Call User', 'module_name' => 'Calls', 'link_field_name' => 'users', 'fields' => array(), 'relationships' => array('call_id','user_id')),
													'calls_leads' => array('label' => 'Relationship Call Lead', 'module_name' => 'Calls', 'link_field_name' => 'leads', 'fields' => array(), 'relationships' => array('call_id','lead_id')),
													'cases_bugs' => array('label' => 'Relationship Case Bug', 'module_name' => 'Cases', 'link_field_name' => 'bugs', 'fields' => array(), 'relationships' => array('case_id','bug_id')),
													'contacts_bugs' => array('label' => 'Relationship Contact Bug', 'module_name' => 'Contacts', 'link_field_name' => 'bugs', 'fields' => array(), 'relationships' => array('contact_id','bug_id')),
													'contacts_cases' => array('label' => 'Relationship Contact Case', 'module_name' => 'Contacts', 'link_field_name' => 'cases', 'fields' => array(), 'relationships' => array('contact_id','case_id')),
													'meetings_contacts' => array('label' => 'Relationship Metting Contact', 'module_name' => 'Meetings', 'link_field_name' => 'contacts', 'fields' => array(), 'relationships' => array('meeting_id','contact_id')),
													'meetings_users' => array('label' => 'Relationship Meeting User', 'module_name' => 'Meetings', 'link_field_name' => 'users', 'fields' => array(), 'relationships' => array('meeting_id','user_id')),
													'meetings_leads' => array('label' => 'Relationship Meeting Lead', 'module_name' => 'Meetings', 'link_field_name' => 'leads', 'fields' => array(), 'relationships' => array('meeting_id','lead_id')),
													'opportunities_contacts' => array('label' => 'Relationship Opportunity Contact', 'module_name' => 'Opportunities', 'link_field_name' => 'contacts', 'fields' => array('contact_role'), 'relationships' => array('opportunity_id','contact_id')), // contact_role exist in opportunities vardef for module contact (entry rel_fields)
													'prospect_list_campaigns' => array('label' => 'Relationship Prospect_list Campaign', 'module_name' => 'ProspectLists', 'link_field_name' => 'campaigns', 'fields' => array(), 'relationships' => array('prospect_list_id','campaign_id')),
													'prospect_list_contacts' => array('label' => 'Relationship Prospect_list Contact', 'module_name' => 'ProspectLists', 'link_field_name' => 'contacts', 'fields' => array(), 'relationships' => array('prospect_list_id','contact_id')),
													'prospect_list_prospects' => array('label' => 'Relationship Prospect_list Prospect', 'module_name' => 'ProspectLists', 'link_field_name' => 'prospects', 'fields' => array(), 'relationships' => array('prospect_list_id','Prospect_id')),
													'prospect_list_leads' => array('label' => 'Relationship Prospect_list Lead', 'module_name' => 'ProspectLists', 'link_field_name' => 'leads', 'fields' => array(), 'relationships' => array('prospect_list_id','lead_id')),
													'prospect_list_users' => array('label' => 'Relationship Prospect_list User', 'module_name' => 'ProspectLists', 'link_field_name' => 'users', 'fields' => array(), 'relationships' => array('prospect_list_id','user_id')),
													'prospect_list_accounts' => array('label' => 'Relationship Prospect_list Account', 'module_name' => 'ProspectLists', 'link_field_name' => 'accounts', 'fields' => array(), 'relationships' => array('prospect_list_id','account_id')),
													'projects_bugs' => array('label' => 'Relationship Project Bug', 'module_name' => 'Projects', 'link_field_name' => 'bugs', 'fields' => array(), 'relationships' => array('project_id','bug_id')),
													'projects_cases' => array('label' => 'Relationship Project Case', 'module_name' => 'Projects', 'link_field_name' => 'cases', 'fields' => array(), 'relationships' => array('project_id','case_id')),
													'projects_accounts' => array('label' => 'Relationship Project Account', 'module_name' => 'Projects', 'link_field_name' => 'accounts', 'fields' => array(), 'relationships' => array('project_id','account_id')),
													'projects_contacts' => array('label' => 'Relationship Project Contact', 'module_name' => 'Projects', 'link_field_name' => 'contacts', 'fields' => array(), 'relationships' => array('project_id','contact_id')),
													'projects_opportunities' => array('label' => 'Relationship Project Opportunity', 'module_name' => 'Projects', 'link_field_name' => 'opportunities', 'fields' => array(), 'relationships' => array('project_id','opportunity_id')),
													'email_marketing_prospect_lists' => array('label' => 'Relationship Email_marketing Prospect_list', 'module_name' => 'EmailMarketing', 'link_field_name' => 'prospect_lists', 'fields' => array(), 'relationships' => array('email_marketing_id','prospect_list_id')),
													'leads_documents' => array('label' => 'Relationship Lead Document', 'module_name' => 'Leads', 'link_field_name' => 'documents', 'fields' => array(), 'relationships' => array('lead_id','document_id')),
													'documents_accounts' => array('label' => 'Relationship Document Account', 'module_name' => 'Documents', 'link_field_name' => 'accounts', 'fields' => array(), 'relationships' => array('document_id','account_id')),
													'documents_contacts' => array('label' => 'Relationship Document Contact', 'module_name' => 'Documents', 'link_field_name' => 'contacts', 'fields' => array(), 'relationships' => array('document_id','contact_id')),
													'documents_opportunities' => array('label' => 'Relationship Document Opportunity', 'module_name' => 'Documents', 'link_field_name' => 'opportunities', 'fields' => array(), 'relationships' => array('document_id','opportunity_id')),
													'documents_cases' => array('label' => 'Relationship Document Case', 'module_name' => 'Documents', 'link_field_name' => 'cases', 'fields' => array(), 'relationships' => array('document_id','case_id')),
													'documents_bugs' => array('label' => 'Relationship Document Bug', 'module_name' => 'Documents', 'link_field_name' => 'bugs', 'fields' => array(), 'relationships' => array('document_id','bug_id')),
												);
}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/sugarcrm.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class sugarcrm extends sugarcrmcore {
		
	}
}