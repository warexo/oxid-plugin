[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]

<script type="text/javascript">
<!--
function ChangeDiscountType(oObj)
{   var oHObj = document.getElementById("itmart");
    var oDObj = document.getElementById("editval[oxdiscount__oxaddsum]");
    if ( oDObj != null && oHObj != null && oObj != null)
    {   if ( oObj.value == "itm")
        {   oHObj.style.display = "";
            oDObj.style.display = "none";
        }
        else
        {   oHObj.style.display = "none";
            oDObj.style.display = "";
        }
    }
}
//-->
</script>

[{ if $readonly}]
    [{assign var="readonly" value="readonly disabled"}]
[{else}]
    [{assign var="readonly" value=""}]
[{/if}]

<form name="transfer" id="transfer" action="[{ $oViewConf->getSelfLink() }]" method="post">
    [{ $oViewConf->getHiddenSid() }]
    <input type="hidden" name="oxid" value="[{ $oxid }]">
    <input type="hidden" name="oxidCopy" value="[{ $oxid }]">
    <input type="hidden" name="cl" value="wawi_discount_main">
    <input type="hidden" name="language" value="[{ $actlang }]">
</form>

<form name="myedit" id="myedit" action="[{ $oViewConf->getSelfLink() }]" method="post">
[{ $oViewConf->getHiddenSid() }]
<input type="hidden" name="cl" value="wawi_discount_main">
<input type="hidden" name="fnc" value="">
<input type="hidden" name="oxid" value="[{ $oxid }]">
<input type="hidden" name="editval[oxdiscount__oxid]" value="[{ $oxid }]">
<input type="hidden" name="language" value="[{ $actlang }]">

<table cellspacing="0" cellpadding="0" border="0" width="98%">
<tr>
    <td valign="top" class="edittext">

        <table cellspacing="0" cellpadding="0" border="0">
          
                
                <tr id="itmart"[{if $edit->oxdiscount__oxaddsumtype->value != "itm" }] style="display:none;"[{/if}]>
                  <td class="edittext">
                    [{ oxmultilang ident="DISCOUNT_MAIN_EXTRA" }]
                  </td>
                  <td class="edittext">
                    <table>
                      <tr>
                        <td>[{$oView->getItemDiscountProductTitle()}]</td>
                        <td>
                          <input [{ $readonly }] type="button" value="[{ oxmultilang ident="GENERAL_CHANGEPRODUCT" }]" class="edittext" onclick="JavaScript:showDialog('&cl=discount_main&aoc=2&oxid=[{ $oxid }]');">
                          [{ oxinputhelp ident="HELP_DISCOUNT_MAIN_EXTRA" }]
                        </td>
                      </tr>
                      <tr>
                        <td>[{ oxmultilang ident="DISCOUNT_MAIN_MULTIPLY_DISCOUNT_AMOUNT" }]</td>
                        <td><input type="text" class="editinput" size="5" maxlength="[{$edit->oxdiscount__oxitmamount->fldmax_length}]" name="editval[oxdiscount__oxitmamount]" value="[{$edit->oxdiscount__oxitmamount->value}]" [{ $readonly }]></td>
                      </tr>
                      <tr>
                        <td>[{ oxmultilang ident="DISCOUNT_MAIN_MULTIPLY_DISCOUNT_ARTICLES" }]</td>
                        <td>
                          <input type="hidden" name="editval[oxdiscount__oxitmmultiple]" value="0">
                          <input class="edittext" type="checkbox" name="editval[oxdiscount__oxitmmultiple]" value='1' [{if $edit->oxdiscount__oxitmmultiple->value == 1}]checked[{/if}] [{ $readonly }]>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
          
        <tr>
            <td class="edittext">
            </td>
            <td class="edittext"><br>
            <input type="submit" class="edittext" name="save" value="[{ oxmultilang ident="GENERAL_SAVE" }]" onClick="Javascript:document.myedit.fnc.value='save'"" [{ $readonly }]><br>
            </td>
        </tr>
        </table>
    </td>
    </tr>
</table>

</form>

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]
