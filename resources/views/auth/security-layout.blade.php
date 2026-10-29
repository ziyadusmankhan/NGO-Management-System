<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Account Security</title>
<link rel="stylesheet" href="{{ asset('css/views/auth-security-layout-1.css') }}"><link rel="stylesheet" href="{{ asset('css/foundation-auth.css') }}"><script src="{{ asset('js/theme-init.js') }}"></script><link rel="stylesheet" href="{{ asset('css/foundation-legacy-tokens.css') }}"><link rel="stylesheet" href="{{ asset('css/foundation-theme.css') }}?v=2"></head><body><main>
@if($errors->any())<div class="error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@if(session('success'))<p class="success">{{ session('success') }}</p>@endif
@yield('content')</main></body></html>
