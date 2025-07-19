@extends('layouts/contentNavbarLayout')

@section('title', 'Add Product')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Add Stock</h5>
            <div>
                <a href="{{ asset('sample-stock-import.xlsx') }}" class="btn btn-outline-secondary" download>
                    Download Excel Format
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Manual Entry Form -->
            <form id="manualEntryForm" class="row g-3 mb-4">
                @csrf
                <!-- SKU dropdown -->
                <div class="col-md-3">
                    <select class="form-control" name="sku" id="skuSelect" required>
                        <option value="">Select SKU</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->sku }}" data-name="{{ $product->name }}" data-price="{{ $product->price }}" data-unit="{{ $product->unit }}">
                                {{ $product->sku }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Product name input (readonly, auto-filled) -->
                <div class="col-md-3">
{{--                    <input type="text" class="form-control" name="name" id="productName" placeholder="Product Name" disabled >--}}
                    <select class="form-control" name="name" id="productName" required>
                        <option value="">Select Item</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->name }}" data-sku="{{ $product->sku }}" data-price="{{ $product->price }}" data-unit="{{ $product->unit }}">
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control" name="quantity" placeholder="Qty" min="1" required>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control" name="price" placeholder="Price" step="0.01" id="price" disabled>
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control" name="unit" placeholder="Unit" step="0.01" id="unit" disabled>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Add</button>
                </div>
            </form>

            <!-- Excel Import -->
            <div class="mb-4">
                <label class="form-label">Import from Excel</label>
                <input type="file" id="excelInput" class="form-control" accept=".xlsx,.xls,.csv">
            </div>

            <!-- Preview Table -->
            <div class="table-responsive">
                <table class="table table-bordered" id="stockTable">
                    <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Unit</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <!-- Rows will be dynamically inserted -->
                    </tbody>
                </table>
            </div>

            <div class="text-end">
                <button class="btn btn-success" id="submitAll">Save All</button>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function()  {
        const tableBody = document.querySelector('#stockTable tbody');

        function addRow(data) {
          const row = document.createElement('tr');
          row.innerHTML = `
      <td><input type="text" name="sku[]" class="form-control" value="${data.sku}" readonly></td>
      <td><input type="text" name="name[]" class="form-control" value="${data.name}" readonly></td>
      <td><input type="number" name="quantity[]" min="0"  class="form-control" value="${data.quantity}" required></td>
      <td><input type="number" name="price[]" step="0.01" class="form-control" value="${data.price}" readonly></td>
      <td><input type="text" name="unit[]" step="0.01" class="form-control" value="${data.unit}" readonly></td>
      <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">Delete</button></td>
    `;
          row.setAttribute('data-source',data.source);
          tableBody.appendChild(row);
        }

        document.getElementById('manualEntryForm').addEventListener('submit', function(e) {
          e.preventDefault();
          const formData = new FormData(e.target);
          addRow({
            sku: formData.get('sku'),
            name: document.getElementById('productName').value,
            quantity: formData.get('quantity'),
            price: document.getElementById('price').value,
            unit: document.getElementById('unit').value,
            source:'manual'
          });
          e.target.reset();
        });

        document.getElementById('excelInput').addEventListener('change', function(e) {
          const file = e.target.files[0];
          if (!file) return;

          const reader = new FileReader();
          reader.onload = function(event) {
            const data = new Uint8Array(event.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const sheet = workbook.Sheets[workbook.SheetNames[0]];
            const rows = XLSX.utils.sheet_to_json(sheet);
            rows.forEach(row => {
              addRow({
                sku: row['SKU'] || '',
                name: row['Product Name'] || '',
                quantity: row['Quantity'] || '',
                price: row['Price'] || '',
                unit: row['Unit'] || '',
                source:'import'
              });
            });
          };
          reader.readAsArrayBuffer(file);
        });

        document.getElementById('submitAll').addEventListener('click', function () {
          const rows = Array.from(tableBody.querySelectorAll('tr'));
          const items = rows.map(row => ({
            sku: row.querySelector('input[name="sku[]"]').value,
            name: row.querySelector('input[name="name[]"]').value,
            quantity: row.querySelector('input[name="quantity[]"]').value,
            price: row.querySelector('input[name="price[]"]').value,
            unit: row.querySelector('input[name="unit[]"]').value,
            source: row.getAttribute('data-source'),
          }));

          fetch('{{ route('stocks.store') }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ items }) // key must be "items"
          })
            .then(response => response.json())
            .then(result => {
              alert('Stock successfully added!');
              // window.location.reload();
            })
            .catch(error => {
              alert('Error occurred!');
              console.error(error);
            });
        });


        document.getElementById('skuSelect').addEventListener('change', function () {
          const selectedOption = this.options[this.selectedIndex];
          const name = selectedOption.getAttribute('data-name') || '';
          const price = selectedOption.getAttribute('data-price') || '';
          const unit = selectedOption.getAttribute('data-unit') || '';
          document.getElementById('productName').value = name;
          document.getElementById('price').value = price;
          document.getElementById('unit').value = unit;
          document.getElementById('quantity').focus();
        });

        document.getElementById('productName').addEventListener('change', function () {
          const selectedOption = this.options[this.selectedIndex];
          const sku = selectedOption.getAttribute('data-sku') || '';
          const name = selectedOption.getAttribute('data-name') || '';
          const price = selectedOption.getAttribute('data-price') || '';
          const unit = selectedOption.getAttribute('data-unit') || '';
          document.getElementById('skuSelect').value = sku;
          document.getElementById('price').value = price;
          document.getElementById('unit').value = unit;
          document.getElementById('quantity').focus();
        });

      });
    </script>
@endpush
