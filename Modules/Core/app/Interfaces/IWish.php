<?php

namespace Modules\Core\Interfaces;

use Mockery\MockInterface;

interface IWish
{
    public function wish(MockInterface $clientMock): MockInterface;
}
