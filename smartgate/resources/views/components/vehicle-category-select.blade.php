@props(['selected' => null, 'name' => 'vehicle_type', 'id' => null, 'required' => false, 'categories' => null])

<select name="{{ $name }}" id="{{ $id ?? $name }}" {{ $required ? 'required' : '' }} {{ $attributes->merge(['class' => 'form-control']) }}>
    <option value="" disabled {{ !$selected ? 'selected' : '' }}>Select Category...</option>
    @if($categories && $categories->count())
        @foreach($categories as $cat)
            <option value="{{ $cat->name }}" {{ $selected == $cat->name ? 'selected' : '' }}>{{ $cat->name }}</option>
        @endforeach
    @else
        <option value="motorcycle" {{ $selected == 'motorcycle' ? 'selected' : '' }}>Motorcycle</option>
        <option value="car" {{ $selected == 'car' ? 'selected' : '' }}>Car / Sedan</option>
        <option value="suv" {{ $selected == 'suv' ? 'selected' : '' }}>SUV / Van</option>
        <option value="pickup" {{ $selected == 'pickup' ? 'selected' : '' }}>Pickup</option>
        <option value="truck" {{ $selected == 'truck' ? 'selected' : '' }}>Truck</option>
    @endif
</select>
