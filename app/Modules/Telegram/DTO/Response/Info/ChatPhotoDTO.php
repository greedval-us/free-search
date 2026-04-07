<?php

namespace App\Modules\Telegram\DTO\Response\Info;

class ChatPhotoDTO
{
    public object|array|null $photo;

    public function __construct($data)
    {
        $this->photo = (is_array($data) || is_object($data)) ? $data : null;
    }
}
