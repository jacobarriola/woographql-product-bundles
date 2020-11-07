# WooGraphQL Product Bundles
Add WooCommerce Product Bundle support to your WPGraphQL schema.

> This extension requires the `develop` branch of WooGraphQL because some of the new methods that
> make extensions easier have not been published as part of a release.
 
## Requirements
* PHP +7.4
* WordPress +5.0
* WPGraphQL +0.13.3
* WooGraphQL `develop` branch
* WooCommerce +3.6.0
* WooCommerce Product Bundles +0.5.0

### Documentation

#### Add To Cart Mutation
For now, add to cart is a different mutation since additional data is required. The mutation
 mimics the existing WooGraphQL `addToCart`, with a few differences.
 
 Essentially it attempts to mimic the underlying Product Bundles' API, which is found https://docs.woocommerce.com/document/bundles/bundles-functions-reference/#add_bundle_to_cart

```graphql
mutation addToCartBundle(input: { clientMutationId: "123" quantity: 1 productId: 456789 extraData
: SEE_BELOW
 } ) {
    ...
}
```

The `extraData` input is a JSON value.

##### Arguments
* `object index`: The bundle item ID
* `product_id`: Product ID of the bundled item
* `quantity`: Quantity of the bundled item
* `variation_id`: The variation ID of the selected variation, if applicable
* `attributes`: An object with the key as `attribute_ATTRIBUTE_SLUG` and the value as the
 attribute value, if applicable

Example:
```json
{
  "765": {
    "product_id": "9876",
    "quantity": "1",
    "optional_selected": "yes",
    "variation_id": "4321",
    "attributes": {
      "attribute_pa_color": "red",
      "attribute_pa_size": "2xl"
    } 
  },
  "896": {
    "product_id": "1234",
    "quantity": "1"
  }
}
```

In this example, the Bundle contains two Bundle Items, `765` and `896`. `765` is a variable
 product (designated by the `product_id: 9876`). The `variation_id` and attributes are required
 . On the other hand, `896` is a simple product. It's only passing in the `product_id`.

## Todo
* return appropriate types instead of JSON for `allowedVariations` and `defaultVariationAttributes`
* simplify the `addToCartBundle` of merge it with the core `addToCart` mutation
