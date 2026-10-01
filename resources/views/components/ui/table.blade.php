<div class="overflow-x-auto">
    <table {{ $attributes->merge(['class' => 'w-full text-left text-sm']) }}>
        <thead class="border-y border-slate-100 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
            <tr>{{ $head }}</tr>
        </thead>
        <tbody class="divide-y divide-slate-100">{{ $slot }}</tbody>
    </table>
</div>