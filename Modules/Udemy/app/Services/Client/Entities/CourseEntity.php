<?php

namespace Modules\Udemy\Services\Client\Entities;

/**
 * @property int $completion_ratio
 */
class CourseEntity extends AbstractBaseEntity
{
    public function getUrl(): string
    {
        return $this->data->url;
    }

    public function toArray(): array
    {
        return array_merge((array) $this->data, ['class' => $this->data->_class]);
    }

    public function isCompleted(): bool
    {
        return $this->data->completion_ratio === 100;
    }
}
