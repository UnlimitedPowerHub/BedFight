<?php

declare(strict_types=1);

namespace BedFight\Form;

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