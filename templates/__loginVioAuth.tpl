{if VIO_OAUTH_CLIENT_ID !== '' && VIO_OAUTH_CLIENT_SECRET !== '' && VIO_OAUTH_EMAIL_PLACEHOLDER !== ''}
    <li id="vioAuthConnectAuth" class="thirdPartyLogin">
        <a href="{link controller='OAuthLogin'}{/link}" class="button thirdPartyLoginButton vioAuthConnectLoginButton">
            {if OPENID_ICON}
                {icon name=OPENID_ICON size=24}
            {else}
                {icon name='link' size=24}
            {/if}
            <span>{lang}wcf.user.3rdparty.viooauth.login{/lang}</span>
        </a>
    </li>
{/if}
