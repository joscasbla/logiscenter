<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Controller\Customer;

use Logiscenter\ImmutableQuote\Api\ImmutableQuoteRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

/**
 * Customer Immutable Quotes List Controller
 */
class CustomerImmutableQuotesController implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    private PageFactory $resultPageFactory;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var RedirectFactory
     */
    private RedirectFactory $redirectFactory;

    /**
     * @var ImmutableQuoteRepositoryInterface
     */
    private ImmutableQuoteRepositoryInterface $immutableQuoteRepository;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructor
     */
    public function __construct(
        PageFactory $resultPageFactory,
        CustomerSession $customerSession,
        RedirectFactory $redirectFactory,
        ImmutableQuoteRepositoryInterface $immutableQuoteRepository,
        RequestInterface $request,
        LoggerInterface $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->redirectFactory = $redirectFactory;
        $this->immutableQuoteRepository = $immutableQuoteRepository;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * Execute action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        if (!$this->customerSession->isLoggedIn()) {
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('customer/account/login');
        }

        try {
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->set(__('My Immutable Quotes'));

            return $resultPage;

        } catch (\Exception $exception) {
            $this->logger->error('Error loading immutable quotes page', [
                'error' => $exception->getMessage(),
                'customer_id' => $this->customerSession->getCustomerId()
            ]);

            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('customer/account');
        }
    }
}
