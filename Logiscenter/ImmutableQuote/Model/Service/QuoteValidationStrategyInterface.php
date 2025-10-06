<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Model\Service;

use Magento\Quote\Api\Data\CartInterface;

/**
 * Quote Validation Strategy Interface
 * 
 * Strategy Pattern for different validation rules
 */
interface QuoteValidationStrategyInterface
{
    /**
     * Validate quote
     *
     * @param CartInterface $quote
     * @return ValidationResultInterface
     */
    public function validate(CartInterface $quote): ValidationResultInterface;
}
