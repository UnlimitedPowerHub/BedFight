<?php

declare(strict_types=1);

namespace BedFight\Form;

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