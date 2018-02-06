<?php

namespace Crevasse;

class Report extends Command
{
    public static function setReport($message)
    {
        switch (true) {
            case self::$cli_mode === true:
                print Color::YELLOW.
                    '[Converter CLI] ['.date('H:i:s').'] >> '.
                    "[{$message}]\r\n".
                    Color::BG_DEFAULT;
                sleep(1);
                break;
            case self::$cli_mode === false:
                error_log('[Converter] ['.date('H:i:s').'] >> '.
                    "[message:{$message}]\r\n");
                break;
            default:
        }
    }
}
