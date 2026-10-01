{{-- $detail (EmployeePersonalDetail|null), $phone, $required (bool) --}}
@php
    $req   = $required ?? false;
    $birth = $detail?->birth_date ? \Illuminate\Support\Carbon::parse($detail->birth_date)->format('Y-m-d') : null;
@endphp
<div class="grid gap-5 sm:grid-cols-2">
    <x-ui.input name="personal_email" type="email" label="Personal email" :value="$detail?->personal_email" autocomplete="email" :required="$req" />
    <x-ui.input name="phone" type="tel" label="Mobile number" :value="$phone" autocomplete="tel" :required="$req" />
    <x-ui.input name="birth_date" type="date" label="Birthday" :value="$birth" autocomplete="bday" :required="$req" />
    <div class="hidden sm:block"></div>
    <div class="sm:col-span-2"><x-ui.input name="address_line1" label="Address line 1" :value="$detail?->address_line1" autocomplete="address-line1" :required="$req" /></div>
    <div class="sm:col-span-2"><x-ui.input name="address_line2" label="Address line 2 (optional)" :value="$detail?->address_line2" autocomplete="address-line2" /></div>
    <x-ui.input name="barangay" label="Barangay (optional)" :value="$detail?->barangay" />
    <x-ui.input name="city" label="City / municipality" :value="$detail?->city" autocomplete="address-level2" :required="$req" />
    <x-ui.input name="province" label="Province" :value="$detail?->province" autocomplete="address-level1" :required="$req" />
    <x-ui.input name="postal_code" label="Postal code" :value="$detail?->postal_code" autocomplete="postal-code" :required="$req" />
</div>