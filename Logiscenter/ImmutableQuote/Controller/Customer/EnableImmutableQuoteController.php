<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Controller\Customer;

use Logiscenter\ImmutableQuote\Api\ImmutableQuoteManagementInterface;
use Logiscenter\ImmutableQuote\Model\Service\RateLimitingService;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Enable Immutable Quote Controller
 */
class EnableImmutableQuoteController implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private JsonFactory $jsonFactory;

    /**
     * @var RedirectFactory
     */
    private RedirectFactory $redirectFactory;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var ImmutableQuoteManagementInterface
     */
    private ImmutableQuoteManagementInterface $immutableQuoteManagement;

    /**
     * @var RateLimitingService
     */
    private RateLimitingService $rateLimitingService;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var MessageManagerInterface
     */
    private MessageManagerInterface $messageManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructor
     */
    public function __construct(
        JsonFactory $jsonFactory,
        RedirectFactory $redirectFactory,
        CustomerSession $customerSession,
        ImmutableQuoteManagementInterface $immutableQuoteManagement,
        RateLimitingService $rateLimitingService,
        RequestInterface $request,
        MessageManagerInterface $messageManager,
        LoggerInterface $logger
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->redirectFactory = $redirectFactory;
        $this->customerSession = $customerSession;
        $this->immutableQuoteManagement = $immutableQuoteManagement;
        $this->rateLimitingService = $rateLimitingService;
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
    }

    /**
     * Execute action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $isAjax = $this->request->isAjax();

        if (!$this->customerSession->isLoggedIn()) {
            if ($isAjax) {
                $result = $this->jsonFactory->create();
                return $result->setData([
                    'success' => false,
                    'message' => __('Please log in to continue.')
                ]);
            }

            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('customer/account/login');
        }

        try {
            $quoteId = (int) $this->request->getParam('quote_id');
            $customerId = (int) $this->customerSession->getCustomerId();

            if (!$quoteId) {
                throw new \InvalidArgumentException('Quote ID is required');
            }

            // Rate limiting
            $this->rateLimitingService->checkRateLimit('enable_quote', $customerId);

            // Enable quote
            $immutableQuote = $this->immutableQuoteManagement->enableQuote($quoteId, $customerId);

            $successMessage = __('Quote #%1 has been enabled successfully.', $quoteId);

            if ($isAjax) {
                $result = $this->jsonFactory->create();
                return $result->setData([
                    'success' => true,
                    'message' => $successMessage,
                    'quote_data' => [
                        'quote_id' => $immutableQuote->getQuoteId(),
                        'enabled_at' => $immutableQuote->getEnabledAt()
                    ]
                ]);
            }

            $this->messageManager->addSuccessMessage($successMessage);
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('immutable-quotes');

        } catch (\Exception $exception) {
            $errorMessage = __('Failed to enable quote: %1', $exception->getMessage());

            $this->logger->error('Failed to enable immutable quote', [
                'quote_id' => $this->request->getParam('quote_id'),
                'customer_id' => $this->customerSession->getCustomerId(),
                'error' => $exception->getMessage()
            ]);

            if ($isAjax) {
                $result = $this->jsonFactory->create();
                return $result->setData([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }

            $this->messageManager->addErrorMessage($errorMessage);
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('immutable-quotes');
        }
    }
}
