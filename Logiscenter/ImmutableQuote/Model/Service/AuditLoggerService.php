<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Model\Service;

use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\HTTP\Header;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Customer\Model\Session as CustomerSession;
use Psr\Log\LoggerInterface;

/**
 * Audit Logger Service
 * 
 * Comprehensive audit logging with user context, IP, timestamps
 */
class AuditLoggerService
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var RemoteAddress
     */
    private RemoteAddress $remoteAddress;

    /**
     * @var Header
     */
    private Header $httpHeader;

    /**
     * @var AdminSession
     */
    private AdminSession $adminSession;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * Constructor
     */
    public function __construct(
        LoggerInterface $logger,
        RemoteAddress $remoteAddress,
        Header $httpHeader,
        AdminSession $adminSession,
        CustomerSession $customerSession
    ) {
        $this->logger = $logger;
        $this->remoteAddress = $remoteAddress;
        $this->httpHeader = $httpHeader;
        $this->adminSession = $adminSession;
        $this->customerSession = $customerSession;
    }

    /**
     * Log action with complete context
     *
     * @param string $action
     * @param array $context
     * @return void
     */
    public function logAction(string $action, array $context = []): void
    {
        $fullContext = array_merge([
            'action' => $action,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $this->remoteAddress->getRemoteAddress(),
            'user_agent' => $this->httpHeader->getHttpUserAgent(),
            'admin_user_id' => $this->getAdminUserId(),
            'customer_id' => $this->getCustomerId(),
            'session_id' => session_id()
        ], $context);

        $this->logger->info('Immutable Quote Action', $fullContext);
    }

    /**
     * Get current context for events
     *
     * @return array
     */
    public function getCurrentContext(): array
    {
        return [
            'ip_address' => $this->remoteAddress->getRemoteAddress(),
            'user_agent' => $this->httpHeader->getHttpUserAgent(),
            'admin_user_id' => $this->getAdminUserId(),
            'customer_id' => $this->getCustomerId(),
            'session_id' => session_id(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get current admin user ID
     *
     * @return int|null
     */
    private function getAdminUserId(): ?int
    {
        if ($this->adminSession->isLoggedIn()) {
            return (int) $this->adminSession->getUser()->getId();
        }
        return null;
    }

    /**
     * Get current customer ID
     *
     * @return int|null
     */
    private function getCustomerId(): ?int
    {
        if ($this->customerSession->isLoggedIn()) {
            return (int) $this->customerSession->getCustomerId();
        }
        return null;
    }
}
