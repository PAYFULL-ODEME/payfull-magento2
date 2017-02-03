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
        'Magento_Payment/js/model/credit-card-validation/validator',
        'mage/validation'           
    ],
    function ($, ko, quote, priceUtils, url, Component) {
        'use strict';
        
        var billingAddress = quote.billingAddress();
        var totals = quote.totals();
        var grandtotal_first = 0;
        var grandtotal = 0;
        var count = 0;
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
        var bank_id = '';
        var gateway = '';
        var campaign_id = new Array();
        var per_month_value = new Array();
        var total_value = new Array();
        var extra_installments = new Array();
        var installment = 1;
        var baseinstallment=1;
        var flag = 0;
        var cc_bin_length = '';
        var cc_bin = '';
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

            beforeAjaxCall: function(){
                var cc = $('#input-cc-number').val();
                cc_bin = cc.substring(0, 6);
                console.log(cc_bin+"aaaa"+flag);
                if (flag == 0 && cc_bin.length == 6) {
                    cc_bin_length = cc_bin.length;
                    flag = 1;
                    console.log(cc_bin+"first"+"aaaa"+flag);
                    this.ajaxCall();
                } else if (flag == 1 && cc_bin.length < 6) {
                    cc_bin_length = cc_bin.length;
                    flag = 0;
                    console.log(cc_bin+"second"+"aaaa"+flag);
                    this.ajaxCall();
                }
            },
            
            ajaxCall: function(){                  
                console.log(cc_bin+"yyyyyy");
                var min_order = this.getMinOrderTotal();
                if(cc_bin_length <= 6) {
                    var data= {cc:cc_bin}
                    $.ajax({
                        dataType: 'json',
                        url: url_cc,
                        data: data,
                        type: 'post',
                        success: function(result)
                        {   
                            var html = '' ;
                            var wrapper = $(".installment_row"); /*Fields wrapper*/
                            if(result == null){
                                var grandtotal_null = priceUtils.formatPrice(grandtotal, quote.getPriceFormat());
                                /*some this wrong with api*/
                                $('#bankImage').attr('src',''); 
                                html += '<div class="install_body_label installment_radio"><input rel="1" class="installment_radio" id="installment_radio" checked="" name="installments" value="1" type="radio"></div>';
                                html += '<div class="install_body_label installment_lable_code"><span data-bind="text: installment">One Shot</span></div>';
                                html +='<div class="install_body_label">';
                                html +='<span data-bind="text: amount">'+grandtotal_null+'</span>';
                                html +='</div>';
                                html +='<div class="install_body_label final_commi_price">';
                                html +='<span data-bind="text: total">'+grandtotal_null+'</span>';
                                html +='</div></div>';   
                                $(wrapper).html(html);
                            }else{
                                bank_id = result.bank;
                                gateway = result.gateway;                                        
                            
                                if(grandtotal >= min_order){
                                    count = 0;
                                    installment_count = 0;
                                    $('#bankImage').attr('src',result.image);
                                    if(result.installments != null ){                                   
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
                                                for (var i = 0; i < campaigns_length; i++) {
                                                    total_installments = parseInt(extra_installments[i]);
                                                    total_installments += parseInt(installment);
                                                    options += '<option value="' + total_installments +'">+ ' + extra_installments[i] +'</option>';
                                                }
                                                $('.extra_installment').html('<label>Extra Installments</label><div class="extra_installments_select"><select id ="campaign_id" name="campaign_id" class="form-control"><option value="">- Select -</option>'+options+'</select></div>');
                                                                                                
                                            });
                                            $("#installment_radio"+ installment_count).click(function(){  
                                             
                                                $('.extra_installment').html('');     
                                                installment = $(this).val(); 
                                                baseinstallment = parseInt(installment);
                                            });
                                        }/*end for*/
                                        $('body').on('change','#campaign_id',function(){                                    
                                            var extra = $(this).val();                                     
                                            
                                            extra = parseInt(extra);
                                            baseinstallment =  parseInt(installment);
                                            installment = extra; 
                                             
                                                                                            
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

            submitForm: function(){
                if (this.validateForm('#payfull-form')) {
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
                            var permonth =$('#per_month'+baseinstallment).attr('rel');
                            var totalInstallamt =$('#total'+baseinstallment).attr('rel');
                            var data= {installments:installment, total:grandtotal, cc_name:cc_name, cc_number:cc_number, cc_month:cc_month, cc_year:cc_year, cc_cvc:cc_cvc, use3d:use3D,
                            customer_firstname:firstname, customer_lastname:lastname, customer_email:email, customer_phone:phone, bank_id:bank_id, gateway:gateway}
                            if(cc_length == 16){                                
                                $.ajax({
                                    dataType: 'json',
                                    url: url_submit,
                                    data: data,
                                    type: 'post',
                                    success: function(result)
                                    {   
                                        // alert(result+"qqqq");                                     
                                        if(result != null){
                                            // alert("result");
                                            if(result.ErrorCode !=0){
                                                var wrapper = $(".error-message");
                                                $(wrapper).html('<div class="message message-warning warning">' + result.ErrorMSG + '</div>');
                                            }else{
                                                var wrapper = $(".error-message-bkm");                                    
                                                $(wrapper).html('');
                                            }
                                        }else{
                                            // alert(result+"qqqqq");
                                             window.location.assign(url_redirect);
                                        }
                                    },
                                    error: function(){                                       
                                    }
                                });
                            }
                    }else{
                        var isInstallment = this.isInstallmentActive(); 
                        var data= {installments:isInstallment, total:grandtotal,customer_firstname:firstname, customer_lastname:lastname, customer_email:email, customer_phone:phone}
                        $.ajax({
                            dataType: 'json',
                            url: url_bkm,
                            data: data,
                            type: 'post',
                            success: function(result)
                            {
                                if(result != null){
                                    if(result.ErrorCode !=0){
                                        var wrapper = $(".error-message-bkm");                                    
                                        $(wrapper).html('<div class="message message-warning warning">' + result.ErrorMSG + '</div>');
                                    }else{
                                        var wrapper = $(".error-message-bkm");                                    
                                        $(wrapper).html('');
                                    }
                                }else{
                                    window.location.assign(url_redirect);
                                }
                            },
                            error: function(){
                                
                            }
                        });                        
                    }                    
                   this.placeOrder();
                }
            }

        });
    }
);