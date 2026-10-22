@php
    $itemValue = fn ($key, $default = '') => is_scalar($item[$key] ?? null) ? $item[$key] : $default;
@endphp
<div class="order-item-row" data-order-item>
    <div class="order-item-heading"><strong>Item <span data-item-number>{{ is_numeric($index) ? $index + 1 : 1 }}</span></strong><button type="button" class="row-action danger" data-remove-order-item>Hapus item</button></div>
    <div class="form-field"><label for="item-{{ $index }}-name">Nama jasa / layanan</label><input id="item-{{ $index }}-name" name="items[{{ $index }}][name]" data-item-field="name" value="{{ $itemValue('name') }}" required maxlength="255" placeholder="Contoh: Desain undangan"></div>
    <div class="form-grid">
        <div class="form-field"><label for="item-{{ $index }}-quantity">Jumlah</label><input id="item-{{ $index }}-quantity" name="items[{{ $index }}][quantity]" data-item-field="quantity" type="number" value="{{ $itemValue('quantity', 1) }}" required min="1" max="10000" step="1"></div>
        <div class="form-field"><label for="item-{{ $index }}-unit_price">Harga satuan (Rp)</label><input id="item-{{ $index }}-unit_price" name="items[{{ $index }}][unit_price]" data-item-field="unit_price" type="number" value="{{ $itemValue('unit_price') }}" required min="1" max="1000000000" step="1"></div>
    </div>
    <p class="order-item-subtotal">Subtotal item <strong data-item-subtotal>Rp0</strong></p>
</div>
