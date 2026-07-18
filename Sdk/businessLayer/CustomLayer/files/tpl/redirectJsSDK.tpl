<!--In this file the paymentprovider js events for Redurect are defined/created. 
This we will find in documentation from payment provider. Change type of this file 
to the shopsystem used template file type (phtml/twig/tpl)-->

<script>
window.onload = function() {
    AfterPay.initialize({
        countryCode: '{$countrycode}'
    });
    AfterPay.redirect({
        token: '{$ordertoken}'
    });
};
</script>