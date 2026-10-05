<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Db;

use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\ParameterContainer;
use Override;

/**
 * Prepared statement that logs its SQL on execution and returns fixed rows.
 */
final class RecordingStatement implements StatementInterface
{
    private string $sql = '';

    private ParameterContainer $parameters;

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function __construct(
        private readonly SqlLog $log,
        private readonly array $rows,
    ) {
        $this->parameters = new ParameterContainer();
    }

    /**
     * @param mixed $parameters
     */
    #[Override]
    public function execute($parameters = null): ArrayResult
    {
        $this->log->record($this->sql);

        return new ArrayResult($this->rows);
    }

    #[Override]
    public function getParameterContainer(): ParameterContainer
    {
        return $this->parameters;
    }

    #[Override]
    public function getResource(): null
    {
        return null;
    }

    #[Override]
    public function getSql(): string
    {
        return $this->sql;
    }

    #[Override]
    public function isPrepared(): bool
    {
        return true;
    }

    /**
     * @param string|null $sql
     */
    #[Override]
    public function prepare($sql = null): self
    {
        return $this;
    }

    #[Override]
    public function setParameterContainer(ParameterContainer $parameterContainer): self
    {
        $this->parameters = $parameterContainer;

        return $this;
    }

    /**
     * @param string $sql
     */
    #[Override]
    public function setSql($sql): self
    {
        $this->sql = $sql;

        return $this;
    }
}
