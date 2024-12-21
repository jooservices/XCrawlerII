<?php

namespace Modules\Udemy\Client\Dto;

use Modules\Core\Dto\BaseDto;

class CourseCurriculumItemDto extends BaseDto
{
    public function toArray(): array
    {
        return array_merge(
            (array) $this->data,
            [
                'class' => $this->data->_class,
                'asset_id' => isset($this->data->asset) ? $this->data->asset->id : null,
                'asset_type' => $this->data->asset->asset_type ?? null,
                'asset_filename' => $this->data->asset->filename ?? null,
                'asset_is_external' => $this->data->asset->is_external ?? null,
                'asset_status' => $this->data->asset->status ?? null,
                'asset_time_estimation' => $this->data->asset->time_estimation ?? null,
                'asset_title' => $this->data->asset->title ?? null,
                'asset_class' => $this->data->asset->_class ?? null,
            ]
        );
    }
}
