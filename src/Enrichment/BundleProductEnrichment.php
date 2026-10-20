<?php

declare(strict_types=1);

namespace SilverShop\ProductBundles\Enrichment;

use SilverShop\Api\Enrichment\ProductEnrichmentExtension;
use SilverShop\Page\Product;
use SilverShop\ProductBundles\Model\BundleChoiceGroup;
use SilverShop\ProductBundles\Model\BundleItem;
use SilverShop\ProductBundles\Page\BundleProduct;

/**
 * Adds a `bundle` object to the serialised product when silvershop-suite/product-bundles is installed and the product
 * is a {@link BundleProduct}; null for every ordinary product.
 *
 * The shape mirrors the module's own accessors: the bundle's pricing type, the savings-vs-buying-separately figures,
 * the component lines ({@link BundleItem}) and the mix-and-match choice groups ({@link BundleChoiceGroup}). Money
 * values are plain floats (the serializer base's money shape). Because {@link BundleProduct} is this module's own
 * class, it is referenced directly — the enricher is registered only when silvershop-suite/api is installed and the
 * bundle classes are always present alongside it (see _config/api.yml).
 */
class BundleProductEnrichment extends ProductEnrichmentExtension
{
    /**
     * @param array<string, mixed> $data
     */
    public function updateSerialisedProduct(array &$data, Product $product): void
    {
        $data['bundle'] = $product instanceof BundleProduct ? $this->serialiseBundle($product) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialiseBundle(BundleProduct $bundle): array
    {
        $components = [];
        /** @var BundleItem $item */
        foreach ($bundle->BundleItems() as $item) {
            $components[] = [
                'productID' => (int) $item->ProductID,
                'variationID' => (int) $item->VariationID,
                'title' => (string) $item->getBuyableTitle(),
                'quantity' => (int) $item->Quantity,
                'optional' => (bool) $item->Optional,
                'choiceGroupID' => (int) $item->ChoiceGroupID,
                'price' => (float) $item->getSubTotal(),
            ];
        }

        $choiceGroups = [];
        /** @var BundleChoiceGroup $group */
        foreach ($bundle->ChoiceGroups() as $group) {
            $choiceGroups[] = [
                'id' => (int) $group->ID,
                'title' => (string) $group->Title,
                'min' => (int) $group->MinChoices,
                'max' => (int) $group->MaxChoices,
                'range' => (string) $group->getChoiceRange(),
                'itemIDs' => array_values(array_map('intval', $group->Items()->column('ID'))),
            ];
        }

        return [
            'pricingType' => (string) $bundle->PricingType,
            'componentsPrice' => (float) $bundle->getComponentsPrice(),
            'savings' => (float) $bundle->getSavings(),
            'savingsPercentage' => (int) $bundle->getSavingsPercentage(),
            'components' => $components,
            'choiceGroups' => $choiceGroups,
        ];
    }
}
