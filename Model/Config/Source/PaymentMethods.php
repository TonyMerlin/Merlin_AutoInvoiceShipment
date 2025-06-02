<?php
/**
 * Class PaymentMethods
 *
 *
 * @category Merlin
 * @package  Merlin_AutoInvoiceShipment
 * @author   Merlin
 */
namespace Merlin\AutoInvoiceShipment\Model\Config\Source;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Payment\Model\Config;

/**
 * Class PaymentMethods
 *
 * PHP version 7
 *
 * @category Merlin
 * @package  Merlin_AutoInvoiceShipment
 * @author   Merlin 
 */
class PaymentMethods extends \Magento\Framework\DataObject 
    implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $_appConfigScopeConfigInterface;
    /**
     * @var Config
     */
    protected $_paymentModelConfig;
    
    /**
     * @param ScopeConfigInterface $appConfigScopeConfigInterface
     * @param Config               $paymentModelConfig
     */
    public function __construct(
        ScopeConfigInterface $appConfigScopeConfigInterface,
        Config $paymentModelConfig
    ) {

        $this->_appConfigScopeConfigInterface = $appConfigScopeConfigInterface;
        $this->_paymentModelConfig = $paymentModelConfig;
    }
    
    /**
     * 
     * @return array
     * Get all payment method collection
     */
    public function toOptionArray()
    {
        $payments = $this->_paymentModelConfig->getActiveMethods();
        $methods = array();
        foreach ($payments as $paymentCode => $paymentModel) {
            $paymentTitle = $this->_appConfigScopeConfigInterface
                ->getValue('payment/'.$paymentCode.'/title');
            $methods[$paymentCode] = array(
                'label' => $paymentTitle,
                'value' => $paymentCode
            );
        }
        return $methods;
    }
}
