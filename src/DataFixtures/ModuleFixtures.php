<?php

namespace App\DataFixtures;

use App\Entity\Module;
use App\Entity\Solution;
use App\DataFixtures\SolutionFixtures;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ModuleFixtures extends Fixture  implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $solutionRepository = $manager->getRepository(Solution::class);
        $modules = $this->getDefaultModuleData();
        foreach ($modules as $moduleProperties) {
            $module = new Module();
            $module->setName($moduleProperties['name']);
            $module->setNameKey($moduleProperties['nameKey']);
            $solution = $solutionRepository->findOneBy(['name' => $moduleProperties['solution']]);
            $module->setSolution($solution);
            $module->setDirection($moduleProperties['direction']);
            $manager->persist($module);
        }

        $manager->flush();
    }

    public function getDefaultModuleData(): ?array
    {
        return [
            // ['name' => 'Organizer', 'nameKey' => 'Organizer', 'solution' => 'Eventbrite', 'direction' => 'both'],
            // ['name' => 'Events', 'nameKey' => 'Events', 'solution' => 'Eventbrite', 'direction' => 'both'],
            // ['name' => 'Tickets', 'nameKey' => 'Tickets', 'solution' => 'Eventbrite', 'direction' => 'both'],
            // ['name' => 'Venues', 'nameKey' => 'Venues', 'solution' => 'Eventbrite', 'direction' => 'both'],
            // ['name' => 'Attendees', 'nameKey' => 'Attendees', 'solution' => 'Eventbrite', 'direction' => 'source'],
            // ['name' => 'Users', 'nameKey' => 'Users', 'solution' => 'Eventbrite', 'direction' => 'both'],
            ['name' => 'associate_deal', 'nameKey' => 'Associate deals with companies/contacts', 'solution' => 'Hubspot', 'direction' => 'target'],
            ['name' => 'associate_deal_contact', 'nameKey' => 'Associate deals with contacts', 'solution' => 'Hubspot', 'direction' => 'source'],
            ['name' => 'associate_deal_company', 'nameKey' => 'Associate deals with companies', 'solution' => 'Hubspot', 'direction' => 'source'],
            ['name' => 'engagement_task', 'nameKey' => 'Engagement Task', 'solution' => 'Hubspot', 'direction' => 'source'],
            ['name' => 'engagement_call', 'nameKey' => 'Engagement Call', 'solution' => 'Hubspot', 'direction' => 'source'],
            ['name' => 'engagement_email', 'nameKey' => 'Engagement Email', 'solution' => 'Hubspot', 'direction' => 'source'],
            ['name' => 'engagement_meeting', 'nameKey' => 'Engagement Meeting', 'solution' => 'Hubspot', 'direction' => 'source'],
            ['name' => 'products', 'nameKey' => 'Products', 'solution' => 'Hubspot', 'direction' => 'source'],
            ['name' => 'line_items', 'nameKey' => 'Line items', 'solution' => 'Hubspot', 'direction' => 'source'],
            ['name' => 'companies', 'nameKey' => 'Companies', 'solution' => 'Hubspot', 'direction' => 'both'],
            ['name' => 'contacts', 'nameKey' => 'Contacts', 'solution' => 'Hubspot', 'direction' => 'both'],
            ['name' => 'deals', 'nameKey' => 'Deals', 'solution' => 'Hubspot', 'direction' => 'both'],
            ['name' => 'owners', 'nameKey' => 'Owners', 'solution' => 'Hubspot', 'direction' => 'both'],
            ['name' => 'deal_pipeline', 'nameKey' => 'Deal pipeline', 'solution' => 'Hubspot', 'direction' => 'both'],
            ['name' => 'deal_pipeline_stage', 'nameKey' => 'Deal pipeline stage', 'solution' => 'Hubspot', 'direction' => 'both'],
            ['name' => 'engagement_note', 'nameKey' => 'Engagement Note', 'solution' => 'Hubspot', 'direction' => 'both'],
            ['name' => 'customers', 'nameKey' => 'Customers', 'solution' => 'Magento', 'direction' => 'source'],
            ['name' => 'customer_address', 'nameKey' => 'Customer Address', 'solution' => 'Magento', 'direction' => 'source'],
            ['name' => 'orders', 'nameKey' => 'Sales Order', 'solution' => 'Magento', 'direction' => 'source'],
            ['name' => 'products', 'nameKey' => 'Products', 'solution' => 'Magento', 'direction' => 'source'],
            ['name' => 'orders_items', 'nameKey' => 'Orders Items', 'solution' => 'Magento', 'direction' => 'source'],
            ['name' => 'campaigns', 'nameKey' => 'Campaigns', 'solution' => 'Mailchimp', 'direction' => 'target'],
            ['name' => 'lists', 'nameKey' => 'Lists', 'solution' => 'Mailchimp', 'direction' => 'target'],
            ['name' => 'members', 'nameKey' => 'Members', 'solution' => 'Mailchimp', 'direction' => 'target'],
            ['name' => 'users', 'nameKey' => 'Users', 'solution' => 'Moodle', 'direction' => 'source'],
            ['name' => 'courses', 'nameKey' => 'Courses', 'solution' => 'Moodle', 'direction' => 'source'],
            ['name' => 'get_users_completion', 'nameKey' => 'Get course activity completion', 'solution' => 'Moodle', 'direction' => 'source'],
            ['name' => 'get_users_last_access', 'nameKey' => 'Get users last access', 'solution' => 'Moodle', 'direction' => 'source'],
            ['name' => 'get_enrolments_by_date', 'nameKey' => 'Get enrolments', 'solution' => 'Moodle', 'direction' => 'source'],
            ['name' => 'get_course_completion_by_date', 'nameKey' => 'Get course completion', 'solution' => 'Moodle', 'direction' => 'source'],
            ['name' => 'get_user_compentencies_by_date', 'nameKey' => 'Get user compentency', 'solution' => 'Moodle', 'direction' => 'source'],
            ['name' => 'get_competency_module_completion_by_date', 'nameKey' => 'Get compentency module completion', 'solution' => 'Moodle', 'direction' => 'source'],
            ['name' => 'get_user_grades', 'nameKey' => 'Get user grades', 'solution' => 'Moodle', 'direction' => 'source'],
            ['name' => 'groups', 'nameKey' => 'Groups', 'solution' => 'Moodle', 'direction' => 'target'],
            ['name' => 'group_members', 'nameKey' => 'Group members', 'solution' => 'Moodle', 'direction' => 'target'],
            ['name' => 'manual_enrol_users', 'nameKey' => 'Manual enrol users', 'solution' => 'Moodle', 'direction' => 'target'],
            ['name' => 'manual_unenrol_users', 'nameKey' => 'Manual unenrol users', 'solution' => 'Moodle', 'direction' => 'target'],
            ['name' => 'notes', 'nameKey' => 'Notes', 'solution' => 'Moodle', 'direction' => 'target'],
            ['name' => 'call-log', 'nameKey' => 'Call log', 'solution' => 'RingCentral', 'direction' => 'source'],
            ['name' => 'message-store', 'nameKey' => 'Messages', 'solution' => 'RingCentral', 'direction' => 'source'],
            ['name' => 'presence', 'nameKey' => 'Presence', 'solution' => 'RingCentral', 'direction' => 'source'],
            ['name' => 'contacts', 'nameKey' => 'Contacts', 'solution' => 'Sendinblue', 'direction' => 'source'],
            ['name' => 'transactionalEmails', 'nameKey' => 'Transactional emails', 'solution' => 'Sendinblue', 'direction' => 'source'],
            ['name' => 'transactionalEmailActivity', 'nameKey' => 'Transactional email activity', 'solution' => 'Sendinblue', 'direction' => 'source'],
            ['name' => 'customers', 'nameKey' => 'Customers', 'solution' => 'WooCommerce', 'direction' => 'source'],
            ['name' => 'orders', 'nameKey' => 'Orders', 'solution' => 'WooCommerce', 'direction' => 'source'],
            ['name' => 'products', 'nameKey' => 'Products', 'solution' => 'WooCommerce', 'direction' => 'source'],
            ['name' => 'line_items', 'nameKey' => 'Line Items', 'solution' => 'WooCommerce', 'direction' => 'source'],
            ['name' => 'mep_cat', 'nameKey' => 'Categories', 'solution' => 'WooEventManager', 'direction' => 'source'],
            ['name' => 'mep_events', 'nameKey' => 'Events', 'solution' => 'WooEventManager', 'direction' => 'source'],
            ['name' => 'mep_event_more_date', 'nameKey' => 'Event More Date', 'solution' => 'WooEventManager', 'direction' => 'source'],
            ['name' => 'mep_org', 'nameKey' => 'Organizers', 'solution' => 'WooEventManager', 'direction' => 'source'],
            ['name' => 'posts', 'nameKey' => 'Posts', 'solution' => 'WordPress', 'direction' => 'source'],
            ['name' => 'pages', 'nameKey' => 'Pages', 'solution' => 'WordPress', 'direction' => 'source'],
            ['name' => 'comments', 'nameKey' => 'Comments', 'solution' => 'WordPress', 'direction' => 'source'],
        ];
    }

    public function getDependencies()
    {
        return [
            SolutionFixtures::class,
        ];
    }
}
