<?php
namespace Concrete\Package\CommunityStoreApi\Api\Product;

use Carbon\Carbon;
use Concrete\Core\Http\Request;
use League\Fractal\Resource\Item;
use Concrete\Core\Api\ApiController;
use League\Fractal\Resource\Collection;
use Concrete\Core\Application\Application;
use Concrete\Core\Search\Pagination\PaginationFactory;
use League\Fractal\Pagination\PagerfantaPaginatorAdapter;
use Concrete\Package\CommunityStore\Src\CommunityStore\Product\Product;
use Concrete\Package\CommunityStore\Src\CommunityStore\Product\ProductVariation\ProductVariation;

class ProductsController extends ApiController
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Request
     */
    protected $request;

    public function __construct(Application $app, Request $request)
    {
        $this->app = $app;
        $this->request = $request;
    }

    /**
     * Return detailed information about a product.
     *
     * @param $pID
     *
     * @return \League\Fractal\Resource\Item|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function read($pID)
    {
        $pID = (int) $pID;
        $product = Product::getByID($pID);
        if (!$product) {
            return $this->error(t('Product not found'), 404);
        }

        return $this->transform($product, new ProductTransformer());
    }

    /**
     * Write to a product.
     *
     * @param $pID
     *
     * @return \League\Fractal\Resource\Item|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function write($pID) {
        $pID = (int) $pID;
        $product = Product::getByID($pID);
        if (!$product) {
            return $this->error(t('Product not found'), 404);
        }

        $data = json_decode($this->request->getContent(), true);

        if (!$data) {
            return $this->error(t('Bad Request'), 400);
        }

        if (isset($data['data']['stock_level'])) {
            $product->setStockLevel($data['data']['stock_level']);
        }

        if (isset($data['data']['stock_unlimited'])) {
            $product->setIsUnlimited((bool)$data['data']['stock_unlimited']);
        }

        $product->save();

        return $this->transform($product, new ProductTransformer());
    }



    /**
     * Return all products.
     *
     * @param $pID
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function all()
    {
        $productsList = new ProductList();

        $paging = $this->request->get('paging');

        if (!$paging) {
            $paging = 20;
        }

        $filter = $this->request->get('filter');

        if ($filter) {
            $filtervars = explode(' ', $filter);

            if ($filtervars[0] == 'date_updated' || $filtervars[0] == 'date_added' ) {
                if (isset($filtervars[2])) {
                    $filterDate = trim($filtervars[2], "'");

                    $field = 'pDateUpdated';

                    if ($filtervars[0] == 'date_added') {
                        $field = 'pDateAdded';
                    }

                    $comparison = '>';

                    if ($filtervars[1] == 'lt') {
                        $comparison = '<';
                    }

                    if ($filtervars[1] == 'eq') {
                        $comparison = '=';
                    }

                    if ($filterDate) {
                        $date = Carbon::parse($filterDate);
                        $productsList->getQueryObject()->andWhere($field . ' ' . $comparison .' "' . $date . '"');
                    }
                }
            }
        }

        $productsList->setItemsPerPage($paging);
        $productsList->setActiveOnly(false);
        $productsList->setShowOutOfStock(true);
        $productsList->setSortBy('date');
        $productsList->setSortByDirection('desc');

        $factory = new PaginationFactory($this->app->make(Request::class));
        $paginator = $factory->createPaginationObject($productsList);
        $products = $paginator->getCurrentPageResults();

        $fanta = new PagerfantaPaginatorAdapter($paginator, function (int $page)  {
            $path = $this->request->getUri();
            $pathInfo = parse_url($path);
            $queryString = $pathInfo['query'];
            parse_str($queryString, $queryArray);
            $queryArray['page'] = $page;
            $newQueryStr = http_build_query($queryArray);

            return $pathInfo['scheme'] . '://' . $pathInfo['host']  . $pathInfo['path'] . '?' . $newQueryStr;
        });

        $resource = new Collection($products, new ProductTransformer);
        $resource->setPaginator($fanta);

        return $resource;
    }


    public function stockLevelReadSku($sku) {
        $db = $this->app->make('database')->connection();

        $product = false;
        $variation = false;

        if ($sku) {
            $results = $db->query('SELECT pID FROM CommunityStoreProducts WHERE pSKU = ?', [ $sku ]);

            while ($value = $results->fetch()) {
                $pID = (int)$value['pID'];
            }

            if ($pID) {
                $product = Product::getByID($pID);
            }

            $results = $db->query('SELECT pvID FROM CommunityStoreProductVariations WHERE pvSKU = ?', [ $sku ]);

            while ($value = $results->fetch()) {
                $pvID = (int)$value['pvID'];
            }

            if ($pvID) {
                $variation = ProductVariation::getByID($pvID);
            }
        }

        if (!$product && !$variation) {
            return $this->error(t('SKU not found'), 404);
        }

        if ($product) {
            $resource = new Item($product, function (Product $product) use ($sku) {
                return [
                    'id' => $product->getID(),
                    'sku' => $sku,
                    'name' => $product->getName(),
                    'stock_level' => (float)$product->getStockLevel(),
                    'stock_unlimited' => $product->isUnlimited()
                ];
            });
        }

        if ($variation) {
            $resource = new Item($variation, function (ProductVariation $variation) use ($sku) {
                return [
                    'id' => $variation->getID(),
                    'product_id' => $variation->getProduct()->getID(),
                    'sku' => $sku,
                    'name' => $variation->getProduct()->getName(),
                    'stock_level' => (float)$variation->getStockLevel(),
                    'stock_unlimited' => $variation->isUnlimited(),
                    'variation' =>true
                ];
            });
        }

        return $resource;
    }

    public function stockLevelWriteSku($sku) {
        $db = $this->app->make('database')->connection();

        $product = false;
        $variation = false;

        if ($sku) {
            $results = $db->query('SELECT pID FROM CommunityStoreProducts WHERE pSKU = ?', [ $sku ]);

            while ($value = $results->fetch()) {
                $pID = (int)$value['pID'];
            }

            if ($pID) {
                $product = Product::getByID($pID);
            }

            $results = $db->query('SELECT pvID FROM CommunityStoreProductVariations WHERE pvSKU = ?', [ $sku ]);

            while ($value = $results->fetch()) {
                $pvID = (int)$value['pvID'];
            }

            if ($pvID) {
                $variation = ProductVariation::getByID($pvID);
            }
        }

        if (!$product && !$variation) {
            return $this->error(t('Product not found'), 404);
        }

        $data = json_decode($this->request->getContent(), true);

        if (!$data) {
            return $this->error(t('Bad Request'), 400);
        }

        if ($product) {
            if (isset($data['data']['stock_level'])) {
                $product->setStockLevel($data['data']['stock_level']);
            }

            if (isset($data['data']['stock_unlimited'])) {
                $product->setIsUnlimited((bool)$data['data']['stock_unlimited']);
            }

            $product->save();
        }

        if ($variation) {
            if (isset($data['data']['stock_level'])) {
                $variation->setVariationStockLevel($data['data']['stock_level']);
            }

            if (isset($data['data']['stock_unlimited'])) {
                $variation->setVariationIsUnlimited((bool)$data['data']['stock_unlimited']);
            }

            $variation->save();
        }

        if ($product) {
            $resource = new Item($product, function (Product $product) use ($sku) {
                return [
                    'id' => $product->getID(),
                    'sku' => $sku,
                    'name' => $product->getName(),
                    'stock_level' => (float)$product->getStockLevel(),
                    'stock_unlimited' => $product->isUnlimited()
                ];
            });
        }

        if ($variation) {
            $resource = new Item($variation, function (ProductVariation $variation) use ($sku) {
                return [
                    'id' => $variation->getID(),
                    'product_id' => $variation->getProduct()->getID(),
                    'sku' => $sku,
                    'name' => $variation->getProduct()->getName(),
                    'stock_level' => (float)$variation->getStockLevel(),
                    'stock_unlimited' => $variation->isUnlimited(),
                    'variation' =>true
                ];
            });
        }

        return $resource;

    }

    public function stockLevelReadVariation($id) {
        $variation = false;

        if ($id) {
            $variation = ProductVariation::getByID($id);
        }

        if (!$variation) {
            return $this->error(t('Variation not found'), 404);
        }

        if ($variation) {
            $resource = new Item($variation, function (ProductVariation $variation)  {
                return [
                    'id' => $variation->getID(),
                    'product_id' => $variation->getProduct()->getID(),
                    'sku' => $variation->getVariationSKU(),
                    'name' => $variation->getProduct()->getName(),
                    'stock_level' => (float)$variation->getStockLevel(),
                    'stock_unlimited' => $variation->isUnlimited(),
                    'variation' =>true
                ];
            });
        }

        return $resource;
    }

    public function stockLevelWriteVariation($id) {
        if ($id) {
            $variation = ProductVariation::getByID($id);
        }

        if (!$variation) {
            return $this->error(t('Variation not found'), 404);
        }

        $data = json_decode($this->request->getContent(), true);

        if (!$data) {
            return $this->error(t('Bad Request'), 400);
        }

        if ($variation) {
            if (isset($data['data']['stock_level'])) {
                $variation->setVariationStockLevel($data['data']['stock_level']);
            }

            if (isset($data['data']['stock_unlimited'])) {
                $variation->setVariationIsUnlimited((bool)$data['data']['stock_unlimited']);
            }

            $variation->save();

            $resource = new Item($variation, function (ProductVariation $variation) {
                return [
                    'id' => $variation->getID(),
                    'product_id' => $variation->getProduct()->getID(),
                    'sku' => $variation->getVariationSKU(),
                    'name' => $variation->getProduct()->getName(),
                    'stock_level' => (float)$variation->getStockLevel(),
                    'stock_unlimited' => $variation->isUnlimited(),
                    'variation' =>true
                ];
            });
        }
        return $resource;
    }


}
