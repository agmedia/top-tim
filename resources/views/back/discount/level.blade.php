<div class="row align-items-end border rounded mx-0 p-2 mb-3" data-tier-row>
    <input type="hidden" data-field="id" name="tiers[{{ $index }}][id]" value="{{ $level['id'] ?? '' }}">
    <div class="form-group col-md-4 mb-2">
        <label data-for="min_quantity" for="discount-level-{{ $index }}-min_quantity">Od komada brenda</label>
        <input data-field="min_quantity" id="discount-level-{{ $index }}-min_quantity" name="tiers[{{ $index }}][min_quantity]" type="number" min="1" max="1000000" step="1" required class="form-control" placeholder="npr. 10" value="{{ $level['min_quantity'] ?? '' }}">
    </div>
    <div class="form-group col-md-4 mb-2">
        <label data-for="percentage" for="discount-level-{{ $index }}-percentage">Popust (%)</label>
        <input data-field="percentage" id="discount-level-{{ $index }}-percentage" name="tiers[{{ $index }}][percentage]" type="number" min="0.01" max="100" step="0.01" required class="form-control" placeholder="npr. 10" value="{{ $level['percentage'] ?? '' }}">
    </div>
    <div class="form-group col-md-2 mb-2">
        <input type="hidden" data-field="active" name="tiers[{{ $index }}][active]" value="0">
        <label data-for="active" for="discount-level-{{ $index }}-active"><input data-field="active" id="discount-level-{{ $index }}-active" type="checkbox" name="tiers[{{ $index }}][active]" value="1" @checked($level['active'] ?? false)> Aktivno</label>
    </div>
    <div class="form-group col-md-2 mb-2 text-md-right">
        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-tier aria-label="Ukloni razinu popusta">Ukloni</button>
    </div>
</div>
