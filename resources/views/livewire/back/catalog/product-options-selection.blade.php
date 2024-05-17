<div>
    @foreach ($items as $group => $item)
        <div class="block-header p-0 mb-2" wire:ignore>
            <h3 class="block-title">{{ $group }}</h3>
            <a class="btn btn-success btn-sm" href="javascript:void(0);" wire:click="addItem('{{ $group }}')">
                <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1">{{ __('back/attribute.dodaj_vrijednost') }}</span>
            </a>
        </div>


        <table class="table table-striped table-borderless table-vcenter">
            <thead class="thead-light">
            <tr>
                <th class="font-size-sm" style="width:25%">Vrijednost</th>
                <th class="font-size-sm">Šifra</th>
                <th class="font-size-sm">Količina</th>
                <th class="font-size-sm">Cijena</th>
                <th class="text-right font-size-sm"  class="text-center">Uredi</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($item['options'] as $key => $option)
                <tr>
                    <td class="font-size-sm">
                        <select class="js-select2 form-control form-control-sm form-select-solid" id="select-{{ $group }}-{{ $key }}" wire:model="items.{{ $group }}.options.{{ $key }}.value" name="options[{{ $group }}][{{ $key }}][value]" style="width: 100%;" data-placeholder="Odaberite opciju">
                            <option></option>
                            @foreach ($item['selections'] as $selection)
                                <option value="{{ $selection['id'] }}">{{ $selection['title'] }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <span class="font-size-sm"> <input type="text" class="form-control form-control-sm" wire:model="items.{{ $group }}.options.{{ $key }}.sku" name="options[{{ $group }}][{{ $key }}][sku]"> </span>
                    </td>
                    <td>
                        <span class="font-size-sm"> <input type="number" class="form-control form-control-sm" wire:model="items.{{ $group }}.options.{{ $key }}.qty" name="options[{{ $group }}][{{ $key }}][qty]"> </span>
                    </td>
                    <td>
                        <span class="font-size-sm"> <input type="text" class="form-control form-control-sm" wire:model="items.{{ $group }}.options.{{ $key }}.price" name="options[{{ $group }}][{{ $key }}][price]"></span>
                    </td>
                    <td class="text-right font-size-sm">
                        <a href="javascript:void();" wire:click="deleteItem('{{ $group }}',{{ $key }})" class="btn btn-sm btn-alt-danger"><i class="fa fa-fw fa-trash-alt"></i></a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endforeach

</div>

@push('product_scripts')
    <script>
        document.addEventListener('livewire:load', function () {
            @foreach ($items as $group => $item)
            @foreach ($item['options'] as $key => $option)
            $('#select-{{ $group }}-{{ $key }}').select2({
                placeholder: "{{ __('back/app.geozone.select_country') }}"
            });
            @endforeach
            @endforeach
        });

        Livewire.on('success_alert', () => {

        });

        Livewire.on('error_alert', () => {

        });
    </script>
@endpush
