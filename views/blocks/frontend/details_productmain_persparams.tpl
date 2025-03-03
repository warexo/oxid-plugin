[{$smarty.block.parent}]

[{assign var=oParentProduct value=$oDetailsProduct}]
[{if $oParentProduct->getParentArticle()}]
[{assign var=oParentProduct value=$oParentProduct->getParentArticle()}]
[{/if}]
[{if $oParentProduct->oxarticles__agisvoucher->value == 1}]
<div class="selectorsBox clear">
    <strong>[{oxmultilang ident="PAGE_DETAILS_PERSPARAM_VOUCHER_SEND_TO"}]</strong><br/>
    <div class="whom-send-row clear">
        <input class="voucherchanger" id="vouchersendto1" type="radio" name="persparam[vouchersendto]" value="customer" [{if !$aPersParam || $aPersParam.vouchersendto == 'customer'}]checked="checked"[{/if}]>
        <label for="vouchersendto1">[{oxmultilang ident="PAGE_DETAILS_PERSPARAM_VOUCHER_SEND_TO_CUSTOMER"}]</label>
        
    </div>
    <div class="whom-send-row clear">
        <input class="voucherchanger" id="vouchersendto2" type="radio" name="persparam[vouchersendto]" value="other" [{if $aPersParam.vouchersendto == 'other'}]checked="checked"[{/if}]>
        <label for="vouchersendto2">[{oxmultilang ident="PAGE_DETAILS_PERSPARAM_VOUCHER_SEND_TO_OTHER"}]</label>
    </div>
    <div id="voucherother">
        [{if !$oViewConf->isResponsiveTheme()}]
        <ul class="form">
            <li>
                <label for="vouchername">[{oxmultilang ident="PAGE_DETAILS_PERSPARAM_VOUCHER_OTHER_NAME"}]</label>
                <input id="vouchername" name="persparam[vouchername]" value="[{$aPersParam.vouchername}]" class="js-oxValidate js-oxValidate_notEmpty">
                <p class="oxValidateError">
                    <span class="js-oxError_notEmpty">[{ oxmultilang ident="EXCEPTION_INPUT_NOTALLFIELDS" }]</span>                    
                </p>

            </li>
            <li>
                <label for="vouchermail">[{oxmultilang ident="PAGE_DETAILS_PERSPARAM_VOUCHER_OTHER_EMAIL"}]</label>
                <input id="vouchermail" name="persparam[vouchermail]" value="[{$aPersParam.vouchermail}]" class="js-oxValidate js-oxValidate_notEmpty js-oxValidate_email">
                <p class="oxValidateError">
                    <span class="js-oxError_notEmpty">[{ oxmultilang ident="EXCEPTION_INPUT_NOTALLFIELDS" }]</span>                    
                    <span class="js-oxError_email">[{ oxmultilang ident="EXCEPTION_INPUT_NOVALIDEMAIL" }]</span>
                </p>
            </li>
            <li>
                <label for="vouchermessage">[{oxmultilang ident="PAGE_DETAILS_PERSPARAM_VOUCHER_OTHER_MESSAGE"}]</label>
                <textarea rows="6" id="vouchermessage" name="persparam[vouchermessage]">[{$aPersParam.vouchermessage|@utf8_decode}]</textarea>
                
            </li>
        </ul>
        [{else}]
        <div class="form-horizontal">
        <div class="form-group">
            <label class="req col-lg-12">[{oxmultilang ident="PAGE_DETAILS_PERSPARAM_VOUCHER_OTHER_NAME"}]</label>
            <div class="col-lg-12">
                <input id="vouchername" name="persparam[vouchername]" value="[{$aPersParam.vouchername}]" class="form-control voucher-required" >
            </div>
        </div>
        <div class="form-group">
            <label class="req col-lg-12">[{oxmultilang ident="PAGE_DETAILS_PERSPARAM_VOUCHER_OTHER_EMAIL"}]</label>
            <div class="col-lg-12">
                 <input id="vouchermail" name="persparam[vouchermail]" value="[{$aPersParam.vouchermail}]" class="form-control voucher-required">
            </div>
        </div>
        <div class="form-group">
            <label class="req col-lg-12">[{oxmultilang ident="PAGE_DETAILS_PERSPARAM_VOUCHER_OTHER_MESSAGE"}]</label>
            <div class="col-lg-12">
                 <textarea rows="6" id="vouchermessage" name="persparam[vouchermessage]" class="form-control">[{$aPersParam.vouchermessage|@utf8_decode}]</textarea>
            </div>
        </div>
        </div>
        [{/if}]
    </div>
    <input type="hidden" name="persparam[details]" value="1">
</div>            
[{capture assign="voucherScript"}]
    $(function(){
        
        if( $('#vouchersendto1').is(':checked')){
            $('#voucherother').hide();
        }    
        $('.voucherchanger').change(function(){
            $('#vouchersendto').val($(this).val());
            if($(this).val() == 'customer'){
                $('#voucherother').hide();
            }else{
                $('#voucherother').show();
            }
        });
        $('#vouchermessage').change(function(){
            $('#vouchermessagefield').val($(this).val());
        });
    });
[{/capture}]
[{oxscript add=$voucherScript}]
[{if !$oViewConf->isResponsiveTheme()}]
[{oxscript include="js/widgets/oxinputvalidator.js" priority=10 }]
[{oxscript add="$('form.js-oxProductForm').oxInputValidator();"}]
[{else}]
[{capture assign="voucherScript"}]
    [{strip}]
    $('.voucher-required').jqBootstrapValidation(
        {
            filter: function()
            {
                if( $( '#voucherother' ).css( 'display' ) == 'block' )
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
        }
    );
    [{/strip}]
[{/capture}]
[{oxscript add=$voucherScript}]
[{/if}]
[{/if}]
