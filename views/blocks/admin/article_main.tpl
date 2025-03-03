[{if $edit->oxarticles__oxid->value}]
<tr>
    <td class="edittext" colspan="2">
        <b>Oxid: [{$edit->oxarticles__oxid->value}]<br>
        <b>WAWI Id: [{$edit->oxarticles__wwforeignid->value}] 
    </td>
                  
</tr>
[{/if}]
[{$smarty.block.parent}]
