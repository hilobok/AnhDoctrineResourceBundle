<?php

namespace Anh\DoctrineResourceBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\HttpFoundation\Request;

class OptionsParser
{
    protected $langauge;

    protected $resolver;

    protected $request;

    protected $resource;

    protected $resourceName;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->language = new ExpressionLanguage();
        $this->resolver = new OptionsResolver();
        $this->configureOptions($this->resolver);
    }

    public function parse(array $options = array(), array $defaults = array())
    {
        $this->resolver->setDefaults($defaults);

        return $this->process(
            $this->resolver->resolve($options)
        );
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    public function setResource($resource)
    {
        $this->resource = $resource;

        return $this;
    }

    public function setResourceName($resourceName)
    {
        $this->resourceName = $resourceName;

        return $this;
    }

    public function process($option)
    {
        if (is_array($option)) {
            foreach ($option as $key => $value) {
                $option[$key] = $this->process($value);
            }

            return $option;
        }

        if (strpos($option, 'request.') !== false && $this->request) {
            return $this->language->evaluate($option, array(
                'request' => $this->request
            ));
        }

        if (strpos($option, 'resource.') !== false && $this->resource) {
            return $this->language->evaluate($option, array(
                'resource' => $this->resource
            ));
        }

        if (strpos($option, 'container.') !== false && $this->container) {
            return $this->language->evaluate($option, array(
                'container' => $this->container
            ));
        }

        return $option;
    }

    protected function configureOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'criteria' => null,
            'sorting' => null,
            'offset' => null,
            'page' => null,
            'limit' => function (Options $options) {
                return is_null($options['page']) ? null : 10;
            },

            'method' => function (Options $options) {
                return is_null($options['page']) ? 'fetch' : 'paginate';
            },
            'arguments' => function (Options $options) {
                switch ($options['method']) {
                    case 'fetch':
                        return array(
                            $options['criteria'],
                            $options['sorting'],
                            $options['limit'],
                            $options['offset'],
                        );

                    case 'paginate':
                        return array(
                            $options['page'],
                            $options['limit'],
                            $options['criteria'],
                            $options['sorting'],
                        );

                    case 'findOneBy':
                        return array(
                            $options['criteria'],
                            $options['sorting'],
                        );

                    default:
                        return array();
                }
            },

            'redirect' => 'list',

            'form' => function (Options $options) {
                return str_replace('.', '_', $this->resourceName);
            },

            'view' => null,
            'viewVars' => array(),
        ));

        $resolver->setNormalizers(array(
            'redirect' => function (Options $options, $value) {
                if (!is_array($value)) {
                    $value = array(
                        'route' => $value,
                        'parameters' => array(),
                    );
                }

                if (in_array($value['route'], array('create', 'update', 'list', 'show'), true)) {
                    $value['route'] = sprintf(
                        '%s_%s',
                        str_replace('.', '_', $this->resourceName),
                        $value['route']
                    );
                }

                return $value + array('parameters' => array());
            }
        ));

        $resolver->setAllowedValues(array(
            'redirect' => function ($value) {
                return is_null($value) || is_string($value) || (is_array($value) && isset($value['route']));
            },
            'view' => function ($value) {
                return is_null($value) || is_string($value) || (is_array($value) && isset($value['format']));
            }
        ));
    }
}
