[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]
[{ if $readonly }]
    [{assign var="readonly" value="readonly disabled"}]
[{else}]
    [{assign var="readonly" value=""}]
[{/if}]


<script type="text/javascript">
<!--
window.onload = function ()
{
    [{ if $updatelist == 1}]
        top.oxid.admin.updateList('[{ $oxid }]');
    [{ /if}]
    top.reloadEditFrame();
}
function editThis( sID )
{
    var oTransfer = top.basefrm.edit.document.getElementById( "transfer" );
    oTransfer.oxid.value = sID;
    oTransfer.cl.value = top.basefrm.list.sDefClass;

    //forcing edit frame to reload after submit
    top.forceReloadingEditFrame();

    var oSearch = top.basefrm.list.document.getElementById( "search" );
    oSearch.oxid.value = sID;
    oSearch.actedit.value = 0;
    oSearch.submit();
}
function processUnitInput( oSelect, sInputId )
{
    document.getElementById( sInputId ).disabled = oSelect.value ? true : false;
}
function loadLang(obj)
{
    var langvar = document.getElementById("agblang");
    if (langvar != null )
        langvar.value = obj.value;
    document.myedit.submit();
}
//-->
</script>

<form name="transfer" id="transfer" action="[{ $oViewConf->getSelfLink() }]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="oxid" value="[{ $oxid }]">
    <input type="hidden" name="cl" value="wawi_article_extend">
    <input type="hidden" name="editlanguage" value="[{ $editlanguage }]">
</form>

<form name="myedit" id="myedit" action="[{ $oViewConf->getSelfLink() }]" enctype="multipart/form-data" method="post">
[{$oViewConf->getHiddenSid()}]
<input type="hidden" name="cl" value="wawi_article_extend">
<input type="hidden" name="fnc" value="">
<input type="hidden" name="oxid" value="[{ $oxid }]">
<input type="hidden" name="voxid" value="[{ $oxid }]">
<input type="hidden" name="oxparentid" value="[{ $oxparentid }]">
<input type="hidden" name="editval[article__oxid]" value="[{ $oxid }]">



  <table cellspacing="0" cellpadding="0" border="0" height="100%" width="100%">
    <tr height="10">
      <td></td><td></td>
    </tr>
    <tr>
      <td width="15"></td>
      <td valign="top" class="edittext">

        <table cellspacing="0" cellpadding="0" border="0">
              <tr>
                <td class="edittext" width="120">
                 Alt. Template
                </td>
                <td class="edittext">
                  <input type="text" class="editinput" size="25" maxlength="[{$edit->oxarticles__oxtemplate->fldmax_length}]" name="editval[oxarticles__oxtemplate]" value="[{$edit->oxarticles__oxtemplate->value}]" [{ $readonly }]>
                </td>
              </tr>
              <tr>
                <td class="edittext" width="120">
                  Kann gesucht werden
                </td>
                <td class="edittext">
                  <input class="edittext" type="hidden" name="editval[oxarticles__oxissearch]" value='0'>
                  <input class="edittext" type="checkbox" name="editval[oxarticles__oxissearch]" value='1' [{if $edit->oxarticles__oxissearch->value == 1}]checked[{/if}] [{ $readonly }]>
                  
                </td>
              </tr>
              <tr>
                <td class="edittext" width="140">
                  Artikel ist individualisierbar
                </td>
                <td class="edittext">
                  <input type="hidden" name="editval[oxarticles__oxisconfigurable]" value='0'>
                  <input class="edittext" type="checkbox" name="editval[oxarticles__oxisconfigurable]" value='1' [{if $edit->oxarticles__oxisconfigurable->value == 1}]checked[{/if}]>
                 
                </td>
              </tr>
              <tr>
                <td class="edittext" width="120">
                  Versandkostenfrei 
                </td>
                <td class="edittext">
                  <input class="edittext" type="hidden" name="editval[oxarticles__oxfreeshipping]" value='0'>
                  <input class="edittext" type="checkbox" name="editval[oxarticles__oxfreeshipping]" value='1' [{if $edit->oxarticles__oxfreeshipping->value == 1}]checked[{/if}] [{ $readonly }] [{if $oxparentid }]readonly disabled[{/if}]>
                  
                </td>
              </tr>
              <tr>
                <td class="edittext">
                  Preisalarm deaktivieren
                </td>
                <td class="edittext">
                  <input type="hidden" name="editval[oxarticles__oxblfixedprice]" value='0'>
                  <input class="edittext" type="checkbox" name="editval[oxarticles__oxblfixedprice]" value='1' [{if $edit->oxarticles__oxblfixedprice->value == 1}]checked[{/if}] [{ $readonly }]>
                
                </td>
              </tr>
              <tr>
                <td class="edittext" width="140">
                  Alle neg. Nachl&auml;sse ignorieren.
                </td>
                <td class="edittext">
                  <input type="hidden" name="editval[oxarticles__oxskipdiscounts]" value='0'>
                  <input class="edittext" type="checkbox" name="editval[oxarticles__oxskipdiscounts]" value='1' [{if $edit->oxarticles__oxskipdiscounts->value == 1}]checked[{/if}]>
                  
                </td>
              </tr>
              <tr>
                  <td class="edittext" width="140">
                      AGB best&auml;tigen 
                  </td>
                  <td class="edittext">
                      <input type="hidden" name="editval[oxarticles__oxshowcustomagreement]" value='0'>
                      <input class="edittext" type="checkbox" name="editval[oxarticles__oxshowcustomagreement]" value='1' [{if $edit->oxarticles__oxshowcustomagreement->value == 1}]checked[{/if}] [{if $oxparentid }]disabled[{/if}]>
                      
                  </td>
              </tr>
              <tr>
                    <td class="edittext wrap">
                      E-Mail schicken, falls Bestand<br>unter folg. Wert sinkt 
                    </td>
                    <td class="edittext">
                      <input type="hidden" name="editval[oxarticles__oxremindactive]" value='0'>
                      <input type="checkbox" class="editinput" name="editval[oxarticles__oxremindactive]" value='1' [{if $edit->oxarticles__oxremindactive->value }]checked[{/if}] [{ $readonly }] [{if $oxparentid }]readonly disabled[{/if}]>
                      
                      <input type="text" class="editinput" size="20" maxlength="[{$edit->oxarticles__oxremindamount->fldmax_length}]" name="editval[oxarticles__oxremindamount]" value="[{$edit->oxarticles__oxremindamount->value}]" [{ $readonly }]>
                    </td>
              </tr>
              <tr>
                <td class="edittext">
                 Artikel dazu
                </td>
                <td class="edittext">
                  [{ $bundle_artnum }] [{ $bundle_title|oxtruncate:21:"...":true }]
                  <input [{ $readonly }] type="button" value="Artikel zuordnen" class="edittext" onclick="JavaScript:showDialog('&cl=article_extend&aoc=2&oxid=[{ $oxid }]');">
                </td>
              </tr>
              
              <tr>
                  <td colspan="2">
                      <fieldset title="[{ oxmultilang ident="ARTICLE_EXTEND_UPDATEPRICES" }]" style="padding-left: 5px;">
            <legend>Preis zur festgesetzten Zeit aktualisieren</legend><br>

            <table cellspacing="0" cellpadding="0" border="0">
                <tr>
                    [{oxhasrights object=$edit field='oxupdateprice' readonly=$readonly }]
                        <td>Basispreis ([{ $oActCur->sign }]):&nbsp;</td><td><input type="text" class="editinput" size="4" maxlength="[{$edit->oxarticles__oxupdateprice->fldmax_length}]" name="editval[oxarticles__oxupdateprice]" value="[{$edit->oxarticles__oxupdateprice->value}]"></td>
                    [{/oxhasrights}]
                    [{oxhasrights object=$edit field='oxupdatepricea' readonly=$readonly }]
                        <td>&nbsp;A&nbsp;</td><td><input type="text" class="editinput" size="4" maxlength="[{$edit->oxarticles__oxupdatepricea->fldmax_length}]" name="editval[oxarticles__oxupdatepricea]" value="[{$edit->oxarticles__oxupdatepricea->value}]"></td>
                    [{/oxhasrights}]
                    [{oxhasrights object=$edit field='oxupdatepriceb' readonly=$readonly }]
                        <td>&nbsp;B&nbsp;</td><td><input type="text" class="editinput" size="4" maxlength="[{$edit->oxarticles__oxupdatepriceb->fldmax_length}]" name="editval[oxarticles__oxupdatepriceb]" value="[{$edit->oxarticles__oxupdatepriceb->value}]"></td>
                    [{/oxhasrights}]
                    [{oxhasrights object=$edit field='oxupdatepricec' readonly=$readonly }]
                        <td>&nbsp;C&nbsp;</td><td><input type="text" class="editinput" size="4" maxlength="[{$edit->oxarticles__oxupdatepricec->fldmax_length}]" name="editval[oxarticles__oxupdatepricec]" value="[{$edit->oxarticles__oxupdatepricec->value}]"></td>
                    [{/oxhasrights}]
                </tr>
                [{oxhasrights object=$edit field='oxupdatepricetime' readonly=$readonly }]
                <tr>
                    <td>Startzeit&nbsp;</td>
                    <td colspan="7">
                        <input type="text" class="editinput" size="20" maxlength="20" name="editval[oxarticles__oxupdatepricetime]" value="[{$edit->oxarticles__oxupdatepricetime->value|oxformdate}]">
                    </td>
                </tr>
                [{/oxhasrights}]
            </table>

            

       </fieldset>
                  </td>
              </tr>
              <tr>
                    <td class="edittext" colspan="2"><br>
                      <fieldset title="Info falls Artikel auf Lager" style="padding-left: 5px;">
                      <legend>Info falls Artikel auf Lager</legend><br>
                      <table>
                        <tr>
                          <td class="edittext">
                            In Sprache
                          </td>
                          <td class="edittext">
                             <select name="editlanguage" id="test_editlanguage" class="editinput" onChange="Javascript:loadLang(this);" [{$readonly}] [{$readonly_fields}]>
                             [{foreach from=$otherlang key=lang item=olang}]
                             <option value="[{ $lang }]"[{ if $olang->selected}]SELECTED[{/if}]>[{ $olang->sLangDesc }]</option>
                             [{/foreach}]
                             </select>
                             
                          </td>
                        </tr>
                        <tr>
                          <td class="edittext">
                            Info falls Artikel auf Lager
                          </td>
                          <td class="edittext">
                            <input type="text" class="editinput" size="40" maxlength="[{$edit->oxarticles__oxstocktext->fldmax_length}]" name="editval[oxarticles__oxstocktext]" value="[{$edit->oxarticles__oxstocktext->value}]" [{ $readonly }]>
                          </td>
                        </tr>
                        <tr>
                          <td class="edittext">
                           Info falls Artikel nicht auf Lager
                          </td>
                          <td class="edittext">
                            <input type="text" class="editinput" size="40" maxlength="[{$edit->oxarticles__oxnostocktext->fldmax_length}]" name="editval[oxarticles__oxnostocktext]" value="[{$edit->oxarticles__oxnostocktext->value}]" [{ $readonly }]>
                            
                          </td>
                        </tr>
                      </table>
                      </fieldset>
                    </td>
                  </tr>
          <tr>
            <td class="edittext"></td>
            <td class="edittext">
              <input type="submit" class="edittext" name="save" value="Speichern" onClick="Javascript:document.myedit.fnc.value='save'"" ><br>
            </td>
          </tr>
        </table>

      </td>

      <!-- Anfang rechte Seite -->

      <td valign="top" class="edittext" align="left" width="55%" style="table-layout:fixed">

        

      </td>
      <!-- Ende rechte Seite -->
    </tr>
  </table>


</form>

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]