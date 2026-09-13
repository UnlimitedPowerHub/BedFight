<?php

declare(strict_types=1);

namespace BedFight\Form;

use BedFight\Core\BedFight;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ServerboundKnownPacksPacket;
use pocketmine\player\Player;

interface Form {
    public function getId(): int;
    public function getType(): string;
    public function encode(): array;
    public function handleResponse(Player $player, mixed $data): void;
}

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

class SimpleForm extends BaseForm {

    private string $content = "";
    private array $buttons = [];

    public function setContent(string $content): self {
        $this->content = $content;
        return $this;
    }

    public function addButton(string $text, ?string $image = null): self {
        $this->buttons[] = ['text' => $text, 'image' => $image ?? ''];
        return $this;
    }

    public function getType(): string {
        return "form";
    }

    public function encode(): array {
        return [
            'type' => 'form',
            'title' => $this->title,
            'content' => $this->content,
            'buttons' => $this->buttons
        ];
    }
}

class CustomForm extends BaseForm {

    private array $elements = [];

    public function addLabel(string $text): self {
        $this->elements[] = ['type' => 'label', 'text' => $text];
        return $this;
    }

    public function addInput(string $text, string $placeholder = "", string $default = ""): self {
        $this->elements[] = ['type' => 'input', 'text' => $text, 'placeholder' => $placeholder, 'default' => $default];
        return $this;
    }

    public function addDropdown(string $text, array $options, int $default = 0): self {
        $this->elements[] = ['type' => 'dropdown', 'text' => $text, 'options' => $options, 'default' => $default];
        return $this;
    }

    public function addToggle(string $text, bool $default = false): self {
        $this->elements[] = ['type' => 'toggle', 'text' => $text, 'default' => $default];
        return $this;
    }

    public function addSlider(string $text, int $min, int $max, int $step = 1, int $default = 0): self {
        $this->elements[] = ['type' => 'slider', 'text' => $text, 'min' => $min, 'max' => $max, 'step' => $step, 'default' => $default];
        return $this;
    }

    public function addStepSlider(string $text, array $steps, int $default = 0): self {
        $this->elements[] = ['type' => 'step_slider', 'text' => $text, 'steps' => $steps, 'default' => $default];
        return $this;
    }

    public function getType(): string {
        return "custom_form";
    }

    public function encode(): array {
        return [
            'type' => 'custom_form',
            'title' => $this->title,
            'content' => $this->elements
        ];
    }
}

class ModalForm extends BaseForm {

    private string $content = "";
    private string $button1 = "Confirm";
    private string $button2 = "Cancel";

    public function setContent(string $content): self {
        $this->content = $content;
        return $this;
    }

    public function setButton1(string $text): self {
        $this->button1 = $text;
        return $this;
    }

    public function setButton2(string $text): self {
        $this->button2 = $text;
        return $this;
    }

    public function getType(): string {
        return "modal";
    }

    public function encode(): array {
        return [
            'type' => 'modal',
            'title' => $this->title,
            'content' => $this->content,
            'button1' => $this->button1,
            'button2' => $this->button2
        ];
    }
}

interface PersistentForm extends Form {}