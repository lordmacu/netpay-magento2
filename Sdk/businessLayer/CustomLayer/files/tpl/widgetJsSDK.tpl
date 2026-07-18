<!--In this file the paymentprovider js events for Widget are defined/created. 
This we will find in documentation from payment provider. Change type of this file 
to the shopsystem used template file type (phtml/twig/tpl)-->

{include file="`$dir`/`$paymentpage->plugIntFilesFolderPath`/helperFunctions.html"}
<script>
var mappingJson = {$paymentpage->mappingArray|@json_encode};
$('#customButton').on('click', function (e) {
    e.preventDefault();
    LatpayCheckout.open({
        merchantid: 'test_ideatarmac' ,
        publickey: 'TlbILw7ydhLPN0AZ' ,
        amount: {$total},
        currency: 'AUD' ,
        reference: 'Test1234_ecommerce' ,
        description: 'Test1234_ecommerce',
        token: function (token) {
            console.log(token);
            var form = createForm(mappingJson,token);
            $('#customButton').append(form);
            if (token == '1') {
                $("#widgetPaymentForm").attr("action", "{$paymentpage->callbackUrls.cancled}");
                $("#widgetPaymentForm").submit();
            } else if (token.Status == '1') {
                $("#widgetPaymentForm").attr("action", "{$paymentpage->callbackUrls.failed}");
                $("#widgetPaymentForm").submit();
            } else if (token.Status == '0') {
                $("#widgetPaymentForm").attr("action", "{$paymentpage->callbackUrls.success}");
                $("#widgetPaymentForm").submit();
            }
        }
    });
});
</script>