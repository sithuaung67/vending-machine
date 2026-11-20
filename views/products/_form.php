<?php
/** Shared product form fields. @var array $product (optional) */
$product ??= [];
?>
<div class="field">
  <label for="name">Name</label>
  <input id="name" name="name" value="<?= old('name', $product['name'] ?? '') ?>" placeholder="e.g. Coke" required data-rule="required">
  <div class="field-err"></div>
</div>
<div class="field">
  <label for="price">Price (USD)</label>
  <div class="input-prefix"><span>$</span>
    <input id="price" name="price" type="number" step="0.001" min="0.001" inputmode="decimal" placeholder="0.00"
           value="<?= old('price', $product['price'] ?? '') ?>" required data-rule="required|positive">
  </div>
  <div class="field-err"></div>
  <div class="hint">Must be greater than 0. Up to 3 decimals (e.g. 6.885).</div>
</div>
<div class="field">
  <label for="quantity_available">Quantity available</label>
  <input id="quantity_available" name="quantity_available" type="number" step="1" min="0" placeholder="0"
         value="<?= old('quantity_available', $product['quantity_available'] ?? '') ?>" required data-rule="required|nonneg">
  <div class="field-err"></div>
  <div class="hint">Whole number, 0 or more.</div>
</div>
