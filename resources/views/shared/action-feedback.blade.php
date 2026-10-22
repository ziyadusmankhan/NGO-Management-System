@if (session('success'))
    <div role="status" style="margin-bottom:18px;padding:14px 16px;border:1px solid #cce8d8;border-radius:10px;background:#edf8f1;color:#226640;">
        {{ session('success') }}
    </div>
@endif
@if (session('error') || $errors->any())
    <div role="alert" style="margin-bottom:18px;padding:14px 16px;border:1px solid #efcccc;border-radius:10px;background:#fff3f3;color:#a13d3d;">
        @if (session('error')) <p>{{ session('error') }}</p> @endif
        @if ($errors->any())
            <ul style="margin:0;padding-left:20px;">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        @endif
    </div>
@endif
