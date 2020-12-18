<?php
namespace Concrete\Package\CommunityStoreApi\Api\Product;
use Concrete\Package\CommunityStore\Src\CommunityStore\Product\ProductList as CSProductList;

class ProductList extends CSProductList
{
    protected $paginationPageParameter = 'page';

}