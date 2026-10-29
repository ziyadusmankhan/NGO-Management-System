<?php

use Laravel\Fortify\Features;

return ['guard' => 'web', 'middleware' => ['web'], 'username' => 'email', 'email' => 'email', 'home' => '/', 'views' => false, 'features' => [Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true])]];
