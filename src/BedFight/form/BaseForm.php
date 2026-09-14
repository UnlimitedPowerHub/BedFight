<?php

declare(strict_types=1);

namespace BedFight\Form;

use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\player\Player;

abstract class BaseForm implements Form {

    protected int $id;
    protected string $title = "";
    protected ?callable $callback = null;

    public function __construct(int $id = 0) {
        $this->id = $id ?: self::generateId();
    }

    public static function generateId(): int {
        return random_int(100000, 999999);
    }

    public function getId(): int {
        return $this->id;
    }

    public function setTitle(string $title): self {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function setCallback(callable $callback): self {
        $this->callback = $callback;
        return $this;
    }

    public function send(Player $player): void {
        $pk = new ModalFormRequestPacket();
        $pk->formId = $this->id;
        $pk->formData = json_encode($this->encode());
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public function handleResponse(Player $player, mixed $data): void {
        if ($this->callback !== null) {
            ($this->callback)($player, $data);
        }
    }

    abstract public function getType(): string;
    abstract public function encode(): array;
}