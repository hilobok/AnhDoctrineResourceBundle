<?php

namespace Anh\DoctrineResourceBundle;

class FilterSorting
{
    protected $fields;

    protected $orders;

    protected $field;

    protected $order;

    public function __construct(array $fields = null, array $orders = null)
    {
        $this->fields = $fields;
        $this->orders = $orders;
        $this->field = key($fields);
        $this->order = key($orders);
    }

    public function getField()
    {
        return $this->field;
    }

    public function setField($field)
    {
        $this->field = $field;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setOrder($order)
    {
        $this->order = $order;
    }

    public function getSorting()
    {
        return empty($this->field) ? array() : array($this->field => $this->order);
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getOrders()
    {
        return $this->orders;
    }

    public function isEmpty()
    {
        return empty($this->fields);
    }
}
