<?php

declare(strict_types=1);

namespace BedFight\Form;

use BedFight\Core\BedFight;
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
            if (!($form instanceof PersistentForm)) {
                unset($this->forms[$formId]);
            }
        }
    }

    public function createSimpleForm(): SimpleForm {
        return new SimpleForm($this->nextId++);
    }

    public function createCustomForm(): CustomForm {
        return new CustomForm($this->nextId++);
    }

    public function createModalForm(): ModalForm {
        return new ModalForm($this->nextId++);
    }
}