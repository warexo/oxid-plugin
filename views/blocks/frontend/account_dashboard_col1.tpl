[{$smarty.block.parent}]
[{if $oViewConf->isAggroExtranetActive() && $oViewConf->isAggroExtranetTicketsActive()}]
[{if !$oViewConf->isResponsiveTheme()}]
<dl>
    <dt><a id="linkAccountTickets" href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=account_tickets" }]" rel="nofollow">[{ oxmultilang ident="ACCOUNT_TICKETS" }]</a></dt>
    <dd>[{oxmultilang ident="ACCOUNT_TICKETS" }]</dd>
</dl>
[{else}]
<div class="panel panel-default">
    <div class="panel-heading">
        <a id="linkAccountTickets" href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=account_tickets"}]">[{oxmultilang ident="ACCOUNT_TICKETS"}]</a>
        <a href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=account_tickets"}]" class="btn btn-default btn-xs pull-right">
            <i class="fa fa-arrow-right"></i>
        </a>
    </div>
    <div class="panel-body">[{oxmultilang ident="ACCOUNT_TICKETS"}]</div>
</div>
[{/if}]
[{/if}]

[{if $oViewConf->isAggroExtranetActive() && $oViewConf->isAggroExtranetLicensesActive()}]
[{if !$oViewConf->isResponsiveTheme()}]
<dl>
    <dt><a id="linkAccountTickets" href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=account_licenses" }]" rel="nofollow">[{ oxmultilang ident="ACCOUNT_LICENSES" }]</a></dt>
    <dd>[{oxmultilang ident="ACCOUNT_LICENSES" }]</dd>
</dl>
[{else}]
<div class="panel panel-default">
    <div class="panel-heading">
        <a id="linkAccountTickets" href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=account_licenses"}]">[{oxmultilang ident="ACCOUNT_LICENSES"}]</a>
        <a href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=account_licenses"}]" class="btn btn-default btn-xs pull-right">
            <i class="fa fa-arrow-right"></i>
        </a>
    </div>
    <div class="panel-body">[{oxmultilang ident="ACCOUNT_LICENSES"}]</div>
</div>
[{/if}]
[{/if}]

[{if $oViewConf->isAggroExtranetActive() && $oViewConf->isAggroExtranetSubscriptionContractsActive()}]
[{if !$oViewConf->isResponsiveTheme()}]
<dl>
    <dt><a id="linkAccountSubscriptionContracts" href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=account_subscription_contracts" }]" rel="nofollow">[{ oxmultilang ident="ACCOUNT_SUBSCRIPTION_CONTRACTS" }]</a></dt>
    <dd>[{oxmultilang ident="ACCOUNT_SUBSCRIPTION_CONTRACTS" }]</dd>
</dl>
[{else}]
<div class="panel panel-default">
    <div class="panel-heading">
        <a id="linkAccountSubscriptionContracts" href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=account_subscription_contracts"}]">[{oxmultilang ident="ACCOUNT_SUBSCRIPTION_CONTRACTS"}]</a>
        <a href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=account_subscription_contracts"}]" class="btn btn-default btn-xs pull-right">
            <i class="fa fa-arrow-right"></i>
        </a>
    </div>
    <div class="panel-body">[{oxmultilang ident="ACCOUNT_LICENSES"}]</div>
</div>
[{/if}]
[{/if}]