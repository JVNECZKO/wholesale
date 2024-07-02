<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class WholesalePrice extends Module
{
    public function __construct()
    {
        $this->name = 'wholesaleprice';
        $this->tab = 'pricing';
        $this->version = '1.0.0';
        $this->author = 'YourName';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Wholesale Price Display');
        $this->description = $this->l('Displays wholesale price for admin users.');

        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('displayProductAdditionalInfo');
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->deleteDatabaseTable();
    }

    protected function createDatabaseTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "wholesale_prices` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `reference` VARCHAR(64) NOT NULL,
            `wholesale_price` DECIMAL(20,6) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";
        return Db::getInstance()->execute($sql);
    }

    protected function deleteDatabaseTable()
    {
        $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "wholesale_prices`";
        return Db::getInstance()->execute($sql);
    }

    public function hookDisplayProductAdditionalInfo($params)
    {
        $this->logDebug('hookDisplayProductAdditionalInfo called');

        if (!isset($params['product']) || !isset($params['product']['id'])) {
            $this->logDebug('Product ID not set in params');
            return '';
        }

        $product = new Product((int)$params['product']['id']);
        $reference = $product->reference;

        // Check if the product has combinations
        $idProductAttribute = Tools::getValue('id_product_attribute');
        if ($idProductAttribute) {
            $combination = new Combination($idProductAttribute);
            $reference = $combination->reference;
        }

        if (empty($reference)) {
            $this->logDebug('Product reference is empty or not set');
            return '';
        }

        $wholesalePrice = $this->getWholesalePriceByReference($reference);

        $this->logDebug('Product ID: ' . $params['product']['id']);
        $this->logDebug('Product Reference: ' . $reference);
        $this->logDebug('Wholesale Price: ' . $wholesalePrice);

        // Check if the user is logged in and is an employee (admin)
        if ($this->context->employee && $this->context->employee->id && $wholesalePrice) {
            $this->context->smarty->assign(array(
                'wholesale_price' => $wholesalePrice,
            ));
            $this->logDebug('Displaying wholesale price');
            $output = $this->display(__FILE__, 'views/templates/hook/displayprice.tpl');
            $this->logDebug('Output from template: ' . $output);
            return $output;
        }

        return '';
    }

    protected function getWholesalePriceByReference($reference)
    {
        $sql = 'SELECT `wholesale_price` FROM `' . _DB_PREFIX_ . 'wholesale_prices` WHERE `reference` = "' . pSQL($reference) . '"';
        $result = Db::getInstance()->getValue($sql);
        $this->logDebug('SQL Query: ' . $sql);
        $this->logDebug('SQL Result: ' . $result);
        return $result;
    }

    protected function logDebug($message)
    {
        $file = _PS_ROOT_DIR_ . '/log/wholesaleprice.log';
        if (!file_exists($file)) {
            file_put_contents($file, ''); // Create the file if it does not exist
        }
        $current = file_get_contents($file);
        $current .= date('Y-m-d H:i:s') . ' - ' . $message . "\n";
        file_put_contents($file, $current);
    }
}
?>
