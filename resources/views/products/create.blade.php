@extends('layouts/contentNavbarLayout')

@section('title', 'Add Product')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Add New Product</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="mb-3 col-md-6">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                        @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="mb-3 col-md-6">
                        <label class="form-label">SKU *</label>
                        <input type="text" name="sku" value="{{ old('sku') }}" class="form-control" required>
                        @error('sku') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-control">
                            <option value="">-- Select Category --</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div class="mb-3 col-md-3">
                        <label class="form-label">Quantity *</label>
                        <input type="number" name="quantity" value="{{ old('quantity') }}" class="form-control" required>
                    </div>

                    <div class="mb-3 col-md-3">
                        <label class="form-label">Unit</label>
                        <input type="text" name="unit" value="{{ old('unit') }}" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="mb-3 col-md-4">
                        <label class="form-label">Price *</label>
                        <input type="number" step="0.01" name="price" value="{{ old('price') }}" class="form-control" required>
                    </div>

                    <div class="mb-3 col-md-4">
                        <label class="form-label">Min Stock Alert</label>
                        <input type="number" name="min_stock_alert" value="{{ old('min_stock_alert') }}" class="form-control">
                    </div>

                    <div class="mb-3 col-md-4">
                        <label class="form-label">Max Stock</label>
                        <input type="number" name="max_stock" value="{{ old('max_stock') }}" class="form-control">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control">{{ old('description') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Product Image</label>
                    <input type="file" name="image" class="form-control">
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Create</button>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
