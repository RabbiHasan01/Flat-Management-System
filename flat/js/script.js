(function(window, $) {
    $( document ).ready(function() {
        //$('.collapse').collapse();
    });

    $('#users_table').DataTable({
        "order": [[ 1, "desc" ]]
    });

    $('#flat_table_manager').DataTable({
        "order": [[ 1, "desc" ]]
    });

    if ( $('#flat_table').length > 0 ) {
        var flatTable = $('#flat_table').dataTable({
            "bJQueryUI": true
        }).yadcf([{
            column_number: 1,
            column_data_type: "html",
            html_data_type: "text",
            filter_default_label: "Type"
        }, {
            column_number: 2,
            filter_type: "text",
            text_data_delimiter: ",",
            filter_default_label: 'Address'
        }, {
            column_number: 3,
            column_data_type: "html",
            html_data_type: "text",
            filter_default_label: "Floor"
        }, {
            column_number: 4,
            filter_type: "range_number_slider",
            ignore_char: ","
        }, {
            column_number: 5,
            filter_type: "range_number_slider",
            ignore_char: ","
        }]);
    }

    $(document).on("change", 'input[type="checkbox"][name="update_psw"]', function(){
        var $this = $(this), $psw = $this.closest(".row").find('input[type="password"][name="password"]').closest(".col-12"), $repsw = $this.closest(".row").find('input[type="password"][name="repassword"]').closest(".col-12");

        if($this.prop('checked')) {
            $psw.show();
            $repsw.show();
        } else {
            $psw.hide();
            $repsw.hide();
        }
    });

    var $this = $('input[type="checkbox"][name="update_psw"]'), $psw = $this.closest(".row").find('input[type="password"][name="password"]').closest(".col-12"), $repsw = $this.closest(".row").find('input[type="password"][name="repassword"]').closest(".col-12");
    if($this.prop('checked')) {
        $psw.show();
        $repsw.show();
    }

    if(typeof uimage !== 'undefined') {
        $('.room-photos').imageUploader({
            preloaded: uimage,
            extensions: ['.jpg', '.jpeg', '.png', '.gif'],
            imagesInputName: 'photos',
            maxSize: 5 * 1024 * 1024,
            maxFiles: 5
        });
    } else {
        $('.room-photos').imageUploader({
            extensions: ['.jpg', '.jpeg', '.png', '.gif'],
            imagesInputName: 'photos',
            maxSize: 5 * 1024 * 1024,
            maxFiles: 5
        });
    }


    $( ".popup-gallery" ).each(function(index, element) {
        $(element).magnificPopup({
            delegate: 'a',
            type: 'image',
            tLoading: 'Loading image #%curr%...',
            mainClass: 'mfp-img-mobile',
            gallery: {
            enabled: true,
            navigateByImgClick: true,
            preload: [0,1]
            },
            image: {
            tError: '<a href="%url%">The image #%curr%</a> could not be loaded.',
            titleSrc: function(item) {
                return item.el.attr('title') + '<small>by Marsel Van Oosten</small>';
            }
            }
        });
    });
    

   

})(window, jQuery);