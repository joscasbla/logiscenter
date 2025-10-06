<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Model\Service;

/**
 * Validation Result Interface
 */
interface ValidationResultInterface
{
    /**
     * Check if validation passed
     *
     * @return bool
     */
    public function isValid(): bool;

    /**
     * Get validation errors
     *
     * @return array
     */
    public function getErrors(): array;

    /**
     * Get validation warnings
     *
     * @return array
     */
    public function getWarnings(): array;
}
