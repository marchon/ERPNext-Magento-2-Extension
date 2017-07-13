<?php //-->

namespace Redscript\Erpnext\Observer;

class Erpnextproduct implements \Magento\Framework\Event\ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //get the dispatched data
        $product = $observer->getProduct()->getData();
        
        //require the library
        require(dirname(__FILE__).'/lib/FrappeClient.php');

        //init the class
        $client = new \FrappeClient();

        //save the product data
        $this->_saveProduct($client, $product);

        //if the quantity is greater than 0
        if($product['stock_data']['qty'] > 0) {
            //save the stocks
            $this->_saveStocks($client, $product);
        }

        //insert the images if not empty
        if(isset($product['media_gallery']['images']) 
        && !empty($product['media_gallery']['images'])) {
            //get the product image directory
            $productDir = dirname(__FILE__).'/../../../../../pub/media/catalog/product';

            //images can be found here /pub/media/catalog/product
            foreach($product['media_gallery']['images'] as $image) {
                //check if the file exist
                if(!file_exists($productDir.$image['file'])) {
                    continue;
                }

                //save the image
                $this->_saveImage($client, $image, $product['sku']);
            }
        }
    }

    private function _saveProduct($client, $product)
    {
        $data = array(
            'magento_id'        => $product['entity_id'],
            'item_code'         => $product['sku'],
            'item_name'         => $product['name'],
            'item_group'        => 'Products',
            'stock_uom'         => 'UNIT',
            'is_stock_item'     => $product['stock_data']['is_in_stock'],
            'valuation_rate'    => 1,

            'store_id'          => $product['store_id'],
            'type_id'           => $product['type_id'],
            'attribute_set_id'  => $product['attribute_set_id'],
            'visibility'        => $product['visibility'],
            'tax_class_id'      => $product['tax_class_id'],
            'categories'        => $product['category_ids'],
            'product'           => array(
                'id'        => $product['entity_id'],
                'name'      => $product['name'],
                'sku'       => $product['sku'],
                'price'     => $product['price'],
                'status'    => $product['status'],
                'weight'    => $product['weight'],
                'new'       => array(
                    'news_from_date'    => $product['news_from_date'],
                    'news_to_date'      => $product['news_to_date']),
                'description'           => $product['description'],
                'short_description'     => $product['short_description']),
            'stock'             => array(
                'qty'       => $product['stock_data']['qty'],
                'in_stock'  => $product['stock_data']['is_in_stock']),
            'created_at'    => $product['created_at']);

        //insert the product
        $client->insert('Item', $data);

        return $this;
    }

    private function _saveImage($client, $image, $sku)
    {
        //get the host
        $host       = $_SERVER['HTTP_HOST'];
        $protocol   = $_SERVER['REQUEST_SCHEME'];

        //get the filename
        $filename   = explode('/', $image['file']);
        $name       = end($filename);

        //get the full url
        $uri = $protocol.'://'.$host.'/pub/media/catalog/product';

        //set the image data
        $fileContent = array(
            'file_name'             => $name,
            'file_url'              => $uri.$image['file'],
            'attached_to_name'      => $sku,
            'attached_to_doctype'   => 'Item',
            'is_private'            => 1);

        //add the image
        $client->insert('File', $fileContent);

        return $this;
    }

    private function _saveStocks($client, $product)
    {
        $stock = array(
            'doctype'           => 'Stock Entry',
            'title'             => 'Material Receipt',
            'to_warehouse'      => 'Stores - NB',
            'docstatus'         => 1,
            'company'           => 'NexusBond ASIA Inc.',
            'purpose'           => 'Material Receipt',
            'request_from'      => 'MAGENTO',
            'items'             => array(
                array(
                    'item_code'             => $product['sku'],
                    'qty'                   => $product['stock_data']['qty'],
                    't_warehouse'           => 'Stores - NB',
                    'basic_amount'          => 0.0,
                    'cost_center'           => 'Main - NB',
                    'stock_uom'             => 'Kg',
                    'conversion_factor'     => 1.0,
                    'docstatus'             => 1,
                    'uom'                   => 'Kg',
                    'basic_rate'            => 0.0,
                    'doctype'               => 'Stock Entry Detail',
                    'expense_account'       => 'Stock Adjustment - NB',
                    'parenttype'            => 'Stock Entry',
                    'parentfield'           => 'items')
            )
        );

        //insert the STOCK
        $client->insert('Stock Entry', $stock);
        
        return $this;
    }
}