<?php


namespace Koabana\Model\Repository;

use Koabana\Database\BDDFactory;

abstract class AbstractRepository
{
    public function __construct(protected BDDFactory $bddFactory) {}
}
