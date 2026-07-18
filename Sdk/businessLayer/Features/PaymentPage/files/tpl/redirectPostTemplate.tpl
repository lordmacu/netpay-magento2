<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <script language="javascript">
        var autoload = false;
        function load(){
            document.Redirect.submit();
            autoload = true;
        }
    </script>
</head>
<body onload="load();">
    <form action="{$paymentpage->endpointUrl}" method="POST" name="Redirect">
        {foreach key=name item=val from=$paymentpage->mappingArray}
            <input type="hidden" name="{$name}" value="${$val}">
        {/foreach}
    </form>
    <p style="text-align:center;padding:40px;">Redirecting to Payment Authentication Page.<br/>Please wait...</p>
</body>
</html>