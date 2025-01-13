<?php

namespace Modules\Udemy\Services\Study;

use Illuminate\Contracts\Container\BindingResolutionException;
use Modules\Udemy\Exceptions\StudyClassTypeNotFound;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\Study\Interfaces\IStudy;

abstract class BaseStudy implements IStudy
{
    public function __construct(
        protected UserToken $userToken,
        protected CurriculumItem $curriculumItem
    ) {
    }

    abstract protected function getClass(): string;

    /**
     * @throws BindingResolutionException
     */
    final public function study(): void
    {
        $class = $this->getClass();

        if (!class_exists($class)) {
            throw new StudyClassTypeNotFound("Class $class does not exist");
        }

        app()->makeWith(
            $class,
            [
                'userToken' => $this->userToken,
                'curriculumItem' => $this->curriculumItem,
            ]
        )->study();
    }
}
