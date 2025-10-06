<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Model\Service;

/**
 * Validation Result Implementation
 */
class ValidationResult implements ValidationResultInterface
{
    /**
     * @var bool
     */
    private bool $valid;

    /**
     * @var array
     */
    private array $errors;

    /**
     * @var array
     */
    private array $warnings;

    /**
     * Constructor
     *
     * @param bool $valid
     * @param array $errors
     * @param array $warnings
     */
    public function __construct(bool $valid, array $errors = [], array $warnings = [])
    {
        $this->valid = $valid;
        $this->errors = $errors;
        $this->warnings = $warnings;
    }

    /**
     * @inheritDoc
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @inheritDoc
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
