[{$smarty.block.parent}]
[{if $oViewConf->isAggroExtranetActive() && $oViewConf->isAggroExtranetTicketsActive()}]
<li class="list-group-item [{if $active_link == "tickets"}]active[{/if}]"><a href="[{ oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=account_tickets" }]" rel="nofollow">[{ oxmultilang ident="ACCOUNT_TICKETS" }]</a></li>
[{/if}]
[{if $oViewConf->isAggroExtranetActive() && $oViewConf->isAggroExtranetLicensesActive()}]
<li class="list-group-item [{if $active_link == "licenses"}]active[{/if}]"><a href="[{ oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=account_licenses" }]" rel="nofollow">[{ oxmultilang ident="ACCOUNT_LICENSES" }]</a></li>
[{/if}]
[{if $oViewConf->isAggroExtranetActive() && $oViewConf->isAggroExtranetSubscriptionContractsActive()}]
<li class="list-group-item [{if $active_link == "subscription_contracts"}]active[{/if}]">
    <a href="[{ oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=account_subscription_contracts" }]" rel="nofollow">[{ oxmultilang ident="ACCOUNT_SUBSCRIPTION_CONTRACTS" }]</a>
</li>
[{/if}]