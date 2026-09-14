<?php

declare(strict_types=1);

namespace BedFight\Form;

use BedFight\Core\BedFight;
use BedFight\FormAPI\SimpleForm;
use BedFight\FormAPI\CustomForm;
use BedFight\FormAPI\ModalForm;
use BedFight\FormAPI\Form;
use pocketmine\player\Player;

class FormManager {

    private BedFight $plugin;
    private array $forms = [];
    private int $nextId = 100000;

    public function __construct(BedFight $plugin) {
        $this->plugin = $plugin;
    }

    public function registerForm(Form $form): int {
        $id = $form->getId();
        $this->forms[$id] = $form;
        return $id;
    }

    public function unregisterForm(int $id): void {
        unset($this->forms[$id]);
    }

    public function getForm(int $id): ?Form {
        return $this->forms[$id] ?? null;
    }

    public function handleResponse(Player $player, int $formId, mixed $data): void {
        $form = $this->forms[$formId] ?? null;
        if ($form !== null) {
            $form->handleResponse($player, $data);
            unset($this->forms[$formId]);
        }
    }

    public function createSimpleForm(?callable $callback = null): SimpleForm {
        return new SimpleForm($callback);
    }

    public function createCustomForm(?callable $callback = null): CustomForm {
        return new CustomForm($callback);
    }

    public function createModalForm(?callable $callback = null): ModalForm {
        return new ModalForm($callback);
    }
}