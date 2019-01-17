<?php

namespace UniteCMS\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

class ExceptionController extends AbstractController
{
    /**
     * Converts an Exception to a Response.
     *
     * @param Request $request
     * @param FlattenException $exception
     * @param DebugLoggerInterface|null $logger
     * @return Response
     *
     */
    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        // Get http error code from exception.
        $code = $exception->getStatusCode();

        // Get messages only for known http errors. Fallback is 500.
        if(!in_array($code, [403, 404])) {
            $code = 500;
        }

        // Internal Server Error
        return $this->render('@UniteCMSCore/Exception/http-error.html.twig', ['code' => $code]);
    }
}
