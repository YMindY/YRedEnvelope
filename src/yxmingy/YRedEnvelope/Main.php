<?php
/*
  Date: 2018.12.31
  Author: xMing
  Editor: Quoda
  Poem:
    手持两把锟斤拷，口中疾呼烫烫烫。
    脚踏千朵屯屯屯，笑看万物锘锘锘。
  Mantra: 高内聚，低耦合。
*/
namespace yxmingy\YRedEnvelope;
use pocketmine\utils\Config;
use onebone\economyapi\EconomyAPI;
use pocketmine\Server;
use pocketmine\command\{Command,CommandSender};
class Main extends Starter
{
  public function onLoad()
  {
    self::assignInstance();
    self::info("[YRedEnvelope] is Loading...");
  }
  public function onEnable()
  {
    $this->conf = new Config($this->getDataFolder().'/res.yml',Config::YAML,array());
    /*
    (num)=>
    [
      type=>fair/luck
      pieces=>
      [
        (num)=>(awardnum),
        ......
      ]
      getters=>[(playernames),......]
    ],
    ......
    */
    self::notice("[YRedEnvelope] is Enabled by xMing!");
  }
  public function onDisable()
  {
    self::warning("[YRedEnvelope] is Turned Off.");
  }
  private $res;
  public function onCommand(CommandSender $sender, Command $command, $label, array $args):bool
  {
    if($command->getName() != "hb") return false;
    if(!isset($args[0])) return false;
    $eapi = EconomyAPI::getInstance();
    $server = Server::getInstance();
    if(is_numeric($args[0]))
    {
      if(!$this->conf->exists($args[0]))
      {
        $sender->sendMessage("没有序号为".$args[0]."的红包!");
        return true;
      }
      $hb = $this->conf->get($args[0]);
      if(in_array($sender->getName(),$hb['getters']))
      {
        $sender->sendMessage("这个红包你已经领过了!");
        return true;
      }
      $p = $hb['pieces'];
      for($i=0;$i<count($p);$i++)
      {
        if(isset($p[$i]))
        {
          $eapi->addMoney($sender->getName(),$p[$i]);
          $sender->sendMessage("领取成功!你领到了{$p[$i]}枚游戏币");
          $get = $p[$i];
          unset($p[$i]);
          $hb['pieces'] = array_merge($p);
          $hb['getters'][] = $sender->getName();
          $this->conf->set($args[0],$hb);
          if(empty($p))
          {
            $this->conf->remove($args[0]);
            $server->broadcastMessage($sender->getName()."从[{$args[0]}]号红包中领到了{$get}游戏币,本红包已领完!");
          }else{
            $server->broadcastMessage($sender->getName()."从[{$args[0]}]号红包中领到了{$get}游戏币,本红包剩余".count($p)."份可领取!");
          }
          $this->conf->save();
          return true;
        }
      }
    }
    $name = $sender->getName();
    $wallet = $eapi->MyMoney($name);
    if($args[0]=="fair")
    {
      if(count($args)<3 || !is_numeric($args[1]) || !is_numeric($args[2]))
      {
        $sender->sendMessage("用法:/hb fair [份数] [单份金额]");
        return true;
      }
      $total = $args[2] * $args[1];
      if($wallet < $total)
      {
        if($sender instanceof \pocketmine\Player && !$sender->isOp())
        {
          $sender->sendMessage("少年你钱不够{$total}啊");
          return true;
        }
      }
      if($args[1] > 50)
      {
        $sender->sendMessage("你这份数也太多了吧! 你是魔鬼么!？");
        return true;
      }
      if($args[2] < 0.25)
      {
        $sender->sendMessage("单个红包太小了! 你个秀儿!？");
        return true;
      }
      if($sender instanceof \pocketmine\Player && !$sender->isOp()) $eapi->reduceMoney($name,$total);
      $hb = array(
      'type'=>'fair',
      'pieces'=>[],
      'getters'=>[]
      );
      for($i=0;$i<$args[1];$i++)
      {
        $hb['pieces'][$i] = $args[2];
      }
      $hbs = $this->conf->getAll();
      //补空发红包
      for($order=0;$order<=count($hbs);$order++)
      {
        if(!isset($hbs[$order]))
        {
          $hbs[$order]=$hb;
          break;
        }
      }
      $this->conf->setAll($hbs);
      $this->conf->save();
      $sender->sendMessage("发红包成功!共{$args[1]}份，每份{$args[2]}游戏币，共计{$total}游戏币");
      $server->broadcastMessage($name."发了{$total}游戏币的普通红包，输入/hb {$order}进行领取!");
    }elseif($args[0]=="luck"){
      if(count($args)<3 || !is_numeric($args[1]) || !is_numeric($args[2]))
      {
        $sender->sendMessage("用法:/hb luck [份数] [总金额]");
        return true;
      }
      $total = $args[2];
      if($wallet < $total)
      {
        if($sender instanceof \pocketmine\Player && !$sender->isOp())
        {
          $sender->sendMessage("少年你钱不够{$total}啊");
          return true;
        }
      }
      if($sender instanceof \pocketmine\Player && $sender->isOp()) $eapi->reduceMoney($name,$total);
      $hb = array(
      'type'=>'luck',
      'pieces'=>[],
      'getters'=>[]
      );
      if($args[1] > 50)
      {
        $sender->sendMessage("你这份数也太多了吧! 你是魔鬼么!？");
        return true;
      }
      $awards=array();
      for($i=0;$i<$args[1];$i++)
      {
        $awards[$i]=mt_rand(2,6);
      }
      for($i=0;$i<$args[1];$i++)
      {
        $hb['pieces'][$i] = (double)($awards[$i])/(double)(array_sum($awards))*$total;
        if($hb['pieces'][$i] < 0.25)
        {
          $sender->sendMessage("单个红包太小了! 你是魔鬼么?");
          $eapi->addMoney($name,$total);
          return true;
        }
      }
      $hbs = $this->conf->getAll();
      for($order=0;$order<=count($hbs);$order++)
      {
        if(!isset($hbs[$order]))
        {
          $hbs[$order]=$hb;
          break;
        }
      }
      $this->conf->setAll($hbs);
      $this->conf->save();
      $sender->sendMessage("发随机红包成功!共{$args[1]}份，共计{$total}游戏币");
      $server->broadcastMessage($name."发了{$total}游戏币的随机红包，输入/hb {$order}进行领取!");
    }else{
      $sender->sendMessage("用法:/hb [红包序号]");
    }
    return true;
  }
}