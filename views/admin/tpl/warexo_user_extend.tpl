[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]

[{ if $readonly}]
    [{assign var="readonly" value="readonly disabled"}]
[{else}]
    [{assign var="readonly" value=""}]
[{/if}]

<form name="transfer" id="transfer" action="[{ $oViewConf->getSelfLink() }]" method="post">
    [{ $oViewConf->getHiddenSid() }]
    <input type="hidden" name="oxid" value="[{ $oxid }]">
    <input type="hidden" name="cl" value="wawi_user_extend">
</form>

<form name="myedit" id="myedit" action="[{ $oViewConf->getSelfLink() }]" method="post">
[{ $oViewConf->getHiddenSid() }]
<input type="hidden" name="cl" value="wawi_user_extend">
<input type="hidden" name="fnc" value="">
<input type="hidden" name="oxid" value="[{ $oxid }]">
<input type="hidden" name="editval[oxuser__oxid]" value="[{ $oxid }]">

<table cellspacing="0" cellpadding="0" border="0" height="100%" width="100%">
<tr>
    <td width="15"></td>
    <td valign="top" class="edittext">
        <table cellspacing="0" cellpadding="0" border="0">
      
            
            <tr>
                <td class="edittext">
                Newsletter
                </td>
                <td class="edittext">
                    <input type="hidden" name="editnews" value='0'>
                    <input class="edittext" type="checkbox" name="editnews" value='1' [{if $edit->sDBOptin == 1}]checked[{/if}] [{ $readonly}]>
                   
                </td>
            </tr>
            <tr>
                <td class="edittext">
                E-Mail Adr. ung&uuml;ltig
                </td>
                <td class="edittext">
                    <input type="hidden" name="emailfailed" value='0'>
                    <input class="edittext" type="checkbox" name="emailfailed" value='1' [{if $edit->sEmailFailed == 1}]checked[{/if}] [{ $readonly}]>
                  
                </td>
            </tr>
           
            <tr>
                <td class="edittext">
                Bonuspunkte
                </td>
                <td class="edittext">
                [{$edit->oxuser__oxpoints->value}]
                </td>
            </tr>
            <tr>
                <td class="edittext wrap">
               Keine automatische Benutzergruppen-Zuordnung 
                </td>
                <td class="edittext">
                 <input type="hidden" name="editval[oxuser__oxdisableautogrp]" value='0'>
                <input class="edittext" type="checkbox" name="editval[oxuser__oxdisableautogrp]" value='1' [{if $edit->oxuser__oxdisableautogrp->value == 1}]checked[{/if}] [{ $readonly}]>
              
                </td>
            </tr>
      
        <tr>
            <td class="edittext">
            </td>
            <td class="edittext"><br>
            <input type="submit" class="edittext" name="save" value="Speichern" onClick="Javascript:document.myedit.fnc.value='save'"" [{ $readonly}]>
            </td>
        </tr>
        </table>
    </td>
   
 
    </tr>
</table>
</form>

[{include file="bottomnaviitem.tpl"}]

[{include file="bottomitem.tpl"}]
