<?php

namespace UniteCMS\CoreBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;

class ApiRequestMatcher extends RequestMatcher
{

    public function __construct($approach, $allowed_hostname, $identifier_pattern)
    {
        // Remove prefix and suffix.
        $identifier_pattern = str_replace('^', '', $identifier_pattern);
        $identifier_pattern = str_replace('$', '', $identifier_pattern);

        // Matches /{domain}/api
        if($approach == 'subdomain') {
            parent::__construct('^/'.$identifier_pattern.'/api(/|$)', '^'.$identifier_pattern.'\.'.$allowed_hostname);

        // Matches /{organization}/{domain}/api
        } else {
            parent::__construct('^/'.$identifier_pattern.'/'.$identifier_pattern.'/api(/|$)');
        }
    }

    /**
     * Extends the default request matcher to also check if a special header field: Authentication-Fallback was set.
     * This allows the client to fall back to cookie authentication for API requests.
     *
     * @param Request $request The request to check for a match
     *
     * @return bool true if the request matches, false otherwise
     */
    public function matches(Request $request)
    {
        return parent::matches($request) && (!$request->headers->has('Authentication-Fallback') || !filter_var(
                    $request->headers->get('Authentication-Fallback'),
                    FILTER_VALIDATE_BOOLEAN
                ));
    }
}
