<ul class="order-service-items">
    @foreach($bill->lineItems() as $item)
        <li><div><strong>{{ $item['name'] }}</strong><span>{{ number_format($item['quantity'], 0, ',', '.') }} &times; Rp{{ number_format($item['unit_price'], 0, ',', '.') }}</span></div><strong>Rp{{ number_format($item['quantity'] * $item['unit_price'], 0, ',', '.') }}</strong></li>
    @endforeach
</ul>
