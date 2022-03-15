<?php
namespace Concrete\Package\CommunityStoreApi\Api\Product;

use Concrete\Core\Page\Page;
use Concrete\Core\Url\Url;
use Concrete\Package\CommunityStore\Src\CommunityStore\Product\Product;
use Concrete\Package\CommunityStore\Src\CommunityStore\Product\ProductVariation\ProductVariation;
use League\Fractal\TransformerAbstract;

class ProductTransformer extends TransformerAbstract
{
    /**
     * Basic transforming of a product into an array
     *
     * @param Product $product
     * @return array
     */
    public function transform(Product $product)
    {
        $groups = $product->getGroups();

        $outputGroups = [];
        foreach($groups as $g) {
            $outputGroups[] = $g->getGroup()->getGroupName();
        }
        $primaryImageURL = false;
        $primaryImage = $product->getImageObj();

        if ($primaryImage) {
            $primaryImageURL = $primaryImage->getURL();
        }

        $secondaryUrls = [];
        $secondaryImages =  $product->getimagesobjects();

        foreach($secondaryImages as $imageObject) {
            $secondaryUrls[] = $imageObject->getURL();
        }

        $locations = $product->getLocationPages();

        $categoryPages = [];

        foreach($locations as $location) {
            $locationpage = Page::getByID($location->getCollectionID());
            if ($locationpage) {
                $categoryPages[] =
                    (object)[
                    'name'=>$locationpage->getCollectionName(),
                    'url'=>$locationpage->getCollectionLink()
                  ];
            }
        }

        $options = [];

        foreach ($product->getOptions() as $option) {
            $optionData = [
                'name'=>$option->getName(),
                'handle'=>$option->getHandle(),
                'type'=>$option->getType(),
                'required'=>$required = $option->getRequired()];

            if ($option->getType() == 'select') {
                $optionItems = $option->getOptionItems();

                if ($optionItems) {
                    $optionData['options'] = [];
                    foreach($optionItems as $optionItem) {
                        $option = [
                            'name'=>$optionItem->getName(),
                            'price_adjustment'=>$optionItem->getPriceAdjustment(),
                            'weight_adjustment'=>$optionItem->getWeightAdjustment()
                            ];
                        $optionData['options'][] = $option;
                    }
                }
            }

            $options[] = $optionData;

        }

        $variations = [];

        foreach($product->getVariations() as $variation) {

            $variationImage = $variation->getVariationImageObj();

            if ($variationImage) {
                $variationImage = $variationImage->getURL();
            }

            $options = $variation->getOptions();

            $variationOptionData = [];

            foreach($options as $optionItem) {
                $variationOptionItem = $optionItem->getOptionItem();
                $variationOptionData[] = [
                    'name'=>$variationOptionItem->getOption()->getName(),
                    'value'=>$variationOptionItem->getName()
                ];
            }

            $varationData = [
                'id' => $variation->getID(),
                'sku' => $variation->getVariationSKU(),
                'barcode' => $variation->getVariationBarcode(),
                'stock_unlimited' => $variation->isUnlimited(),
                'stock_level' => (float)$variation->getStockLevel(),
                'price' => $variation->getVariationPrice(),
                'wholesale_price' => $variation->getVariationWholesalePrice(),
                'cost_price' => $variation->getVariationCostPrice(),
                'sale_price' => $variation->getVariationSalePrice(),
                'primary_image'=> $variationImage,
                'options' =>$variationOptionData,
                'shipping' => [
                    'width' => $variation->getVariationWidth(),
                    'height' => $variation->getVariationHeight(),
                    'length' => $variation->getVariationLength(),
                    'number_items' => $variation->getVariationNumberItems()
                ]
            ];

            $variations[] = $varationData;
        }

        $data = [
            'id' => $product->getID(),
            'name' => $product->getName(),
            'sku' => $product->getSKU(),
            'barcode' => $product->getBarcode(),
            'active' => $product->isActive(),
            'stock_unlimited' => $product->isUnlimited(),
            'stock_level' => (float)$product->getStockLevel(),
            'short_description' => $product->getDescription(),
            'description' => $product->getDetail(),
            'brand' => $product->getManufacturer(),
            'price' => $product->getPrice(),
            'wholesale_price' => $product->getWholesalePriceValue(),
            'cost_price' => $product->getCostPrice(),
            'sale_price' => $product->getSalePriceValue(),
            'sale_start' => $product->getSaleStart() ? (array)$product->getSaleStart() : null,
            'sale_end' =>  $product->getSaleEnd() ? (array)$product->getSaleEnd() : null,
            'primary_image'=>$primaryImageURL,
            'additional_images'=>$secondaryUrls,
            'groups'=>$outputGroups,
            'categories'=> $categoryPages,
            'options'=>$options,
            'variations'=>$variations,
            'date_added' => (array)$product->getDateAdded(),
            'date_updated' => (array)$product->getDateUpdated(),
            'shipping' => [
                'shippable' => $product->isShippable(),
                'width' => $product->getWidth(),
                'height' => $product->getHeight(),
                'length' => $product->getLength(),
                'number_items' => $product->getNumberItems(),
                'stacked_height'=> $product->getStackedHeight(),
                'separate_ship' => $product->isSeparateShip()
            ]
        ];


        unset($data['date_added']['timezone_type']);
        unset($data['date_updated']['timezone_type']);

        unset($data['sale_start']['timezone_type']);
        unset($data['sale_end']['timezone_type']);

        return $data;
    }
}
