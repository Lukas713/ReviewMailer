<?php

namespace Inchoo\ReviewMailer\Observer;

use Inchoo\StoreReview\Api\Data\StoreReviewInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SendEmail implements ObserverInterface
{
    const XML_PATH_EMAIL_RECIPIENT = 'trans_email/ident_general/email';
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;
    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    private $dataObjectFactory;
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;
    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository
     */
    private $customerRepository;
    /**
     * SendTicketEmail constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->escaper = $escaper;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->transportBuilder = $transportBuilder;
        $this->customerRepository = $customerRepository;
    }
    public function execute(Observer $observer)
    {
        if ($this->scopeConfig->getValue(
            'reviewMailer/email/enable',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )) {
            $test = $observer->getEvent()->getData('data');
            $subjectData = [StoreReviewInterface::TITLE => $test[StoreReviewInterface::TITLE], StoreReviewInterface::CONTENT => $test[StoreReviewInterface::CONTENT]];
            $customerId = $test[StoreReviewInterface::CUSTOMER];
            $customer = $this->customerRepository->getById($customerId);
            $sender = [
                'name' => $this->escaper->escapeHtml(ucfirst($customer->getFirstname()) . ' ' . ucfirst($customer->getLastname())),
                'email' => $this->escaper->escapeHtml($customer->getEmail())
            ];
            $template = $this->scopeConfig->getValue('reviewMailer/email/email_template', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $transport = $this->transportBuilder->setTemplateIdentifier($template)
                ->setTemplateOptions(
                    ['area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,]
                )
                ->setTemplateVars($subjectData)
                ->setFromByScope($sender)
                ->addTo(
                    $this->scopeConfig->getValue(
                        'reviewMailer/email/recipient_email',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    )
                )
                ->getTransport();
            $transport->sendMessage();

        }
        return null;
    }
}
