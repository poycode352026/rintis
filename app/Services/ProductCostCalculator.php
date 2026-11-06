<?php

namespace App\Services;

use InvalidArgumentException;

class ProductCostCalculator
{
    private const UNITS = ['g' => ['mass', 1], 'kg' => ['mass', 1000], 'ml' => ['volume', 1], 'l' => ['volume', 1000], 'pcs' => ['count', 1]];

    public function calculate(array $costing): array
    {
        $rows = [];
        foreach ($costing['ingredients'] as $row) {
            $purchase = self::UNITS[$row['purchase_unit']];
            $used = self::UNITS[$row['used_unit']];
            if ($purchase[0] !== $used[0]) {
                throw new InvalidArgumentException('Purchase and usage units must measure the same thing (mass, volume or pieces).');
            }
            $denominator = $this->scaled($row['purchase_quantity'], 3) * $purchase[1];
            $numerator = (int) $row['purchase_cost'] * $this->scaled($row['used_quantity'], 3) * $used[1];
            $amount = $this->roundedDivide($numerator, $denominator);
            if ($amount > 999999999999) {
                throw new InvalidArgumentException('Ingredient cost is too large. Check purchase quantity and usage.');
            }
            $rows[] = $amount;
        }
        $batch = array_sum($rows) + (int) $costing['additional_cost'];
        $unit = $this->roundedDivide($batch, (int) $costing['batch_yield']);
        $margin = $costing['target_margin'] ?? null;
        $suggested = null;
        if (array_key_exists('target_markup', $costing)) {
            $markup = $costing['target_markup'];
            if ($markup !== null && $markup !== '') {
                $rate = $this->scaled($markup, 2);
                if ($rate < 0 || $rate > 100000000) {
                    throw new InvalidArgumentException('Target profit must be between 0 and 1,000,000%.');
                }
                $suggested = $unit + intdiv($unit, 10000) * $rate + intdiv(($unit % 10000) * $rate + 9999, 10000);
            }
        } elseif ($margin !== null && $margin !== '') {
            $denominator = 10000 - $this->scaled($margin, 2);
            $suggested = intdiv($unit * 10000 + $denominator - 1, $denominator);
        }

        $priceLimitExceeded = $suggested !== null && $suggested > 999999999;
        if ($priceLimitExceeded) {
            $suggested = null;
        }

        return ['priceLimitExceeded' => $priceLimitExceeded, 'ingredientCosts' => $rows, 'batchCost' => $batch, 'unitCost' => $unit, 'suggestedPrice' => $suggested];
    }

    private function scaled(string|int|float $value, int $precision): int
    {
        $parts = explode('.', (string) $value, 2);

        return (int) $parts[0] * (10 ** $precision) + (int) str_pad($parts[1] ?? '', $precision, '0');
    }

    private function roundedDivide(int $numerator, int $denominator): int
    {
        return intdiv($numerator, $denominator) + ($numerator % $denominator >= intdiv($denominator + 1, 2) ? 1 : 0);
    }
}
