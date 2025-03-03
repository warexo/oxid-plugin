[{if $edit->oxdeliveryset__oxid->value}]
<tr>
    <td class="edittext" colspan="2">
        <b>Oxid (WAWI-Ident): [{$edit->oxdeliveryset__oxid->value}]<br>
    </td>
                  
</tr>
[{/if}]
[{$smarty.block.parent}]