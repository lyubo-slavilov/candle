<?php
namespace Spark;

use Candle\Config;

/**
 * Simple candle mail service
 * Which works with PHPMailer
 * 
 * @see http://phpmailer.sourceforge.net/ or PHPMailer README file
 * 
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
class Mailer {

    private static $cfg;
    
    /**
     * Factory for the PHPMailer instance.
     * 
     * This method also reads the configuration for a specific SMTP settings
     * @return \PHPMailer
     */
    static public function get()
    {
        require_once __DIR__ . '/phpmailer/class.phpmailer.php';

        if (!isset(self::$cfg)) {
            self::$cfg = Config::get('spark');
        }


        $mailer = new \PHPMailer();

        $mailer->IsSMTP();

        $mailer->SMTPAuth   = self::$cfg->smtpauth;
        $mailer->SMTPSecure = self::$cfg->smtpsecure;
        $mailer->Host       = self::$cfg->host;
        $mailer->Port       = self::$cfg->port;
        $mailer->Username   = self::$cfg->username;
        $mailer->Password   = self::$cfg->password;
        $mailer->CharSet = 'utf-8';
        return $mailer;

    }

}