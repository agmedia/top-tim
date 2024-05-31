@push('product_css')
    <style>
        .fileContainer {
            overflow: hidden;
            position: relative;
        }

        .fileContainer [type=file] {
            cursor: inherit;
            display: block;
            font-size: 999px;
            filter: alpha(opacity=0);
            min-height: 34px;
            min-width: 100%;
            opacity: 0;
            position: absolute;
            right: 0;
            text-align: right;
            top: 0;
        }

        .fileContainer {
            background: #E3E3E3;
            float: left;
            padding: .5em 1.5rem;
            height: 34px;
        }

        .fileContainer [type=file] {
            cursor: pointer;
        }

        img.preview {
            width: 200px;
            background-color: white;
            border: 1px solid #DDD;
            padding: 5px;
        }
    </style>
@endpush

<div>
    <div class="row">
        <div class="col-12">
            <div class="file-drop-area">
                <label for="files" style="display: block;padding: 1rem 2rem;border: 1px solid #CCCCCC;background-color: #eee;text-align: center;cursor: pointer;">Odaberite fotografiju... Ili više njih...</label>
                <input name="files[][image]" id="files" type="file" multiple>
            </div>
        </div>
    </div>

    <div class="row items-push" id="sortable">
        @if (isset($resource))

            <div class="col-sm-12">
                <div class="row items-push" id="new-images">
                    @if (! empty($existing))
                        @foreach($existing as $image)

                            <div class="col-sm-12 animated fadeIn mb-0 p-3 ribbon ribbon-left ribbon-bookmark ribbon-crystal" id="{{ 'image_id_' . $image['id'] }}">
                                <div class="row form-group mt-2">
                                    <div class="@if ($image['default']) col-md-5 @else col-md-4 @endif">
                                        <div class="options-container fx-item-zoom-in fx-overlay-zoom-out">
                                            @if ($image['default'])
                                                <div class="ribbon-box" style="background-color: #c3c3c3">
                                                    <i class="fa fa-check"></i> Glavna Slika
                                                </div>
                                            @endif
                                            <div class="slim"
                                                 {{--data-service="{{ route('images.ajax.upload') }}"--}}
                                                 data-ratio="free"
                                                 {{--                                         data-size="600,800"--}}
                                                 data-max-file-size="2"
                                                 data-meta-type="gallery"
                                                 data-meta-type_id="{{ $resource->id }}"
                                                 data-meta-image_id="{{ $image['id'] }}"
                                                 data-will-remove="removeImage"
                                            >
                                                <img src="{{ asset($image['image']) }}" alt="{{ 'image_' . $image['id'] }}"/>
                                                <input type="file" name="slim[{{ $image['id'] }}][image]"/>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="@if ($image['default']) col-md-7 @else col-md-8 @endif pl-4">
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <div class="form-group mb-2">
                                                    <label for="title-input" class="w-100">Naziv fotografije
                                                        <ul class="nav nav-pills float-right">
                                                            @foreach(ag_lang() as $lang)
                                                                <li @if ($lang->code == current_locale()) class="active" @endif>
                                                                    <a class="btn btn-sm btn-outline-secondary ml-2 @if ($lang->code == current_locale()) active @endif " data-toggle="pill" href="#title-{{ $image['id'] . $lang->code }}">
                                                                        <img src="{{ asset('media/flags/' . $lang->code . '.png') }}" />
                                                                    </a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </label>

                                                    <div class="tab-content">
                                                        @foreach(ag_lang() as $lang)
                                                            <div id="title-{{ $image['id'] . $lang->code }}" class="tab-pane @if ($lang->code == current_locale()) active @endif">
                                                                <input type="text" class="form-control" id="title-input-{{ $lang->code }}" name="slim[{{ $image['id'] }}][title][{{ $lang->code }}]" placeholder="{{ $lang->code }}" value="{{ $image['title'][$lang->code] }}">
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12 mt-3">
                                                <label for="alt-input" class="w-100">Alternativni tekst fotografije
                                                    <ul class="nav nav-pills float-right">
                                                        @foreach(ag_lang() as $lang)
                                                            <li @if ($lang->code == current_locale()) class="active" @endif>
                                                                <a class="btn btn-sm btn-outline-secondary ml-2 @if ($lang->code == current_locale()) active @endif " data-toggle="pill" href="#alt-{{ $image['id'] . $lang->code }}">
                                                                    <img src="{{ asset('media/flags/' . $lang->code . '.png') }}" />
                                                                </a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </label>
                                                <div class="tab-content">
                                                    @foreach(ag_lang() as $lang)
                                                        <div id="alt-{{ $image['id'] . $lang->code }}" class="tab-pane @if ($lang->code == current_locale()) active @endif">
                                                            <input type="text" class="form-control" id="alt-input-{{ $lang->code }}" name="slim[{{ $image['id'] }}][alt][{{ $lang->code }}]" placeholder="{{ $lang->code }}" value="{{ $image['alt'][$lang->code] }}">
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-6 text-left">
                                                <select class="js-select2 form-control  form-select-solid" id="select-product-option" name="slim[{{ $image['id'] }}][option_id]"  data-placeholder="Odaberite opciju">
                                                    <option value="0"> Pridruži opciju</option>
                                                    @foreach ($color_options as $option)
                                                        <option value="{{ $option['id'] }}" {{ ($image['option_id'] == $option['id']) ? 'selected' : '' }}>{{ $option['title'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <label class="col-sm-3 text-right font-size-sm pt-2" >Redosljed</label>
                                            <div class="col-sm-3">
                                                <input type="text" class="form-control js-tooltip-enabled" name="slim[{{ $image['id'] }}][sort_order]" value="{{ $image['sort_order'] }}" data-toggle="tooltip" data-placement="top" title="Sort Order">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12 text-right mb-2">
                                                <div class="custom-control custom-radio mb-1">
                                                    <input type="radio" class="custom-control-input" id="radio-default" name="slim[{{ $image['id'] }}][default]" value="{{ $image['id'] }}" @if ($image['default']) checked @endif>
                                                    <label class="custom-control-label" for="radio-default">Glavna fotografija</label>
                                                </div>
                                            </div>
                                            <div class="col-md-12 text-right">
                                                <div class="custom-control custom-checkbox custom-checkbox-square custom-control-success mb-1">
                                                    <input type="checkbox" class="custom-control-input" id="check-published[{{ $image['id'] }}]" name="slim[{{ $image['id'] }}][published]" @if($image['published']) checked @endif>
                                                    <label class="custom-control-label" for="check-published[{{ $image['id'] }}]">Vidljivost foto.</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        @endforeach
                        <input type="hidden" name="images_order" id="images-order">
                    @endif
                </div>
            </div>
        @else
            <div class="row items-push" id="new-images"></div>
        @endif
    </div>

</div>

@push('product_scripts')

    <script>
        /*let el = $('#max');

        el.maxlength({
            alwaysShow: true,
            threshold: el.data('threshold') || 10,
            warningClass: el.data('warning-class') || 'badge badge-warning',
            limitReachedClass: el.data('limit-reached-class') || 'badge badge-danger',
            placement: el.data('placement') || 'bottom',
            preText: el.data('pre-text') || '',
            separator: el.data('separator') || '/',
            postText: el.data('post-text') || ''
        });*/

        $(() => {
            // Override for radio buttons unchecking
            $('input[type="radio"]').click((e) => {
                if (e.currentTarget.id == 'radio-default') {
                    var matches = document.querySelectorAll('input[type="radio"]');

                    for (match in matches) {
                        if (e.currentTarget.value != matches[match].value) {
                            matches[match].checked = false;
                        }
                    }
                }
            })
        })
    </script>

    <script>
        //
        let blocks = "{{ (isset($resource) && isset($existing)) ? count($existing) : 0 }}";
        let created_id = 0;
        // get a reference to the file drop area and the file input
        var fileDropArea = document.querySelector('.file-drop-area');
        var fileInput = fileDropArea.querySelector('input');
        var fileInputName = fileInput.name;

        // listen to events for dragging and dropping
        fileDropArea.addEventListener('dragover', handleDragOver);
        fileDropArea.addEventListener('drop', handleDrop);
        fileInput.addEventListener('change', handleFileSelect);

        /**
         *
         * @param e
         */
        function handleDragOver(e) {
            e.preventDefault();
        }

        /**
         *
         * @param e
         */
        function handleDrop(e) {
            e.preventDefault();
            handleFileItems(e.dataTransfer.items || e.dataTransfer.files);
        }

        /**
         *
         * @param e
         */
        function handleFileSelect(e) {
            handleFileItems(e.target.files);
        }

        /**
         * loops over a list of items
         *
         * @param items
         */
        function handleFileItems(items) {
            let l = items.length;
            for (let i=0; i<l; i++) {
                handleItem(items[i]);
            }
        }

        /**
         *
         * @param item
         */
        function handleItem(item) {
            // get file from item
            let file = item;
            if (item.getAsFile && item.kind == 'file') {
                file = item.getAsFile();
            }

            handleFile(file);
        }

        /**
         * now we're sure each item is a file
         *
         * @param file
         */
        function handleFile(file) {
            createCropper(file);
        }

        /**
         * create an Image Cropper for each passed file
         *
         * @param file
         */
        function createCropper(file) {
            // create container element for cropper
            let holder = document.getElementById('new-images');

            let col = document.createElement('div');
            col.className = 'col-lg-3 col-md-4 animated fadeIn mb-5 p-3 ribbon ribbon-left ribbon-bookmark ribbon-crystal';

            let cropper = document.createElement('div');

            // insert this element after the file drop area
            col.insertAdjacentElement('afterbegin', cropper);
            col.insertAdjacentHTML('beforeend', '<div class="row form-group mt-2">\n' +
                '                                    <div class="col-sm-4" style="padding-right: 0;">\n' +
                '                                        <input type="text" class="form-control js-tooltip-enabled" name="files[' + created_id + '][sort_order]" value="' + blocks + '" data-toggle="tooltip" data-placement="top" title="Sort Order">\n' +
                '                                    </div>\n' +
                '                                    <div class="col-sm-8 text-right">\n' +
                '                                        <label class="css-control css-control-primary css-radio mt-2">\n' +
                '                                            <input type="radio" class="css-control-input" name="files[' + created_id + '][default]" value="image/' + file.name + '">\n' +
                '                                            <span class="mr-2">Default</span> <span class="css-control-indicator"></span>\n' +
                '                                        </label>\n' +
                '                                    </div>\n' +
                '                                </div>');

            holder.insertAdjacentElement('beforeend', col);

            // create a Slim Cropper
            Slim.create(cropper, {
                ratio: 'free',
                //size: '600,800',
                maxFileSize: '2',
                service: false,
                meta: {
                    type: 'gallery',
                    type_id: "{{ isset($resource) ? $resource->id : '' }}",
                    image_id: 0
                },
                defaultInputName: fileInputName,
                didInit: function() {
                    // load the file to our slim cropper
                    this.load(file);

                },
                didRemove: function(data, slim) {
                    col.parentNode.removeChild(col)
                    // destroy the slim cropper
                    this.destroy();

                }
            });

            blocks++;
            created_id++;
        }

        /**
         *
         * @param xhr
         */
        function handleXHRRequest(xhr) {
            xhr.setRequestHeader('X-CSRF-TOKEN', "{{ csrf_token() }}");

            console.log(fileInput)
        }

        /**
         *
         * @param data
         * @param slim
         */
        function removeImage(data, slim) {
            if (data.meta.hasOwnProperty('image_id')) {
                axios.post("{{ $delete_url }}", { data: data.meta.image_id })
                    .then((response) => {
                        successToast.fire({
                            text: 'Fotografija je uspješno izbrisana',
                        })

                        let elem = document.getElementById('image_id_' + data.meta.image_id);

                        elem.parentNode.removeChild(elem);
                    })
                    .catch((error) => {
                        errorToast.fire({
                            text: 'Greška u brisanju fotografije..! Molimo pokušajte ponovo.',
                        })
                    })
            } else {
                errorToast.fire({
                    text: 'Glavna slika se ne može izbrisati..!',
                })
            }

            //slim.destroy();
        }

        // hide file input, we can now upload with JavaScript
        fileInput.style.display = 'none';

        // remove file input name so it's value is
        // not posted to the server
        fileInput.removeAttribute('name');
    </script>

@endpush
