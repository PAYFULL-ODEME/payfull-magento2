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
function temp() {
    alert("ssssssssssss");
}
