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
        if (is_array($redirect)) {
            $redirect += array(
                'route' => '',
                'parameters' => array()
            );

            return $this->redirectTo(
                $redirect['route'],
                $this->optionsParser->process($redirect['parameters'])
            );
        }

        if ($redirect === 'referer') {
            return $this->redirect($this->referer);
        }

        if ($this->router->getRouteCollection()->get($redirect)) {
            return $this->redirect($this->router->generate($redirect, $parameters));
        }

        return $this->redirect($redirect);
    }

    protected function redirect($url, $status = 302)
    {
        return new RedirectResponse($url, $status);
    }
}
