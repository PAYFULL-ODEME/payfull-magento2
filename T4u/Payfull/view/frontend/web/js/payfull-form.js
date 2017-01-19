function openPaymentMethod(evt, paymentName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(paymentName).style.display = "block";
    evt.currentTarget.className += " active";
    if(paymentName == 'bkmPaymentMethod'){
        document.getElementById("useBKM").value = '1';
    }
    else{
    document.getElementById("useBKM").value = '0';        
    }
}

function setInputImage(event, value)
{   
    var first_n_char = value.substring(0, 1);
    ccImage = document.getElementById("input-cc-number");
   
    if(first_n_char=='4'){
        ccImage.style.background = 'rgba(0, 0, 0, 0) url("http://payfull.net/plugins/oc/v2.x/image/payfull/payfull_creditcard_visa.png") no-repeat scroll right center / 8% auto';       
    }
    else if(first_n_char=='5'){
        ccImage.style.background = 'rgba(0, 0, 0, 0) url("http://payfull.net/plugins/oc/v2.x/image/payfull/payfull_creditcard_master.png") no-repeat scroll right center / 8% auto';        
    }
    else{        
        ccImage.style.background = 'rgba(0, 0, 0, 0) url("http://payfull.net/plugins/oc/v2.x/image/payfull/payfull_creditcard_not_supported.png") no-repeat scroll right center / 8% auto';
    }
    
}