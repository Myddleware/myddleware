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

namespace App\Solutions;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Filesystem\Filesystem;

class suitecrm extends solution
{
    protected int $limitCall = 100;
    protected string $urlSuffix = '/service/v4_1/rest.php';

    protected ?array $cachedSession = null;
    protected ?int $sessionCacheTime = null;
    protected int $sessionCacheTTL = 300; // 5 minutes in seconds (reduced from 20 minutes because SuiteCRM invalidates sessions faster)
    protected ?string $cookieFilePath = null;
    // Enable to read deletion and to delete data
    protected bool $readDeletion = true;
    protected bool $sendDeletion = true;

    protected array $required_fields = ['default' => ['id', 'date_modified', 'date_entered']];

    protected array $FieldsDuplicate = ['Contacts' => ['email1', 'last_name'],
        'Accounts' => ['email1', 'name'],
        'Users' => ['email1', 'last_name'],
        'Leads' => ['email1', 'last_name'],
        'Prospects' => ['email1', 'name'],
        'default' => ['name'],
    ];

    protected $required_relationships = [
        'default' => [],
        'Contacts' => [],
        'Cases' => [],
    ];

    // liste des modules à exclure pour chaque solution
    protected array $exclude_module_list = [
        'default' => ['Home', 'Calendar', 'Documents', 'Administration', 'Currencies', 'CustomFields', 'Connectors', 'Dropdown', 'Dynamic', 'DynamicFields', 'DynamicLayout', 'EditCustomFields', 'Help', 'Import', 'MySettings', 'FieldsMetaData', 'UpgradeWizard', 'Sync', 'Versions', 'LabelEditor', 'Roles', 'OptimisticLock', 'TeamMemberships', 'TeamSets', 'TeamSetModule', 'Audit', 'MailMerge', 'MergeRecords', 'Schedulers', 'Schedulers_jobs', 'Groups', 'InboundEmail', 'ACLActions', 'ACLRoles', 'DocumentRevisions', 'ACL', 'Configurator', 'UserPreferences', 'SavedSearch', 'Studio', 'SugarFeed', 'EAPM', 'OAuthKeys', 'OAuthTokens'],
        'target' => [],
        'source' => [],
    ];

    protected array $exclude_field_list = [
        'default' => ['date_entered', 'date_modified', 'created_by_name', 'modified_by_name', 'created_by', 'modified_user_id'],
        'Contacts' => ['c_accept_status_fields', 'm_accept_status_fields', 'accept_status_id', 'accept_status_name', 'opportunity_role_fields', 'opportunity_role_id', 'opportunity_role', 'email'],
        'Leads' => ['email'],
        'Accounts' => ['email'],
        'Cases' => ['case_number'],
    ];

    // Tableau représentant les relation many-to-many de Sugar
    protected array $module_relationship_many_to_many = [
        'calls_contacts' => ['label' => 'Relationship Call Contact', 'module_name' => 'Calls', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['call_id', 'contact_id']],
        'calls_users' => ['label' => 'Relationship Call User', 'module_name' => 'Calls', 'link_field_name' => 'users', 'fields' => [], 'relationships' => ['call_id', 'user_id']],
        'calls_leads' => ['label' => 'Relationship Call Lead', 'module_name' => 'Calls', 'link_field_name' => 'leads', 'fields' => [], 'relationships' => ['call_id', 'lead_id']],
        'cases_bugs' => ['label' => 'Relationship Case Bug', 'module_name' => 'Cases', 'link_field_name' => 'bugs', 'fields' => [], 'relationships' => ['case_id', 'bug_id']],
        'contacts_bugs' => ['label' => 'Relationship Contact Bug', 'module_name' => 'Contacts', 'link_field_name' => 'bugs', 'fields' => [], 'relationships' => ['contact_id', 'bug_id']],
        'contacts_cases' => ['label' => 'Relationship Contact Case', 'module_name' => 'Contacts', 'link_field_name' => 'cases', 'fields' => [], 'relationships' => ['contact_id', 'case_id']],
        'meetings_contacts' => ['label' => 'Relationship Metting Contact', 'module_name' => 'Meetings', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['meeting_id', 'contact_id']],
        'meetings_users' => ['label' => 'Relationship Meeting User', 'module_name' => 'Meetings', 'link_field_name' => 'users', 'fields' => [], 'relationships' => ['meeting_id', 'user_id']],
        'meetings_leads' => ['label' => 'Relationship Meeting Lead', 'module_name' => 'Meetings', 'link_field_name' => 'leads', 'fields' => [], 'relationships' => ['meeting_id', 'lead_id']],
        'opportunities_contacts' => ['label' => 'Relationship Opportunity Contact', 'module_name' => 'Opportunities', 'link_field_name' => 'contacts', 'fields' => ['contact_role'], 'relationships' => ['opportunity_id', 'contact_id']], // contact_role exist in opportunities vardef for module contact (entry rel_fields)
        'prospect_list_campaigns' => ['label' => 'Relationship Prospect_list Campaign', 'module_name' => 'ProspectLists', 'link_field_name' => 'campaigns', 'fields' => [], 'relationships' => ['prospect_list_id', 'campaign_id']],
        'prospect_list_contacts' => ['label' => 'Relationship Prospect_list Contact', 'module_name' => 'ProspectLists', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['prospect_list_id', 'contact_id']],
        'prospect_list_prospects' => ['label' => 'Relationship Prospect_list Prospect', 'module_name' => 'ProspectLists', 'link_field_name' => 'prospects', 'fields' => [], 'relationships' => ['prospect_list_id', 'Prospect_id']],
        'prospect_list_leads' => ['label' => 'Relationship Prospect_list Lead', 'module_name' => 'ProspectLists', 'link_field_name' => 'leads', 'fields' => [], 'relationships' => ['prospect_list_id', 'lead_id']],
        'prospect_list_users' => ['label' => 'Relationship Prospect_list User', 'module_name' => 'ProspectLists', 'link_field_name' => 'users', 'fields' => [], 'relationships' => ['prospect_list_id', 'user_id']],
        'prospect_list_accounts' => ['label' => 'Relationship Prospect_list Account', 'module_name' => 'ProspectLists', 'link_field_name' => 'accounts', 'fields' => [], 'relationships' => ['prospect_list_id', 'account_id']],
        'projects_bugs' => ['label' => 'Relationship Project Bug', 'module_name' => 'Projects', 'link_field_name' => 'bugs', 'fields' => [], 'relationships' => ['project_id', 'bug_id']],
        'projects_cases' => ['label' => 'Relationship Project Case', 'module_name' => 'Projects', 'link_field_name' => 'cases', 'fields' => [], 'relationships' => ['project_id', 'case_id']],
        'projects_accounts' => ['label' => 'Relationship Project Account', 'module_name' => 'Projects', 'link_field_name' => 'accounts', 'fields' => [], 'relationships' => ['project_id', 'account_id']],
        'projects_contacts' => ['label' => 'Relationship Project Contact', 'module_name' => 'Projects', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['project_id', 'contact_id']],
        'projects_opportunities' => ['label' => 'Relationship Project Opportunity', 'module_name' => 'Projects', 'link_field_name' => 'opportunities', 'fields' => [], 'relationships' => ['project_id', 'opportunity_id']],
        'email_marketing_prospect_lists' => ['label' => 'Relationship Email_marketing Prospect_list', 'module_name' => 'EmailMarketing', 'link_field_name' => 'prospect_lists', 'fields' => [], 'relationships' => ['email_marketing_id', 'prospect_list_id']],
        'leads_documents' => ['label' => 'Relationship Lead Document', 'module_name' => 'Leads', 'link_field_name' => 'documents', 'fields' => [], 'relationships' => ['lead_id', 'document_id']],
        'documents_accounts' => ['label' => 'Relationship Document Account', 'module_name' => 'Documents', 'link_field_name' => 'accounts', 'fields' => [], 'relationships' => ['document_id', 'account_id']],
        'documents_contacts' => ['label' => 'Relationship Document Contact', 'module_name' => 'Documents', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['document_id', 'contact_id']],
        'documents_opportunities' => ['label' => 'Relationship Document Opportunity', 'module_name' => 'Documents', 'link_field_name' => 'opportunities', 'fields' => [], 'relationships' => ['document_id', 'opportunity_id']],
        'documents_cases' => ['label' => 'Relationship Document Case', 'module_name' => 'Documents', 'link_field_name' => 'cases', 'fields' => [], 'relationships' => ['document_id', 'case_id']],
        'documents_bugs' => ['label' => 'Relationship Document Bug', 'module_name' => 'Documents', 'link_field_name' => 'bugs', 'fields' => [], 'relationships' => ['document_id', 'bug_id']],
        'aos_quotes_aos_invoices' => ['label' => 'Relationship Quote Invoice', 'module_name' => 'AOS_Quotes', 'link_field_name' => 'aos_quotes_aos_invoices', 'fields' => [], 'relationships' => ['aos_quotes77d9_quotes_ida', 'aos_quotes6b83nvoices_idb']],
        'fp_events_contacts' => ['label' => 'Relationship Event Contact', 'module_name' => 'FP_events', 'link_field_name' => 'fp_events_contacts', 'fields' => ['accept_status', 'invite_status'], 'relationships' => ['fp_events_contactsfp_events_ida', 'fp_events_contactscontacts_idb']],
        'fp_events_leads_1' => ['label' => 'Relationship Event Lead', 'module_name' => 'FP_events', 'link_field_name' => 'fp_events_leads_1', 'fields' => ['accept_status', 'invite_status'], 'relationships' => ['fp_events_leads_1fp_events_ida', 'fp_events_leads_1leads_idb']],
        'fp_events_prospects_1' => ['label' => 'Relationship Event Prospect', 'module_name' => 'FP_events', 'link_field_name' => 'fp_events_prospects_1', 'fields' => ['accept_status', 'invite_status'], 'relationships' => ['fp_events_prospects_1fp_events_ida', 'fp_events_prospects_1prospects_idb']],
    ];

    protected string $customRelationship = 'MydCustRelSugar';

    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            $this->logger->critical("starting the login process");
            $this->logger->critical("login credentials - user: " . $this->paramConnexion['login'] . ", url: " . $this->paramConnexion['url']);

            // Initialize cookie file path based on credentials
            $this->logger->critical("calling initializeCookieFile");
            $this->initializeCookieFile();
            $this->logger->critical("initializeCookieFile completed, cookieFilePath: " . ($this->cookieFilePath ?? 'null'));

            // Generate cache key based on login credentials and URL
            $cacheKey = md5($this->paramConnexion['login'] . $this->paramConnexion['password'] . $this->paramConnexion['url']);
            $this->logger->critical("generated cache key: " . $cacheKey);

            $originalUrl = $this->paramConnexion['url'];
            $this->paramConnexion['url'] = str_replace('index.php', '', $this->paramConnexion['url']);
            $this->logger->critical("removed index.php from URL - before: " . $originalUrl . ", after: " . $this->paramConnexion['url']);

            $this->paramConnexion['url'] .= $this->urlSuffix;
            $this->logger->critical("added urlSuffix to URL - final URL: " . $this->paramConnexion['url']);

            $this->logger->critical("checking if cache is valid for cache key: " . $cacheKey);
            if ($this->isCacheValid($cacheKey)) {
                $this->session = $this->cachedSession['session_id'];
                $this->logger->critical("cache is valid, skipping login - session ID: " . $this->session);
                $this->connexion_valide = true;
                $this->logger->critical("connexion_valide set to true, returning early");
                return;
            } else {
                $this->logger->critical("cache is NOT valid, proceeding with full login");
            }

            $this->logger->critical("preparing login parameters");
            $login_paramaters = [
                'user_auth' => [
                    'user_name' => $this->paramConnexion['login'],
                    'password' => md5($this->paramConnexion['password']),
                    'version' => '.01',
                ],
                'application_name' => 'myddleware',
            ];
            $this->logger->critical("login parameters prepared - user_name: " . $this->paramConnexion['login']);

            $this->logger->critical("making login call to: " . $this->paramConnexion['url']);
            $result = $this->call('login', $login_paramaters, $this->paramConnexion['url']);
            $this->logger->critical("login call completed, result is: " . ($result === false ? 'FALSE' : 'not false'));

            if (false != $result) {
                $this->logger->critical("result is not false, checking for result->id");
                if (empty($result->id)) {
                    $this->logger->critical("result->id is EMPTY - error description: " . ($result->description ?? 'no description'));
                    throw new \Exception($result->description);
                } else {
                    $this->logger->critical("result->id is present: " . $result->id);
                }

                $this->session = $result->id;
                $this->logger->critical("session set to: " . $this->session);

                // Cache the session
                $this->logger->critical("caching session with cache key: " . $cacheKey);
                $this->cacheSession($cacheKey, $result->id);
                $this->logger->critical("session cached successfully");

                $this->connexion_valide = true;
                $this->logger->critical("connexion_valide set to true - login successful");
            } else {
                $this->logger->critical("result is FALSE - URL check failed");
                throw new \Exception('Please check url');
            }
        } catch (\Exception $e) {
            $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->critical("EXCEPTION caught in login - " . $error);
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    public function logout(): bool
    {
        try {
            $logout_parameters = ['session' => $this->session];
            $this->call('logout', $logout_parameters, $this->paramConnexion['url']);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error logout REST '.$e->getMessage());

            return false;
        }
    }

    /**
     * Clear the cookie file
     */
    protected function clearCookieFile(): void
    {
        $this->logger->critical("clearCookieFile called");
        if ($this->cookieFilePath !== null && file_exists($this->cookieFilePath)) {
            $this->logger->critical("cookie file exists and path is not null - path: " . $this->cookieFilePath);
            $fs = new Filesystem();
            $this->logger->critical("removing cookie file");
            $fs->remove($this->cookieFilePath);
            $this->logger->critical("cookie file removed successfully");
            $this->cookieFilePath = null;
            $this->logger->critical("cookieFilePath set to null");
        } else {
            $this->logger->critical("cookie file does not exist or path is null - path: " . ($this->cookieFilePath ?? 'null') . ", file_exists: " . ($this->cookieFilePath !== null && file_exists($this->cookieFilePath) ? 'true' : 'false'));
        }
    }

    /**
     * Invalidate the current session cache
     */
    protected function invalidateSession(): void
    {
        $this->logger->critical("invalidateSession called - clearing all session data");
        $this->cachedSession = null;
        $this->sessionCacheTime = null;
        $this->session = null;
        $this->clearCookieFile();
        $this->logger->critical("session invalidated - all data cleared");
    }

    /**
     * Initialize the cookie file path for this session
     */
    protected function initializeCookieFile(): void
    {
        $this->logger->critical("initializeCookieFile called - cookieFilePath: " . ($this->cookieFilePath ?? 'null'));
        if ($this->cookieFilePath !== null) {
            $this->logger->critical("cookieFilePath is already initialized, returning early - path: " . $this->cookieFilePath);
            return; // Already initialized
        } else {
            $this->logger->critical("cookieFilePath is null, proceeding with initialization");
        }

        $cacheKey = md5($this->paramConnexion['login'] . $this->paramConnexion['password'] . $this->paramConnexion['url']);
        $this->logger->critical("generated cache key for cookie file: " . $cacheKey);

        $cookieDir = $this->parameterBagInterface->get('kernel.cache_dir') . '/myddleware/solutions/suitecrm';
        $this->logger->critical("cookie directory: " . $cookieDir);

        $this->cookieFilePath = $cookieDir . '/cookies_' . $cacheKey . '.txt';
        $this->logger->critical("cookie file path set to: " . $this->cookieFilePath);

        // Create directory if it doesn't exist
        $fs = new Filesystem();
        $this->logger->critical("creating cookie directory if it doesn't exist");
        $fs->mkdir($cookieDir);
        $this->logger->critical("cookie directory created/verified");
    }

    /**
     * Cache the session ID with current timestamp
     */
    protected function cacheSession(string $cacheKey, string $sessionId): void
    {
        $this->logger->critical("cacheSession called - cacheKey: " . $cacheKey . ", sessionId: " . $sessionId);

        $this->cachedSession = [
            'cache_key' => $cacheKey,
            'session_id' => $sessionId,
        ];
        $this->logger->critical("cachedSession array set with cache_key and session_id");

        $this->sessionCacheTime = time(); // current moment
        $this->logger->critical("sessionCacheTime set to: " . $this->sessionCacheTime);
    }

    /**
     * Check if cached session is still valid (not expired)
     */
    protected function isCacheValid(string $cacheKey): bool
    {
        $this->logger->critical("isCacheValid called - cacheKey: " . $cacheKey);
        $this->logger->critical("cachedSession is: " . ($this->cachedSession === null ? 'NULL' : 'not null'));
        $this->logger->critical("sessionCacheTime is: " . ($this->sessionCacheTime === null ? 'NULL' : $this->sessionCacheTime));

        // If class variables are null, check if the session file exists on disk
        if ($this->cachedSession === null || $this->sessionCacheTime === null) {
            $this->logger->critical("cachedSession or sessionCacheTime is null, checking disk for session file");
            $cookieFilePath = $this->parameterBagInterface->get('kernel.cache_dir') . '/myddleware/solutions/suitecrm/cookies_' . $cacheKey . '.txt';
            $this->logger->critical("checking for cookie file at path: " . $cookieFilePath);

            if (file_exists($cookieFilePath)) {
                $this->logger->critical("cookie file EXISTS on disk");
                // Session file exists, extract session ID from cookie file
                $this->logger->critical("calling extractSessionIdFromCookieFile");
                $sessionId = $this->extractSessionIdFromCookieFile($cookieFilePath);
                $this->logger->critical("extractSessionIdFromCookieFile returned: " . ($sessionId ?? 'null'));

                if (empty($sessionId)) {
                    $this->logger->critical("sessionId is EMPTY, returning false");
                    return false;
                } else {
                    $this->logger->critical("sessionId is NOT empty, loading into class variables");
                }

                // Load it into class variables and validate
                $this->cookieFilePath = $cookieFilePath;
                $this->logger->critical("cookieFilePath set to: " . $this->cookieFilePath);

                $this->cachedSession = ['cache_key' => $cacheKey, 'session_id' => $sessionId];
                $this->logger->critical("cachedSession array created with cache_key and session_id");

                $this->sessionCacheTime = filemtime($cookieFilePath);
                $this->logger->critical("session file found on disk, loading from file - sessionCacheTime: " . $this->sessionCacheTime);
            } else {
                $this->logger->critical("cookie file does NOT exist on disk, returning false - cache not used");
                return false; // cache not used
            }
        } else {
            $this->logger->critical("cachedSession and sessionCacheTime are already set, proceeding with validation");
        }

        // Check if cache key matches
        $this->logger->critical("checking if cache key matches - stored: " . ($this->cachedSession['cache_key'] ?? 'null') . ", provided: " . $cacheKey);
        if ($this->cachedSession['cache_key'] !== $cacheKey) {
            $this->logger->critical("cache key MISMATCH - wrong credentials");
            $this->clearCookieFile(); // because the cache wrong or corrupted, clear the file
            $this->logger->critical("returning false - cache created but for different credentials");
            return false; // cache created but for different credentials
        } else {
            $this->logger->critical("cache key MATCHES - proceeding");
        }

        // Check if cache has expired (TTL exceeded)
        $currentTime = time();
        $this->logger->critical("checking cache expiration - currentTime: " . $currentTime . ", sessionCacheTime: " . $this->sessionCacheTime);
        $cacheAge = $currentTime - $this->sessionCacheTime;
        $this->logger->critical("cache age calculated: " . $cacheAge . " seconds, TTL: " . $this->sessionCacheTTL . " seconds");

        if ($cacheAge > $this->sessionCacheTTL) { // if cache is too old because its age is superior to TTL
            $this->logger->critical("cache age EXCEEDS TTL - cache is expired");
            $this->cachedSession = null;
            $this->logger->critical("cachedSession set to null");
            $this->sessionCacheTime = null;
            $this->logger->critical("sessionCacheTime set to null");
            $this->clearCookieFile(); // because the cache is too old, empty the file
            $this->logger->critical("expired cache - returning false");
            return false;
        } else {
            $this->logger->critical("cache age is within TTL - cache is NOT expired");
        }

        // Check if the session file still exists
        $this->logger->critical("checking if session file still exists - path: " . ($this->cookieFilePath ?? 'null'));
        if ($this->cookieFilePath === null || !file_exists($this->cookieFilePath)) {
            $this->logger->critical("session file does NOT exist or path is null");
            $this->cachedSession = null;
            $this->logger->critical("cachedSession set to null");
            $this->sessionCacheTime = null;
            $this->logger->critical("sessionCacheTime set to null");
            $this->logger->critical("session file not found - returning false");
            return false;
        } else {
            $this->logger->critical("session file still EXISTS");
        }

        $this->logger->critical("ALL VALIDATION CHECKS PASSED - cache is valid, returning true");
        return true;
    }

    /**
     * Extract session ID from the Netscape cookie file
     */
    protected function extractSessionIdFromCookieFile(string $filePath): ?string
    {
        $this->logger->critical("extractSessionIdFromCookieFile called - filePath: " . $filePath);

        if (!file_exists($filePath)) {
            $this->logger->critical("file does NOT exist, returning null");
            return null;
        } else {
            $this->logger->critical("file exists, proceeding to read");
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->logger->critical("read file lines - count: " . (is_array($lines) ? count($lines) : 0));

        if (empty($lines)) {
            $this->logger->critical("lines array is EMPTY, returning null");
            return null;
        } else {
            $this->logger->critical("lines array is NOT empty, proceeding to parse");
        }

        $lineNumber = 0;
        foreach ($lines as $line) {
            $lineNumber++;
            $this->logger->critical("processing line #" . $lineNumber . ": " . substr($line, 0, 100));

            // Skip comment lines
            if (strpos($line, 'LEGACYSESSID') === 0) {
                $this->logger->critical("line starts with LEGACYSESSID (comment line), skipping");
                continue;
            } else {
                $this->logger->critical("line does NOT start with LEGACYSESSID, parsing as cookie");
            }

            // Parse Netscape cookie format: domain, flag, path, secure, expiration, name, value
            $parts = preg_split('/\t+/', trim($line));
            $this->logger->critical("split line into parts - count: " . count($parts));

            if (count($parts) >= 7) {
                $this->logger->critical("line has >= 7 parts, extracting cookie name and value");
                $cookieName = $parts[5];
                $cookieValue = $parts[6];
                $this->logger->critical("cookieName: " . $cookieName . ", cookieValue: " . substr($cookieValue, 0, 50));

                // Look for LEGACYSESSID cookie
                if ($cookieName === 'LEGACYSESSID') {
                    $this->logger->critical("FOUND LEGACYSESSID cookie, returning value: " . $cookieValue);
                    return $cookieValue;
                } else {
                    $this->logger->critical("cookieName is NOT LEGACYSESSID, continuing to next line");
                }
            } else {
                $this->logger->critical("line has LESS than 7 parts, skipping to next line");
            }
        }

        $this->logger->critical("finished processing all lines, LEGACYSESSID not found, returning null");
        return null;
    }

    public function getFieldsLogin(): array
    {
        return [
            [
                'name' => 'login',
                'type' => TextType::class,
                'label' => 'solution.fields.login',
            ],
            [
                'name' => 'password',
                'type' => PasswordType::class,
                'label' => 'solution.fields.password',
            ],
            [
                'name' => 'url',
                'type' => TextType::class,
                'label' => 'solution.fields.url',
            ],
        ];
    }

    // Permet de récupérer tous les modules accessibles à l'utilisateur
    public function get_modules($type = 'source')
    {
        try {
            $get_available_modules_parameters = [
                'session' => $this->session,
            ];
            $get_available_modules = $this->call('get_available_modules', $get_available_modules_parameters);
            if (!empty($get_available_modules->modules)) {
                foreach ($get_available_modules->modules as $module) {
                    // On ne renvoie que les modules autorisés
                    if (
                            !in_array($module->module_key, $this->exclude_module_list['default'])
                        && !in_array($module->module_key, $this->exclude_module_list[$type])
                    ) {
                        $modules[$module->module_key] = $module->module_label;
                    }
                }
            }
            // Création des modules type relationship
            if (!empty($this->module_relationship_many_to_many)) {
                foreach ($this->module_relationship_many_to_many as $key => $value) {
                    $modules[$key] = $value['label'];
                }
            }

            return (isset($modules)) ? $modules : false;
        } catch (\Exception $e) {
            return false;
        }
    }

    // Permet de récupérer tous les champs d'un module
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
            // Si le module est un module "fictif" relation créé pour Myddlewar
            if (array_key_exists($module, $this->module_relationship_many_to_many)) {
                foreach ($this->module_relationship_many_to_many[$module]['fields'] as $name) {
                    $this->moduleFields[$name] = [
                        'label' => $name,
                        'type' => 'varchar(255)',
                        'type_bdd' => 'varchar(255)',
                        'required' => 0,
                        'relate' => false,
                    ];
                }
                foreach ($this->module_relationship_many_to_many[$module]['relationships'] as $relationship) {
                    $this->moduleFields[$relationship] = [
                        'label' => $relationship,
                        'type' => 'varchar(36)',
                        'type_bdd' => 'varchar(36)',
                        'required' => 0,
                        'required_relationship' => 0,
                        'relate' => true,
                    ];
                }
            } else {
                $get_module_fields_parameters = [
                    'session' => $this->session,
                    'module_name' => $module,
                ];

                $get_module_fields = $this->call('get_module_fields', $get_module_fields_parameters);
                foreach ($get_module_fields->module_fields as $field) {
                    if (isset($this->exclude_field_list['default'])) {
                        // Certains champs ne peuvent pas être modifiés
                        if (in_array($field->name, $this->exclude_field_list['default']) && 'target' == $type) {
                            continue;
                        } // Ces champs doivent être exclus de la liste des modules pour des raisons de structure de BD SuiteCRM
                    }

                    if (!in_array($field->type, $this->type_valide)) {
                        if (isset($this->exclude_field_list[$module])) {
                            if (in_array($field->name, $this->exclude_field_list[$module]) && 'target' == $type) {
                                continue;
                            } // Ces champs doivent être exclus de la liste des modules pour des raisons de structure de BD SuiteCRM
                        }
                        $type_bdd = 'varchar(255)';
                    } else {
                        $type_bdd = $field->type;
                    }
                    if (
                            '_id' == substr($field->name, -3)
                        || '_ida' == substr($field->name, -4)
                        || '_idb' == substr($field->name, -4)
                        || '_id_c' == substr($field->name, -5)
                        || (
                                'id' == $field->type
                            && 'id' != $field->name
                        )
                        || 'created_by' == $field->name
                    ) {
                        $this->moduleFields[$field->name] = [
                            'label' => $field->label,
                            'type' => 'varchar(36)',
                            'type_bdd' => 'varchar(36)',
                            'required' => $field->required,
                            'required_relationship' => 0,
                            'relate' => true,
                        ];
                    }
                    //To enable to take out all fields where there are 'relate' in the type of the field
                    else {
                        // Le champ id n'est envoyé qu'en source
                        if ('id' != $field->name || 'source' == $type) {
                            $this->moduleFields[$field->name] = [
                                'label' => $field->label,
                                'type' => $field->type,
                                'type_bdd' => $type_bdd,
                                'required' => $field->required,
                                'relate' => false,
                            ];
                        }
                        // Récupération des listes déroulantes (sauf si datetime pour SuiteCRM)
                        if (
                                !empty($field->options)
                            && !in_array($field->type, ['datetime', 'bool'])
                        ) {
                            foreach ($field->options as $option) {
                                $this->moduleFields[$field->name]['option'][$option->name] = parent::truncate($option->value, 80);
                            }
                        }
                    }
                }
                // Ajout des champ type link (custom relationship ou custom module souvent)
                if (!empty($get_module_fields->link_fields)) {
                    foreach ($get_module_fields->link_fields as $field) {
                        if (isset($this->exclude_field_list['default'])) {
                            if (in_array($field->name, $this->exclude_field_list['default']) && 'target' == $type) {
                                continue;
                            } // Ces champs doivent être exclus en écriture de la liste des modules pour des raisons de structure de BD SuiteCRM
                        }
                        if (!in_array($field->type, $this->type_valide)) {
                            if (isset($this->exclude_field_list[$module])) {
                                if (in_array($field->name, $this->exclude_field_list[$module]) && 'target' == $type) {
                                    continue;
                                } // Ces champs doivent être exclus en écriture de la liste des modules pour des raisons de structure de BD SuiteCRM
                            }
                            $type_bdd = 'varchar(255)';
                        } else {
                            $type_bdd = $field->type;
                        }
                        if (
                                '_id' == substr($field->name, -3)
                            || '_ida' == substr($field->name, -4)
                            || '_idb' == substr($field->name, -4)
                            || '_id_c' == substr($field->name, -5)
                            || (
                                    'id' == $field->type
                                && 'id' != $field->name
                            )
                        ) {
                            // On met un préfix pour les relation custom afin de pouvoir les détecter dans le read
                            $this->moduleFields[$this->customRelationship.$field->name] = [
                                'label' => $field->relationship,
                                'type' => 'varchar(36)',
                                'type_bdd' => 'varchar(36)',
                                'required' => 0,
                                'required_relationship' => 0,
                                'relate' => true,
                            ];
                            // Get the name field for this relationship (already in array moduleFields but we need to flag it as a customrelationship)
                            if (!empty($this->moduleFields[$field->relationship.'_name'])) {
                                // Create the field with prefix
                                $this->moduleFields[$this->customRelationship.$field->relationship.'_name'] = $this->moduleFields[$field->relationship.'_name'];
                                // Remove the old field
                                unset($this->moduleFields[$field->relationship.'_name']);
                            }
                        }
                    }
                }
            }
			// Add field filecontents for notes module
			if ($module == 'Notes') {
				$this->moduleFields['filecontents'] = [
					'label' => 'File contents',
					'type' => 'text',
					'type_bdd' => 'text',
					'required' => 0,
					'required_relationship' => 0,
					'relate' => false,
				];
			}
            return $this->moduleFields;
        } catch (\Exception $e) {
            return [];
        }
    }

    

    // Permet de lire les données

    /**
     * @throws \Exception
     */
    public function read($param)
    {
        $this->logger->critical("read() method called - module: " . ($param['module'] ?? 'unknown'));
        $this->logger->critical("read() - current session ID: " . ($this->session ?? 'null'));
        $this->logger->critical("read() - query params: " . json_encode($param['query'] ?? 'no query'));

        $result = [];

        // Manage delete option to enable
        $deleted = false;
        if (!empty($param['ruleParams']['deletion'])) {
            $this->logger->critical("read() - deletion enabled, adding deleted field");
            $deleted = true;
            $param['fields'][] = 'deleted';
        } else {
            $this->logger->critical("read() - deletion NOT enabled");
        }

        $totalCount = 0;
        $currentCount = 0;
        $query = '';

        // On va chercher le nom du champ pour la date de référence: Création ou Modification
        $this->logger->critical("read() - getting reference field name");
        $dateRefField = $this->getRefFieldName($param);
        $this->logger->critical("read() - reference field: " . $dateRefField);

        // Si le module est un module "fictif" relation créé pour Myddlewar	alors on récupère tous les enregistrements du module parent modifié
        if (array_key_exists($param['module'], $this->module_relationship_many_to_many)) {
            $this->logger->critical("read() - module is many-to-many relationship, adjusting params");
            $paramSave = $param;
            $param['fields'] = [];
            $param['module'] = $this->module_relationship_many_to_many[$paramSave['module']]['module_name'];
            $this->logger->critical("read() - adjusted module to: " . $param['module']);
        } else {
            $this->logger->critical("read() - module is NOT many-to-many");
        }

        // Built the query
        $this->logger->critical("read() - generating query");
        $query = $this->generateQuery($param, 'read');
        $this->logger->critical("read() - query generated: " . $query);
        //Pour tous les champs, si un correspond à une relation custom alors on change le tableau en entrée
        $link_name_to_fields_array = [];
        foreach ($param['fields'] as $field) {
            if (substr($field, 0, strlen($this->customRelationship)) == $this->customRelationship) {
                // Get all custom relationships
                if (empty($customRelationshipList)) {
                    $customRelationshipListFields = $this->getCustomRelationshipListFields($param['module']);
                }
                // Get the relationship name for all custom relationship field (coudb be id field or name field)
                // Search the field in the array
                if (!empty($customRelationshipListFields)) {
                    foreach ($customRelationshipListFields as $key => $value) {
                        // If a request field (name or id) is a custom relationship then we add the entry in array link_name_to_fields_array
                        if (
                                $value['id'] == $field
                            or $value['name'] == $field
                        ) {
                            $link_name_to_fields_array[] = ['name' => $key, 'value' => ['id', 'name']];
                            break;
                        }
                    }
                }
            }
        }

        // add limit to query
        if (!empty($param['limit'])) {
            $this->logger->critical("read() - setting limit to: " . $param['limit']);
            $this->limitCall = $param['limit'];
        } else {
            $this->logger->critical("read() - using default limit: " . $this->limitCall);
        }

        // On lit les données dans le CRM
        $this->logger->critical("read() - starting do-while loop to read data from CRM");
        do {
            $this->logger->critical("read() - preparing get_entry_list parameters - offset: " . ($param['offset'] ?? 0));
            $get_entry_list_parameters = [
                'session' => $this->session,
                'module_name' => $param['module'],
                'query' => $query,
                'order_by' => $dateRefField.' ASC',
                'offset' => $param['offset'],
                'select_fields' => $param['fields'],
                'link_name_to_fields_array' => $link_name_to_fields_array,
                'max_results' => $this->limitCall,
                'deleted' => $deleted,
            ];
            $this->logger->critical("read() - calling API get_entry_list with session: " . $this->session . ", module: " . $param['module']);
            $get_entry_list_result = $this->call('get_entry_list', $get_entry_list_parameters);
            $this->logger->critical("read() - get_entry_list call completed");

            // Construction des données de sortie
            $this->logger->critical("read() - checking if result has result_count");
            if (isset($get_entry_list_result->result_count)) {
                $this->logger->critical("read() - result_count is SET: " . $get_entry_list_result->result_count);
                $currentCount = $get_entry_list_result->result_count;
                $totalCount += $currentCount;
                $record = [];
                $i = 0;
                // For each records, we add all fields requested
                for ($i = 0; $i < $currentCount; ++$i) {
                    $entry = $get_entry_list_result->entry_list[$i];
                    foreach ($entry->name_value_list as $value) {
                        $record[$value->name] = $value->value;
                    }
                    // Manage deletion by adding the flag Myddleware_deletion to the record
                    if (
                            true == $deleted
                        and !empty($entry->name_value_list->deleted->value)
                    ) {
                        $record['myddleware_deletion'] = true;
                    }

                    // All custom relationships will be added even the ones no requested (Myddleware will ignore them later)
                    if (!empty($customRelationshipListFields)) {
                        // For each fields requested corresponding to a custom relationship
                        foreach ($param['fields'] as $field) {
                            // Check if the field is a custom relationship
                            foreach ($customRelationshipListFields as $key => $value) {
                                if (
                                        $field == $value['id']
                                    or $field == $value['name']
                                ) {
                                    // Init field even if the relationship is empty. Myddleware needs the field to be set
                                    $record[$value['id']] = '';
                                    $record[$value['name']] = '';

                                    // Find the the right relationship into SuiteCRM result call
                                    foreach ($get_entry_list_result->relationship_list[$i]->link_list as $relationship) {
                                        if (
                                                !empty($relationship->name)
                                            and $relationship->name == $key
                                        ) {
                                            // Save relationship values
                                            if (!empty($relationship->records[0]->link_value->id->value)) {
                                                $record[$value['id']] = $relationship->records[0]->link_value->id->value;
                                                $record[$value['name']] = $relationship->records[0]->link_value->name->value;
                                            }
                                            break 2; // Go to the next field
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $result[] = $record;
                    $record = [];
                }
                // Préparation l'offset dans le cas où on fera un nouvel appel à Salesforce
                $this->logger->critical("read() - incrementing offset by: " . $this->limitCall);
                $param['offset'] += $this->limitCall;
                $this->logger->critical("read() - new offset: " . $param['offset']);
            } else {
                $this->logger->critical("read() - result_count is NOT set - ERROR occurred");
                $this->logger->critical("read() - full result object: " . json_encode($get_entry_list_result));
                if (!empty($get_entry_list_result->number)) {
                    $this->logger->critical("read() - error number is set: " . $get_entry_list_result->number);
                    $this->logger->critical("read() - error name: " . ($get_entry_list_result->name ?? 'no name'));
                    $this->logger->critical("read() - error description: " . ($get_entry_list_result->description ?? 'no description'));
                    $this->logger->critical("read() - THROWING EXCEPTION with error details");
                    // $result['error'] = $get_entry_list_result->number.' : '.$get_entry_list_result->name.'. '.$get_entry_list_result->description;
                    throw new \Exception($get_entry_list_result->number.' : '.$get_entry_list_result->name.'. '.$get_entry_list_result->description);
                } else {
                    $this->logger->critical("read() - error number is NOT set");
                    $this->logger->critical("read() - THROWING EXCEPTION - no error details from SuiteCRM");
                    // $result['error'] = 'Failed to read data from SuiteCRM. No error return by SuiteCRM';
                    throw new \Exception('Failed to read data from SuiteCRM. No error return by SuiteCRM');
                }
                break; // Stop the loop if an error happened
            }
        }
        // On continue si le nombre de résultat du dernier appel est égal à la limite
        while ($currentCount == $this->limitCall and $totalCount < $param['limit'] - 1); // -1 because a limit of 1000 = 1001 in the system
        $this->logger->critical("read() - do-while loop completed - totalCount: " . $totalCount);
        // Si on est sur un module relation, on récupère toutes les données liées à tous les module sparents modifiés
        if (!empty($paramSave)) {
            $this->logger->critical("read() - processing many-to-many relationship, calling readRelationship");
            $resultRel = $this->readRelationship($paramSave, $result);
            // Récupération des données sauf de la date de référence qui dépend des enregistrements parent
            if (!empty($resultRel['count'])) {
                $this->logger->critical("read() - readRelationship returned count: " . $resultRel['count']);
                $result = $resultRel['values'];
            }
            // Si aucun résultat dans les relations on renvoie null, sinon un flux vide serait créé.
            else {
                $this->logger->critical("read() - readRelationship returned NO results, returning null");
                return;
            }
        } else {
            $this->logger->critical("read() - NOT a many-to-many relationship");
        }

        $this->logger->critical("read() - returning result with " . (is_array($result) ? count($result) : 0) . " records");
        return $result;
    }

    // Build the direct link to the record (used in data transfer view)
    public function getDirectLink($rule, $document, $type): string
    {
        // Get url, module and record ID depending on the type
        if ('source' == $type) {
            $url = $this->getConnectorParam($rule->getConnectorSource(), 'url');
            $module = $rule->getModuleSource();
            $recordId = $document->getSource();
        } else {
            $url = $this->getConnectorParam($rule->getConnectorTarget(), 'url');
            $module = $rule->getModuleTarget();
            $recordId = $document->gettarget();
        }

        // Build the URL (delete if exists / to be sure to not have 2 / in a row)
        return rtrim($url, '/').'/#/'.strtolower($module).'/record/'.$recordId;
    }

    protected function readRelationship($param, $dataParent): array
    {
        if (empty($param['limit'])) {
            $param['limit'] = 100;
        }
        $result['error'] = '';
        $i = 0;
        // Pour toutes les données parents, on récupère toutes les données liées de la relation
        if (!empty($dataParent['values'])) {
            $module_relationship_many_to_many = $this->module_relationship_many_to_many[$param['module']];

            foreach ($dataParent['values'] as $parent) {
                $get_relationships_parameters = [
                    'session' => $this->session,
                    'module_name' => $module_relationship_many_to_many['module_name'],
                    'module_id' => $parent['id'],
                    'link_field_name' => $module_relationship_many_to_many['link_field_name'],
                    'related_module_query' => '',
                    'related_fields' => ['id'],
                    'related_module_link_name_to_fields_array' => [],
                    'deleted' => '0',
                    'order_by' => '',
                    'offset' => 0,
                    'limit' => $param['limit'],
                ];
                $get_entry_list_result = $this->call('get_relationships', $get_relationships_parameters);

                if (!empty($get_entry_list_result)) {
                    $record = [];
                    foreach ($get_entry_list_result->entry_list as $entry) {
                        // R2cupération de l'id parent
                        $record[$module_relationship_many_to_many['relationships'][0]] = $parent['id'];
                        foreach ($entry->name_value_list as $value) {
                            if ('id' == $value->name) {
                                $record[$module_relationship_many_to_many['relationships'][1]] = $value->value;
                            } else {
                                $record[$value->name] = $value->value;
                            }
                        }
                        // La date de référence de chaque relation est égale à la date de référence du parent
                        $record['date_modified'] = $parent['date_modified'];
                        // L'id de la relation est généré en concatenant les 2 id
                        $record['id'] = $record[$module_relationship_many_to_many['relationships'][0]].$record[$module_relationship_many_to_many['relationships'][1]];
                        $result['values'][$record['id']] = $record;
                        $record = [];
                        ++$i;
                    }
                } else {
                    $result['error'] .= $get_entry_list_result->number.' : '.$get_entry_list_result->name.'. '.$get_entry_list_result->description.'       ';
                }
            }
        }
        $result['count'] = $i;

        return $result;
    }


    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function createData($param): array
    {
        $this->logger->critical("createData() method called - module: " . ($param['module'] ?? 'unknown'));
        $this->logger->critical("createData() - data count: " . (isset($param['data']) ? count($param['data']) : 0));
        $this->logger->critical("createData() - current session ID: " . ($this->session ?? 'null'));

        // Si le module est un module "fictif" relation créé pour Myddlewar	alors on ne fait pas de readlast
        if (array_key_exists($param['module'], $this->module_relationship_many_to_many)) {
            $this->logger->critical("createData() - module is many-to-many relationship, calling createRelationship");
            return $this->createRelationship($param);
        } else {
            $this->logger->critical("createData() - module is NOT many-to-many, proceeding with normal creation");
        }

        // Transformation du tableau d'entrée pour être compatible webservice Sugar
        foreach ($param['data'] as $idDoc => $data) {
            $this->logger->critical("createData() - processing document ID: " . $idDoc);
            try {
                // Check control before create
                $this->logger->critical("createData() - calling checkDataBeforeCreate for doc: " . $idDoc);
                $data = $this->checkDataBeforeCreate($param, $data, $idDoc);
                $this->logger->critical("createData() - checkDataBeforeCreate completed for doc: " . $idDoc);

                $dataSugar = [];
                $this->logger->critical("createData() - building dataSugar array from data fields");
                foreach ($data as $key => $value) {
                    if ('Birthdate' == $key && '0000-00-00' == $value) {
                        $this->logger->critical("createData() - skipping Birthdate field with 0000-00-00 value");
                        continue;
                    } else if ('Birthdate' == $key) {
                        $this->logger->critical("createData() - including Birthdate field with value: " . $value);
                    }

                    // Si un champ est une relation custom alors on enlève le prefix
                    if (substr($key, 0, strlen($this->customRelationship)) == $this->customRelationship) {
                        $originalKey = $key;
                        $key = substr($key, strlen($this->customRelationship));
                        $this->logger->critical("createData() - removed custom relationship prefix - original: " . $originalKey . ", new: " . $key);
                    }

                    // Note are sent using setNoteAttachement function
                    if (
                        $param['module'] == 'Notes'
                        and $key == 'filecontents'
                    ) {
                        $this->logger->critical("createData() - skipping filecontents field for Notes module");
                        continue;
                    }
                    $dataSugar[] = ['name' => $key, 'value' => $value];
                }
                $this->logger->critical("createData() - dataSugar array built with " . count($dataSugar) . " fields");

                $setEntriesListParameters = [
                    'session' => $this->session,
                    'module_name' => $param['module'],
                    'name_value_list' => $dataSugar,
                ];
                $this->logger->critical("createData() - calling API set_entry with session: " . $this->session . ", module: " . $param['module']);
                $get_entry_list_result = $this->call('set_entry', $setEntriesListParameters);
                $this->logger->critical("createData() - set_entry call completed");

                $this->logger->critical("createData() - checking if result has id field");
                if (!empty($get_entry_list_result->id)) {
                    $this->logger->critical("createData() - result HAS id: " . $get_entry_list_result->id);

                    // In case of module note with attachement, we generate a second call to add the file
                    if (
                        $param['module'] == 'Notes'
                        and !empty($data['filecontents'])
                    ) {
                        $this->logger->critical("createData() - Notes module with filecontents, calling setNoteAttachement");
                        $this->setNoteAttachement($data, $get_entry_list_result->id);
                        $this->logger->critical("createData() - setNoteAttachement completed");
                    } else {
                        $this->logger->critical("createData() - NOT a Notes module with filecontents, skipping attachment");
                    }

                    $result[$idDoc] = [
                        'id' => $get_entry_list_result->id,
                        'error' => false,
                    ];
                    $this->logger->critical("createData() - SUCCESS for doc " . $idDoc . ", created ID: " . $get_entry_list_result->id);
                } else {
                    $this->logger->critical("createData() - result does NOT have id - THROWING EXCEPTION");
                    $this->logger->critical("createData() - error name: " . (!empty($get_entry_list_result->name) ? $get_entry_list_result->name : 'empty'));
                    $this->logger->critical("createData() - error description: " . (!empty($get_entry_list_result->description) ? $get_entry_list_result->description : 'empty'));
                    $this->logger->critical("createData() - full result object: " . json_encode($get_entry_list_result));
                    throw new \Exception('error '.(!empty($get_entry_list_result->name) ? $get_entry_list_result->name : '').' : '.(!empty($get_entry_list_result->description) ? $get_entry_list_result->description : ''));
                }
            } catch (\Exception $e) {
                $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->logger->critical("createData() - EXCEPTION caught for doc " . $idDoc . " - " . $error);
                $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $error,
                ];
            }
            // Modification du statut du flux
            $this->logger->critical("createData() - updating document status for doc: " . $idDoc);
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        $this->logger->critical("createData() - returning result with " . (isset($result) ? count($result) : 0) . " documents");
        return $result;
    }

    // Permet de créer les relation many-to-many (considéré comme un module avec 2 relation 1-n dans Myddleware)

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function createRelationship($param)
    {
        $this->logger->critical("createRelationship() method called - module: " . ($param['module'] ?? 'unknown'));
        $this->logger->critical("createRelationship() - data count: " . (isset($param['data']) ? count($param['data']) : 0));
        $this->logger->critical("createRelationship() - current session ID: " . ($this->session ?? 'null'));

        foreach ($param['data'] as $idDoc => $data) {
            $this->logger->critical("createRelationship() - processing document ID: " . $idDoc);
            try {
                // Check control before create
                $this->logger->critical("createRelationship() - calling checkDataBeforeCreate for doc: " . $idDoc);
                $data = $this->checkDataBeforeCreate($param, $data, $idDoc);
                $this->logger->critical("createRelationship() - checkDataBeforeCreate completed for doc: " . $idDoc);

                $dataSugar = [];
                if (!empty($this->module_relationship_many_to_many[$param['module']]['fields'])) {
                    $this->logger->critical("createRelationship() - module has relationship fields, building dataSugar");
                    foreach ($this->module_relationship_many_to_many[$param['module']]['fields'] as $field) {
                        if (isset($data[$field])) {
                            $this->logger->critical("createRelationship() - adding field: " . $field);
                            $dataSugar[] = ['name' => $field, 'value' => $data[$field]];
                        } else {
                            $this->logger->critical("createRelationship() - field NOT set: " . $field);
                        }
                    }
                } else {
                    $this->logger->critical("createRelationship() - module has NO relationship fields");
                }

                $set_relationship_params = [
                    'session' => $this->session,
                    'module_name' => $this->module_relationship_many_to_many[$param['module']]['module_name'],
                    'module_id' => $data[$this->module_relationship_many_to_many[$param['module']]['relationships'][0]],
                    'link_field_name' => $this->module_relationship_many_to_many[$param['module']]['link_field_name'],
                    'related_ids' => [$data[$this->module_relationship_many_to_many[$param['module']]['relationships'][1]]],
                    'name_value_list' => $dataSugar,
                    'delete' => (!empty($data['deleted']) ? 1 : 0),
                ];
                $this->logger->critical("createRelationship() - calling API set_relationship with session: " . $this->session);
                $set_relationship_result = $this->call('set_relationship', $set_relationship_params);
                $this->logger->critical("createRelationship() - set_relationship call completed");

                $this->logger->critical("createRelationship() - checking if result has created field");
                if (!empty($set_relationship_result->created)) {
                    $this->logger->critical("createRelationship() - relationship created SUCCESSFULLY");
                    $result[$idDoc] = [
                        'id' => $idDoc, // On met $idDoc car onn a pas l'id de la relation
                        'error' => false,
                    ];
                    $this->logger->critical("createRelationship() - SUCCESS for doc " . $idDoc);
                } else {
                    $this->logger->critical("createRelationship() - relationship NOT created - setting error code 01");
                    $this->logger->critical("createRelationship() - result object: " . json_encode($set_relationship_result));
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => '01',
                    ];
                }
            } catch (\Exception $e) {
                $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->logger->critical("createRelationship() - EXCEPTION caught for doc " . $idDoc . " - " . $error);
                $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $error,
                ];
            }
            // Modification du statut du flux
            $this->logger->critical("createRelationship() - updating document status for doc: " . $idDoc);
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        $this->logger->critical("createRelationship() - returning result with " . (isset($result) ? count($result) : 0) . " documents");
        return $result;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateData($param): array
    {
        $this->logger->critical("updateData() method called - module: " . ($param['module'] ?? 'unknown'));
        $this->logger->critical("updateData() - data count: " . (isset($param['data']) ? count($param['data']) : 0));
        $this->logger->critical("updateData() - current session ID: " . ($this->session ?? 'null'));

        // In case of many to many relationship, the update is done by using createRelationship function
        if (array_key_exists($param['module'], $this->module_relationship_many_to_many)) {
            $this->logger->critical("updateData() - module is many-to-many relationship, calling createRelationship");
            return $this->createRelationship($param);
        } else {
            $this->logger->critical("updateData() - module is NOT many-to-many, proceeding with normal update");
        }

        // Transformation du tableau d'entrée pour être compatible webservice Sugar
        foreach ($param['data'] as $idDoc => $data) {
            $this->logger->critical("updateData() - processing document ID: " . $idDoc);
            try {
                // Check control before update
                $this->logger->critical("updateData() - calling checkDataBeforeUpdate for doc: " . $idDoc);
                $data = $this->checkDataBeforeUpdate($param, $data, $idDoc);
                $this->logger->critical("updateData() - checkDataBeforeUpdate completed for doc: " . $idDoc);

                $dataSugar = [];
                $this->logger->critical("updateData() - building dataSugar array from data fields");
                foreach ($data as $key => $value) {
                    // Important de renommer le champ id pour que SuiteCRM puisse effectuer une modification et non une création
                    if ('target_id' == $key) {
                        $this->logger->critical("updateData() - renaming target_id to id");
                        $key = 'id';
                    }
                    // Si un champ est une relation custom alors on enlève le prefix
                    if (substr($key, 0, strlen($this->customRelationship)) == $this->customRelationship) {
                        $originalKey = $key;
                        $key = substr($key, strlen($this->customRelationship));
                        $this->logger->critical("updateData() - removed custom relationship prefix - original: " . $originalKey . ", new: " . $key);
                    }

                    if ('Birthdate' == $key && '0000-00-00' == $value) {
                        $this->logger->critical("updateData() - skipping Birthdate field with 0000-00-00 value");
                        continue;
                        // Note are sent using setNoteAttachement function
                        if (
                            $param['module'] == 'Notes'
                            and $key == 'filecontents'
                        ) {
                            continue;
                        }
                    }

                    // Note are sent using setNoteAttachement function
                    if (
                        $param['module'] == 'Notes'
                        and $key == 'filecontents'
                    ) {
                        $this->logger->critical("updateData() - skipping filecontents field for Notes module");
                        continue;
                    }

                    $dataSugar[] = ['name' => $key, 'value' => $value];
                }
                $this->logger->critical("updateData() - dataSugar array built with " . count($dataSugar) . " fields");

                $setEntriesListParameters = [
                    'session' => $this->session,
                    'module_name' => $param['module'],
                    'name_value_list' => $dataSugar,
                ];
                $this->logger->critical("updateData() - calling API set_entry with session: " . $this->session . ", module: " . $param['module']);

                $get_entry_list_result = $this->call('set_entry', $setEntriesListParameters);
                $this->logger->critical("updateData() - set_entry call completed");

                $this->logger->critical("updateData() - checking if result has id field");
                if (!empty($get_entry_list_result->id)) {
                    $this->logger->critical("updateData() - result HAS id: " . $get_entry_list_result->id);

                    // In case of module note with attachement, we generate a second call to add the file
                    if (
                        $param['module'] == 'Notes'
                        and !empty($data['filecontents'])
                    ) {
                        $this->logger->critical("updateData() - Notes module with filecontents, calling setNoteAttachement");
                        $this->setNoteAttachement($data, $get_entry_list_result->id);
                        $this->logger->critical("updateData() - setNoteAttachement completed");
                    } else {
                        $this->logger->critical("updateData() - NOT a Notes module with filecontents, skipping attachment");
                    }

                    $result[$idDoc] = [
                        'id' => $get_entry_list_result->id,
                        'error' => false,
                    ];
                    $this->logger->critical("updateData() - SUCCESS for doc " . $idDoc . ", updated ID: " . $get_entry_list_result->id);
                } else {
                    $this->logger->critical("updateData() - result does NOT have id - THROWING EXCEPTION");
                    $this->logger->critical("updateData() - error name: " . (!empty($get_entry_list_result->name) ? $get_entry_list_result->name : 'empty'));
                    $this->logger->critical("updateData() - error description: " . (!empty($get_entry_list_result->description) ? $get_entry_list_result->description : 'empty'));
                    $this->logger->critical("updateData() - full result object: " . json_encode($get_entry_list_result));
                    throw new \Exception('error '.(!empty($get_entry_list_result->name) ? $get_entry_list_result->name : '').' : '.(!empty($get_entry_list_result->description) ? $get_entry_list_result->description : ''));
                }
            } catch (\Exception $e) {
                $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->logger->critical("updateData() - EXCEPTION caught for doc " . $idDoc . " - " . $error);
                $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $error,
                ];
            }
            // Modification du statut du flux
            $this->logger->critical("updateData() - updating document status for doc: " . $idDoc);
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        $this->logger->critical("updateData() - returning result with " . (isset($result) ? count($result) : 0) . " documents");
        return $result;
    }

    // Function to send a note
	protected function setNoteAttachement($data, $noteId) {					
		$setNoteAttachementParameters = array(
			'session' => $this->session,
			'note' => array(
				'id' => $noteId,
				'filename' => $data['filename'],
				'file' => $data['filecontents'],
			),
		);

		$set_not_attachement_result = $this->call('set_note_attachment', $setNoteAttachementParameters);
		if (
				empty($set_not_attachement_result->id)
			 OR (
					!empty($set_not_attachement_result->id)
				AND $set_not_attachement_result->id == '-1'
			)
		) {
			 throw new \Exception('Failed to create the attachement on the note. ');
		}				
	}

    
    // Function to delete a record
    public function deleteData($param): array
    {
        // We set the flag deleted to 1 and we call the update function
        foreach ($param['data'] as $idDoc => $data) {
            $param['data'][$idDoc]['deleted'] = 1;
        }

        // In case of many to many relationship, the delettion is done by using createRelationship function
        if (array_key_exists($param['module'], $this->module_relationship_many_to_many)) {
            return $this->createRelationship($param);
        }

        return $this->updateData($param);
    }

	
    // Build the query for read data to SuiteCRM
    /**
     * @throws \Exception
     */
    protected function generateQuery($param, $method): string
    {
        $query = '';
        // if a specific query is requeted we don't use date_ref
        if (!empty($param['query'])) {
            foreach ($param['query'] as $key => $value) {
                if (!empty($query)) {
                    $query .= ' AND ';
                }
                if ('email1' == $key) {
                    $query .= strtolower($param['module']).".id in (SELECT eabr.bean_id FROM email_addr_bean_rel eabr JOIN email_addresses ea ON (ea.id = eabr.email_address_id) WHERE eabr.deleted=0 and ea.email_address LIKE '".$value."') ";
                } else {
                    // Pour ProspectLists le nom de la table et le nom de l'objet sont différents
                    if ('ProspectLists' == $param['module']) {
                        $query .= 'prospect_lists.'.$key." = '".$value."' ";
                    } elseif ('Employees' == $param['module']) {
                        $query .= 'users.'.$key." = '".$value."' ";
                    } else {
                        $query .= (substr($key,-2) == '_c' ? strtolower($param['module']).'_cstm' : strtolower($param['module'])).'.'.$key." = '".$value."' ";
                    }
                }
            }
            // Filter by date only for read method (no need for read_last method
        } elseif ('read' == $method) {
            $dateRefField = $this->getRefFieldName($param);
            // Pour ProspectLists le nom de la table et le nom de l'objet sont différents
            if ('ProspectLists' == $param['module']) {
                $query = 'prospect_lists.'.$dateRefField." > '".$param['date_ref']."'";
            } elseif ('Employees' == $param['module']) {
                $query = 'users.'.$dateRefField." > '".$param['date_ref']."'";
            } else {
                $query = strtolower($param['module']).'.'.$dateRefField." > '".$param['date_ref']."'";
            }
        }

        return $query;
    }

    // Permet de renvoyer le mode de la règle en fonction du module target
    // Valeur par défaut "0"
    // Si la règle n'est qu'en création, pas en modicication alors le mode est C
    // public function getRuleMode($module, $type): array
    // {
        // if (
                // 'target' == $type
            // && array_key_exists($module, $this->module_relationship_many_to_many)
        // ) {
            // return [
                // 'C' => 'create_only',
            // ];
        // }

        // return parent::getRuleMode($module, $type);
    // }

    // Renvoie le nom du champ de la date de référence en fonction du module et du mode de la règle

    /**
     * @throws \Exception
     */
    public function getRefFieldName($param): string
    {
        if (in_array($param['ruleParams']['mode'], ['0', 'S', 'U'])) {
            return 'date_modified';
        } elseif ('C' == $param['ruleParams']['mode']) {
            return 'date_entered';
        }
        throw new \Exception("$param[ruleParams][mode] is not a correct Rule mode.");
    }

    // Get the list of field (name and id) for each custom relationship
    protected function getCustomRelationshipListFields($module): array
    {
		$result = array();
        $get_module_fields_parameters = [
            'session' => $this->session,
            'module_name' => $module,
        ];
        $get_module_fields = $this->call('get_module_fields', $get_module_fields_parameters);
        // Get all custom relationship fields
        if (!empty($get_module_fields->link_fields)) {
            foreach ($get_module_fields->link_fields as $field) {
                if (
                        '_id' == substr($field->name, -3)
                    || '_ida' == substr($field->name, -4)
                    || '_idb' == substr($field->name, -4)
                    || (
                            'id' == $field->type
                        && 'id' != $field->name
                    )
                ) {
                    // Build the result array to get the relationship name for all field name
                    $result[$field->name]['id'] = $this->customRelationship.$field->name;
                    $result[$field->name]['name'] = $this->customRelationship.$field->relationship.'_name';
                }
            }
        }

        return $result;
    }

    //function to make cURL request
    protected function call($method, $parameters)
    {
        try {
            $this->logger->critical("call() method invoked - method: " . $method);
            $this->logger->critical("call() - URL: " . $this->paramConnexion['url']);

            ob_start();
            $this->logger->critical("output buffering started");

            // we check if we have a cookie file to manage the session
            $this->logger->critical("checking for cookie file - path: " . ($this->cookieFilePath ?? 'null'));
            if ($this->isCacheValid($this->cookieFilePath)) {
                $this->logger->critical("cookie cache is VALID check before cookie content");
            } else {
                $this->logger->critical("cookie cache is NOT valid check before cookie content");
                $this->invalidateSession();
            }
            if ($this->cookieFilePath && file_exists($this->cookieFilePath)) {
                $this->logger->critical("cookie file EXISTS, reading content");
                $cookieContent = file_get_contents($this->cookieFilePath);
                $this->logger->critical("cookie file content length: " . strlen($cookieContent));
            } else {
                $this->logger->critical("cookie file does NOT exist or path is null");
            }

            $this->logger->critical("initializing cURL request");
            $curl_request = curl_init();
            $this->logger->critical("cURL initialized");

            $this->logger->critical("setting CURLOPT_URL to: " . $this->paramConnexion['url']);
            curl_setopt($curl_request, CURLOPT_URL, $this->paramConnexion['url']);
            $this->logger->critical("setting CURLOPT_POST to 1");
            curl_setopt($curl_request, CURLOPT_POST, 1);
            $this->logger->critical("setting CURLOPT_HTTP_VERSION to CURL_HTTP_VERSION_1_0");
            curl_setopt($curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
            $this->logger->critical("setting CURLOPT_HEADER to 1");
            curl_setopt($curl_request, CURLOPT_HEADER, 1);
            $this->logger->critical("setting CURLOPT_SSL_VERIFYPEER to 0");
            curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);
            $this->logger->critical("setting CURLOPT_RETURNTRANSFER to 1");
            curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
            $this->logger->critical("setting CURLOPT_FOLLOWLOCATION to 0");
            curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);
            $this->logger->critical("all base cURL options set");

            // If the cookie is found, we use it for the curl request
            $this->logger->critical("checking if method is 'login' - method: " . $method);
            if ($this->cookieFilePath !== null && $method == 'login') {
                $this->logger->critical("method IS 'login' and cookieFilePath is not null, setting cookie options");
                $this->logger->critical("setting CURLOPT_COOKIEJAR to: " . $this->cookieFilePath);
                curl_setopt($curl_request, CURLOPT_COOKIEJAR, $this->cookieFilePath);
                $this->logger->critical("setting CURLOPT_COOKIEFILE to: " . $this->cookieFilePath);
                curl_setopt($curl_request, CURLOPT_COOKIEFILE, $this->cookieFilePath);
                $this->logger->critical("cookie options set for login");
            } else {
                $this->logger->critical("NOT setting cookie options - cookieFilePath: " . ($this->cookieFilePath ?? 'null') . ", method: " . $method);
            }

            $this->logger->critical("encoding parameters to JSON");
            $jsonEncodedData = json_encode($parameters);
            $this->logger->critical("JSON encoded data length: " . strlen($jsonEncodedData));

            $post = [
                'method' => $method,
                'input_type' => 'JSON',
                'response_type' => 'JSON',
                'rest_data' => $jsonEncodedData,
            ];
            $this->logger->critical("POST data array created - method: " . $method);

            $this->logger->critical("setting CURLOPT_POSTFIELDS");
            curl_setopt($curl_request, CURLOPT_POSTFIELDS, $post);
            $this->logger->critical("CURLOPT_POSTFIELDS set");

            $this->logger->critical("executing cURL request");
            $result = curl_exec($curl_request);
            $curlError = curl_error($curl_request);
            $curlErrno = curl_errno($curl_request);
            $httpCode = curl_getinfo($curl_request, CURLINFO_HTTP_CODE);
            $this->logger->critical("cURL request executed - HTTP code: " . $httpCode . ", errno: " . $curlErrno . ", error: " . ($curlError ?: 'none'));

            $this->logger->critical("closing cURL");
            curl_close($curl_request);
            $this->logger->critical("cURL closed");

            if (empty($result)) {
                $this->logger->critical("result is EMPTY, returning false");
                return false;
            } else {
                $this->logger->critical("result is NOT empty, length: " . strlen($result));
            }

            // Extract headers and body
            $this->logger->critical("extracting headers and body from result");
            $result = explode("\r\n\r\n", $result, 2);
            $this->logger->critical("result split into parts - count: " . count($result));

            $this->logger->critical("decoding JSON response");
            $response = json_decode($result[1] ?? ''); // we add ?? '' to avoid error if index 1 does not exists
            $this->logger->critical("JSON decoded - response type: " . gettype($response));

            if ($response === null) {
                $this->logger->critical("JSON decode returned NULL - possible parse error");
            } else {
                $this->logger->critical("JSON decoded successfully");
            }

            ob_end_flush();
            $this->logger->critical("output buffering flushed");

            $this->logger->critical("returning response from call() method");
            return $response;
        } catch (\Exception $e) {
            $this->logger->critical("EXCEPTION in call() method - message: " . $e->getMessage() . ", file: " . $e->getFile() . ", line: " . $e->getLine());
            return false;
        }
    }
}
