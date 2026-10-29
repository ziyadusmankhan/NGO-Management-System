<?php

return ['lookup_key' => env('SECURITY_LOOKUP_KEY'), 'trusted_proxies' => array_filter(explode(',', env('TRUSTED_PROXIES', '')))];
