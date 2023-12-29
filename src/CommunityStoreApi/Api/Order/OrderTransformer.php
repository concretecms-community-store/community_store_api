<?php

namespace Concrete\Package\CommunityStoreApi\Api\Order;

use Concrete\Core\User\User;
use League\Fractal\TransformerAbstract;
use Concrete\Core\Support\Facade\Config;
use Concrete\Core\Attribute\Key\Category;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order;

class OrderTransformer extends TransformerAbstract
{
    /**
     * Transforming of a order into an array
     *
     * @param Order $order
     * @return array
     */
    public function transform(Order $order)
    {

        $defaults = [
            'email',
            'billing_first_name',
            'billing_last_name',
            'billing_phone',
            'billing_company',
            'billing_address',
            'vat_number',
            'shipping_first_name',
            'shipping_last_name',
            'shipping_company',
            'shipping_address'
        ];


        $orderItems = $order->getOrderItems();

        $items = [];

        foreach ($orderItems as $orderItem) {

            $itemOptions = [];

            $options = $orderItem->getProductOptions();
            if ($options) {
                foreach ($options as $option) {
                    $itemOptions[] = [
                        'name'=>$option['oioKey'],
                        'handle'=> isset($option['oioHandle']) ? $option['oioHandle'] : '',
                        'value'=> h($option['oioValue']) ? h($option['oioValue']) :  t('None')];
                }
            }

            $itemData = [
                'id' => $orderItem->getID(),
                'name' => $orderItem->getProductName(),
                'sku' => $orderItem->getSKU(),
                'quantity' => $orderItem->getQuantity(),
                'price' => (float)$orderItem->getPricePaid(),
                'options'=> $itemOptions
            ];

            // if File Uploads add-in installed
            if (class_exists('Concrete\Package\CommunityStoreFileUploads\Src\CommunityStore\Order\OrderItemFile')) {

                $itemFiles = \Concrete\Package\CommunityStoreFileUploads\Src\CommunityStore\Order\OrderItemFile::getAllByOrderItem($orderItem);

                if (!empty($itemFiles)) {
                    $itemData['uploads'] = [];

                    foreach($itemFiles as $itemFile) {
                        $file = $itemFile->getFile();

                        if ($file) {
                            $itemData['uploads'][] =  (string)$file->getURL();
                        }
                    }
                }

            }

            $items[] = $itemData;
        }


        $username = false;

        $customerID = $order->getCustomerID();

        if ($customerID) {
            $user = User::getByUserID($order->getCustomerID());

            if ($user) {
                $username = $user->getUserName();
            }
        }

        $attributes = [];

        $category = Category::getByHandle('store_order')->getController();
        $catgegoryAttributes = $category->getList();

        foreach ($catgegoryAttributes as $att) {
            $handle = $att->getAttributeKeyHandle();
            $type = $att->getAttributeType();

            $attType = $type->getAttributeTypeHandle();

            if (!in_array($handle, $defaults)) {
                $value = $order->getAttribute($handle);

                if ($attType == 'image_file') {
                    if ($value) {
                        $value = $value->getURL();
                    }
                }

                $attributes[] =
                    [
                        'handle'=>$handle,
                        'name'=>$att->getAttributeKeyName(),
                        'value'=>$value,
                    ];

            }
        }



        $data = [
            'id' => (int)$order->getOrderID(),
            'date_placed' => (array)$order->getOrderDate(),
            'total' => (float)$order->getTotal(),
            'payment_method' => $order->getPaymentMethodName(),
            'payment_date' => (array)$order->getPaid(),
            'payment_reference' => $order->getTransactionReference(),
            'shipping_method' => $order->getShippingMethodName(),
            'fulfilment' => [
               'status'=>$order->getStatus(),
               'handle'=>$order->getStatusHandle(),
               'tracking_id' => $order->getTrackingID(),
               'tracking_code' => $order->getTrackingCode(),
               'tracking_url'=>$order->getTrackingURL(),
            ],
            'locale' => $order->getLocale(),
            'customer' => [
                'email' => $order->getAttribute('email'),
                'username' => $username,
                'billing' => [
                    'phone' => $order->getAttribute('billing_phone'),
                    'first_name' => $order->getAttribute('billing_first_name'),
                    'last_name' => $order->getAttribute('billing_last_name'),
                    'company' => $order->getAttribute('billing_company'),
                    'address' => $order->getAttribute('billing_address'),
                    'vat_number'=>$order->getAttribute('vat_number')
                ],
                'shipping' => [
                    'first_name' => $order->getAttribute('shipping_first_name'),
                    'last_name' => $order->getAttribute('shipping_last_name'),
                    'company' => $order->getAttribute('shipping_company'),
                    'address' => $order->getAttribute('shipping_address'),

                ],
            ],
            'items' => $items,
            'attributes' => $attributes
        ];

        if ($order->getRefunded()) {
            $data['refunded'] = [
                'date' => (array) $order->getRefunded(),
                'reason' => (string) $order->getRefundReason(),
            ];
            unset($data['refunded']['date']['timezone_type']);
        }

        if (!Config::get('community_store.vat_number')) {
            unset($data['customer']['billing']['vat_number']);
        }

        unset($data['date_placed']['timezone_type']);
        unset($data['payment_date']['timezone_type']);


        return $data;
    }
}
