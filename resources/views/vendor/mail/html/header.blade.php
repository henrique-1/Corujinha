@props(['url'])
<tr>
<td class="header" style="text-align: center; padding: 25px 0 15px 0;">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
    <img src="{{ asset('images/logo/logo-horizontal.webp') }}?v={{ file_exists(public_path('images/logo/logo-horizontal.webp')) ? filemtime(public_path('images/logo/logo-horizontal.webp')) : '1' }}" class="logo" alt="{{ config('app.name', 'Corujinha') }}" style="max-height: 52px; width: auto; display: block; margin: 0 auto;">
</a>
</td>
</tr>
