@props(['url'])
<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            <img src="{{ Vite::asset('resources/assets/images/logo-blue.png') }}" style="width: 100%; max-width: 140px; height: auto;" alt="{{ config('app.name') }}">
        </a>
    </td>
</tr>