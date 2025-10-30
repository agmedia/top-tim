@php($groupSlug = \Illuminate\Support\Str::slug($group))

<div class="mb-0 input-group">
    <input
        type="search"
        wire:model.debounce.300ms="search"
        class="form-control @error('attribute_id') is-invalid @enderror"
        id="attribute-input-{{ $groupSlug }}"
        placeholder="{{ $group }}"
        autocomplete="off"
    >

    @if (!$list)
        <input
            type="hidden"
            wire:model="attribute_id"
            name="attributes[{{ $groupSlug }}]"
        >

        <span class="input-group-append" data-toggle="modal" data-target="#new-attribute-modal-{{ $groupSlug }}">
      <a href="javascript:void(0)" wire:click="viewAddWindow" class="btn btn-secondary btn-search py-0">
        <i class="fa fa-plus pt-2"></i>
      </a>
    </span>

        <div class="autocomplete p-3"
             @if(!$show_add_window) hidden @endif
             style="position:absolute; z-index:10; top:38px; background:#f6f6f6; border:1px solid #d7d7d7;">
            <div class="row">
                <div class="mb-4 col-12">
                    <label class="form-label required" for="input-title-{{ $groupSlug }}">{{ $group }} - Naziv</label>
                    <input type="text"
                           id="input-title-{{ $groupSlug }}"
                           class="form-control @if (session()->has('title')) is-invalid @endif"
                           wire:model.defer="new.title">
                    @if (session()->has('title'))
                        <label class="small text-danger">Ime brenda je obvezno...</label>
                    @endif
                </div>

                <div class="mb-0 mt-1 col-12 text-right">
                    <a href="javascript:void(0)" wire:click="makeNewAttribute" class="btn btn-primary btn-save shadow-sm">
                        <i class="align-middle" data-feather="save"></i> Snimi
                    </a>
                </div>
            </div>
        </div>
    @endif

    @if(!empty($search_results))
        <div class="autocomplete pt-1"
             style="position:absolute; z-index:10; top:38px; background:#f6f6f6; border:1px solid #d7d7d7; width:100%">
            <div id="autocomplete-list-{{ $groupSlug }}" class="autocomplete-items">
                @foreach($search_results as $attribute)
                    <div
                        wire:key="suggest-{{ $groupSlug }}-{{ $attribute->id }}"
                        style="cursor:pointer; border-bottom:1px solid #d7d7d7; padding:10px; font-size:16px"
                        wire:click="addAttribute({{ $attribute->id }})"  {{-- BEZ navodnika --}}
                    >
                        <small class="font-weight-lighter">
                            Ime: <strong>{{ $attribute->translation->title }}</strong>
                        </small>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            Livewire.on('success_alert', (payload) => {
                successToast.fire({ title: payload?.message || 'OK', timer: 2160 });
            });

            Livewire.on('error_alert', (payload) => {
                errorToast.fire({ title: payload?.message || 'Greška', timer: 2160 });
            });
        </script>
    @endpush
</div>
