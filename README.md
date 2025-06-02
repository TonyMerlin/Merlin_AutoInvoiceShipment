# Merlin_AutoInvoiceShipment Extension
Magento 2 Auto Invoice & Shipment extension automates order invoicing and shipment creations after order payments are completed which makes order processing quicker.

##Support: 
version - 2.3.x, 2.4.x

##How to install Extension

1. Download the archive file.
2. Unzip the file
3. Create a folder [Magento_Root]/app/code/Merlin/AutoInvoiceShipment
4. Drop/move the unzipped files to directory '[Magento_Root]/app/code/Merlin/AutoInvoiceShipment'

#Enable Extension:
- php bin/magento module:enable Merlin_AutoInvoiceShipment
- php bin/magento setup:upgrade
- php bin/magento setup:di:compile
- php bin/magento setup:static-content:deploy
- php bin/magento cache:flush

#Disable Extension:
- php bin/magento module:disable Merlin_AutoInvoiceShipment
- php bin/magento setup:upgrade
- php bin/magento setup:di:compile
- php bin/magento setup:static-content:deploy
- php bin/magento cache:flush
