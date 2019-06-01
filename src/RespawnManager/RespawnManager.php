<?php
namespace RespawnManager;

use Core\Core;
use Core\util\Util;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use TeleMoney\TeleMoney;
use UiLibrary\UiLibrary;

class RespawnManager extends PluginBase {
    public $pre = "§e•";
    public $pos = [];

    //public $pre = "§l§e[ §f시스템 §e]§r§e";
    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->saveResource("place.yml");
        $this->pdata = (new Config($this->getDataFolder() . "place.yml", Config::YAML))->getAll();
        $this->util = new Util(Core::getInstance());
        $this->ui = UiLibrary::getInstance();
        $this->money = TeleMoney::getInstance();
    }

    public function RespawnUI(Player $player) {
        /*$instance = new EffectInstance(Effect::getEffect(15), 100000, 100, false);
        $player->addEffect($instance);*/
        $form = $this->ui->ModalForm(function (Player $player, array $data) {
            $pos = new \pocketmine\level\Position($this->pos[$player->getName()][0], $this->pos[$player->getName()][1], $this->pos[$player->getName()][2], $this->getServer()->getLevelByName($this->pos[$player->getName()][3]));
            if ($data[0] == true && $this->money->getMoney($player->getName()) >= 100) {
                $player->teleport($pos);
                $player->sendMessage("{$this->pre} 100 테나로 부활하였습니다!");
                $this->money->reduceMoney($player->getName(), 100);
                unset($this->pos[$player->getName()]);
            } elseif ($data[0] == true && $this->money->getMoney($player->getName()) < 100) {
                $player->sendMessage("{$this->pre} 테나가 부족합니다..");
                $player->sendMessage("{$this->pre} 가장 가까운 마을에서 의식을 되찾았습니다.");
                $player->teleport($player->getSpawn());
                unset($this->pos[$player->getName()]);
            } else {
                $player->sendMessage("{$this->pre} 가장 가까운 마을에서 의식을 되찾았습니다.");
                $player->teleport($player->getSpawn());
                unset($this->pos[$player->getName()]);
            }
            $player->removeEffect(15);
        });
        $form->setTitle("Tele Respawn");
        $form->setContent("\n§f부활 하시겠습니까? 100테나가 소모됩니다.");
        $form->setButton1("§l[예]");
        $form->setButton2("§l[아니오]");
        $send = $form->sendToPlayer($player);
    }
}
