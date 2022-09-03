<?php

namespace Forecho\LaravelRepository\Contracts;

/**
 * Interface CriteriaInterface
 *
 * @author Anderson Andrade <contato@andersonandra.de>
 */
interface CriteriaInterface
{
    /**
     * Apply criteria in query repository
     *
     * @param    $model
     * @param  RepositoryInterface  $repository
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository);
}
