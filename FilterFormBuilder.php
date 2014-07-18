<?php

namespace Anh\DoctrineResourceBundle;

use Symfony\Component\Form\FormFactoryInterface;

class FilterFormBuilder
{
    protected $formFactory;

    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    public function build($filter, array $parameters = array())
    {
        if (is_string($filter)) {
            $filter = new $filter;
        }

        $builder = $this->formFactory->createNamedBuilder('filter', 'form', $filter, array(
            'attr' => array(
                'class' => 'resource_filter',
            ),
            'csrf_protection' => false,
            'empty_data' => null,
        ));

        $builder->setMethod('GET');

        foreach ($filter->getDefinition($parameters) as $field => $definition) {
            $definition += array(
                'type' => 'text',
                'form' => array(),
            );

            $builder->add($field, $definition['type'], $definition['form'] + array('required' => false));
        }

        $sorting = $filter->getSorting($parameters);

        if (!$sorting->isEmpty()) {
            $sortBuilder = $this->formFactory->createNamedBuilder('sort', 'form', $sorting);

            $sortBuilder->add('field', 'choice', array(
                'choices' => $sorting->getFields(),
            ));

            $sortBuilder->add('order', 'choice', array(
                'choices' => $sorting->getOrders(),
                'expanded' => true,
                'multiple' => false,
            ));

            $builder->add($sortBuilder);
        }

        $builder->add('filter', 'submit');

        return $builder->getForm();
    }
}
