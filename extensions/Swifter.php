<?php
/**
 * li3_swifter: e-mail library for lithium framework that uses Swiftmailer.
 *
 * @copyright  Copyright 2011, Tobias Sandelius (http://sandelius.org)
 * @license    http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_swifter\extensions;

use Swift_Mailer;
use Swift_Message;
use Swift_MailTransport;
use Swift_SmtpTransport;
use lithium\template\View;
use lithium\core\Libraries;

/**
 * The `Swifter` class allow us to quickly send e-mails using `Swiftmailer`.
 *
 * @link http://swiftmailer.org/
 * @uses lithium\template\View
 * @uses lithium\core\Libraries
 */
class Swifter extends \lithium\core\Adaptable {

    /**
     * Stores configurations for mailer adapters
     *
     * @var array
     */
    protected static $_configurations = array();

    /**
     * A stub method called by `_config()` which allows `Adaptable` subclasses to automatically
     * assign or auto-generate additional configuration data, once a configuration is first
     * accessed. This allows configuration data to be lazy-loaded from adapters or other data
     * sources.
     *
     * @param string $name The name of the configuration which is being accessed. This is the key
     *               name containing the specific set of configuration passed into `config()`.
     * @param array $config Contains the configuration assigned to `$name`. If this configuration is
     *              segregated by environment, then this will contain the configuration for the
     *              current environment.
     * @return array Returns the final array of settings for the given named configuration.
     */
    protected static function _initConfig($name, $config) {
        $defaults = array(
            'transport' => 'mail',
            'filters' => array(),
            'from' => null,
            'to' => null,
            'host' => 'smtp.example.org',
            'port' => 25,
            'username' => null,
            'password' => null
        );
        return (array) $config + $defaults;
    }

    /**
     * Get global configurations for Swiftmailer.
     *
     * @return void
     */
    public static function __init() {
        static::config(array('default' => Libraries::get('li3_swifter')));
    }

    /**
     * Send mail using `smtp` transport.
     *
     * @param array $options Message and smtp options.
     * @return boolean
     */
    public static function send($name, array $options = array()) {
        //$options += array('conditions' => null, 'strategies' => true);
        $settings = static::config();

        if (!isset($settings[$name])) {
            return false;
        }

        $transport = $settings[$name]['transport'];
        return static::$transport($options + $settings[$name]);

    }

    /**
     * Send mail using `smtp` transport.
     *
     * @param array $options Message and smtp options.
     * @return boolean
     */
    protected static function smtp(array $options) {
        $transport = Swift_SmtpTransport::newInstance($options['host'], $options['port'])
                   ->setUsername($options['username'])
                   ->setPassword($options['password']);

        $mailer = Swift_Mailer::newInstance($transport);
        return $mailer->send(static::_message($options));
    }

    /**
     * Send email using `mail` transport.
     *
     * @param array $options Message options.
     * @return boolean
     */
    protected static function mail(array $options) {
        $transport = Swift_MailTransport::newInstance();
        $mailer = Swift_Mailer::newInstance($transport);
        return $mailer->send(static::_message($options));
    }

    /**
     * Build the e-mail message we should send.
     *
     * @param array $options Message options.
     * @return object `Swift_Message`
     */
    protected static function _message(array $options) {
        $options += array(
            'cc' => false,
            'bcc' => false,
            'subject' => '',
            'body' => '',
            'template' => false,
            'data' => array() // Data to be available in the view
        );

        // Subject is always available is templates as `$subject`
        $options['subject'] = $options['subject'];

        if(!$options['to']) throw new \BadMethodCallException();

        $message = Swift_Message::newInstance($options['subject'])
                 ->setTo($options['to'])
                 ->setFrom($options['from']);

        if ($options['cc']) {
            if(is_array($options['cc'])) {
                foreach($options['cc'] as $key => $cc) {
                    if(!is_numeric($key)) $message->addCc($key, $cc);
                    else $message->addCc($cc);
                }
            } else {
                $message->addCc($options['cc']);
            }
        }
        if ($options['bcc']) {
            if(is_array($options['bcc'])) {
                foreach($options['bcc'] as $key => $bcc) {
                    if(!is_numeric($key)) $message->addBcc($key, $bcc);
                    else $message->addBcc($bcc);
                }
            } else {
                $message->addBcc($options['bcc']);
            }
        }

        if ($options['template']) {
            $view  = new View(array(
                'loader' => 'File',
                'renderer' => 'File',
                'paths' => array(
                    'template' => '{:library}/views/{:template}.mail.php'
                )
            ));

            $message->setBody($view->render('template', $options['data'], array(
                'template' => $options['template'],
                'layout' => false,
            )), 'text/html');
        }
        else {
            $message->setBody($options['body']);
        }

        return $message;
    }
}

?>
