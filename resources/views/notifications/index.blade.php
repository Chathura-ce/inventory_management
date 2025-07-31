@extends('layouts/contentNavbarLayout')
@section('title','Notifications')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">All Notifications</h5>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>Message</th>
                    <th>Received</th>
                    <th>Status</th>
{{--                    <th>Action</th>--}}
                </tr>
                </thead>
                <tbody>
                @forelse($notifications as $notification)
                    <tr @unless($notification->read_at) class="table-warning" @endunless>
                        <td>{{ $notification->data['message'] }}</td>
                        <td>{{ $notification->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            @if($notification->read_at)
                                <span class="badge bg-success">Read</span>
                            @else
                                <span class="badge bg-secondary">Unread</span>
                            @endif
                        </td>
{{--                        <td>--}}
{{--                            <a href="{{ route('notifications.show', $notification->id) }}"--}}
{{--                               class="btn btn-sm btn-primary">--}}
{{--                                View--}}
{{--                            </a>--}}
{{--                        </td>--}}
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">No notifications found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            <div class="mt-3">
                {{ $notifications->links() }}
            </div>
        </div>
    </div>
@endsection