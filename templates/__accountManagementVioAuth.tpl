{if VIO_OAUTH_CLIENT_ID !== '' && VIO_OAUTH_CLIENT_SECRET !== '' && VIO_OAUTH_EMAIL_PLACEHOLDER !== ''}
    <dl>
        <dt>{lang}wcf.user.3rdparty.viooauth{/lang}</dt>
        <dd>
            <a href="{link controller='VioAuth'}{/link}" class="thirdPartyLoginButton vioAuthConnectLoginButton button">
                <img src="https://apiv1.vio-v.com/images/favicon.ico" alt="vioicon" style="width: 24px; height: 24px;"/>
                <span>{lang}wcf.user.3rdparty.viooauth.login{/lang}</span>
            </a>
        </dd>
    </dl>
{/if}