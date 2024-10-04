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
            @foreach (ag_lang() as $lang)
                <th class="font-size-sm" style="width:35%"> <img src="{{ asset('media/flags/' . $lang->code . '.png') }}" /></th>
            @endforeach
            @if ($type == 'color')
                <th class="font-size-sm" style="width:10%">Boja</th>
                <th colspan="2"  class="font-size-sm" style="width:10%">Boja dodatna</th>

                <th class="font-size-sm" style="width:10%">{{ __('Šifra') }}</th>
                @endif
            <th class="font-size-sm" style="width:10%">{{ __('back/attribute.sort') }}</th>
            <th class="text-right font-size-sm"  style="width:20%">{{ __('back/attribute.obrisi') }}</th>
        </tr>
        </thead>
        <tbody>


        @foreach ($items as $key => $item)
            <tr>
                <input type="hidden" name="item[{{ $key }}][id]" wire:model="items.{{ $key }}.id">
                @foreach (ag_lang() as $lang)
                    <td>
                        <span class="font-size-sm"><input type="text" class="form-control form-control-sm" wire:model="items.{{ $key }}.title.{{ $lang->code }}" name="item[{{ $key }}][title][{{ $lang->code }}]" required></span>
                    </td>
                @endforeach
                @if ($type == 'color')
                    <td>
                        <span class="font-size-sm"> <input type="color" class="form-control form-control-sm" wire:model="items.{{ $key }}.color" name="item[{{ $key }}][color]"></span>
                    </td>
                    <td>
                        <span class="font-size-sm"> <input type="checkbox"  class="form-check"  wire:model="items.{{ $key }}.color_opt_show"></span>
                    </td>
                    <td>
                        @if($items[$key]['color_opt_show'])
                            <span class="font-size-sm"  > <input type="color" class="form-control form-control-sm" wire:model="items.{{ $key }}.color_opt"   name="item[{{ $key }}][color_opt]" ></span>
                        @endif
                    </td>


                <td>
                    <span class="font-size-sm"> <input type="text" class="form-control form-control-sm" wire:model="items.{{ $key }}.option_sku" name="item[{{ $key }}][option_sku]" required></span>
                </td>
                @endif
                <td>
                    <span class="font-size-sm"> <input type="number" class="form-control form-control-sm" wire:model="items.{{ $key }}.sort_order" name="item[{{ $key }}][sort_order]"></span>
                </td>
                <td class="text-right font-size-sm">
                    <button type="button" class="btn btn-sm btn-alt-success d-none"><i class="fa fa-save"></i></button>

                    <a href="javascript:void();" wire:click="deleteItem({{ $key }})" class="btn btn-sm btn-alt-danger"><i class="fa fa-fw fa-trash-alt"></i></a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@push('js_after')
    <script>
        Livewire.on('success_alert', () => {

        });

        Livewire.on('error_alert', () => {

        });
    </script>
@endpush
