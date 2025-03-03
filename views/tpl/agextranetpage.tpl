
<script>
    if (typeof window.handleExtranetReloadPage === "undefined")
    {
        window.handleExtranetReloadPage = function(event) {
            if (event.data && event.data.message)
            {
                if (event.data.message == 'extranetreloadpage')
                {
                    if (document.location.href.indexOf('?') === -1)
                        document.location.href = document.location.href+'?'+'extraneturl='+btoa(event.data.context.url);
                    else if (document.location.href.indexOf('extraneturl=') !== -1)
                        document.location.href = document.location.href.replace(/extraneturl=[^&]+/,'extraneturl='+btoa(event.data.context.url));
                    else
                        document.location.href = document.location.href+'&'+'extraneturl='+btoa(event.data.context.url);
                }
                else if (event.data.message == 'extranetgotocart')
                {
                    document.location.href = '[{oxgetseourl ident=$oViewConf->getSelfLink()|cat:"cl=basket"}]';
                }
                else if (event.data.message == 'setextranetpageheight')
                {
                    $('#[{$id}]_div').css('height', event.data.context.height+'px');
                }
                else if (event.data.message == 'extranetreloadloginpage')
                {
                    var tokenstr = "";
                    if (document.location.href.indexOf('?') === -1)
                        tokenstr = "?regeneratetoken=1";
                    else
                        tokenstr = "&regeneratetoken=1";
                    document.location.href = document.location.href+tokenstr;
                }
                else if (event.data.message == 'extranetopenforeignurl')
                {
                    window.open(event.data.context.url, '_blank');
                }
                else if (event.data.message == 'setextranetpageurl')
                {
                    var stateObj = { };
                    if (document.location.href.indexOf('extraneturl=') !== -1)
                        history.pushState(stateObj, "", document.location.href.replace(/extraneturl=[^&]+/,'extraneturl='+btoa(event.data.context.url)));
                    else if (document.location.href.indexOf('?') === -1)
                        history.pushState(stateObj, "", document.location.href+'?'+'extraneturl='+btoa(event.data.context.url));
                    else
                        history.pushState(stateObj, "", document.location.href+'&'+'extraneturl='+btoa(event.data.context.url));
                }
            }
        };
        
        window.addEventListener("message", window.handleExtranetReloadPage, false);
    }
</script>
<div style="clear: both; position: relative; height: [{if $height}][{$height}][{else}]1200px[{/if}]" id="[{$id}]_div">
<iframe id="[{$id}]" src="[{$url}]" frameborder="0" class="extranet-iframe" style="border: none; width:100%; height:100%; position: absolute"></iframe>
</div>
