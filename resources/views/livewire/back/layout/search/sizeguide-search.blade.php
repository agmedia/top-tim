<div class="mb-0 input-group">
    <input type="search" wire:model.debounce.300ms="search" class="form-control  @error('sizeguide_id') is-invalid @enderror" id="sizeguide-input" placeholder="{{ !$list ? 'Upište naziv...' : 'Ipišite naziv...' }}" autocomplete="off">


    @if ( ! $list)
        <input type="hidden" wire:model="sizeguide_id" name="sizeguide_id">


    @endif


    @if( ! empty($search_results))
        <div class="autocomplete pt-1" style="position:absolute; z-index:10; top:38px; background-color: #f6f6f6; border: 1px solid #d7d7d7;width:100%">
            <div id="myInputautocomplete-list" class="autocomplete-items">
                @foreach($search_results as $sizeguide)
                    <div style="cursor: pointer;border-bottom: 1px solid #d7d7d7;padding-bottom: 10px;padding-left: 10px;font-size: 16px" wire:click="addSizeGuide('{{ $sizeguide->id }}')">
                        <small class="font-weight-lighter">Ime: <strong>{{ $sizeguide->translation->title }}</strong></small>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            document.addEventListener('livewire:load', function () {

            });

            Livewire.on('success_alert', () => {

            });

            Livewire.on('error_alert', (e) => {

            });
        </script>
    @endpush

</div>
