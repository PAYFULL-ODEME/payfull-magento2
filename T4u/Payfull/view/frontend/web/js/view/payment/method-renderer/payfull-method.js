/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'mage/url',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Payment/js/view/payment/cc-form',     
        'T4u_Payfull/js/model/validator',
        'mage/validation'           
    ],
    function ($, ko, quote, priceUtils, url, Component) {
        'use strict';
        
        var billingAddress = quote.billingAddress();
        var totals = quote.totals();
        var grandtotal_first = 0;
        var grandtotal = 0;
        var use3D = 0;
        var result_cc_month = 0;
        var result_cc_year = 0;
        var result_cc_cvc = 0;
        var currency_per_month = 0;
        var currency_total = 0;
        var url_cc = url.build('payfull/payment/index');
        var url_submit = url.build('payfull/payment/cardinfo');
        var url_bkm = url.build('payfull/payment/salebkm');
        var url_redirect = url.build('payfull/payment/redirectaction');
        var url_ajaxLoaderImage = url.build('pub/static/frontend/Magento/luma/en_US/T4u_Payfull/images/ajax/ajax_loader_checkout.gif');
        var bank_id = '';
        var gateway = '';
        var campaign_id = new Array();
        var per_month_value = new Array();
        var total_value = new Array();
        var extra_installments = new Array();
        var installment = 1;
        var baseinstallment=1;
        var flag = 0;
        var flag_success = 1;
        var cc_bin_length = '';
        var cc_bin = '';
        var cc_first = '';
        var campaign_id_set = '';
        if (totals) {
            grandtotal = totals.grand_total;
            var month_inst = totals.grand_total;
            var total_inst = totals.grand_total;
        }
        grandtotal_first = priceUtils.formatPrice(grandtotal, quote.getPriceFormat());

        return Component.extend({
            defaults: {
                template: 'T4u_Payfull/payment/form',
                transactionResult: ''
            },
            initialize: function () {
                this._super();
                return this;
            },
            
            initObservable: function () {

                this._super()
                    .observe([
                        'transactionResult'
                    ]);
                return this;
            },

            getCode: function() {
                return 'payfull';
            },

            getData: function() {
                return {
                    'method': this.item.method
                };
            },
            getBKM: function() {
                return window.checkoutConfig.payment.payfull.bkm_express;
            },

            isEnableBKM: function() {
              if(this.getBKM()=='1') {
                return true;
              }
              return false;
            },
            get3DSecure: function() {
                return window.checkoutConfig.payment.payfull.threed_secure;
            },
            isEnable3DSecure: function() {
              if(this.get3DSecure()=='1') {
                return true;
              }
              return false;
            },
            getMinOrderTotal: function() {
                return window.checkoutConfig.payment.payfull.minimum_order;
            },
            getGrandTotal: function() {
                return grandtotal;
            },

            is3DChecked: function(){
                if(document.getElementById("use3d").checked == true){
                    document.getElementById("use3d").value = 1;
                    return true;
                }else{
                    document.getElementById("use3d").value = 0;
                    return true;
                }
            },

            validate: function() {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },
            isInstallmentActive: function(){
                 return window.checkoutConfig.payment.payfull.installment;  
            },
            ajaxCall: function(){                        
                var min_order = this.getMinOrderTotal();
                var cc = $('#input-cc-number').val();
                var prevcno = $('#nolen').val();
                var callflag = $('#callflag').val();
                prevcno = prevcno.substring(0, 5);
                var cc_bin = cc.substring(0, 6);
                var currcardno = cc.substring(0, 5);
                var cc_bin_length = cc.length;
                var prv_length = prevcno.length;
                if(cc_bin_length == 6 || (callflag==1 && cc_bin_length ==16) || (prevcno == currcardno && cc_bin_length==5) || (prv_length>0 && cc_bin_length==0)){  
                    if(cc_bin_length == 6){
                        $('#nolen').val(cc);
                        $('#callflag').val(1);
                    }   
                    var data= {cc:cc_bin}
                    $.ajax({
                        dataType: 'json',
                        url: url_cc,
                        data: data,
                        type: 'post',
                        success: function(result)
                        {   
                            /* when user re-enter details then no-error msg default */
                            $(".error-message").html('');
                            $(".error-message-bkm").html('');
                            $('#bankImage').attr('src','');
                            var html = '' ;
                            var wrapper = $(".installment_row"); /*Fields wrapper*/
                            if(result == null || result.installments == null){
                                var grandtotal_null = priceUtils.formatPrice(grandtotal, quote.getPriceFormat());
                                /*some this wrong with api*/
                                if(result != null){ 
                                    if(result.bankImageUrl != null && result.bankImageUrl != undefined){
                                        $('#bankImage').attr('src',result.bankImageUrl);
                                    }else{
                                        $('#bankImage').attr('src','');
                                    } 
                                }
                                html += '<div class="install_body_label installment_radio"><input rel="1" class="installment_radio" id="installment_radio" checked="" name="installments" value="1" type="radio"></div>';
                                html += '<div class="install_body_label installment_lable_code"><span data-bind="text: installment">One Shot</span></div>';
                                html +='<div class="install_body_label">';
                                html +='<span data-bind="text: amount">'+grandtotal_null+'</span>';
                                html +='</div>';
                                html +='<div class="install_body_label final_commi_price">';
                                html +='<span data-bind="text: total">'+grandtotal_null+'</span>';
                                html +='</div></div>';   
                                $(wrapper).html(html);
                                if(result.has3d != null){
                                    if(result.has3d == 1) {
                                        $('.payfull-checkbox').show();                                        
                                    }else{
                                        $('.payfull-checkbox').hide(); 
                                    }                                    
                                }/*end if*/
                            }else{
                                bank_id = result.bank;
                                gateway = result.gateway;                                        
                            
                                if(grandtotal >= min_order){
                                    installment_count = 0;
                                    if(result.bankImageUrl != null && result.bankImageUrl != undefined){
                                        $('#bankImage').attr('src',result.bankImageUrl);
                                    }else{
                                        $('#bankImage').attr('src','');
                                    }
                                    if(result.installments != null ){    
                                        campaign_id_set = '';                               
                                        $('.extra_installment').html('');
                                        var installments_length = result.installments.length;
                                       
                                        for (var j = 0; j < installments_length; j++) { /*for start*/
                                            var commission = parseInt(result.installments[j].commission);                          
                                            var installment_count = j + 1;

                                            var total = grandtotal + ((grandtotal * commission)/100);
                                            var per_month = (total/installment_count);
                                            per_month_value.push(per_month);
                                            total_value.push(total);
                                            var currency_total = priceUtils.formatPrice(total, quote.getPriceFormat());
                                            var currency_per_month = priceUtils.formatPrice(per_month, quote.getPriceFormat());
                                            if('0' in result && result['0'].base_installments == installment_count){
                                                var campaigns_length = result.campaigns.length;
                                                for (var i = 0; i < campaigns_length; i++) {
                                                    campaign_id.push(result.campaigns[i].campaign_id);
                                                    extra_installments.push(result.campaigns[i].extra_installments);
                                                }
                                                html +='<div class="install_body_label installment_radio "><input rel=' + installment_count + ' class="installment_radio" id="installment_radio_joker'+ installment_count + '" name="installments" value=' + installment_count + ' type="radio"></div>' ;
                                                html += '<div class="install_body_label installment_lable_code "><div class="joker">' + installment_count +' + JOKER </div></div>';
                                            }else{
                                                if(installment_count == 1){
                                                    html +='<div class="install_body_label installment_radio installment_radio'+ installment_count + '" ><input checked="" rel=' + installment_count + ' class="installment_radio" id="installment_radio'+ installment_count + '" name="installments" value=' + installment_count + ' type="radio"></div>'; 
                                                    html += '<div class="install_body_label installment_lable_code">One Shot</div>'; 
                                                }else{
                                                    html +='<div class="install_body_label installment_radio installment_radio'+ installment_count + '" ><input rel=' + installment_count + ' class="installment_radio" id="installment_radio'+ installment_count + '" name="installments" value=' + installment_count + ' type="radio"></div>'; 
                                                    html += '<div class="install_body_label installment_lable_code">' + installment_count + '  </div>'; 
                                                }    
                                            }
                                            html += '<div class="install_body_label"><span id="per_month' + installment_count + '" rel="'+per_month+'">' + currency_per_month + '</span></div>';
                                            html += '<div class="install_body_label final_commi_price"><span id="total' + installment_count + '" rel="'+total+'">'+currency_total + '</span></div>';
                                            
                                        }/*end for*/
                                        $(wrapper).html(html);
                                    
                                        for (var j = 0; j < installments_length; j++) { /*for start*/
                                            var installment_count = j + 1;
                                            var commission = parseInt(result.installments[j].commission);                                                                          
                                            $("#installment_radio_joker"+ installment_count).click(function(){
                                                var options = '';
                                                var total_installments;
                                                installment_count = parseInt(installment_count);
                                                installment = $(this).val();  
                                                baseinstallment = parseInt(installment);
                                                if(campaigns_length != null || campaigns_length != undefined){
                                                    for (var i = 0; i < campaigns_length; i++) {
                                                        var campaign = i + 1;
                                                        total_installments = parseInt(extra_installments[i]);
                                                        total_installments += parseInt(installment);
                                                        options += '<option value="' + campaign +'">+ ' + extra_installments[i] +'</option>';
                                                    }
                                                    $('.extra_installment').html('<label>Extra Installments</label><div class="extra_installments_select"><select id ="campaign_id" name="campaign_id" class="form-control"><option value="">- Select -</option>'+options+'</select></div>');
                                                }
                                            });
                                            $("#installment_radio"+ installment_count).click(function(){  
                                                $('.extra_installment').html('');     
                                                installment = $(this).val(); 
                                                baseinstallment = parseInt(installment);
                                                campaign_id_set = '';
                                            });
                                        }/*end for*/
                                        $('body').on('change','#campaign_id',function(){                                    
                                            baseinstallment =  parseInt(installment);
                                            campaign_id_set = $(this).val(); 
                                        });
                                    }/*end if*/
                                    if(result.has3d != null){
                                        if(result.has3d == 1) {
                                            $('.payfull-checkbox').show();                                        
                                        }else{
                                            $('.payfull-checkbox').hide(); 
                                        }                                    
                                    }/*end if*/
                                }/*end if*/
                            }/*end else*/                            
                        },/*end success*/
                        error: function(){
                            /*alert("You Failed!");*/
                        }
                    });/*end ajax*/
                } /*end if*/
            }, /*ens ajaxCall*/ 
                  
            installment_list_amount: [
                    { installment: 'One Shot', amount: grandtotal_first, total: grandtotal_first }
            ],
            getTransactionResults: function() {
                return _.map(window.checkoutConfig.payment.sample_gateway.transactionResults, function(value, key) {
                    return {
                        'value': key,
                        'transaction_result': value
                    }
                });
            },
            getEmail: function () {
                if(quote.guestEmail) return quote.guestEmail;
                else return window.checkoutConfig.customerData.email;
            },

            getFirstName: function () {
                return billingAddress.firstname;
            },

            getLastName: function () {
                return billingAddress.lastname;
            },

            getPhone: function () {
                return billingAddress.telephone;
            },
    
            validateForm: function (form) {
                 return $(form).validation() && $(form).validation('isValid');
            },
            validateCardExpiryMonth: function (month, year) {
                var d = new Date();
                var current_month = d.getMonth();
                var current_year = d.getFullYear();
                if(current_year == year) {   
                    if(++current_month < month){
                        $(".error-message-month").html('');
                        return true;
                    }else{
                        $(".error-message-month").html('<div class="message message-warning warning"><span>Incorrect Expiry Month</span></div>');
                        return false;
                    }
                }else{
                    $(".error-message-month").html('');
                    return true;
                }
            },
            validateCardExpiryYear: function (year) {
                 var d = new Date();
                 var current_year = d.getFullYear();
                 if(current_year <= year){
                    $(".error-message-year").html('');
                    return true;
                 }else{
                    $(".error-message-year").html('<div class="message message-warning warning"><span>Incorrect Expiry Year</span></div>');
                   return false;
                 }
            },
            submitForm: function(){
                if (this.validateForm('#payfull-form')) {
                    $(".ajaxLoader").html('<div class="primary"><img id="ajaxLoader" width="42" height="42" src="' + url_ajaxLoaderImage + '"></div>');
                    $(".error-message").html('');
                    $(".error-message-bkm").html('');
                    var firstname = this.getFirstName();
                    var lastname = this.getLastName();
                    var phone = this.getPhone();
                    var email = this.getEmail();
                    if ($('#use3d').is(':checked')) {
                        var use3D = $('#use3d').val();
                    }else{
                        var use3D = 0; 
                    }
                    var useBKM = $('#useBKM').val();
                    if(useBKM == '0'){
                            var cc_name = $('#input-cc-name').val();
                            var cc_number = $('#input-cc-number').val();
                            var cc_month = $('#input-cc-month').val();
                            var cc_year = $('#input-cc-year').val();
                            var cc_cvc = $('#input-cc-cvc').val();
                            var cc_length = cc_number.length;
                            var expiry_month = this.validateCardExpiryMonth(cc_month, cc_year);                   
                            var expiry_year = this.validateCardExpiryYear(cc_year);                   
                            var permonth =$('#per_month'+baseinstallment).attr('rel');
                            var totalInstallamt =$('#total'+baseinstallment).attr('rel');
                            if(campaign_id_set == 0 || campaign_id_set == null || campaign_id_set == undefined){
                                var data= {installments:installment, total:grandtotal, cc_name:cc_name, cc_number:cc_number, cc_month:cc_month, cc_year:cc_year, cc_cvc:cc_cvc, use3d:use3D,
                                customer_firstname:firstname, customer_lastname:lastname, customer_email:email, customer_phone:phone, bank_id:bank_id, gateway:gateway}
                            }else{
                                var data= {installments:installment, campaign_id:campaign_id_set, total:grandtotal, cc_name:cc_name, cc_number:cc_number, cc_month:cc_month, cc_year:cc_year, cc_cvc:cc_cvc, use3d:use3D,
                                customer_firstname:firstname, customer_lastname:lastname, customer_email:email, customer_phone:phone, bank_id:bank_id, gateway:gateway}
                            }    
                            if(cc_length == 16 && expiry_month == true && expiry_year == true){
                                $.ajax({
                                    dataType: 'json',
                                    url: url_submit,
                                    data: data,
                                    async : false,
                                    type: 'post',
                                    success: function(result)
                                    {   
                                        if(result != null) {
                                            if(result.ErrorCode == 0){
                                                flag_success = 1;
                                                $(".error-message").html('');
                                            } else {
                                                flag_success = 0;
                                                $(".error-message").html('<div class="message message-warning warning"><span>' + result.ErrorMSG + '</span></div>');
                                            }
                                        } else if ( use3D != null && use3D == 1 && result == null){
                                            flag_success = 1;
                                            window.location.assign(url_redirect);
                                        } else {
                                            flag_success = 0;    
                                            $(".error-message").html('<div class="message message-warning warning"><span>Something happend wrong please try again!</span></div>');
                                        }
                                    },
                                    error: function(){
                                        /*alert("error");*/  
                                    }
                                });
                            } else {
                                flag_success = 0;
                            }
                    } else {
                        var isInstallment = this.isInstallmentActive(); 
                        var data= {installments:isInstallment, total:grandtotal,customer_firstname:firstname, customer_lastname:lastname, customer_email:email, customer_phone:phone}
                        $.ajax({
                            dataType: 'json',
                            url: url_bkm,
                            data: data,
                            async : false,
                            type: 'post',
                            success: function(result)
                            {
                                if(result != null){
                                    flag_success = 0;
                                    if(result.ErrorCode == 0){
                                        var wrapper = $(".error-message-bkm");                                    
                                        $(wrapper).html('');
                                    } else {
                                        var wrapper = $(".error-message-bkm");                                    
                                        $(wrapper).html('<div class="message message-warning warning"><span>' + result.ErrorMSG + '</span></div>');
                                    }
                                } else if( useBKM != null && useBKM == 1 && result == null){
                                    flag_success = 1;
                                    window.location.assign(url_redirect);
                                } else {
                                    flag_success = 0;
                                    $(".error-message-bkm").html('<div class="message message-warning warning"><span>Something happend wrong please try again!</span></div>');
                                }
                            },
                            error: function(){
                            }
                        });                        
                    } 
                    if(flag_success == 1){
                        this.placeOrder();
                    } else {
                        $(".ajaxLoader").html('');
                    }
                }/*end if*/
            }

        });
    }
);