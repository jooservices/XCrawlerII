<?php

namespace Modules\Core\Zeus\Interfaces;

use Mockery\MockInterface;

interface IWish
{
    public function wish(MockInterface $clientMock): MockInterface;
}
