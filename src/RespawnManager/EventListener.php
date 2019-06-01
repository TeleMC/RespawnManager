<?php
namespace RespawnManager;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class EventListener implements Listener {
    public function __construct(RespawnManager $plugin) {
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $ev) {
        $name = mb_strtolower($ev->getPlayer()->getName());
        if (!file_exists("{$this->plugin->getServer()->getDataPath()}players/{$name}.dat"))
            $ev->getPlayer()->teleport(new Vector3(0, 76, 1000));
        unset($this->plugin->pos[$ev->getPlayer()->getName()]);
    }

    public function onQuit(PlayerQuitEvent $ev) {
        unset($this->plugin->pos[$ev->getPlayer()->getName()]);
    }

    public function onMove(PlayerMoveEvent $ev) {
        $player = $ev->getPlayer();
        if ($player->getY() <= -5) {
            $player->setHealth($player->getMaxHealth());
            $this->plugin->util->setMp($ev->getPlayer()->getName(), $this->plugin->util->getMaxMp($ev->getPlayer()->getName()));
            $player->setFood(20);
            $player->teleport($player->getSpawn());
            $player->removeAllEffects();
            Server::getInstance()->getPluginManager()->callEvent(new PlayerDeathEvent($player, $player->getInventory()->getContents()));
        }
    }

    public function onDeath(PlayerDeathEvent $ev) {
        $player = $ev->getPlayer();
        $pos = $ev->getPlayer()->asPosition();
        $this->plugin->pos[$ev->getPlayer()->getName()] = [$pos->x, $pos->y, $pos->z, $pos->getLevel()->getName()];
        $spawn = [];
        foreach ($this->plugin->pdata as $place => $pos) {
            $pos = explode(":", $pos);
            $level = $player->getServer()->getLevelByName($pos[3]);
            $pos = new Vector3($pos[0], $pos[1], $pos[2]);
            if ($player->getLevel()->getName() == $level->getName()) {
                $spawn[$place] = $player->distance($pos);
            }
        }
        $spawnPoint = array_search(min($spawn), $spawn);
        $spawnPoint = explode(":", $this->plugin->pdata[$spawnPoint]);
        $player->setSpawn(new Vector3((float) $spawnPoint[0], (float) $spawnPoint[1], (float) $spawnPoint[2]));
    }

    public function onPacketReceived(DataPacketReceiveEvent $ev) {
        $pk = $ev->getPacket();
        if ($pk instanceof PlayerActionPacket && $pk->action == PlayerActionPacket::ACTION_RESPAWN) {
            if (isset($this->plugin->pos[$ev->getPlayer()->getName()])) {
                //$instance = new EffectInstance(Effect::getEffect(15), 100000, 10, false);
                //$ev->getPlayer()->addEffect($instance);
                $this->plugin->getScheduler()->scheduleDelayedTask(
                        new class ($this->plugin, $ev->getPlayer(), $this->plugin->pos[$ev->getPlayer()->getName()]) extends Task {
                            public function __construct(RespawnManager $plugin, Player $player, array $pos) {
                                $this->plugin = $plugin;
                                $this->player = $player;
                            }

                            public function onRun($currentTick) {
                                $this->plugin->RespawnUI($this->player);
                            }
                        }, 1);
            }
        }
    }

    /*public function onRespawn(PlayerRespawnEvent $ev){
      if(isset($this->plugin->pos[$ev->getPlayer()->getName()])){
        $instance = new EffectInstance(Effect::getEffect(15), 100000, 10, false);
        $ev->getPlayer()->addEffect($instance);
        $this->plugin->getScheduler()->scheduleDelayedTask(
          new class ($this->plugin, $ev->getPlayer(), $this->plugin->pos[$ev->getPlayer()->getName()]) extends Task{
            public function __construct(RespawnManager $plugin, Player $player, array $pos){
              $this->plugin = $plugin;
              $this->player = $player;
            }
            public function onRun($currentTick){
              $this->plugin->RespawnUI($this->player);
            }
          }, 1);
      }
    }*/

    /*public function onDamage(EntityDamageEvent $ev){
      if($ev->getEntity() instanceof Player){
        if($ev->isCancelled()) return;
        if($ev->getBaseDamage() >= $ev->getEntity()->getHealth()){
          $ev->setCancelled(true);
          $player = $ev->getEntity();
          $this->vector[$player->getName()] = $player->asVector3();
          $ev->setBaseDamage(0);
          $player->setHealth($player->getMaxHealth());
          $this->plugin->util->setMp($player->getName(), $this->plugin->util->getMaxMp($player->getName()));
          $player->setFood(20);
          $player->teleport($player->getSpawn());
          $player->removeAllEffects();
          Server::getInstance()->getPluginManager()->callEvent(new PlayerDeathEvent($player, $player->getInventory()->getContents()));
          $form = $this->plugin->ui->ModalForm(function(Player $player, array $data){
            if($data[0] == true && $this->plugin->money->getMoney($player->getName()) >= 100){
              $player->teleport($this->vector[$player->getName()]);
              $player->sendMessage("{$this->plugin->pre} 100 테나로 부활하였습니다!");
              $this->plugin->money->reduceMoney($player->getName(), 100);
            }elseif($data[0] == true && $this->plugin->money->getMoney($player->getName()) < 100){
              $player->sendMessage("{$this->plugin->pre} 테나가 부족합니다.");
            }else{
              return false;
            }
          });
          $form->setTitle("Tele Respawn");
          $form->setContent("\n§f부활 하시겠습니까? 100테나가 소모됩니다.");
          $form->setButton1("§l[예]");
          $form->setButton2("§l[아니오]");
          $form->sendToPlayer($player);
        }
      }
    }*/
}
