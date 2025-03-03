[{if $edit->oxuser__oxid->value}]
<tr>
    <td class="edittext" colspan="2">
        <b>Oxid: [{$edit->oxuser__oxid->value}]<br>
        <b>WAWI Id: [{$edit->oxuser__wwforeignid->value}] 
    </td>
                  
</tr>
[{/if}]
[{if $edit->oxuser__wwcustomernumber->value}]
<tr>
    <td class="edittext" colspan="2">
        <b>Kundennummer in WAWI: [{$edit->oxuser__wwcustomernumber->value}]</b>
    </td>
                  
</tr>
[{/if}]
[{$smarty.block.parent}]

