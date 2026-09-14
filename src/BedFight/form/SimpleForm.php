<?php

declare(strict_types=1);

namespace BedFight\Form;

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