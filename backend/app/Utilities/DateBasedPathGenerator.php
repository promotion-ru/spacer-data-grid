<?php

namespace App\Utilities;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class DateBasedPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->getBasePath($media) . '/';
    }

    protected function getBasePath(Media $media): string
    {
        $date = $media->created_at ?? now();

        $year = $date->format('Y');
        $month = $date->format('m');
        $day = $date->format('d');

        $modelName = strtolower(class_basename($media->model_type));

        return "{$modelName}/{$year}/{$month}/{$day}/{$media->id}";
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getBasePath($media) . '/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getBasePath($media) . '/responsive-images/';
    }
}
