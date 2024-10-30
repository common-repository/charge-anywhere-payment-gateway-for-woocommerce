var caw_incChargeSet = 0;
var caw_incChargeAch = 0;
jQuery(document).ready(function() {
    jQuery('#woocommerce_chargeanywhere_accept_credit').change(function() {
        if (jQuery(this).is(':checked')) {
            displayCreditBoxes(false);
        } else {
            displayCreditBoxes(true);
        }
    });
    
    jQuery('#woocommerce_chargeanywhere_credit_service_fee').on("keydown", function(){
       alert("tetete");
    });

    // jQuery('.do-api-refund').on("click",function(){
    //     alert("etette");
    //     return false;
    // });
  

    jQuery('#woocommerce_chargeanywhere_accept_ach').change(function() {
        if (jQuery(this).is(':checked')) {
            displayACHBoxes(false);
        } else {
            displayACHBoxes(true);
        }
    });

    jQuery('#woocommerce_chargeanywhere_apply_credit_service').change(function() {
        if (jQuery(this).is(':checked')) {
            disableCreditBoxes(false);
        } else {
            disableCreditBoxes(true);
        }
    });

    jQuery('#woocommerce_chargeanywhere_apply_credit_convenience_service').change(function() {
        if (jQuery(this).is(':checked')) {
            disableCreditBoxesConv(false);
        } else {
            disableCreditBoxesConv(true);
        }
    });

    jQuery('#woocommerce_chargeanywhere_apply_ach_service').change(function() {
        console.log(jQuery(this).is(':checked'));
        if (jQuery(this).is(':checked')) {
            disableACHBoxes(false);
        } else {
            disableACHBoxes(true);
        }
    });

    jQuery('#woocommerce_chargeanywhere_apply_ach_convenience_service').change(function() {
        if (jQuery(this).is(':checked')) {
            disableACHBoxesConv(false);
        } else {
            disableACHBoxesConv(true);
        }
    });

    loadData();
    jQuery("#woocommerce_chargeanywhere_credit_service_fee_type").css("width", "100%");
    jQuery("#woocommerce_chargeanywhere_ach_service_fee_type").css("width", "100%");
})

function loadData() {

    if (jQuery("#woocommerce_chargeanywhere_accept_credit").is(':checked'))
        displayCreditBoxes(false);
        else
        displayCreditBoxes(true);

    if (jQuery("#woocommerce_chargeanywhere_apply_credit_service").is(':checked'))
        disableCreditBoxes(false);
    else
        disableCreditBoxes(true);
    
    
    if (jQuery("#woocommerce_chargeanywhere_accept_ach").is(':checked'))
        displayACHBoxes(false);
    else
        displayACHBoxes(true);

    if (jQuery("#woocommerce_chargeanywhere_apply_ach_service").is(':checked'))
        disableACHBoxes(false);
    else
        disableACHBoxes(true);

    if(jQuery("#woocommerce_chargeanywhere_apply_credit_convenience_service").is(':checked'))
        disableCreditBoxesConv(false);
    else
        disableCreditBoxesConv(true);

    if(jQuery("#woocommerce_chargeanywhere_apply_ach_convenience_service").is(':checked'))
        disableACHBoxesConv(false);
    else
        disableACHBoxesConv(true);
}

function disableCreditBoxes(flag) {
    jQuery("#woocommerce_chargeanywhere_credit_service_fee").prop("disabled", flag);
    jQuery("#woocommerce_chargeanywhere_credit_service_fee_value").prop("disabled", flag);
    jQuery("#woocommerce_chargeanywhere_apply_credit_tax_amount_service_fee").prop("disabled", flag);
    jQuery("#woocommerce_chargeanywhere_credit_refund_service_fee").prop("disabled", flag);
    
}

function disableCreditBoxesConv(flag) {
    jQuery("#woocommerce_chargeanywhere_credit_convenience_service_fee").prop("disabled", flag);
    jQuery("#woocommerce_chargeanywhere_credit_convenience_service_fee_value").prop("disabled", flag);
    jQuery("#woocommerce_chargeanywhere_credit_refund_convenience_fee").prop("disabled", flag);
    
}

function displayCreditBoxes(flag) {
    jQuery("#woocommerce_chargeanywhere_apply_credit_service").closest('tr').addClass('credit-outer-table');
    jQuery("#woocommerce_chargeanywhere_apply_credit_convenience_service").closest('tr').addClass('credit-outer-table2');

    if (!flag) {
        jQuery("#woocommerce_chargeanywhere_apply_credit_service").closest('tr').css('display', 'table-row');
        jQuery("#woocommerce_chargeanywhere_apply_credit_service").closest('td').css('padding-left', '40px');
        jQuery("#woocommerce_chargeanywhere_credit_service_fee").closest('tr').addClass('credit-row').css('display', 'table-row');
        jQuery("#woocommerce_chargeanywhere_credit_service_fee_value").closest('tr').addClass('credit-row').css('display', 'table-row');

        jQuery("#woocommerce_chargeanywhere_apply_credit_tax_amount_service_fee").closest('tr').addClass('credit-row').css('display', 'table-row');
        jQuery("#woocommerce_chargeanywhere_credit_refund_service_fee").closest('tr').addClass('credit-row').css('display', 'table-row');

        jQuery("#woocommerce_chargeanywhere_apply_credit_convenience_service").closest('tr').css('display', 'table-row');
        jQuery("#woocommerce_chargeanywhere_apply_credit_convenience_service").closest('td').css('padding-left', '40px');

        jQuery("#woocommerce_chargeanywhere_credit_convenience_service_fee").closest('tr').addClass('credit-row2').css('display', 'table-row');
        jQuery("#woocommerce_chargeanywhere_credit_convenience_service_fee_value").closest('tr').addClass('credit-row2').css('display', 'table-row');

        jQuery("#woocommerce_chargeanywhere_credit_refund_convenience_fee").closest('tr').addClass('credit-row2').css('display', 'table-row');
    } else {
        jQuery("#woocommerce_chargeanywhere_apply_credit_service").closest('tr').css('display', 'none');
        jQuery("#woocommerce_chargeanywhere_credit_service_fee").closest('tr').addClass('credit-row').css('display', 'none');
        jQuery("#woocommerce_chargeanywhere_credit_service_fee_value").closest('tr').addClass('credit-row').css('display', 'none');

        jQuery("#woocommerce_chargeanywhere_apply_credit_tax_amount_service_fee").closest('tr').addClass('credit-row').css('display', 'none');
        jQuery("#woocommerce_chargeanywhere_credit_refund_service_fee").closest('tr').addClass('credit-row').css('display', 'none');

        jQuery("#woocommerce_chargeanywhere_apply_credit_convenience_service").closest('tr').css('display', 'none');
        jQuery("#woocommerce_chargeanywhere_credit_convenience_service_fee").closest('tr').addClass('credit-row2').css('display', 'none');
        jQuery("#woocommerce_chargeanywhere_credit_convenience_service_fee_value").closest('tr').addClass('credit-row2').css('display', 'none');
        
        jQuery("#woocommerce_chargeanywhere_credit_refund_convenience_fee").closest('tr').addClass('credit-row2').css('display', 'none');

        jQuery("#woocommerce_chargeanywhere_credit_service_fee").val("");
        jQuery("#woocommerce_chargeanywhere_credit_service_fee_value").val("");
        jQuery("#woocommerce_chargeanywhere_credit_refund_service_fee").prop("checked", false);
        jQuery("#woocommerce_chargeanywhere_apply_credit_service").prop("checked", false);
        jQuery("#woocommerce_chargeanywhere_apply_credit_tax_amount_service_fee").prop("checked", false);

        jQuery("#woocommerce_chargeanywhere_credit_convenience_service_fee").val("");
        jQuery("#woocommerce_chargeanywhere_credit_convenience_service_fee_value").val("");
        jQuery("#woocommerce_chargeanywhere_apply_credit_convenience_service").prop("checked", false);
        jQuery("#woocommerce_chargeanywhere_credit_refund_convenience_fee").prop("checked", false);
       // jQuery("#woocommerce_chargeanywhere_credit_refund_service_fee").prop("checked", false);
        
    }
    if (caw_incChargeAch == 0) {
        jQuery(".credit-outer-table").closest('tr').after("<tr class='credit-group'><th></th><td><table class='credit-table'></table></td></tr>");
        jQuery(".credit-row").each(function() {
            var html = "<tr class='credit-row'>";
            html += jQuery(this).html();
            html += "</tr>";
            jQuery(".credit-group .credit-table").append(html).css({"margin-left":"50px"});
            jQuery(this).remove();
        });

        jQuery(".credit-outer-table2").closest('tr').after("<tr class='credit-group2'><th></th><td><table class='credit-table2'></table></td></tr>");
        jQuery(".credit-row2").each(function() {
            var html = "<tr class='credit-row'>";
            html += jQuery(this).html();
            html += "</tr>";
            jQuery(".credit-group2 .credit-table2").append(html).css({"margin-left":"50px"});
            jQuery(this).remove();
        });
    }
    if (!flag) {
        jQuery(".credit-group").css("display", "table-row");
        jQuery(".credit-group2").css("display", "table-row");
        disableCreditBoxes(true)
    } else {
        jQuery(".credit-group").css("display", "none");
        jQuery(".credit-group2").css("display", "none");
        disableCreditBoxes(true)
    }

    if (jQuery("#woocommerce_chargeanywhere_apply_credit_service").is(':checked'))
        disableCreditBoxes(false)
    else
        disableCreditBoxes(true)
    caw_incChargeAch++;
}

function disableACHBoxes(flag) {
    jQuery("#woocommerce_chargeanywhere_ach_service_fee").prop("disabled", flag);
    jQuery("#woocommerce_chargeanywhere_ach_service_fee_value").prop("disabled", flag);

    jQuery("#woocommerce_chargeanywhere_apply_ach_tax_amount_service_fee").prop("disabled", flag);
    jQuery("#woocommerce_chargeanywhere_ach_refund_service_fee").prop("disabled", flag);

    
}

function disableACHBoxesConv(flag) {
    jQuery("#woocommerce_chargeanywhere_ach_convenience_service_fee").prop("disabled", flag);
    jQuery("#woocommerce_chargeanywhere_ach_convenience_service_fee_value").prop("disabled", flag);
    jQuery("#woocommerce_chargeanywhere_ach_refund_convenience_fee").prop("disabled", flag);
}

function displayACHBoxes(flag) {
    jQuery("#woocommerce_chargeanywhere_apply_ach_service").closest('tr').addClass('ach-outer-table');
    jQuery("#woocommerce_chargeanywhere_apply_ach_convenience_service").closest('tr').addClass('ach-outer-table2');
    if (!flag) {
        jQuery("#woocommerce_chargeanywhere_apply_ach_service").closest('tr').css('display', 'table-row');
        jQuery("#woocommerce_chargeanywhere_apply_ach_service").closest('td').css('padding-left', '40px');
        jQuery("#woocommerce_chargeanywhere_ach_service_fee").closest('tr').addClass('ach-row').css('display', 'table-row');
        jQuery("#woocommerce_chargeanywhere_ach_service_fee_value").closest('tr').addClass('ach-row').css('display', 'table-row');

        jQuery("#woocommerce_chargeanywhere_apply_ach_tax_amount_service_fee").closest('tr').addClass('ach-row').css('display', 'table-row');
        jQuery("#woocommerce_chargeanywhere_ach_refund_service_fee").closest('tr').addClass('ach-row').css('display', 'table-row');

        jQuery("#woocommerce_chargeanywhere_apply_ach_convenience_service").closest('tr').css('display', 'table-row');
        jQuery("#woocommerce_chargeanywhere_apply_ach_convenience_service").closest('td').css('padding-left', '40px');
        jQuery("#woocommerce_chargeanywhere_ach_convenience_service_fee").closest('tr').addClass('ach-row2').css('display', 'table-row');
        jQuery("#woocommerce_chargeanywhere_ach_convenience_service_fee_value").closest('tr').addClass('ach-row2').css('display', 'table-row');

        jQuery("#woocommerce_chargeanywhere_ach_refund_convenience_fee").closest('tr').addClass('ach-row2').css('display', 'table-row');
    } else {
        jQuery("#woocommerce_chargeanywhere_apply_ach_service").closest('tr').css('display', 'none');
        jQuery("#woocommerce_chargeanywhere_ach_service_fee").closest('tr').addClass('ach-row').css('display', 'none');
        jQuery("#woocommerce_chargeanywhere_ach_service_fee_value").closest('tr').addClass('ach-row').css('display', 'none');

        jQuery("#woocommerce_chargeanywhere_apply_ach_tax_amount_service_fee").closest('tr').addClass('ach-row').css('display', 'none');
        jQuery("#woocommerce_chargeanywhere_ach_refund_service_fee").closest('tr').addClass('ach-row').css('display', 'none');

        jQuery("#woocommerce_chargeanywhere_apply_ach_convenience_service").closest('tr').css('display', 'none');
        jQuery("#woocommerce_chargeanywhere_ach_convenience_service_fee").closest('tr').addClass('ach-row2').css('display', 'none');
        jQuery("#woocommerce_chargeanywhere_ach_convenience_service_fee_value").closest('tr').addClass('ach-row2').css('display', 'none');

        jQuery("#woocommerce_chargeanywhere_ach_refund_convenience_fee").closest('tr').addClass('ach-row2').css('display', 'none');


        jQuery("#woocommerce_chargeanywhere_ach_service_fee").val("");
        jQuery("#woocommerce_chargeanywhere_ach_service_fee_value").val("");
        jQuery("#woocommerce_chargeanywhere_ach_refund_service_fee").prop("checked", false);
        jQuery("#woocommerce_chargeanywhere_apply_ach_service").prop("checked", false);
        jQuery("#woocommerce_chargeanywhere_ach_convenience_service_fee").val("");
        jQuery("#woocommerce_chargeanywhere_apply_ach_tax_amount_service_fee").prop("checked", false);

        jQuery("#woocommerce_chargeanywhere_ach_convenience_service_fee_value").val("");
        jQuery("#woocommerce_chargeanywhere_apply_ach_convenience_service").prop("checked", false);
        jQuery("#woocommerce_chargeanywhere_ach_refund_convenience_fee").prop("checked", false);
    }
    if (caw_incChargeSet == 0) {
        jQuery(".ach-outer-table").closest('tr').after("<tr class='ach-group'><th></th><td><table class='ach-table'></table></td></tr>");

        jQuery(".ach-row").each(function() {
            var html = "<tr class='ach-row'>";
            html += jQuery(this).html();
            html += "</tr>";
            jQuery(".ach-group .ach-table").append(html).css({"margin-left":"50px"});
            jQuery(this).remove();
        })

        jQuery(".ach-outer-table2").closest('tr').after("<tr class='ach-group2'><th></th><td><table class='ach-table2'></table></td></tr>");

        jQuery(".ach-row2").each(function() {
            var html = "<tr class='ach-row'>";
            html += jQuery(this).html();
            html += "</tr>";
            jQuery(".ach-group2 .ach-table2").append(html).css({"margin-left":"50px"});
            jQuery(this).remove();
        })
    }
    if (!flag) {
        jQuery(".ach-group").css("display", "table-row");
        jQuery(".ach-group2").css("display", "table-row");
        disableACHBoxes(false)
    } else {
        jQuery(".ach-group").css("display", "none");
        jQuery(".ach-group2").css("display", "none");
        disableACHBoxes(true)
    }
    if (jQuery("#woocommerce_chargeanywhere_apply_ach_service").is(':checked'))
        disableACHBoxes(false)
    else
        disableACHBoxes(true)

    caw_incChargeSet++;
}