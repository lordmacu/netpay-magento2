<script>var $ = jQuery; </script>
<script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.4.0.min.js"></script>
{$paymentpage->jsFiles}
{foreach $paymentpage->templates as $path}
    {include file="$dir$path"}
{/foreach}