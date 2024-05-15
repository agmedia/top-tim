<div>
    <div class="block-header p-0 mb-2" wire:ignore>
        <h3 class="block-title">{{ __('back/attribute.vrijednosti_atributa') }}</h3>
        <a class="btn btn-success btn-sm" href="javascript:void(0);" wire:click="addItem()">
            <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1">{{ __('back/attribute.dodaj_vrijednost') }}</span>
        </a>
    </div>

    <table class="table table-striped table-borderless table-vcenter">
        <thead class="thead-light">
        <tr>
            <th class="font-size-sm" style="width:35%"> <img src="{{ asset('media/flags/hr.png') }}" /></th>
            <th class="font-size-sm" style="width:35%"><img src="{{ asset('media/flags/en.png') }}" /></th>
            <th class="font-size-sm" style="width:10%">{{ __('back/attribute.sort') }}</th>
            <th class="text-right font-size-sm"  style="width:20%">{{ __('back/attribute.uredi') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($items as $key => $item)
            <tr>
                <input type="hidden" name="item[{{ $key }}][id]" wire:model="items.{{ $key }}.id">
                <td>
                    <span class="font-size-sm"> <input type="text" class="form-control form-control-sm" wire:model="items.{{ $key }}.title.hr" name="item[{{ $key }}][title][hr]"></span>
                </td>
                <td>
                    <span class="font-size-sm"> <input type="text" class="form-control form-control-sm" wire:model="items.{{ $key }}.title.en" name="item[{{ $key }}][title][en]"></span>
                </td>
                <td>
                    <span class="font-size-sm"> <input type="number" class="form-control form-control-sm" wire:model="items.{{ $key }}.sort_order" name="item[{{ $key }}][sort_order]"></span>
                </td>
                <td class="text-right font-size-sm">
                    <button type="button" class="btn btn-sm btn-alt-success"><i class="fa fa-save"></i></button>

                    <a href="javascript:void();" wire:click="deleteItem({{ $key }})" class="btn btn-sm btn-alt-danger"><i class="fa fa-fw fa-trash-alt"></i></a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@push('js_after')
    <script>
        document.addEventListener('livewire:load', function () {
            /*$('#countries-select').select2({
                placeholder: "{{ __('back/app.geozone.select_country') }}"
            });

            $('#countries-select').on('change', function (e) {
                var data = $('#countries-select').select2("val");
                console.log(data);
                @this.stateSelected(data);
            });*/
        });

        Livewire.on('success_alert', () => {

        });

        Livewire.on('error_alert', () => {

        });
    </script>
@endpush
