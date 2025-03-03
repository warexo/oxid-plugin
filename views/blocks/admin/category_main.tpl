[{if $edit->oxcategories__oxid->value}]
<tr>
    <td class="edittext" colspan="2">
        <b>Oxid: [{$edit->oxcategories__oxid->value}]<br>
        <b>WAWI Id: [{$edit->oxcategories__wwforeignid->value}] 
    </td>
                  
</tr>
[{/if}]
[{$smarty.block.parent}]
