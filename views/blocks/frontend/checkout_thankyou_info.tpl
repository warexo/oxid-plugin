[{$smarty.block.parent}]

[{assign var="order" value=$oView->getOrder()}]

[{if $order->oxorder__oxpaymenttype->value == 'oxiddebitnote' && $sepaCreditorNumber && $sepaMandate }]
<div style="border: solid 1px #333;padding:10px;margin-bottom:10px">
    Gl&auml;ubiger-Identifikationsnummer: <b>[{$sepaCreditorNumber}]</b><br>
    SEPA-Mandat: <b>[{$sepaMandate}]</b>
</div>
[{/if}]