<?php

namespace wcf\system\exception;

use wcf\system\box\BoxHandler;
use wcf\system\session\SessionHandler;
use wcf\system\WCF;
use wcf\system\WCFACP;

class VioOAuthException extends UserException
{
    /**
     * Shows a styled page with the given error message.
     */
    public function show()
    {
        if (!\class_exists(WCFACP::class, false)) {
            BoxHandler::disablePageLayout();
        }
        SessionHandler::getInstance()->disableTracking();

        $name = static::class;
        $exceptionClassName = \mb_substr($name, \mb_strrpos($name, '\\') + 1);

        WCF::getTPL()->assign([
            'name' => $name,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'message' => $this->getMessage(),
            'stacktrace' => $this->getTraceAsString(),
            'templateName' => 'userException',
            'templateNameApplication' => 'wcf',
            'exceptionClassName' => $exceptionClassName,
        ]);
        WCF::getTPL()->display('userException');
    }
}
