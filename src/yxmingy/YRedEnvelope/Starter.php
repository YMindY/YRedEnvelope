<?php
namespace yxmingy\YRedEnvelope;
use pocketmine\Server;
abstract class Starter extends \pocketmine\plugin\PluginBase
{
  protected static $instance;
  //这个一定要在主类执行
  protected function assignInstance()
  {
    self::$instance=$this;
  }
  public static function getInstance():?object
  {
    return self::$instance;
  }
  public static function info(string $message):void
  {
    Server::getInstance()->getLogger()->info($message);
  }
  public static function notice(string $message):void
  {
    Server::getInstance()->getLogger()->notice($message);
  }
  public static function warning(string $message):void
  {
    Server::getInstance()->getLogger()->warning($message);
  }
  public static function error(string $message):void
  {
    Server::getInstance()->getLogger()->error($message);
  }
}