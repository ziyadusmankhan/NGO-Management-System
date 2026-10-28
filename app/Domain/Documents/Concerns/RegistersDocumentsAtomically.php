<?php

namespace App\Domain\Documents\Concerns;

trait RegistersDocumentsAtomically
{
    public function save(array $options = [])
    {
        return $this->getConnection()->transaction(fn () => parent::save($options));
    }
}
