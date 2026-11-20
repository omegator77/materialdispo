<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vertrauenswürdige Reverse-Proxys
    |--------------------------------------------------------------------------
    |
    | Von diesen Absendern werden X-Forwarded-*-Header übernommen (Client-IP,
    | HTTPS-Erkennung). Kommagetrennte IPs/CIDRs oder "PRIVATE_SUBNETS" (Standard:
    | Docker-/interne Netze). "*" nur, wenn der App-Port nie direkt erreichbar ist.
    |
    */

    'proxies' => env('TRUSTED_PROXIES', 'PRIVATE_SUBNETS'),

];
