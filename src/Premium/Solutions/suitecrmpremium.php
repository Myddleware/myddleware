<?php

namespace App\Premium\Solutions;

use App\Solutions\suitecrm;

class suitecrmpremium extends suitecrm
{
	public function login($paramConnexion)
    {
		return ['error' => 'premium'];
    }
}