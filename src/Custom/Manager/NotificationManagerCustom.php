<?php

namespace App\Custom\Manager;

use App\Manager\NotificationManager;

/**
 * Class NotificationManagerCustom.
 */
class NotificationManagerCustom extends NotificationManager
{
    // Redefine method to keep only myddleware email for notifications and alerts
    protected function setEmailAddresses()
    {
		parent::setEmailAddresses();
        if (!empty($this->emailAddresses)) {
            foreach ($this->emailAddresses as $key => $emailAddress) {
                if (strpos($emailAddress, '@myddleware.com') === false) {
                    unset($this->emailAddresses[$key]);
                }
            }
        }
    }
}
