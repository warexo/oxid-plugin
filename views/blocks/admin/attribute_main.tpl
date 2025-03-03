[{if $edit->oxattribute__oxid->value}]
<tr>
    <td class="edittext" colspan="2">
        <b>Oxid: [{$edit->oxattribute__oxid->value}]<br>
        <b>WAWI Id: [{$edit->oxattribute__wwforeignid->value}] 
    </td>
                  
</tr>
[{/if}]
[{$smarty.block.parent}]
