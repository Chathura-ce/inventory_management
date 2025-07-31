@extends('layouts/contentNavbarLayout')
@section('title','Notification Details')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Notification Details</h5>
        </div>
        <div class="card-body">
            <p><strong>Message:</strong> {{ $notification->data['message'] }}</p>
            <p><strong>Received:</strong> {{ $notification->created_at->format('Y-m-d H:i:s') }}</p>
            <p><strong>Status:</strong>
                @if($notification->read_at)
                    Read at {{ $notification->read_at->format('Y-m-d H:i') }}
                @else
                    Unread
                @endif
            </p>

            @if(is_null($notification->read_at))
                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success">Mark as Read</button>
                </form>
            @endif

            <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary mt-3">Back to List</a>
        </div>
    </div>
@endsection