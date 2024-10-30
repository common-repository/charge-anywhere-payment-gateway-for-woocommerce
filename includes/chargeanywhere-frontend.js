function showSelectedOption(val){
    jQuery(".chargeanywhere-credit-card, .chargeanywhere-ach-card").css("display","none");
    if(val == "ach"){
        jQuery(".chargeanywhere-ach-card").css("display","block");
    } else {
        jQuery(".chargeanywhere-credit-card").css("display","block");
    }
    jQuery('body').trigger("update_checkout");
}

function validateAccNumber(thisObj){
    thisObj.val(function (index, value) {
        return value.replace(/\W/gi, '').replace(/(.{3})/g, '$1 ');
    });
}

jQuery(function($) {
    $('#chargeanywhere-account-number').on('keypress change', function () {
        validateAccNumber($(this));
    });

    $('#chargeanywhere-routing-number').on('keypress change', function () {
        validateAccNumber($(this));
    });

    $( document.body )
		.on( 'updated_checkout wc-credit-card-form-init', function() {
            $('#chargeanywhere-account-number').on('keypress change', function () {
                validateAccNumber($(this));
            });
        
            $('#chargeanywhere-routing-number').on('keypress change', function () {
                validateAccNumber($(this));
            });
        });
});

jQuery( function( $ ) {
	$( '#chargeanywhere-card-number' ).payment( 'formatCardNumber' );
	$( '#chargeanywhere-card-expiry' ).payment( 'formatCardExpiry' );
	$( '#chargeanywhere-card-cvc' ).payment( 'formatCardCVC' );

	$( document.body )
		.on( 'updated_checkout wc-credit-card-form-init', function() {
			$( '#chargeanywhere-card-number' ).payment( 'formatCardNumber' );
			$( '#chargeanywhere-card-expiry' ).payment( 'formatCardExpiry' );
			$( '#chargeanywhere-card-cvc' ).payment( 'formatCardCVC' );
		})
		.trigger( 'wc-credit-card-form-init' );
});

