# Community Store API

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

A Concrete CMS add-on that provides a RESTful API for Community Store data, returning and receiving JSON data.

## Current Functionality

- Fetch lists of orders and individual order details
- Update the fulfilment status of orders 
- Fetch lists of products and individual products
- Update product and variation stock levels by product ID or by SKU
- Fetch fulfilment statuses

This functionality may be expanded in the future.

## Setup

Once installed, the Community Store API is accessed through concrete5's built-in API, which uses **OAuth2** for authentication.
To configure an integration:
- Visit in the concrete5 dashboard, **System & Settings -> API**
- Enable the API via the checkbox if not already enabled, and ensure the **Client Credentials** option is checked under the **Enabled Grant Types**
- Add an Integration via the **Add Integration** button
- Give the integration a name appropriate for the purpose, i.e. 'Community Store API'
- Record the *Client ID* and *Client Secret* values generated

## Missing Authorization Header

When running concrete5 with Apache with CGI/FastCGI, Authorization headers may not be passed to PHP, resulting in a 'Missing Authorization header' error message.
Adding the following line to an .htaccess file can correct this:

`SetEnvIf Authorization .+ HTTP_AUTHORIZATION=$0`

## Usage with PHP

If you need to access the Community Store API via PHP, you may find useful [this project](https://github.com/concretecms-community-store/community_store_api_client).

## General Usage

Once an integration has been created through concrete5's dashboard, the Client ID and Client Secret can be used to generate access tokens, and those are then used to access the API.

Request access tokens by sending a POST request to the URL: `/oauth/2.0/token`, sending in the body:

- grant_type: "client_credentials"
- scope: "cs:orders:read cs:products:write cs:products:read cs:products:write cs:config:read"
- client_id 
- client_secret

The scopes passed can be reduced down to only those needed for the integration. 

A JSON response will contain an `access_token` value. The access token typically lasts for 1 hour.

The access token can then be used when performing requests against the API, being sent as an authorization header. The authorization header needs to be prefixed with 'Bearer '.

Responses follow typical RESTful style HTTP response codes, such as 404 for not found records, 200 for found records, etc, along with error messages.

### Order related endpoints:

#### GET /cs/api/v1/orders

Get paginated orders
- scope required: cs:orders:read
- example response:
```
{
    "data": [
        {
            "id": 3,
            "date_placed": {
                "date": "2020-10-06 12:12:37.000000",
                "timezone": "America/Phoenix"
            },
            "total": 100.50,
            "payment_method": "Invoice",
            "payment_date": null,
            "payment_reference": null,
            "shipping_method": "",
            "fulfilment": {
                "status": "Delivered",
                "handle": "Delivered",
                "tracking_id": '123',
                "tracking_code": 'ABC1234',
                "tracking_url": null
            },
            "locale": "en_US",
            "customer": {
                "email": "test@test.com,
                "username": null,
                "billing": {
                    "phone": "123123123",
                    "first_name": "Joe",
                    "last_name": "Smith",
                    "company": "ABC Company",
                    "address": {
                        "address1": "123 Street",
                        "address2": "",
                        "address3": null,
                        "city": "Phoenix",
                        "state_province": "AZ",
                        "country": "US",
                        "postal_code": "55555"
                    }
                },
                "shipping": {
                    "first_name": "Joe",
                    "last_name": "Smith",
                    "company": "ABC Company", 
                    "address": {
                          "address1": "123 Street",
                          "address2": "",
                          "address3": null,
                          "city": "Phoenix",
                          "state_province": "AZ",
                          "country": "US",
                          "postal_code": "55555"
                      }
                }
            },
            "items": [
                {
                    "id": 123,
                    "name": "Example Product",
                    "sku": "ABCD",
                    "quantity": 1,
                    "price": 100.50
                }
            ]
        }

        // ... addition 19 orders would be included here

    ],
    "meta": {
        "pagination": {
            "total": 25,
            "count": 20,
            "per_page": 20,
            "current_page": 1,
            "total_pages": 2,
            "links": {
                "next": "http://concrete5.test/cs/api/v1/orders?page=2"
            }
        }
    }
}
```

##### Paginating Orders

Orders are returned with the most recent orders first, which is the same as the order ID in descending order.

The orders are paginated, returning 20 orders at a time. Note in the response there is a `meta` -> `pagination` section, containing values representing the pagination position.
The `links` within the pagination data include when applicable previous and next URLs, to navigate the pages of orders. No more pages are available when the `next` value is not returned.
The GET attribute `page` is used in the API call to select which page to return.

##### Filtering orders

You can use querystring parameters to filter the results.
Here's a list of accepted querystring parameters:

- `status`: the status of the orders (predefined statuses are `incomplete`, `processing`, `shipped`, `delivered`, `nodelivery`, and `returned`)
- `paymentStatus`: the status of the order payments (`paid`, `unpaid`, `cancelled`, `refunded`, or `incomplete`)
- `fromDate`: the initial date of the orders (format: YYYY-MM-DD)
- `toDate`: the final date of the orders (format: YYYY-MM-DD)
- `paid`: set to 0 for unpaid or refunded orders, 1 for paid and not refunded orders
- `cancelled`: set to 0 to exclude cancelled orders, 1 to retrieve only cancelled orders
- `refunded`: set to 0 to exclude refunded orders, 1 to retrieve only refunded orders
- `shippable`: set to 0 to exclude shippable orders, 1 to retrieve only shippable orders
- `id`: the order ID. You can also query for more IDs using an array (for example: `orders?id[]=1&id[]=2`) (requires Community Store with [this pull request](https://github.com/concretecms-community-store/community_store/pull/833))

#### GET /cs/api/v1/orders/oID

Get an order
- scope required: cs:orders:read
- url parameters: oID = Order ID
- response: JSON representing individual order, within `data` value

#### PATCH /cs/api/v1/orders/oID

Update fulfilment details of an an individual order
- scope required: 'cs:orders:write
- updatable fields: tracking_id, tracking_code, tracking_url, handle
- raw body expected: JSON data, e.g.:
```
{
"data":
  { 
      "fulfilment": { 
          "handle": "shipped",
          "tracking_id": "123123123",
          "tracking_code": "AAABBCCC",
          "tracking_url": "https://trackmyparcel.com"
      }
  }
}
```
- response: JSON representing individual order, within `data` value, after update performed

#### GET /cs/api/v1/fulfilmentstatuses

Get all fulfilment statuses
- scope required: cs:orders:read
- example response:
```
{
    "data": [
        {
            "id": "1",
            "handle": "incomplete",
            "name": "Awaiting Processing"
        },
        {
            "id": "2",
            "handle": "processing",
            "name": "Processing"
        },
        {
            "id": "3",
            "handle": "shipped",
            "name": "Shipped"
        },
        {
            "id": "4",
            "handle": "delivered",
            "name": "Delivered"
        },
        {
            "id": "5",
            "handle": "nodelivery",
            "name": "Will not deliver"
        },
        {
            "id": "6",
            "handle": "returned",
            "name": "Returned"
        }
    ]
}
```

### Product related endpoints:

#### GET /cs/api/v1/products

Get all products
- scope required: cs:products:read
- response: JSON array of products, within `data` value

##### Paginating Products

Products are returned with the most recently added products first.

The products are paginated, returning 20 products at a time. Note in the response there is a `meta` -> `pagination` section, containing values representing the pagination position.
The `links` within the pagination data include when applicable previous and next URLs, to navigate the pages of products. No more pages are available when the `next` value is not returned.
The GET attribute `page` is used in the API call to select which page to return.

##### Filtering Products

Products can be filtered through the use of a `filter` parameter, following the pattern:
`?filter=date_added gt '2021-01-01T20:00'`
Currently, `date_added` and `date_updated` fields can be filtered, with gt, lt and eq comparisons.

#### GET /cs/api/v1/products/pID

Get a product
- scope required: cs:products:read
- url parameters: pID = Product ID
- response: JSON representing individual product, within `data` value
- example response:
```
{
    "data": {
        "id": 123,
        "name": "Example Product",
        "sku": "ABCD",
        "barcode": "",
        "active": true,
        "stock_unlimited": false,
        "stock_level": 10,
        "short_description": "Short description HTML",
        "description": "Extended description HTML",
        "brand": null,
        "price": 100.50,
        "primary_image": "https://domain.com/product.jpg",
        "additional_images": [
            "https://domain.com/product2.jpg"
        ],
        "groups": [
            "Product Group 1"
        ],
        "categories": [
            {
                "name": "Product Category 1",
                "url": "https://domain.com/category1"
            } 
        ],
        "variations": [],
        "date_added": {
            "date": "2020-10-05 15:41:00.000000",
            "timezone": "America/Phoenix"
        },
       "date_updated": {
            "date": "2020-10-05 15:41:00.000000",
            "timezone": "America/Phoenix"
        }
    }
}
```

#### PATCH /cs/api/v1/products/pID

Update a product
- updatable fields: stock_unlimited (boolean), stock_level (float)
- scope required: cs:products:write
- url parameters: pID = Product ID
- raw body expected: JSON data, e.g.:
```
{
  "data":
    { 
        "stock_level": 5,
        "stock_unlimited": true
    }
}
 ```
- response: JSON representing individual product, within `data` value, after update performed
 

#### GET /cs/api/v1/skus/sku

Get stock level of a product or variation, found via SKU 
- scope required: cs:products:read
- url parameters: sku = Product or Variation SKU

#### PATCH /cs/api/v1/skus/sku

Update the stock level of a product or variation by SKU
- updatable fields: stock_unlimited (boolean), stock_level (float)
- scope required: cs:products:write
- url parameters: sku = Product or Variation SKU
- raw body expected: JSON data, e.g.:
```
{
"data":
    { 
        "stock_level": 5,
        "stock_unlimited": true
    }
}
```

### Miscellaneous endpoints

#### GET /cs/api/v1/config

Get some Comminity Store configuration value

- scope required: cs:config:read
- response: JSON object whose keys are the configuration keys, and values are their values
- example response:
  ```json
  {
      "currency": {
          "code": "EUR",
          "symbol": "€",
          "decimal_digits": 2
      }
  }
  ```
