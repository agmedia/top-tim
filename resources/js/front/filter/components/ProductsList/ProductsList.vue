<template>
    <section class="col">
        <!-- Toolbar-->
        <div class="d-flex justify-content-between align-items-center pt-2 pb-4 pb-sm-2">
            <div class="d-flex flex-wrap pb-3">



                <button class="btn btn-outline-secondary bg-white btn-icon" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight"><i class="ci-filter me-2"></i> Filter</button>



            </div>

            <div class="d-flex flex-wrap ">


                <div class="d-flex align-items-center flex-nowrap me-0  pb-3">
                    <select class="form-select pe-2" style="max-width: 120px;" v-model="sorting">
                        <option value="">{{ trans.sortiraj }}</option>
                        <option value="novi">{{ trans.najnovije }}</option>
                        <option value="price_up">{{ trans.najmanja_cijena }}</option>
                        <option value="price_down">{{ trans.najveca_cijena }}</option>
                        <option value="naziv_up">{{ trans.a_z }}</option>
                        <option value="naziv_down">{{ trans.z_a }}</option>
                    </select>
                </div>

            </div>


            <div class="d-flex pb-3 d-none"><span class="fs-sm text-dark btn btn-white btn-sm text-nowrap ms-0 d-block">{{ products.total ? Number(products.total).toLocaleString('hr-HR') : 0 }} {{ trans.artikala }}</span></div>
            <div class="d-flex d-sm-none pb-3">
                <button class="btn btn-outline-secondary bg-white btn-icon nav-link-style  me-1" v-on:click="tworow()" >2</button>

                <button class="btn btn-outline-secondary bg-white  btn-icon btn-sm nav-link-style " v-on:click="onerow()">1</button>
            </div>


        </div>

        <!-- Offcanvas -->
        <div class="offcanvas offcanvas-end" id="offcanvasRight" tabindex="-1">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title">Filter</h5>
                <button class="btn-close" type="button" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body" data-simplebar>
                <div class="row pt-2">

                    <div class="col-lg-12">

                        <!-- Filter by Brand-->
                        <div class=" mb-grid-gutter">
                            <div class=" px-2">
                                <div class="widget widget-filter">
                                    <h3 class="widget-title">Brand</h3>
                                    <div class="input-group input-group-sm mb-2">
                                        <input class="widget-filter-search form-control rounded-end pe-5" type="text" placeholder="Search"><i class="ci-search position-absolute top-50 end-0 translate-middle-y fs-sm me-3"></i>
                                    </div>
                                    <ul class="widget-list widget-filter-list list-unstyled pt-1" style="max-height: 11rem;" data-simplebar data-simplebar-auto-hide="false">
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="adidas">
                                                <label class="form-check-label widget-filter-item-text" for="adidas">Adidas</label>
                                            </div><span class="fs-xs text-muted">425</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="ataylor">
                                                <label class="form-check-label widget-filter-item-text" for="ataylor">Ann Taylor</label>
                                            </div><span class="fs-xs text-muted">15</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="armani">
                                                <label class="form-check-label widget-filter-item-text" for="armani">Armani</label>
                                            </div><span class="fs-xs text-muted">18</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="banana">
                                                <label class="form-check-label widget-filter-item-text" for="banana">Banana Republic</label>
                                            </div><span class="fs-xs text-muted">103</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="bilabong">
                                                <label class="form-check-label widget-filter-item-text" for="bilabong">Bilabong</label>
                                            </div><span class="fs-xs text-muted">27</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="birkenstock">
                                                <label class="form-check-label widget-filter-item-text" for="birkenstock">Birkenstock</label>
                                            </div><span class="fs-xs text-muted">10</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="klein">
                                                <label class="form-check-label widget-filter-item-text" for="klein">Calvin Klein</label>
                                            </div><span class="fs-xs text-muted">365</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="columbia">
                                                <label class="form-check-label widget-filter-item-text" for="columbia">Columbia</label>
                                            </div><span class="fs-xs text-muted">508</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="converse">
                                                <label class="form-check-label widget-filter-item-text" for="converse">Converse</label>
                                            </div><span class="fs-xs text-muted">176</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="dockers">
                                                <label class="form-check-label widget-filter-item-text" for="dockers">Dockers</label>
                                            </div><span class="fs-xs text-muted">54</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="fruit">
                                                <label class="form-check-label widget-filter-item-text" for="fruit">Fruit of the Loom</label>
                                            </div><span class="fs-xs text-muted">739</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="hanes">
                                                <label class="form-check-label widget-filter-item-text" for="hanes">Hanes</label>
                                            </div><span class="fs-xs text-muted">92</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="choo">
                                                <label class="form-check-label widget-filter-item-text" for="choo">Jimmy Choo</label>
                                            </div><span class="fs-xs text-muted">17</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="levis">
                                                <label class="form-check-label widget-filter-item-text" for="levis">Levi's</label>
                                            </div><span class="fs-xs text-muted">361</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="lee">
                                                <label class="form-check-label widget-filter-item-text" for="lee">Lee</label>
                                            </div><span class="fs-xs text-muted">264</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="wearhouse">
                                                <label class="form-check-label widget-filter-item-text" for="wearhouse">Men's Wearhouse</label>
                                            </div><span class="fs-xs text-muted">75</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="newbalance">
                                                <label class="form-check-label widget-filter-item-text" for="newbalance">New Balance</label>
                                            </div><span class="fs-xs text-muted">218</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="nike">
                                                <label class="form-check-label widget-filter-item-text" for="nike">Nike</label>
                                            </div><span class="fs-xs text-muted">810</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="navy">
                                                <label class="form-check-label widget-filter-item-text" for="navy">Old Navy</label>
                                            </div><span class="fs-xs text-muted">147</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="polo">
                                                <label class="form-check-label widget-filter-item-text" for="polo">Polo Ralph Lauren</label>
                                            </div><span class="fs-xs text-muted">64</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="puma">
                                                <label class="form-check-label widget-filter-item-text" for="puma">Puma</label>
                                            </div><span class="fs-xs text-muted">370</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="reebok">
                                                <label class="form-check-label widget-filter-item-text" for="reebok">Reebok</label>
                                            </div><span class="fs-xs text-muted">506</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="skechers">
                                                <label class="form-check-label widget-filter-item-text" for="skechers">Skechers</label>
                                            </div><span class="fs-xs text-muted">209</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="hilfiger">
                                                <label class="form-check-label widget-filter-item-text" for="hilfiger">Tommy Hilfiger</label>
                                            </div><span class="fs-xs text-muted">487</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="armour">
                                                <label class="form-check-label widget-filter-item-text" for="armour">Under Armour</label>
                                            </div><span class="fs-xs text-muted">90</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="urban">
                                                <label class="form-check-label widget-filter-item-text" for="urban">Urban Outfitters</label>
                                            </div><span class="fs-xs text-muted">152</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="vsecret">
                                                <label class="form-check-label widget-filter-item-text" for="vsecret">Victoria's Secret</label>
                                            </div><span class="fs-xs text-muted">238</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="wolverine">
                                                <label class="form-check-label widget-filter-item-text" for="wolverine">Wolverine</label>
                                            </div><span class="fs-xs text-muted">29</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="wrangler">
                                                <label class="form-check-label widget-filter-item-text" for="wrangler">Wrangler</label>
                                            </div><span class="fs-xs text-muted">115</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12 ">
                        <!-- Filter by Size-->
                        <div class=" mb-grid-gutter">
                            <div class=" px-2">
                                <div class="widget widget-filter">
                                    <h3 class="widget-title">Veličina</h3>
                                    <div class="input-group input-group-sm mb-2">
                                        <input class="widget-filter-search form-control rounded-end pe-5" type="text" placeholder="Search"><i class="ci-search position-absolute top-50 end-0 translate-middle-y fs-sm me-3"></i>
                                    </div>
                                    <ul class="widget-list widget-filter-list list-unstyled pt-1" style="max-height: 11.5rem;" data-simplebar data-simplebar-auto-hide="false">
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="size-xs">
                                                <label class="form-check-label widget-filter-item-text" for="size-xs">XS</label>
                                            </div><span class="fs-xs text-muted">34</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="size-s">
                                                <label class="form-check-label widget-filter-item-text" for="size-s">S</label>
                                            </div><span class="fs-xs text-muted">57</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="size-m">
                                                <label class="form-check-label widget-filter-item-text" for="size-m">M</label>
                                            </div><span class="fs-xs text-muted">198</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="size-l">
                                                <label class="form-check-label widget-filter-item-text" for="size-l">L</label>
                                            </div><span class="fs-xs text-muted">72</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="size-xl">
                                                <label class="form-check-label widget-filter-item-text" for="size-xl">XL</label>
                                            </div><span class="fs-xs text-muted">46</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="size-39">
                                                <label class="form-check-label widget-filter-item-text" for="size-39">39</label>
                                            </div><span class="fs-xs text-muted">112</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="size-40">
                                                <label class="form-check-label widget-filter-item-text" for="size-40">40</label>
                                            </div><span class="fs-xs text-muted">85</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="size-41">
                                                <label class="form-check-label widget-filter-item-text" for="size-40">41</label>
                                            </div><span class="fs-xs text-muted">210</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="size-42">
                                                <label class="form-check-label widget-filter-item-text" for="size-42">42</label>
                                            </div><span class="fs-xs text-muted">57</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="size-43">
                                                <label class="form-check-label widget-filter-item-text" for="size-43">43</label>
                                            </div><span class="fs-xs text-muted">30</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="size-44">
                                                <label class="form-check-label widget-filter-item-text" for="size-44">44</label>
                                            </div><span class="fs-xs text-muted">61</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="size-45">
                                                <label class="form-check-label widget-filter-item-text" for="size-45">45</label>
                                            </div><span class="fs-xs text-muted">23</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="size-46">
                                                <label class="form-check-label widget-filter-item-text" for="size-46">46</label>
                                            </div><span class="fs-xs text-muted">19</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="size-47">
                                                <label class="form-check-label widget-filter-item-text" for="size-47">47</label>
                                            </div><span class="fs-xs text-muted">15</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="size-48">
                                                <label class="form-check-label widget-filter-item-text" for="size-48">48</label>
                                            </div><span class="fs-xs text-muted">12</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="size-49">
                                                <label class="form-check-label widget-filter-item-text" for="size-49">49</label>
                                            </div><span class="fs-xs text-muted">8</span>
                                        </li>
                                        <li class="widget-filter-item d-flex justify-content-between align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="size-50">
                                                <label class="form-check-label widget-filter-item-text" for="size-50">50</label>
                                            </div><span class="fs-xs text-muted">6</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12 ">
                        <!-- Filter by Color-->
                        <div class=" mb-grid-gutter">
                            <div class=" px-2">
                                <div class="widget">
                                    <h3 class="widget-title">Boja</h3>
                                    <div class="d-flex flex-wrap">
                                        <div class="form-check form-option text-center mb-2 mx-1" style="width: 4rem;">
                                            <input class="form-check-input" type="checkbox" id="color-blue-gray">
                                            <label class="form-option-label rounded-circle" for="color-blue-gray"><span class="form-option-color rounded-circle" style="background-color: #b3c8db;"></span></label>
                                            <label class="d-block fs-xs text-muted mt-n1" for="color-blue-gray">Blue-gray</label>
                                        </div>
                                        <div class="form-check form-option text-center mb-2 mx-1" style="width: 4rem;">
                                            <input class="form-check-input" type="checkbox" id="color-burgundy">
                                            <label class="form-option-label rounded-circle" for="color-burgundy"><span class="form-option-color rounded-circle" style="background-color: #ca7295;"></span></label>
                                            <label class="d-block fs-xs text-muted mt-n1" for="color-burgundy">Burgundy</label>
                                        </div>
                                        <div class="form-check form-option text-center mb-2 mx-1" style="width: 4rem;">
                                            <input class="form-check-input" type="checkbox" id="color-teal">
                                            <label class="form-option-label rounded-circle" for="color-teal"><span class="form-option-color rounded-circle" style="background-color: #91c2c3;"></span></label>
                                            <label class="d-block fs-xs text-muted mt-n1" for="color-teal">Teal</label>
                                        </div>
                                        <div class="form-check form-option text-center mb-2 mx-1" style="width: 4rem;">
                                            <input class="form-check-input" type="checkbox" id="color-brown">
                                            <label class="form-option-label rounded-circle" for="color-brown"><span class="form-option-color rounded-circle" style="background-color: #9a8480;"></span></label>
                                            <label class="d-block fs-xs text-muted mt-n1" for="color-brown">Brown</label>
                                        </div>
                                        <div class="form-check form-option text-center mb-2 mx-1" style="width: 4rem;">
                                            <input class="form-check-input" type="checkbox" id="color-coral-red">
                                            <label class="form-option-label rounded-circle" for="color-coral-red"><span class="form-option-color rounded-circle" style="background-color: #ff7072;"></span></label>
                                            <label class="d-block fs-xs text-muted mt-n1" for="color-coral-red">Coral red</label>
                                        </div>
                                        <div class="form-check form-option text-center mb-2 mx-1" style="width: 4rem;">
                                            <input class="form-check-input" type="checkbox" id="color-navy">
                                            <label class="form-option-label rounded-circle" for="color-navy"><span class="form-option-color rounded-circle" style="background-color: #696dc8;"></span></label>
                                            <label class="d-block fs-xs text-muted mt-n1" for="color-navy">Navy</label>
                                        </div>
                                        <div class="form-check form-option text-center mb-2 mx-1" style="width: 4rem;">
                                            <input class="form-check-input" type="checkbox" id="color-charcoal">
                                            <label class="form-option-label rounded-circle" for="color-charcoal"><span class="form-option-color rounded-circle" style="background-color: #4e4d4d;"></span></label>
                                            <label class="d-block fs-xs text-muted mt-n1" for="color-charcoal">Charcoal</label>
                                        </div>
                                        <div class="form-check form-option text-center mb-2 mx-1" style="width: 4rem;">
                                            <input class="form-check-input" type="checkbox" id="color-sky-blue">
                                            <label class="form-option-label rounded-circle" for="color-sky-blue"><span class="form-option-color rounded-circle" style="background-color: #8bcdf5;"></span></label>
                                            <label class="d-block fs-xs text-muted mt-n1" for="color-sky-blue">Sky blue</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Products grid-->
        <div class="row row-cols-xxxl-5 row-cols-xxl-4 row-cols-xl-4 row-cols-lg-3 row-cols-md-3 row-cols-sm-2 row-cols-2 g-0 mx-n2 mb-5"  id="product-grid" v-if="products.total">
            <div class="px-2 mb-4 d-flex align-items-stretch" v-for="product in products.data">
                <div class="card product-card card-static pb-3">

                    <span class="badge bg-warning mt-1 ms-1 badge-end"  v-if="product.quantity <= 0">{{ trans.rasprodano }}</span>
                    <span class="badge rounded-pill bg-red mt-1 ms-1 badge-shadow" v-if="product.special">-{{ ($store.state.service.getDiscountAmount(product.price, product.special)) }}%</span>

                    <a class="card-img-top d-block pb-2 overflow-hidden " :href="origin + product.url"><img load="lazy" :src="product.image.replace('.webp', '-thumb.webp')" width="400" height="400" :alt="product.name">
                    </a>
                    <div class="card-body py-2 pt-0">

                        <h3 class="product-title fs-sm text-truncate"><a :href="origin + product.url">{{ product.name }}</a></h3>
                        <div class="d-flex flex-wrap justify-content-between align-items-center" v-if="product.category_string">
                            <div class="fs-sm me-2"><span v-html="product.category_string"></span></div>
                        </div>
                        <div class="product-price">
                            <span class="fs-sm text-muted"  v-if="product.special"><small>NC 30 dana: {{ product.main_price_text }} </small> <small v-if="product.secondary_price_text">{{ product.secondary_price_text }} </small></span>
                        </div>
                        <div class="product-price">
                            <span class="text-red fs-md" v-if="product.special">{{ product.main_special_text }} <small v-if="product.secondary_special_text">{{ product.secondary_special_text }} </small></span>
                        </div>
                        <div class="product-price">
                            <span class="text-dark fs-md" v-if="!product.special">{{ product.main_price_text }} <small v-if="product.secondary_price_text ">{{ product.secondary_price_text }} </small></span>
                        </div>
                    </div>
                    <div class="product-floating-btn" v-if="product.quantity > 0">
                        <button class="btn btn-primary btn-shadow btn-sm" :disabled="product.disabled" v-on:click="add(product.id, product.quantity)" type="button">+<i class="ci-cart fs-base ms-1"></i></button>
                    </div>
                </div>
            </div>
        </div>
        <pagination :data="products" align="center" :show-disabled="true" :limit="4" @pagination-change-page="getProductsPage"></pagination>

        <div class="row" v-if="!products_loaded">
            <div class="col-md-12 d-flex justify-content-center mt-4">
                <div class="spinner-border text-primary opacity-75" role="status" style="width: 9rem; height: 9rem;"></div>
            </div>
        </div>
        <div class="col-md-12 d-flex justify-content-center mt-4" v-if="products.total">
            <p class="fs-sm">{{ trans.prikazano }}
                <span class="font-weight-bolder mx-1">{{ products.from ? Number(products.from).toLocaleString('hr-HR') : 0 }}</span> {{ trans.do }}
                <span class="font-weight-bolder mx-1">{{ products.to ? Number(products.to).toLocaleString('hr-HR') : 0 }}</span> {{ trans.od }}
                <span class="font-weight-bold mx-1">{{ products.total ? Number(products.total).toLocaleString('hr-HR') : 0 }}</span> {{ trans.rezultata }}
            </p>
        </div>
        <div class="col-md-12 px-2 mb-4" v-if="products_loaded && search_zero_result">
            <h2>{{ trans.nema_rezultata }}</h2>
            <p> {{ trans.vasa_pretraga }} <mark>{{ search_query }}</mark> {{ trans.pronasla_nula }}.</p>
            <h4 class="h5">{{ trans.s1 }}</h4>
            <ul class="list-style">
                <li>{{ trans.s2 }}</li>
                <li>{{ trans.s3 }}</li>
                <li>{{ trans.s4 }}</li>
            </ul>
            <hr class="d-sm-none">
        </div>
        <div class="col-md-12  mb-4" v-if="products_loaded && navigation_zero_result">
            <h2>{{ trans.t1 }}</h2>
            <p> {{ trans.t2 }}</p>
            <hr class="d-sm-none">
        </div>

    </section>
</template>

<script>

Vue.directive('tooltip', function(el, binding){
    $(el).tooltip({
        title: binding.value,
        placement: binding.arg,
        trigger: 'hover'
    })
})

export default {
    name: 'ProductsList',
    props: {
        ids: String,
        group: String,
        cat: String,
        subcat: String,
        author: String,
        publisher: String,
        brand: String,
    },
    //
    data() {
        return {
            products: {},
            autor: '',
            brend: '',
            nakladnik: '',
            start: '',
            end: '',
            sorting: '',
            search_query: '',
            page: 1,
            origin: location.origin + '/',
            hr_total: 'rezultata',
            products_loaded: false,
            search_zero_result: false,
            navigation_zero_result: false,
            trans: window.trans,
        }
    },
    //
    watch: {
        sorting(value) {
            this.setQueryParam('sort', value);
        },
        $route(params) {
            this.checkQuery(params);
        }
    },
    //
    mounted() {
        this.checkQuery(this.$route);
    },

    methods: {
        /**
         *
         */
        getProducts() {
            this.search_zero_result = false;
            this.navigation_zero_result = false;
            this.products_loaded = false;
            let params = this.setParams();

            console.log('tu sam...')
            console.log(params)

            axios.post('filter/getProducts', { params }).then(response => {
                this.products_loaded = true;
                this.products = response.data;
                this.checkHrTotal();
                this.checkSpecials();

                if (this.$store.state.storage.getCart()) {
                    this.checkAvailables();
                }

                console.log('Response::data.data')
                console.log(response.data.data)

                if (params.pojam != '' && !this.products.total) {
                    this.search_zero_result = true;
                }

                if (params.pojam == '' && !this.products.total) {
                    this.navigation_zero_result = true;
                }
            });
        },

        /**
         *
         * @param page
         */
        getProductsPage(page = 1) {
            this.products_loaded = false;
            this.page = page;
            this.setQueryParam('page', page);

            let params = this.setParams();
            window.scrollTo({top: 0, behavior: 'smooth'});

            axios.post('filter/getProducts?page=' + page, { params }).then(response => {
                this.products_loaded = true;
                this.products = response.data;
                this.checkHrTotal();
                this.checkSpecials();
                this.checkAvailables();
            });
        },

        /**
         *
         * @param type
         * @param value
         */
        setQueryParam(type, value) {
            this.closeFilter();
            this.$router.push({query: this.resolveQuery()}).catch(()=>{});

            if (value == '' || value == 1) {
                this.$router.push({query: this.resolveQuery()}).catch(()=>{});
            }
        },

        /**
         *
         * @return {{}}
         */
        resolveQuery() {
            let params = {
                start: this.start,
                end: this.end,
                autor: this.autor,
                brand: this.brend,
                nakladnik: this.nakladnik,
                sort: this.sorting,
                pojam: this.search_query,
                page: this.page
            };

            return Object.entries(params).reduce((acc, [key, val]) => {
                if (!val) return acc
                return { ...acc, [key]: val }
            }, {});
        },

        /**
         *
         * @param params
         */
        checkQuery(params) {
            this.start = params.query.start ? params.query.start : '';
            this.end = params.query.end ? params.query.end : '';
            this.autor = params.query.autor ? params.query.autor : '';
            this.brend = params.query.brand ? params.query.brand : '';
            this.nakladnik = params.query.nakladnik ? params.query.nakladnik : '';
            this.page = params.query.page ? params.query.page : '';
            this.sorting = params.query.sort ? params.query.sort : '';
            this.search_query = params.query.pojam ? params.query.pojam : '';

            if (this.page != '') {
                this.getProductsPage(this.page);
            } else {
                this.getProducts();
            }
        },

        /**
         *
         * @return {{cat: String, start: string, pojam: string, subcat: String, end: string, sort: string, nakladnik: string, autor: string, group: String}}
         */
        setParams() {
            let params = {
                ids: this.ids,
                group: this.group,
                cat: this.cat,
                subcat: this.subcat,
                autor: this.autor,
                brand: this.brend ? this.brend : this.brand,
                nakladnik: this.nakladnik,
                start: this.start,
                end: this.end,
                sort: this.sorting,
                pojam: this.search_query
            };

            if (this.author != '') {
                params.autor = this.author;
            }

            if (this.brend != '') {
                params.brand = this.brend;
            }
            if (this.publisher != '') {
                params.nakladnik = this.publisher;
            }

            return params;
        },

        /**
         *
         */
        checkSpecials() {
            let now = new Date();

            for (let i = 0; i < this.products.data.length; i++) {
                if (Number(this.products.data[i].main_price) <= Number(this.products.data[i].main_special)) {
                    this.products.data[i].special = false;
                }
            }
        },

        /**
         *
         */
        checkAvailables() {
            let cart = this.$store.state.storage.getCart();

            for (let i = 0; i < this.products.data.length; i++) {
                this.products.data[i].disabled = false;

                for (const key in cart.items) {
                    if (this.products.data[i].id == cart.items[key].id) {
                        if (this.products.data[i].quantity <= cart.items[key].quantity) {
                            this.products.data[i].disabled = true;
                        }
                    }
                }
            }
        },

        /**
         *
         */
        checkHrTotal() {
            this.hr_total = 'rezultata';

            if ((this.products.total).toString().slice(-1) == '1') {
                this.hr_total = 'rezultat';
            }
        },

        /**
         *
         * @param id
         */
        add(id, product_quantity) {
            let cart = this.$store.state.storage.getCart();

            for (const key in cart.items) {
                if (id == cart.items[key].id) {
                    if (product_quantity <= cart.items[key].quantity) {
                        return window.ToastWarning.fire('Nažalost nema dovoljnih količina artikla..!');
                    }
                }
            }

            this.$store.dispatch('addToCart', {
                id: id,
                quantity: 1
            })
        },

        /**
         *
         */
        closeFilter() {
            $('#shop-sidebar').removeClass('collapse show');
        },

        onerow() {
            $('#product-grid').removeClass('row-cols-2');
            $('#product-grid').addClass('row-cols-1');
        },

        tworow() {
            $('#product-grid').removeClass('row-cols-1');
            $('#product-grid').addClass('row-cols-2');
        }
    }
};
</script>

<style>
</style>
