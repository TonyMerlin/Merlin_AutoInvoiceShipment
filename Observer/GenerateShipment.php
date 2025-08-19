<?php
/**
 * Class GenerateShipment
 *
 * @category Magento 2
 * @package  Merlin_AutoInvoiceShipment
 * @author   Merlin admin@sky-merlin.co.uk
 */
namespace Merlin\AutoInvoiceShipment\Observer;
class GenerateShipment implements \Magento\Framework\Event\ObserverInterface
{      
        public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Convert\OrderFactory $convertOrderFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\Order\Email\Sender\ShipmentSender $shipmentSender,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderConverter = $convertOrderFactory->create();
        $this->transactionFactory = $transactionFactory;
        $this->messageManager = $messageManager;
        $this->shipmentSender = $shipmentSender;
        $this->invoiceSender=$invoiceSender;
        $this->invoiceService=$invoiceService;
        $this->scopeConfig=$scopeConfig;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
    	$enable_extension= $this->scopeConfig->getValue('invoice_and_shipment/general/invoiceShipment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

    	if($enable_extension){
            $orderData = $observer->getEvent()->getOrder();
            $orderId = $orderData->getId();

            //get payment method at the time of order place
            $paymentMethod = $orderData->getPayment()->getMethod();
            $list = $this->scopeConfig->getValue("invoice_and_shipment/general/autoinvship_general_payment_methods",
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $list !== null ? explode(',', $list) : [];
            $selected_method_list = explode(",", $list);

            if (in_array($paymentMethod, $selected_method_list))
            {
            try {
                $order = $this->orderRepository->get($orderId);
                if (!$order->getId()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('The order no longer exists.'));
                }
                $enable_shipment= $this->scopeConfig->getValue('invoice_and_shipment/general/shipmentgenerate', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                if($enable_shipment){
                    /* check shipment exist for order or not */
                    if ($order->canShip())
                    {
                        // Initialize the order shipment object
                        $shipment = $this->orderConverter->toShipment($order);
                        foreach ($order->getAllItems() AS $orderItem) {
                            // Check if order item has qty to ship or is order is virtual
                            if (! $orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                                continue;
                            }
                            $qtyShipped = $orderItem->getQtyToShip();
                            // Create shipment item with qty
                            $shipmentItem = $this->orderConverter->itemToShipmentItem($orderItem)->setQty($qtyShipped);
                            // Add shipment item to shipment
                            $shipment->addItem($shipmentItem);
                        }
                        // Register shipment
                        $shipment->register();
                        $shipment->getOrder()->setIsInProcess(true);

                        try {
                            $transaction = $this->transactionFactory->create()->addObject($shipment)
                                ->addObject($shipment->getOrder())
                                ->save();
                                $shipmentId = $shipment->getIncrementId();
                        } catch (\Exception $e) {
                            $this->messageManager->addError(__('We can\'t generate shipment.'));
                        }

                        if ($shipment) {
                            try {
                                $this->shipmentSender->send($shipment);
                            } catch (\Exception $e) {
                                $this->messageManager->addError(__('We can\'t send the shipment right now.'));
                            }
                        }
                    }
                }
                $enable_invoice= $this->scopeConfig->getValue('invoice_and_shipment/general/invoicegenerate', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                if($enable_invoice){
                    if ($order->canInvoice()) {
                        $invoice = $this->invoiceService->prepareInvoice($order);
                        $invoice->register();
                        $invoice->save();

                        $transactionSave = 
                            $this->transactionFactory->create()
                                ->addObject($invoice)
                                ->addObject($invoice->getOrder());
                        $transactionSave->save();
                        $this->invoiceSender->send($invoice);

                        $order->addCommentToStatusHistory(
                            __('Notified customer about invoice creation #%1.', $invoice->getId())
                        )->setIsCustomerNotified(true)->save();
                    }
                }
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
            }
            return true;
        }
    }
}
