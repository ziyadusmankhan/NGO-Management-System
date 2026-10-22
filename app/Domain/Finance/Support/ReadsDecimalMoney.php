<?php

namespace App\Domain\Finance\Support;

trait ReadsDecimalMoney
{
    public function newModelQuery()
    {
        $query = parent::newModelQuery();
        if ($this->getConnection()->getDriverName() === 'sqlite') {
            $query->select($this->getTable().'.*');
            foreach ($this->moneyColumns ?? ['amount'] as $column) {
                $wrapped = $this->getConnection()->getQueryGrammar()->wrap($this->qualifyColumn($column));
                $query->selectRaw("CASE WHEN {$wrapped} IS NULL THEN NULL ELSE printf('%.2f', {$wrapped}) END AS {$column}");
            }
        }

        return $query;
    }
}
