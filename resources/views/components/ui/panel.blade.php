@props(['title', 'href' => null, 'link' => 'View all'])
<section {{ $attributes->class(['dashboard-card']) }}><header class="card-header-custom"><h2 style="font-size:16px;margin:0">{{ $title }}</h2>@if($href)<a href="{{ $href }}">{{ $link }} →</a>@endif</header><div class="card-body-custom">{{ $slot }}</div></section>
