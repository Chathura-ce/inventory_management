{{-- resources/views/sales/pos.blade.php --}}
@extends('layouts/contentNavbarLayout')
@section('title','Point-of-Sale')

@section('vendor-style')
    @vite('resources/assets/vendor/libs/apex-charts/apex-charts.scss')
@endsection

@section('content')
    <div class="row gx-4">
        {{-- ◀︎ LEFT: Product catalog ▶︎ --}}
        <div class="col-md-6">
            <div class="mb-3">
                <input id="prodSearch"
                       type="text"
                       class="form-control"
                       placeholder="Search products…">
            </div>
            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 g-2" id="productList">
                @foreach($products as $p)
                    <div class="col">
                        <button type="button"
                                class="btn btn-outline-secondary w-100 h-100 product-btn d-flex flex-column justify-content-center align-items-center p-3"
                                data-id="{{ $p->id }}"
                                data-name="{{ $p->name }}"
                                data-price="{{ $p->price }}"
                                data-unit="{{ $p->unit }}"
                                data-stock="{{ $p->stock }}">

                            <div class="fw-semibold text-truncate">{{ $p->name }}</div>
                            <div class="text-primary">Rs {{ number_format($p->price,2) }}</div>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ◀︎ RIGHT: Cart ▶︎ --}}
        <div class="col-md-6">
            <div class="card sticky-top" style="top:1rem">
                <div class="card-header">
                    <h5 class="mb-0">Current Sale</h5>
                </div>
                <div class="card-body p-2">
                    <table class="table table-sm mb-2" id="cartTable">
                        <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Total (Rs)</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Grand Total:</span>
                        <span id="grandTotal">0.00</span>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button id="completeSale" class="btn btn-primary w-100">Complete Sale</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const productList = document.getElementById('productList');
        const productBtns = productList.querySelectorAll('.product-btn');
        const searchInput = document.getElementById('prodSearch');
        const cartTbody   = document.querySelector('#cartTable tbody');
        const grandTotal  = document.getElementById('grandTotal');
        let cart = {}; // { productId: { name, price, qty, stock, lineTotal } }

        // 1. Search filter
        searchInput.addEventListener('input', () => {
          const term = searchInput.value.toLowerCase();
          productBtns.forEach(btn => {
            const name = btn.dataset.name.toLowerCase();
            btn.closest('.col').classList.toggle('d-none', !name.includes(term));
          });
        });

        // 2. Add item to cart from product list
        productBtns.forEach(btn => {
          btn.addEventListener('click', () => {
            const id    = btn.dataset.id;
            const name  = btn.dataset.name;
            const unit  = btn.dataset.unit;
            const price = parseFloat(btn.dataset.price);
            const stock = parseInt(btn.dataset.stock);

            if (!cart[id]) {
              cart[id] = { name, price, qty: 1, stock, lineTotal: price , unit:unit};
            } else if (cart[id].qty < stock) {
              cart[id].qty++;
              cart[id].lineTotal = cart[id].qty * price;
            } else {
              return alert('Out of stock');
            }
            renderCart();
          });
        });

        // 3. Handle cart interactions (quantity change and item removal)
        cartTbody.addEventListener('click', e => {
          if (e.target.matches('.remove-btn')) {
            delete cart[e.target.dataset.id];
            renderCart();
          }
        });

        cartTbody.addEventListener('change', e => {
          if (e.target.matches('.qty-input')) {
            const id  = e.target.dataset.id;
            let qty = parseInt(e.target.value);
            const item = cart[id];

            if (isNaN(qty) || qty < 1) {
              qty = 1;
            }

            if (qty > item.stock) {
              alert(`Only ${item.stock} items available.`);
              qty = item.stock;
            }

            e.target.value = qty; // Reflect corrected value in the input
            item.qty = qty;
            item.lineTotal = qty * item.price;
            renderCart();
          }
        });

        // 4. Render the entire cart table
        function renderCart() {
          cartTbody.innerHTML = '';
          let total = 0;
          Object.entries(cart).forEach(([id, item]) => {
            total += item.lineTotal;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${item.name}</td>
                <td style="width: 80px">
                  <input style="width: 90px;" type="number" value="${item.qty}" min="1" max="${item.stock}" class="form-control form-control-sm qty-input" data-id="${id}">
                </td>
<td>${item.unit}</td>
                <td>${item.lineTotal.toFixed(2)}</td>
                <td>
                  <button class="btn btn-sm btn-outline-danger remove-btn" data-id="${id}">&times;</button>
                </td>
              `;
            cartTbody.append(tr);
          });
          grandTotal.textContent = `₹${total.toFixed(2)}`;
        }

        // 5. Complete sale via AJAX
        document.getElementById('completeSale').addEventListener('click', async () => {
          if (Object.keys(cart).length === 0) {
            return alert('Cart is empty');
          }

          const items = Object.entries(cart).map(([id, item]) => ({
            product_id: id,
            qty:        item.qty,
          }));

          try {
            const response = await fetch('{{ route("sales.store") }}', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
              },
              body: JSON.stringify({
                sale_date: new Date().toISOString().slice(0, 10), // Format as YYYY-MM-DD
                items
              })
            });

            const data = await response.json();

            if (response.ok) {
              alert(data.message || 'Sale recorded successfully!');
              const url = `{{ url('sales') }}/${data.id}/receipt`;
              window.open(url, '_blank');
              cart = {};
              renderCart();
              // Optionally, you might want to refresh product stock data on the page
            } else {
              alert(data.error || 'Failed to record sale. Please check the details.');
            }
          } catch (err) {
            console.error('Sale completion error:', err);
            alert('An unexpected error occurred. Please try again.');
          }
        });
      });
    </script>
@endpush