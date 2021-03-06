<?php

namespace Anh\DoctrineResourceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Anh\DoctrineResource\ResourceManager;

class ResourceController extends Controller
{
    protected $resourceManager;

    protected $optionsParser;

    protected $redirectHandler;

    public function __construct(
        ResourceManager $resourceManager,
        OptionsParser $optionsParser,
        RedirectHandler $redirectHandler
    ) {
        $this->resourceManager = $resourceManager;
        $this->optionsParser = $optionsParser;
        $this->redirectHandler = $redirectHandler;
    }

    public function createAction(Request $request)
    {
        $options = $this->getOptions($request);

        $resource = $this->resourceManager->createResource();
        $form = $this->createForm($options['form'], $resource, $options['form_options']);
        $this->addRedirect($form, $request->headers->get('referer'));

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $this->resourceManager->create($resource);
            $this->optionsParser->setResource($resource);

            $response['redirect'] = $this->redirectHandler
                ->setReferer($form->get('_redirect')->getData() ?: $request->getUri())
                ->redirectTo($options['redirect'])
            ;
        }

        $response['view'] = $options['view'];
        $response['data'] = array(
            'resource' => $resource,
            'resource_form' => $form,
            'form' => $form->createView(),
        ) + $options['data'];

        return $response;
    }

    public function updateAction(Request $request)
    {
        $options = $this->getOptions($request, array(
            'criteria' => array(
                'id' => 'request.attributes.get("id")'
            ),
            'method' => 'findOneBy',
        ));

        $resource = $this->getResources($options);
        $form = $this->createForm($options['form'], $resource, $options['form_options']);
        $this->addRedirect($form, $request->headers->get('referer'));

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $this->resourceManager->update($resource);
            $this->optionsParser->setResource($resource);

            $response['redirect'] = $this->redirectHandler
                ->setReferer($form->get('_redirect')->getData() ?: $request->getUri())
                ->redirectTo($options['redirect'])
            ;
        }

        $response['view'] = $options['view'];
        $response['data'] = array(
            'resource' => $resource,
            'resource_form' => $form,
            'form' => $form->createView(),
        ) + $options['data'];

        return $response;
    }

    public function deleteAction(Request $request)
    {
        $options = $this->getOptions($request, array(
            'resource' => 'request.request.get("id")',
        ));

        $resource = $options['resource'];

        if (!empty($options['criteria'])) {
            $resource = $this->getResources($options);
        }

        $this->resourceManager->delete($resource);

        return $this->redirectHandler
            ->setReferer($request->headers->get('referer') ?: $request->getUri())
            ->redirectTo($options['redirect'])
        ;
    }

    public function listAction(Request $request)
    {
        $options = $this->getOptions($request);

        $resources = $this->getResources($options);

        return array(
            'view' => $options['view'],
            'data' => array(
                'resources' => $resources
            ) + $options['data']
        );
    }

    public function showAction(Request $request)
    {
        $options = $this->getOptions($request, array(
            'criteria' => array(
                'id' => 'request.attributes.get("id")'
            ),
            'method' => 'findOneBy',
        ));

        $resource = $this->getResources($options);

        if (empty($resource)) {
            throw $this->createNotFoundException(
                'Resource not found.'
            );
        }

        return array(
            'view' => $options['view'],
            'data' => array(
                'resource' => $resource
            ) + $options['data']
        );
    }

    public function dummyAction(Request $request)
    {
        $options = $this->getOptions($request);

        return array(
            'view' => $options['view'],
            'data' => $options['data']
        );
    }

    protected function getOptions(Request $request, array $defaults = array())
    {
        return $this->optionsParser
            ->setRequest($request)
            ->setResource(null)
            ->setResourceName($this->resourceManager->getResourceName())
            ->parse($request->attributes->get('_anh_resource', array()), $defaults)
        ;
    }

    protected function getResources($options)
    {
        return call_user_func_array(
            array(
                $this->resourceManager->getRepository(),
                $options['method']
            ),
            $options['arguments']
        );
    }

    protected function addRedirect($form, $redirect)
    {
        if (!$form->has('_redirect')) {
            $form->add($this->get('form.factory')->createNamed(
                '_redirect',
                'hidden',
                $redirect,
                array(
                    'auto_initialize' => false,
                    'mapped' => false
                )
            ));
        }
    }
}
