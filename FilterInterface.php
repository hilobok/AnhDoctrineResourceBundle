<?php

namespace Anh\DoctrineResourceBundle;

interface FilterInterface
{
    public function getDefinition(array $parameters = array());
    public function getSortFields(array $parameters = array());
    public function getSortOrders(array $parameters = array());

    public function buildCriteria();
    public function buildSorting();
}
