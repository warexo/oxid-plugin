[{if $edit->oxmanufacturers__oxid->value}]
<tr>
    <td class="edittext" colspan="2">
        <b>Oxid: [{$edit->oxmanufacturers__oxid->value}]<br>
        <b>WAWI Id: [{$edit->oxmanufacturers__wwforeignid->value}] 
    </td>
                  
</tr>
[{/if}]
[{$smarty.block.parent}]
