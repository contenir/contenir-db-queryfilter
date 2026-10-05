<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

use Contenir\Db\Model\Exception\HydrationException;
use Contenir\Db\Model\Exception\IdentityConflictException;
use Contenir\Db\Model\Exception\PersistenceException;
use Contenir\Db\Model\Exception\TypeConversionException;
use Contenir\Db\Model\Repository;
use Override;
use PhpDb\Sql\Select;

/**
 * Adapts any contenir-db-model 2 `Repository` to QueryFilterTableInterface,
 * so a plain repository from `$em->getRepository(Product::class)` can be the
 * query filter's table without a subclass.
 *
 * Requires contenir/contenir-db-model ^2.0, which this package does not
 * install: add it to your project to use this class.
 *
 *     $queryFilter->setQueryFilterTable(new RepositoryTable($em->getRepository(Product::class)));
 *
 * @api
 */
final readonly class RepositoryTable implements QueryFilterTableInterface
{
    /**
     * @param Repository<object> $repository
     */
    public function __construct(
        private Repository $repository,
    ) {}

    /**
     * The repository's select over every mapped column.
     */
    #[Override]
    public function createSelect(): Select
    {
        return $this->repository->createSelect();
    }

    /**
     * Hydrated entities for the select, through the repository.
     *
     * @return list<object>
     *
     * @throws HydrationException
     * @throws IdentityConflictException
     * @throws PersistenceException
     * @throws TypeConversionException
     */
    #[Override]
    public function fetch(Select $select): array
    {
        return $this->repository->fetch($select);
    }
}
