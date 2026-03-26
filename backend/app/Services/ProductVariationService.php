<?php
// app/Services/ProductVariationService.php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductVariationService
{
    /**
     * Create variation for product
     */
    public function createForProduct(Product $product, array $data)
    {
        return DB::transaction(function () use ($product, $data) {
            // Generate SKU if not provided
            if (empty($data['sku'])) {
                $data['sku'] = $this->generateSku($product->name . ' ' . ($data['name'] ?? ''));
            }

            $data['product_id'] = $product->id;

            // Keep stable ordering for newly created variations.
            // If client does not provide position, append to the end.
            if (!array_key_exists('position', $data) || $data['position'] === null) {
                $data['position'] = (int) ($product->variations()->max('position') ?? -1) + 1;
            }

            // Handle default variation
            if (!empty($data['is_default'])) {
                $product->variations()->update(['is_default' => false]);
            }

            $variation = ProductVariation::create($data);

            // Update product lowest price if needed
            $product->syncVariationPrices();

            return $variation;
        });
    }

    /**
     * Update variation
     */
    public function update(ProductVariation $variation, array $data)
    {
        return DB::transaction(function () use ($variation, $data) {
            // Handle default variation
            if (!empty($data['is_default']) && !$variation->is_default) {
                $variation->product->variations()
                    ->where('id', '!=', $variation->id)
                    ->update(['is_default' => false]);
            }

            $variation->update($data);

            // Update product lowest price
            $variation->product->syncVariationPrices();

            return $variation->fresh();
        });
    }

    /**
     * Delete variation
     */
    public function delete(ProductVariation $variation)
    {
        return DB::transaction(function () use ($variation) {
            $product = $variation->product;
            $result = $variation->delete();

            // If this was default, set another as default
            if ($variation->is_default) {
                $newDefault = $product->variations()->first();
                if ($newDefault) {
                    $newDefault->update(['is_default' => true]);
                }
            }

            // Update product prices
            $product->syncVariationPrices();

            return $result;
        });
    }

    /**
     * Bulk update variations
     */
    public function bulkUpdate(Product $product, array $variations)
    {
        return DB::transaction(function () use ($product, $variations) {
            $updatedIds = [];

            foreach ($variations as $variationData) {
                if (isset($variationData['id'])) {
                    $variation = ProductVariation::where('product_id', $product->id)
                        ->where('id', $variationData['id'])
                        ->first();

                    if ($variation) {
                        $this->update($variation, $variationData);
                        $updatedIds[] = $variation->id;
                    }
                } else {
                    $variation = $this->createForProduct($product, $variationData);
                    $updatedIds[] = $variation->id;
                }
            }

            return $product->variations()->whereIn('id', $updatedIds)->get();
        });
    }

    /**
     * Set default variation
     */
    public function setDefault(Product $product, ProductVariation $variation)
    {
        if ($variation->product_id !== $product->id) {
            throw new \Exception('Variation does not belong to this product');
        }

        return DB::transaction(function () use ($product, $variation) {
            $product->variations()->update(['is_default' => false]);
            $variation->update(['is_default' => true]);

            return $variation;
        });
    }

    /**
     * Reorder variations
     */
    public function reorder(Product $product, array $order)
    {
        return DB::transaction(function () use ($product, $order) {
            $variationIds = collect($order)
                ->map(fn($id) => (int) $id)
                ->filter(fn($id) => $id > 0)
                ->values();

            if ($variationIds->isEmpty()) {
                throw new \Exception('Order payload is empty or invalid.');
            }

            if ($variationIds->count() !== $variationIds->unique()->count()) {
                throw new \Exception('Order payload contains duplicate variation IDs.');
            }

            $matchedCount = ProductVariation::where('product_id', $product->id)
                ->whereIn('id', $variationIds->all())
                ->count();

            if ($matchedCount !== $variationIds->count()) {
                throw new \Exception('One or more variation IDs do not belong to this product.');
            }

            foreach ($variationIds as $position => $variationId) {
                ProductVariation::where('product_id', $product->id)
                    ->where('id', $variationId)
                    ->update(['position' => $position]);
            }

            return true;
        });
    }

    /**
     * Update stock for variation
     */
    public function updateStock(ProductVariation $variation, int $quantity, string $operation = 'set')
    {
        return DB::transaction(function () use ($variation, $quantity, $operation) {
            switch ($operation) {
                case 'increment':
                    $variation->incrementStock($quantity);
                    break;
                case 'decrement':
                    $variation->decrementStock($quantity);
                    break;
                default:
                    $variation->stock_quantity = $quantity;
                    $variation->updateStockStatus();
                    $variation->save();
            }

            return $variation;
        });
    }

    /**
     * Generate unique SKU
     */
    protected function generateSku($name)
    {
        $base = 'VAR-' . strtoupper(preg_replace('/[^A-Za-z0-9]/', '', substr($name, 0, 10)));
        $sku = $base;
        $counter = 1;

        while (ProductVariation::where('sku', $sku)->exists()) {
            $sku = $base . '-' . $counter;
            $counter++;
        }

        return $sku;
    }

    /**
     * Duplicate variations from one product to another
     */
    public function duplicateToProduct(Product $sourceProduct, Product $targetProduct)
    {
        return DB::transaction(function () use ($sourceProduct, $targetProduct) {
            foreach ($sourceProduct->variations as $variation) {
                $newVariation = $variation->replicate();
                $newVariation->product_id = $targetProduct->id;
                $newVariation->uuid = (string) Str::uuid();
                $newVariation->sku = $this->generateSku($variation->name);
                $newVariation->save();
            }

            return $targetProduct->variations;
        });
    }
}
