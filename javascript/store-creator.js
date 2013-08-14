jQuery(document).ready(function($){
    //detect address change and determine lat and long values from entry.
    $("#Form_ItemEditForm_Address").on("change",function(e){
        if($.trim(e.target.value).length > 0 ){
            $.ajax({
                type: "POST",
                url: "AddressLookup_Controller/findLatLongForAddress",
                data: { address: e.target.value }
            }).done(function( msg ) {
                var data = JSON.parse(msg);
                $("#Form_ItemEditForm_Latitude").val(data.Latitude);
                $("#Form_ItemEditForm_Longitude").val(data.Longitude);
                //e.preventDefault();
            });
        }
    });

});
