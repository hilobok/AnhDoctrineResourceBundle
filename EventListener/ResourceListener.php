<?php

namespace Anh\DoctrineResourceBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ResourceListener
{
    public function __construct($templating, $serializer = null)
    {
        $this->templating = $templating;
        $this->serializer = $serializer;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $filter = $request->query->get('filter');

        if (!empty($filter) && is_array($filter)) {
            $request->attributes->set('_route_params', array_merge(
                array('filter' => $filter),
                $request->attributes->get('_route_params')
            ));
        }
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = (array) $event->getControllerResult() + array(
            'view' => null,
            'data' => array(),
            'redirect' => null,
        );

        if (isset($controllerResult['redirect'])) {
            $redirect = ($controllerResult['redirect'] instanceof RedirectResponse)
                ? $controllerResult['redirect']
                : new RedirectResponse($controllerResult['redirect'])
            ;

            return $event->setResponse($redirect);
        }

        if (is_string($controllerResult['view'])) {
            $response = $this->templating->renderResponse(
                $controllerResult['view'],
                $controllerResult['data'],
                $event->hasResponse() ? $event->getResponse() : null
            );

            return $event->setResponse($response);
        }

        if (is_array($controllerResult['view'])) {
            if (!$this->serializer) {
                throw new \RuntimeException(
                    'Serializer service is not enabled.'
                );
            }

            $view = $controllerResult['view'] + array(
                'format' => 'json',
                'vars' => array()
            );

            $data = empty($view['vars']) ? $controllerResult['data'] :
                array_intersect_key(
                    (array) $controllerResult['data'],
                    array_flip((array) $view['vars'])
                )
            ;

            $content = $this->serializer->serialize($data, $view['format']);

            switch ($view['format']) {
                case 'json':
                    $response = new JsonResponse($content);
                    break;

                case 'xml':
                    $response = new Response(
                        sprintf("<?xml version='1.0' encoding='UTF-8'?>\n%s", $content)
                    );
                    $response->headers->set('Content-Type', 'text/xml');
                    break;

                default:
                    throw new \InvalidArgumentException(
                        sprintf("Have no idea how to create reponse for '%s' format.", $view['format'])
                    );
            }

            $event->setResponse($response);
        }
    }
}
