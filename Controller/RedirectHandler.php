<?php

namespace Anh\DoctrineResourceBundle\Controller;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RedirectHandler
{
    protected $router;

    protected $optionsParser;

    protected $referer;

    public function __construct(RouterInterface $router, OptionsParser $optionsParser)
    {
        $this->router = $router;
        $this->optionsParser = $optionsParser;
    }

    public function setReferer($referer)
    {
        $this->referer = $referer;

        return $this;
    }

    public function redirectTo($redirect, array $parameters = array())
    {
        if (is_array($redirect) && isset($redirect['route'])) {
            $parameters = isset($redirect['parameters']) ? $redirect['parameters'] : array();

            return $this->redirectTo($redirect['route'], $this->optionsParser->process($parameters));
        }

        if (is_string($redirect)) {
            if ($redirect === 'referer') {
                return $this->redirect($this->referer);
            }

            $route = $this->router->getRouteCollection()->get($redirect);
            if ($route) {
                return $this->redirect($this->router->generate($redirect, $parameters));
            }

            return $this->redirect($redirect);
        }
    }

    protected function redirect($url, $status = 302)
    {
        return new RedirectResponse($url, $status);
    }
}
