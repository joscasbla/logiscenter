<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Model\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;

/**
 * Rate Limiting Service
 *
 * Prevents API abuse with configurable limits
 */
class RateLimitingService
{
    private const CONFIG_PATH_RATE_LIMITS = 'logiscenter_immutable_quote/rate_limiting/';

    private const DEFAULT_LIMITS = [
        'create_quote' => 10,   // per hour
        'list_quotes' => 100,   // per hour
        'enable_quote' => 20,   // per hour
        'get_quote' => 200,     // per hour
    ];

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var DateTime
     */
    private DateTime $dateTime;

    /**
     * @var CacheServiceForImmutableQuotes
     */
    private CacheServiceForImmutableQuotes $cacheService;

    /**
     * Constructor
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DateTime $dateTime,
        CacheServiceForImmutableQuotes $cacheService
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->dateTime = $dateTime;
        $this->cacheService = $cacheService;
    }

    /**
     * Check rate limit for action
     *
     * @param string $action
     * @param int $userId
     * @param string $userType
     * @throws LocalizedException
     */
    public function checkRateLimit(string $action, int $userId, string $userType = 'customer'): void
    {
        $limit = $this->getLimit($action);
        $currentHour = $this->dateTime->gmtDate('Y-m-d H');
        $cacheKey = "rate_limit_{$userType}_{$userId}_{$action}_{$currentHour}";

        // Get current count from cache
        $currentCount = $this->cacheService->getQuoteStatus($cacheKey)['count'] ?? 0;

        if ($currentCount >= $limit) {
            throw new LocalizedException(
                __('Rate limit exceeded for action "%1". Limit: %2 per hour', $action, $limit)
            );
        }

        // Increment counter
        $this->cacheService->setQuoteStatus($cacheKey, ['count' => $currentCount + 1]);
    }

    /**
     * Get rate limit for action
     *
     * @param string $action
     * @return int
     */
    private function getLimit(string $action): int
    {
        $configPath = self::CONFIG_PATH_RATE_LIMITS . $action;
        $configuredLimit = $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE);

        return (int) ($configuredLimit ?: self::DEFAULT_LIMITS[$action] ?? 50);
    }
}
