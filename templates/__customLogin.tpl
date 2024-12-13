{if $__userAuthConfig->canRegister}
    {capture assign='contentDescription'}{lang}wcf.user.login.noAccount{/lang}{/capture}
{/if}

{include file='authFlowHeader'}

{if $forceLoginRedirect}
    <woltlab-core-notice type="info">{lang}wcf.user.login.forceLogin{/lang}</woltlab-core-notice>
{/if}

{if !$errorField|empty && $errorField == 'cookie'}
    <woltlab-core-notice type="error">{lang}wcf.user.login.error.cookieRequired{/lang}</woltlab-core-notice>
{else}
    {include file='shared_formError'}
{/if}

<form id="loginForm" method="post" action="{$loginController}">
    {include file='thirdPartySsoButtons'}
</form>

{include file='authFlowFooter'}