$(document).ready(function () {

    let isReadyToGetResult = false;
    let t0;
    let t1;

    $('[name=search-query-meili]').each(function () {

        $(this).keyup(function () {

            t0 = performance.now();

            // console.log(isReadyToGetResult);
            // if(!isReadyToGetResult) {
            //     isReadyToGetResult = true;
            //     $( this ).after( '<table id="table-result-search-meili"></table>' );
            //     $('#table-result-search-meili').append(
            //         '<thead id="thead-products"><tr><th>Our Products</th></tr></thead>' +
            //         '<tbody id="tbody-products"></tbody>' +
            //         '<thead id="thead-categories"><tr><th>Our Categories</th></tr></thead>' +
            //         '<tbody id="tbody-categories"></tbody>');
            // }

            if (this.value.length >= 1) {
                $.ajax({
                    type: "GET",
                    url: 'http://127.0.0.1:7700/indexes/' + UidIndexSearchProducts + '/search',
                    contentType: "application/json",
                    dataType: 'json',
                    data: {q: this.value,
                        attributesToHighlight: 'name, description',
                        attributesToCrop: 'description',
                        cropLength: 200,
                        limit: 2},
                    success: function(result){
                        $('tbody.products').empty();
                        var data = '';
                        result.hits.forEach( element => {
                            data += '<tr class="row"><td class="col-lg-10 col-xs-10">' +
                                '<h5 class="header">' +
                                '<img src="http://ps1763/img/p/2/9/7/2/6/29726-small_default.jpg" ' +
                                    'height="90" width="90" class="rounded image">' +
                                '<div class="content">' +
                                '<a href="http://ps1763/index.php?id_product=1428&amp;rewrite=imc-21-m-sc&amp;controller=product">' +
                                element._formatted.name +
                                '</a><p>' + element._formatted.description + '</p></div></h5></td>' +
                                '<td class="col-lg-2 col-xs-2">' +
                                '<div class="center aligned">' +
                                '<span class="price">' +  element.price + '</span>' +
                                '</div></td></tr>';

                        });

                        if(result.hits.length) {
                            if(!$('#thead-products').length) {
                                $('#table-result-search-meili').empty().append(
                                    '<thead id="thead-products"><tr><th>Our Products</th></tr></thead>' +
                                    '<tbody id="tbody-products"></tbody>' +
                                    '<thead id="thead-categories"><tr><th>Our Categories</th></tr></thead>' +
                                    '<tbody id="tbody-categories"></tbody>');
                            }
                            $('#tbody-products').html(data);
                        } else {
                            $('#thead-products').remove();
                            $('#tbody-products').remove();
                        }
                    }
                });

                $.ajax({
                    type: "GET",
                    url: 'http://127.0.0.1:7700/indexes/' + UidIndexSearchCategories + '/search',
                    contentType: "application/json",
                    dataType: 'json',
                    data: {q: this.value,
                        attributesToHighlight: 'name, description',
                        attributesToCrop: 'description',
                        cropLength: 200,
                        limit: 2},
                    success: function(result){
                        $('tbody.categories').empty();
                        var data = '';
                        result.hits.forEach( element => {

                            var link_image = '';
                            if(element.link_image.lengt) {
                                var link_image = '<img src="' + element.link_image + '" height="90" width="90" class="rounded">';
                            }

                            data += '<tr class="row"><td class="col-lg-12 col-xs-12">' +
                                '<h5 class="header">';

                            if(element.link_image.lengt) {
                                data += '<img src="' + element.link_image + '" height="90" width="90" class="rounded">';
                            }
                            data += '<div class="content">' +
                                '<a href="' + element.link + '">' +
                                element._formatted.name +
                                '</a>' +
                                '<p>' + element._formatted.description + '</p>' +
                                '</div></h5></td></tr>';
                        });

                        if(result.hits.length) {
                            if(!$('#thead-categories').length) {
                                $('#table-result-search-meili').append(
                                    '<thead id="thead-categories"><tr>' +
                                    '<th>Our Categories</th></tr></thead>' +
                                    '<tbody id="tbody-categories"></tbody>');
                            }
                            $('#tbody-categories').html(data);
                        } else {
                            $('#thead-categories').remove();
                            $('#tbody-categories').remove();
                        }
                    }
                })

                var specifiedElement = document.getElementById('search-box');
                document.addEventListener('click', function(event) {
                    var isClickInside = specifiedElement.contains(event.target);

                    if (!isClickInside) {
                        $('#table-result-search-meili').empty();
                        // isReadyToGetResult = false;
                    }
                });

            } else {
                $('#table-result-search-meili').empty();
                // isReadyToGetResult = false;
                // console.log(isReadyToGetResult);
            }

            t1 = performance.now();
            console.log("ao mieli " + (t1 - t0) + " milliseconds.");
        });
    });

});


// http://ps1763/admin99/index.php?controller=AdminSearch&action=searchCron&ajax=1&full=1&token=XGbm7LJD&id_shop=1