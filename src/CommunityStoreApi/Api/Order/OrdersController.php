<?php
namespace Concrete\Package\CommunityStoreApi\Api\Order;
use Concrete\Core\Http\Request;
use Concrete\Core\Api\ApiController;
use League\Fractal\Resource\Collection;
use Concrete\Core\Application\Application;
use Concrete\Core\Search\Pagination\PaginationFactory;
use League\Fractal\Pagination\PagerfantaPaginatorAdapter;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order;
use Concrete\Package\CommunityStoreApi\Api\Order\OrderStatus\OrderStatusTransformer;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderStatus\OrderStatus as OrderStatus;
use RuntimeException;

class OrdersController extends ApiController
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
     * Return detailed information about an order.
     *
     * @param $oID
     *
     * @return \League\Fractal\Resource\Item|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function read($oID)
    {
        $oID = (int) $oID;
        $order = Order::getByID($oID);
        if (!$order) {
            return $this->error(t('Order not found'), 404);
        }

        return $this->transform($order, new OrderTransformer());
    }


    /**
     * Write to an order.
     *
     * @param $oID
     *
     * @return \League\Fractal\Resource\Item|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function write($oID)
    {
        $oID = (int) $oID;
        $order = Order::getByID($oID);
        if (!$order) {
            return $this->error(t('Order not found'), 404);
        }

        $data = json_decode($this->request->getContent(), true);

        if (!$data) {
            return $this->error(t('Bad Request'), 400);
        }

        if (isset($data['data']['fulfilment']['tracking_id'])) {
            $order->setTrackingID($data['data']['fulfilment']['tracking_id']);
        }

        if (isset($data['data']['fulfilment']['tracking_code'])) {
            $order->setTrackingCode($data['data']['fulfilment']['tracking_code']);
        }

        if (isset($data['data']['fulfilment']['tracking_url'])) {
            $order->setTrackingURL($data['data']['fulfilment']['tracking_url']);
        }

        if (isset($data['data']['fulfilment']['handle'])) {
            $order->updateStatus($data['data']['fulfilment']['handle']);
        }

        return $this->transform($order, new OrderTransformer());
    }


    /**
     * Return all orders.
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function all()
    {
        $orderList = new OrderList();

        $paging = $this->request->get('paging');
        $paging = $paging && is_numeric($paging) ? (int) $paging : 0;
        $orderList->setItemsPerPage($paging > 0 ? $paging : 20);
        if (($value = $this->request->query->get('status', '')) !== '') {
            $orderList->setStatus($value);
        }
        if (($value = $this->request->query->get('paymentStatus', '')) !== '') {
            $orderList->setPaymentStatus($value);
        }
        if (($value = $this->request->query->get('fromDate', '')) !== '') {
            $orderList->setFromDate($value);
        }
        if (($value = $this->request->query->get('toDate', '')) !== '') {
            $orderList->setToDate($value);
        }
        if (($value = filter_var($this->request->query->get('paid'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) !== null) {
            $orderList->setPaid($value);
        }
        if (($value = filter_var($this->request->query->get('cancelled'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) !== null) {
            $orderList->setCancelled($value);
        }
        if (($value = filter_var($this->request->query->get('refunded'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) !== null) {
            $orderList->setRefunded($value);
        }
        if (($value = filter_var($this->request->query->get('shippable'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) !== null) {
            $orderList->setIsShippable($value);
        }
        $orderIDs = $this->getOrderIDsFromRequest();
        if ($orderIDs !== []) {
            if (!method_exists($orderList, 'setOrderIDs')) {
                throw new RuntimeException('You need to upgrade Community Store in order to filter by order ID(s)');
            }
            $orderList->setOrderIDs($orderIDs);
        }
        
        $factory = new PaginationFactory($this->app->make(Request::class));
        $paginator = $factory->createPaginationObject($orderList);
        $orders = $paginator->getCurrentPageResults();

        $fanta = new PagerfantaPaginatorAdapter($paginator, function (int $page)  {
            $path = $this->request->getUri();
            $pathInfo = parse_url($path);
            $queryString = $pathInfo['query'];
            parse_str($queryString, $queryArray);
            $queryArray['page'] = $page;
            $newQueryStr = http_build_query($queryArray);

            return $pathInfo['scheme'] . '://' . $pathInfo['host']  . $pathInfo['path'] . '?' . $newQueryStr;
        });

        $resource = new Collection($orders, new OrderTransformer);
        $resource->setPaginator($fanta);

        return $resource;
    }

    public function orderStatuses() {
        $orderStatuses = OrderStatus::getAll();
        $resource = new Collection($orderStatuses, new OrderStatusTransformer());

        return $resource;
    }

    /**
     * @return array
     */
    private function getOrderIDsFromRequest()
    {
        $qs = $this->request->query->all();
        if (isset($qs['id']) && is_array($qs['id'])) {
            return $qs['id'];
        }
        if (is_numeric($orderID = $this->request->query->get('id'))) {
            return [$orderID];
        }

        return [];
    }
}