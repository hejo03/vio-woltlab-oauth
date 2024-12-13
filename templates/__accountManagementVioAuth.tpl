{if VIO_OAUTH_CLIENT_ID !== '' && VIO_OAUTH_CLIENT_SECRET !== '' && VIO_OAUTH_EMAIL_PLACEHOLDER !== ''}
    <dl>
        <dt>{lang}wcf.user.3rdparty.viooauth{/lang}</dt>
        <dd>
            <a href="{link controller='OpenIDConnectAuth'}{/link}" class="thirdPartyLoginButton openIDConnectLoginButton button">
                {if OPENID_ICON}
                    {icon name=OPENID_ICON size=24}
                {else}
                    {icon name='link' size=24}
                {/if}

                <span>{lang}wcf.user.3rdparty.viooauth.login{/lang}</span>
            </a>
        </dd>
    </dl>
{/if}