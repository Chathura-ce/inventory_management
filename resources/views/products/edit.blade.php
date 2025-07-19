@extends('layouts/contentNavbarLayout')

@section('title', 'Edit Product')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Edit Product</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="mb-3 col-md-6">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}" class="form-control" required>
                        @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="mb-3 col-md-6">
                        <label class="form-label">SKU *</label>
                        <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" class="form-control" required>
                        @error('sku') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="mb-3 col-md-6">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-control">
                            <option value="">-- Select Category --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="mb-3 col-md-3">
                        <label class="form-label">Quantity *</label>
                        <input type="number" name="quantity" value="{{ old('quantity', $product->quantity) }}" class="form-control" required>
                        @error('quantity') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="mb-3 col-md-3">
                        <label class="form-label">Unit</label>
                        <input type="text" name="unit" value="{{ old('unit', $product->unit) }}" class="form-control">
                        @error('unit') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="mb-3 col-md-4">
                        <label class="form-label">Price *</label>
                        <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" class="form-control" required>
                        @error('price') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="mb-3 col-md-4">
                        <label class="form-label">Min Stock Alert</label>
                        <input type="number" name="min_stock_alert" value="{{ old('min_stock_alert', $product->min_stock_alert) }}" class="form-control">
                        @error('min_stock_alert') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="mb-3 col-md-4">
                        <label class="form-label">Max Stock</label>
                        <input type="number" name="max_stock" value="{{ old('max_stock', $product->max_stock) }}" class="form-control">
                        @error('max_stock') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control">{{ old('description', $product->description) }}</textarea>
                    @error('description') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Product Image</label><br>
                    @if($product->image_path)
                        <img src="{{ asset('storage/' . $product->image_path) }}" width="100" class="mb-2" />
                    @endif
                    <input type="file" name="image" class="form-control">
                    @error('image') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
