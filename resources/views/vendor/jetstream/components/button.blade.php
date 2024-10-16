<button {{ $attributes->merge(['type' => 'submit', 'class' => ' mb-3']) }}>
    {{ $slot }}
</button>
