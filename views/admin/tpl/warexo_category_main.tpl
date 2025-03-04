[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]

<script type="text/javascript">
<!--
function SchnellSortManager(oObj)
{   oRadio = document.getElementsByName("editval[oxcategories__oxdefsortmode]");
    if(oObj.value)
        for ( i=0; i<oRadio.length; i++)
            oRadio.item(i).disabled="";
    else
        for ( i=0; i<oRadio.length; i++)
            oRadio.item(i).disabled = true;
}

function DeletePic( sField )
{
    var oForm = document.getElementById("myedit");
    oForm.fnc.value="deletePicture";
    oForm.masterPicField.value=sField;
    oForm.submit();
}

function LockAssignment(obj)
{   var aButton = document.myedit.assignArticle;
    if ( aButton != null && obj != null )
    {
        if (obj.value > 0)
        {
            aButton.disabled = true;
        }
        else
        {
            aButton.disabled = false;
        }
    }
}
//-->
</script>
<!-- END add to *.css file -->
<form name="transfer" id="transfer" action="[{ $oViewConf->getSelfLink() }]" method="post">
    [{ $oViewConf->getHiddenSid() }]
    <input type="hidden" name="oxid" id="oxid" value="[{ $oxid }]">
    <input type="hidden" name="cl" value="wawi_category_main">
    <input type="hidden" name="editlanguage" value="[{ $editlanguage }]">
</form>

[{ if $readonly }]
    [{assign var="readonly" value="readonly disabled"}]
[{else}]
    [{assign var="readonly" value=""}]
[{/if}]

[{ if $readonly_fields }]
    [{assign var="readonly_fields" value="readonly disabled"}]
[{else}]
    [{assign var="readonly_fields" value=""}]
[{/if}]

<form name="myedit" id="myedit" enctype="multipart/form-data" action="[{ $oViewConf->getSelfLink() }]" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="[{$iMaxUploadFileSize}]">
[{ $oViewConf->getHiddenSid() }]
<input type="hidden" name="cl" value="wawi_category_main">
<input type="hidden" name="fnc" value="">
<input type="hidden" name="oxid" value="[{ $oxid }]">
<input type="hidden" name="editval[oxcategories__oxid]" value="[{ $oxid }]">
<input type="hidden" name="masterPicField" value="">

[{if !$sortableFields}]
[{assign var=sortableFields value=$pwrsearchfields}]
[{/if}]
<table cellspacing="0" cellpadding="0" border="0" width="98%">
<tr>
    <td valign="top" class="edittext">

      <table cellspacing="0" cellpadding="0" border="0">
          <input type="hidden" value="[{$edit->oxcategories__oxactive->value}]" name="editval[oxcategories__oxactive]">
          <input type="hidden" value="[{$edit->oxcategories__oxhidden->value}]" name="editval[oxcategories__oxhidden]">
            <tr>
                <td class="edittext">
                Alternatives Template
                </td>
                <td class="edittext" colspan="2">
                <input type="text" class="editinput" size="42" maxlength="[{$edit->oxcategories__oxtemplate->fldmax_length}]" name="editval[oxcategories__oxtemplate]" value="[{$edit->oxcategories__oxtemplate->value}]" [{include file="help.tpl" helpid=article_template}] [{$readonly}]>
                
                </td>
            </tr>

            <tr>
                <td class="edittext">
                Schnellsortierung
                </td>
                <td class="edittext" colspan="2">
                <select name="editval[oxcategories__oxdefsort]" class="editinput" onChange="JavaScript:SchnellSortManager(this);">
                <option value="">[{ oxmultilang ident="CATEGORY_MAIN_NONE" }]</option>
                [{foreach from=$sortableFields key=field item=desc}]
                [{assign var="ident" value=GENERAL_ARTICLE_$desc}]
                [{assign var="ident" value=$ident|oxupper }]
                <option value="[{ $desc }]" [{ if $defsort == $desc }]SELECTED[{/if}]>[{ oxmultilang|oxtruncate:20:"..":true ident=$ident }]</option>
                [{/foreach}]
                </select>
                <input type="radio" class="editinput" name="editval[oxcategories__oxdefsortmode]" [{if !$defsort}]disabled[{/if}] value="0" [{if $edit->oxcategories__oxdefsortmode->value=="0"}]checked[{/if}]>asc
                <input type="radio" class="editinput" name="editval[oxcategories__oxdefsortmode]" [{if !$defsort}]disabled[{/if}] value="1" [{if $edit->oxcategories__oxdefsortmode->value=="1"}]checked[{/if}]>desc
                
                </td>
            </tr>
            <tr>
                <td class="edittext">
                Preis von/bis  ([{ $oActCur->sign }])
                </td>
                <td class="edittext" colspan="2">
                <input type="text" class="editinput" size="5" maxlength="[{$edit->oxcategories__oxpricefrom->fldmax_length}]" name="editval[oxcategories__oxpricefrom]" value="[{$edit->oxcategories__oxpricefrom->value}]" [{$readonly}]>&nbsp;
                <input type="text" class="editinput" size="5" maxlength="[{$edit->oxcategories__oxpriceto->fldmax_length}]" name="editval[oxcategories__oxpriceto]" value="[{$edit->oxcategories__oxpriceto->value}]" onchange="JavaScript:LockAssignment(this);" onkeyup="JavaScript:LockAssignment(this);" onmouseout="JavaScript:LockAssignment(this);" [{$readonly}]>
                
                </td>
            </tr>
            
            <tr>
                <td class="edittext">
                    Alle neg. Nachl&auml;sse ignorieren.<br>(Rabatte, Gutscheine, Zahlungsarten ...)
                </td>
                <td class="edittext" colspan="2">
                <input type="hidden" name="editval[oxcategories__oxskipdiscounts]" value='0' [{$readonly_fields}]>
                <input class="edittext" type="checkbox" name="editval[oxcategories__oxskipdiscounts]" value='1' [{if $edit->oxcategories__oxskipdiscounts->value == 1}]checked[{/if}] [{$readonly_fields}]>
               
                </td>
            </tr>
      
        <tr>
            <td class="edittext">
            </td>
            <td class="edittext" colspan="2"><br>
            <input type="submit" class="edittext" name="save" value="[{ oxmultilang ident="CATEGORY_MAIN_SAVE" }]" onClick="Javascript:document.myedit.fnc.value='save'" [{$readonly}]><br>
            </td>
        </tr>

        </table>
    </td>
    </tr>
</table>

</form>
[{include file="bottomnaviitem.tpl"}]

[{include file="bottomitem.tpl"}]