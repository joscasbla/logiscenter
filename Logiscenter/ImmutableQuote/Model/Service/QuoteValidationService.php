<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Model\Service;

use Magento\Quote\Api\Data\CartInterface;

/**
 * Quote Validation Service
 *
 * Validates if a quote can be made immutable using Strategy Pattern
 */
class QuoteValidationService
{
    /**
     * @var QuoteValidationStrategyInterface[]
     */
    private array $validationStrategies;

    /**
     * Constructor
     *
     * @param array $validationStrategies
     */
    public function __construct(array $validationStrategies = [])
    {
        $this->validationStrategies = $validationStrategies;
    }

    /**
     * Validate if quote can be made immutable
     *
     * @param CartInterface $quote
     * @return array
     */
    public function validateForImmutability(CartInterface $quote): array
    {
        $errors = [];

        // Basic validations
        if (!$quote->getId()) {
            $errors[] = 'Quote ID is required';
        }

        if (!$quote->getCustomerId()) {
            $errors[] = 'Customer ID is required';
        }

        if ($quote->getItemsCount() === 0) {
            $errors[] = 'Quote must have at least one item';
        }

        if (!$quote->getBillingAddress() || !$quote->getBillingAddress()->getFirstname()) {
            $errors[] = 'Billing address is required';
        }

        // Strategy-based validations
        foreach ($this->validationStrategies as $strategy) {
            try {
                $result = $strategy->validate($quote);
                if (!$result->isValid()) {
                    $errors = array_merge($errors, $result->getErrors());
                }
            } catch (\Exception $exception) {
                $errors[] = 'Validation error: ' . $exception->getMessage();
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
